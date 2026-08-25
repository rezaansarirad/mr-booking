<?php
/**
 * SMS manager / factory.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\SMS;

use MRBooking\Helpers;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class SMS_Manager {

	private const CREDIT_TTL = 900;

	/**
	 * @return array<string, Provider_Interface>
	 */
	public static function providers(): array {
		$providers = array(
			'kavenegar'   => new Kavenegar_Provider(),
			'melipayamak' => new Melipayamak_Provider(),
			'smsir'       => new Smsir_Provider(),
		);

		/**
		 * Filter SMS providers.
		 *
		 * @param array<string, Provider_Interface> $providers Providers.
		 */
		return apply_filters( 'mr_booking_sms_providers', $providers );
	}

	/**
	 * @return array{ok:bool,response?:string,error?:string}
	 */
	public static function send( string $to, string $message ): array {
		$settings = Settings::get();
		if ( empty( $settings['sms_enabled'] ) ) {
			return array( 'ok' => false, 'error' => 'SMS disabled' );
		}

		$to = Helpers::sanitize_mobile( $to );
		if ( ! Helpers::is_valid_mobile( $to ) ) {
			return array( 'ok' => false, 'error' => 'Invalid mobile' );
		}

		$slug      = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
		$providers = self::providers();
		$provider  = $providers[ $slug ] ?? null;

		if ( ! $provider ) {
			return array( 'ok' => false, 'error' => 'Unknown provider' );
		}

		$result = $provider->send( $to, $message, $settings );

		/**
		 * Fires after SMS attempt.
		 */
		do_action( 'mr_booking_sms_sent', $to, $message, $result );

		return $result;
	}

	/**
	 * Deliver a one-time code: template/verify API when the provider supports it and a
	 * template is configured, otherwise the plain message. Every attempt is logged so the
	 * admin can see the provider's answer under notification logs.
	 *
	 * @return array{ok:bool,error?:string,method?:string}
	 */
	public static function send_otp( string $to, string $code, string $message ): array {
		$settings = Settings::get();
		$slug     = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
		$provider = self::providers()[ $slug ] ?? null;
		$result   = null;

		if ( $provider && method_exists( $provider, 'send_otp' ) && '' !== trim( (string) ( $settings['sms_otp_template'] ?? '' ) ) ) {
			$result = $provider->send_otp( Helpers::sanitize_mobile( $to ), $code, $settings );
		}

		if ( ! is_array( $result ) ) {
			$result           = self::send( $to, $message );
			$result['method'] = 'sms/send';
		}

		global $wpdb;
		$wpdb->insert(
			Helpers::table( 'notification_logs' ),
			array(
				'booking_id'        => null,
				'customer_id'       => null,
				'channel'           => 'sms',
				'recipient'         => Helpers::sanitize_mobile( $to ),
				'subject'           => 'otp',
				'body'              => preg_replace( '/\d{4,}/', '****', $message ) ?? '',
				'status'            => ! empty( $result['ok'] ) ? 'sent' : 'failed',
				'provider_response' => wp_json_encode( $result ),
				'created_at'        => current_time( 'mysql' ),
			)
		);

		return $result;
	}

	/**
	 * @param array<string, mixed> $overrides Optional settings overrides (e.g. unsaved form values).
	 * @return array{ok:bool,message?:string,error?:string,account?:array<string,mixed>,response?:string}
	 */
	public static function test_connection( array $overrides = array() ): array {
		$settings = array_merge( Settings::get(), $overrides );
		$slug     = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
		$provider = self::providers()[ $slug ] ?? null;

		if ( ! $provider ) {
			return array( 'ok' => false, 'error' => __( 'سرویس‌دهنده پیامک نامعتبر است.', 'mr-booking' ) );
		}

		$result = $provider->test_connection( $settings );
		if ( ! empty( $result['ok'] ) ) {
			self::store_account_credit_from_test( $slug, $provider, $result );
		}

		return $result;
	}

	/**
	 * Cached account credit for admin bar / settings UI.
	 *
	 * @return array{ok:bool,provider?:string,provider_label?:string,credit?:float|null,error?:string|null,checked_at?:int,reason?:string}
	 */
	public static function get_account_credit( bool $force_refresh = false ): array {
		$settings = Settings::get();
		if ( empty( $settings['sms_enabled'] ) ) {
			return array( 'ok' => false, 'reason' => 'disabled' );
		}

		$slug     = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
		$provider = self::providers()[ $slug ] ?? null;
		if ( ! $provider || ! $provider->supports_account_credit() ) {
			return array( 'ok' => false, 'reason' => 'unsupported', 'provider' => $slug );
		}

		if ( ! $force_refresh ) {
			$cached = get_transient( self::credit_transient_key( $slug ) );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		return self::refresh_account_credit();
	}

	/**
	 * @return array{ok:bool,provider?:string,provider_label?:string,credit?:float|null,error?:string|null,checked_at?:int,reason?:string}
	 */
	public static function refresh_account_credit( array $overrides = array() ): array {
		$settings = array_merge( Settings::get(), $overrides );
		$slug     = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
		$provider = self::providers()[ $slug ] ?? null;

		if ( ! $provider || ! $provider->supports_account_credit() ) {
			self::clear_account_credit_cache( $slug );

			return array( 'ok' => false, 'reason' => 'unsupported', 'provider' => $slug );
		}

		$result  = $provider->test_connection( $settings );
		$payload = self::build_credit_payload( $slug, $provider, $result );

		if ( ! empty( $payload['ok'] ) ) {
			set_transient( self::credit_transient_key( $slug ), $payload, self::CREDIT_TTL );
		} else {
			delete_transient( self::credit_transient_key( $slug ) );
		}

		return $payload;
	}

	public static function clear_account_credit_cache( ?string $slug = null ): void {
		if ( null !== $slug && '' !== $slug ) {
			delete_transient( self::credit_transient_key( $slug ) );
			return;
		}

		foreach ( array_keys( self::providers() ) as $provider_slug ) {
			delete_transient( self::credit_transient_key( $provider_slug ) );
		}
	}

	public static function format_credit( ?float $credit ): string {
		if ( null === $credit ) {
			return '—';
		}

		return number_format_i18n( $credit, 0 );
	}

	private static function credit_transient_key( string $slug ): string {
		return 'mr_booking_sms_credit_' . sanitize_key( $slug );
	}

	/**
	 * @param array<string, mixed> $result
	 */
	private static function store_account_credit_from_test( string $slug, Provider_Interface $provider, array $result ): void {
		if ( ! $provider->supports_account_credit() ) {
			return;
		}

		$payload = self::build_credit_payload( $slug, $provider, $result );
		if ( ! empty( $payload['ok'] ) ) {
			set_transient( self::credit_transient_key( $slug ), $payload, self::CREDIT_TTL );
		}
	}

	/**
	 * @param array<string, mixed> $result
	 * @return array{ok:bool,provider:string,provider_label:string,credit:float|null,error:string|null,checked_at:int}
	 */
	private static function build_credit_payload( string $slug, Provider_Interface $provider, array $result ): array {
		$credit = isset( $result['account']['credit'] ) ? (float) $result['account']['credit'] : null;

		return array(
			'ok'             => ! empty( $result['ok'] ),
			'provider'       => $slug,
			'provider_label' => $provider->label(),
			'credit'         => $credit,
			'error'          => empty( $result['ok'] ) ? (string) ( $result['error'] ?? '' ) : null,
			'checked_at'     => time(),
		);
	}
}
