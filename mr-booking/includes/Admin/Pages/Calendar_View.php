<?php
/**
 * Admin calendar view.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Calendar\Jalali;
use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Calendar_View {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-calendar' );
		$view  = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'month';
		$date  = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : current_time( 'Y-m-d' );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			$date = current_time( 'Y-m-d' );
		}

		$from = gmdate( 'Y-m-01', strtotime( $date ) );
		$to   = gmdate( 'Y-m-t', strtotime( $date ) );

		if ( 'week' === $view ) {
			$ts   = strtotime( $date );
			$dow  = (int) gmdate( 'w', $ts );
			// Start Saturday.
			$diff = ( $dow + 1 ) % 7;
			$from = gmdate( 'Y-m-d', strtotime( "-{$diff} days", $ts ) );
			$to   = gmdate( 'Y-m-d', strtotime( '+6 days', strtotime( $from ) ) );
		} elseif ( 'day' === $view ) {
			$from = $date;
			$to   = $date;
		}

		$bookings = Booking_Repository::query(
			array(
				'date_from' => $from,
				'date_to'   => $to,
				'limit'     => 500,
				'order'     => 'ASC',
			)
		);

		$by_date = array();
		foreach ( $bookings as $b ) {
			$d = substr( $b->start_datetime, 0, 10 );
			$by_date[ $d ][] = $b;
		}

		include MR_BOOKING_PATH . 'templates/admin/calendar.php';
	}
}
