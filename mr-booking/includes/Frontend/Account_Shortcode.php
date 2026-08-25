<?php
/**
 * [mr_booking_account] — customer dashboard (bookings + profile) with OTP login.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Frontend;

use MRBooking\Auth\Customer_Auth;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Account_Shortcode {

	public function hooks(): void {
		add_shortcode( 'mr_booking_account', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public function render( $atts = array() ): string {
		$settings = Settings::get();

		if ( ! Customer_Auth::is_enabled() ) {
			if ( current_user_can( 'manage_options' ) ) {
				return '<div class="mrb mrb--account"><div class="mrb__shell"><p class="mrb__hint" style="padding:16px">'
					. esc_html__( 'ورود مشتری غیرفعال است. آن را از تنظیمات → حساب کاربری فعال کنید. (این پیام فقط برای مدیر نمایش داده می‌شود.)', 'mr-booking' )
					. '</p></div></div>';
			}
			return '';
		}

		Assets::enqueue_account();

		$customer  = Customer_Auth::current_customer();
		$logged_in = null !== $customer;

		ob_start();
		include MR_BOOKING_PATH . 'templates/frontend/account.php';
		return (string) ob_get_clean();
	}
}
