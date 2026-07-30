<?php
/**
 * Staff admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;
use MRBooking\WorkingHours\Hours_Repository;
use MRBooking\WorkingHours\Staff_Time_Block_Repository;

defined( 'ABSPATH' ) || exit;

final class Staff_Page {

	public static function render(): void {
		$settings   = Settings::get();
		$hours_mode = (string) ( $settings['hours_mode'] ?? 'global' );
		$staff_list = Staff_Repository::all();
		$edit_id    = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$editing    = $edit_id > 0;
		$open_new   = ! empty( $_GET['new'] );
		$staff      = $edit_id ? Staff_Repository::find( $edit_id ) : null;
		$services   = Service_Repository::all( 'active' );
		$linked     = $staff ? Staff_Repository::service_ids( (int) $staff->id ) : array();
		$labels     = Helpers::weekday_labels();
		$order      = array( 6, 0, 1, 2, 3, 4, 5 );
		$hours_grouped   = array();
		$blocks_grouped  = array();
		$global_break       = max( 0, (int) ( $settings['break_between_appointments'] ?? 0 ) );
		$new_hours_grouped  = Hours_Repository::all_grouped( null );

		if ( $staff ) {
			$blocks_grouped = Staff_Time_Block_Repository::grouped_by_day( (int) $staff->id );
			if ( 'per_staff' === $hours_mode ) {
				$hours_grouped = Hours_Repository::all_grouped( (int) $staff->id );
			}
		}

		include MR_BOOKING_PATH . 'templates/admin/staff.php';
	}

	public static function save(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_save_staff' );

		$settings   = Settings::get();
		$hours_mode = (string) ( $settings['hours_mode'] ?? 'global' );
		$id          = absint( $_POST['id'] ?? 0 );
		$was_new     = $id === 0;
		$service_ids = array_map( 'absint', (array) ( $_POST['service_ids'] ?? array() ) );

		$id = Staff_Repository::save(
			array(
				'first_name'    => wp_unslash( $_POST['first_name'] ?? '' ),
				'last_name'     => wp_unslash( $_POST['last_name'] ?? '' ),
				'phone'         => wp_unslash( $_POST['phone'] ?? '' ),
				'email'         => wp_unslash( $_POST['email'] ?? '' ),
				'image_id'      => absint( $_POST['image_id'] ?? 0 ),
				'status'        => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) ),
				'break_minutes' => absint( $_POST['break_minutes'] ?? 0 ),
			),
			$service_ids,
			$id
		);

		if ( $id > 0 ) {
			if ( isset( $_POST['days'] ) && is_array( $_POST['days'] ) && ( 'per_staff' === $hours_mode || $was_new ) ) {
				$days_raw = wp_unslash( $_POST['days'] ); // phpcs:ignore
				$days     = Hours_Repository::parse_week_input( $days_raw );
				if ( ! empty( $_POST['apply_all'] ) ) {
					$days = Hours_Repository::apply_template_to_open_days( $days );
				}
				Hours_Repository::save_week( $days, $id );
			}

			if ( ! $was_new ) {
				$blocks_raw = isset( $_POST['blocks'] ) && is_array( $_POST['blocks'] ) ? wp_unslash( $_POST['blocks'] ) : array(); // phpcs:ignore
				Staff_Time_Block_Repository::save_week( $id, Staff_Time_Block_Repository::parse_from_request( $blocks_raw ) );
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-staff&edit=' . $id . '&saved=1' ) );
		exit;
	}

	public static function delete(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_delete_staff' );
		Staff_Repository::delete( absint( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-staff&deleted=1' ) );
		exit;
	}
}
