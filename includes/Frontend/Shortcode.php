<?php
/**
 * Frontend shortcode.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Frontend;

use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Shortcode {

	public function hooks(): void {
		add_shortcode( 'mr_booking', array( $this, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public function render( $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'service' => '',
				'staff'   => '',
				'theme'   => 'default',
			),
			is_array( $atts ) ? $atts : array(),
			'mr_booking'
		);

		Assets::enqueue();

		ob_start();
		$preselect_service = absint( $atts['service'] );
		$preselect_staff   = absint( $atts['staff'] );
		$settings          = Settings::get();
		include MR_BOOKING_PATH . 'templates/frontend/booking-form.php';
		return (string) ob_get_clean();
	}
}
