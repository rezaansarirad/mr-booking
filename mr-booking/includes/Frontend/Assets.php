<?php
/**
 * Frontend assets.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Frontend;

use MRBooking\Settings\Color_Presets;
use MRBooking\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Assets {

	private static bool $registered = false;

	private static bool $enqueued = false;

	private static bool $localized = false;

	public function hooks(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'register' ), 5 );
	}

	public static function color_css_vars( string $selector = ':root' ): string {
		$settings = Settings::get();
		$label    = sanitize_hex_color( (string) ( $settings['color_label'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_text'] ?? '' ) )
			?: '#134e4a';
		$input    = sanitize_hex_color( (string) ( $settings['color_input_text'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_text'] ?? '' ) )
			?: '#134e4a';
		$service  = sanitize_hex_color( (string) ( $settings['color_service_text'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_label'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_text'] ?? '' ) )
			?: '#134e4a';
		$placeholder_color = sanitize_hex_color( (string) ( $settings['color_input_placeholder'] ?? '' ) )
			?: '#94a3b8';
		$service_card = sanitize_hex_color( (string) ( $settings['color_service_card'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_card'] ?? '' ) )
			?: '#ffffff';
		$service_card_selected = sanitize_hex_color( (string) ( $settings['color_service_card_selected'] ?? '' ) )
			?: '#e8f5f3';
		$radio_active = sanitize_hex_color( (string) ( $settings['color_radio_active'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_primary'] ?? '' ) )
			?: '#0f766e';
		$service_border = sanitize_hex_color( (string) ( $settings['color_service_card_border'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_border'] ?? '' ) )
			?: '#d1e7e3';
		$service_desc = sanitize_hex_color( (string) ( $settings['color_service_desc'] ?? '' ) ) ?: '#64748b';
		$duration_bg = sanitize_hex_color( (string) ( $settings['color_service_duration_bg'] ?? '' ) ) ?: '#e8f5f1';
		$duration_text = sanitize_hex_color( (string) ( $settings['color_service_duration_text'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_primary'] ?? '' ) )
			?: '#0f766e';
		$duration_bg_sel = sanitize_hex_color( (string) ( $settings['color_service_duration_bg_selected'] ?? '' ) ) ?: '#d1ede8';
		$service_price = sanitize_hex_color( (string) ( $settings['color_service_price'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_accent'] ?? '' ) )
			?: '#f59e0b';
		$check_border = sanitize_hex_color( (string) ( $settings['color_service_check_border'] ?? '' ) ) ?: $service_border;
		$check_active = sanitize_hex_color( (string) ( $settings['color_service_check_active'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_primary'] ?? '' ) )
			?: '#d4af37';
		$input_bg = sanitize_hex_color( (string) ( $settings['color_card'] ?? '' ) ) ?: '#111111';
		$btn_loading = sanitize_hex_color( (string) ( $settings['color_btn_loading'] ?? '' ) )
			?: sanitize_hex_color( (string) ( $settings['color_btn_text'] ?? '' ) )
			?: '#ffffff';

		return sprintf(
			'%40$s{--mrb-primary:%1$s;--mrb-secondary:%2$s;--mrb-accent:%3$s;--mrb-button:%4$s;--mrb-button-hover:%5$s;--mrb-btn-text:%22$s;--mrb-btn-loading:%39$s;--mrb-btn-ghost-bg:%23$s;--mrb-btn-ghost-hover:%24$s;--mrb-btn-ghost-text:%25$s;--mrb-text:%6$s;--mrb-label:%19$s;--mrb-title:%19$s;--mrb-input-text:%20$s;--mrb-input-placeholder:%26$s;--mrb-input-bg:%38$s;--mrb-service-text:%21$s;--mrb-service-desc:%30$s;--mrb-service-card:%27$s;--mrb-service-card-selected:%28$s;--mrb-service-card-border:%31$s;--mrb-service-duration-bg:%32$s;--mrb-service-duration-text:%33$s;--mrb-service-duration-bg-selected:%34$s;--mrb-service-price:%35$s;--mrb-service-check-border:%36$s;--mrb-service-check-active:%37$s;--mrb-radio-active:%29$s;--mrb-bg:%7$s;--mrb-card:%8$s;--mrb-border:%9$s;--mrb-holiday:%10$s;--mrb-holiday-bg:%11$s;--mrb-available:%12$s;--mrb-unavailable:%13$s;--mrb-full:%14$s;--mrb-success:%15$s;--mrb-error:%16$s;--mrb-warning:%17$s;--mrb-font:%18$s;}',
			$settings['color_primary'],
			$settings['color_secondary'],
			$settings['color_accent'],
			$settings['color_button'],
			$settings['color_button_hover'],
			$settings['color_text'],
			$settings['color_background'],
			$settings['color_card'],
			$settings['color_border'],
			$settings['color_holiday'],
			$settings['color_holiday_bg'],
			$settings['color_available'],
			$settings['color_unavailable'],
			$settings['color_fully_booked'],
			$settings['color_success'],
			$settings['color_error'],
			$settings['color_warning'],
			Settings::font_family_css( (string) $settings['font_family'] ),
			$label,
			$input,
			$service,
			sanitize_hex_color( (string) ( $settings['color_btn_text'] ?? '' ) ) ?: '#ffffff',
			sanitize_hex_color( (string) ( $settings['color_btn_ghost_bg'] ?? '' ) ) ?: '#eef5f3',
			sanitize_hex_color( (string) ( $settings['color_btn_ghost_hover'] ?? '' ) ) ?: '#dfece8',
			sanitize_hex_color( (string) ( $settings['color_btn_ghost_text'] ?? '' ) ) ?: $label,
			$placeholder_color,
			$service_card,
			$service_card_selected,
			$radio_active,
			$service_desc,
			$service_border,
			$duration_bg,
			$duration_text,
			$duration_bg_sel,
			$service_price,
			$check_border,
			$check_active,
			$input_bg,
			$btn_loading,
			$selector,
		);
	}

	public static function register(): void {
		if ( self::$registered ) {
			return;
		}
		self::$registered = true;

		$query = Settings::font_google_query();
		if ( $query ) {
			wp_register_style(
				'mr-booking-fonts',
				'https://fonts.googleapis.com/css2?family=' . $query . '&display=swap',
				array(),
				null
			);
		} else {
			wp_register_style( 'mr-booking-fonts', false );
		}

		wp_register_style(
			'mr-booking-frontend',
			MR_BOOKING_URL . 'assets/css/frontend.css',
			array( 'mr-booking-fonts' ),
			MR_BOOKING_VERSION
		);

		wp_add_inline_style( 'mr-booking-frontend', self::color_css_vars( '.mrb' ) );
		wp_add_inline_style( 'mr-booking-frontend', self::page_background_css( '.mrb' ) );

		wp_register_script(
			'mr-booking-birth-picker',
			MR_BOOKING_URL . 'assets/js/birth-picker.js',
			array(),
			MR_BOOKING_VERSION,
			true
		);

		wp_register_script(
			'mr-booking-otp-login',
			MR_BOOKING_URL . 'assets/js/otp-login.js',
			array(),
			MR_BOOKING_VERSION,
			true
		);

		wp_register_script(
			'mr-booking-frontend',
			MR_BOOKING_URL . 'assets/js/frontend.js',
			array( 'mr-booking-birth-picker', 'mr-booking-otp-login' ),
			MR_BOOKING_VERSION,
			true
		);

		wp_register_script(
			'mr-booking-account',
			MR_BOOKING_URL . 'assets/js/account.js',
			array( 'mr-booking-birth-picker', 'mr-booking-otp-login' ),
			MR_BOOKING_VERSION,
			true
		);

		self::localize_scripts();
	}

	/**
	 * Assets for the [mr_booking_account] page.
	 */
	/**
	 * Variables for the customer dashboard. "admin" (default) mirrors the plugin panel's
	 * green palette; "form" leaves the booking-form preset in place.
	 */
	public static function account_css_vars(): string {
		// The inline `.mrb{background:…}` from the form preset is printed after frontend.css and
		// would otherwise paint the wizard embedded in the dashboard; keep it transparent there.
		$css = '.mrb--account .mrb--embedded{background:transparent;}';

		$scheme = (string) Settings::get_value( 'account_color_scheme', 'admin' );
		if ( 'admin' !== $scheme ) {
			return $css;
		}

		$palette = Color_Presets::admin_palette();
		$vars    = '';
		foreach ( $palette as $name => $value ) {
			$vars .= $name . ':' . $value . ';';
		}

		// Scope to the dashboard and to the wizard embedded inside it (its own .mrb rule
		// would otherwise re-declare the form preset on that element).
		return $css . sprintf(
			'.mrb--account,.mrb--account .mrb{%1$s}.mrb--account{background:%2$s;}',
			$vars,
			$palette['--mrb-bg']
		);
	}

	public static function enqueue_account(): void {
		self::register();

		wp_enqueue_style( 'mr-booking-fonts' );
		wp_enqueue_style( 'mr-booking-frontend' );
		wp_add_inline_style( 'mr-booking-frontend', self::account_css_vars() );
		wp_enqueue_script( 'mr-booking-birth-picker' );
		wp_enqueue_script( 'mr-booking-otp-login' );
		wp_enqueue_script( 'mr-booking-account' );

		wp_localize_script(
			'mr-booking-account',
			'mrBookingAccount',
			array(
				'restUrl'      => esc_url_raw( rest_url( 'mr-booking/v1' ) ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'calendarMode' => (string) Settings::get_value( 'calendar_mode', 'jalali' ),
				'months'       => \MRBooking\Calendar\Jalali::month_names(),
				'wallet'       => array(
					'enabled'       => \MRBooking\Payments\Payment_Service::wallet_enabled() ? 1 : 0,
					'topup_enabled' => \MRBooking\Payments\Payment_Service::topup_enabled() ? 1 : 0,
					'topup_min'     => \MRBooking\Payments\Payment_Service::topup_min(),
					'topup_min_label' => \MRBooking\Helpers::format_price( \MRBooking\Payments\Payment_Service::topup_min() ),
				),
				'i18n'         => array_merge(
					self::otp_i18n(),
					array(
						'topup'         => __( 'افزایش موجودی', 'mr-booking' ),
						'topupMin'      => __( 'حداقل مبلغ %s است.', 'mr-booking' ),
						'redirecting'   => __( 'در حال انتقال به درگاه پرداخت...', 'mr-booking' ),
						'topupSuccess'  => __( 'پرداخت موفق بود و موجودی کیف پول شما افزایش یافت.', 'mr-booking' ),
						'topupFailed'   => __( 'پرداخت ناموفق بود. اگر مبلغی کسر شده، با ما تماس بگیرید.', 'mr-booking' ),
						'topupCancelled'=> __( 'پرداخت لغو شد و موجودی تغییری نکرد.', 'mr-booking' ),
						'loading'       => __( 'در حال بارگذاری...', 'mr-booking' ),
						'saved'         => __( 'ذخیره شد.', 'mr-booking' ),
						'cancel'        => __( 'لغو نوبت', 'mr-booking' ),
						'confirmCancel' => __( 'این نوبت لغو شود؟', 'mr-booking' ),
						'yesCancel'     => __( 'بله، لغو کن', 'mr-booking' ),
						'keep'          => __( 'نه، نگه دار', 'mr-booking' ),
						'cancelled'     => __( 'نوبت لغو شد.', 'mr-booking' ),
						'cancelPolicy'  => __( 'لغو نوبت تا %s دقیقه قبل از شروع امکان‌پذیر است.', 'mr-booking' ),
						'minutes'       => __( 'دقیقه', 'mr-booking' ),
						'selectBirth'   => __( 'انتخاب تاریخ تولد', 'mr-booking' ),
						'invalidEmail'  => __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ),
					)
				),
			)
		);
	}

	/**
	 * Strings shared by every OTP widget instance.
	 *
	 * @return array<string, string>
	 */
	private static function otp_i18n(): array {
		return array(
			'error'        => __( 'خطایی رخ داد. دوباره تلاش کنید.', 'mr-booking' ),
			'invalidPhone' => __( 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۱۲۳۴۵۶۷', 'mr-booking' ),
			'invalidCode'  => __( 'کد ۵ رقمی را وارد کنید.', 'mr-booking' ),
			'invalidName'  => __( 'نام باید حداقل ۲ حرف باشد.', 'mr-booking' ),
			'sentTo'       => __( 'کد به %s پیامک شد.', 'mr-booking' ),
			'resendIn'     => __( 'ارسال مجدد تا %s ثانیه', 'mr-booking' ),
		);
	}

	public static function enqueue_fonts(): void {
		self::register();
		wp_enqueue_style( 'mr-booking-fonts' );
	}

	public static function page_background_css( string $selector = '.mrb' ): string {
		$settings = Settings::get();
		$base     = sanitize_hex_color( (string) $settings['color_background'] ) ?: '#f8faf9';

		if ( empty( $settings['bg_gradient_enabled'] ) ) {
			return sprintf( '%s{background:%s;}', $selector, $base );
		}

		$primary = sanitize_hex_color( (string) $settings['color_bg_gradient_primary'] )
			?: sanitize_hex_color( (string) $settings['color_primary'] )
			?: '#0f766e';
		$accent  = sanitize_hex_color( (string) $settings['color_bg_gradient_accent'] )
			?: sanitize_hex_color( (string) $settings['color_accent'] )
			?: '#f59e0b';
		$primary_mix = max( 0, min( 100, (int) ( $settings['bg_gradient_primary_mix'] ?? 12 ) ) );
		$accent_mix  = max( 0, min( 100, (int) ( $settings['bg_gradient_accent_mix'] ?? 10 ) ) );

		return sprintf(
			'%1$s{background:radial-gradient(1200px 500px at 100%% -10%%, color-mix(in srgb, %2$s %3$d%%, transparent), transparent 60%%), radial-gradient(800px 400px at 0%% 100%%, color-mix(in srgb, %4$s %5$d%%, transparent), transparent 55%%), %6$s;}',
			$selector,
			$primary,
			$primary_mix,
			$accent,
			$accent_mix,
			$base
		);
	}

	public static function enqueue(): void {
		self::register();

		if ( self::$enqueued ) {
			return;
		}
		self::$enqueued = true;

		wp_enqueue_style( 'mr-booking-fonts' );
		wp_enqueue_style( 'mr-booking-frontend' );
		wp_enqueue_script( 'mr-booking-birth-picker' );
		wp_enqueue_script( 'mr-booking-frontend' );
	}

	/**
	 * Wallet snapshot for the current visitor (null when not logged in / disabled).
	 *
	 * @return array{balance:float,balance_label:string}|null
	 */
	private static function wallet_state(): ?array {
		if ( ! \MRBooking\Payments\Payment_Service::wallet_enabled() ) {
			return null;
		}
		$customer = \MRBooking\Auth\Customer_Auth::current_customer();
		if ( ! $customer ) {
			return null;
		}
		$balance = \MRBooking\Wallet\Wallet_Repository::balance( (int) $customer->id );

		return array(
			'balance'       => $balance,
			'balance_label' => $balance > 0 ? \MRBooking\Helpers::format_price( $balance ) : \MRBooking\Helpers::to_persian_digits( '0' ) . ' ' . __( 'تومان', 'mr-booking' ),
		);
	}

	private static function localize_scripts(): void {
		if ( self::$localized ) {
			return;
		}
		self::$localized = true;

		$settings = Settings::get();

		wp_localize_script(
			'mr-booking-frontend',
			'mrBooking',
			array(
				'restUrl' => esc_url_raw( rest_url( 'mr-booking/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'settings'=> array(
					'calendar_mode'          => $settings['calendar_mode'],
					'hour_format'            => \MRBooking\Helpers::hour_format(),
					'show_prices'            => (int) $settings['show_prices'],
					'enable_multi_service'   => (int) $settings['enable_multi_service'],
					'enable_staff_selection' => (int) $settings['enable_staff_selection'],
					'require_email'          => (int) $settings['require_email'],
					'require_birth_date'     => (int) $settings['require_birth_date'],
					'require_staff'          => (int) $settings['require_staff'],
					'allow_same_day'         => (int) $settings['allow_same_day'],
					'min_notice_minutes'     => (int) $settings['min_notice_minutes'],
					'today'                  => \MRBooking\Bookings\Slot_Engine::today(),
					'show_deposit'           => \MRBooking\Payments\Payment_Service::deposit_enabled() ? 1 : 0,
					'enable_tip'             => \MRBooking\Payments\Payment_Service::tip_enabled() ? 1 : 0,
					'wallet_enabled'         => \MRBooking\Payments\Payment_Service::wallet_enabled() ? 1 : 0,
					'online_enabled'         => \MRBooking\Payments\Payment_Service::online_enabled() ? 1 : 0,
					'require_terms'          => (int) ( $settings['require_terms'] ?? 1 ),
				),
				'wallet'  => self::wallet_state(),
				'texts'   => array(
					'title'              => $settings['text_title'],
					'step_personal'      => $settings['text_step_personal'],
					'step_service'       => $settings['text_step_service'],
					'step_date'          => $settings['text_step_date'],
					'step_time'          => $settings['text_step_time'],
					'step_confirm'       => $settings['text_step_confirm'],
					'btn_next'           => $settings['text_btn_next'],
					'btn_prev'           => $settings['text_btn_prev'],
					'btn_submit'         => $settings['text_btn_submit'],
					'success'            => $settings['text_success'],
					'fully_booked'       => $settings['text_fully_booked'],
					'no_slots'           => $settings['text_no_slots'],
					'booking_for_myself' => $settings['text_booking_for_myself'],
					'booking_for_child'  => $settings['text_booking_for_child'],
					'booking_for_other'  => $settings['text_booking_for_other'],
					'closed_day'         => $settings['text_closed_day'],
					'holiday'            => $settings['text_holiday'],
					'ph_first_name'      => $settings['text_ph_first_name'],
					'ph_last_name'       => $settings['text_ph_last_name'],
					'ph_phone'           => $settings['text_ph_phone'],
					'ph_email'           => $settings['text_ph_email'],
					'ph_birth_date'      => $settings['text_ph_birth_date'],
					'ph_booking_for_name'=> $settings['text_ph_booking_for_name'],
					'ph_staff'           => $settings['text_ph_staff'],
					'step_payment'       => $settings['text_step_payment'] ?? 'پرداخت',
					'deposit_label'      => $settings['text_deposit_label'] ?? 'پیش‌پرداخت',
					'tip_label'          => $settings['text_tip_label'] ?? 'انعام (اختیاری)',
				),
				'months'  => \MRBooking\Calendar\Jalali::month_names(),
				'auth'    => array(
					'mode'        => \MRBooking\Auth\Customer_Auth::login_mode(),
					'logged_in'   => null !== \MRBooking\Auth\Customer_Auth::current_customer(),
					'customer'    => \MRBooking\Auth\Customer_Auth::public_customer( \MRBooking\Auth\Customer_Auth::current_customer() ),
					'account_url' => \MRBooking\Auth\Customer_Auth::account_url(),
				),
				'i18n'    => array_merge( self::otp_i18n(), array(
					'loginRequired' => __( 'برای ثبت رزرو ابتدا با شماره موبایل وارد شوید.', 'mr-booking' ),
					'loggedInAs'    => __( 'رزرو با حساب', 'mr-booking' ),
					'phoneLocked'   => __( 'شماره تأییدشده حساب شما؛ قابل تغییر نیست.', 'mr-booking' ),
					'account'       => __( 'حساب کاربری', 'mr-booking' ),
					'logout'        => __( 'خروج', 'mr-booking' ),
					'haveAccount'   => __( 'قبلاً رزرو کرده‌اید؟ با کد پیامکی وارد شوید تا اطلاعات‌تان پر شود.', 'mr-booking' ),
					'loginBtn'      => __( 'ورود با کد پیامکی', 'mr-booking' ),
					'guestBtn'      => __( 'ادامه بدون ورود', 'mr-booking' ),
					'welcomeBack'   => __( 'خوش آمدید! اطلاعات شما پر شد.', 'mr-booking' ),
					'selectService' => __( 'لطفاً خدمت را انتخاب کنید.', 'mr-booking' ),
					'selectDate'    => __( 'لطفاً تاریخ را انتخاب کنید.', 'mr-booking' ),
					'selectTime'    => __( 'لطفاً ساعت را انتخاب کنید.', 'mr-booking' ),
					'pastDate'      => __( 'تاریخ‌های گذشته قابل انتخاب نیستند.', 'mr-booking' ),
					'sameDayDisabled' => __( 'رزرو برای امروز در تنظیمات غیرفعال است.', 'mr-booking' ),
					'noFutureSlots' => __( 'برای این روز ساعت آزاد باقی نمانده است.', 'mr-booking' ),
					'todayLabel'    => __( 'امروز', 'mr-booking' ),
					'officialHoliday' => __( 'تعطیل رسمی', 'mr-booking' ),
					'selectedDateHint' => __( 'برای ادامه «بعدی» را بزنید یا تاریخ دیگری انتخاب کنید.', 'mr-booking' ),
					'pastTime'      => __( 'این ساعت گذشته است.', 'mr-booking' ),
					'bookedTime'    => __( 'این ساعت رزرو شده است.', 'mr-booking' ),
					'selectBirth'   => __( 'لطفاً تاریخ تولد را انتخاب کنید.', 'mr-booking' ),
					'selectStaff'   => __( 'لطفاً پرسنل را انتخاب کنید.', 'mr-booking' ),
					'staffAny'      => __( 'همه پرسنل', 'mr-booking' ),
					'staffPlaceholder' => __( 'انتخاب پرسنل…', 'mr-booking' ),
					'staffLabelRequired' => __( 'انتخاب پرسنل', 'mr-booking' ),
					'staffLabelOptional' => __( 'انتخاب پرسنل (اختیاری)', 'mr-booking' ),
					'staffHintRequired' => __( 'ابتدا پرسنل را انتخاب کنید؛ سپس خدمات همان پرسنل نمایش داده می‌شود.', 'mr-booking' ),
					'staffHintOptional' => __( 'پرسنل را انتخاب کنید تا خدمات مرتبط فیلتر شود، یا «همه پرسنل» را بگذارید.', 'mr-booking' ),
					'invalidPhone'  => __( 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۱۲۳۴۵۶۷', 'mr-booking' ),
					'invalidEmail'  => __( 'ایمیل واردشده معتبر نیست.', 'mr-booking' ),
					'invalidName'   => __( 'نام باید حداقل ۲ حرف باشد.', 'mr-booking' ),
					'required'      => __( 'این فیلد الزامی است.', 'mr-booking' ),
					'forNameRequired' => __( 'نام فرد را وارد کنید.', 'mr-booking' ),
					'fixErrors'    => __( 'لطفاً موارد مشخص‌شده را اصلاح کنید.', 'mr-booking' ),
					'loading'       => __( 'در حال بارگذاری...', 'mr-booking' ),
					'am'            => __( 'ق.ظ', 'mr-booking' ),
					'pm'            => __( 'ب.ظ', 'mr-booking' ),
					'submitting'    => __( 'در حال ثبت...', 'mr-booking' ),
					'error'         => __( 'خطایی رخ داد. دوباره تلاش کنید.', 'mr-booking' ),
					'acceptTerms'   => __( 'برای ادامه باید قوانین و شرایط را بپذیرید.', 'mr-booking' ),
					'selectPayment' => __( 'روش پرداخت را انتخاب کنید.', 'mr-booking' ),
					'btnPay'        => __( 'پرداخت و ثبت رزرو', 'mr-booking' ),
					'btnToPayment'  => __( 'ادامه به پرداخت', 'mr-booking' ),
					'redirecting'   => __( 'در حال انتقال به درگاه پرداخت...', 'mr-booking' ),
					'walletShort'   => __( 'موجودی کیف پول کافی نیست.', 'mr-booking' ),
					'walletLogin'   => __( 'برای پرداخت با کیف پول باید وارد حساب کاربری شوید.', 'mr-booking' ),
					'walletBalance' => __( 'موجودی', 'mr-booking' ),
					'payWallet'     => __( 'پرداخت با کیف پول', 'mr-booking' ),
					'payOnline'     => __( 'پرداخت آنلاین', 'mr-booking' ),
					'payOnlineHint' => __( 'انتقال امن به درگاه زرین‌پال', 'mr-booking' ),
					'total'         => __( 'مبلغ قابل پرداخت', 'mr-booking' ),
					'noDeposit'     => __( 'بدون پیش‌پرداخت', 'mr-booking' ),
					'paid'          => __( 'پرداخت‌شده', 'mr-booking' ),
					'paySuccess'    => __( 'پرداخت با موفقیت انجام شد و رزرو شما ثبت شد.', 'mr-booking' ),
					'payFailed'     => __( 'پرداخت ناموفق بود و رزروی ثبت نشد. می‌توانید دوباره تلاش کنید.', 'mr-booking' ),
					'payCancelled'  => __( 'پرداخت لغو شد و رزروی ثبت نشد. می‌توانید دوباره تلاش کنید.', 'mr-booking' ),
					'payInvalid'    => __( 'نتیجه پرداخت قابل بررسی نبود. اگر مبلغی کسر شده با ما تماس بگیرید.', 'mr-booking' ),
				) ),
			)
		);
	}
}
