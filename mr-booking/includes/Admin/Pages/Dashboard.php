<?php
/**
 * Admin dashboard page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Dashboard {

	public static function render(): void {
		Helpers::require_page( 'mr-booking' );
		$today = current_time( 'Y-m-d' );
		$stats = Booking_Repository::stats( gmdate( 'Y-m-01' ), gmdate( 'Y-m-t' ) );
		$today_bookings = Booking_Repository::query(
			array(
				'date'    => $today,
				'limit'   => 50,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			)
		);
		$recent = Booking_Repository::query(
			array(
				'limit'   => 15,
				'orderby' => 'created_at',
				'order'   => 'DESC',
			)
		);
		$upcoming = Booking_Repository::query(
			array(
				'date_from' => $today,
				'limit'     => 10,
				'order'     => 'ASC',
				'exclude_statuses' => array( 'cancelled', 'rejected' ),
			)
		);

		$show_form_help = ! empty( Settings::get_value( 'dashboard_show_form_help', 1 ) );

		include MR_BOOKING_PATH . 'templates/admin/dashboard.php';
	}

	public static function hide_form_help(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::DASHBOARD );
		check_admin_referer( 'mr_booking_hide_form_help' );

		Settings::update( array( 'dashboard_show_form_help' => 0 ) );

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking' ) );
		exit;
	}
}
