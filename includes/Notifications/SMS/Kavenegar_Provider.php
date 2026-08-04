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

	public function supports_account_credit(): bool {
		return true;
	}

	public function test_hint(): string {
		return __( 'کلید API کاوه‌نگار را وارد کنید و «تست اتصال» را بزنید. در صورت موفقیت، اعتبار حساب (ریال) در همین بخش و نوار بالای پیشخوان نمایش داده می‌شود.', 'mr-booking' );
	}

	public function send( string $to, string $message, array $config = array() ): array {
		$api_key = trim( (string) ( $config['sms_api_key'] ?? '' ) );
		$sender  = trim( (string) ( $config['sms_sender'] ?? '' ) );

		if ( ! $api_key ) {
			return array( 'ok' => false, 'error' => __( 'کلید API کاوه‌نگار وارد نشده است.', 'mr-booking' ) );
		}

		$body = array(
			'receptor' => $to,
			'message'  => $message,
		);
		if ( $sender ) {
			$body['sender'] = $sender;
		}

		$response = wp_remote_post(
			$this->api_url( $api_key, 'sms/send.json' ),
			array(
				'timeout' => 20,
				'body'    => $body,
			)
		);

		return $this->parse_response( $response );
	}

	public function test_connection( array $config = array() ): array {
		$api_key = trim( (string) ( $config['sms_api_key'] ?? '' ) );
		if ( ! $api_key ) {
			return array( 'ok' => false, 'error' => __( 'کلید API وارد نشده است.', 'mr-booking' ) );
		}

		$response = wp_remote_get(
			$this->api_url( $api_key, 'account/info.json' ),
			array( 'timeout' => 20 )
		);
		$result   = $this->parse_response( $response );

		if ( empty( $result['ok'] ) ) {
			return $result;
		}

		$entries = is_array( $result['entries'] ?? null ) ? $result['entries'] : array();
		$credit  = isset( $entries['remaincredit'] ) ? (float) $entries['remaincredit'] : null;

		$result['account'] = array(
			'credit'     => $credit,
			'expiredate' => (string) ( $entries['expiredate'] ?? '' ),
			'type'       => (string) ( $entries['type'] ?? '' ),
		);

		if ( null !== $credit ) {
			$result['message'] = sprintf(
				/* translators: %s: account credit in Rials */
				__( 'اتصال به کاوه‌نگار برقرار است. اعتبار حساب: %s ریال', 'mr-booking' ),
				number_format_i18n( $credit )
			);
		} else {
			$result['message'] = __( 'اتصال به کاوه‌نگار برقرار است.', 'mr-booking' );
		}

		unset( $result['entries'] );

		return $result;
	}

	private function api_url( string $api_key, string $endpoint ): string {
		return 'https://api.kavenegar.com/v1/' . $api_key . '/' . ltrim( $endpoint, '/' );
	}

	/**
	 * @param array<string, mixed>|\WP_Error $response
	 * @return array{ok:bool,message?:string,error?:string,entries?:array<string,mixed>,response?:string}
	 */
	private function parse_response( $response ): array {
		if ( is_wp_error( $response ) ) {
			return array( 'ok' => false, 'error' => $response->get_error_message() );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || ! isset( $data['return']['status'] ) ) {
			return array(
				'ok'       => false,
				'error'    => ( $code >= 200 && $code < 300 )
					? __( 'پاسخ نامعتبر از کاوه‌نگار دریافت شد.', 'mr-booking' )
					: sprintf(
						/* translators: %d: HTTP status code */
						__( 'خطای HTTP %d از کاوه‌نگار', 'mr-booking' ),
						$code
					),
				'response' => $raw,
			);
		}

		$status  = (int) $data['return']['status'];
		$message = trim( (string) ( $data['return']['message'] ?? '' ) );

		if ( 200 === $status ) {
			return array(
				'ok'       => true,
				'message'  => $message,
				'entries'  => is_array( $data['entries'] ?? null ) ? $data['entries'] : array(),
				'response' => $raw,
			);
		}

		return array(
			'ok'       => false,
			'error'    => $message ?: sprintf(
				/* translators: %d: Kavenegar API status code */
				__( 'خطای کاوه‌نگار (کد %d)', 'mr-booking' ),
				$status
			),
			'response' => $raw,
		);
	}
}
