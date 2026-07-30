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
	 * Format booking datetime for admin display (respects calendar_mode).
	 */
	public static function format_booking_datetime( string $datetime ): string {
		$date = substr( $datetime, 0, 10 );
		$time = substr( $datetime, 11, 5 );
		if ( ! $date || ! $time ) {
			return self::to_persian_digits( $datetime );
		}

		$mode = Settings\Settings::get_value( 'calendar_mode', 'jalali' );

		if ( 'gregorian' === $mode ) {
			$date_label = $date;
		} elseif ( 'both' === $mode ) {
			$date_label = Calendar\Jalali::format_from_date( $date ) . ' (' . $date . ')';
		} else {
			$date_label = Calendar\Jalali::format_from_date( $date );
		}

		return self::to_persian_digits( trim( $date_label . ' ' . $time ) );
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
}
