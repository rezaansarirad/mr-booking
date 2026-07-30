<?php
/**
 * Staff repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Staff;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Staff_Repository {

	/**
	 * @return list<object>
	 */
	public static function all( string $status = '' ): array {
		global $wpdb;
		$table = Helpers::table( 'staff' );

		if ( $status ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY first_name ASC", $status ) // phpcs:ignore
			) ?: array();
		}

		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY first_name ASC" ) ?: array(); // phpcs:ignore
	}

	public static function find( int $id ): ?object {
		global $wpdb;
		$table = Helpers::table( 'staff' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore

		return $row ?: null;
	}

	/**
	 * Staff who provide all given services.
	 *
	 * @param list<int> $service_ids
	 * @return list<object>
	 */
	public static function for_services( array $service_ids ): array {
		$service_ids = array_values( array_filter( array_map( 'intval', $service_ids ) ) );
		if ( empty( $service_ids ) ) {
			return self::all( 'active' );
		}

		global $wpdb;
		$staff_table = Helpers::table( 'staff' );
		$link_table  = Helpers::table( 'staff_services' );
		$needed      = count( $service_ids );
		$place       = implode( ',', array_fill( 0, $needed, '%d' ) );

		$sql = "SELECT s.* FROM {$staff_table} s
			INNER JOIN {$link_table} ls ON ls.staff_id = s.id
			WHERE s.status = 'active' AND ls.service_id IN ($place)
			GROUP BY s.id
			HAVING COUNT(DISTINCT ls.service_id) = %d
			ORDER BY s.first_name ASC";

		$args    = array_merge( $service_ids, array( $needed ) );
		$results = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ) ); // phpcs:ignore

		// If no staff-service links exist yet, fall back to all active staff.
		if ( empty( $results ) ) {
			$link_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$link_table}" ); // phpcs:ignore
			if ( 0 === $link_count ) {
				return self::all( 'active' );
			}
		}

		return $results ?: array();
	}

	/**
	 * @param array<string, mixed> $data
	 * @param list<int>            $service_ids
	 */
	public static function save( array $data, array $service_ids = array(), int $id = 0 ): int {
		global $wpdb;
		$table = Helpers::table( 'staff' );
		$now   = current_time( 'mysql' );

		$status_raw = (string) ( $data['status'] ?? 'active' );
		$row = array(
			'first_name'    => sanitize_text_field( (string) ( $data['first_name'] ?? '' ) ),
			'last_name'     => sanitize_text_field( (string) ( $data['last_name'] ?? '' ) ),
			'phone'         => Helpers::sanitize_mobile( (string) ( $data['phone'] ?? '' ) ),
			'email'         => sanitize_email( (string) ( $data['email'] ?? '' ) ),
			'image_id'      => absint( $data['image_id'] ?? 0 ) ?: null,
			'status'        => in_array( $status_raw, array( 'active', 'inactive' ), true ) ? $status_raw : 'active',
			'break_minutes' => max( 0, absint( $data['break_minutes'] ?? 0 ) ),
			'working_hours' => isset( $data['working_hours'] ) ? wp_json_encode( $data['working_hours'] ) : null,
			'updated_at'    => $now,
		);

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) );
		} else {
			$row['created_at'] = $now;
			$wpdb->insert( $table, $row );
			$id = (int) $wpdb->insert_id;
		}

		self::sync_services( $id, $service_ids );

		return $id;
	}

	/**
	 * @param list<int> $service_ids
	 */
	public static function sync_services( int $staff_id, array $service_ids ): void {
		global $wpdb;
		$link = Helpers::table( 'staff_services' );
		$wpdb->delete( $link, array( 'staff_id' => $staff_id ), array( '%d' ) );

		foreach ( $service_ids as $sid ) {
			$sid = absint( $sid );
			if ( $sid ) {
				$wpdb->insert(
					$link,
					array(
						'staff_id'   => $staff_id,
						'service_id' => $sid,
					),
					array( '%d', '%d' )
				);
			}
		}
	}

	/**
	 * @return list<int>
	 */
	public static function service_ids( int $staff_id ): array {
		global $wpdb;
		$link = Helpers::table( 'staff_services' );
		$ids  = $wpdb->get_col( $wpdb->prepare( "SELECT service_id FROM {$link} WHERE staff_id = %d", $staff_id ) ); // phpcs:ignore

		return array_map( 'intval', $ids ?: array() );
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		$wpdb->delete( Helpers::table( 'staff_services' ), array( 'staff_id' => $id ), array( '%d' ) );
		\MRBooking\WorkingHours\Hours_Repository::delete_for_staff( $id );
		\MRBooking\WorkingHours\Staff_Time_Block_Repository::delete_for_staff( $id );
		return false !== $wpdb->delete( Helpers::table( 'staff' ), array( 'id' => $id ), array( '%d' ) );
	}

	public static function display_name( object $staff ): string {
		return trim( $staff->first_name . ' ' . $staff->last_name );
	}
}
