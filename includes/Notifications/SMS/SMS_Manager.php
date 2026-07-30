<?php
/**
 * SMS manager / factory.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\SMS;

use MRBooking\Helpers;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class SMS_Manager {

	/**
	 * @return array<string, Provider_Interface>
	 */
	public static function providers(): array {
		$providers = array(
			'kavenegar'   => new Kavenegar_Provider(),
			'melipayamak' => new Melipayamak_Provider(),
			'smsir'       => new Smsir_Provider(),
		);

		/**
		 * Filter SMS providers.
		 *
		 * @param array<string, Provider_Interface> $providers Providers.
		 */
		return apply_filters( 'mr_booking_sms_providers', $providers );
	}

	/**
	 * @return array{ok:bool,response?:string,error?:string}
	 */
	public static function send( string $to, string $message ): array {
		$settings = Settings::get();
		if ( empty( $settings['sms_enabled'] ) ) {
			return array( 'ok' => false, 'error' => 'SMS disabled' );
		}

		$to = Helpers::sanitize_mobile( $to );
		if ( ! Helpers::is_valid_mobile( $to ) ) {
			return array( 'ok' => false, 'error' => 'Invalid mobile' );
		}

		$slug      = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
		$providers = self::providers();
		$provider  = $providers[ $slug ] ?? null;

		if ( ! $provider ) {
			return array( 'ok' => false, 'error' => 'Unknown provider' );
		}

		$result = $provider->send( $to, $message, $settings );

		/**
		 * Fires after SMS attempt.
		 */
		do_action( 'mr_booking_sms_sent', $to, $message, $result );

		return $result;
	}
}
