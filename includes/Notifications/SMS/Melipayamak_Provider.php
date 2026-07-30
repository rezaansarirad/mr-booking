<?php
/**
 * Melipayamak SMS provider.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\SMS;

defined( 'ABSPATH' ) || exit;

final class Melipayamak_Provider implements Provider_Interface {

	public function slug(): string {
		return 'melipayamak';
	}

	public function label(): string {
		return 'ملی‌پیامک';
	}

	public function send( string $to, string $message, array $config = array() ): array {
		$username = (string) ( $config['sms_username'] ?? '' );
		$password = (string) ( $config['sms_password'] ?? '' );
		$sender   = (string) ( $config['sms_sender'] ?? '' );

		if ( ! $username || ! $password ) {
			return array( 'ok' => false, 'error' => 'Missing credentials' );
		}

		$url = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode(
					array(
						'username' => $username,
						'password' => $password,
						'to'       => $to,
						'from'     => $sender,
						'text'     => $message,
						'isflash'  => false,
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
}
