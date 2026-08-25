<?php
/**
 * Accounting admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Accounting\Accounting_Repository;
use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Accounting {

	/**
	 * Longest custom range we aggregate in one page load (keeps queries cheap).
	 */
	private const MAX_RANGE_DAYS = 366;

	/**
	 * @return array<string, string>
	 */
	public static function presets(): array {
		return array(
			'today'      => __( 'امروز', 'mr-booking' ),
			'yesterday'  => __( 'دیروز', 'mr-booking' ),
			'week'       => __( '۷ روز اخیر', 'mr-booking' ),
			'month'      => __( 'این ماه', 'mr-booking' ),
			'last_month' => __( 'ماه گذشته', 'mr-booking' ),
			'year'       => __( 'امسال', 'mr-booking' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function group_options(): array {
		return array(
			'day'   => __( 'روزانه', 'mr-booking' ),
			'month' => __( 'ماهانه', 'mr-booking' ),
		);
	}

	/**
	 * Resolve a preset to a Gregorian range in the site timezone.
	 *
	 * Month presets follow the admin calendar (Jalali month when the admin
	 * calendar is Jalali) so "this month" matches what the owner means.
	 *
	 * @return array{0:string,1:string}
	 */
	private static function preset_range( string $preset ): array {
		$now   = new \DateTimeImmutable( 'now', wp_timezone() );
		$today = $now->format( 'Y-m-d' );

		switch ( $preset ) {
			case 'yesterday':
				$d = $now->modify( '-1 day' )->format( 'Y-m-d' );
				return array( $d, $d );
			case 'week':
				return array( $now->modify( '-6 days' )->format( 'Y-m-d' ), $today );
			case 'month':
				return self::calendar_month_range( $now );
			case 'last_month':
				return self::calendar_month_range( self::previous_month_anchor( $now ) );
			case 'year':
				return self::calendar_year_range( $now );
			case 'today':
			default:
				return array( $today, $today );
		}
	}

	/**
	 * @return array{0:string,1:string}
	 */
	private static function calendar_month_range( \DateTimeImmutable $anchor ): array {
		if ( 'gregorian' === Helpers::admin_calendar_mode() ) {
			return array( $anchor->format( 'Y-m-01' ), $anchor->format( 'Y-m-t' ) );
		}

		[ $jy, $jm ] = \MRBooking\Calendar\Jalali::from_gregorian( (int) $anchor->format( 'Y' ), (int) $anchor->format( 'n' ), (int) $anchor->format( 'j' ) );
		$days        = \MRBooking\Calendar\Jalali::days_in_month( $jy, $jm );
		$from        = \MRBooking\Calendar\Jalali::to_gregorian( $jy, $jm, 1 );
		$to          = \MRBooking\Calendar\Jalali::to_gregorian( $jy, $jm, $days );

		return array(
			sprintf( '%04d-%02d-%02d', $from[0], $from[1], $from[2] ),
			sprintf( '%04d-%02d-%02d', $to[0], $to[1], $to[2] ),
		);
	}

	/**
	 * @return array{0:string,1:string}
	 */
	private static function calendar_year_range( \DateTimeImmutable $anchor ): array {
		if ( 'gregorian' === Helpers::admin_calendar_mode() ) {
			return array( $anchor->format( 'Y-01-01' ), $anchor->format( 'Y-m-d' ) );
		}

		[ $jy ] = \MRBooking\Calendar\Jalali::from_gregorian( (int) $anchor->format( 'Y' ), (int) $anchor->format( 'n' ), (int) $anchor->format( 'j' ) );
		$from   = \MRBooking\Calendar\Jalali::to_gregorian( $jy, 1, 1 );

		return array( sprintf( '%04d-%02d-%02d', $from[0], $from[1], $from[2] ), $anchor->format( 'Y-m-d' ) );
	}

	/**
	 * A date safely inside the previous calendar month (Jalali or Gregorian).
	 */
	private static function previous_month_anchor( \DateTimeImmutable $now ): \DateTimeImmutable {
		[ $from ] = self::calendar_month_range( $now );

		return ( new \DateTimeImmutable( $from . ' 12:00:00', wp_timezone() ) )->modify( '-1 day' );
	}

	/**
	 * Parse filters from the request (shared by the page and the CSV export).
	 *
	 * @return array<string, mixed>
	 */
	public static function filters_from_request(): array {
		$preset = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$from   = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$to     = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$valid_date = static fn( string $d ): bool => (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d );
		if ( ! $valid_date( $from ) ) {
			$from = '';
		}
		if ( ! $valid_date( $to ) ) {
			$to = '';
		}

		if ( ! $from && ! $to ) {
			if ( ! isset( self::presets()[ $preset ] ) ) {
				$preset = 'today';
			}
			[ $from, $to ] = self::preset_range( $preset );
		} else {
			$preset = '';
			if ( ! $from ) {
				$from = $to;
			}
			if ( ! $to ) {
				$to = $from;
			}
			if ( $from > $to ) {
				[ $from, $to ] = array( $to, $from );
			}
			// Clamp very long ranges.
			$span = ( new \DateTimeImmutable( $from ) )->diff( new \DateTimeImmutable( $to ) )->days;
			if ( $span > self::MAX_RANGE_DAYS ) {
				$from = ( new \DateTimeImmutable( $to ) )->modify( '-' . self::MAX_RANGE_DAYS . ' days' )->format( 'Y-m-d' );
			}
		}

		$group = isset( $_GET['group'] ) ? sanitize_key( wp_unslash( $_GET['group'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( self::group_options()[ $group ] ) ) {
			// Auto: monthly when the range spans more than ~2 months.
			$span  = ( new \DateTimeImmutable( $from ) )->diff( new \DateTimeImmutable( $to ) )->days;
			$group = $span > 62 ? 'month' : 'day';
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'all' !== $status && ! isset( Helpers::booking_statuses()[ $status ] ) ) {
			$status = '';
		}

		$source = isset( $_GET['source'] ) ? sanitize_key( wp_unslash( $_GET['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( Booking_Repository::source_labels()[ $source ] ) ) {
			$source = '';
		}

		return array(
			'preset'     => $preset,
			'date_from'  => $from,
			'date_to'    => $to,
			'group'      => $group,
			'service_id' => isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'staff_id'   => isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'source'     => $source,
			'status'     => $status,
		);
	}

	/**
	 * Build a page URL with merged filters.
	 *
	 * @param array<string, mixed> $overrides
	 * @param array<string, mixed> $filters
	 */
	public static function filter_url( array $overrides, array $filters ): string {
		$args = array_merge(
			array(
				'preset'     => $filters['preset'],
				'date_from'  => $filters['preset'] ? '' : $filters['date_from'],
				'date_to'    => $filters['preset'] ? '' : $filters['date_to'],
				'group'      => $filters['group'],
				'service_id' => $filters['service_id'],
				'staff_id'   => $filters['staff_id'],
				'source'     => $filters['source'],
				'status'     => $filters['status'],
			),
			$overrides
		);

		$args = array_filter( $args, static fn( $v ) => '' !== $v && 0 !== $v && null !== $v );

		return add_query_arg( $args, admin_url( 'admin.php?page=mr-booking-accounting' ) );
	}

	public static function render(): void {
		Helpers::require_page( 'mr-booking-accounting' );

		$filters = self::filters_from_request();
		$totals  = Accounting_Repository::totals( $filters );
		$days    = Accounting_Repository::by_day( $filters );
		$periods = 'month' === $filters['group']
			? Accounting_Repository::group_by_month( $days )
			: array_map(
				static fn( array $d ): array => array(
					'key'     => $d['date'],
					'label'   => Helpers::format_admin_date( $d['date'] ),
					'revenue' => $d['revenue'],
					'count'   => $d['count'],
					'days'    => 1,
				),
				array_reverse( $days )
			);

		$by_service = Accounting_Repository::by_service( $filters );
		$by_source  = Accounting_Repository::by_source( $filters );
		$by_staff   = Accounting_Repository::by_staff( $filters );
		$ledger     = Accounting_Repository::ledger( $filters );

		$services = Service_Repository::all();
		$staff    = Staff_Repository::all();
		$statuses = Helpers::booking_statuses();
		$sources  = Booking_Repository::source_labels();

		$max_period_revenue = 0.0;
		foreach ( $periods as $p ) {
			$max_period_revenue = max( $max_period_revenue, (float) $p['revenue'] );
		}

		$show_prices    = ! empty( \MRBooking\Settings\Settings::get_value( 'show_prices' ) );
		$unpriced_count = 0;
		foreach ( $services as $svc ) {
			if ( 'active' === (string) $svc->status && ! Service_Repository::has_price( $svc ) ) {
				$unpriced_count++;
			}
		}

		$export_url = wp_nonce_url(
			self::filter_url( array( 'mr_export' => 'accounting' ), $filters ),
			'mr_export'
		);

		include MR_BOOKING_PATH . 'templates/admin/accounting.php';
	}
}
