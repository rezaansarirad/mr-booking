<?php
/**
 * Form color theme presets (dark / light) built from brand harmony.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Settings;

defined( 'ABSPATH' ) || exit;

final class Color_Presets {

	public const BRAND_PRIMARY   = '#D4AF37';

	public const BRAND_SECONDARY = '#F0D875';

	public const BRAND_ACCENT    = '#142A38';

	/**
	 * The admin panel's green palette (assets/css/admin.css) as frontend variables,
	 * used by the customer dashboard when it should match the plugin panel.
	 *
	 * @return array<string, string>
	 */
	public static function admin_palette(): array {
		return array(
			'--mrb-primary'                     => '#0f766e',
			'--mrb-secondary'                   => '#134e4a',
			'--mrb-accent'                      => '#0d9488',
			'--mrb-button'                      => '#0f766e',
			'--mrb-button-hover'                => '#0b5f59',
			'--mrb-btn-text'                    => '#ffffff',
			'--mrb-btn-loading'                 => '#ffffff',
			'--mrb-btn-ghost-bg'                => '#eef5f3',
			'--mrb-btn-ghost-hover'             => '#dfece8',
			'--mrb-btn-ghost-text'              => '#134e4a',
			'--mrb-text'                        => '#134e4a',
			'--mrb-title'                       => '#134e4a',
			'--mrb-label'                       => '#3d5c57',
			'--mrb-input-text'                  => '#134e4a',
			'--mrb-input-placeholder'           => '#94a3b8',
			'--mrb-input-bg'                    => '#fcfefd',
			'--mrb-bg'                          => '#f4f8f7',
			'--mrb-card'                        => '#ffffff',
			'--mrb-border'                      => '#d7e8e4',
			'--mrb-unavailable'                 => '#6b8580',
			'--mrb-available'                   => '#0f766e',
			'--mrb-full'                        => '#b45309',
			'--mrb-holiday'                     => '#92400e',
			'--mrb-holiday-bg'                  => '#fef3c7',
			'--mrb-success'                     => '#059669',
			'--mrb-error'                       => '#dc2626',
			'--mrb-warning'                     => '#f59e0b',
			'--mrb-radio-active'                => '#0f766e',
			'--mrb-service-text'                => '#134e4a',
			'--mrb-service-desc'                => '#64748b',
			'--mrb-service-card'                => '#ffffff',
			'--mrb-service-card-selected'       => '#e8f5f3',
			'--mrb-service-card-border'         => '#d1e7e3',
			'--mrb-service-duration-bg'         => '#e8f5f1',
			'--mrb-service-duration-text'       => '#0f766e',
			'--mrb-service-duration-bg-selected'=> '#d1ede8',
			'--mrb-service-price'               => '#0f766e',
			'--mrb-service-check-border'        => '#d1e7e3',
			'--mrb-service-check-active'        => '#0f766e',
		);
	}

	/**
	 * @return list<string>
	 */
	public static function theme_ids(): array {
		return array( 'dark', 'light' );
	}

	/**
	 * Setting keys replaced when applying a theme preset.
	 *
	 * @return list<string>
	 */
	public static function theme_keys(): array {
		return array(
			'form_theme',
			'bg_gradient_enabled',
			'bg_gradient_primary_mix',
			'bg_gradient_accent_mix',
			'color_primary',
			'color_secondary',
			'color_accent',
			'color_button',
			'color_button_hover',
			'color_btn_text',
			'color_btn_loading',
			'color_btn_ghost_bg',
			'color_btn_ghost_hover',
			'color_btn_ghost_text',
			'color_text',
			'color_label',
			'color_input_text',
			'color_input_placeholder',
			'color_radio_active',
			'color_service_text',
			'color_service_desc',
			'color_service_card',
			'color_service_card_selected',
			'color_service_card_border',
			'color_service_duration_bg',
			'color_service_duration_text',
			'color_service_duration_bg_selected',
			'color_service_price',
			'color_service_check_border',
			'color_service_check_active',
			'color_background',
			'color_card',
			'color_border',
			'color_holiday',
			'color_holiday_bg',
			'color_available',
			'color_unavailable',
			'color_fully_booked',
			'color_success',
			'color_error',
			'color_warning',
			'color_bg_gradient_primary',
			'color_bg_gradient_accent',
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function all(): array {
		return array(
			'dark'  => self::get( 'dark' ),
			'light' => self::get( 'light' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get( string $theme ): array {
		$presets = array(
			'dark'  => self::dark(),
			'light' => self::light(),
		);

		return $presets[ $theme ] ?? $presets['dark'];
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function brand_base(): array {
		return array(
			'color_primary'   => self::BRAND_PRIMARY,
			'color_secondary' => self::BRAND_SECONDARY,
			'color_accent'    => self::BRAND_ACCENT,
		);
	}

	/**
	 * Dark theme — black surfaces, gold accents.
	 *
	 * @return array<string, mixed>
	 */
	private static function dark(): array {
		return array_merge(
			self::brand_base(),
			array(
				'form_theme'                => 'dark',
				'bg_gradient_enabled'       => 1,
				'bg_gradient_primary_mix'   => 10,
				'bg_gradient_accent_mix'    => 18,
				'color_bg_gradient_primary' => self::BRAND_PRIMARY,
				'color_bg_gradient_accent'  => self::BRAND_ACCENT,
				'color_background'          => '#0A0A0A',
				'color_card'                => '#111111',
				'color_border'              => '#2A3540',
				'color_button'              => self::BRAND_PRIMARY,
				'color_button_hover'        => '#E8C547',
				'color_btn_text'            => self::BRAND_ACCENT,
				'color_btn_loading'         => self::BRAND_ACCENT,
				'color_btn_ghost_bg'        => '#1A2330',
				'color_btn_ghost_hover'     => '#243040',
				'color_btn_ghost_text'      => self::BRAND_SECONDARY,
				'color_text'                => '#C8C4B8',
				'color_label'               => self::BRAND_SECONDARY,
				'color_input_text'          => '#F5F3EE',
				'color_input_placeholder'   => '#6B7280',
				'color_radio_active'        => self::BRAND_PRIMARY,
				'color_service_text'        => self::BRAND_SECONDARY,
				'color_service_desc'        => '#9CA3AF',
				'color_service_card'        => '#141414',
				'color_service_card_selected' => '#1A2330',
				'color_service_card_border' => '#3D3520',
				'color_service_duration_bg' => '#2A2418',
				'color_service_duration_text' => self::BRAND_PRIMARY,
				'color_service_duration_bg_selected' => '#3D3520',
				'color_service_price'       => self::BRAND_SECONDARY,
				'color_service_check_border'=> '#4A4435',
				'color_service_check_active'=> self::BRAND_PRIMARY,
				'color_available'           => self::BRAND_PRIMARY,
				'color_unavailable'         => '#4B5563',
				'color_fully_booked'        => '#C97B2B',
				'color_holiday'             => self::BRAND_SECONDARY,
				'color_holiday_bg'          => '#2A2418',
				'color_success'             => '#6FBF73',
				'color_error'               => '#C97B2B',
				'color_warning'             => self::BRAND_PRIMARY,
			)
		);
	}

	/**
	 * Light theme — warm cream surfaces, navy text.
	 *
	 * @return array<string, mixed>
	 */
	private static function light(): array {
		return array_merge(
			self::brand_base(),
			array(
				'form_theme'                => 'light',
				'bg_gradient_enabled'       => 1,
				'bg_gradient_primary_mix'   => 8,
				'bg_gradient_accent_mix'    => 6,
				'color_bg_gradient_primary' => self::BRAND_PRIMARY,
				'color_bg_gradient_accent'  => self::BRAND_ACCENT,
				'color_background'          => '#F7F5F0',
				'color_card'                => '#FFFFFF',
				'color_border'              => '#E5DFD0',
				'color_button'              => self::BRAND_PRIMARY,
				'color_button_hover'        => '#C9A030',
				'color_btn_text'            => self::BRAND_ACCENT,
				'color_btn_loading'         => self::BRAND_ACCENT,
				'color_btn_ghost_bg'        => '#F5F0E6',
				'color_btn_ghost_hover'     => '#EBE4D6',
				'color_btn_ghost_text'      => self::BRAND_ACCENT,
				'color_text'                => self::BRAND_ACCENT,
				'color_label'               => self::BRAND_ACCENT,
				'color_input_text'          => self::BRAND_ACCENT,
				'color_input_placeholder'   => '#94A3B8',
				'color_radio_active'        => self::BRAND_PRIMARY,
				'color_service_text'        => self::BRAND_ACCENT,
				'color_service_desc'        => '#64748B',
				'color_service_card'        => '#FFFFFF',
				'color_service_card_selected' => '#FFF8E7',
				'color_service_card_border' => '#E5DFD0',
				'color_service_duration_bg' => '#FFF8E7',
				'color_service_duration_text' => '#B8941F',
				'color_service_duration_bg_selected' => '#F0E6C8',
				'color_service_price'       => self::BRAND_PRIMARY,
				'color_service_check_border'=> '#D1D5DB',
				'color_service_check_active'=> self::BRAND_PRIMARY,
				'color_available'           => '#B8941F',
				'color_unavailable'         => '#94A3B8',
				'color_fully_booked'        => '#C97B2B',
				'color_holiday'             => '#B8941F',
				'color_holiday_bg'          => '#FFF8E7',
				'color_success'             => '#5A9E5C',
				'color_error'               => '#C97B2B',
				'color_warning'             => self::BRAND_PRIMARY,
			)
		);
	}
}
