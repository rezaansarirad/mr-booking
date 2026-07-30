<?php
/**
 * First-run setup wizard.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin;

use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;
use MRBooking\WorkingHours\Hours_Repository;

defined( 'ABSPATH' ) || exit;

final class Setup_Wizard {

	public const OPTION_COMPLETE = 'mr_booking_setup_complete';
	public const OPTION_REDIRECT = 'mr_booking_setup_redirect';

	public function hooks(): void {
		// After parent menu (Admin::menu priority 10) so the page is reachable.
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_mr_booking_setup_wizard', array( $this, 'save_step' ) );
		add_action( 'admin_post_mr_booking_setup_skip', array( $this, 'skip' ) );
	}

	public static function mark_needed(): void {
		update_option( self::OPTION_COMPLETE, 0, false );
		set_transient( self::OPTION_REDIRECT, 1, MINUTE_IN_SECONDS * 5 );
	}

	public static function is_complete(): bool {
		return (int) get_option( self::OPTION_COMPLETE, 0 ) === 1;
	}

	public function menu(): void {
		add_submenu_page(
			'mr-booking',
			__( 'راه‌اندازی اولیه', 'mr-booking' ),
			__( 'راه‌اندازی اولیه', 'mr-booking' ),
			Helpers::manage_cap(),
			'mr-booking-setup',
			array( $this, 'render' )
		);

		// Keep page reachable by URL, but hide from sidebar after setup is done.
		if ( self::is_complete() ) {
			remove_submenu_page( 'mr-booking', 'mr-booking-setup' );
		}
	}

	public function maybe_redirect(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) || self::is_complete() ) {
			return;
		}

		if ( ! get_transient( self::OPTION_REDIRECT ) ) {
			return;
		}

		// Only redirect once after activation.
		delete_transient( self::OPTION_REDIRECT );

		if ( wp_doing_ajax() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
		if ( 'mr-booking-setup' === $page ) {
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-setup' ) );
		exit;
	}

	public function notice(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) || self::is_complete() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore
		if ( 'mr-booking-setup' === $page ) {
			return;
		}

		$url = admin_url( 'admin.php?page=mr-booking-setup' );
		echo '<div class="notice notice-info"><p>';
		echo esc_html__( 'راه‌اندازی اولیه MR Booking هنوز کامل نشده است.', 'mr-booking' );
		echo ' <a href="' . esc_url( $url ) . '"><strong>' . esc_html__( 'ادامه ویزارد', 'mr-booking' ) . '</strong></a>';
		echo '</p></div>';
	}

	public function assets( string $hook ): void {
		if ( false === strpos( $hook, 'mr-booking-setup' ) ) {
			return;
		}

		wp_enqueue_style(
			'mr-booking-admin',
			MR_BOOKING_URL . 'assets/css/admin.css',
			array(),
			MR_BOOKING_VERSION
		);
		wp_enqueue_script(
			'mr-booking-color-input',
			MR_BOOKING_URL . 'assets/js/color-input.js',
			array(),
			MR_BOOKING_VERSION,
			true
		);
		wp_enqueue_script(
			'mr-booking-setup',
			MR_BOOKING_URL . 'assets/js/setup-wizard.js',
			array( 'mr-booking-color-input' ),
			MR_BOOKING_VERSION,
			true
		);
	}

	public function render(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( esc_html__( 'دسترسی غیرمجاز', 'mr-booking' ) );
		}

		$step     = max( 1, min( 6, absint( $_GET['step'] ?? 1 ) ) ); // phpcs:ignore
		$settings = Settings::get();
		$services = Service_Repository::all();
		$staff    = Staff_Repository::all();
		$hours    = Hours_Repository::all_grouped();
		$labels   = Helpers::weekday_labels();
		$order    = array( 6, 0, 1, 2, 3, 4, 5 );

		$active_services   = array_values(
			array_filter(
				$services,
				static fn( $s ) => 'active' === ( $s->status ?? '' )
			)
		);
		$staff_service_map = array();
		foreach ( $staff as $member ) {
			$staff_service_map[ (int) $member->id ] = Staff_Repository::service_ids( (int) $member->id );
		}

		include MR_BOOKING_PATH . 'templates/admin/setup-wizard.php';
	}

	public function skip(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_setup_skip' );
		update_option( self::OPTION_COMPLETE, 1 );
		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking&setup=skipped' ) );
		exit;
	}

	public function save_step(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_setup_wizard' );

		$step = absint( $_POST['step'] ?? 1 );

		switch ( $step ) {
			case 1:
				$this->save_welcome();
				$next = 2;
				break;
			case 2:
				$this->save_calendar();
				$next = 3;
				break;
			case 3:
				$this->save_hours();
				$next = 4;
				break;
			case 4:
				$this->save_services();
				$next = 5;
				break;
			case 5:
				$this->save_staff();
				$next = 6;
				break;
			case 6:
				$this->save_rules();
				update_option( self::OPTION_COMPLETE, 1 );
				wp_safe_redirect( admin_url( 'admin.php?page=mr-booking&setup=done' ) );
				exit;
			default:
				$next = 1;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-setup&step=' . $next ) );
		exit;
	}

	private function save_welcome(): void {
		$name = sanitize_text_field( wp_unslash( $_POST['business_name'] ?? '' ) );
		if ( $name ) {
			Settings::update( array( 'business_name' => $name ) );
		}
	}

	private function save_calendar(): void {
		$mode = sanitize_text_field( wp_unslash( $_POST['calendar_mode'] ?? 'jalali' ) );
		if ( ! in_array( $mode, array( 'jalali', 'gregorian', 'both' ), true ) ) {
			$mode = 'jalali';
		}

		Settings::update(
			array(
				'calendar_mode'         => $mode,
				'allow_holiday_booking' => ! empty( $_POST['allow_holiday_booking'] ) ? 1 : 0,
				'color_holiday'         => sanitize_hex_color( wp_unslash( $_POST['color_holiday'] ?? '#DC2626' ) ) ?: '#DC2626',
			)
		);
	}

	private function save_hours(): void {
		$apply_all = ! empty( $_POST['apply_all'] );
		$closed    = array_map( 'absint', (array) ( $_POST['closed_days'] ?? array() ) );
		$start1    = sanitize_text_field( wp_unslash( $_POST['start1'] ?? '09:00' ) );
		$end1      = sanitize_text_field( wp_unslash( $_POST['end1'] ?? '13:00' ) );
		$start2    = sanitize_text_field( wp_unslash( $_POST['start2'] ?? '' ) );
		$end2      = sanitize_text_field( wp_unslash( $_POST['end2'] ?? '' ) );
		$use_break = ! empty( $_POST['use_second_period'] );

		$period = array(
			array(
				'start' => $start1,
				'end'   => $end1,
			),
		);
		if ( $use_break && $start2 && $end2 ) {
			$period[] = array(
				'start' => $start2,
				'end'   => $end2,
			);
		}

		$days = array();
		for ( $d = 0; $d <= 6; $d++ ) {
			if ( in_array( $d, $closed, true ) ) {
				$days[ $d ] = array( array( 'closed' => true ) );
			} else {
				$days[ $d ] = $apply_all || true ? $period : $period;
			}
		}

		Hours_Repository::save_week( $days );
	}

	private function save_staff(): void {
		// Update service assignments for existing staff.
		$existing_ids = array_map( 'absint', (array) ( $_POST['existing_staff_id'] ?? array() ) );
		$existing_map = isset( $_POST['existing_staff_services'] ) ? (array) wp_unslash( $_POST['existing_staff_services'] ) : array(); // phpcs:ignore

		foreach ( $existing_ids as $staff_id ) {
			if ( $staff_id <= 0 ) {
				continue;
			}
			$service_ids = array_map( 'absint', (array) ( $existing_map[ $staff_id ] ?? array() ) );
			Staff_Repository::sync_services( $staff_id, $service_ids );
		}

		$first_names = isset( $_POST['staff_first'] ) ? (array) wp_unslash( $_POST['staff_first'] ) : array(); // phpcs:ignore
		$last_names  = isset( $_POST['staff_last'] ) ? (array) wp_unslash( $_POST['staff_last'] ) : array(); // phpcs:ignore
		$phones      = isset( $_POST['staff_phone'] ) ? (array) wp_unslash( $_POST['staff_phone'] ) : array(); // phpcs:ignore
		$svc_map     = isset( $_POST['staff_services'] ) ? (array) wp_unslash( $_POST['staff_services'] ) : array(); // phpcs:ignore

		$keys = array_unique( array_merge( array_keys( $first_names ), array_keys( $last_names ) ) );
		foreach ( $keys as $key ) {
			$first = sanitize_text_field( (string) ( $first_names[ $key ] ?? '' ) );
			$last  = sanitize_text_field( (string) ( $last_names[ $key ] ?? '' ) );
			if ( ! $first && ! $last ) {
				continue;
			}

			$service_ids = array_values(
				array_filter(
					array_map( 'absint', (array) ( $svc_map[ $key ] ?? array() ) )
				)
			);

			Staff_Repository::save(
				array(
					'first_name' => $first ?: __( 'پرسنل', 'mr-booking' ),
					'last_name'  => $last,
					'phone'      => sanitize_text_field( (string) ( $phones[ $key ] ?? '' ) ),
					'status'     => 'active',
				),
				$service_ids
			);
		}

		Settings::update(
			array(
				'enable_staff_selection' => ! empty( $_POST['enable_staff_selection'] ) ? 1 : 0,
				'require_staff'          => ! empty( $_POST['require_staff'] ) ? 1 : 0,
				'auto_assign_staff'      => ! empty( $_POST['auto_assign_staff'] ) ? 1 : 0,
			)
		);
	}

	private function save_services(): void {
		$keep = array_map( 'absint', (array) ( $_POST['keep_services'] ?? array() ) );
		$all  = Service_Repository::all();

		// If user unchecked seeded services, deactivate them (don't delete history risk).
		if ( ! empty( $_POST['manage_samples'] ) ) {
			foreach ( $all as $svc ) {
				$status = in_array( (int) $svc->id, $keep, true ) ? 'active' : 'inactive';
				Service_Repository::save(
					array(
						'name'        => $svc->name,
						'description' => $svc->description,
						'duration'    => $svc->duration,
						'price'       => $svc->price,
						'status'      => $status,
						'image_id'    => $svc->image_id,
						'color'       => $svc->color,
						'sort_order'  => $svc->sort_order,
					),
					(int) $svc->id
				);
			}
		}

		// Optional new services.
		$names     = isset( $_POST['new_service_name'] ) ? (array) wp_unslash( $_POST['new_service_name'] ) : array(); // phpcs:ignore
		$durations = isset( $_POST['new_service_duration'] ) ? (array) wp_unslash( $_POST['new_service_duration'] ) : array(); // phpcs:ignore
		$prices    = isset( $_POST['new_service_price'] ) ? (array) wp_unslash( $_POST['new_service_price'] ) : array(); // phpcs:ignore

		foreach ( $names as $i => $name ) {
			$name = sanitize_text_field( (string) $name );
			if ( ! $name ) {
				continue;
			}
			$price = (float) ( $prices[ $i ] ?? 0 );
			Service_Repository::save(
				array(
					'name'      => $name,
					'duration'  => absint( $durations[ $i ] ?? 30 ),
					'has_price' => $price > 0 || ! empty( $_POST['show_prices'] ) ? ( $price > 0 ? 1 : 0 ) : 0,
					'price'     => $price,
					'status'    => 'active',
				)
			);
		}

		Settings::update(
			array(
				'show_prices'          => ! empty( $_POST['show_prices'] ) ? 1 : 0,
				'enable_multi_service' => ! empty( $_POST['enable_multi_service'] ) ? 1 : 0,
			)
		);
	}

	private function save_rules(): void {
		Settings::update(
			array(
				'booking_interval'   => max( 5, absint( $_POST['booking_interval'] ?? 15 ) ),
				'min_notice_minutes' => absint( $_POST['min_notice_minutes'] ?? 60 ),
				'max_days_ahead'     => absint( $_POST['max_days_ahead'] ?? 60 ),
				'allow_same_day'     => ! empty( $_POST['allow_same_day'] ) ? 1 : 0,
				'require_phone'      => ! empty( $_POST['require_phone'] ) ? 1 : 0,
				'require_email'      => ! empty( $_POST['require_email'] ) ? 1 : 0,
				'default_status'     => sanitize_text_field( wp_unslash( $_POST['default_status'] ?? 'pending' ) ),
				'color_primary'      => sanitize_hex_color( wp_unslash( $_POST['color_primary'] ?? '#0F766E' ) ) ?: '#0F766E',
				'color_button'       => sanitize_hex_color( wp_unslash( $_POST['color_button'] ?? '#0F766E' ) ) ?: '#0F766E',
			)
		);
	}
}
