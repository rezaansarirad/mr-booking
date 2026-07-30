<?php
/**
 * Elementor integration bootstrap.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Elementor;

use MRBooking\Frontend\Assets as Frontend_Assets;

defined( 'ABSPATH' ) || exit;

final class Bootstrap {

	public function hooks(): void {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		add_action( 'elementor/editor/before_enqueue_scripts', array( Frontend_Assets::class, 'register' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( Frontend_Assets::class, 'enqueue' ) );
		add_action( 'elementor/preview/enqueue_styles', array( Frontend_Assets::class, 'enqueue' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( Frontend_Assets::class, 'enqueue' ) );
		add_action( 'elementor/frontend/after_enqueue_scripts', array( Frontend_Assets::class, 'enqueue' ) );
	}

	/**
	 * @param \Elementor\Elements_Manager $elements_manager Manager.
	 */
	public function register_category( $elements_manager ): void {
		$title = \MRBooking\Premium\License::hide_branding()
			? __( 'رزرو', 'mr-booking' )
			: __( 'MR Booking', 'mr-booking' );

		$elements_manager->add_category(
			'mr-booking',
			array(
				'title' => $title,
				'icon'  => 'fa fa-calendar-check',
			)
		);
	}

	/**
	 * @param \Elementor\Widgets_Manager $widgets_manager Manager.
	 */
	public function register_widgets( $widgets_manager ): void {
		$widgets_manager->register( new Widgets\Booking_Form() );
	}
}
