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
}
