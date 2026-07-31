<?php
/**
 * Plugin settings repository.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Settings;

defined( 'ABSPATH' ) || exit;

final class Settings {

	public const OPTION_KEY = 'mr_booking_settings';

	public static function init(): void {
		add_action( 'admin_init', array( self::class, 'maybe_upgrade' ) );
	}

	public static function maybe_upgrade(): void {
		$db_version = get_option( 'mr_booking_db_version' );
		if ( $db_version !== MR_BOOKING_DB_VERSION ) {
			\MRBooking\Database\Installer::create_tables();
			update_option( 'mr_booking_db_version', MR_BOOKING_DB_VERSION );
		} else {
			\MRBooking\Database\Installer::migrate_columns();
		}

		// Ensure price display is opt-in (off by default) for existing installs.
		if ( ! get_option( 'mr_booking_show_prices_migrated', false ) ) {
			$stored = get_option( self::OPTION_KEY, array() );
			if ( ! is_array( $stored ) ) {
				$stored = array();
			}
			$stored['show_prices'] = 0;
			update_option( self::OPTION_KEY, $stored );
			update_option( 'mr_booking_show_prices_migrated', 1, false );
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		$defaults = array(
			// General.
			'business_name'           => get_bloginfo( 'name' ),
			'calendar_mode'           => 'jalali', // gregorian | jalali | both
			'default_status'          => 'pending',
			'timezone'                => wp_timezone_string(),

			// Booking rules.
			'booking_interval'        => 15,
			'min_notice_minutes'      => 60,
			'max_days_ahead'          => 60,
			'allow_same_day'          => 1,
			'allow_holiday_booking'   => 0,
			'allow_cancellation'      => 1,
			'cancellation_deadline'   => 24,
			'allow_reschedule'        => 1,
			'reschedule_deadline'     => 24,
			'require_email'           => 0,
			'require_phone'           => 1,
			'require_birth_date'      => 0,
			'require_staff'           => 0,
			'enable_staff_selection'  => 1,
			'enable_multi_service'    => 1,
			'enable_guest_booking'    => 1,
			'show_prices'             => 0,
			'auto_assign_staff'       => 1,
			'hours_mode'              => 'global', // global | per_staff
			'break_between_appointments' => 0,

			// Premium / white-label.
			'premium_key'             => '',
			'hide_branding'           => 0,
		);

		$defaults = array_merge( $defaults, \MRBooking\Admin\Dashboard_Widget::default_settings() );

		return array_merge(
			$defaults,
			Color_Presets::get( 'dark' ),
			array(
			'font_family'             => 'vazirmatn',
			// Frontend texts.
			'text_title'              => 'رزرو نوبت',
			'text_step_personal'      => 'اطلاعات شخصی',
			'text_step_service'       => 'انتخاب خدمت',
			'text_step_date'          => 'انتخاب تاریخ',
			'text_step_time'          => 'انتخاب ساعت',
			'text_step_confirm'       => 'تأیید رزرو',
			'text_btn_next'           => 'ادامه',
			'text_btn_prev'           => 'قبلی',
			'text_btn_submit'         => 'ثبت رزرو',
			'text_success'            => 'رزرو شما با موفقیت ثبت شد.',
			'text_fully_booked'       => 'ظرفیت این روز تکمیل شده است',
			'text_no_slots'           => 'در این روز زمان خالی برای رزرو وجود ندارد.',
			'text_booking_for_myself' => 'برای خودم',
			'text_booking_for_child'  => 'برای فرزندم',
			'text_booking_for_other'  => 'برای شخص دیگری',
			'text_closed_day'         => 'تعطیل',
			'text_holiday'            => 'تعطیل رسمی',

			// Input placeholders (frontend booking form).
			'text_ph_first_name'      => 'مثلاً: علی',
			'text_ph_last_name'       => 'مثلاً: احمدی',
			'text_ph_phone'           => '09121234567',
			'text_ph_email'           => 'example@email.com',
			'text_ph_birth_date'      => 'انتخاب تاریخ تولد',
			'text_ph_booking_for_name'=> 'نام فرد',
			'text_ph_staff'           => 'انتخاب پرسنل…',

			// Email.
			'email_from_name'         => get_bloginfo( 'name' ),
			'email_from_email'        => get_option( 'admin_email' ),
			'email_reply_to'          => get_option( 'admin_email' ),
			'email_admin'             => get_option( 'admin_email' ),
			'email_enabled'           => 1,

			// SMS.
			'sms_enabled'             => 0,
			'sms_provider'            => 'kavenegar',
			'sms_api_key'             => '',
			'sms_username'            => '',
			'sms_password'            => '',
			'sms_sender'              => '',
			'sms_api_url'             => '',
			'sms_template_id'         => '',
			'sms_admin_phone'         => '',

			// Reminder.
			'reminder_hours_before'   => 24,
			'reminder_enabled'        => 1,
			'notify_customer_on_confirm' => 1,

			// Templates stored separately but defaults here for bootstrap.
			'tpl_sms_created'           => 'سلام {customer_name}، درخواست رزرو شما برای {service_name} در تاریخ {booking_date} ساعت {booking_time} ثبت شد و در انتظار تأیید است. کد: {booking_id}',
			'tpl_sms_confirmed'         => 'سلام {customer_name}، رزرو شما تأیید شد. {service_name} — {booking_date} ساعت {booking_time}. منتظرتان هستیم.',
			'tpl_sms_cancelled'         => 'سلام {customer_name}، متأسفانه رزرو {booking_id} تأیید نشد / لغو شد.',
			'tpl_sms_reminder'          => 'یادآوری: فردا ساعت {booking_time} نوبت {service_name} دارید. {business_name}',
			'tpl_sms_birthday'          => 'تولدتان مبارک {customer_name}! از طرف {business_name}',
			'tpl_email_created_subject' => 'ثبت درخواست رزرو — {booking_id}',
			'tpl_email_created_body'    => '<p>سلام {customer_name}،</p><p>درخواست رزرو شما برای <strong>{service_name}</strong> در تاریخ <strong>{booking_date}</strong> ساعت <strong>{booking_time}</strong> ثبت شد و <strong>در انتظار تأیید</strong> است.</p><p>کد رزرو: {booking_id}</p>',
			'tpl_email_confirmed_subject' => 'تأیید رزرو — {booking_id}',
			'tpl_email_confirmed_body'  => '<p>سلام {customer_name}،</p><p>رزرو شما <strong>تأیید شد</strong>.</p><p>{service_name} — {booking_date} ساعت {booking_time}</p><p>منتظر دیدارتان هستیم.</p>',
			'tpl_email_cancelled_subject' => 'عدم تأیید / لغو رزرو — {booking_id}',
			'tpl_email_cancelled_body'  => '<p>سلام {customer_name}،</p><p>متأسفانه رزرو شما تأیید نشد یا لغو گردید.</p>',
			'tpl_email_reminder_subject'=> 'یادآوری نوبت',
			'tpl_email_reminder_body'   => '<p>سلام {customer_name}،</p><p>یادآوری نوبت {service_name} در تاریخ {booking_date} ساعت {booking_time}.</p>',
			'notify_emails'             => '',
			'notify_phones'             => '',
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$defaults = self::defaults();
		$stored   = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$merged = array_merge( $defaults, $stored );

		// Empty saved templates should not override built-in defaults.
		foreach ( $defaults as $key => $value ) {
			if ( 0 === strpos( $key, 'tpl_' ) && '' === trim( (string) ( $merged[ $key ] ?? '' ) ) ) {
				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * @param array<string, mixed> $data Settings to merge.
	 */
	public static function update( array $data ): void {
		$current = self::get();
		$merged  = array_merge( $current, $data );
		update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * @param mixed $default Default.
	 * @return mixed
	 */
	public static function get_value( string $key, $default = null ) {
		$all = self::get();

		return $all[ $key ] ?? $default;
	}

	/**
	 * Available frontend font families.
	 *
	 * @return array<string, array{label:string,family:string,google:string}>
	 */
	public static function font_families(): array {
		return array(
			'vazirmatn'        => array(
				'label'  => __( 'وزیرمتن', 'mr-booking' ),
				'family' => '"Vazirmatn", Tahoma, sans-serif',
				'google' => 'Vazirmatn:wght@400;500;600;700',
			),
			'rubik'            => array(
				'label'  => __( 'Rubik', 'mr-booking' ),
				'family' => '"Rubik", Tahoma, sans-serif',
				'google' => 'Rubik:wght@400;500;600;700',
			),
			'cairo'            => array(
				'label'  => __( 'Cairo', 'mr-booking' ),
				'family' => '"Cairo", Tahoma, sans-serif',
				'google' => 'Cairo:wght@400;500;600;700',
			),
			'noto_sans_arabic' => array(
				'label'  => __( 'Noto Sans Arabic', 'mr-booking' ),
				'family' => '"Noto Sans Arabic", Tahoma, sans-serif',
				'google' => 'Noto+Sans+Arabic:wght@400;500;600;700',
			),
			'ibm_plex_arabic'  => array(
				'label'  => __( 'IBM Plex Sans Arabic', 'mr-booking' ),
				'family' => '"IBM Plex Sans Arabic", Tahoma, sans-serif',
				'google' => 'IBM+Plex+Sans+Arabic:wght@400;500;600;700',
			),
			'tahoma'           => array(
				'label'  => __( 'Tahoma (سیستم)', 'mr-booking' ),
				'family' => 'Tahoma, Arial, sans-serif',
				'google' => '',
			),
		);
	}

	/**
	 * Resolve saved font key to CSS font-family value.
	 */
	public static function font_family_css( ?string $key = null ): string {
		$key   = $key ?? (string) self::get_value( 'font_family', 'vazirmatn' );
		$fonts = self::font_families();

		return $fonts[ $key ]['family'] ?? $fonts['vazirmatn']['family'];
	}

	/**
	 * Google Fonts CSS2 family query for the selected font.
	 */
	public static function font_google_query( ?string $key = null ): string {
		$key   = $key ?? (string) self::get_value( 'font_family', 'vazirmatn' );
		$fonts = self::font_families();

		return $fonts[ $key ]['google'] ?? $fonts['vazirmatn']['google'];
	}
}
