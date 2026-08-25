<?php
/**
 * Admin bootstrap & menus.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin;

use MRBooking\Export\Exporter;
use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;
use MRBooking\Notifications\SMS\SMS_Manager;
use MRBooking\Roles\Capabilities;
use MRBooking\Settings\Color_Presets;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin {

	public function hooks(): void {
		Helpers::ensure_caps();

		( new Setup_Wizard() )->hooks();
		( new Dashboard_Widget() )->hooks();

		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_menu', array( $this, 'menu_badges' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_init', array( $this, 'handle_exports' ) );
		add_action( 'wp_ajax_mr_booking_admin', array( $this, 'ajax' ) );

		add_action( 'admin_init', array( Pages\Appointments::class, 'maybe_restore_filters' ) );
		add_action( 'admin_post_mr_booking_save_settings', array( Pages\Settings_Page::class, 'save' ) );
		add_action( 'admin_post_mr_booking_apply_theme', array( Pages\Settings_Page::class, 'apply_theme' ) );
		add_action( 'admin_post_mr_booking_save_service', array( Pages\Services::class, 'save' ) );
		add_action( 'admin_post_mr_booking_delete_service', array( Pages\Services::class, 'delete' ) );
		add_action( 'admin_post_mr_booking_service_toggle', array( Pages\Services::class, 'quick_toggle' ) );
		add_action( 'admin_post_mr_booking_quick_price', array( Pages\Services::class, 'quick_price' ) );
		add_action( 'admin_post_mr_booking_save_staff', array( Pages\Staff_Page::class, 'save' ) );
		add_action( 'admin_post_mr_booking_delete_staff', array( Pages\Staff_Page::class, 'delete' ) );
		add_action( 'admin_post_mr_booking_save_hours', array( Pages\Working_Hours::class, 'save' ) );
		add_action( 'admin_post_mr_booking_save_holiday', array( Pages\Holidays::class, 'save' ) );
		add_action( 'admin_post_mr_booking_delete_holiday', array( Pages\Holidays::class, 'delete' ) );
		add_action( 'admin_post_mr_booking_save_special', array( Pages\Holidays::class, 'save_special' ) );
		add_action( 'admin_post_mr_booking_delete_special', array( Pages\Holidays::class, 'delete_special' ) );
		add_action( 'admin_post_mr_booking_update_status', array( Pages\Appointments::class, 'update_status' ) );
		add_action( 'admin_post_mr_booking_cancel_booking', array( Pages\Appointments::class, 'cancel_booking' ) );
		add_action( 'admin_post_mr_booking_delete_booking', array( Pages\Appointments::class, 'delete_booking' ) );
		add_action( 'admin_post_mr_booking_send_message', array( Pages\Customers::class, 'send_message' ) );
		add_action( 'admin_post_mr_booking_save_customer', array( Pages\Customers::class, 'save' ) );
		add_action( 'admin_post_mr_booking_wallet_adjust', array( Pages\Customers::class, 'wallet_adjust' ) );
		add_action( 'admin_post_mr_booking_hide_form_help', array( Pages\Dashboard::class, 'hide_form_help' ) );
	}

	public function menu(): void {
		$name = Helpers::plugin_name();

		add_menu_page(
			$name,
			$name,
			Helpers::access_cap(),
			'mr-booking',
			array( Pages\Dashboard::class, 'render' ),
			'dashicons-calendar-alt',
			26
		);

		$pages = array(
			'mr-booking'              => array( __( 'داشبورد', 'mr-booking' ), array( Pages\Dashboard::class, 'render' ), Capabilities::DASHBOARD ),
			'mr-booking-appointments' => array( __( 'نوبت‌ها', 'mr-booking' ), array( Pages\Appointments::class, 'render' ), Capabilities::APPOINTMENTS ),
			'mr-booking-phone'        => array( __( 'رزرو تلفنی', 'mr-booking' ), array( Pages\Phone_Booking::class, 'render' ), Capabilities::PHONE ),
			'mr-booking-walkin'       => array( __( 'مراجعه حضوری', 'mr-booking' ), array( Pages\Walkin_Booking::class, 'render' ), Capabilities::WALKIN ),
			'mr-booking-calendar'     => array( __( 'تقویم', 'mr-booking' ), array( Pages\Calendar_View::class, 'render' ), Capabilities::CALENDAR ),
			'mr-booking-customers'    => array( __( 'مشتریان', 'mr-booking' ), array( Pages\Customers::class, 'render' ), Capabilities::CUSTOMERS ),
			'mr-booking-services'     => array( __( 'خدمات', 'mr-booking' ), array( Pages\Services::class, 'render' ), Capabilities::SERVICES ),
			'mr-booking-staff'        => array( __( 'پرسنل', 'mr-booking' ), array( Pages\Staff_Page::class, 'render' ), Capabilities::STAFF ),
			'mr-booking-hours'        => array( __( 'ساعات کاری', 'mr-booking' ), array( Pages\Working_Hours::class, 'render' ), Capabilities::HOURS ),
			'mr-booking-holidays'     => array( __( 'تعطیلات', 'mr-booking' ), array( Pages\Holidays::class, 'render' ), Capabilities::HOLIDAYS ),
			'mr-booking-notifications'=> array( __( 'اعلان‌ها', 'mr-booking' ), array( Pages\Notifications::class, 'render' ), Capabilities::NOTIFICATIONS ),
			'mr-booking-reports'      => array( __( 'گزارش‌ها', 'mr-booking' ), array( Pages\Reports::class, 'render' ), Capabilities::REPORTS ),
			'mr-booking-accounting'   => array( __( 'حسابداری', 'mr-booking' ), array( Pages\Accounting::class, 'render' ), Capabilities::ACCOUNTING ),
			'mr-booking-settings'     => array( __( 'تنظیمات', 'mr-booking' ), array( Pages\Settings_Page::class, 'render' ), Capabilities::SETTINGS ),
		);

		foreach ( $pages as $slug => $cfg ) {
			add_submenu_page(
				'mr-booking',
				$cfg[0],
				$cfg[0],
				$cfg[2],
				$slug,
				$cfg[1]
			);
		}
	}

	public function menu_badges(): void {
		if ( ! Helpers::user_can( Capabilities::APPOINTMENTS ) ) {
			return;
		}

		global $submenu;
		$pending = Booking_Repository::pending_count();
		if ( $pending < 1 || ! isset( $submenu['mr-booking'] ) ) {
			return;
		}

		$badge = ' <span class="awaiting-mod update-plugins count-' . (int) $pending . '"><span class="pending-count">' . (int) $pending . '</span></span>';

		foreach ( $submenu['mr-booking'] as $index => $item ) {
			if ( isset( $item[2] ) && 'mr-booking-appointments' === $item[2] ) {
				$submenu['mr-booking'][ $index ][0] .= $badge; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				break;
			}
		}
	}

	private function enqueue_admin_styles(): void {
		\MRBooking\Frontend\Assets::enqueue_fonts();

		wp_enqueue_style(
			'mr-booking-admin',
			MR_BOOKING_URL . 'assets/css/admin.css',
			array( 'mr-booking-fonts' ),
			MR_BOOKING_VERSION
		);

		wp_add_inline_style(
			'mr-booking-admin',
			sprintf( ':root{--mrb-font:%s;}', Settings::font_family_css() )
		);
	}

	public function assets( string $hook ): void {
		if ( 'index.php' === $hook ) {
			$this->enqueue_admin_styles();
		}

		if ( false === strpos( $hook, 'mr-booking' ) ) {
			return;
		}

		$this->enqueue_admin_styles();

		wp_enqueue_script(
			'mr-booking-color-input',
			MR_BOOKING_URL . 'assets/js/color-input.js',
			array(),
			MR_BOOKING_VERSION,
			true
		);

		wp_enqueue_script(
			'mr-booking-birth-picker',
			MR_BOOKING_URL . 'assets/js/birth-picker.js',
			array(),
			MR_BOOKING_VERSION,
			true
		);

		wp_enqueue_script(
			'mr-booking-money-input',
			MR_BOOKING_URL . 'assets/js/money-input.js',
			array(),
			MR_BOOKING_VERSION,
			true
		);

		wp_enqueue_script(
			'mr-booking-admin',
			MR_BOOKING_URL . 'assets/js/admin.js',
			array( 'jquery', 'mr-booking-color-input', 'mr-booking-birth-picker', 'mr-booking-money-input' ),
			MR_BOOKING_VERSION,
			true
		);

		$notify_poll_seconds = max( 0, (int) Settings::get_value( 'admin_notify_poll_seconds', 30 ) );
		if ( $notify_poll_seconds > 0 ) {
			$notify_poll_seconds = max( 15, min( 600, $notify_poll_seconds ) );
		}

		wp_localize_script(
			'mr-booking-admin',
			'mrBookingAdmin',
			array_merge(
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'mr_booking_admin' ),
					'pollBookings'    => false !== strpos( $hook, 'mr-booking' ) && $notify_poll_seconds > 0 && Helpers::user_can( Capabilities::APPOINTMENTS ),
					'latestBookingId' => Booking_Repository::latest_id(),
					'appointmentsUrl' => admin_url( 'admin.php?page=mr-booking-appointments' ),
					'dashboardUrl'    => admin_url( 'admin.php?page=mr-booking' ),
					'pollIntervalMs'  => $notify_poll_seconds > 0 ? $notify_poll_seconds * 1000 : 0,
					'i18n'    => array(
						'confirmDelete'       => __( 'آیا مطمئن هستید؟', 'mr-booking' ),
						'saved'               => __( 'ذخیره شد.', 'mr-booking' ),
						'error'               => __( 'خطا رخ داد.', 'mr-booking' ),
						'durationPreview'     => __( 'این خدمت %s از وقت مشتری را می‌گیرد.', 'mr-booking' ),
						'minute'              => __( 'دقیقه', 'mr-booking' ),
						'hour'                => __( 'ساعت', 'mr-booking' ),
						'hourAndMinute'       => __( '%1$s ساعت و %2$s دقیقه', 'mr-booking' ),
						'applyBlocksNoSource' => __( 'لطفاً ابتدا بازه زمانی (از و تا) را در حداقل یک روز وارد کنید.', 'mr-booking' ),
						'themePreviewApplied' => __( 'پیش‌نمایش قالب در فیلدهای رنگ اعمال شد. برای ثبت نهایی «ذخیره تنظیمات» را بزنید.', 'mr-booking' ),
						'smsTesting'          => __( 'در حال تست اتصال…', 'mr-booking' ),
						'smsTestSuccess'      => __( 'اتصال برقرار است.', 'mr-booking' ),
						'smsTestFailed'       => __( 'اتصال ناموفق بود.', 'mr-booking' ),
						'newBookingTitle'     => __( 'رزرو جدید', 'mr-booking' ),
						'newBookingBody'      => __( '%1$s — %2$s', 'mr-booking' ),
						'newBookingReload'    => __( 'مشاهده لیست', 'mr-booking' ),
						'newBookingDismiss'   => __( 'بستن', 'mr-booking' ),
						'newBookingMute'      => __( 'بی‌صدا', 'mr-booking' ),
						'selectBirth'         => __( 'انتخاب تاریخ تولد', 'mr-booking' ),
						'currency'            => __( 'تومان', 'mr-booking' ),
						'noPrice'             => __( 'بدون قیمت', 'mr-booking' ),
						'noDeposit'           => __( 'بدون پیش‌پرداخت', 'mr-booking' ),
					),
					'calendarMode' => 'gregorian' === Helpers::admin_calendar_mode() ? 'gregorian' : 'jalali',
					'jalaliMonths' => \MRBooking\Calendar\Jalali::month_names(),
				),
				false !== strpos( $hook, 'mr-booking-settings' )
					? array(
						'colorPresets'    => Color_Presets::all(),
						'colorPresetKeys' => Color_Presets::theme_keys(),
					)
					: array()
			)
		);

		wp_enqueue_media();

		$needs_birth_picker = false !== strpos( $hook, 'mr-booking-phone' )
			|| false !== strpos( $hook, 'mr-booking-customers' )
			|| false !== strpos( $hook, 'mr-booking-appointments' );

		if ( $needs_birth_picker ) {
			wp_enqueue_style(
				'mr-booking-frontend',
				MR_BOOKING_URL . 'assets/css/frontend.css',
				array( 'mr-booking-admin' ),
				MR_BOOKING_VERSION
			);
		}

		if ( false !== strpos( $hook, 'mr-booking-walkin' ) ) {
			wp_enqueue_style(
				'mr-booking-frontend',
				MR_BOOKING_URL . 'assets/css/frontend.css',
				array( 'mr-booking-admin' ),
				MR_BOOKING_VERSION
			);
			wp_add_inline_style(
				'mr-booking-frontend',
				\MRBooking\Frontend\Assets::color_css_vars( '.mrb-walkin-page' )
			);

			wp_enqueue_script(
				'mr-booking-walkin',
				MR_BOOKING_URL . 'assets/js/walkin-booking.js',
				array( 'mr-booking-money-input' ),
				MR_BOOKING_VERSION,
				true
			);

			wp_localize_script(
				'mr-booking-walkin',
				'mrWalkinBooking',
				array(
					'restUrl'  => esc_url_raw( rest_url( 'mr-booking/v1' ) ),
					'nonce'    => wp_create_nonce( 'wp_rest' ),
					'settings' => array(
						'enable_multi_service' => (int) Settings::get_value( 'enable_multi_service', 1 ),
					),
					'appointmentsUrl' => admin_url( 'admin.php?page=mr-booking-appointments' ),
					'accountingUrl'   => admin_url( 'admin.php?page=mr-booking-accounting' ),
					'currency'        => __( 'تومان', 'mr-booking' ),
					'i18n'            => array(
						'error'         => __( 'خطایی رخ داد.', 'mr-booking' ),
						'selectService' => __( 'لطفاً حداقل یک خدمت انتخاب کنید.', 'mr-booking' ),
						'submit'        => __( 'ثبت مراجعه حضوری', 'mr-booking' ),
						'submitting'    => __( 'در حال ثبت...', 'mr-booking' ),
						'noCustomer'    => __( 'مشتری یافت نشد.', 'mr-booking' ),
						'required'      => __( 'این فیلد الزامی است.', 'mr-booking' ),
						'invalidPhone'  => __( 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۱۲۳۴۵۶۷', 'mr-booking' ),
						'invalidName'   => __( 'نام باید حداقل ۲ حرف باشد.', 'mr-booking' ),
						'invalidEmail'  => __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ),
						'invalidPrice'  => __( 'مبلغ باید عددی صفر یا بیشتر باشد.', 'mr-booking' ),
						'fixErrors'     => __( 'لطفاً موارد مشخص‌شده را اصلاح کنید.', 'mr-booking' ),
						'bookingCode'   => __( 'کد رزرو', 'mr-booking' ),
						'total'         => __( 'جمع', 'mr-booking' ),
						'free'          => __( 'بدون مبلغ', 'mr-booking' ),
						'newCustomer'   => __( 'مشتری جدید', 'mr-booking' ),
						'existing'      => __( 'مشتری موجود', 'mr-booking' ),
						'viewBooking'   => __( 'مشاهده نوبت', 'mr-booking' ),
						'another'       => __( 'ثبت مراجعه بعدی', 'mr-booking' ),
					),
				)
			);
		}

		if ( false !== strpos( $hook, 'mr-booking-phone' ) ) {
			wp_add_inline_style(
				'mr-booking-frontend',
				\MRBooking\Frontend\Assets::color_css_vars( '.mrb-phone-book-page' )
			);

			wp_enqueue_script(
				'mr-booking-phone',
				MR_BOOKING_URL . 'assets/js/phone-booking.js',
				array( 'mr-booking-birth-picker' ),
				MR_BOOKING_VERSION,
				true
			);

			$settings = \MRBooking\Settings\Settings::get();

			wp_localize_script(
				'mr-booking-phone',
				'mrPhoneBooking',
				array(
					'restUrl' => esc_url_raw( rest_url( 'mr-booking/v1' ) ),
					'nonce'   => wp_create_nonce( 'wp_rest' ),
					'settings'=> array(
						'calendar_mode'        => \MRBooking\Helpers::admin_calendar_mode(),
						'hour_format'          => \MRBooking\Helpers::hour_format(),
						'enable_multi_service' => (int) $settings['enable_multi_service'],
						'allow_same_day'       => (int) $settings['allow_same_day'],
						'today'                => \MRBooking\Bookings\Slot_Engine::today(),
					),
					'months'  => \MRBooking\Calendar\Jalali::month_names(),
					'texts'   => array(
						'no_slots' => $settings['text_no_slots'],
					),
					'i18n'    => array(
						'loading'      => __( 'در حال بارگذاری...', 'mr-booking' ),
						'error'        => __( 'خطایی رخ داد.', 'mr-booking' ),
						'selectService'=> __( 'لطفاً حداقل یک خدمت انتخاب کنید.', 'mr-booking' ),
						'selectDate'   => __( 'لطفاً تاریخ را انتخاب کنید.', 'mr-booking' ),
						'selectTime'   => __( 'لطفاً ساعت را انتخاب کنید.', 'mr-booking' ),
						'submit'       => __( 'ثبت رزرو تلفنی', 'mr-booking' ),
						'submitting'   => __( 'در حال ثبت...', 'mr-booking' ),
						'noCustomer'   => __( 'مشتری یافت نشد.', 'mr-booking' ),
						'selectBirth'  => __( 'انتخاب تاریخ تولد', 'mr-booking' ),
						'required'     => __( 'این فیلد الزامی است.', 'mr-booking' ),
						'todayLabel'   => __( 'امروز', 'mr-booking' ),
						'invalidPhone' => __( 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۱۲۳۴۵۶۷', 'mr-booking' ),
						'invalidName'  => __( 'نام باید حداقل ۲ حرف باشد.', 'mr-booking' ),
						'invalidEmail' => __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ),
						'fixErrors'    => __( 'لطفاً موارد مشخص‌شده را اصلاح کنید.', 'mr-booking' ),
						'bookingCode'  => __( 'کد رزرو', 'mr-booking' ),
						'am'           => __( 'ق.ظ', 'mr-booking' ),
						'pm'           => __( 'ب.ظ', 'mr-booking' ),
					),
				)
			);
		}
	}

	public function handle_exports(): void {
		if ( ! isset( $_GET['mr_export'] ) || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'mr_export' ) ) {
			return;
		}

		$type = sanitize_text_field( wp_unslash( $_GET['mr_export'] ) );

		if ( 'accounting' === $type ) {
			if ( Helpers::user_can( Capabilities::ACCOUNTING ) ) {
				Exporter::accounting_csv( Pages\Accounting::filters_from_request() );
			}
			return;
		}

		if ( ! Helpers::user_can( Capabilities::REPORTS ) ) {
			return;
		}

		if ( 'customers' === $type ) {
			Exporter::customers_csv( $_GET ); // phpcs:ignore
		}
		if ( 'bookings' === $type ) {
			Exporter::bookings_csv( $_GET ); // phpcs:ignore
		}
	}

	public function ajax(): void {
		check_ajax_referer( 'mr_booking_admin', 'nonce' );

		$action = sanitize_text_field( wp_unslash( $_POST['mr_action'] ?? '' ) );

		if ( 'update_status' === $action ) {
			if ( ! Helpers::user_can( Capabilities::APPOINTMENTS ) ) {
				wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
			}
			$id     = absint( $_POST['id'] ?? 0 );
			$status = sanitize_text_field( wp_unslash( $_POST['status'] ?? '' ) );
			$ok = Booking_Repository::update_status( $id, $status );
			wp_send_json_success( array( 'ok' => $ok ) );
		}

		if ( 'check_new_bookings' === $action ) {
			if ( ! Helpers::user_can( Capabilities::APPOINTMENTS ) ) {
				wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
			}
			$since_id = absint( $_POST['since_id'] ?? 0 );
			$new      = Booking_Repository::query(
				array(
					'id_min'  => $since_id,
					'orderby' => 'id',
					'order'   => 'ASC',
					'limit'   => 20,
				)
			);

			wp_send_json_success(
				array(
					'latest_id'     => Booking_Repository::latest_id(),
					'pending_count' => Booking_Repository::pending_count(),
					'bookings'      => Booking_Repository::format_for_admin_notice( $new ),
				)
			);
		}

		if ( 'test_sms_connection' === $action ) {
			if ( ! Helpers::user_can( Capabilities::SETTINGS ) ) {
				wp_send_json_error( array( 'message' => 'Forbidden' ), 403 );
			}
			$fields = array(
				'sms_provider' => sanitize_text_field( wp_unslash( $_POST['sms_provider'] ?? '' ) ),
				'sms_api_key'  => sanitize_text_field( wp_unslash( $_POST['sms_api_key'] ?? '' ) ),
				'sms_username' => sanitize_text_field( wp_unslash( $_POST['sms_username'] ?? '' ) ),
				'sms_password' => sanitize_text_field( wp_unslash( $_POST['sms_password'] ?? '' ) ),
				'sms_sender'   => sanitize_text_field( wp_unslash( $_POST['sms_sender'] ?? '' ) ),
				'sms_api_url'  => esc_url_raw( wp_unslash( $_POST['sms_api_url'] ?? '' ) ),
			);
			$overrides = array();
			foreach ( $fields as $key => $value ) {
				if ( '' !== $value ) {
					$overrides[ $key ] = $value;
				}
			}

			$result = SMS_Manager::test_connection( $overrides );
			if ( ! empty( $result['ok'] ) ) {
				wp_send_json_success( $result );
			}

			wp_send_json_error( $result );
		}

		wp_send_json_error( array( 'message' => 'Unknown action' ) );
	}
}
