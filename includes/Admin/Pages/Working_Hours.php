<?php
/**
 * Working hours admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;
use MRBooking\WorkingHours\Hours_Repository;

defined( 'ABSPATH' ) || exit;

final class Working_Hours {

	public static function render(): void {
		$settings   = Settings::get();
		$hours_mode = (string) ( $settings['hours_mode'] ?? 'global' );
		$staff_list = Staff_Repository::all( 'active' );
		$staff_id   = isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0; // phpcs:ignore
		$selected   = $staff_id ? Staff_Repository::find( $staff_id ) : null;

		if ( 'per_staff' === $hours_mode && ! $staff_id && ! empty( $staff_list ) ) {
			$staff_id = (int) $staff_list[0]->id;
			$selected = $staff_list[0];
		}

		$scope_staff = ( 'per_staff' === $hours_mode && $staff_id ) ? $staff_id : null;
		$grouped     = Hours_Repository::all_grouped( $scope_staff );
		$labels      = Helpers::weekday_labels();
		$order       = array( 6, 0, 1, 2, 3, 4, 5 );

		include MR_BOOKING_PATH . 'templates/admin/working-hours.php';
	}

	public static function save(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_save_hours' );

		$settings   = Settings::get();
		$hours_mode = (string) ( $settings['hours_mode'] ?? 'global' );
		$staff_id   = absint( $_POST['staff_id'] ?? 0 );
		$scope      = ( 'per_staff' === $hours_mode && $staff_id ) ? $staff_id : null;

		if ( 'per_staff' === $hours_mode && ! $scope ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-hours&error=staff' ) );
			exit;
		}

		$apply_all = ! empty( $_POST['apply_all'] );
		$days_raw  = isset( $_POST['days'] ) && is_array( $_POST['days'] ) ? wp_unslash( $_POST['days'] ) : array(); // phpcs:ignore
		$days      = Hours_Repository::parse_week_input( $days_raw );

		if ( $apply_all && ! empty( $days ) ) {
			$days = Hours_Repository::apply_template_to_open_days( $days );
		}

		Hours_Repository::save_week( $days, $scope );

		$redirect = admin_url( 'admin.php?page=mr-booking-hours&saved=1' );
		if ( $scope ) {
			$redirect = add_query_arg( 'staff_id', $scope, $redirect );
		}
		wp_safe_redirect( $redirect );
		exit;
	}
}
