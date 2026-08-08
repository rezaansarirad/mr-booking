<?php
/**
 * Phone / manual booking admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Phone_Booking {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-phone' );
		$settings = Settings::get();
		$services = Service_Repository::all( 'active' );
		$staff    = Staff_Repository::all( 'active' );
		$statuses = Helpers::booking_statuses();

		include MR_BOOKING_PATH . 'templates/admin/phone-booking.php';
	}
}
