<?php
/**
 * Plugin capabilities and page access map.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Roles;

defined( 'ABSPATH' ) || exit;

final class Capabilities {

	public const MANAGE = 'manage_mr_booking';

	public const ACCESS         = 'mr_booking_access';
	public const DASHBOARD      = 'mr_booking_dashboard';
	public const APPOINTMENTS   = 'mr_booking_appointments';
	public const PHONE            = 'mr_booking_phone';
	public const CALENDAR         = 'mr_booking_calendar';
	public const CUSTOMERS        = 'mr_booking_customers';
	public const SERVICES         = 'mr_booking_services';
	public const STAFF            = 'mr_booking_staff';
	public const HOURS            = 'mr_booking_hours';
	public const HOLIDAYS         = 'mr_booking_holidays';
	public const NOTIFICATIONS    = 'mr_booking_notifications';
	public const REPORTS          = 'mr_booking_reports';
	public const SETTINGS         = 'mr_booking_settings';

	/**
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::MANAGE,
			self::ACCESS,
			self::DASHBOARD,
			self::APPOINTMENTS,
			self::PHONE,
			self::CALENDAR,
			self::CUSTOMERS,
			self::SERVICES,
			self::STAFF,
			self::HOURS,
			self::HOLIDAYS,
			self::NOTIFICATIONS,
			self::REPORTS,
			self::SETTINGS,
		);
	}

	/**
	 * @return array<string, string>
	 */
	public static function page_caps(): array {
		return array(
			'mr-booking'               => self::DASHBOARD,
			'mr-booking-appointments'  => self::APPOINTMENTS,
			'mr-booking-phone'         => self::PHONE,
			'mr-booking-calendar'      => self::CALENDAR,
			'mr-booking-customers'     => self::CUSTOMERS,
			'mr-booking-services'      => self::SERVICES,
			'mr-booking-staff'         => self::STAFF,
			'mr-booking-hours'         => self::HOURS,
			'mr-booking-holidays'      => self::HOLIDAYS,
			'mr-booking-notifications' => self::NOTIFICATIONS,
			'mr-booking-reports'       => self::REPORTS,
			'mr-booking-settings'      => self::SETTINGS,
		);
	}

	/**
	 * @return array<string, array{label:string,desc:string,cap:string}>
	 */
	public static function sections(): array {
		return array(
			self::DASHBOARD      => array(
				'label' => __( 'داشبورد', 'mr-booking' ),
				'desc'  => __( 'خلاصه وضعیت رزروها', 'mr-booking' ),
				'cap'   => self::DASHBOARD,
			),
			self::APPOINTMENTS   => array(
				'label' => __( 'نوبت‌ها', 'mr-booking' ),
				'desc'  => __( 'مشاهده، تأیید و مدیریت رزروها', 'mr-booking' ),
				'cap'   => self::APPOINTMENTS,
			),
			self::PHONE          => array(
				'label' => __( 'رزرو تلفنی', 'mr-booking' ),
				'desc'  => __( 'ثبت رزرو دستی / تلفنی', 'mr-booking' ),
				'cap'   => self::PHONE,
			),
			self::CALENDAR       => array(
				'label' => __( 'تقویم', 'mr-booking' ),
				'desc'  => __( 'نمای تقویمی نوبت‌ها', 'mr-booking' ),
				'cap'   => self::CALENDAR,
			),
			self::CUSTOMERS      => array(
				'label' => __( 'مشتریان', 'mr-booking' ),
				'desc'  => __( 'لیست و پیام به مشتریان', 'mr-booking' ),
				'cap'   => self::CUSTOMERS,
			),
			self::SERVICES       => array(
				'label' => __( 'خدمات', 'mr-booking' ),
				'desc'  => __( 'مدیریت خدمات', 'mr-booking' ),
				'cap'   => self::SERVICES,
			),
			self::STAFF          => array(
				'label' => __( 'پرسنل', 'mr-booking' ),
				'desc'  => __( 'مدیریت پرسنل', 'mr-booking' ),
				'cap'   => self::STAFF,
			),
			self::HOURS          => array(
				'label' => __( 'ساعات کاری', 'mr-booking' ),
				'desc'  => __( 'تنظیم ساعات کاری', 'mr-booking' ),
				'cap'   => self::HOURS,
			),
			self::HOLIDAYS       => array(
				'label' => __( 'تعطیلات', 'mr-booking' ),
				'desc'  => __( 'تعطیلات رسمی و روزهای خاص', 'mr-booking' ),
				'cap'   => self::HOLIDAYS,
			),
			self::NOTIFICATIONS  => array(
				'label' => __( 'اعلان‌ها', 'mr-booking' ),
				'desc'  => __( 'قالب پیامک و ایمیل', 'mr-booking' ),
				'cap'   => self::NOTIFICATIONS,
			),
			self::REPORTS        => array(
				'label' => __( 'گزارش‌ها', 'mr-booking' ),
				'desc'  => __( 'گزارش و خروجی', 'mr-booking' ),
				'cap'   => self::REPORTS,
			),
			self::SETTINGS       => array(
				'label' => __( 'تنظیمات', 'mr-booking' ),
				'desc'  => __( 'پیکربندی کامل افزونه', 'mr-booking' ),
				'cap'   => self::SETTINGS,
			),
		);
	}

	public static function cap_for_page( string $page_slug ): string {
		return self::page_caps()[ $page_slug ] ?? self::MANAGE;
	}

	/**
	 * @return array<string, string>
	 */
	public static function admin_post_caps(): array {
		return array(
			'mr_booking_save_settings'    => self::SETTINGS,
			'mr_booking_apply_theme'        => self::SETTINGS,
			'mr_booking_save_service'       => self::SERVICES,
			'mr_booking_delete_service'     => self::SERVICES,
			'mr_booking_service_toggle'     => self::SERVICES,
			'mr_booking_save_staff'         => self::STAFF,
			'mr_booking_delete_staff'       => self::STAFF,
			'mr_booking_save_hours'         => self::HOURS,
			'mr_booking_save_holiday'       => self::HOLIDAYS,
			'mr_booking_delete_holiday'     => self::HOLIDAYS,
			'mr_booking_save_special'       => self::HOLIDAYS,
			'mr_booking_delete_special'     => self::HOLIDAYS,
			'mr_booking_update_status'      => self::APPOINTMENTS,
			'mr_booking_cancel_booking'     => self::APPOINTMENTS,
			'mr_booking_delete_booking'     => self::APPOINTMENTS,
			'mr_booking_send_message'       => self::CUSTOMERS,
			'mr_booking_save_customer'      => self::CUSTOMERS,
			'mr_booking_hide_form_help'     => self::DASHBOARD,
		);
	}

	public static function cap_for_admin_post( string $action ): string {
		return self::admin_post_caps()[ $action ] ?? self::MANAGE;
	}

	public static function user_can( string $cap, ?\WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		if ( user_can( $user, 'manage_options' ) || user_can( $user, self::MANAGE ) ) {
			return true;
		}

		return user_can( $user, $cap );
	}

	public static function user_can_page( string $page_slug, ?\WP_User $user = null ): bool {
		return self::user_can( self::cap_for_page( $page_slug ), $user );
	}

	public static function user_has_any_booking_access( ?\WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		if ( user_can( $user, 'manage_options' ) || user_can( $user, self::MANAGE ) ) {
			return true;
		}

		foreach ( self::all() as $cap ) {
			if ( self::MANAGE !== $cap && user_can( $user, $cap ) ) {
				return true;
			}
		}

		return false;
	}

	public static function is_booking_only_user( ?\WP_User $user = null ): bool {
		$user = $user ?? wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}

		return self::user_has_any_booking_access( $user )
			&& ! user_can( $user, 'manage_options' )
			&& ! user_can( $user, self::MANAGE );
	}

	/**
	 * First accessible plugin page URL for restricted users.
	 */
	public static function landing_url( ?\WP_User $user = null ): string {
		$user = $user ?? wp_get_current_user();

		foreach ( self::page_caps() as $slug => $cap ) {
			if ( self::user_can( $cap, $user ) ) {
				return admin_url( 'admin.php?page=' . $slug );
			}
		}

		return admin_url( 'admin.php?page=mr-booking' );
	}

	public static function accessible_pages( ?\WP_User $user = null ): array {
		$user  = $user ?? wp_get_current_user();
		$pages = array();

		foreach ( self::page_caps() as $slug => $cap ) {
			if ( self::user_can( $cap, $user ) ) {
				$pages[] = $slug;
			}
		}

		return $pages;
	}
}
