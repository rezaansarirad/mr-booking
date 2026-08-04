<?php
/**
 * SMS.ir provider.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\SMS;

defined( 'ABSPATH' ) || exit;

final class Smsir_Provider implements Provider_Interface {

	public function slug(): string {
		return 'smsir';
	}

	public function label(): string {
		return 'SMS.ir';
	}

	public function supports_account_credit(): bool {
		return false;
	}

	public function test_hint(): string {
		return __( 'کلید API و شماره خط SMS.ir را وارد کنید. تست خودکار اتصال برای این سرویس‌دهنده هنوز فعال نیست — پس از ذخیره، یک رزرو آزمایشی ثبت کنید.', 'mr-booking' );
	}

	public function send( string $to, string $message, array $config = array() ): array {
		$api_key = (string) ( $config['sms_api_key'] ?? '' );
		$sender  = (string) ( $config['sms_sender'] ?? '' );

		if ( ! $api_key ) {
			return array( 'ok' => false, 'error' => 'Missing API key' );
		}

		$url = ! empty( $config['sms_api_url'] )
			? (string) $config['sms_api_url']
			: 'https://api.sms.ir/v1/send/bulk';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'text/plain',
					'x-api-key'    => $api_key,
				),
				'body'    => wp_json_encode(
					array(
						'lineNumber'  => (int) $sender,
						'MessageText' => $message,
						'Mobiles'     => array( $to ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false, 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );

		return array(
			'ok'       => $code >= 200 && $code < 300,
			'response' => $raw,
		);
	}

	public function test_connection( array $config = array() ): array {
		return array(
			'ok'    => false,
			'error' => __( 'تست اتصال برای SMS.ir هنوز پشتیبانی نمی‌شود. پس از ذخیره تنظیمات، یک رزرو آزمایشی ثبت کنید.', 'mr-booking' ),
		);
	}
}
