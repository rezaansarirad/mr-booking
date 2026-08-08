<?php
/**
 * Holidays & special dates admin.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Holidays\Holiday_Repository;
use MRBooking\WorkingHours\Hours_Repository;

defined( 'ABSPATH' ) || exit;

final class Holidays {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-holidays' );
		$holidays = Holiday_Repository::all();
		$specials = Hours_Repository::all_special_dates();

		include MR_BOOKING_PATH . 'templates/admin/holidays.php';
	}

	public static function save(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::HOLIDAYS );
		check_admin_referer( 'mr_booking_save_holiday' );

		Holiday_Repository::save(
			array(
				'holiday_date' => sanitize_text_field( wp_unslash( $_POST['holiday_date'] ?? '' ) ),
				'title'        => wp_unslash( $_POST['title'] ?? '' ),
				'is_official'  => ! empty( $_POST['is_official'] ),
				'is_closed'    => ! empty( $_POST['is_closed'] ),
			),
			absint( $_POST['id'] ?? 0 )
		);

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-holidays&saved=1' ) );
		exit;
	}

	public static function delete(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::HOLIDAYS );
		check_admin_referer( 'mr_booking_delete_holiday' );
		Holiday_Repository::delete( absint( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-holidays&deleted=1' ) );
		exit;
	}

	public static function save_special(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::HOLIDAYS );
		check_admin_referer( 'mr_booking_save_special' );

		$type    = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'closed' ) );
		$periods = array();
		if ( 'special' === $type ) {
			$starts = (array) ( $_POST['start'] ?? array() );
			$ends   = (array) ( $_POST['end'] ?? array() );
			foreach ( $starts as $i => $start ) {
				$end = $ends[ $i ] ?? '';
				if ( $start && $end ) {
					$periods[] = array(
						'start' => sanitize_text_field( wp_unslash( (string) $start ) ),
						'end'   => sanitize_text_field( wp_unslash( (string) $end ) ),
					);
				}
			}
		}

		Hours_Repository::save_special_date(
			array(
				'special_date' => sanitize_text_field( wp_unslash( $_POST['special_date'] ?? '' ) ),
				'type'         => $type,
				'reason'       => wp_unslash( $_POST['reason'] ?? '' ),
				'note'         => wp_unslash( $_POST['note'] ?? '' ),
				'periods'      => $periods,
			),
			absint( $_POST['id'] ?? 0 )
		);

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-holidays&saved=1' ) );
		exit;
	}

	public static function delete_special(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::HOLIDAYS );
		check_admin_referer( 'mr_booking_delete_special' );
		Hours_Repository::delete_special_date( absint( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-holidays&deleted=1' ) );
		exit;
	}
}
