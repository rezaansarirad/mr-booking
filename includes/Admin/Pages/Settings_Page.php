<?php
/**
 * Settings admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Notifications\SMS\SMS_Manager;
use MRBooking\Settings\Settings;
use MRBooking\Admin\Dashboard_Widget;

defined( 'ABSPATH' ) || exit;

final class Settings_Page {

	/**
	 * Checkbox keys rendered on each settings tab (only these reset to 0 when unchecked on save).
	 *
	 * @return list<string>
	 */
	private static function tab_bool_keys( string $tab ): array {
		$map = array(
			'general'    => array( 'show_prices', 'enable_multi_service', 'enable_staff_selection', 'auto_assign_staff' ),
			'dashboard'  => Dashboard_Widget::bool_setting_keys(),
			'calendar'   => array( 'allow_holiday_booking' ),
			'rules'      => array( 'allow_same_day', 'require_phone', 'require_email', 'require_birth_date', 'require_staff', 'reminder_enabled' ),
			'appearance' => array( 'bg_gradient_enabled' ),
			'sms'        => array( 'sms_enabled' ),
			'email'      => array( 'email_enabled' ),
			'premium'    => array( 'hide_branding' ),
		);

		return $map[ $tab ] ?? array();
	}

	public static function render(): void {
		$settings  = Settings::get();
		$tab       = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		$providers = SMS_Manager::providers();

		// Allow re-running setup wizard from settings.
		if ( ! empty( $_GET['rerun_setup'] ) && current_user_can( \MRBooking\Helpers::manage_cap() ) ) { // phpcs:ignore
			check_admin_referer( 'mr_booking_rerun_setup' );
			update_option( \MRBooking\Admin\Setup_Wizard::OPTION_COMPLETE, 0 );
			\MRBooking\Admin\Setup_Wizard::mark_needed();
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-setup' ) );
			exit;
		}

		include MR_BOOKING_PATH . 'templates/admin/settings.php';
	}

	public static function save(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_save_settings' );

		$raw  = isset( $_POST['settings'] ) && is_array( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array(); // phpcs:ignore
		$data = array();

		$int_keys = array(
			'booking_interval',
			'min_notice_minutes',
			'max_days_ahead',
			'cancellation_deadline',
			'reschedule_deadline',
			'reminder_hours_before',
			'break_between_appointments',
			'bg_gradient_primary_mix',
			'bg_gradient_accent_mix',
		);

		$bool_keys = array_merge(
			array(
				'allow_same_day',
				'allow_holiday_booking',
				'allow_cancellation',
				'allow_reschedule',
				'require_email',
				'require_phone',
				'require_birth_date',
				'require_staff',
				'enable_staff_selection',
				'enable_multi_service',
				'enable_guest_booking',
				'show_prices',
				'auto_assign_staff',
				'email_enabled',
				'sms_enabled',
				'reminder_enabled',
				'hide_branding',
				'bg_gradient_enabled',
			),
			Dashboard_Widget::bool_setting_keys()
		);

		foreach ( $raw as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( in_array( $key, $int_keys, true ) ) {
				$data[ $key ] = absint( $value );
				if ( in_array( $key, array( 'bg_gradient_primary_mix', 'bg_gradient_accent_mix' ), true ) ) {
					$data[ $key ] = max( 0, min( 100, (int) $data[ $key ] ) );
				}
			} elseif ( in_array( $key, $bool_keys, true ) ) {
				$data[ $key ] = ! empty( $value ) ? 1 : 0;
			} elseif ( 'font_family' === $key ) {
				$fonts = Settings::font_families();
				$data[ $key ] = isset( $fonts[ (string) $value ] ) ? (string) $value : 'vazirmatn';
			} elseif ( 0 === strpos( $key, 'color_' ) ) {
				$data[ $key ] = sanitize_hex_color( (string) $value ) ?: Settings::defaults()[ $key ] ?? '#000000';
			} elseif ( 0 === strpos( $key, 'tpl_' ) || 0 === strpos( $key, 'text_' ) ) {
				$data[ $key ] = wp_kses_post( (string) $value );
			} elseif ( 'email_from_email' === $key || 'email_reply_to' === $key || 'email_admin' === $key ) {
				$data[ $key ] = sanitize_email( (string) $value );
			} elseif ( 'notify_emails' === $key || 'notify_phones' === $key ) {
				$data[ $key ] = sanitize_textarea_field( (string) $value );
			} elseif ( 'premium_key' === $key ) {
				$data[ $key ] = sanitize_text_field( (string) $value );
			} else {
				$data[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		// Unchecked checkboxes on the current tab only (avoid wiping toggles from other tabs).
		$tab = sanitize_text_field( wp_unslash( $_POST['tab'] ?? 'general' ) );
		foreach ( self::tab_bool_keys( $tab ) as $bk ) {
			if ( ! isset( $raw[ $bk ] ) ) {
				$data[ $bk ] = 0;
			}
		}

		if ( isset( $data['hours_mode'] ) && ! in_array( $data['hours_mode'], array( 'global', 'per_staff' ), true ) ) {
			$data['hours_mode'] = 'global';
		}

		// Premium gate: hide_branding only when license is valid.
		$effective_key = isset( $data['premium_key'] )
			? (string) $data['premium_key']
			: (string) Settings::get_value( 'premium_key', '' );
		if ( ! \MRBooking\Premium\License::is_valid_key( $effective_key ) ) {
			$data['hide_branding'] = 0;
		}

		Settings::update( $data );

		if ( 'notifications' === $tab ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-notifications&saved=1' ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-settings&tab=' . $tab . '&saved=1' ) );
		}
		exit;
	}
}
