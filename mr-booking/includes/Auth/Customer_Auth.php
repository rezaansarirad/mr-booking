<?php
/**
 * Customer accounts (OTP login) — identity, sessions and access policy.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Auth;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Roles\Capabilities;
use MRBooking\Roles\Roles;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Customer_Auth {

	public const MODE_OFF      = 'off';
	public const MODE_OPTIONAL = 'optional';
	public const MODE_REQUIRED = 'required';

	private static ?object $current = null;
	private static bool $resolved   = false;

	public function hooks(): void {
		add_action( 'admin_init', array( $this, 'block_admin_for_customers' ) );
		add_filter( 'show_admin_bar', array( $this, 'hide_admin_bar' ), 20 );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
	}

	/**
	 * @return array<string, string>
	 */
	public static function modes(): array {
		return array(
			self::MODE_OFF      => __( 'غیرفعال', 'mr-booking' ),
			self::MODE_OPTIONAL => __( 'اختیاری', 'mr-booking' ),
			self::MODE_REQUIRED => __( 'الزامی', 'mr-booking' ),
		);
	}

	/**
	 * @return 'off'|'optional'|'required'
	 */
	public static function login_mode(): string {
		$mode = (string) Settings::get_value( 'customer_login_mode', self::MODE_OFF );

		return isset( self::modes()[ $mode ] ) ? $mode : self::MODE_OFF;
	}

	public static function is_enabled(): bool {
		return self::MODE_OFF !== self::login_mode();
	}

	public static function is_required(): bool {
		return self::MODE_REQUIRED === self::login_mode();
	}

	/**
	 * Customer profile linked to the current WordPress user, if any.
	 */
	public static function current_customer(): ?object {
		if ( self::$resolved ) {
			return self::$current;
		}
		self::$resolved = true;

		if ( ! is_user_logged_in() ) {
			return null;
		}

		self::$current = Customer_Repository::find_by_user_id( get_current_user_id() );

		return self::$current;
	}

	public static function reset_current(): void {
		self::$resolved = false;
		self::$current  = null;
	}

	/**
	 * Public shape of a customer for the frontend.
	 *
	 * @return array<string, mixed>
	 */
	public static function public_customer( ?object $c ): ?array {
		if ( ! $c ) {
			return null;
		}

		return array(
			'id'         => (int) $c->id,
			'first_name' => (string) $c->first_name,
			'last_name'  => (string) $c->last_name,
			'phone'      => (string) $c->phone,
			'email'      => (string) ( $c->email ?? '' ),
			'birth_date' => (string) ( $c->birth_date ?? '' ),
		);
	}

	/**
	 * URL of the page holding [mr_booking_account] (empty when not configured).
	 */
	public static function account_url(): string {
		$page_id = absint( Settings::get_value( 'account_page_id', 0 ) );
		if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
			return (string) get_permalink( $page_id );
		}

		/**
		 * Filter the customer account page URL when no page is configured.
		 *
		 * @param string $url
		 */
		return (string) apply_filters( 'mr_booking_account_url', '' );
	}

	/**
	 * Whether a WordPress user is a front-end customer only (no plugin/admin powers).
	 */
	public static function is_customer_user( ?\WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		if ( ! in_array( Roles::CUSTOMER, (array) $user->roles, true ) ) {
			return false;
		}

		return ! user_can( $user, 'edit_posts' )
			&& ! user_can( $user, 'manage_options' )
			&& ! Capabilities::user_has_any_booking_access( $user );
	}

	/**
	 * Make sure the customer has a WordPress user, create/link if needed.
	 *
	 * @return int User ID (0 on failure).
	 */
	public static function ensure_user( object $customer ): int {
		$existing_id = (int) ( $customer->user_id ?? 0 );
		if ( $existing_id && get_user_by( 'id', $existing_id ) ) {
			return $existing_id;
		}

		$phone = (string) $customer->phone;
		$user  = get_user_by( 'login', $phone );

		// Never hijack a staff/admin account that happens to use a phone-shaped username.
		if ( $user && ! self::is_customer_user( $user ) && ! self::is_plain_subscriber( $user ) ) {
			$user = null;
		}

		if ( ! $user ) {
			$login = username_exists( $phone ) ? 'c' . $phone : $phone;
			$email = sanitize_email( (string) ( $customer->email ?? '' ) );
			if ( ! $email || ! is_email( $email ) || email_exists( $email ) ) {
				$email = '';
			}

			$user_id = wp_insert_user(
				array(
					'user_login'   => $login,
					'user_pass'    => wp_generate_password( 32, true, true ),
					'user_email'   => $email,
					'first_name'   => (string) $customer->first_name,
					'last_name'    => (string) $customer->last_name,
					'display_name' => trim( $customer->first_name . ' ' . $customer->last_name ),
					'role'         => Roles::CUSTOMER,
				)
			);

			if ( is_wp_error( $user_id ) ) {
				return 0;
			}
			$user_id = (int) $user_id;
		} else {
			$user_id = (int) $user->ID;
			$user->set_role( Roles::CUSTOMER );
		}

		Customer_Repository::link_user( (int) $customer->id, $user_id );

		return $user_id;
	}

	private static function is_plain_subscriber( \WP_User $user ): bool {
		return array( 'subscriber' ) === array_values( (array) $user->roles ) && ! user_can( $user, 'edit_posts' );
	}

	/**
	 * Start a session for the customer (remember me).
	 */
	public static function login( int $user_id ): void {
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );

		// Cookies are only sent as headers; mirror the logged-in cookie into $_COOKIE so
		// wp_create_nonce() (which reads the session token from it) works in this request.
		$mirror = static function ( string $cookie ): void {
			$_COOKIE[ LOGGED_IN_COOKIE ] = $cookie;
		};
		add_action( 'set_logged_in_cookie', $mirror, 10, 1 );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		remove_action( 'set_logged_in_cookie', $mirror, 10 );

		self::reset_current();

		$user = get_user_by( 'id', $user_id );
		if ( $user ) {
			/** This action is documented in wp-includes/user.php */
			do_action( 'wp_login', $user->user_login, $user );
		}
	}

	public static function logout(): void {
		wp_logout();
		unset( $_COOKIE[ LOGGED_IN_COOKIE ], $_COOKIE[ AUTH_COOKIE ], $_COOKIE[ SECURE_AUTH_COOKIE ] );
		self::reset_current();
	}

	/**
	 * Fresh REST nonce for the (possibly just-changed) current user.
	 */
	public static function rest_nonce(): string {
		return wp_create_nonce( 'wp_rest' );
	}

	/* ─── Cancellation policy ─── */

	public static function cancel_min_minutes(): int {
		return max( 0, (int) Settings::get_value( 'customer_cancel_min_minutes', 30 ) );
	}

	/**
	 * Whether the customer may cancel this booking right now.
	 *
	 * @return array{ok:bool,reason:string,minutes_left:int}
	 */
	public static function can_cancel( object $booking ): array {
		$minutes_left = self::minutes_until( (string) $booking->start_datetime );

		if ( empty( Settings::get_value( 'allow_cancellation', 1 ) ) ) {
			return array( 'ok' => false, 'reason' => __( 'لغو نوبت توسط مشتری غیرفعال است.', 'mr-booking' ), 'minutes_left' => $minutes_left );
		}
		if ( ! in_array( (string) $booking->status, array( 'pending', 'confirmed' ), true ) ) {
			return array( 'ok' => false, 'reason' => __( 'این نوبت قابل لغو نیست.', 'mr-booking' ), 'minutes_left' => $minutes_left );
		}
		if ( $minutes_left < self::cancel_min_minutes() ) {
			return array(
				'ok'           => false,
				'reason'       => sprintf(
					/* translators: %s: minutes */
					__( 'لغو فقط تا %s دقیقه قبل از نوبت ممکن است.', 'mr-booking' ),
					Helpers::to_persian_digits( (string) self::cancel_min_minutes() )
				),
				'minutes_left' => $minutes_left,
			);
		}

		return array( 'ok' => true, 'reason' => '', 'minutes_left' => $minutes_left );
	}

	public static function minutes_until( string $datetime ): int {
		try {
			$start = new \DateTimeImmutable( $datetime, wp_timezone() );
			$now   = new \DateTimeImmutable( 'now', wp_timezone() );
		} catch ( \Exception $e ) {
			return -1;
		}

		return (int) floor( ( $start->getTimestamp() - $now->getTimestamp() ) / 60 );
	}

	/**
	 * Cancel on behalf of the logged-in customer (ownership + policy checked).
	 *
	 * @return array{ok:bool,error?:string}
	 */
	public static function cancel_own_booking( int $booking_id ): array {
		$customer = self::current_customer();
		if ( ! $customer ) {
			return array( 'ok' => false, 'error' => __( 'ابتدا وارد حساب کاربری شوید.', 'mr-booking' ) );
		}

		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking || (int) $booking->customer_id !== (int) $customer->id ) {
			return array( 'ok' => false, 'error' => __( 'نوبت یافت نشد.', 'mr-booking' ) );
		}

		$policy = self::can_cancel( $booking );
		if ( ! $policy['ok'] ) {
			return array( 'ok' => false, 'error' => $policy['reason'] );
		}

		if ( ! Booking_Repository::update_status( $booking_id, 'cancelled' ) ) {
			return array( 'ok' => false, 'error' => __( 'لغو نوبت انجام نشد. دوباره تلاش کنید.', 'mr-booking' ) );
		}

		/**
		 * Fires after a customer cancels their own booking from the account page.
		 *
		 * @param int    $booking_id
		 * @param object $customer
		 */
		do_action( 'mr_booking_customer_cancelled', $booking_id, $customer );

		return array( 'ok' => true );
	}

	/* ─── WordPress integration ─── */

	public function block_admin_for_customers(): void {
		if ( wp_doing_ajax() || ! self::is_customer_user() ) {
			return;
		}
		$url = self::account_url() ?: home_url( '/' );
		wp_safe_redirect( $url );
		exit;
	}

	public function hide_admin_bar( bool $show ): bool {
		return self::is_customer_user() ? false : $show;
	}

	/**
	 * @param string           $redirect_to
	 * @param string           $requested
	 * @param \WP_User|\WP_Error $user
	 */
	public function login_redirect( $redirect_to, $requested, $user ) {
		if ( $user instanceof \WP_User && self::is_customer_user( $user ) ) {
			return self::account_url() ?: home_url( '/' );
		}

		return $redirect_to;
	}
}
