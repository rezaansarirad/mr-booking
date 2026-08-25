<?php
/**
 * Reports admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;

defined( 'ABSPATH' ) || exit;

final class Reports {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-reports' );
		$from  = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : gmdate( 'Y-m-01' );
		$to    = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : gmdate( 'Y-m-d' );
		$stats = Booking_Repository::stats( $from, $to );
		$today = Booking_Repository::count_today();
		$customers = Customer_Repository::count();

		global $wpdb;
		$bs = Helpers::table( 'booking_services' );
		$s  = Helpers::table( 'services' );
		$b  = Helpers::table( 'bookings' );
		$popular = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.name, COUNT(*) as cnt FROM {$bs} bs
				INNER JOIN {$b} b ON b.id = bs.booking_id
				INNER JOIN {$s} s ON s.id = bs.service_id
				WHERE DATE(b.start_datetime) BETWEEN %s AND %s
				GROUP BY bs.service_id
				ORDER BY cnt DESC LIMIT 10", // phpcs:ignore
				$from,
				$to
			)
		);

		include MR_BOOKING_PATH . 'templates/admin/reports.php';
	}
}
