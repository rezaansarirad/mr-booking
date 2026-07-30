<?php
/**
 * Working hours & special dates repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\WorkingHours;

use MRBooking\Helpers;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Hours_Repository {

	/**
	 * Whether working-hour rows include at least one open period.
	 *
	 * @param list<object> $rows
	 */
	private static function rows_have_open_periods( array $rows ): bool {
		foreach ( $rows as $row ) {
			if ( (int) $row->is_closed ) {
				continue;
			}
			$start = substr( (string) $row->start_time, 0, 5 );
			$end   = substr( (string) $row->end_time, 0, 5 );
			if ( '00:00' === $start && '00:00' === $end ) {
				continue;
			}
			return true;
		}

		return false;
	}

	/**
	 * @return list<object>
	 */
	public static function for_day( int $day_of_week, ?int $staff_id = null ): array {
		global $wpdb;
		$table = Helpers::table( 'working_hours' );
		$mode  = (string) Settings::get_value( 'hours_mode', 'global' );

		$effective_staff = ( 'per_staff' === $mode && $staff_id ) ? $staff_id : null;

		if ( $effective_staff ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE day_of_week = %d AND staff_id = %d ORDER BY start_time ASC", // phpcs:ignore
					$day_of_week,
					$effective_staff
				)
			);
			if ( ! empty( $rows ) && self::rows_have_open_periods( $rows ) ) {
				return $rows;
			}
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE day_of_week = %d AND staff_id IS NULL ORDER BY start_time ASC", // phpcs:ignore
				$day_of_week
			)
		) ?: array();
	}

	/**
	 * @return array<int, list<object>>
	 */
	public static function all_grouped( ?int $staff_id = null ): array {
		$grouped = array();
		for ( $d = 0; $d <= 6; $d++ ) {
			$grouped[ $d ] = self::for_day( $d, $staff_id );
		}
		return $grouped;
	}

	/**
	 * Replace all global working hours.
	 *
	 * @param array<int, list<array{start:string,end:string,closed?:bool}>> $days
	 */
	public static function save_week( array $days, ?int $staff_id = null ): void {
		global $wpdb;
		$table = Helpers::table( 'working_hours' );

		if ( $staff_id ) {
			$wpdb->delete( $table, array( 'staff_id' => $staff_id ), array( '%d' ) );
		} else {
			$wpdb->query( "DELETE FROM {$table} WHERE staff_id IS NULL" ); // phpcs:ignore
		}

		foreach ( $days as $dow => $periods ) {
			$dow = (int) $dow;
			if ( empty( $periods ) || ( ! empty( $periods[0]['closed'] ) ) ) {
				$wpdb->insert(
					$table,
					array(
						'day_of_week' => $dow,
						'start_time'  => '00:00:00',
						'end_time'    => '00:00:00',
						'is_closed'   => 1,
						'staff_id'    => $staff_id,
					)
				);
				continue;
			}

			foreach ( $periods as $period ) {
				if ( empty( $period['start'] ) || empty( $period['end'] ) ) {
					continue;
				}
				$wpdb->insert(
					$table,
					array(
						'day_of_week' => $dow,
						'start_time'  => self::normalize_time( (string) $period['start'] ),
						'end_time'    => self::normalize_time( (string) $period['end'] ),
						'is_closed'   => 0,
						'staff_id'    => $staff_id,
					)
				);
			}
		}
	}

	public static function normalize_time( string $time ): string {
		$time = Helpers::to_english_digits( $time );
		if ( preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			return $time . ':00';
		}
		if ( preg_match( '/^\d{2}:\d{2}:\d{2}$/', $time ) ) {
			return $time;
		}
		return '00:00:00';
	}

	/**
	 * Special date by Y-m-d.
	 */
	public static function special_date( string $ymd ): ?object {
		global $wpdb;
		$table = Helpers::table( 'special_dates' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE special_date = %s", $ymd ) ); // phpcs:ignore

		return $row ?: null;
	}

	/**
	 * @return list<object>
	 */
	public static function all_special_dates(): array {
		global $wpdb;
		$table = Helpers::table( 'special_dates' );
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY special_date ASC" ) ?: array(); // phpcs:ignore
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function save_special_date( array $data, int $id = 0 ): int {
		global $wpdb;
		$table = Helpers::table( 'special_dates' );

		$type_raw = (string) ( $data['type'] ?? 'closed' );
		$row = array(
			'special_date' => sanitize_text_field( (string) $data['special_date'] ),
			'type'         => in_array( $type_raw, array( 'closed', 'special' ), true ) ? $type_raw : 'closed',
			'reason'       => sanitize_text_field( (string) ( $data['reason'] ?? '' ) ),
			'note'         => sanitize_textarea_field( (string) ( $data['note'] ?? '' ) ),
			'periods'      => isset( $data['periods'] ) ? wp_json_encode( $data['periods'] ) : null,
		);

		if ( $id > 0 ) {
			$wpdb->update( $table, $row, array( 'id' => $id ) );
			return $id;
		}

		$wpdb->insert( $table, $row );
		return (int) $wpdb->insert_id;
	}

	public static function delete_special_date( int $id ): bool {
		global $wpdb;
		return false !== $wpdb->delete( Helpers::table( 'special_dates' ), array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Parse week hours from admin POST `days`.
	 *
	 * @param array<mixed> $days_raw
	 * @return array<int, list<array{start:string,end:string,closed?:bool}>>
	 */
	public static function parse_week_input( array $days_raw ): array {
		$days = array();

		foreach ( $days_raw as $dow => $data ) {
			$dow = (int) $dow;
			if ( ! empty( $data['closed'] ) ) {
				$days[ $dow ] = array( array( 'closed' => true ) );
				continue;
			}
			$periods = array();
			$starts  = (array) ( $data['start'] ?? array() );
			$ends    = (array) ( $data['end'] ?? array() );
			foreach ( $starts as $i => $start ) {
				$end = $ends[ $i ] ?? '';
				if ( $start && $end ) {
					$periods[] = array(
						'start' => sanitize_text_field( (string) $start ),
						'end'   => sanitize_text_field( (string) $end ),
					);
				}
			}
			$days[ $dow ] = $periods ?: array( array( 'closed' => true ) );
		}

		return $days;
	}

	/**
	 * Apply first open day template to all other open days.
	 *
	 * @param array<int, list<array{start:string,end:string,closed?:bool}>> $days
	 * @return array<int, list<array{start:string,end:string,closed?:bool}>>
	 */
	public static function apply_template_to_open_days( array $days ): array {
		$template = null;
		foreach ( $days as $periods ) {
			if ( ! empty( $periods ) && empty( $periods[0]['closed'] ) ) {
				$template = $periods;
				break;
			}
		}
		if ( ! $template ) {
			return $days;
		}
		foreach ( array_keys( $days ) as $dow ) {
			if ( empty( $days[ $dow ][0]['closed'] ) ) {
				$days[ $dow ] = $template;
			}
		}
		return $days;
	}

	public static function delete_for_staff( int $staff_id ): void {
		global $wpdb;
		$wpdb->delete( Helpers::table( 'working_hours' ), array( 'staff_id' => $staff_id ), array( '%d' ) );
	}
}
