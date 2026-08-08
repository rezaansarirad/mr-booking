<?php
/**
 * Trim WordPress admin for booking-only staff.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin;

use MRBooking\Helpers;
use MRBooking\Roles\Capabilities;

defined( 'ABSPATH' ) || exit;

final class Restricted_Admin {

	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'trim_menus' ), 9999 );
		add_action( 'admin_init', array( $this, 'block_foreign_pages' ) );
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 20, 3 );
		add_action( 'admin_bar_menu', array( $this, 'trim_admin_bar' ), 9999 );
		add_filter( 'show_admin_bar', array( $this, 'hide_front_admin_bar' ) );
		add_action( 'admin_head', array( $this, 'styles' ) );
	}

	public function trim_menus(): void {
		if ( ! Capabilities::is_booking_only_user() ) {
			return;
		}

		global $menu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		foreach ( $menu as $index => $item ) {
			$slug = (string) ( $item[2] ?? '' );
			if ( 'mr-booking' === $slug ) {
				continue;
			}
			remove_menu_page( $slug );
			unset( $menu[ $index ] );
		}
	}

	public function block_foreign_pages(): void {
		if ( ! Capabilities::is_booking_only_user() ) {
			return;
		}

		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore

		if ( str_starts_with( $page, 'mr-booking' ) ) {
			if ( 'mr-booking' === $page && ! Capabilities::user_can_page( $page ) ) {
				wp_safe_redirect( Capabilities::landing_url() );
				exit;
			}

			if ( Capabilities::user_can_page( $page ) ) {
				return;
			}

			wp_safe_redirect( Capabilities::landing_url() );
			exit;
		}

		$script = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
		$allowed_scripts = array( 'admin-ajax.php', 'admin-post.php' );

		if ( in_array( $script, $allowed_scripts, true ) ) {
			return;
		}

		wp_safe_redirect( Capabilities::landing_url() );
		exit;
	}

	/**
	 * @param string           $redirect
	 * @param string           $requested_redirect_to
	 * @param \WP_User|\WP_Error $user
	 */
	public function login_redirect( $redirect, $requested_redirect_to, $user ): string {
		if ( is_wp_error( $user ) || ! ( $user instanceof \WP_User ) ) {
			return $redirect;
		}

		if ( Capabilities::is_booking_only_user( $user ) ) {
			return Capabilities::landing_url( $user );
		}

		return $redirect;
	}

	public function trim_admin_bar( \WP_Admin_Bar $wp_admin_bar ): void {
		if ( ! Capabilities::is_booking_only_user() ) {
			return;
		}

		$remove = array(
			'wp-logo',
			'about',
			'site-name',
			'view-site',
			'updates',
			'comments',
			'new-content',
			'customize',
		);

		foreach ( $remove as $id ) {
			$wp_admin_bar->remove_node( $id );
		}
	}

	/**
	 * @param bool $show
	 */
	public function hide_front_admin_bar( $show ): bool {
		if ( is_admin() ) {
			return (bool) $show;
		}

		if ( Capabilities::is_booking_only_user() ) {
			return false;
		}

		return (bool) $show;
	}

	public function styles(): void {
		if ( ! Capabilities::is_booking_only_user() ) {
			return;
		}

		echo '<style id="mr-booking-restricted-admin">'
			. '#wpadminbar #wp-admin-bar-site-name,#wpadminbar #wp-admin-bar-wp-logo{display:none!important;}'
			. '</style>';
	}
}
