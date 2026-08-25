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
use MRBooking\Settings\Color_Presets;
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
			'premium'       => array( 'hide_branding' ),
			'notifications' => array( 'notify_customer_on_confirm' ),
			'account'       => array( 'allow_cancellation', 'account_embed_booking' ),
			'payment'       => array( 'show_deposit', 'enable_tip', 'enable_wallet_payment', 'enable_online_payment', 'zarinpal_sandbox', 'require_terms' ),
		);

		return $map[ $tab ] ?? array();
	}

	public static function render(): void {
		Helpers::require_page( 'mr-booking-settings' );
		$settings  = Settings::get();
		$tab       = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
		$providers = SMS_Manager::providers();
		$sms_test_hints = array();
		$sms_credit_support = array();
		foreach ( $providers as $slug => $provider ) {
			$sms_test_hints[ $slug ]       = $provider->test_hint();
			$sms_credit_support[ $slug ]   = $provider->supports_account_credit();
		}
		$sms_account_credit = SMS_Manager::get_account_credit();

		// Allow re-running setup wizard from settings.
		if ( ! empty( $_GET['rerun_setup'] ) && Helpers::user_can( \MRBooking\Roles\Capabilities::SETTINGS ) ) { // phpcs:ignore
			check_admin_referer( 'mr_booking_rerun_setup' );
			update_option( \MRBooking\Admin\Setup_Wizard::OPTION_COMPLETE, 0 );
			\MRBooking\Admin\Setup_Wizard::mark_needed();
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-setup' ) );
			exit;
		}

		include MR_BOOKING_PATH . 'templates/admin/settings.php';
	}

	public static function save(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::SETTINGS );
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
			'admin_notify_poll_seconds',
			'customer_cancel_min_minutes',
			'account_page_id',
			'wallet_topup_min',
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
				'notify_customer_on_confirm',
				'hide_branding',
				'bg_gradient_enabled',
				'show_deposit',
				'enable_tip',
				'enable_wallet_payment',
				'enable_online_payment',
				'zarinpal_sandbox',
				'require_terms',
				'account_embed_booking',
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
				if ( 'admin_notify_poll_seconds' === $key ) {
					$secs = (int) $data[ $key ];
					if ( $secs > 0 ) {
						$data[ $key ] = max( 15, min( 600, $secs ) );
					} else {
						$data[ $key ] = 0;
					}
				}
			} elseif ( in_array( $key, $bool_keys, true ) ) {
				$data[ $key ] = ! empty( $value ) ? 1 : 0;
			} elseif ( 'font_family' === $key ) {
				$fonts = Settings::font_families();
				$data[ $key ] = isset( $fonts[ (string) $value ] ) ? (string) $value : 'vazirmatn';
			} elseif ( 'form_theme' === $key ) {
				$data[ $key ] = in_array( (string) $value, array_merge( Color_Presets::theme_ids(), array( 'custom' ) ), true )
					? (string) $value
					: 'custom';
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

		if ( isset( $data['hour_format'] ) && ! in_array( $data['hour_format'], array( '12', '24' ), true ) ) {
			$data['hour_format'] = '12';
		}

		if ( isset( $data['payment_gateway'] ) && 'zarinpal' !== $data['payment_gateway'] ) {
			$data['payment_gateway'] = 'zarinpal';
		}
		if ( isset( $data['zarinpal_merchant_id'] ) ) {
			$data['zarinpal_merchant_id'] = preg_replace( '/[^A-Za-z0-9\-]/', '', (string) $data['zarinpal_merchant_id'] ) ?? '';
		}

		if ( isset( $data['account_color_scheme'] ) && ! in_array( $data['account_color_scheme'], array( 'admin', 'form' ), true ) ) {
			$data['account_color_scheme'] = 'admin';
		}

		if ( isset( $data['customer_login_mode'] ) && ! isset( \MRBooking\Auth\Customer_Auth::modes()[ $data['customer_login_mode'] ] ) ) {
			$data['customer_login_mode'] = 'off';
		}

		// Premium gate: hide_branding only when license is valid.
		$effective_key = isset( $data['premium_key'] )
			? (string) $data['premium_key']
			: (string) Settings::get_value( 'premium_key', '' );
		if ( ! \MRBooking\Premium\License::is_valid_key( $effective_key ) ) {
			$data['hide_branding'] = 0;
		}

		Settings::update( $data );

		if ( 'sms' === $tab ) {
			SMS_Manager::clear_account_credit_cache();
			SMS_Manager::refresh_account_credit( $data );
		}

		if ( 'notifications' === $tab ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-notifications&saved=1' ) );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-settings&tab=' . $tab . '&saved=1' ) );
		}
		exit;
	}

	public static function apply_theme(): void {
		Helpers::require_cap( \MRBooking\Roles\Capabilities::SETTINGS );
		check_admin_referer( 'mr_booking_apply_theme' );

		$theme = isset( $_GET['theme'] ) ? sanitize_key( wp_unslash( $_GET['theme'] ) ) : '';
		if ( ! in_array( $theme, Color_Presets::theme_ids(), true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-settings&tab=appearance' ) );
			exit;
		}

		Settings::update( Color_Presets::get( $theme ) );

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-settings&tab=appearance&theme_applied=' . $theme ) );
		exit;
	}
}
