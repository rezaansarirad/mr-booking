<?php
/**
 * Holidays repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Holidays;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Holiday_Repository {

	/**
	 * @return list<object>
	 */
	public static function all(): array {
		global $wpdb;
		$table = Helpers::table( 'holidays' );
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY holiday_date ASC" ) ?: array(); // phpcs:ignore
	}

	/**
	 * @return list<object>
	 */
	public static function in_range( string $from, string $to ): array {
		global $wpdb;
		$table = Helpers::table( 'holidays' );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE holiday_date BETWEEN %s AND %s ORDER BY holiday_date ASC", // phpcs:ignore
				$from,
				$to
			)
		) ?: array();
	}

	public static function find_by_date( string $ymd ): ?object {
		global $wpdb;
		$table = Helpers::table( 'holidays' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE holiday_date = %s", $ymd ) ); // phpcs:ignore

		return $row ?: null;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function save( array $data, int $id = 0 ): int {
		global $wpdb;
		$table = Helpers::table( 'holidays' );

		$row = array(
			'holiday_date' => sanitize_text_field( (string) $data['holiday_date'] ),
			'title'        => sanitize_text_field( (string) ( $data['title'] ?? '' ) ),
			'is_official'  => ! empty( $data['is_official'] ) ? 1 : 0,
			'is_closed'    => isset( $data['is_closed'] ) ? ( ! empty( $data['is_closed'] ) ? 1 : 0 ) : 1,
		);

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) );
			return $id;
		}

		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( Helpers::table( 'holidays' ), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Map of Y-m-d => holiday object for a month.
	 *
	 * @return array<string, object>
	 */
	public static function map_for_month( string $from, string $to ): array {
		$map = array();
		foreach ( self::in_range( $from, $to ) as $h ) {
			$map[ $h->holiday_date ] = $h;
		}
		return $map;
	}
}
