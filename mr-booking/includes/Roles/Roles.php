<?php
/**
 * Register booking staff roles.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Roles;

defined( 'ABSPATH' ) || exit;

final class Roles {

	public const RECEPTIONIST = 'mr_booking_receptionist';
	public const MANAGER      = 'mr_booking_manager';
	public const CUSTOMER     = 'mr_booking_customer';

	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ), 5 );
	}

	public static function register(): void {
		self::grant_administrator_caps();
		self::register_receptionist();
		self::register_manager();
		self::register_customer();
	}

	/**
	 * Front-end customers (OTP login). No admin capabilities at all.
	 */
	private static function register_customer(): void {
		if ( get_role( self::CUSTOMER ) ) {
			return;
		}
		add_role( self::CUSTOMER, __( 'مشتری رزرو', 'mr-booking' ), array( 'read' => false ) );
	}

	private static function grant_administrator_caps(): void {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}

		foreach ( Capabilities::all() as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}
	}

	private static function register_receptionist(): void {
		$caps = array(
			Capabilities::ACCESS       => true,
			Capabilities::APPOINTMENTS => true,
			Capabilities::PHONE        => true,
			Capabilities::WALKIN       => true,
			Capabilities::CUSTOMERS    => true,
			Capabilities::CALENDAR     => true,
			'read'                     => true,
		);

		self::upsert_role(
			self::RECEPTIONIST,
			__( 'منشی رزرو', 'mr-booking' ),
			$caps
		);
	}

	private static function register_manager(): void {
		$caps = array(
			Capabilities::ACCESS         => true,
			Capabilities::DASHBOARD      => true,
			Capabilities::APPOINTMENTS   => true,
			Capabilities::PHONE          => true,
			Capabilities::CALENDAR       => true,
			Capabilities::CUSTOMERS      => true,
			Capabilities::SERVICES       => true,
			Capabilities::STAFF          => true,
			Capabilities::HOURS          => true,
			Capabilities::HOLIDAYS       => true,
			Capabilities::NOTIFICATIONS  => true,
			Capabilities::REPORTS        => true,
			Capabilities::ACCOUNTING     => true,
			Capabilities::WALKIN         => true,
			Capabilities::SETTINGS       => true,
			'read'                       => true,
		);

		self::upsert_role(
			self::MANAGER,
			__( 'مدیر رزرو', 'mr-booking' ),
			$caps
		);
	}

	/**
	 * @param array<string, bool> $caps
	 */
	private static function upsert_role( string $slug, string $label, array $caps ): void {
		$role = get_role( $slug );
		if ( ! $role ) {
			add_role( $slug, $label, $caps );
			return;
		}

		foreach ( $caps as $cap => $grant ) {
			if ( $grant ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * @return array<string, array{label:string,desc:string,caps:list<string>}>
	 */
	public static function definitions(): array {
		return array(
			self::RECEPTIONIST => array(
				'label' => __( 'منشی رزرو', 'mr-booking' ),
				'desc'  => __( 'فقط نوبت‌ها، رزرو تلفنی، مشتریان و تقویم — مناسب پذیرش تلفنی', 'mr-booking' ),
				'caps'  => array(
					Capabilities::APPOINTMENTS,
					Capabilities::PHONE,
					Capabilities::WALKIN,
					Capabilities::CUSTOMERS,
					Capabilities::CALENDAR,
				),
			),
			self::MANAGER => array(
				'label' => __( 'مدیر رزرو', 'mr-booking' ),
				'desc'  => __( 'دسترسی کامل به همه بخش‌های افزونه رزرو، شامل تنظیمات', 'mr-booking' ),
				'caps'  => array(
					Capabilities::DASHBOARD,
					Capabilities::APPOINTMENTS,
					Capabilities::PHONE,
					Capabilities::CALENDAR,
					Capabilities::CUSTOMERS,
					Capabilities::SERVICES,
					Capabilities::STAFF,
					Capabilities::HOURS,
					Capabilities::HOLIDAYS,
					Capabilities::NOTIFICATIONS,
					Capabilities::REPORTS,
					Capabilities::ACCOUNTING,
					Capabilities::WALKIN,
					Capabilities::SETTINGS,
				),
			),
		);
	}
}
