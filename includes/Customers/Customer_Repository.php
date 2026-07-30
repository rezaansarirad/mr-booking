<?php
/**
 * Customer repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Customers;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Customer_Repository {

	/**
	 * @param array<string, mixed> $args
	 * @return list<object>
	 */
	public static function query( array $args = array() ): array {
		global $wpdb;
		$table   = Helpers::table( 'customers' );
		$where   = array( '1=1' );
		$params  = array();
		$search  = sanitize_text_field( (string) ( $args['search'] ?? '' ) );
		$limit   = absint( $args['limit'] ?? 100 );
		$offset  = absint( $args['offset'] ?? 0 );
		$orderby_raw = (string) ( $args['orderby'] ?? 'id' );
		$orderby = in_array( $orderby_raw, array( 'id', 'first_name', 'last_name', 'phone', 'created_at', 'birth_date' ), true )
			? $orderby_raw
			: 'id';
		$order   = ( isset( $args['order'] ) && 'ASC' === strtoupper( (string) $args['order'] ) ) ? 'ASC' : 'DESC';

		if ( $search ) {
			$like    = '%' . $wpdb->esc_like( $search ) . '%';
			$phone_q = Helpers::sanitize_mobile( $search );
			if ( $phone_q && strlen( $phone_q ) >= 3 ) {
				$phone_like = '%' . $wpdb->esc_like( $phone_q ) . '%';
				$where[]    = '(first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s OR email LIKE %s OR CONCAT(first_name, " ", last_name) LIKE %s OR phone LIKE %s)';
				array_push( $params, $like, $like, $like, $like, $like, $phone_like );
			} else {
				$where[] = '(first_name LIKE %s OR last_name LIKE %s OR phone LIKE %s OR email LIKE %s OR CONCAT(first_name, " ", last_name) LIKE %s)';
				array_push( $params, $like, $like, $like, $like, $like );
			}
		}

		if ( ! empty( $args['birthday_today'] ) ) {
			$where[] = 'MONTH(birth_date) = MONTH(CURDATE()) AND DAY(birth_date) = DAY(CURDATE())';
		}

		if ( ! empty( $args['birthday_week'] ) ) {
			$where[] = 'DATE_FORMAT(birth_date, "%m-%d") BETWEEN DATE_FORMAT(CURDATE(), "%m-%d") AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), "%m-%d")';
		}

		$sql = 'SELECT * FROM ' . $table . ' WHERE ' . implode( ' AND ', $where ) . " ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) ) ?: array(); // phpcs:ignore
	}

	public static function find( int $id ): ?object {
		global $wpdb;
		$table = Helpers::table( 'customers' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ); // phpcs:ignore

		return $row ?: null;
	}

	public static function find_by_phone( string $phone ): ?object {
		global $wpdb;
		$table = Helpers::table( 'customers' );
		$phone = Helpers::sanitize_mobile( $phone );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE phone = %s", $phone ) ); // phpcs:ignore

		return $row ?: null;
	}

	/**
	 * Create or update by phone.
	 *
	 * @param array<string, mixed> $data
	 */
	public static function upsert( array $data ): int {
		$phone = Helpers::sanitize_mobile( (string) ( $data['phone'] ?? '' ) );
		$existing = $phone ? self::find_by_phone( $phone ) : null;

		return self::save( $data, $existing ? (int) $existing->id : 0 );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function save( array $data, int $id = 0 ): int {
		global $wpdb;
		$table = Helpers::table( 'customers' );
		$now   = current_time( 'mysql' );

		$row = array(
			'first_name' => sanitize_text_field( (string) ( $data['first_name'] ?? '' ) ),
			'last_name'  => sanitize_text_field( (string) ( $data['last_name'] ?? '' ) ),
			'phone'      => Helpers::sanitize_mobile( (string) ( $data['phone'] ?? '' ) ),
			'email'      => sanitize_email( (string) ( $data['email'] ?? '' ) ) ?: null,
			'birth_date' => self::sanitize_date( (string) ( $data['birth_date'] ?? '' ) ),
			'notes'      => sanitize_textarea_field( (string) ( $data['notes'] ?? '' ) ),
			'updated_at' => $now,
		);

		if ( ! empty( $data['user_id'] ) ) {
			$row['user_id'] = absint( $data['user_id'] );
		}

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) );
			return $id;
		}

		$row['created_at'] = $now;
		$wpdb->insert( $table, $row );

		return (int) $wpdb->insert_id;
	}

	private static function sanitize_date( string $date ): ?string {
		$date = Helpers::to_english_digits( trim( $date ) );
		if ( ! $date ) {
			return null;
		}
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return $date;
		}
		// Try Jalali.
		$g = \MRBooking\Calendar\Jalali::jalali_string_to_gregorian( $date );

		return $g ?: null;
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( Helpers::table( 'customers' ), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function stats( int $customer_id ): array {
		global $wpdb;
		$bookings = Helpers::table( 'bookings' );

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE customer_id = %d", $customer_id ) // phpcs:ignore
		);
		$cancelled = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE customer_id = %d AND status = 'cancelled'", $customer_id ) // phpcs:ignore
		);
		$no_show = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$bookings} WHERE customer_id = %d AND status = 'no_show'", $customer_id ) // phpcs:ignore
		);
		$last = $wpdb->get_var(
			$wpdb->prepare( "SELECT start_datetime FROM {$bookings} WHERE customer_id = %d ORDER BY start_datetime DESC LIMIT 1", $customer_id ) // phpcs:ignore
		);

		return array(
			'total'     => $total,
			'cancelled' => $cancelled,
			'no_show'   => $no_show,
			'last'      => $last,
		);
	}

	public static function count(): int {
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Helpers::table( 'customers' ) ); // phpcs:ignore
	}
}
