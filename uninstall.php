<?php
/**
 * Uninstall cleanup.
 *
 * @package MRBooking
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$tables = array(
	'services',
	'staff',
	'staff_services',
	'customers',
	'bookings',
	'booking_services',
	'working_hours',
	'holidays',
	'special_dates',
	'notification_logs',
);

foreach ( $tables as $table ) {
	$name = $wpdb->prefix . 'mr_' . $table;
	$wpdb->query( "DROP TABLE IF EXISTS {$name}" ); // phpcs:ignore
}

delete_option( 'mr_booking_settings' );
delete_option( 'mr_booking_db_version' );
delete_option( 'mr_booking_setup_complete' );
delete_option( 'mr_booking_show_prices_migrated' );

$role = get_role( 'administrator' );
if ( $role ) {
	$role->remove_cap( 'manage_mr_booking' );
}
