<?php
/**
 * Accounting / revenue aggregates.
 *
 * Revenue is read from the amounts stored on each booking at the time it was
 * created (bookings.total_price / booking_services.price), so later edits to a
 * service's configured price never rewrite history.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Accounting;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Accounting_Repository {

	/**
	 * Statuses that count as realised revenue.
	 *
	 * @return list<string>
	 */
	public static function revenue_statuses(): array {
		/**
		 * Filter which booking statuses count toward revenue.
		 *
		 * @param list<string> $statuses
		 */
		return apply_filters( 'mr_booking_accounting_revenue_statuses', array( 'confirmed', 'completed' ) );
	}

	/**
	 * Build WHERE fragments shared by every aggregate.
	 *
	 * @param array<string, mixed> $filters {date_from,date_to,service_id,staff_id,source,status}
	 * @return array{where:string,params:list<mixed>}
	 */
	private static function where( array $filters, string $alias = 'b' ): array {
		$bs     = Helpers::table( 'booking_services' );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $filters['date_from'] ) ) {
			$where[]  = "DATE({$alias}.start_datetime) >= %s";
			$params[] = sanitize_text_field( (string) $filters['date_from'] );
		}
		if ( ! empty( $filters['date_to'] ) ) {
			$where[]  = "DATE({$alias}.start_datetime) <= %s";
			$params[] = sanitize_text_field( (string) $filters['date_to'] );
		}
		if ( ! empty( $filters['staff_id'] ) ) {
			$where[]  = "{$alias}.staff_id = %d";
			$params[] = absint( $filters['staff_id'] );
		}
		if ( ! empty( $filters['source'] ) ) {
			$where[]  = "{$alias}.source = %s";
			$params[] = sanitize_key( (string) $filters['source'] );
		}
		if ( ! empty( $filters['service_id'] ) ) {
			$where[]  = "EXISTS (SELECT 1 FROM {$bs} bsx WHERE bsx.booking_id = {$alias}.id AND bsx.service_id = %d)";
			$params[] = absint( $filters['service_id'] );
		}

		$status = (string) ( $filters['status'] ?? '' );
		if ( 'all' !== $status ) {
			$statuses = $status && isset( Helpers::booking_statuses()[ $status ] )
				? array( $status )
				: self::revenue_statuses();
			$place    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			$where[]  = "{$alias}.status IN ({$place})";
			$params   = array_merge( $params, $statuses );
		}

		return array(
			'where'  => implode( ' AND ', $where ),
			'params' => $params,
		);
	}

	/**
	 * @param list<mixed> $params
	 * @return list<object>
	 */
	private static function results( string $sql, array $params ): array {
		global $wpdb;
		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) // phpcs:ignore
			: $wpdb->get_results( $sql ); // phpcs:ignore

		return $rows ?: array();
	}

	/**
	 * Headline numbers.
	 *
	 * @param array<string, mixed> $filters
	 * @return array{revenue:float,count:int,average:float,walkin_revenue:float,walkin_count:int}
	 */
	public static function totals( array $filters ): array {
		$b = Helpers::table( 'bookings' );
		$w = self::where( $filters );

		$sql = "SELECT
				COALESCE(SUM(b.total_price), 0) AS revenue,
				COUNT(*) AS cnt,
				COALESCE(SUM(CASE WHEN b.source = 'walkin' THEN b.total_price ELSE 0 END), 0) AS walkin_revenue,
				COALESCE(SUM(CASE WHEN b.payment_status = 'paid' THEN b.paid_amount ELSE 0 END), 0) AS deposits_collected,
				SUM(CASE WHEN b.source = 'walkin' THEN 1 ELSE 0 END) AS walkin_count
			FROM {$b} b
			WHERE {$w['where']}";

		$row = self::results( $sql, $w['params'] )[0] ?? null;
		$revenue = (float) ( $row->revenue ?? 0 );
		$count   = (int) ( $row->cnt ?? 0 );

		return array(
			'revenue'        => $revenue,
			'count'          => $count,
			'average'        => $count > 0 ? $revenue / $count : 0.0,
			'walkin_revenue' => (float) ( $row->walkin_revenue ?? 0 ),
			'walkin_count'   => (int) ( $row->walkin_count ?? 0 ),
			'deposits'       => (float) ( $row->deposits_collected ?? 0 ),
		);
	}

	/**
	 * Revenue per calendar day (Gregorian Y-m-d keys, ascending).
	 *
	 * @param array<string, mixed> $filters
	 * @return list<array{date:string,revenue:float,count:int}>
	 */
	public static function by_day( array $filters ): array {
		$b = Helpers::table( 'bookings' );
		$w = self::where( $filters );

		$sql = "SELECT DATE(b.start_datetime) AS d, COALESCE(SUM(b.total_price), 0) AS revenue, COUNT(*) AS cnt
			FROM {$b} b
			WHERE {$w['where']}
			GROUP BY DATE(b.start_datetime)
			ORDER BY d ASC";

		$out = array();
		foreach ( self::results( $sql, $w['params'] ) as $row ) {
			$out[] = array(
				'date'    => (string) $row->d,
				'revenue' => (float) $row->revenue,
				'count'   => (int) $row->cnt,
			);
		}

		return $out;
	}

	/**
	 * Group daily rows into months (Jalali or Gregorian, per admin calendar mode).
	 *
	 * @param list<array{date:string,revenue:float,count:int}> $days
	 * @return list<array{key:string,label:string,revenue:float,count:int,days:int}>
	 */
	public static function group_by_month( array $days ): array {
		$jalali = 'gregorian' !== Helpers::admin_calendar_mode();
		$months = array();

		foreach ( $days as $day ) {
			$gy = (int) substr( $day['date'], 0, 4 );
			$gm = (int) substr( $day['date'], 5, 2 );
			$gd = (int) substr( $day['date'], 8, 2 );

			if ( $jalali ) {
				$j     = \MRBooking\Calendar\Jalali::from_gregorian( $gy, $gm, $gd );
				$key   = sprintf( '%04d-%02d', $j[0], $j[1] );
				$label = ( \MRBooking\Calendar\Jalali::month_names()[ $j[1] ] ?? '' ) . ' ' . Helpers::to_persian_digits( (string) $j[0] );
			} else {
				$key   = sprintf( '%04d-%02d', $gy, $gm );
				$label = wp_date( 'F Y', strtotime( $day['date'] . ' 12:00:00' ), wp_timezone() );
			}

			if ( ! isset( $months[ $key ] ) ) {
				$months[ $key ] = array(
					'key'     => $key,
					'label'   => $label,
					'revenue' => 0.0,
					'count'   => 0,
					'days'    => 0,
				);
			}
			$months[ $key ]['revenue'] += $day['revenue'];
			$months[ $key ]['count']   += $day['count'];
			$months[ $key ]['days']++;
		}

		return array_values( $months );
	}

	/**
	 * Revenue per service (line-level amounts).
	 *
	 * @param array<string, mixed> $filters
	 * @return list<array{service_id:int,name:string,revenue:float,count:int}>
	 */
	public static function by_service( array $filters ): array {
		$b  = Helpers::table( 'bookings' );
		$bs = Helpers::table( 'booking_services' );
		$s  = Helpers::table( 'services' );
		$w  = self::where( $filters );

		$sql = "SELECT bs.service_id, COALESCE(s.name, '') AS name, COALESCE(SUM(bs.price), 0) AS revenue, COUNT(*) AS cnt
			FROM {$bs} bs
			INNER JOIN {$b} b ON b.id = bs.booking_id
			LEFT JOIN {$s} s ON s.id = bs.service_id
			WHERE {$w['where']}
			GROUP BY bs.service_id, s.name
			ORDER BY revenue DESC, cnt DESC";

		$out = array();
		foreach ( self::results( $sql, $w['params'] ) as $row ) {
			$out[] = array(
				'service_id' => (int) $row->service_id,
				'name'       => (string) $row->name ?: __( 'خدمت حذف‌شده', 'mr-booking' ),
				'revenue'    => (float) $row->revenue,
				'count'      => (int) $row->cnt,
			);
		}

		return $out;
	}

	/**
	 * Revenue per source (online / phone / walk-in).
	 *
	 * @param array<string, mixed> $filters
	 * @return list<array{source:string,label:string,revenue:float,count:int}>
	 */
	public static function by_source( array $filters ): array {
		$b = Helpers::table( 'bookings' );
		$w = self::where( $filters );

		$sql = "SELECT b.source, COALESCE(SUM(b.total_price), 0) AS revenue, COUNT(*) AS cnt
			FROM {$b} b
			WHERE {$w['where']}
			GROUP BY b.source
			ORDER BY revenue DESC";

		$labels = \MRBooking\Bookings\Booking_Repository::source_labels();
		$out    = array();
		foreach ( self::results( $sql, $w['params'] ) as $row ) {
			$src   = (string) $row->source;
			$out[] = array(
				'source'  => $src,
				'label'   => $labels[ $src ] ?? $src,
				'revenue' => (float) $row->revenue,
				'count'   => (int) $row->cnt,
			);
		}

		return $out;
	}

	/**
	 * Revenue per staff member.
	 *
	 * @param array<string, mixed> $filters
	 * @return list<array{staff_id:int,name:string,revenue:float,count:int}>
	 */
	public static function by_staff( array $filters ): array {
		$b  = Helpers::table( 'bookings' );
		$st = Helpers::table( 'staff' );
		$w  = self::where( $filters );

		$sql = "SELECT b.staff_id, TRIM(CONCAT_WS(' ', st.first_name, st.last_name)) AS name,
				COALESCE(SUM(b.total_price), 0) AS revenue, COUNT(*) AS cnt
			FROM {$b} b
			LEFT JOIN {$st} st ON st.id = b.staff_id
			WHERE {$w['where']}
			GROUP BY b.staff_id, st.first_name, st.last_name
			ORDER BY revenue DESC";

		$out = array();
		foreach ( self::results( $sql, $w['params'] ) as $row ) {
			$out[] = array(
				'staff_id' => (int) $row->staff_id,
				'name'     => (string) $row->name ?: __( 'بدون پرسنل', 'mr-booking' ),
				'revenue'  => (float) $row->revenue,
				'count'    => (int) $row->cnt,
			);
		}

		return $out;
	}

	/**
	 * Individual bookings (ledger lines) for the range.
	 *
	 * @param array<string, mixed> $filters
	 * @return list<object>
	 */
	public static function ledger( array $filters, int $limit = 300 ): array {
		$args = array(
			'date_from'  => $filters['date_from'] ?? '',
			'date_to'    => $filters['date_to'] ?? '',
			'service_id' => $filters['service_id'] ?? 0,
			'staff_id'   => $filters['staff_id'] ?? 0,
			'source'     => $filters['source'] ?? '',
			'limit'      => $limit,
			'orderby'    => 'start_datetime',
			'order'      => 'DESC',
		);

		$status = (string) ( $filters['status'] ?? '' );
		if ( $status && 'all' !== $status ) {
			$args['status'] = $status;
		} elseif ( 'all' !== $status ) {
			$args['exclude_statuses'] = array_values(
				array_diff( array_keys( Helpers::booking_statuses() ), self::revenue_statuses() )
			);
		}

		return \MRBooking\Bookings\Booking_Repository::query( array_filter( $args ) );
	}
}
