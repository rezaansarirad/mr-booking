<?php
/**
 * Notifications / templates admin.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Notifications {

	public static function render(): void {
		$settings = Settings::get();
		include MR_BOOKING_PATH . 'templates/admin/notifications.php';
	}
}
