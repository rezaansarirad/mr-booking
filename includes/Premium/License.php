<?php
/**
 * Premium license helpers.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Premium;

use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class License {

	/**
	 * Valid premium activation keys (lowercase).
	 *
	 * @return list<string>
	 */
	public static function valid_keys(): array {
		/**
		 * Filter allowed premium license keys.
		 *
		 * @param list<string> $keys Lowercase keys.
		 */
		return apply_filters(
			'mr_booking_premium_keys',
			array(
				'rezaansarirad',
			)
		);
	}

	public static function normalize_key( string $key ): string {
		return strtolower( trim( $key ) );
	}

	public static function is_valid_key( string $key ): bool {
		$key = self::normalize_key( $key );
		if ( '' === $key ) {
			return false;
		}
		return in_array( $key, self::valid_keys(), true );
	}

	public static function is_active(): bool {
		$stored = (string) Settings::get_value( 'premium_key', '' );
		return self::is_valid_key( $stored );
	}

	/**
	 * Whether white-label / hide branding is enabled.
	 */
	public static function hide_branding(): bool {
		if ( ! self::is_active() ) {
			return false;
		}
		return (int) Settings::get_value( 'hide_branding', 0 ) === 1;
	}
}
