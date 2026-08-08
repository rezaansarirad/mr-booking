<?php
/**
 * CSV export helpers.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Export;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Exporter {

	/**
	 * @param array<string, mixed> $args
	 */
	public static function customers_csv( array $args = array() ): void {
		if ( ! \MRBooking\Helpers::user_can( \MRBooking\Roles\Capabilities::REPORTS ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز', 'mr-booking' ) );
		}

		$customers = Customer_Repository::query(
			array_merge(
				$args,
				array(
					'limit'  => 10000,
					'offset' => 0,
				)
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mr-booking-customers-' . gmdate( 'Ymd' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv( $out, array( 'ID', 'First Name', 'Last Name', 'Phone', 'Email', 'Birth Date', 'Created' ) );

		foreach ( $customers as $c ) {
			fputcsv(
				$out,
				array(
					$c->id,
					$c->first_name,
					$c->last_name,
					$c->phone,
					$c->email,
					! empty( $c->birth_date ) ? Helpers::format_admin_date( (string) $c->birth_date ) : '',
					! empty( $c->created_at ) ? Helpers::format_admin_datetime( (string) $c->created_at ) : '',
				)
			);
		}

		fclose( $out );
		exit;
	}

	/**
	 * @param array<string, mixed> $args
	 */
	public static function bookings_csv( array $args = array() ): void {
		if ( ! \MRBooking\Helpers::user_can( \MRBooking\Roles\Capabilities::REPORTS ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز', 'mr-booking' ) );
		}

		$bookings = Booking_Repository::query(
			array_merge(
				$args,
				array(
					'limit'  => 10000,
					'offset' => 0,
				)
			)
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mr-booking-bookings-' . gmdate( 'Ymd' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv( $out, array( 'Code', 'Customer', 'Phone', 'Start', 'End', 'Status', 'Price', 'Duration' ) );

		foreach ( $bookings as $b ) {
			fputcsv(
				$out,
				array(
					$b->booking_code,
					trim( $b->first_name . ' ' . $b->last_name ),
					$b->phone,
					Helpers::format_admin_datetime( (string) $b->start_datetime ),
					Helpers::format_admin_datetime( (string) $b->end_datetime ),
					$b->status,
					$b->total_price,
					$b->total_duration,
				)
			);
		}

		fclose( $out );
		exit;
	}
}
