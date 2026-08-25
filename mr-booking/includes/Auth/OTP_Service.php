<?php
/**
 * One-time-password issuing & verification (SMS).
 *
 * Codes are never stored in clear text; only a keyed hash lives in a
 * short-lived transient together with the attempt counter.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Auth;

use MRBooking\Helpers;
use MRBooking\Notifications\SMS\SMS_Manager;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class OTP_Service {

	public const CODE_LENGTH     = 5;
	public const TTL_SECONDS     = 5 * MINUTE_IN_SECONDS;
	public const RESEND_SECONDS  = 60;
	public const MAX_ATTEMPTS    = 5;
	public const MAX_PER_PHONE_H = 5;
	public const MAX_PER_IP_H    = 20;
	public const TOKEN_TTL       = 10 * MINUTE_IN_SECONDS;

	private static function key( string $prefix, string $subject ): string {
		return 'mrb_' . $prefix . '_' . md5( $subject );
	}

	private static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return (string) apply_filters( 'mr_booking_otp_client_ip', $ip );
	}

	/**
	 * Sliding counter with 1-hour window. Returns false when the cap is exceeded.
	 */
	private static function bump_counter( string $key, int $cap ): bool {
		$count = (int) get_transient( $key );
		if ( $count >= $cap ) {
			return false;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );

		return true;
	}

	public static function can_send(): bool {
		return ! empty( Settings::get_value( 'sms_enabled' ) );
	}

	/**
	 * Issue and send a code.
	 *
	 * @return array{ok:bool,error?:string,resend_after?:int,expires_in?:int,retry_after?:int}
	 */
	public static function request( string $phone ): array {
		$phone = Helpers::sanitize_mobile( $phone );
		if ( ! Helpers::is_valid_mobile( $phone ) ) {
			return array( 'ok' => false, 'error' => __( 'شماره موبایل معتبر نیست.', 'mr-booking' ) );
		}

		if ( ! self::can_send() ) {
			return array( 'ok' => false, 'error' => __( 'ارسال پیامک در حال حاضر فعال نیست. لطفاً با ما تماس بگیرید.', 'mr-booking' ) );
		}

		$otp_key  = self::key( 'otp', $phone );
		$existing = get_transient( $otp_key );
		if ( is_array( $existing ) && ! empty( $existing['sent_at'] ) ) {
			$since = time() - (int) $existing['sent_at'];
			if ( $since < self::RESEND_SECONDS ) {
				return array(
					'ok'          => false,
					'retry_after' => self::RESEND_SECONDS - $since,
					'error'       => sprintf(
						/* translators: %s: seconds */
						__( 'کد قبلی هنوز معتبر است. %s ثانیه دیگر می‌توانید کد جدید بگیرید.', 'mr-booking' ),
						Helpers::to_persian_digits( (string) ( self::RESEND_SECONDS - $since ) )
					),
				);
			}
		}

		if ( ! self::bump_counter( self::key( 'otp_ph', $phone ), self::MAX_PER_PHONE_H ) ) {
			return array( 'ok' => false, 'error' => __( 'تعداد درخواست کد برای این شماره زیاد است. یک ساعت دیگر تلاش کنید.', 'mr-booking' ) );
		}
		$ip = self::client_ip();
		if ( $ip && ! self::bump_counter( self::key( 'otp_ip', $ip ), self::MAX_PER_IP_H ) ) {
			return array( 'ok' => false, 'error' => __( 'درخواست‌های زیادی از این دستگاه ثبت شده. کمی بعد تلاش کنید.', 'mr-booking' ) );
		}

		$code = self::generate_code();

		/**
		 * Filter the OTP message. {otp_code} and {business_name} are replaced.
		 */
		$template = (string) Settings::get_value( 'tpl_sms_otp', '' );
		if ( '' === trim( $template ) ) {
			$template = Settings::defaults()['tpl_sms_otp'];
		}
		$message = Helpers::replace_vars(
			$template,
			array(
				'otp_code'      => $code,
				'business_name' => (string) Settings::get_value( 'business_name', get_bloginfo( 'name' ) ),
				'minutes'       => (string) ( self::TTL_SECONDS / MINUTE_IN_SECONDS ),
			)
		);

		/**
		 * Short-circuit OTP delivery (e.g. a custom channel). Return an array with 'ok'
		 * to skip the SMS provider.
		 *
		 * @param array|null $result
		 * @param string     $phone
		 * @param string     $code
		 * @param string     $message
		 */
		$sent = apply_filters( 'mr_booking_otp_send', null, $phone, $code, $message );
		if ( ! is_array( $sent ) ) {
			$sent = SMS_Manager::send_otp( $phone, $code, $message );
		}

		if ( empty( $sent['ok'] ) ) {
			$error = __( 'ارسال پیامک ناموفق بود. لطفاً دوباره تلاش کنید.', 'mr-booking' );
			// Site managers get the provider's reason so misconfiguration is obvious.
			if ( current_user_can( 'manage_options' ) && ! empty( $sent['error'] ) ) {
				$error .= ' (' . sanitize_text_field( (string) $sent['error'] ) . ')';
			}
			return array( 'ok' => false, 'error' => $error );
		}

		set_transient(
			$otp_key,
			array(
				'hash'     => self::hash( $phone, $code ),
				'expires'  => time() + self::TTL_SECONDS,
				'sent_at'  => time(),
				'attempts' => 0,
			),
			self::TTL_SECONDS
		);

		return array(
			'ok'           => true,
			'resend_after' => self::RESEND_SECONDS,
			'expires_in'   => self::TTL_SECONDS,
		);
	}

	/**
	 * Verify a code. On success the OTP is consumed and a short-lived
	 * "verified" token is issued (used to complete a new profile).
	 *
	 * @return array{ok:bool,error?:string,token?:string}
	 */
	public static function verify( string $phone, string $code ): array {
		$phone = Helpers::sanitize_mobile( $phone );
		$code  = preg_replace( '/\D/', '', Helpers::to_english_digits( $code ) ) ?? '';

		if ( ! Helpers::is_valid_mobile( $phone ) || strlen( $code ) !== self::CODE_LENGTH ) {
			return array( 'ok' => false, 'error' => __( 'کد واردشده معتبر نیست.', 'mr-booking' ) );
		}

		$otp_key = self::key( 'otp', $phone );
		$data    = get_transient( $otp_key );
		if ( ! is_array( $data ) || empty( $data['hash'] ) || time() > (int) ( $data['expires'] ?? 0 ) ) {
			delete_transient( $otp_key );
			return array( 'ok' => false, 'error' => __( 'کد منقضی شده است. کد جدید درخواست کنید.', 'mr-booking' ) );
		}

		if ( ! hash_equals( (string) $data['hash'], self::hash( $phone, $code ) ) ) {
			$data['attempts'] = (int) ( $data['attempts'] ?? 0 ) + 1;
			if ( $data['attempts'] >= self::MAX_ATTEMPTS ) {
				delete_transient( $otp_key );
				return array( 'ok' => false, 'error' => __( 'تعداد تلاش‌های اشتباه زیاد شد. کد جدید درخواست کنید.', 'mr-booking' ) );
			}
			set_transient( $otp_key, $data, max( 1, (int) $data['expires'] - time() ) );
			$left = self::MAX_ATTEMPTS - $data['attempts'];
			return array(
				'ok'    => false,
				'error' => sprintf(
					/* translators: %s: attempts left */
					__( 'کد اشتباه است. %s تلاش دیگر باقی مانده.', 'mr-booking' ),
					Helpers::to_persian_digits( (string) $left )
				),
			);
		}

		delete_transient( $otp_key );

		$token = wp_generate_password( 32, false, false );
		set_transient( self::key( 'otp_ok', $phone ), self::hash( $phone, $token ), self::TOKEN_TTL );

		return array( 'ok' => true, 'token' => $token );
	}

	/**
	 * Validate (and optionally consume) a verified token.
	 */
	public static function check_token( string $phone, string $token, bool $consume = false ): bool {
		$phone = Helpers::sanitize_mobile( $phone );
		$key   = self::key( 'otp_ok', $phone );
		$hash  = get_transient( $key );
		if ( ! is_string( $hash ) || '' === $token || ! hash_equals( $hash, self::hash( $phone, $token ) ) ) {
			return false;
		}
		if ( $consume ) {
			delete_transient( $key );
		}

		return true;
	}

	private static function generate_code(): string {
		$max = (int) str_repeat( '9', self::CODE_LENGTH );
		$min = (int) ( '1' . str_repeat( '0', self::CODE_LENGTH - 1 ) );

		return (string) wp_rand( $min, $max );
	}

	private static function hash( string $phone, string $secret ): string {
		return hash_hmac( 'sha256', $phone . '|' . $secret, wp_salt( 'auth' ) );
	}
}
