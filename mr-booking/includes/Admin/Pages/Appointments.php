<?php
/**
 * Appointments admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Appointments {

	private const FILTERS_META = 'mr_booking_appt_filters';

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

		$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'newest';
		if ( ! isset( self::sort_options()[ $sort ] ) ) {
			$sort = 'newest';
		}

		$sort_query = self::sort_query_args( $sort );

		$filters = array(
			'status'     => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'date_from'  => $date_from,
			'date_to'    => $date_to,
			'phone'      => isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '',
			'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'staff_id'   => isset( $_GET['staff_id'] ) ? absint( $_GET['staff_id'] ) : 0,
			'service_id' => isset( $_GET['service_id'] ) ? absint( $_GET['service_id'] ) : 0,
			'preset'     => $preset,
			'sort'       => $sort,
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
				'orderby'    => $sort_query['orderby'],
				'order'      => $sort_query['order'],
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
		$booking_customer  = $booking ? self::customer_for_booking( $booking ) : null;
		$can_edit_customer = (bool) $booking_customer;
		$updated        = isset( $_GET['updated'] );
		$notify_feedback = null;
		if ( is_user_logged_in() ) {
			$notify_feedback = get_transient( 'mrb_notify_feedback_' . get_current_user_id() );
			if ( $notify_feedback ) {
				delete_transient( 'mrb_notify_feedback_' . get_current_user_id() );
			}
		}
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
		if ( $booking ) {
			$stored   = self::get_stored_filters();
			$base_url = $stored ? self::list_url( $stored ) : admin_url( 'admin.php?page=mr-booking-appointments' );
		} else {
			self::persist_filters( $filters, $has_filters );
			$base_url = self::list_url( $filters );
		}

		$nearest_booking = null;
		if ( ! $booking ) {
			$nearest_args = array_filter(
				array(
					'status'     => $filters['status'],
					'phone'      => $filters['phone'],
					'search'     => $filters['search'],
					'staff_id'   => $filters['staff_id'],
					'service_id' => $filters['service_id'],
				)
			);
			$nearest_args['datetime_from'] = current_time( 'mysql' );
			$nearest_args['limit']         = 1;
			$nearest_args['orderby']       = 'start_datetime';
			$nearest_args['order']         = 'ASC';
			if ( empty( $nearest_args['status'] ) ) {
				$nearest_args['exclude_statuses'] = array( 'cancelled', 'rejected' );
			}
			$nearest_rows    = Booking_Repository::query( $nearest_args );
			$nearest_booking = $nearest_rows[0] ?? null;
		}

		$args = $filters; // Backward-compatible template variable.

		include MR_BOOKING_PATH . 'templates/admin/appointments.php';
	}

	/**
	 * Restore saved appointment filters before the list renders.
	 */
	public static function maybe_restore_filters(): void {
		if ( ! is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'mr-booking-appointments' !== $page ) {
			return;
		}

		if ( ! Helpers::user_can( \MRBooking\Roles\Capabilities::APPOINTMENTS ) ) {
			return;
		}

		if ( ! empty( $_GET['view'] ) ) {
			return;
		}

		if ( ! empty( $_GET['filters_cleared'] ) ) {
			self::clear_stored_filters();
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-appointments' ) );
			exit;
		}

		if ( ! empty( $_GET['applied'] ) ) {
			return;
		}

		if ( ! self::is_bare_list_request() ) {
			return;
		}

		$stored = self::get_stored_filters();
		if ( ! $stored ) {
			return;
		}

		$url = self::filter_url( array(), $stored );
		if ( ! empty( $stored['preset'] ) ) {
			$url = self::filter_url(
				array(
					'date_from' => '',
					'date_to'   => '',
				),
				$stored
			);
		}
		foreach ( self::notice_query_keys() as $key ) {
			if ( ! isset( $_GET[ $key ] ) ) {
				continue;
			}
			$url = add_query_arg( $key, sanitize_text_field( wp_unslash( $_GET[ $key ] ) ), $url );
		}

		if ( $url === self::filter_url( array(), array() ) ) {
			return;
		}

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function get_stored_filters(): ?array {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return null;
		}

		$stored = get_user_meta( $uid, self::FILTERS_META, true );
		if ( ! is_array( $stored ) ) {
			return null;
		}

		$sort = sanitize_key( (string) ( $stored['sort'] ?? 'newest' ) );
		if ( ! isset( self::sort_options()[ $sort ] ) ) {
			$sort = 'newest';
		}

		$filters = array(
			'search'     => sanitize_text_field( (string) ( $stored['search'] ?? '' ) ),
			'phone'      => sanitize_text_field( (string) ( $stored['phone'] ?? '' ) ),
			'status'     => sanitize_text_field( (string) ( $stored['status'] ?? '' ) ),
			'staff_id'   => absint( $stored['staff_id'] ?? 0 ),
			'service_id' => absint( $stored['service_id'] ?? 0 ),
			'date_from'  => sanitize_text_field( (string) ( $stored['date_from'] ?? '' ) ),
			'date_to'    => sanitize_text_field( (string) ( $stored['date_to'] ?? '' ) ),
			'preset'     => sanitize_key( (string) ( $stored['preset'] ?? '' ) ),
			'sort'       => $sort,
		);

		$has_value = (bool) array_filter(
			array(
				$filters['search'],
				$filters['phone'],
				$filters['status'],
				$filters['staff_id'],
				$filters['service_id'],
				$filters['date_from'],
				$filters['date_to'],
				$filters['preset'],
			)
		);

		if ( ! $has_value && 'newest' === $filters['sort'] ) {
			return null;
		}

		return $filters;
	}

	/**
	 * @param array<string, mixed> $filters
	 */
	private static function persist_filters( array $filters, bool $has_filters ): void {
		$uid = get_current_user_id();
		if ( ! $uid ) {
			return;
		}

		$custom_sort = ! empty( $filters['sort'] ) && 'newest' !== $filters['sort'];
		if ( ! $has_filters && ! $custom_sort ) {
			if ( ! empty( $_GET['applied'] ) ) {
				self::clear_stored_filters();
			}
			return;
		}

		update_user_meta( $uid, self::FILTERS_META, $filters );
	}

	private static function clear_stored_filters(): void {
		$uid = get_current_user_id();
		if ( $uid ) {
			delete_user_meta( $uid, self::FILTERS_META );
		}
	}

	private static function is_bare_list_request(): bool {
		$ignore = array(
			'page',
			'view',
			'updated',
			'approved',
			'rejected',
			'deleted',
			'slot_freed',
			'customer_saved',
			'error',
			'filters_cleared',
			'applied',
		);

		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = sanitize_key( (string) $key );
			if ( in_array( $key, $ignore, true ) ) {
				continue;
			}
			if ( 'sort' === $key ) {
				$sort = sanitize_key( wp_unslash( (string) $value ) );
				if ( '' === $sort || 'newest' === $sort ) {
					continue;
				}
			}
			$val = is_array( $value ) ? '' : trim( sanitize_text_field( wp_unslash( (string) $value ) ) );
			if ( '' === $val || '0' === $val ) {
				continue;
			}
			return false;
		}

		return true;
	}

	/**
	 * @return list<string>
	 */
	private static function notice_query_keys(): array {
		return array( 'updated', 'approved', 'rejected', 'deleted', 'slot_freed', 'customer_saved', 'error' );
	}

	/**
	 * Resolve the customer linked to a booking.
	 */
	private static function customer_for_booking( object $booking ): ?object {
		$customer_id = absint( $booking->customer_id ?? 0 );
		if ( ! $customer_id ) {
			$customer_id = absint( $booking->joined_customer_id ?? 0 );
		}

		if ( $customer_id ) {
			$customer = Customer_Repository::find( $customer_id );
			if ( $customer ) {
				return $customer;
			}
		}

		$phone = (string) ( $booking->phone ?? '' );
		if ( $phone ) {
			$by_phone = Customer_Repository::find_by_phone( $phone );
			if ( $by_phone ) {
				return $by_phone;
			}
		}

		return null;
	}

	/**
	 * List URL with current (or stored) filters.
	 *
	 * @param array<string, mixed> $filters
	 */
	public static function list_url( array $filters ): string {
		return self::filter_url( array(), $filters );
	}

	/**
	 * Build filter URL preserving current query params.
	 *
	 * @param array<string, mixed> $overrides
	 * @param array<string, mixed> $current
	 */
	public static function filter_url( array $overrides, array $current ): string {
		$keys = array( 's', 'phone', 'status', 'staff_id', 'service_id', 'date_from', 'date_to', 'preset', 'sort' );
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
			'sort'       => $current['sort'] ?? 'newest',
		);

		foreach ( $overrides as $key => $value ) {
			$map[ $key ] = $value;
		}

		foreach ( $keys as $key ) {
			$val = $map[ $key ] ?? '';
			if ( '' === $val || 0 === $val || '0' === $val ) {
				continue;
			}
			if ( 'sort' === $key && 'newest' === $val ) {
				continue;
			}
			$q[ $key ] = $val;
		}

		return add_query_arg( $q, admin_url( 'admin.php' ) );
	}

	/**
	 * Available list sort modes.
	 *
	 * @return array<string, string>
	 */
	public static function sort_options(): array {
		return array(
			'newest'      => __( 'آخرین ثبت', 'mr-booking' ),
			'nearest'     => __( 'نزدیک‌ترین نوبت', 'mr-booking' ),
			'latest_slot' => __( 'دیرترین نوبت', 'mr-booking' ),
		);
	}

	/**
	 * Map a sort mode to repository query args.
	 *
	 * @return array{orderby: string, order: string}
	 */
	public static function sort_query_args( string $sort ): array {
		switch ( $sort ) {
			case 'nearest':
				return array(
					'orderby' => 'nearest',
					'order'   => 'ASC',
				);
			case 'latest_slot':
				return array(
					'orderby' => 'start_datetime',
					'order'   => 'DESC',
				);
			default:
				return array(
					'orderby' => 'created_at',
					'order'   => 'DESC',
				);
		}
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
