<?php
/**
 * CSV export helpers.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Export;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Roles\Capabilities;

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

	/**
	 * Accounting ledger for the filtered range (one line per booking + a totals row).
	 *
	 * @param array<string, mixed> $filters Output of Pages\Accounting::filters_from_request().
	 */
	public static function accounting_csv( array $filters ): void {
		if ( ! Helpers::user_can( Capabilities::ACCOUNTING ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز', 'mr-booking' ) );
		}

		$rows     = \MRBooking\Accounting\Accounting_Repository::ledger( $filters, 10000 );
		$statuses = Helpers::booking_statuses();
		$sources  = Booking_Repository::source_labels();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=mr-booking-accounting-' . $filters['date_from'] . '_' . $filters['date_to'] . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv( $out, array( 'Date', 'Time', 'Code', 'Customer', 'Phone', 'Services', 'Staff', 'Source', 'Status', 'Amount' ) );

		$total = 0.0;
		foreach ( $rows as $b ) {
			$total += (float) $b->total_price;
			fputcsv(
				$out,
				array(
					Helpers::format_admin_date( substr( (string) $b->start_datetime, 0, 10 ) ),
					Helpers::format_admin_time( (string) $b->start_datetime ),
					$b->booking_code,
					trim( $b->first_name . ' ' . $b->last_name ),
					$b->phone,
					(string) ( $b->service_names ?? '' ),
					(string) ( $b->staff_name ?? '' ),
					$sources[ $b->source ] ?? $b->source,
					$statuses[ $b->status ] ?? $b->status,
					(float) $b->total_price,
				)
			);
		}

		fputcsv( $out, array( 'Total', '', '', '', '', '', '', '', count( $rows ), $total ) );

		fclose( $out );
		exit;
	}
}
