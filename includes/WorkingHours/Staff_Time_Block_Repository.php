<?php
/**
 * Recurring weekly time blocks per staff (e.g. lunch break).
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\WorkingHours;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Staff_Time_Block_Repository {

	/**
	 * @return list<array{start:string,end:string,label:string}>
	 */
	public static function for_day( int $staff_id, int $day_of_week ): array {
		global $wpdb;
		$table = Helpers::table( 'staff_time_blocks' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT start_time, end_time, label FROM {$table} WHERE staff_id = %d AND day_of_week = %d ORDER BY start_time ASC", // phpcs:ignore
				$staff_id,
				$day_of_week
			)
		);

		$out = array();
		foreach ( $rows ?: array() as $row ) {
			$start = substr( (string) $row->start_time, 0, 5 );
			$end   = substr( (string) $row->end_time, 0, 5 );
			if ( '00:00' === $start && '00:00' === $end ) {
				continue;
			}
			$out[] = array(
				'start' => $start,
				'end'   => $end,
				'label' => (string) ( $row->label ?? '' ),
			);
		}

		return $out;
	}

	/**
	 * @return array<int, list<array{start:string,end:string,label:string}>>
	 */
	public static function grouped_by_day( int $staff_id ): array {
		$grouped = array();
		for ( $d = 0; $d <= 6; $d++ ) {
			$grouped[ $d ] = self::for_day( $staff_id, $d );
		}
		return $grouped;
	}

	/**
	 * Replace all recurring blocks for a staff member.
	 *
	 * @param array<int, list<array{start:string,end:string,label?:string}>> $days
	 */
	public static function save_week( int $staff_id, array $days ): void {
		global $wpdb;
		$table = Helpers::table( 'staff_time_blocks' );

		$wpdb->delete( $table, array( 'staff_id' => $staff_id ), array( '%d' ) );

		foreach ( $days as $dow => $blocks ) {
			$dow = (int) $dow;
			foreach ( $blocks as $block ) {
				if ( empty( $block['start'] ) || empty( $block['end'] ) ) {
					continue;
				}
				$start = Hours_Repository::normalize_time( (string) $block['start'] );
				$end   = Hours_Repository::normalize_time( (string) $block['end'] );
				if ( $start >= $end ) {
					continue;
				}
				$wpdb->insert(
					$table,
					array(
						'staff_id'    => $staff_id,
						'day_of_week' => $dow,
						'start_time'  => $start,
						'end_time'    => $end,
						'label'       => sanitize_text_field( (string) ( $block['label'] ?? '' ) ),
					),
					array( '%d', '%d', '%s', '%s', '%s' )
				);
			}
		}
	}

	public static function delete_for_staff( int $staff_id ): void {
		global $wpdb;
		$wpdb->delete( Helpers::table( 'staff_time_blocks' ), array( 'staff_id' => $staff_id ), array( '%d' ) );
	}

	/**
	 * @param array<mixed> $raw POST blocks[day][start][], etc.
	 * @return array<int, list<array{start:string,end:string,label:string}>>
	 */
	public static function parse_from_request( array $raw ): array {
		$days = array();
		foreach ( $raw as $dow => $data ) {
			$dow    = (int) $dow;
			$starts = (array) ( $data['start'] ?? array() );
			$ends   = (array) ( $data['end'] ?? array() );
			$labels = (array) ( $data['label'] ?? array() );
			$blocks = array();
			foreach ( $starts as $i => $start ) {
				$end = $ends[ $i ] ?? '';
				if ( ! $start || ! $end ) {
					continue;
				}
				$blocks[] = array(
					'start' => sanitize_text_field( (string) $start ),
					'end'   => sanitize_text_field( (string) $end ),
					'label' => sanitize_text_field( (string) ( $labels[ $i ] ?? '' ) ),
				);
			}
			if ( $blocks ) {
				$days[ $dow ] = $blocks;
			}
		}
		return $days;
	}
}
