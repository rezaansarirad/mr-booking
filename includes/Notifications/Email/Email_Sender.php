<?php
/**
 * Email sender.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\Email;

use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Email_Sender {

	/**
	 * @param array<string, string> $headers Extra headers.
	 */
	public static function send( string $to, string $subject, string $body, array $headers = array() ): bool {
		$settings = Settings::get();
		if ( empty( $settings['email_enabled'] ) ) {
			return false;
		}

		if ( ! is_email( $to ) ) {
			return false;
		}

		$from_name  = (string) $settings['email_from_name'];
		$from_email = (string) $settings['email_from_email'];
		$reply      = (string) $settings['email_reply_to'];

		$default_headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		if ( $reply ) {
			$default_headers[] = 'Reply-To: ' . $reply;
		}

		$html = self::wrap_html( $subject, $body, $settings );

		$sent = wp_mail( $to, $subject, $html, array_merge( $default_headers, $headers ) );

		do_action( 'mr_booking_email_sent', $to, $subject, $sent );

		return (bool) $sent;
	}

	/**
	 * @param array<string, mixed> $settings
	 */
	private static function wrap_html( string $subject, string $body, array $settings ): string {
		$primary = esc_attr( (string) $settings['color_primary'] );
		$name    = esc_html( (string) $settings['business_name'] );

		return '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="UTF-8"><title>'
			. esc_html( $subject )
			. '</title></head><body style="margin:0;padding:0;background:#f4f7f6;font-family:Tahoma,Arial,sans-serif;">'
			. '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7f6;padding:24px 12px;">'
			. '<tr><td align="center">'
			. '<table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5eeec;">'
			. '<tr><td style="background:' . $primary . ';color:#fff;padding:18px 24px;font-size:18px;font-weight:bold;">' . $name . '</td></tr>'
			. '<tr><td style="padding:24px;color:#134E4A;font-size:14px;line-height:1.8;">' . $body . '</td></tr>'
			. '</table></td></tr></table></body></html>';
	}
}
