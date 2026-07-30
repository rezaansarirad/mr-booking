<?php
/**
 * Booking creation service.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Bookings;

use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Booking_Service {

	/**
	 * Create booking from frontend/admin payload.
	 *
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool,error?:string,booking_id?:int,booking?:object}
	 */
	public static function create( array $payload ): array {
		$settings  = Settings::get();
		$source    = sanitize_text_field( (string) ( $payload['source'] ?? 'frontend' ) );
		$is_admin  = in_array( $source, array( 'phone', 'admin' ), true );

		$first = sanitize_text_field( (string) ( $payload['first_name'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $payload['last_name'] ?? '' ) );
		$phone = Helpers::sanitize_mobile( (string) ( $payload['phone'] ?? '' ) );
		$email = sanitize_email( (string) ( $payload['email'] ?? '' ) );

		if ( ! $first || ! $last ) {
			return array( 'ok' => false, 'error' => __( 'نام و نام خانوادگی الزامی است.', 'mr-booking' ) );
		}

		if ( ! Helpers::is_valid_mobile( $phone ) ) {
			return array( 'ok' => false, 'error' => __( 'شماره موبایل معتبر نیست.', 'mr-booking' ) );
		}

		if ( $email && ! is_email( $email ) ) {
			return array( 'ok' => false, 'error' => __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ) );
		}

		if ( ! $is_admin && ! empty( $settings['require_email'] ) && ! is_email( $email ) ) {
			return array( 'ok' => false, 'error' => __( 'ایمیل الزامی و باید معتبر باشد.', 'mr-booking' ) );
		}

		if ( ! $is_admin && ! empty( $settings['require_birth_date'] ) && empty( $payload['birth_date'] ) ) {
			return array( 'ok' => false, 'error' => __( 'تاریخ تولد الزامی است.', 'mr-booking' ) );
		}

		$booking_for = sanitize_text_field( (string) ( $payload['booking_for'] ?? 'myself' ) );
		if ( ! in_array( $booking_for, array( 'myself', 'child', 'other' ), true ) ) {
			$booking_for = 'myself';
		}

		$booking_for_name = sanitize_text_field( (string) ( $payload['booking_for_name'] ?? '' ) );
		if ( in_array( $booking_for, array( 'child', 'other' ), true ) && '' === $booking_for_name ) {
			return array( 'ok' => false, 'error' => __( 'نام فرد برای این نوع رزرو الزامی است.', 'mr-booking' ) );
		}

		$validated = Slot_Engine::validate_booking( $payload );
		if ( empty( $validated['ok'] ) ) {
			return array( 'ok' => false, 'error' => $validated['error'] ?? __( 'خطا در اعتبارسنجی رزرو.', 'mr-booking' ) );
		}

		$data = $validated['data'];

		$customer_data = array(
			'first_name' => $first,
			'last_name'  => $last,
			'phone'      => $phone,
			'email'      => $email,
			'birth_date' => $payload['birth_date'] ?? '',
		);

		$customer_id = absint( $payload['customer_id'] ?? 0 );
		if ( $customer_id && Customer_Repository::find( $customer_id ) ) {
			Customer_Repository::save( $customer_data, $customer_id );
		} else {
			$customer_id = Customer_Repository::upsert( $customer_data );
		}

		$service_rows = array();
		foreach ( $data['services'] as $svc ) {
			$service_rows[] = array(
				'service_id' => (int) $svc->id,
				'duration'   => (int) $svc->duration,
				'price'      => (float) $svc->price,
			);
		}

		$status = (string) ( $settings['default_status'] ?? 'pending' );
		if ( $is_admin ) {
			$status_raw = sanitize_text_field( (string) ( $payload['status'] ?? 'confirmed' ) );
			$allowed    = array_keys( Helpers::booking_statuses() );
			if ( in_array( $status_raw, $allowed, true ) ) {
				$status = $status_raw;
			} elseif ( 'phone' === $source ) {
				$status = 'confirmed';
			}
		}

		$booking_id = Booking_Repository::create(
			array(
				'customer_id'      => $customer_id,
				'staff_id'         => $data['staff_id'],
				'booking_for'      => $booking_for,
				'booking_for_name' => $booking_for_name,
				'start_datetime'   => $data['start_datetime'],
				'end_datetime'     => $data['end_datetime'],
				'status'           => $status,
				'total_price'      => $data['price'],
				'total_duration'   => $data['duration'],
				'notes'            => sanitize_textarea_field( (string) ( $payload['notes'] ?? '' ) ),
				'source'           => $source,
			),
			$service_rows
		);

		if ( ! $booking_id ) {
			return array( 'ok' => false, 'error' => __( 'خطا در ثبت رزرو.', 'mr-booking' ) );
		}

		/**
		 * Fires after a booking is created.
		 *
		 * @param int   $booking_id Booking ID.
		 * @param array $payload    Original payload.
		 */
		do_action( 'mr_booking_booking_created', $booking_id, $payload );

		$booking = Booking_Repository::find( $booking_id );

		return array(
			'ok'         => true,
			'booking_id' => $booking_id,
			'booking'    => $booking,
		);
	}
}
