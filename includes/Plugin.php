<?php
/**
 * Main plugin bootstrap.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking;

use MRBooking\Admin\Admin;
use MRBooking\Admin\Admin_Bar;
use MRBooking\API\Rest_Controller;
use MRBooking\Elementor\Bootstrap as Elementor_Bootstrap;
use MRBooking\Frontend\Assets as Frontend_Assets;
use MRBooking\Frontend\Shortcode;
use MRBooking\Notifications\Notification_Service;
use MRBooking\Privacy\Privacy;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		Settings::init();

		( new Admin_Bar() )->hooks();

		if ( is_admin() ) {
			( new Admin() )->hooks();
		}

		( new Shortcode() )->hooks();
		( new Frontend_Assets() )->hooks();
		( new Rest_Controller() )->hooks();
		( new Notification_Service() )->hooks();
		( new Privacy() )->hooks();
		( new Elementor_Bootstrap() )->hooks();

		add_action( 'mr_booking_send_reminders', array( Notification_Service::class, 'send_reminders' ) );

		if ( ! wp_next_scheduled( 'mr_booking_send_reminders' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'mr_booking_send_reminders' );
		}
	}

	private function __construct() {}
}
