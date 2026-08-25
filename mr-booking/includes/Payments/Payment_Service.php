<?php
/**
 * Deposits, payment methods (wallet / ZarinPal) and refunds.
 *
 * Everything here is inert unless the "show_deposit" setting is on, so the
 * booking flow behaves exactly as before when deposits are disabled.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Payments;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Wallet\Wallet_Repository;

defined( 'ABSPATH' ) || exit;

final class Payment_Service {

	public const METHOD_WALLET = 'wallet';
	public const METHOD_ONLINE = 'online';

	public const STATUS_NONE     = 'none';
	public const STATUS_UNPAID   = 'unpaid';
	public const STATUS_PAID     = 'paid';
	public const STATUS_REFUNDED = 'refunded';
	public const STATUS_FAILED   = 'failed';

	public function hooks(): void {
		add_action( 'mr_booking_booking_status_changed', array( self::class, 'maybe_refund' ), 20, 3 );
		add_action( 'mr_booking_booking_deleted', array( self::class, 'refund_on_delete' ), 20, 2 );
	}

	/* ─── Settings ─── */

	public static function deposit_enabled(): bool {
		return ! empty( Settings::get_value( 'show_deposit', 0 ) );
	}

	public static function tip_enabled(): bool {
		return self::deposit_enabled() && ! empty( Settings::get_value( 'enable_tip', 1 ) );
	}

	public static function wallet_enabled(): bool {
		return self::deposit_enabled() && ! empty( Settings::get_value( 'enable_wallet_payment', 1 ) );
	}

	/**
	 * Wallet top-up through the gateway (needs wallet + gateway both on).
	 */
	public static function topup_enabled(): bool {
		return self::wallet_enabled() && self::online_enabled();
	}

	public static function topup_min(): float {
		return max( 1000.0, (float) Settings::get_value( 'wallet_topup_min', 10000 ) );
	}

	/**
	 * Start a wallet top-up for the logged-in customer.
	 *
	 * @return array{ok:bool,error?:string,redirect?:string}
	 */
	public static function start_topup( object $customer, float $amount, string $return_url ): array {
		if ( ! self::topup_enabled() ) {
			return array( 'ok' => false, 'error' => __( 'افزایش موجودی از طریق درگاه غیرفعال است.', 'mr-booking' ) );
		}
		$amount = round( $amount );
		if ( $amount < self::topup_min() ) {
			return array(
				'ok'    => false,
				'error' => sprintf( /* translators: %s: amount */ __( 'حداقل مبلغ افزایش موجودی %s است.', 'mr-booking' ), Helpers::format_price( self::topup_min() ) ),
			);
		}
		if ( $amount > 500000000 ) {
			return array( 'ok' => false, 'error' => __( 'مبلغ واردشده بیش از حد مجاز است.', 'mr-booking' ) );
		}

		$request = self::gateway_request(
			$amount,
			sprintf(
				/* translators: 1: customer name, 2: site name */
				__( 'افزایش موجودی کیف پول %1$s — %2$s', 'mr-booking' ),
				trim( $customer->first_name . ' ' . $customer->last_name ),
				(string) Settings::get_value( 'business_name', get_bloginfo( 'name' ) )
			),
			(string) $customer->phone
		);
		if ( empty( $request['ok'] ) ) {
			return array( 'ok' => false, 'error' => $request['error'] ?? __( 'اتصال به درگاه پرداخت ممکن نشد.', 'mr-booking' ) );
		}

		set_transient(
			'mrb_pay_' . $request['authority'],
			array(
				'type'        => 'topup',
				'customer_id' => (int) $customer->id,
				'amount'      => $amount,
				'return_url'  => $return_url,
			),
			HOUR_IN_SECONDS
		);

		return array( 'ok' => true, 'redirect' => (string) $request['url'] );
	}

	public static function online_enabled(): bool {
		return self::deposit_enabled()
			&& ! empty( Settings::get_value( 'enable_online_payment', 1 ) )
			&& '' !== trim( (string) Settings::get_value( 'zarinpal_merchant_id', '' ) );
	}

	/**
	 * @return array<string, string>
	 */
	public static function status_labels(): array {
		return array(
			self::STATUS_NONE     => __( 'بدون پرداخت', 'mr-booking' ),
			self::STATUS_UNPAID   => __( 'در انتظار پرداخت', 'mr-booking' ),
			self::STATUS_PAID     => __( 'پرداخت‌شده', 'mr-booking' ),
			self::STATUS_REFUNDED => __( 'بازگشت به کیف پول', 'mr-booking' ),
			self::STATUS_FAILED   => __( 'پرداخت ناموفق', 'mr-booking' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function method_labels(): array {
		return array(
			self::METHOD_WALLET => __( 'کیف پول', 'mr-booking' ),
			self::METHOD_ONLINE => __( 'درگاه زرین‌پال', 'mr-booking' ),
		);
	}

	/* ─── Deposit maths ─── */

	/**
	 * Deposit required for a set of services (0 when deposits are disabled).
	 *
	 * @param list<int> $service_ids
	 */
	public static function deposit_for_services( array $service_ids ): float {
		if ( ! self::deposit_enabled() ) {
			return 0.0;
		}
		$sum = 0.0;
		foreach ( Service_Repository::find_many( $service_ids ) as $svc ) {
			$sum += max( 0.0, (float) ( $svc->deposit ?? 0 ) );
		}

		return round( $sum, 2 );
	}

	/**
	 * Sanitise a tip sent by the visitor.
	 */
	public static function sanitize_tip( $raw ): float {
		if ( ! self::tip_enabled() ) {
			return 0.0;
		}

		return min( 100000000.0, Helpers::parse_money( (string) $raw ) );
	}

	/* ─── Booking payment orchestration ─── */

	/**
	 * Called right after a public booking is created when a deposit is due.
	 *
	 * @return array{ok:bool,error?:string,paid?:bool,redirect?:string}
	 */
	public static function start( int $booking_id, string $method, string $return_url ): array {
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return array( 'ok' => false, 'error' => __( 'رزرو یافت نشد.', 'mr-booking' ) );
		}

		$total = round( (float) $booking->deposit_amount + (float) $booking->tip_amount, 2 );
		if ( $total <= 0 ) {
			Booking_Repository::update( $booking_id, array( 'payment_status' => self::STATUS_NONE ) );
			return array( 'ok' => true, 'paid' => true );
		}

		if ( self::METHOD_WALLET === $method ) {
			if ( ! self::wallet_enabled() ) {
				return array( 'ok' => false, 'error' => __( 'پرداخت با کیف پول غیرفعال است.', 'mr-booking' ) );
			}
			$customer = \MRBooking\Auth\Customer_Auth::current_customer();
			if ( ! $customer || (int) $customer->id !== (int) $booking->customer_id ) {
				return array( 'ok' => false, 'error' => __( 'برای پرداخت با کیف پول باید وارد حساب کاربری باشید.', 'mr-booking' ) );
			}
			if ( ! Wallet_Repository::pay( (int) $customer->id, $total, $booking_id ) ) {
				return array( 'ok' => false, 'error' => __( 'موجودی کیف پول کافی نیست.', 'mr-booking' ) );
			}
			Booking_Repository::update(
				$booking_id,
				array(
					'paid_amount'    => $total,
					'payment_status' => self::STATUS_PAID,
					'payment_method' => self::METHOD_WALLET,
					'payment_ref'    => 'wallet',
				)
			);
			do_action( 'mr_booking_deposit_paid', $booking_id, self::METHOD_WALLET, $total );

			return array( 'ok' => true, 'paid' => true );
		}

		if ( self::METHOD_ONLINE === $method ) {
			if ( ! self::online_enabled() ) {
				return array( 'ok' => false, 'error' => __( 'پرداخت آنلاین غیرفعال است.', 'mr-booking' ) );
			}
			$request = self::zarinpal_request( $booking, $total, $return_url );
			if ( empty( $request['ok'] ) ) {
				return array( 'ok' => false, 'error' => $request['error'] ?? __( 'اتصال به درگاه پرداخت ممکن نشد.', 'mr-booking' ) );
			}
			Booking_Repository::update(
				$booking_id,
				array(
					'payment_status' => self::STATUS_UNPAID,
					'payment_method' => self::METHOD_ONLINE,
					'payment_ref'    => (string) $request['authority'],
				)
			);
			set_transient(
				'mrb_pay_' . $request['authority'],
				array(
					'booking_id' => $booking_id,
					'amount'     => $total,
					'return_url' => $return_url,
				),
				HOUR_IN_SECONDS
			);

			return array( 'ok' => true, 'paid' => false, 'redirect' => (string) $request['url'] );
		}

		return array( 'ok' => false, 'error' => __( 'روش پرداخت را انتخاب کنید.', 'mr-booking' ) );
	}

	/* ─── ZarinPal (v4 REST) ─── */

	private static function zarinpal_base(): string {
		return ! empty( Settings::get_value( 'zarinpal_sandbox', 0 ) )
			? 'https://sandbox.zarinpal.com'
			: 'https://payment.zarinpal.com';
	}

	public static function callback_url(): string {
		return rest_url( 'mr-booking/v1/payment/callback' );
	}

	/**
	 * @return array{ok:bool,error?:string,authority?:string,url?:string}
	 */
	private static function zarinpal_request( object $booking, float $amount, string $return_url ): array {
		return self::gateway_request(
			$amount,
			sprintf(
				/* translators: 1: booking code, 2: site name */
				__( 'پیش‌پرداخت رزرو %1$s — %2$s', 'mr-booking' ),
				(string) $booking->booking_code,
				(string) Settings::get_value( 'business_name', get_bloginfo( 'name' ) )
			),
			(string) $booking->phone
		);
	}

	/**
	 * @return array{ok:bool,error?:string,authority?:string,url?:string}
	 */
	private static function gateway_request( float $amount, string $description, string $mobile ): array {
		$merchant = trim( (string) Settings::get_value( 'zarinpal_merchant_id', '' ) );
		$body     = array(
			'merchant_id'  => $merchant,
			'amount'       => (int) round( $amount ),
			'currency'     => 'IRT',
			'callback_url' => self::callback_url(),
			'description'  => $description,
			'metadata'     => array( 'mobile' => $mobile ),
		);

		$response = wp_remote_post(
			self::zarinpal_base() . '/pg/v4/payment/request.json',
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false, 'error' => $response->get_error_message() );
		}

		$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$data = is_array( $json ) ? ( $json['data'] ?? array() ) : array();

		if ( ! empty( $data['authority'] ) && (int) ( $data['code'] ?? 0 ) === 100 ) {
			return array(
				'ok'        => true,
				'authority' => (string) $data['authority'],
				'url'       => self::zarinpal_base() . '/pg/StartPay/' . rawurlencode( (string) $data['authority'] ),
			);
		}

		$errors  = is_array( $json ) ? ( $json['errors'] ?? array() ) : array();
		$message = is_array( $errors ) && ! empty( $errors['message'] ) ? (string) $errors['message'] : '';

		return array(
			'ok'    => false,
			'error' => $message
				? sprintf( /* translators: %s: gateway message */ __( 'درگاه پرداخت: %s', 'mr-booking' ), $message )
				: __( 'درگاه پرداخت پاسخ معتبری نداد.', 'mr-booking' ),
		);
	}

	/**
	 * Handle the gateway redirect back. Returns the URL to send the visitor to.
	 */
	public static function handle_callback( string $authority, string $status ): string {
		$authority = preg_replace( '/[^A-Za-z0-9]/', '', $authority ) ?? '';
		$context   = $authority ? get_transient( 'mrb_pay_' . $authority ) : false;
		$fallback  = home_url( '/' );

		if ( is_array( $context ) && 'topup' === ( $context['type'] ?? '' ) ) {
			return self::finish_topup( $authority, $context, $status );
		}

		if ( ! is_array( $context ) || empty( $context['booking_id'] ) ) {
			return add_query_arg( 'mrb_payment', 'invalid', $fallback );
		}

		$booking_id = (int) $context['booking_id'];
		$return_url = (string) ( $context['return_url'] ?: $fallback );
		$booking    = Booking_Repository::find( $booking_id );

		if ( ! $booking || (string) $booking->payment_ref !== $authority ) {
			return add_query_arg( 'mrb_payment', 'invalid', $return_url );
		}

		// Already settled (double callback).
		if ( self::STATUS_PAID === (string) $booking->payment_status ) {
			return add_query_arg( array( 'mrb_payment' => 'success', 'mrb_code' => $booking->booking_code ), $return_url );
		}

		if ( 'OK' !== strtoupper( $status ) ) {
			self::fail_booking( $booking_id );
			return add_query_arg( 'mrb_payment', 'cancelled', $return_url );
		}

		$verify = self::zarinpal_verify( (float) $context['amount'], $authority );
		if ( empty( $verify['ok'] ) ) {
			self::fail_booking( $booking_id );
			return add_query_arg( 'mrb_payment', 'failed', $return_url );
		}

		Booking_Repository::update(
			$booking_id,
			array(
				'paid_amount'    => (float) $context['amount'],
				'payment_status' => self::STATUS_PAID,
				'payment_ref'    => (string) $verify['ref_id'],
			)
		);
		delete_transient( 'mrb_pay_' . $authority );
		do_action( 'mr_booking_deposit_paid', $booking_id, self::METHOD_ONLINE, (float) $context['amount'] );

		return add_query_arg( array( 'mrb_payment' => 'success', 'mrb_code' => $booking->booking_code ), $return_url );
	}

	/**
	 * Verify a top-up and credit the wallet (idempotent via the transient).
	 *
	 * @param array<string, mixed> $context
	 */
	private static function finish_topup( string $authority, array $context, string $status ): string {
		$return_url = (string) ( $context['return_url'] ?: home_url( '/' ) );

		if ( 'OK' !== strtoupper( $status ) ) {
			delete_transient( 'mrb_pay_' . $authority );
			return add_query_arg( 'mrb_wallet', 'cancelled', $return_url );
		}

		$verify = self::zarinpal_verify( (float) $context['amount'], $authority );
		if ( empty( $verify['ok'] ) ) {
			delete_transient( 'mrb_pay_' . $authority );
			return add_query_arg( 'mrb_wallet', 'failed', $return_url );
		}

		// Consume first so a double callback cannot credit twice.
		delete_transient( 'mrb_pay_' . $authority );
		Wallet_Repository::add(
			(int) $context['customer_id'],
			(float) $context['amount'],
			Wallet_Repository::TYPE_TOPUP,
			sprintf( /* translators: %s: gateway reference */ __( 'افزایش موجودی آنلاین — پیگیری %s', 'mr-booking' ), (string) $verify['ref_id'] )
		);

		return add_query_arg( array( 'mrb_wallet' => 'success' ), $return_url );
	}

	/**
	 * @return array{ok:bool,ref_id?:string}
	 */
	private static function zarinpal_verify( float $amount, string $authority ): array {
		$response = wp_remote_post(
			self::zarinpal_base() . '/pg/v4/payment/verify.json',
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json', 'Accept' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'merchant_id' => trim( (string) Settings::get_value( 'zarinpal_merchant_id', '' ) ),
						'amount'      => (int) round( $amount ),
						'currency'    => 'IRT',
						'authority'   => $authority,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false );
		}

		$json = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$data = is_array( $json ) ? ( $json['data'] ?? array() ) : array();
		$code = (int) ( $data['code'] ?? 0 );

		if ( in_array( $code, array( 100, 101 ), true ) ) {
			return array( 'ok' => true, 'ref_id' => (string) ( $data['ref_id'] ?? $authority ) );
		}

		return array( 'ok' => false );
	}

	/**
	 * Unpaid online booking that came back failed/cancelled: free the slot.
	 */
	private static function fail_booking( int $booking_id ): void {
		// Direct update (not update_status) so no "cancelled" notification goes to the customer
		// for a booking that never completed; the slot is released because status is cancelled.
		Booking_Repository::update(
			$booking_id,
			array(
				'payment_status' => self::STATUS_FAILED,
				'status'         => 'cancelled',
			)
		);
	}

	/* ─── Refunds ─── */

	/**
	 * Paid deposit + tip goes back to the customer's wallet when a booking is
	 * cancelled or rejected. The cancellation policy itself is enforced before
	 * the status changes (customer panel) or by the admin's decision.
	 */
	public static function maybe_refund( int $booking_id, string $status, string $old_status = '' ): void {
		if ( ! in_array( $status, array( 'cancelled', 'rejected' ), true ) ) {
			return;
		}
		self::refund_booking( $booking_id, $status );
	}

	public static function refund_on_delete( int $booking_id, $booking ): void {
		if ( is_object( $booking ) ) {
			self::refund_booking( 0, 'deleted', $booking );
		}
	}

	private static function refund_booking( int $booking_id, string $reason, ?object $booking = null ): void {
		$booking = $booking ?: Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return;
		}
		$id   = (int) $booking->id;
		$paid = (float) ( $booking->paid_amount ?? 0 );
		if ( $paid <= 0 || self::STATUS_PAID !== (string) ( $booking->payment_status ?? '' ) || Wallet_Repository::has_refund( $id ) ) {
			return;
		}

		/**
		 * Filter whether a cancelled/rejected booking's deposit is refunded to the wallet.
		 *
		 * @param bool   $refund
		 * @param object $booking
		 * @param string $reason  cancelled|rejected|deleted
		 */
		if ( ! apply_filters( 'mr_booking_refund_deposit', true, $booking, $reason ) ) {
			return;
		}

		$row = Wallet_Repository::add(
			(int) $booking->customer_id,
			$paid,
			Wallet_Repository::TYPE_REFUND,
			sprintf( /* translators: %s: booking code */ __( 'بازگشت پیش‌پرداخت نوبت %s', 'mr-booking' ), (string) $booking->booking_code ),
			$id
		);

		if ( $row && 'deleted' !== $reason ) {
			Booking_Repository::update( $id, array( 'payment_status' => self::STATUS_REFUNDED ) );
		}
	}

	/* ─── Presentation helpers ─── */

	/**
	 * Payment summary for admin views.
	 *
	 * @return array<string, string>
	 */
	public static function admin_summary( object $booking ): array {
		$status = (string) ( $booking->payment_status ?? self::STATUS_NONE );

		return array(
			'deposit'      => Helpers::format_price( (float) ( $booking->deposit_amount ?? 0 ) ),
			'tip'          => Helpers::format_price( (float) ( $booking->tip_amount ?? 0 ) ),
			'paid'         => Helpers::format_price( (float) ( $booking->paid_amount ?? 0 ) ),
			'status'       => $status,
			'status_label' => self::status_labels()[ $status ] ?? $status,
			'method'       => self::method_labels()[ (string) ( $booking->payment_method ?? '' ) ] ?? '',
			'ref'          => (string) ( $booking->payment_ref ?? '' ),
		);
	}
}
