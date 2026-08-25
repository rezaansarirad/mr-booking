<?php
/**
 * WordPress admin bar: SMS account credit indicator.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin;

use MRBooking\Helpers;
use MRBooking\Notifications\SMS\SMS_Manager;
use MRBooking\Roles\Capabilities;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Admin_Bar {

	public function hooks(): void {
		add_action( 'admin_bar_menu', array( $this, 'register_node' ), 999 );
		add_action( 'admin_head', array( $this, 'styles' ) );
		add_action( 'wp_head', array( $this, 'styles' ) );
	}

	public function register_node( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! is_user_logged_in() || ! Helpers::user_can( Capabilities::SETTINGS ) ) {
			return;
		}

		$settings = Settings::get();
		if ( empty( $settings['sms_enabled'] ) ) {
			return;
		}

		$credit_info = SMS_Manager::get_account_credit();
		if ( empty( $credit_info['ok'] ) ) {
			if ( 'unsupported' === ( $credit_info['reason'] ?? '' ) ) {
				return;
			}

			$title = '<span class="ab-icon dashicons dashicons-email-alt mrb-ab-sms-icon" aria-hidden="true"></span>'
				. '<span class="mrb-ab-credit mrb-ab-credit--muted">—</span>';
			$tooltip = __( 'اعتبار پیامک در دسترس نیست. تنظیمات SMS را بررسی کنید.', 'mr-booking' );
			if ( ! empty( $credit_info['error'] ) ) {
				$tooltip = sprintf(
					/* translators: %s: provider error */
					__( 'خطا در دریافت اعتبار پیامک: %s', 'mr-booking' ),
					$credit_info['error']
				);
			}
		} else {
			$credit_label = SMS_Manager::format_credit( isset( $credit_info['credit'] ) ? (float) $credit_info['credit'] : null );
			$provider     = (string) ( $credit_info['provider_label'] ?? '' );
			$title        = '<span class="ab-icon dashicons dashicons-email-alt mrb-ab-sms-icon" aria-hidden="true"></span>'
				. '<span class="mrb-ab-credit">' . esc_html( $credit_label ) . '</span>';
			$tooltip      = sprintf(
				/* translators: 1: provider name, 2: credit amount */
				__( 'اعتبار پیامک (%1$s): %2$s ریال', 'mr-booking' ),
				$provider,
				$credit_label
			);
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'mr-booking-sms-credit',
				'title' => $title,
				'href'  => admin_url( 'admin.php?page=mr-booking-settings&tab=sms' ),
				'meta'  => array(
					'title' => $tooltip,
					'class' => 'mrb-admin-bar-sms-credit',
				),
			)
		);
	}

	public function styles(): void {
		if ( ! is_user_logged_in() || ! is_admin_bar_showing() || ! Helpers::user_can( Capabilities::SETTINGS ) ) {
			return;
		}

		$settings = Settings::get();
		if ( empty( $settings['sms_enabled'] ) ) {
			return;
		}

		echo '<style id="mr-booking-admin-bar-sms">'
			. '#wpadminbar #wp-admin-bar-mr-booking-sms-credit > .ab-item { display:flex; align-items:center; gap:4px; }'
			. '#wpadminbar #wp-admin-bar-mr-booking-sms-credit .mrb-ab-sms-icon { font-family:dashicons; speak:never; font-size:18px; line-height:1; width:18px; height:18px; margin:0; opacity:.92; }'
			. '#wpadminbar #wp-admin-bar-mr-booking-sms-credit .mrb-ab-sms-icon:before { content:"\\f466"; top:0; }'
			. '#wpadminbar #wp-admin-bar-mr-booking-sms-credit .mrb-ab-credit { font-weight:700; font-size:13px; letter-spacing:.01em; }'
			. '#wpadminbar #wp-admin-bar-mr-booking-sms-credit .mrb-ab-credit--muted { opacity:.72; font-weight:600; }'
			. '#wpadminbar #wp-admin-bar-mr-booking-sms-credit:hover .ab-item { color:#72aee6; }'
			. '</style>';
	}
}
