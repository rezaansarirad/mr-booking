<?php
/**
 * Notifications / templates admin.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-notifications' );
		$settings = Settings::get();
		include MR_BOOKING_PATH . 'templates/admin/notifications.php';
	}
}
