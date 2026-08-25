<?php
/**
 * Services repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Services;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Service_Repository {

	/**
	 * Whether a service should expose a price.
	 */
	public static function has_price( object $service ): bool {
		if ( isset( $service->has_price ) ) {
			return (int) $service->has_price === 1;
		}
		return (float) ( $service->price ?? 0 ) > 0;
	}

	/**
	 * Default deposit for a price: 50%, rounded to the nearest 1,000.
	 */
	public static function default_deposit( float $price ): float {
		if ( $price <= 0 ) {
			return 0.0;
		}
		/**
		 * Filter the default deposit ratio (0–1).
		 *
		 * @param float $ratio
		 */
		$ratio = (float) apply_filters( 'mr_booking_default_deposit_ratio', 0.5 );

		return (float) ( round( $price * $ratio / 1000 ) * 1000 );
	}

	/**
	 * @return list<object>
	 */
	public static function all( string $status = '' ): array {
		global $wpdb;
		$table = Helpers::table( 'services' );

		if ( $status ) {
			return $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY sort_order ASC, id ASC", $status ) // phpcs:ignore
			) ?: array();
		}

		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC" ) ?: array(); // phpcs:ignore
	}

	public static function find( int $id ): ?object {
		global $wpdb;
		$table = Helpers::table( 'services' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore

		return $row ?: null;
	}

	/**
	 * Active services offered by a staff member.
	 *
	 * @return list<object>
	 */
	public static function for_staff( int $staff_id ): array {
		if ( $staff_id <= 0 ) {
			return self::all( 'active' );
		}

		$ids = \MRBooking\Staff\Staff_Repository::service_ids( $staff_id );
		if ( empty( $ids ) ) {
			global $wpdb;
			$link_count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Helpers::table( 'staff_services' ) ); // phpcs:ignore
			// No assignments configured yet → show all services.
			if ( 0 === $link_count ) {
				return self::all( 'active' );
			}
			return array();
		}

		global $wpdb;
		$table = Helpers::table( 'services' );
		$place = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id IN ($place) AND status = 'active' ORDER BY sort_order ASC, id ASC",
			...$ids
		); // phpcs:ignore

		return $wpdb->get_results( $query ) ?: array(); // phpcs:ignore
	}

	/**
	 * @param list<int> $ids
	 * @return list<object>
	 */
	public static function find_many( array $ids ): array {
		$ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		if ( empty( $ids ) ) {
			return array();
		}

		global $wpdb;
		$table = Helpers::table( 'services' );
		$place = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query = $wpdb->prepare( "SELECT * FROM {$table} WHERE id IN ($place) AND status = 'active'", ...$ids ); // phpcs:ignore

		return $wpdb->get_results( $query ) ?: array(); // phpcs:ignore
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function save( array $data, int $id = 0 ): int {
		global $wpdb;
		$table = Helpers::table( 'services' );
		$now   = current_time( 'mysql' );

		$status_raw = (string) ( $data['status'] ?? 'active' );
		$has_price  = ! empty( $data['has_price'] ) ? 1 : 0;
		$price      = $has_price ? (float) ( $data['price'] ?? 0 ) : 0.0;

		$row = array(
			'name'        => sanitize_text_field( (string) ( $data['name'] ?? '' ) ),
			'description' => sanitize_textarea_field( (string) ( $data['description'] ?? '' ) ),
			'duration'    => max( 5, absint( $data['duration'] ?? 30 ) ),
			'has_price'   => $has_price,
			'price'       => $price,
			'deposit'     => max( 0.0, (float) ( $data['deposit'] ?? 0 ) ),
			'status'      => in_array( $status_raw, array( 'active', 'inactive' ), true ) ? $status_raw : 'active',
			'image_id'    => absint( $data['image_id'] ?? 0 ) ?: null,
			'color'       => sanitize_hex_color( (string) ( $data['color'] ?? '' ) ) ?: null,
			'sort_order'  => (int) ( $data['sort_order'] ?? 0 ),
			'updated_at'  => $now,
		);

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) );
			return $id;
		}

		$row['created_at'] = $now;
		$wpdb->insert( $table, $row );

		return (int) $wpdb->insert_id;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( Helpers::table( 'services' ), array( 'id' => $id ), array( '%d' ) );
	}
}
