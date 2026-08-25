<?php
/**
 * Plugin Name:       MR Booking
 * Plugin URI:        https://github.com/rezaansarirad/mr-booking
 * Description:       سیستم حرفه‌ای مدیریت نوبت و رزرو — تقویم شمسی/میلادی، خدمات، پرسنل، پیامک و ایمیل.
 * Version:           2.5.2
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:            Reza Ansarirad
 * Author URI:        https://github.com/rezaansarirad
 * Text Domain:       mr-booking
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package MRBooking
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MR_BOOKING_VERSION', '2.5.2' );
define( 'MR_BOOKING_FILE', __FILE__ );
define( 'MR_BOOKING_PATH', plugin_dir_path( __FILE__ ) );
define( 'MR_BOOKING_URL', plugin_dir_url( __FILE__ ) );
define( 'MR_BOOKING_BASENAME', plugin_basename( __FILE__ ) );
define( 'MR_BOOKING_DB_VERSION', '1.3.0' );

require_once MR_BOOKING_PATH . 'includes/Autoloader.php';
MRBooking\Autoloader::register();

register_activation_hook( __FILE__, array( 'MRBooking\\Database\\Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MRBooking\\Database\\Installer', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'mr-booking', false, dirname( MR_BOOKING_BASENAME ) . '/languages' );
		MRBooking\Plugin::instance()->boot();
	}
);
