<?php
/**
 * Customers admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Notifications\Notification_Service;

defined( 'ABSPATH' ) || exit;

final class Customers {

	public static function render(): void {
		Helpers::require_page( 'mr-booking-customers' );
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$args   = array(
			'search' => $search,
			'limit'  => 100,
		);

		if ( ! empty( $_GET['birthday_today'] ) ) {
			$args['birthday_today'] = 1;
		}
		if ( ! empty( $_GET['birthday_week'] ) ) {
			$args['birthday_week'] = 1;
		}

		$customers = Customer_Repository::query( $args );
		$view_id   = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		$customer  = $view_id ? Customer_Repository::find( $view_id ) : null;
		$stats     = $customer ? Customer_Repository::stats( (int) $customer->id ) : array();
		$history   = $customer
			? Booking_Repository::query(
				array(
					'customer_id' => (int) $customer->id,
					'limit'       => 50,
				)
			)
			: array();

		include MR_BOOKING_PATH . 'templates/admin/customers.php';
	}

	public static function send_message(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::CUSTOMERS );
		check_admin_referer( 'mr_booking_send_message' );

		$id      = absint( $_POST['customer_id'] ?? 0 );
		$channel = sanitize_text_field( wp_unslash( $_POST['channel'] ?? 'sms' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
		$subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );

		Notification_Service::send_custom( $id, $channel, $message, $subject );

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-customers&view=' . $id . '&sent=1' ) );
		exit;
	}

	public static function save(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::CUSTOMERS );
		check_admin_referer( 'mr_booking_save_customer' );

		$id = absint( $_POST['id'] ?? 0 );
		if ( ! $id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-customers' ) );
			exit;
		}

		$existing = Customer_Repository::find( $id );
		if ( ! $existing ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-customers' ) );
			exit;
		}

		$phone = Helpers::sanitize_mobile( wp_unslash( $_POST['phone'] ?? '' ) );
		if ( ! Helpers::is_valid_mobile( $phone ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-customers&view=' . $id . '&error=phone' ) );
			exit;
		}

		$other = Customer_Repository::find_by_phone( $phone );
		if ( $other && (int) $other->id !== $id ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-customers&view=' . $id . '&error=duplicate' ) );
			exit;
		}

		Customer_Repository::save(
			array(
				'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
				'last_name'  => wp_unslash( $_POST['last_name'] ?? '' ),
				'phone'      => $phone,
				'email'      => wp_unslash( $_POST['email'] ?? '' ),
				'birth_date' => wp_unslash( $_POST['birth_date'] ?? '' ),
				'notes'      => wp_unslash( $_POST['notes'] ?? '' ),
			),
			$id
		);

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-customers&view=' . $id . '&saved=1' ) );
		exit;
	}
}
