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

		if ( ! $is_admin && \MRBooking\Auth\Customer_Auth::is_enabled() ) {
			$account = \MRBooking\Auth\Customer_Auth::current_customer();
			if ( \MRBooking\Auth\Customer_Auth::is_required() && ! $account ) {
				return array( 'ok' => false, 'error' => __( 'برای ثبت رزرو ابتدا وارد حساب کاربری شوید.', 'mr-booking' ), 'code' => 'login_required' );
			}
			if ( $account ) {
				// Verified identity wins over anything posted from the browser.
				$payload['phone']       = (string) $account->phone;
				$payload['customer_id'] = (int) $account->id;
				if ( '' === trim( (string) ( $payload['birth_date'] ?? '' ) ) ) {
					$payload['birth_date'] = (string) ( $account->birth_date ?? '' );
				}
			}
		}

		$customer = self::validate_customer( $payload, $is_admin );
		if ( empty( $customer['ok'] ) ) {
			return $customer;
		}

		$booking_for = sanitize_text_field( (string) ( $payload['booking_for'] ?? 'myself' ) );
		if ( ! in_array( $booking_for, array( 'myself', 'child', 'other' ), true ) ) {
			$booking_for = 'myself';
		}

		$booking_for_name = sanitize_text_field( (string) ( $payload['booking_for_name'] ?? '' ) );
		if ( in_array( $booking_for, array( 'child', 'other' ), true ) && '' === $booking_for_name ) {
			return array( 'ok' => false, 'error' => __( 'نام فرد برای این نوع رزرو الزامی است.', 'mr-booking' ) );
		}

		$terms_accepted = ! empty( $payload['terms_accepted'] );
		if ( ! $is_admin && ! empty( $settings['require_terms'] ) && ! $terms_accepted ) {
			return array( 'ok' => false, 'error' => __( 'برای ثبت رزرو باید قوانین و شرایط را بپذیرید.', 'mr-booking' ) );
		}

		$validated = Slot_Engine::validate_booking( $payload );
		if ( empty( $validated['ok'] ) ) {
			return array( 'ok' => false, 'error' => $validated['error'] ?? __( 'خطا در اعتبارسنجی رزرو.', 'mr-booking' ) );
		}

		$data        = $validated['data'];
		$customer_id = self::persist_customer( $customer['data'], absint( $payload['customer_id'] ?? 0 ) );

		// Deposit is computed server-side from the services; the visitor only chooses a tip.
		$deposit = $is_admin ? 0.0 : \MRBooking\Payments\Payment_Service::deposit_for_services( $data['service_ids'] );
		$tip     = $is_admin || $deposit <= 0 ? 0.0 : \MRBooking\Payments\Payment_Service::sanitize_tip( $payload['tip_amount'] ?? 0 );

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
				'deposit_amount'   => $deposit,
				'tip_amount'       => $tip,
				'payment_status'   => $deposit > 0 ? \MRBooking\Payments\Payment_Service::STATUS_UNPAID : \MRBooking\Payments\Payment_Service::STATUS_NONE,
				'terms_accepted'   => $terms_accepted,
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

	/**
	 * Create a walk-in booking (admin only).
	 *
	 * Walk-ins are stamped with the current time, never validated against
	 * availability, and never block online slots (see Booking_Repository::overlapping()).
	 * Prices may be overridden per service via `service_prices` (service_id => amount).
	 *
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool,error?:string,booking_id?:int,booking?:object}
	 */
	public static function create_walkin( array $payload ): array {
		$settings = Settings::get();

		$customer = self::validate_customer( $payload, true );
		if ( empty( $customer['ok'] ) ) {
			return $customer;
		}

		$service_ids = array_values( array_filter( array_map( 'absint', (array) ( $payload['service_ids'] ?? array() ) ) ) );
		if ( empty( $service_ids ) ) {
			return array( 'ok' => false, 'error' => __( 'لطفاً حداقل یک خدمت انتخاب کنید.', 'mr-booking' ) );
		}

		$services = \MRBooking\Services\Service_Repository::find_many( $service_ids );
		if ( empty( $services ) ) {
			return array( 'ok' => false, 'error' => __( 'خدمت انتخاب‌شده معتبر نیست.', 'mr-booking' ) );
		}

		$overrides = array();
		foreach ( (array) ( $payload['service_prices'] ?? array() ) as $sid => $amount ) {
			$sid = absint( $sid );
			if ( $sid > 0 && is_numeric( $amount ) ) {
				$overrides[ $sid ] = max( 0.0, (float) $amount );
			}
		}

		$service_rows = array();
		$duration     = 0;
		$price        = 0.0;
		foreach ( $services as $svc ) {
			$sid       = (int) $svc->id;
			$line      = array_key_exists( $sid, $overrides )
				? $overrides[ $sid ]
				: ( \MRBooking\Services\Service_Repository::has_price( $svc ) ? (float) $svc->price : 0.0 );
			$duration += (int) $svc->duration;
			$price    += $line;

			$service_rows[] = array(
				'service_id' => $sid,
				'duration'   => (int) $svc->duration,
				'price'      => $line,
			);
		}

		$status  = sanitize_text_field( (string) ( $payload['status'] ?? 'completed' ) );
		$allowed = array_keys( Helpers::booking_statuses() );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'completed';
		}

		$staff_id = absint( $payload['staff_id'] ?? 0 ) ?: null;
		if ( $staff_id && ! \MRBooking\Staff\Staff_Repository::find( $staff_id ) ) {
			$staff_id = null;
		}

		$now   = new \DateTimeImmutable( 'now', wp_timezone() );
		$start = $now->format( 'Y-m-d H:i:00' );
		$end   = $now->modify( '+' . max( 1, $duration ) . ' minutes' )->format( 'Y-m-d H:i:00' );

		$customer_id = self::persist_customer( $customer['data'], absint( $payload['customer_id'] ?? 0 ) );

		$booking_id = Booking_Repository::create(
			array(
				'customer_id'      => $customer_id,
				'staff_id'         => $staff_id,
				'booking_for'      => 'myself',
				'booking_for_name' => '',
				'start_datetime'   => $start,
				'end_datetime'     => $end,
				'status'           => $status,
				'total_price'      => $price,
				'total_duration'   => $duration,
				'notes'            => sanitize_textarea_field( (string) ( $payload['notes'] ?? '' ) ),
				'source'           => Booking_Repository::SOURCE_WALKIN,
			),
			$service_rows
		);

		if ( ! $booking_id ) {
			return array( 'ok' => false, 'error' => __( 'خطا در ثبت رزرو.', 'mr-booking' ) );
		}

		/** This action is documented in includes/Bookings/Booking_Service.php */
		do_action( 'mr_booking_booking_created', $booking_id, $payload );

		return array(
			'ok'         => true,
			'booking_id' => $booking_id,
			'booking'    => Booking_Repository::find( $booking_id ),
		);
	}

	/**
	 * Shared customer validation for online, phone and walk-in bookings.
	 *
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool,error?:string,data?:array<string,mixed>}
	 */
	private static function validate_customer( array $payload, bool $is_admin ): array {
		$settings = Settings::get();

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

		return array(
			'ok'   => true,
			'data' => array(
				'first_name' => $first,
				'last_name'  => $last,
				'phone'      => $phone,
				'email'      => $email,
				'birth_date' => $payload['birth_date'] ?? '',
			),
		);
	}

	/**
	 * Update the chosen customer or upsert by phone.
	 *
	 * @param array<string, mixed> $customer_data
	 */
	private static function persist_customer( array $customer_data, int $customer_id ): int {
		if ( $customer_id && Customer_Repository::find( $customer_id ) ) {
			Customer_Repository::save( $customer_data, $customer_id );
			return $customer_id;
		}

		return Customer_Repository::upsert( $customer_data );
	}
}
