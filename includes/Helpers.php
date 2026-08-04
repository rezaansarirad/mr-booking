<?php
/**
 * Shared helpers.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking;

defined( 'ABSPATH' ) || exit;

final class Helpers {

	/**
	 * Table name with WP prefix.
	 */
	public static function table( string $name ): string {
		global $wpdb;

		return $wpdb->prefix . 'mr_' . $name;
	}

	/**
	 * Sanitize Iranian mobile number to 09xxxxxxxxx.
	 */
	public static function sanitize_mobile( string $phone ): string {
		$phone = self::to_english_digits( $phone );
		$phone = preg_replace( '/[^0-9]/', '', $phone ) ?? '';

		if ( 0 === strpos( $phone, '98' ) && 12 === strlen( $phone ) ) {
			$phone = '0' . substr( $phone, 2 );
		}

		if ( 0 === strpos( $phone, '9' ) && 10 === strlen( $phone ) ) {
			$phone = '0' . $phone;
		}

		return $phone;
	}

	/**
	 * Validate Iranian mobile.
	 */
	public static function is_valid_mobile( string $phone ): bool {
		return (bool) preg_match( '/^09\d{9}$/', self::sanitize_mobile( $phone ) );
	}

	/**
	 * Convert digits to Persian.
	 */
	public static function to_persian_digits( string $value ): string {
		return strtr(
			$value,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		);
	}

	/**
	 * Convert Persian/Arabic digits to English.
	 */
	public static function to_english_digits( string $value ): string {
		return strtr(
			$value,
			array(
				'۰' => '0',
				'۱' => '1',
				'۲' => '2',
				'۳' => '3',
				'۴' => '4',
				'۵' => '5',
				'۶' => '6',
				'۷' => '7',
				'۸' => '8',
				'۹' => '9',
				'٠' => '0',
				'١' => '1',
				'٢' => '2',
				'٣' => '3',
				'٤' => '4',
				'٥' => '5',
				'٦' => '6',
				'٧' => '7',
				'٨' => '8',
				'٩' => '9',
			)
		);
	}

	/**
	 * Format price for display.
	 */
	public static function format_price( float $price ): string {
		if ( $price <= 0 ) {
			return '';
		}

		$formatted = number_format( $price, 0, '.', ',' );

		return self::to_persian_digits( $formatted ) . ' ' . __( 'تومان', 'mr-booking' );
	}

	/**
	 * Format duration minutes.
	 */
	public static function format_duration( int $minutes ): string {
		if ( $minutes < 60 ) {
			return sprintf(
				/* translators: %s: minutes */
				__( '%s دقیقه', 'mr-booking' ),
				self::to_persian_digits( (string) $minutes )
			);
		}

		$hours = intdiv( $minutes, 60 );
		$mins  = $minutes % 60;

		if ( 0 === $mins ) {
			return sprintf(
				/* translators: %s: hours */
				__( '%s ساعت', 'mr-booking' ),
				self::to_persian_digits( (string) $hours )
			);
		}

		return sprintf(
			/* translators: 1: hours, 2: minutes */
			__( '%1$s ساعت و %2$s دقیقه', 'mr-booking' ),
			self::to_persian_digits( (string) $hours ),
			self::to_persian_digits( (string) $mins )
		);
	}

	/**
	 * Whether WP-Parsidate (or compatible) Persian calendar is active.
	 */
	public static function wp_persian_calendar_active(): bool {
		if ( ! function_exists( 'parsidate' ) ) {
			return false;
		}

		$settings = get_option( 'wpp_settings', array() );
		if ( is_array( $settings ) && isset( $settings['conv_dates'] ) && 'disable' === $settings['conv_dates'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Admin display calendar mode (WordPress locale / Parsidate, then plugin fallback).
	 *
	 * @return 'jalali'|'gregorian'|'both'
	 */
	public static function admin_calendar_mode(): string {
		if ( self::wp_persian_calendar_active() ) {
			return 'jalali';
		}

		$locale = function_exists( 'get_user_locale' ) && is_admin()
			? get_user_locale()
			: get_locale();

		if ( 0 === strpos( strtolower( (string) $locale ), 'fa' ) ) {
			return 'jalali';
		}

		$mode = Settings\Settings::get_value( 'calendar_mode', 'jalali' );

		return in_array( $mode, array( 'jalali', 'gregorian', 'both' ), true ) ? $mode : 'jalali';
	}

	/**
	 * Format Y-m-d for admin display.
	 */
	public static function format_admin_date( string $ymd ): string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return self::maybe_localize_digits( $ymd );
		}

		$mode = self::admin_calendar_mode();
		$ts   = self::timestamp_from_ymd( $ymd );

		if ( 'gregorian' === $mode ) {
			return self::maybe_localize_digits( wp_date( get_option( 'date_format' ), $ts, wp_timezone() ) );
		}

		$formatted = self::format_jalali_with_wp_format( $ts, $ymd );
		if ( 'both' === $mode ) {
			$greg = wp_date( get_option( 'date_format' ), $ts, wp_timezone() );

			return self::maybe_localize_digits( $formatted . ' (' . $greg . ')' );
		}

		return self::maybe_localize_digits( $formatted );
	}

	/**
	 * Long admin date label (weekday + date); dual line when mode is both.
	 */
	public static function format_admin_dual_date( string $ymd ): string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return self::maybe_localize_digits( $ymd );
		}

		$mode = self::admin_calendar_mode();
		$ts   = self::timestamp_from_ymd( $ymd );

		if ( 'gregorian' === $mode ) {
			return self::maybe_localize_digits( wp_date( get_option( 'date_format' ), $ts, wp_timezone() ) );
		}

		$fa = self::jalali_long_label( $ymd );
		if ( 'both' === $mode ) {
			$en = wp_date( get_option( 'date_format' ), $ts, wp_timezone() );

			return self::maybe_localize_digits( $fa . "\n" . $en );
		}

		return self::maybe_localize_digits( $fa );
	}

	/**
	 * Format time portion for admin display (respects WordPress time_format).
	 */
	public static function format_admin_time( string $datetime ): string {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $datetime ) ) {
			$ts = self::timestamp_from_datetime( $datetime );
			if ( $ts ) {
				return self::maybe_localize_digits( wp_date( get_option( 'time_format' ), $ts, wp_timezone() ) );
			}
		}

		$time = strlen( $datetime ) >= 16 ? substr( $datetime, 11, 5 ) : substr( $datetime, 0, 5 );

		return self::maybe_localize_digits( $time );
	}

	/**
	 * Format MySQL datetime for admin display.
	 */
	public static function format_admin_datetime( string $datetime ): string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $datetime ) ) {
			return self::maybe_localize_digits( $datetime );
		}

		return trim( self::format_admin_date( substr( $datetime, 0, 10 ) ) . ' ' . self::format_admin_time( $datetime ) );
	}

	/**
	 * Format booking datetime for admin display (WordPress date/time settings).
	 */
	public static function format_booking_datetime( string $datetime ): string {
		return self::format_admin_datetime( $datetime );
	}

	/**
	 * Format date for customer-facing messages (plugin calendar_mode).
	 */
	public static function format_customer_date( string $ymd ): string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return self::to_persian_digits( $ymd );
		}

		$mode = Settings\Settings::get_value( 'calendar_mode', 'jalali' );

		if ( 'gregorian' === $mode ) {
			$ts = self::timestamp_from_ymd( $ymd );

			return self::to_persian_digits( wp_date( get_option( 'date_format' ), $ts, wp_timezone() ) );
		}

		if ( 'both' === $mode ) {
			return self::to_persian_digits( Calendar\Jalali::dual_label( $ymd ) );
		}

		return self::to_persian_digits( Calendar\Jalali::format_from_date( $ymd ) );
	}

	/**
	 * Format time for customer-facing messages.
	 */
	public static function format_customer_time( string $datetime ): string {
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}/', $datetime ) ) {
			$ts = self::timestamp_from_datetime( $datetime );
			if ( $ts ) {
				return self::to_persian_digits( wp_date( get_option( 'time_format' ), $ts, wp_timezone() ) );
			}
		}

		$time = strlen( $datetime ) >= 16 ? substr( $datetime, 11, 5 ) : substr( $datetime, 0, 5 );

		return self::to_persian_digits( $time );
	}

	/**
	 * Unix timestamp from Y-m-d in site timezone.
	 */
	private static function timestamp_from_ymd( string $ymd, int $hour = 12, int $minute = 0 ): int {
		$tz  = wp_timezone();
		$dt  = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i', sprintf( '%s %02d:%02d', $ymd, $hour, $minute ), $tz );

		return $dt ? $dt->getTimestamp() : (int) strtotime( $ymd . ' 12:00:00' );
	}

	/**
	 * Unix timestamp from MySQL datetime in site timezone.
	 */
	private static function timestamp_from_datetime( string $datetime ): int {
		$tz = wp_timezone();
		$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $datetime, $tz );
		if ( ! $dt ) {
			$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i', substr( $datetime, 0, 16 ), $tz );
		}

		return $dt ? $dt->getTimestamp() : (int) strtotime( $datetime );
	}

	/**
	 * Jalali date label with weekday (fallback when Parsidate is unavailable).
	 */
	private static function jalali_long_label( string $ymd ): string {
		$ts = self::timestamp_from_ymd( $ymd );
		if ( ! $ts ) {
			return Calendar\Jalali::format_from_date( $ymd );
		}

		$w     = (int) wp_date( 'w', $ts, wp_timezone() );
		$parts = explode( '-', $ymd );
		[ $jy, $jm, $jd ] = Calendar\Jalali::from_gregorian( (int) $parts[0], (int) $parts[1], (int) $parts[2] );
		$months = Calendar\Jalali::month_names();

		return Calendar\Jalali::weekday_fa()[ $w ] . ' ' . $jd . ' ' . $months[ $jm ] . ' ' . $jy;
	}

	/**
	 * Format Jalali date using WP date_format when Parsidate is available.
	 */
	private static function format_jalali_with_wp_format( int $timestamp, string $ymd ): string {
		if ( function_exists( 'parsidate' ) ) {
			$lang   = self::use_persian_digits() ? 'per' : 'eng';
			$result = parsidate( get_option( 'date_format' ), $timestamp, $lang );
			if ( is_string( $result ) && '' !== $result ) {
				return $result;
			}
		}

		return self::jalali_long_label( $ymd );
	}

	/**
	 * Use Persian digits in admin when not in pure Gregorian mode.
	 */
	private static function use_persian_digits(): bool {
		return 'gregorian' !== self::admin_calendar_mode();
	}

	/**
	 * Localize digits for admin display context.
	 */
	private static function maybe_localize_digits( string $value ): string {
		return self::use_persian_digits() ? self::to_persian_digits( $value ) : $value;
	}

	/**
	 * Booking statuses map.
	 *
	 * @return array<string, string>
	 */
	public static function booking_statuses(): array {
		return array(
			'pending'   => __( 'در انتظار', 'mr-booking' ),
			'confirmed' => __( 'تأیید شده', 'mr-booking' ),
			'completed' => __( 'انجام شده', 'mr-booking' ),
			'cancelled' => __( 'لغو شده', 'mr-booking' ),
			'rejected'  => __( 'رد شده', 'mr-booking' ),
			'no_show'   => __( 'عدم حضور', 'mr-booking' ),
		);
	}

	/**
	 * Booking-for options.
	 *
	 * @return array<string, string>
	 */
	public static function booking_for_options(): array {
		$settings = \MRBooking\Settings\Settings::get();

		return array(
			'myself' => $settings['text_booking_for_myself'] ?? __( 'برای خودم', 'mr-booking' ),
			'child'  => $settings['text_booking_for_child'] ?? __( 'برای فرزندم', 'mr-booking' ),
			'other'  => $settings['text_booking_for_other'] ?? __( 'برای شخص دیگری', 'mr-booking' ),
		);
	}

	/**
	 * Weekday keys (0 = Sunday … 6 = Saturday) mapped to ISO-like Persian week start Saturday.
	 *
	 * @return array<int, string>
	 */
	public static function weekday_labels(): array {
		return array(
			6 => __( 'شنبه', 'mr-booking' ),
			0 => __( 'یکشنبه', 'mr-booking' ),
			1 => __( 'دوشنبه', 'mr-booking' ),
			2 => __( 'سه‌شنبه', 'mr-booking' ),
			3 => __( 'چهارشنبه', 'mr-booking' ),
			4 => __( 'پنجشنبه', 'mr-booking' ),
			5 => __( 'جمعه', 'mr-booking' ),
		);
	}

	/**
	 * Current capability for managing bookings.
	 * Admins always pass via manage_options; custom cap is for dedicated roles.
	 */
	public static function manage_cap(): string {
		return apply_filters( 'mr_booking_manage_cap', 'manage_options' );
	}

	/**
	 * Ensure manage capability exists for admins (legacy custom cap + options).
	 */
	public static function ensure_caps(): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}
		if ( ! $role->has_cap( 'manage_mr_booking' ) ) {
			$role->add_cap( 'manage_mr_booking' );
		}
		if ( ! $role->has_cap( 'manage_options' ) ) {
			// Administrator should already have this; no-op safety.
			return;
		}
	}

	/**
	 * Replace template variables.
	 *
	 * @param array<string, string> $vars Variables.
	 */
	public static function replace_vars( string $template, array $vars ): string {
		foreach ( $vars as $key => $value ) {
			$template = str_replace( '{' . $key . '}', (string) $value, $template );
		}

		return $template;
	}

	/**
	 * Escape and echo HTML attribute.
	 */
	public static function esc_attr_e( string $text ): void {
		echo esc_attr( $text );
	}

	/**
	 * Normalize color to #rrggbb lowercase.
	 */
	public static function normalize_hex_color( string $color, string $fallback = '#000000' ): string {
		$color = sanitize_hex_color( $color );
		if ( ! $color ) {
			$color = sanitize_hex_color( $fallback ) ?: '#000000';
		}

		return strtolower( $color );
	}

	/**
	 * Render color picker with HEX text input.
	 *
	 * @param array<string, string> $args id, class, hex_label, default.
	 */
	public static function color_input( string $name, string $value, array $args = array() ): void {
		$defaults = array(
			'id'        => '',
			'class'     => '',
			'hex_label' => __( 'کد HEX', 'mr-booking' ),
			'default'   => '#000000',
		);
		$args     = wp_parse_args( $args, $defaults );
		$value    = self::normalize_hex_color( $value, (string) $args['default'] );

		include MR_BOOKING_PATH . 'templates/admin/partials/color-input.php';
	}

	/**
	 * Render a palette color card (settings appearance).
	 *
	 * @param array{0:string,1:string} $meta Label + description.
	 * @param array<string, mixed>     $settings
	 */
	public static function palette_color_card( string $key, array $meta, array $settings ): void {
		$color_val = self::normalize_hex_color( (string) ( $settings[ $key ] ?? '#000000' ), '#000000' );

		include MR_BOOKING_PATH . 'templates/admin/partials/palette-color-card.php';
	}

	/**
	 * Admin menu / panel title following the WordPress site locale.
	 */
	public static function plugin_name(): string {
		$translated = __( 'Booking Form', 'mr-booking' );

		if ( 'Booking Form' !== $translated ) {
			return $translated;
		}

		return self::is_persian_locale() ? 'رزروها' : 'Booking Form';
	}

	public static function is_persian_locale(): bool {
		$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();

		return 0 === strpos( (string) $locale, 'fa' );
	}

	/**
	 * Admin notice for customer notification channel result after status change.
	 *
	 * @param array{attempted?:bool,sent?:bool,skip_reason?:string|null} $result
	 * @return array{type:string,message:string}|null
	 */
	public static function notify_feedback_notice( string $channel, array $result ): ?array {
		$sent      = ! empty( $result['sent'] );
		$attempted = ! empty( $result['attempted'] );
		$skip      = (string) ( $result['skip_reason'] ?? '' );

		if ( 'email' === $channel ) {
			if ( $sent ) {
				return array(
					'type'    => 'success',
					'message' => __( 'ایمیل تأیید برای مشتری ارسال شد.', 'mr-booking' ),
				);
			}

			switch ( $skip ) {
				case 'customer_email_missing':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد، اما مشتری ایمیل ثبت نکرده — ایمیل تأیید ارسال نشد.', 'mr-booking' ),
					);
				case 'email_disabled':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد، اما ارسال ایمیل در تنظیمات غیرفعال است.', 'mr-booking' ),
					);
				case 'notify_on_confirm_disabled':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد، اما «ارسال اعلان تأیید به مشتری» در بخش اعلان‌ها خاموش است.', 'mr-booking' ),
					);
				case 'wp_mail_failed':
					return array(
						'type'    => 'error',
						'message' => __( 'رزرو تأیید شد، اما ارسال ایمیل ناموفق بود. تنظیمات SMTP/ایمیل سرور را بررسی کنید.', 'mr-booking' ),
					);
				case 'email_template_empty':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد؛ قالب ایمیل تأیید خالی است.', 'mr-booking' ),
					);
			}

			if ( $attempted ) {
				return array(
					'type'    => 'warning',
					'message' => __( 'رزرو تأیید شد؛ اعلان ایمیل به مشتری ارسال نشد.', 'mr-booking' ),
				);
			}

			return null;
		}

		if ( 'sms' === $channel ) {
			if ( $sent ) {
				return array(
					'type'    => 'success',
					'message' => __( 'پیامک تأیید برای مشتری ارسال شد.', 'mr-booking' ),
				);
			}

			if ( $attempted && $skip ) {
				return array(
					'type'    => 'error',
					'message' => sprintf(
						/* translators: %s: provider error message */
						__( 'رزرو تأیید شد، اما ارسال پیامک ناموفق بود: %s', 'mr-booking' ),
						$skip
					),
				);
			}

			if ( $attempted ) {
				return array(
					'type'    => 'error',
					'message' => __( 'رزرو تأیید شد، اما ارسال پیامک ناموفق بود. کلید API و شماره فرستنده را در تنظیمات SMS بررسی کنید.', 'mr-booking' ),
				);
			}

			switch ( $skip ) {
				case 'phone_missing':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد، اما مشتری شماره موبایل معتبر ثبت نکرده — پیامک ارسال نشد.', 'mr-booking' ),
					);
				case 'sms_disabled':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد، اما ارسال پیامک در تنظیمات غیرفعال است.', 'mr-booking' ),
					);
				case 'sms_template_empty':
					return array(
						'type'    => 'warning',
						'message' => __( 'رزرو تأیید شد؛ قالب پیامک تأیید خالی است.', 'mr-booking' ),
					);
				case 'notify_on_confirm_disabled':
					return null;
			}
		}

		return null;
	}
}
