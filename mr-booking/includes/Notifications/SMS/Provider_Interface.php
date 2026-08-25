<?php
/**
 * SMS provider interface.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications\SMS;

defined( 'ABSPATH' ) || exit;

interface Provider_Interface {

	/**
	 * Unique provider slug.
	 */
	public function slug(): string;

	/**
	 * Human label.
	 */
	public function label(): string;

	/**
	 * Send SMS.
	 *
	 * @param array<string, mixed> $config Provider config.
	 * @return array{ok:bool,response?:string,error?:string}
	 */
	public function send( string $to, string $message, array $config = array() ): array;

	/**
	 * Verify provider credentials / connection.
	 *
	 * @param array<string, mixed> $config Provider config.
	 * @return array{ok:bool,message?:string,error?:string,account?:array<string,mixed>,response?:string}
	 */
	public function test_connection( array $config = array() ): array;

	/**
	 * Whether account credit can be fetched for the admin bar / test UI.
	 */
	public function supports_account_credit(): bool;

	/**
	 * Provider-specific hint shown above the connection test button.
	 */
	public function test_hint(): string;
}
