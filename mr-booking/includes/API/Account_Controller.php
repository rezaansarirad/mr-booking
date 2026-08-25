<?php
/**
 * Customer account REST endpoints.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\API;

use MRBooking\Auth\Customer_Auth;
use MRBooking\Auth\OTP_Service;
use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Settings\Settings;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class Account_Controller {

	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		$ns = Rest_Controller::NAMESPACE;

		register_rest_route( $ns, '/auth/request-otp', array( 'methods' => 'POST', 'callback' => array( $this, 'request_otp' ), 'permission_callback' => array( $this, 'login_enabled' ) ) );
		register_rest_route( $ns, '/auth/verify-otp', array( 'methods' => 'POST', 'callback' => array( $this, 'verify_otp' ), 'permission_callback' => array( $this, 'login_enabled' ) ) );
		register_rest_route( $ns, '/auth/complete-profile', array( 'methods' => 'POST', 'callback' => array( $this, 'complete_profile' ), 'permission_callback' => array( $this, 'login_enabled' ) ) );
		register_rest_route( $ns, '/auth/logout', array( 'methods' => 'POST', 'callback' => array( $this, 'logout' ), 'permission_callback' => 'is_user_logged_in' ) );

		register_rest_route( $ns, '/me', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'me' ), 'permission_callback' => array( $this, 'customer_permission' ) ),
			array( 'methods' => 'POST', 'callback' => array( $this, 'update_me' ), 'permission_callback' => array( $this, 'customer_permission' ) ),
		) );
		register_rest_route( $ns, '/me/bookings', array( 'methods' => 'GET', 'callback' => array( $this, 'my_bookings' ), 'permission_callback' => array( $this, 'customer_permission' ) ) );
		register_rest_route( $ns, '/me/wallet', array( 'methods' => 'GET', 'callback' => array( $this, 'my_wallet' ), 'permission_callback' => array( $this, 'customer_permission' ) ) );
		register_rest_route( $ns, '/me/wallet/topup', array( 'methods' => 'POST', 'callback' => array( $this, 'wallet_topup' ), 'permission_callback' => array( $this, 'customer_permission' ) ) );
		register_rest_route( $ns, '/me/bookings/(?P<id>\d+)/cancel', array( 'methods' => 'POST', 'callback' => array( $this, 'cancel_booking' ), 'permission_callback' => array( $this, 'customer_permission' ) ) );
	}

	public function login_enabled(): bool {
		return Customer_Auth::is_enabled();
	}

	public function customer_permission(): bool {
		return Customer_Auth::is_enabled() && is_user_logged_in() && null !== Customer_Auth::current_customer();
	}

	/**
	 * @param array<string, mixed> $data
	 */
	private function respond( array $data, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( $data, $status );
	}

	private function error( string $message, int $status = 400, array $extra = array() ): WP_REST_Response {
		return $this->respond( array_merge( array( 'ok' => false, 'error' => $message ), $extra ), $status );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function body( WP_REST_Request $request ): array {
		$payload = $request->get_json_params();

		return is_array( $payload ) ? $payload : (array) $request->get_params();
	}

	/* ─── OTP ─── */

	public function request_otp( WP_REST_Request $request ): WP_REST_Response {
		$b      = $this->body( $request );
		$result = OTP_Service::request( (string) ( $b['phone'] ?? '' ) );

		if ( empty( $result['ok'] ) ) {
			$status = ! empty( $result['retry_after'] ) ? 429 : 400;
			return $this->error( (string) $result['error'], $status, array_intersect_key( $result, array( 'retry_after' => 1 ) ) );
		}

		return $this->respond( $result );
	}

	public function verify_otp( WP_REST_Request $request ): WP_REST_Response {
		$b      = $this->body( $request );
		$phone  = Helpers::sanitize_mobile( (string) ( $b['phone'] ?? '' ) );
		$result = OTP_Service::verify( $phone, (string) ( $b['code'] ?? '' ) );

		if ( empty( $result['ok'] ) ) {
			return $this->error( (string) $result['error'] );
		}

		$customer = Customer_Repository::find_by_phone( $phone );
		if ( ! $customer || '' === trim( (string) $customer->first_name ) || '' === trim( (string) $customer->last_name ) ) {
			// New (or incomplete) profile — collect the name before creating the session.
			return $this->respond(
				array(
					'ok'            => true,
					'needs_profile' => true,
					'token'         => $result['token'],
					'customer'      => $customer ? Customer_Auth::public_customer( $customer ) : null,
				)
			);
		}

		OTP_Service::check_token( $phone, (string) $result['token'], true );

		return $this->finish_login( $customer );
	}

	public function complete_profile( WP_REST_Request $request ): WP_REST_Response {
		$b     = $this->body( $request );
		$phone = Helpers::sanitize_mobile( (string) ( $b['phone'] ?? '' ) );
		$token = (string) ( $b['token'] ?? '' );

		if ( ! OTP_Service::check_token( $phone, $token ) ) {
			return $this->error( __( 'اعتبار تأیید شماره تمام شده است. دوباره کد بگیرید.', 'mr-booking' ), 403 );
		}

		$first = sanitize_text_field( (string) ( $b['first_name'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $b['last_name'] ?? '' ) );
		if ( mb_strlen( $first ) < 2 || mb_strlen( $last ) < 2 ) {
			return $this->error( __( 'نام و نام خانوادگی باید حداقل ۲ حرف باشد.', 'mr-booking' ) );
		}

		$email = sanitize_email( (string) ( $b['email'] ?? '' ) );
		if ( $email && ! is_email( $email ) ) {
			return $this->error( __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ) );
		}

		$existing = Customer_Repository::find_by_phone( $phone );
		$id       = Customer_Repository::save(
			array(
				'first_name' => $first,
				'last_name'  => $last,
				'phone'      => $phone,
				'email'      => $email ?: (string) ( $existing->email ?? '' ),
				'birth_date' => (string) ( $b['birth_date'] ?? ( $existing->birth_date ?? '' ) ),
				'notes'      => (string) ( $existing->notes ?? '' ),
			),
			$existing ? (int) $existing->id : 0
		);

		$customer = Customer_Repository::find( $id );
		if ( ! $customer ) {
			return $this->error( __( 'ثبت پروفایل ناموفق بود.', 'mr-booking' ), 500 );
		}

		OTP_Service::check_token( $phone, $token, true );

		return $this->finish_login( $customer );
	}

	private function finish_login( object $customer ): WP_REST_Response {
		$user_id = Customer_Auth::ensure_user( $customer );
		if ( ! $user_id ) {
			return $this->error( __( 'ایجاد حساب کاربری ناموفق بود. لطفاً با ما تماس بگیرید.', 'mr-booking' ), 500 );
		}

		Customer_Auth::login( $user_id );

		return $this->respond(
			array(
				'ok'        => true,
				'logged_in' => true,
				'nonce'     => Customer_Auth::rest_nonce(),
				'customer'  => Customer_Auth::public_customer( Customer_Repository::find( (int) $customer->id ) ),
			)
		);
	}

	public function logout(): WP_REST_Response {
		Customer_Auth::logout();

		return $this->respond( array( 'ok' => true, 'nonce' => Customer_Auth::rest_nonce() ) );
	}

	/* ─── Account ─── */

	public function me(): WP_REST_Response {
		return $this->respond(
			array(
				'ok'       => true,
				'customer' => Customer_Auth::public_customer( Customer_Auth::current_customer() ),
			)
		);
	}

	public function update_me( WP_REST_Request $request ): WP_REST_Response {
		$customer = Customer_Auth::current_customer();
		$b        = $this->body( $request );

		$first = sanitize_text_field( (string) ( $b['first_name'] ?? '' ) );
		$last  = sanitize_text_field( (string) ( $b['last_name'] ?? '' ) );
		if ( mb_strlen( $first ) < 2 || mb_strlen( $last ) < 2 ) {
			return $this->error( __( 'نام و نام خانوادگی باید حداقل ۲ حرف باشد.', 'mr-booking' ) );
		}

		$email = sanitize_email( (string) ( $b['email'] ?? '' ) );
		if ( $email && ! is_email( $email ) ) {
			return $this->error( __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ) );
		}

		// Phone is verified identity: deliberately not accepted from the request.
		Customer_Repository::save(
			array(
				'first_name' => $first,
				'last_name'  => $last,
				'phone'      => (string) $customer->phone,
				'email'      => $email,
				'birth_date' => (string) ( $b['birth_date'] ?? '' ),
				'notes'      => (string) ( $customer->notes ?? '' ),
				'user_id'    => (int) $customer->user_id,
			),
			(int) $customer->id
		);

		$user_id = (int) $customer->user_id;
		if ( $user_id ) {
			wp_update_user(
				array(
					'ID'           => $user_id,
					'first_name'   => $first,
					'last_name'    => $last,
					'display_name' => trim( $first . ' ' . $last ),
				)
			);
		}

		Customer_Auth::reset_current();

		return $this->respond(
			array(
				'ok'       => true,
				'message'  => __( 'پروفایل ذخیره شد.', 'mr-booking' ),
				'customer' => Customer_Auth::public_customer( Customer_Auth::current_customer() ),
			)
		);
	}

	public function my_wallet(): WP_REST_Response {
		$customer = Customer_Auth::current_customer();
		$rows     = \MRBooking\Wallet\Wallet_Repository::transactions( (int) $customer->id, 100 );
		$balance  = \MRBooking\Wallet\Wallet_Repository::balance( (int) $customer->id );

		return new WP_REST_Response(
			array(
				'balance'       => $balance,
				'balance_label' => $balance > 0 ? Helpers::format_price( $balance ) : Helpers::to_persian_digits( '0' ) . ' ' . __( 'تومان', 'mr-booking' ),
				'wallet_enabled'=> \MRBooking\Payments\Payment_Service::wallet_enabled() ? 1 : 0,
				'topup_enabled' => \MRBooking\Payments\Payment_Service::topup_enabled() ? 1 : 0,
				'topup_min'     => \MRBooking\Payments\Payment_Service::topup_min(),
				'transactions'  => array_map( array( \MRBooking\Wallet\Wallet_Repository::class, 'format_public' ), $rows ),
			),
			200
		);
	}

	public function wallet_topup( WP_REST_Request $request ): WP_REST_Response {
		$customer   = Customer_Auth::current_customer();
		$body       = $request->get_json_params();
		$body       = is_array( $body ) ? $body : $request->get_params();
		$amount     = Helpers::parse_money( (string) ( $body['amount'] ?? '' ) );
		$return_url = esc_url_raw( (string) ( $body['return_url'] ?? '' ) );
		if ( ! $return_url || 0 !== strpos( $return_url, home_url() ) ) {
			$return_url = Customer_Auth::account_url() ?: home_url( '/' );
		}

		$result = \MRBooking\Payments\Payment_Service::start_topup( $customer, $amount, $return_url );
		if ( empty( $result['ok'] ) ) {
			return new WP_REST_Response( array( 'ok' => false, 'error' => $result['error'] ?? '' ), 400 );
		}

		return new WP_REST_Response( array( 'ok' => true, 'redirect' => $result['redirect'] ), 200 );
	}

	public function my_bookings(): WP_REST_Response {
		$customer = Customer_Auth::current_customer();
		$rows     = Booking_Repository::query(
			array(
				'customer_id' => (int) $customer->id,
				'limit'       => 100,
				'orderby'     => 'start_datetime',
				'order'       => 'DESC',
			)
		);

		$statuses    = Helpers::booking_statuses();
		$show_prices = ! empty( Settings::get_value( 'show_prices' ) );
		$sources     = Booking_Repository::source_labels();
		$upcoming    = array();
		$past        = array();

		foreach ( $rows as $b ) {
			$policy   = Customer_Auth::can_cancel( $b );
			$date_ymd = substr( (string) $b->start_datetime, 0, 10 );
			$item     = array(
				'id'            => (int) $b->id,
				'code'          => (string) $b->booking_code,
				'status'        => (string) $b->status,
				'status_label'  => (string) ( $statuses[ $b->status ] ?? $b->status ),
				'services'      => (string) ( $b->service_names ?? '' ),
				'staff'         => (string) ( $b->staff_name ?? '' ),
				'source'        => (string) $b->source,
				'source_label'  => (string) ( $sources[ $b->source ] ?? $b->source ),
				'date_label'    => Helpers::format_customer_date( $date_ymd ),
				'time_label'    => Helpers::format_customer_time( (string) $b->start_datetime ),
				'end_label'     => Helpers::format_customer_time( (string) $b->end_datetime ),
				'duration'      => (int) $b->total_duration,
				'is_upcoming'   => Booking_Repository::is_upcoming( $b ),
				'can_cancel'    => $policy['ok'],
				'payment_status'=> (string) ( $b->payment_status ?? 'none' ),
				'payment_label' => \MRBooking\Payments\Payment_Service::status_labels()[ (string) ( $b->payment_status ?? 'none' ) ] ?? '',
				'paid_label'    => (float) ( $b->paid_amount ?? 0 ) > 0 ? Helpers::format_price( (float) $b->paid_amount ) : '',
				'cancel_reason' => $policy['reason'],
				'minutes_left'  => $policy['minutes_left'],
			);
			if ( $show_prices && (float) $b->total_price > 0 ) {
				$item['price_label'] = Helpers::format_price( (float) $b->total_price );
			}

			if ( $item['is_upcoming'] && ! in_array( $item['status'], array( 'cancelled', 'rejected' ), true ) ) {
				$upcoming[] = $item;
			} else {
				$past[] = $item;
			}
		}

		// Upcoming: soonest first.
		$upcoming = array_reverse( $upcoming );

		return $this->respond(
			array(
				'ok'                 => true,
				'upcoming'           => $upcoming,
				'past'               => $past,
				'cancel_min_minutes' => Customer_Auth::cancel_min_minutes(),
			)
		);
	}

	public function cancel_booking( WP_REST_Request $request ): WP_REST_Response {
		$result = Customer_Auth::cancel_own_booking( absint( $request->get_param( 'id' ) ) );
		if ( empty( $result['ok'] ) ) {
			return $this->error( (string) $result['error'] );
		}

		return $this->respond( array( 'ok' => true, 'message' => __( 'نوبت لغو شد.', 'mr-booking' ) ) );
	}
}
