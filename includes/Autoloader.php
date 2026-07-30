<?php
/**
 * PSR-4 style autoloader for MR Booking.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking;

defined( 'ABSPATH' ) || exit;

final class Autoloader {

	/**
	 * Register the autoloader.
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load a class file.
	 *
	 * @param string $class Fully-qualified class name.
	 */
	public static function load( string $class ): void {
		$prefix = 'MRBooking\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = MR_BOOKING_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
