<?php
/**
 * Booking repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Bookings;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Booking_Repository {

	/**
	 * @param array<string, mixed> $args
	 * @return list<object>
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;
		$b  = Helpers::table( 'bookings' );
		$c  = Helpers::table( 'customers' );
		$st = Helpers::table( 'staff' );
		$bs = Helpers::table( 'booking_services' );
		$sv = Helpers::table( 'services' );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'b.status = %s';
			$params[] = sanitize_text_field( (string) $args['status'] );
		}

		if ( ! empty( $args['customer_id'] ) ) {
			$where[]  = 'b.customer_id = %d';
			$params[] = absint( $args['customer_id'] );
		}

		if ( ! empty( $args['staff_id'] ) ) {
			$where[]  = 'b.staff_id = %d';
			$params[] = absint( $args['staff_id'] );
		}

		if ( ! empty( $args['service_id'] ) ) {
			$where[]  = "EXISTS (SELECT 1 FROM {$bs} bsx WHERE bsx.booking_id = b.id AND bsx.service_id = %d)";
			$params[] = absint( $args['service_id'] );
		}

		if ( ! empty( $args['date'] ) ) {
			$where[]  = 'DATE(b.start_datetime) = %s';
			$params[] = sanitize_text_field( (string) $args['date'] );
		}

		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'DATE(b.start_datetime) >= %s';
			$params[] = sanitize_text_field( (string) $args['date_from'] );
		}

		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'DATE(b.start_datetime) <= %s';
			$params[] = sanitize_text_field( (string) $args['date_to'] );
		}

		if ( ! empty( $args['phone'] ) ) {
			$where[]  = 'c.phone LIKE %s';
			$params[] = '%' . $wpdb->esc_like( Helpers::sanitize_mobile( (string) $args['phone'] ) ) . '%';
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$where[]  = '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.phone LIKE %s OR b.booking_code LIKE %s OR CONCAT_WS(\' \', c.first_name, c.last_name) LIKE %s)';
			array_push( $params, $like, $like, $like, $like, $like );
		}

		if ( ! empty( $args['exclude_statuses'] ) && is_array( $args['exclude_statuses'] ) ) {
			$statuses = array_map( 'sanitize_text_field', $args['exclude_statuses'] );
			$place    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			$where[]  = "b.status NOT IN ($place)";
			$params   = array_merge( $params, $statuses );
		}

		$limit  = absint( $args['limit'] ?? 100 );
		$offset = absint( $args['offset'] ?? 0 );
		$order  = ( isset( $args['order'] ) && 'ASC' === strtoupper( (string) $args['order'] ) ) ? 'ASC' : 'DESC';

		$sql = "SELECT b.*, c.first_name, c.last_name, c.phone, c.email,
				TRIM(CONCAT_WS(' ', st.first_name, st.last_name)) AS staff_name,
				(
					SELECT GROUP_CONCAT(s.name ORDER BY s.sort_order ASC, s.id ASC SEPARATOR '، ')
					FROM {$bs} bs
					LEFT JOIN {$sv} s ON s.id = bs.service_id
					WHERE bs.booking_id = b.id
				) AS service_names
			FROM {$b} b
			LEFT JOIN {$c} c ON c.id = b.customer_id
			LEFT JOIN {$st} st ON st.id = b.staff_id
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY b.start_datetime {$order}
			LIMIT %d OFFSET %d";

		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: array(); // phpcs:ignore
	}

	/**
	 * Count bookings grouped by status (for filter chips).
	 *
	 * @return array<string, int>
	 */
	public static function count_by_status( array $args = array() ): array {
		global $wpdb;
		$b  = Helpers::table( 'bookings' );
		$c  = Helpers::table( 'customers' );
		$bs = Helpers::table( 'booking_services' );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['staff_id'] ) ) {
			$where[]  = 'b.staff_id = %d';
			$params[] = absint( $args['staff_id'] );
		}
		if ( ! empty( $args['service_id'] ) ) {
			$where[]  = "EXISTS (SELECT 1 FROM {$bs} bsx WHERE bsx.booking_id = b.id AND bsx.service_id = %d)";
			$params[] = absint( $args['service_id'] );
		}
		if ( ! empty( $args['date_from'] ) ) {
			$where[]  = 'DATE(b.start_datetime) >= %s';
			$params[] = sanitize_text_field( (string) $args['date_from'] );
		}
		if ( ! empty( $args['date_to'] ) ) {
			$where[]  = 'DATE(b.start_datetime) <= %s';
			$params[] = sanitize_text_field( (string) $args['date_to'] );
		}
		if ( ! empty( $args['phone'] ) ) {
			$where[]  = 'c.phone LIKE %s';
			$params[] = '%' . $wpdb->esc_like( Helpers::sanitize_mobile( (string) $args['phone'] ) ) . '%';
		}
		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( (string) $args['search'] ) . '%';
			$where[]  = '(c.first_name LIKE %s OR c.last_name LIKE %s OR c.phone LIKE %s OR b.booking_code LIKE %s OR CONCAT_WS(\' \', c.first_name, c.last_name) LIKE %s)';
			array_push( $params, $like, $like, $like, $like, $like );
		}

		$sql = "SELECT b.status, COUNT(*) AS total
			FROM {$b} b
			LEFT JOIN {$c} c ON c.id = b.customer_id
			WHERE " . implode( ' AND ', $where ) . '
			GROUP BY b.status';

		$rows = $params
			? ( $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: array() ) // phpcs:ignore
			: ( $wpdb->get_results( $sql ) ?: array() ); // phpcs:ignore

		$out = array();
		foreach ( $rows as $row ) {
			$out[ (string) $row->status ] = (int) $row->total;
		}

		return $out;
	}

	public static function find( int $id ): ?object {
		global $wpdb;
		$b = Helpers::table( 'bookings' );
		$c = Helpers::table( 'customers' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, c.first_name, c.last_name, c.phone, c.email, c.birth_date
				FROM {$b} b LEFT JOIN {$c} c ON c.id = b.customer_id WHERE b.id = %d", // phpcs:ignore
				$id
			)
		);

		return $row ?: null;
	}

	public static function find_by_code( string $code ): ?object {
		global $wpdb;
		$b = Helpers::table( 'bookings' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$b} WHERE booking_code = %s", $code ) ); // phpcs:ignore

		return $row ?: null;
	}

	/**
	 * @return list<object>
	 */
	public static function services( int $booking_id ): array {
		global $wpdb;
		$bs = Helpers::table( 'booking_services' );
		$s  = Helpers::table( 'services' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT bs.*, s.name FROM {$bs} bs LEFT JOIN {$s} s ON s.id = bs.service_id WHERE bs.booking_id = %d", // phpcs:ignore
				$booking_id
			)
		) ?: array();
	}

	/**
	 * Overlapping active bookings for a staff (or any) on a datetime range.
	 *
	 * @return list<object>
	 */
	public static function overlapping( string $start, string $end, ?int $staff_id = null, int $exclude_id = 0, int $padding_minutes = 0 ): array {
		global $wpdb;
		$b = Helpers::table( 'bookings' );

		if ( $padding_minutes > 0 ) {
			try {
				$start_dt = new \DateTimeImmutable( $start, wp_timezone() );
				$end_dt   = new \DateTimeImmutable( $end, wp_timezone() );
				$start    = $start_dt->modify( '-' . $padding_minutes . ' minutes' )->format( 'Y-m-d H:i:s' );
				$end      = $end_dt->modify( '+' . $padding_minutes . ' minutes' )->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				// Keep original range.
			}
		}

		$where  = array(
			"status NOT IN ('cancelled','rejected')",
			'start_datetime < %s',
			'end_datetime > %s',
		);
		$params = array( $end, $start );

		if ( $staff_id ) {
			$where[]  = 'staff_id = %d';
			$params[] = $staff_id;
		}

		if ( $exclude_id ) {
			$where[]  = 'id != %d';
			$params[] = $exclude_id;
		}

		$sql = "SELECT * FROM {$b} WHERE " . implode( ' AND ', $where );

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: array(); // phpcs:ignore
	}

	/**
	 * Bookings for a date (for slot blocking).
	 *
	 * @return list<object>
	 */
	public static function for_date( string $ymd, ?int $staff_id = null ): array {
		global $wpdb;
		$b = Helpers::table( 'bookings' );

		$where  = array(
			'DATE(start_datetime) = %s',
			"status NOT IN ('cancelled','rejected')",
		);
		$params = array( $ymd );

		if ( $staff_id ) {
			$where[]  = 'staff_id = %d';
			$params[] = $staff_id;
		}

		$sql = "SELECT * FROM {$b} WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_datetime ASC';

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: array(); // phpcs:ignore
	}

	/**
	 * @param array<string, mixed> $data
	 * @param list<array{service_id:int,duration:int,price:float}> $services
	 */
	public static function create( array $data, array $services ): int {
		global $wpdb;
		$table = Helpers::table( 'bookings' );
		$now   = current_time( 'mysql' );

		$code = self::generate_code();

		$row = array(
			'booking_code'     => $code,
			'customer_id'      => absint( $data['customer_id'] ),
			'staff_id'         => ! empty( $data['staff_id'] ) ? absint( $data['staff_id'] ) : null,
			'booking_for'      => sanitize_text_field( (string) ( $data['booking_for'] ?? 'myself' ) ),
			'booking_for_name' => sanitize_text_field( (string) ( $data['booking_for_name'] ?? '' ) ),
			'start_datetime'   => sanitize_text_field( (string) $data['start_datetime'] ),
			'end_datetime'     => sanitize_text_field( (string) $data['end_datetime'] ),
			'status'           => sanitize_text_field( (string) ( $data['status'] ?? 'pending' ) ),
			'total_price'      => (float) ( $data['total_price'] ?? 0 ),
			'total_duration'   => absint( $data['total_duration'] ?? 0 ),
			'notes'            => sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) ),
			'source'           => sanitize_text_field( (string) ( $data['source'] ?? 'frontend' ) ),
			'created_at'       => $now,
			'updated_at'       => $now,
		);

		$wpdb->insert( $table, $row );
		$id = (int) $wpdb->insert_id;

		$bs = Helpers::table( 'booking_services' );
		foreach ( $services as $svc ) {
			$wpdb->insert(
				$bs,
				array(
					'booking_id' => $id,
					'service_id' => absint( $svc['service_id'] ),
					'duration'   => absint( $svc['duration'] ),
					'price'      => (float) $svc['price'],
				)
			);
		}

		return $id;
	}

	public static function update_status( int $id, string $status ): bool {
		global $wpdb;
		$allowed = array_keys( Helpers::booking_statuses() );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		return false !== $wpdb->update(
			Helpers::table( 'bookings' ),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );

		return false !== $wpdb->update( Helpers::table( 'bookings' ), $data, array( 'id' => $id ) );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( Helpers::table( 'booking_services' ), array( 'booking_id' => $id ), array( '%d' ) );
		return false !== $wpdb->delete( Helpers::table( 'bookings' ), array( 'id' => $id ), array( '%d' ) );
	}

	public static function generate_code(): string {
		return 'MR' . strtoupper( wp_generate_password( 8, false, false ) );
	}

	public static function count_today(): int {
		global $wpdb;
		$b    = Helpers::table( 'bookings' );
		$today = current_time( 'Y-m-d' );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$b} WHERE DATE(start_datetime) = %s AND status NOT IN ('cancelled','rejected')", // phpcs:ignore
				$today
			)
		);
	}

	/**
	 * Stats for reports.
	 *
	 * @return array<string, int|float>
	 */
	public static function stats( string $from = '', string $to = '' ): array {
		global $wpdb;
		$b = Helpers::table( 'bookings' );

		$where  = array( '1=1' );
		$params = array();

		if ( $from ) {
			$where[]  = 'DATE(start_datetime) >= %s';
			$params[] = $from;
		}
		if ( $to ) {
			$where[]  = 'DATE(start_datetime) <= %s';
			$params[] = $to;
		}

		$w = implode( ' AND ', $where );

		$total = $params
			? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$b} WHERE {$w}", ...$params ) ) // phpcs:ignore
			: (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$b} WHERE {$w}" ); // phpcs:ignore

		$by_status = array();
		foreach ( array_keys( Helpers::booking_statuses() ) as $status ) {
			$p   = array_merge( $params, array( $status ) );
			$sql = "SELECT COUNT(*) FROM {$b} WHERE {$w} AND status = %s";
			$by_status[ $status ] = (int) $wpdb->get_var( $wpdb->prepare( $sql, ...$p ) ); // phpcs:ignore
		}

		$revenue_sql = "SELECT COALESCE(SUM(total_price),0) FROM {$b} WHERE {$w} AND status IN ('confirmed','completed')";
		$revenue     = $params
			? (float) $wpdb->get_var( $wpdb->prepare( $revenue_sql, ...$params ) ) // phpcs:ignore
			: (float) $wpdb->get_var( $revenue_sql ); // phpcs:ignore

		return array_merge(
			array(
				'total'   => $total,
				'revenue' => $revenue,
			),
			$by_status
		);
	}
}
