<?php
/**
 * Elementor booking form widget.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use MRBooking\Frontend\Shortcode;
use MRBooking\Services\Service_Repository;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Booking_Form extends Widget_Base {

	public function get_name(): string {
		return 'mr-booking-form';
	}

	public function get_title(): string {
		if ( \MRBooking\Premium\License::hide_branding() ) {
			return __( 'فرم رزرو', 'mr-booking' );
		}
		return __( 'فرم رزرو MR Booking', 'mr-booking' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	/**
	 * @return list<string>
	 */
	public function get_categories(): array {
		return array( 'mr-booking', 'general' );
	}

	/**
	 * @return list<string>
	 */
	public function get_keywords(): array {
		return array( 'booking', 'رزرو', 'نوبت', 'mr booking', 'appointment', 'رنگ', 'فونت' );
	}

	/**
	 * @return list<string>
	 */
	public function get_style_depends(): array {
		return array( 'mr-booking-frontend', 'mr-booking-fonts' );
	}

	/**
	 * @return list<string>
	 */
	public function get_script_depends(): array {
		return array( 'mr-booking-birth-picker', 'mr-booking-frontend' );
	}

	protected function register_controls(): void {
		$this->register_content_controls();
		$this->register_style_colors();
		$this->register_style_typography();
		$this->register_style_buttons();
		$this->register_style_inputs();
		$this->register_style_card();
	}

	private function register_content_controls(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'تنظیمات فرم', 'mr-booking' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'service_id',
			array(
				'label'   => __( 'پیش‌انتخاب خدمت', 'mr-booking' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->service_options(),
				'default' => '0',
			)
		);

		$this->add_control(
			'staff_id',
			array(
				'label'   => __( 'پیش‌انتخاب پرسنل', 'mr-booking' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $this->staff_options(),
				'default' => '0',
			)
		);

		$help = \MRBooking\Premium\License::hide_branding()
			? __( 'رنگ‌ها، فونت و دکمه‌ها را از تب Style همین ویجت تنظیم کنید. خدمات و قوانین رزرو از منوی رزرو در پیشخوان مدیریت می‌شوند.', 'mr-booking' )
			: __( 'رنگ‌ها، فونت و دکمه‌ها را از تب Style همین ویجت تنظیم کنید. خدمات و قوانین رزرو از منوی MR Booking در پیشخوان مدیریت می‌شوند.', 'mr-booking' );

		$this->add_control(
			'help',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => '<p style="margin:0;line-height:1.7">' . esc_html( $help ) . '</p>',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$this->end_controls_section();
	}

	private function register_style_colors(): void {
		$this->start_controls_section(
			'section_style_colors',
			array(
				'label' => __( 'رنگ‌ها', 'mr-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'color_primary',
			array(
				'label'     => __( 'رنگ اصلی / مراحل', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-primary: {{VALUE}}; --mrb-available: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_text',
			array(
				'label'     => __( 'رنگ متن عمومی', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-text: {{VALUE}}; --mrb-btn-ghost-text: {{VALUE}}; color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_title',
			array(
				'label'     => __( 'رنگ عنوان', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'         => '--mrb-title: {{VALUE}};',
					'{{WRAPPER}} .mrb__title'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .mrb__brand'  => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_bg',
			array(
				'label'     => __( 'پس‌زمینه بیرونی', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-bg: {{VALUE}}; background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_card',
			array(
				'label'     => __( 'پس‌زمینه کارت فرم', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'        => '--mrb-card: {{VALUE}};',
					'{{WRAPPER}} .mrb__shell' => 'background: {{VALUE}};',
					'{{WRAPPER}} .mrb__footer'=> 'background: linear-gradient(to top, {{VALUE}} 70%, transparent);',
				),
			)
		);

		$this->add_control(
			'color_border',
			array(
				'label'     => __( 'رنگ حاشیه', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-border: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'color_accent',
			array(
				'label'     => __( 'رنگ تاکیدی', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-accent: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_style_typography(): void {
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => __( 'فونت و تایپوگرافی', 'mr-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'body_typography',
				'label'    => __( 'فونت کلی فرم', 'mr-booking' ),
				'selector' => '{{WRAPPER}} .mrb, {{WRAPPER}} .mrb button, {{WRAPPER}} .mrb input, {{WRAPPER}} .mrb select, {{WRAPPER}} .mrb textarea',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => __( 'فونت عنوان', 'mr-booking' ),
				'selector' => '{{WRAPPER}} .mrb__title',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'label'    => __( 'فونت برچسب فیلدها', 'mr-booking' ),
				'selector' => '{{WRAPPER}} .mrb__fields label > span, {{WRAPPER}} .mrb__booking-for legend',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'steps_typography',
				'label'    => __( 'فونت مراحل', 'mr-booking' ),
				'selector' => '{{WRAPPER}} .mrb__step',
			)
		);

		$this->end_controls_section();
	}

	private function register_style_buttons(): void {
		$this->start_controls_section(
			'section_style_buttons',
			array(
				'label' => __( 'دکمه‌ها', 'mr-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'btn_heading_primary',
			array(
				'label' => __( 'دکمه اصلی (ادامه / ثبت)', 'mr-booking' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'btn_primary_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'                 => '--mrb-button: {{VALUE}}; --mrb-button-hover: {{VALUE}};',
					'{{WRAPPER}} .mrb__btn--primary'   => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_primary_hover',
			array(
				'label'     => __( 'پس‌زمینه هاور', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'                       => '--mrb-button-hover: {{VALUE}};',
					'{{WRAPPER}} .mrb__btn--primary:hover'   => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_primary_text',
			array(
				'label'     => __( 'رنگ متن', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'               => '--mrb-btn-text: {{VALUE}};',
					'{{WRAPPER}} .mrb__btn--primary' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_primary_typography',
				'label'    => __( 'فونت دکمه اصلی', 'mr-booking' ),
				'selector' => '{{WRAPPER}} .mrb__btn--primary',
			)
		);

		$this->add_control(
			'btn_heading_ghost',
			array(
				'label'     => __( 'دکمه قبلی', 'mr-booking' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'btn_ghost_bg',
			array(
				'label'     => __( 'پس‌زمینه', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'             => '--mrb-btn-ghost-bg: {{VALUE}};',
					'{{WRAPPER}} .mrb__btn--ghost' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_ghost_text',
			array(
				'label'     => __( 'رنگ متن', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb'             => '--mrb-btn-ghost-text: {{VALUE}};',
					'{{WRAPPER}} .mrb__btn--ghost' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_radius',
			array(
				'label'      => __( 'گردی گوشه دکمه‌ها', 'mr-booking' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mrb__btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
				'separator'  => 'before',
			)
		);

		$this->end_controls_section();
	}

	private function register_style_inputs(): void {
		$this->start_controls_section(
			'section_style_inputs',
			array(
				'label' => __( 'فیلدهای ورودی', 'mr-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'input_bg',
			array(
				'label'     => __( 'پس‌زمینه فیلد', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-input-bg: {{VALUE}};',
					'{{WRAPPER}} .mrb__fields input, {{WRAPPER}} .mrb__fields select, {{WRAPPER}} .mrb__fields textarea' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_text',
			array(
				'label'     => __( 'رنگ متن فیلد', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-input-text: {{VALUE}};',
					'{{WRAPPER}} .mrb__fields input, {{WRAPPER}} .mrb__fields select, {{WRAPPER}} .mrb__fields textarea, {{WRAPPER}} .mrb__staff-select, {{WRAPPER}} .mrb__birth-trigger' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'رنگ برچسب', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-label: {{VALUE}}; --mrb-title: {{VALUE}};',
					'{{WRAPPER}} .mrb__fields label, {{WRAPPER}} .mrb__title, {{WRAPPER}} .mrb__section-title' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'service_text_color',
			array(
				'label'     => __( 'رنگ متن خدمات', 'mr-booking' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mrb' => '--mrb-service-text: {{VALUE}};',
					'{{WRAPPER}} .mrb__service-main strong' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'input_radius',
			array(
				'label'      => __( 'گردی گوشه فیلدها', 'mr-booking' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 30,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mrb__fields input, {{WRAPPER}} .mrb__fields select, {{WRAPPER}} .mrb__fields textarea' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function register_style_card(): void {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => __( 'کارت و فاصله‌ها', 'mr-booking' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_responsive_control(
			'card_radius',
			array(
				'label'      => __( 'گردی کارت', 'mr-booking' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mrb'        => 'border-radius: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .mrb__shell' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'فاصله داخلی', 'mr-booking' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .mrb' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'max_width',
			array(
				'label'      => __( 'حداکثر عرض فرم', 'mr-booking' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 320,
						'max' => 1000,
					),
					'%'  => array(
						'min' => 50,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .mrb__shell' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * @return array<string, string>
	 */
	private function service_options(): array {
		$options = array( '0' => __( '— همه خدمات —', 'mr-booking' ) );
		foreach ( Service_Repository::all( 'active' ) as $svc ) {
			$options[ (string) $svc->id ] = (string) $svc->name;
		}
		return $options;
	}

	/**
	 * @return array<string, string>
	 */
	private function staff_options(): array {
		$options = array( '0' => __( '— بدون پیش‌انتخاب —', 'mr-booking' ) );
		foreach ( Staff_Repository::all( 'active' ) as $member ) {
			$options[ (string) $member->id ] = Staff_Repository::display_name( $member );
		}
		return $options;
	}

	protected function render(): void {
		\MRBooking\Frontend\Assets::enqueue();

		$settings = $this->get_settings_for_display();

		$atts = array(
			'service' => ! empty( $settings['service_id'] ) ? (string) $settings['service_id'] : '',
			'staff'   => ! empty( $settings['staff_id'] ) ? (string) $settings['staff_id'] : '',
			'theme'   => 'default',
		);

		$this->add_render_attribute( 'wrapper', 'class', 'mrb-elementor-widget' );
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode returns escaped markup.
			echo ( new Shortcode() )->render( $atts );
			?>
		</div>
		<?php
	}

	protected function content_template(): void {
		$title = \MRBooking\Premium\License::hide_branding()
			? __( 'فرم رزرو', 'mr-booking' )
			: __( 'فرم رزرو MR Booking', 'mr-booking' );
		?>
		<div class="elementor-alert elementor-alert-info" style="text-align:center;padding:24px;direction:rtl">
			<strong><?php echo esc_html( $title ); ?></strong>
			<p style="margin:8px 0 0"><?php echo esc_html__( 'از تب Style رنگ، فونت و دکمه‌ها را تنظیم کنید. پیش‌نمایش کامل در فرانت‌اند دیده می‌شود.', 'mr-booking' ); ?></p>
		</div>
		<?php
	}
}
