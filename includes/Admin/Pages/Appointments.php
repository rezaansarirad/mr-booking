<?php
/**
 * Appointments admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Appointments {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-appointments' );
		$now       = new \DateTimeImmutable( 'now', wp_timezone() );
		$today     = $now->format( 'Y-m-d' );
		$tomorrow  = $now->modify( '+1 day' )->format( 'Y-m-d' );
		$week_end  = $now->modify( '+6 days' )->format( 'Y-m-d' );
		$month_end = $now->format( 'Y-m-t' );

		$preset = isset( $_GET['preset'] ) ? sanitize_key( wp_unslash( $_GET['preset'] ) ) : '';

		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

		if ( $preset && ! $date_from && ! $date_to ) {
			switch ( $preset ) {
				case 'today':
					$date_from = $date_to = $today;
					break;
				case 'tomorrow':
					$date_from = $date_to = $tomorrow;
					break;
				case 'week':
					$date_from = $today;
					$date_to   = $week_end;
					break;
				case 'month':
					$date_from = $now->format( 'Y-m-01' );
					$date_to   = $month_end;
					break;
			}
		} elseif ( ! $preset && $date_from && $date_to ) {
			if ( $date_from === $today && $date_to === $today ) {
				$preset = 'today';
			} elseif ( $date_from === $tomorrow && $date_to === $tomorrow ) {
				$preset = 'tomorrow';
			} elseif ( $date_from === $today && $date_to === $week_end ) {
				$preset = 'week';
			} elseif ( $date_from === $now->format( 'Y-m-01' ) && $date_to === $month_end ) {
				$preset = 'month';
			}
		}

		$filters = array(
			'status'     => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'date_from'  => $date_from,
			'date_to'    => $date_to,
			'phone'      => isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '',
			'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'staff_id'   => isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0,
			'service_id' => isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0,
			'preset'     => $preset,
		);

		$query_args = array_filter(
			array(
				'status'     => $filters['status'],
				'date_from'  => $filters['date_from'],
				'date_to'    => $filters['date_to'],
				'phone'      => $filters['phone'],
				'search'     => $filters['search'],
				'staff_id'   => $filters['staff_id'],
				'service_id' => $filters['service_id'],
				'limit'      => 150,
				'orderby'    => 'created_at',
				'order'      => 'DESC',
			)
		);

		$bookings      = Booking_Repository::query( $query_args );
		$status_counts = Booking_Repository::count_by_status(
			array_filter(
				array(
					'date_from'  => $filters['date_from'],
					'date_to'    => $filters['date_to'],
					'phone'      => $filters['phone'],
					'search'     => $filters['search'],
					'staff_id'   => $filters['staff_id'],
					'service_id' => $filters['service_id'],
				)
			)
		);
		$statuses = Helpers::booking_statuses();
		$staff    = Staff_Repository::all();
		$services = Service_Repository::all();

		$view_id        = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		$booking        = $view_id ? Booking_Repository::find( $view_id ) : null;
		$booking_svcs   = $booking ? Booking_Repository::services( (int) $booking->id ) : array();
		$booking_staff  = ( $booking && ! empty( $booking->staff_id ) ) ? Staff_Repository::find( (int) $booking->staff_id ) : null;
		$updated        = isset( $_GET['updated'] );
		$notify_feedback = null;
		if ( is_user_logged_in() ) {
			$notify_feedback = get_transient( 'mrb_notify_feedback_' . get_current_user_id() );
			if ( $notify_feedback ) {
				delete_transient( 'mrb_notify_feedback_' . get_current_user_id() );
			}
		}
		$base_url       = admin_url( 'admin.php?page=mr-booking-appointments' );
		$has_filters    = (bool) array_filter(
			array(
				$filters['status'],
				$filters['date_from'],
				$filters['date_to'],
				$filters['phone'],
				$filters['search'],
				$filters['staff_id'],
				$filters['service_id'],
				$filters['preset'],
			)
		);

		$args = $filters; // Backward-compatible template variable.

		include MR_BOOKING_PATH . 'templates/admin/appointments.php';
	}

	/**
	 * Build filter URL preserving current query params.
	 *
	 * @param array<string, mixed> $overrides
	 * @param array<string, mixed> $current
	 */
	public static function filter_url( array $overrides, array $current ): string {
		$keys = array( 's', 'phone', 'status', 'staff_id', 'service_id', 'date_from', 'date_to', 'preset' );
		$q    = array( 'page' => 'mr-booking-appointments' );

		$map = array(
			's'          => $current['search'] ?? '',
			'phone'      => $current['phone'] ?? '',
			'status'     => $current['status'] ?? '',
			'staff_id'   => $current['staff_id'] ?? 0,
			'service_id' => $current['service_id'] ?? 0,
			'date_from'  => $current['date_from'] ?? '',
			'date_to'    => $current['date_to'] ?? '',
			'preset'     => $current['preset'] ?? '',
		);

		foreach ( $overrides as $key => $value ) {
			$map[ $key ] = $value;
		}

		foreach ( $keys as $key ) {
			$val = $map[ $key ] ?? '';
			if ( '' === $val || 0 === $val || '0' === $val ) {
				continue;
			}
			$q[ $key ] = $val;
		}

		return add_query_arg( $q, admin_url( 'admin.php' ) );
	}

	public static function update_status(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::APPOINTMENTS );
		check_admin_referer( 'mr_booking_update_status' );

		$id       = absint( $_POST['booking_id'] ?? 0 );
		$status   = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
		$redirect = sanitize_text_field( wp_unslash( $_POST['redirect'] ?? 'view' ) );
		$booking  = $id ? Booking_Repository::find( $id ) : null;
		$old_status = $booking ? (string) $booking->status : '';

		if ( $id && $booking && Booking_Repository::update_status( $id, $status ) && $old_status !== $status ) {
			$notify_result = \MRBooking\Notifications\Notification_Service::pull_last_notify_result();

			if ( $notify_result && is_user_logged_in() ) {
				set_transient(
					'mrb_notify_feedback_' . get_current_user_id(),
					$notify_result,
					MINUTE_IN_SECONDS
				);
			}
		}

		if ( 'list' === $redirect ) {
			$url = admin_url( 'admin.php?page=mr-booking-appointments&status=pending&updated=1' );
			if ( 'confirmed' === $status ) {
				$url = admin_url( 'admin.php?page=mr-booking-appointments&status=pending&approved=1' );
			} elseif ( in_array( $status, array( 'rejected', 'cancelled' ), true ) ) {
				$url = admin_url( 'admin.php?page=mr-booking-appointments&status=pending&rejected=1' );
			}
			wp_safe_redirect( $url );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $id . '&updated=1' ) );
		exit;
	}

	public static function cancel_booking(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::APPOINTMENTS );
		check_admin_referer( 'mr_booking_cancel_booking' );

		$id       = absint( $_POST['booking_id'] ?? 0 );
		$redirect = sanitize_text_field( wp_unslash( $_POST['redirect'] ?? 'view' ) );
		$booking  = $id ? Booking_Repository::find( $id ) : null;

		if ( $booking && Booking_Repository::is_upcoming( $booking ) && Booking_Repository::blocks_slot( (string) $booking->status ) ) {
			Booking_Repository::update_status( $id, 'cancelled' );
		}

		if ( 'list' === $redirect ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-appointments&slot_freed=1' ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $id . '&slot_freed=1' ) );
		exit;
	}

	public static function delete_booking(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::APPOINTMENTS );
		check_admin_referer( 'mr_booking_delete_booking' );

		$id      = absint( $_POST['booking_id'] ?? 0 );
		$booking = $id ? Booking_Repository::find( $id ) : null;

		if ( ! $booking ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-appointments' ) );
			exit;
		}

		if ( Booking_Repository::is_upcoming( $booking ) && Booking_Repository::blocks_slot( (string) $booking->status ) ) {
			$notify = new \MRBooking\Notifications\Notification_Service();
			$notify->notify_customer( $id, 'cancelled' );
		}

		Booking_Repository::delete_with_logs( $id );

		/**
		 * Fires after a booking is permanently deleted.
		 *
		 * @param int    $id      Deleted booking ID.
		 * @param object $booking Booking object before deletion.
		 */
		do_action( 'mr_booking_booking_deleted', $id, $booking );

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-appointments&deleted=1&slot_freed=1' ) );
		exit;
	}
}
