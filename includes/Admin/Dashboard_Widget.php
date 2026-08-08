<?php
/**
 * WordPress admin dashboard widget.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Dashboard_Widget {

	/**
	 * Available summary items.
	 *
	 * @return array<string, array{label:string,desc:string,default:int}>
	 */
	public static function items(): array {
		return array(
			'today_bookings'    => array(
				'label'   => __( 'رزروهای امروز', 'mr-booking' ),
				'desc'    => __( 'نوبت‌های فعال امروز', 'mr-booking' ),
				'default' => 1,
			),
			'pending'           => array(
				'label'   => __( 'در انتظار تأیید', 'mr-booking' ),
				'desc'    => __( 'رزروهای با وضعیت در انتظار', 'mr-booking' ),
				'default' => 1,
			),
			'customers'         => array(
				'label'   => __( 'تعداد مشتریان', 'mr-booking' ),
				'desc'    => __( 'کل مشتریان ثبت‌شده', 'mr-booking' ),
				'default' => 1,
			),
			'upcoming_week'     => array(
				'label'   => __( 'نوبت‌های ۷ روز آینده', 'mr-booking' ),
				'desc'    => __( 'رزروهای هفته پیش‌رو', 'mr-booking' ),
				'default' => 1,
			),
			'month_bookings'    => array(
				'label'   => __( 'رزروهای این ماه', 'mr-booking' ),
				'desc'    => __( 'کل رزروهای ماه جاری', 'mr-booking' ),
				'default' => 0,
			),
			'confirmed_today'   => array(
				'label'   => __( 'تأیید‌شده‌های امروز', 'mr-booking' ),
				'desc'    => __( 'نوبت‌های تأییدشده امروز', 'mr-booking' ),
				'default' => 0,
			),
			'cancelled_month'   => array(
				'label'   => __( 'لغوشده‌های این ماه', 'mr-booking' ),
				'desc'    => __( 'رزروهای لغو شده در ماه جاری', 'mr-booking' ),
				'default' => 0,
			),
			'birthday_today'    => array(
				'label'   => __( 'تولد امروز', 'mr-booking' ),
				'desc'    => __( 'مشتریانی که امروز تولدشان است', 'mr-booking' ),
				'default' => 0,
			),
			'active_services'   => array(
				'label'   => __( 'خدمات فعال', 'mr-booking' ),
				'desc'    => __( 'تعداد خدمات فعال', 'mr-booking' ),
				'default' => 0,
			),
			'active_staff'      => array(
				'label'   => __( 'پرسنل فعال', 'mr-booking' ),
				'desc'    => __( 'تعداد پرسنل فعال', 'mr-booking' ),
				'default' => 0,
			),
			'revenue_month'     => array(
				'label'   => __( 'درآمد این ماه', 'mr-booking' ),
				'desc'    => __( 'مجموع مبلغ رزروهای تأیید/انجام‌شده', 'mr-booking' ),
				'default' => 0,
			),
		);
	}

	/**
	 * Setting key for an item.
	 */
	public static function setting_key( string $item ): string {
		return 'dash_show_' . $item;
	}

	/**
	 * @return list<string>
	 */
	public static function bool_setting_keys(): array {
		$keys = array( 'dashboard_widget_enabled', 'dashboard_show_form_help' );
		foreach ( array_keys( self::items() ) as $item ) {
			$keys[] = self::setting_key( $item );
		}
		return $keys;
	}

	/**
	 * Default values for settings merge.
	 *
	 * @return array<string, int>
	 */
	public static function default_settings(): array {
		$out = array(
			'dashboard_widget_enabled'  => 1,
			'dashboard_show_form_help'  => 1,
			'admin_notify_poll_seconds' => 30,
		);
		foreach ( self::items() as $key => $meta ) {
			$out[ self::setting_key( $key ) ] = (int) $meta['default'];
		}
		return $out;
	}

	public function hooks(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'register' ) );
	}

	public function register(): void {
		if ( ! Helpers::user_can( \MRBooking\Roles\Capabilities::DASHBOARD ) ) {
			return;
		}

		if ( empty( Settings::get_value( 'dashboard_widget_enabled', 1 ) ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'mr_booking_activity',
			__( 'MR Booking — خلاصه فعالیت', 'mr-booking' ),
			array( $this, 'render' ),
			array( $this, 'control' )
		);
	}

	/**
	 * Configure widget (WordPress dashboard widget configure callback).
	 */
	public function control(): void {
		if ( isset( $_POST['mr_booking_dash_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mr_booking_dash_nonce'] ) ), 'mr_booking_dash_config' ) ) {
			$update = array();
			foreach ( array_keys( self::items() ) as $item ) {
				$key            = self::setting_key( $item );
				$update[ $key ] = ! empty( $_POST[ $key ] ) ? 1 : 0;
			}
			Settings::update( $update );
		}

		$settings = Settings::get();
		echo '<p><strong>' . esc_html__( 'موارد قابل نمایش:', 'mr-booking' ) . '</strong></p>';
		wp_nonce_field( 'mr_booking_dash_config', 'mr_booking_dash_nonce' );

		foreach ( self::items() as $item => $meta ) {
			$key = self::setting_key( $item );
			printf(
				'<label style="display:block;margin:6px 0;"><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
				esc_attr( $key ),
				checked( ! empty( $settings[ $key ] ), true, false ),
				esc_html( $meta['label'] )
			);
		}

		echo '<p class="description">' . esc_html__( 'همچنین می‌توانید این گزینه‌ها را از تنظیمات MR Booking مدیریت کنید.', 'mr-booking' ) . '</p>';
	}

	public function render(): void {
		$stats = self::collect_stats();
		$items = self::items();
		$settings = Settings::get();

		$visible = array();
		foreach ( $items as $key => $meta ) {
			if ( ! empty( $settings[ self::setting_key( $key ) ] ) && isset( $stats[ $key ] ) ) {
				$visible[ $key ] = array(
					'label' => $meta['label'],
					'value' => $stats[ $key ],
				);
			}
		}

		include MR_BOOKING_PATH . 'templates/admin/dashboard-widget.php';
	}

	/**
	 * @return array<string, string|int|float>
	 */
	public static function collect_stats(): array {
		$today     = current_time( 'Y-m-d' );
		$week_end  = gmdate( 'Y-m-d', strtotime( '+6 days', strtotime( $today ) ) );
		$month_from = gmdate( 'Y-m-01', strtotime( $today ) );
		$month_to   = gmdate( 'Y-m-t', strtotime( $today ) );

		$month_stats = Booking_Repository::stats( $month_from, $month_to );

		$all_pending = Booking_Repository::stats();

		$confirmed_today = count(
			Booking_Repository::query(
				array(
					'date'   => $today,
					'status' => 'confirmed',
					'limit'  => 500,
				)
			)
		);

		$upcoming = Booking_Repository::query(
			array(
				'date_from'        => $today,
				'date_to'          => $week_end,
				'exclude_statuses' => array( 'cancelled', 'rejected' ),
				'limit'            => 500,
				'order'            => 'ASC',
			)
		);

		$birthdays = Customer_Repository::query(
			array(
				'birthday_today' => 1,
				'limit'          => 500,
			)
		);

		return array(
			'today_bookings'  => Booking_Repository::count_today(),
			'pending'         => (int) ( $all_pending['pending'] ?? 0 ),
			'customers'       => Customer_Repository::count(),
			'upcoming_week'   => count( $upcoming ),
			'month_bookings'  => (int) ( $month_stats['total'] ?? 0 ),
			'confirmed_today' => $confirmed_today,
			'cancelled_month' => (int) ( $month_stats['cancelled'] ?? 0 ),
			'birthday_today'  => count( $birthdays ),
			'active_services' => count( Service_Repository::all( 'active' ) ),
			'active_staff'    => count( Staff_Repository::all( 'active' ) ),
			'revenue_month'   => (float) ( $month_stats['revenue'] ?? 0 ),
		);
	}
}
