<?php
/**
 * Kavenegar SMS provider.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\SMS;

defined( 'ABSPATH' ) || exit;

final class Kavenegar_Provider implements Provider_Interface {

	public function slug(): string {
		return 'kavenegar';
	}

	public function label(): string {
		return 'کاوه‌نگار';
	}

	public function send( string $to, string $message, array $config = array() ): array {
		$api_key = (string) ( $config['sms_api_key'] ?? '' );
		$sender  = (string) ( $config['sms_sender'] ?? '' );

		if ( ! $api_key ) {
			return array( 'ok' => false, 'error' => 'Missing API key' );
		}

		$url  = 'https://api.kavenegar.com/v1/' . rawurlencode( $api_key ) . '/sms/send.json';
		$body = array(
			'receptor' => $to,
			'message'  => $message,
		);
		if ( $sender ) {
			$body['sender'] = $sender;
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'body'    => $body,
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
			'error'    => ( $code >= 200 && $code < 300 ) ? null : 'HTTP ' . $code,
		);
	}
}
