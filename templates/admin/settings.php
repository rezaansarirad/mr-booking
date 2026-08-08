<?php
/**
 * Settings template.
 *
 * @package MRBooking
 */

defined( 'ABSPATH' ) || exit;

use MRBooking\Helpers;
use MRBooking\Notifications\SMS\SMS_Manager;
use MRBooking\Roles\Capabilities;
use MRBooking\Roles\Roles;

$tabs = array(
	'general'    => array(
		'label' => __( 'عمومی', 'mr-booking' ),
		'desc'  => __( 'اطلاعات پایه کسب‌وکار و رفتار فرم', 'mr-booking' ),
		'icon'  => 'dashicons-admin-home',
	),
	'dashboard'  => array(
		'label' => __( 'پیشخوان', 'mr-booking' ),
		'desc'  => __( 'خلاصه فعالیت در داشبورد وردپرس', 'mr-booking' ),
		'icon'  => 'dashicons-dashboard',
	),
	'calendar'   => array(
		'label' => __( 'تقویم', 'mr-booking' ),
		'desc'  => __( 'شمسی، میلادی و نمایش تعطیلات', 'mr-booking' ),
		'icon'  => 'dashicons-calendar-alt',
	),
	'rules'      => array(
		'label' => __( 'قوانین رزرو', 'mr-booking' ),
		'desc'  => __( 'اسلات‌ها، مهلت‌ها و فیلدهای الزامی', 'mr-booking' ),
		'icon'  => 'dashicons-shield',
	),
	'appearance' => array(
		'label' => __( 'ظاهر', 'mr-booking' ),
		'desc'  => __( 'فونت، پس‌زمینه و رنگ‌بندی فرم رزرو', 'mr-booking' ),
		'icon'  => 'dashicons-art',
	),
	'texts'      => array(
		'label' => __( 'متن‌ها', 'mr-booking' ),
		'desc'  => __( 'برچسب‌ها و پیام‌های قابل ویرایش', 'mr-booking' ),
		'icon'  => 'dashicons-editor-textcolor',
	),
	'sms'        => array(
		'label' => __( 'پیامک', 'mr-booking' ),
		'desc'  => __( 'سرویس‌دهنده و کلیدهای API', 'mr-booking' ),
		'icon'  => 'dashicons-email-alt',
	),
	'email'      => array(
		'label' => __( 'ایمیل', 'mr-booking' ),
		'desc'  => __( 'فرستنده و اعلان‌های ایمیلی', 'mr-booking' ),
		'icon'  => 'dashicons-email',
	),
	'premium'    => array(
		'label' => __( 'پریمیوم', 'mr-booking' ),
		'desc'  => __( 'فعال‌سازی امکانات ویژه و وایت‌لیبل', 'mr-booking' ),
		'icon'  => 'dashicons-star-filled',
	),
	'access'     => array(
		'label' => __( 'دسترسی', 'mr-booking' ),
		'desc'  => __( 'نقش‌های کاربری و سطح دسترسی پرسنل', 'mr-booking' ),
		'icon'  => 'dashicons-groups',
	),
	'github'     => array(
		'label' => __( 'گیت‌هاب', 'mr-booking' ),
		'desc'  => __( 'پروژه متن‌باز و ستاره دادن', 'mr-booking' ),
		'icon'  => 'dashicons-heart',
	),
);

$current = $tabs[ $tab ] ?? $tabs['general'];

$text_labels = array(
	'text_title'              => __( 'عنوان فرم', 'mr-booking' ),
	'text_step_personal'      => __( 'مرحله اطلاعات شخصی', 'mr-booking' ),
	'text_step_service'       => __( 'مرحله خدمت', 'mr-booking' ),
	'text_step_date'          => __( 'مرحله تاریخ', 'mr-booking' ),
	'text_step_time'          => __( 'مرحله ساعت', 'mr-booking' ),
	'text_step_confirm'       => __( 'مرحله تأیید', 'mr-booking' ),
	'text_btn_next'           => __( 'دکمه ادامه', 'mr-booking' ),
	'text_btn_prev'           => __( 'دکمه قبلی', 'mr-booking' ),
	'text_btn_submit'         => __( 'دکمه ثبت', 'mr-booking' ),
	'text_success'            => __( 'پیام موفقیت', 'mr-booking' ),
	'text_fully_booked'       => __( 'پیام ظرفیت تکمیل', 'mr-booking' ),
	'text_no_slots'           => __( 'پیام نبود زمان خالی', 'mr-booking' ),
	'text_booking_for_myself' => __( 'برای خودم', 'mr-booking' ),
	'text_booking_for_child'  => __( 'برای فرزندم', 'mr-booking' ),
	'text_booking_for_other'  => __( 'برای شخص دیگر', 'mr-booking' ),
	'text_closed_day'         => __( 'متن روز تعطیل', 'mr-booking' ),
	'text_holiday'            => __( 'متن تعطیل رسمی', 'mr-booking' ),
);

$placeholder_labels = array(
	'text_ph_first_name'       => __( 'Placeholder — نام', 'mr-booking' ),
	'text_ph_last_name'        => __( 'Placeholder — نام خانوادگی', 'mr-booking' ),
	'text_ph_phone'            => __( 'Placeholder — موبایل', 'mr-booking' ),
	'text_ph_email'            => __( 'Placeholder — ایمیل', 'mr-booking' ),
	'text_ph_birth_date'       => __( 'Placeholder — تاریخ تولد', 'mr-booking' ),
	'text_ph_booking_for_name' => __( 'Placeholder — نام فرد', 'mr-booking' ),
	'text_ph_staff'            => __( 'Placeholder — انتخاب پرسنل', 'mr-booking' ),
);

$theme_settings = array(
	'color_background' => array( __( 'پس‌زمینه پایه', 'mr-booking' ), __( 'رنگ زمینه اصلی زیر گرادیان‌ها', 'mr-booking' ) ),
	'color_card'       => array( __( 'پس‌زمینه کارت', 'mr-booking' ), __( 'سطح اصلی فرم رزرو', 'mr-booking' ) ),
);

$gradient_settings = array(
	'color_bg_gradient_primary' => array( __( 'گرادیان گوشه بالا', 'mr-booking' ), __( 'رنگ هاله بالا-راست', 'mr-booking' ) ),
	'color_bg_gradient_accent'  => array( __( 'گرادیان گوشه پایین', 'mr-booking' ), __( 'رنگ هاله پایین-چپ', 'mr-booking' ) ),
);

$colors = array(
	'color_primary'       => array( __( 'اصلی', 'mr-booking' ), __( 'رنگ برند و تاکیدها', 'mr-booking' ) ),
	'color_secondary'     => array( __( 'ثانویه', 'mr-booking' ), __( 'متن‌های پررنگ', 'mr-booking' ) ),
	'color_accent'        => array( __( 'اکسنت', 'mr-booking' ), __( 'جزئیات برجسته', 'mr-booking' ) ),
	'color_button'        => array( __( 'دکمه ادامه', 'mr-booking' ), __( 'پس‌زمینه دکمه «ادامه» و «ثبت»', 'mr-booking' ) ),
	'color_button_hover'  => array( __( 'هاور دکمه ادامه', 'mr-booking' ), __( 'حالت hover دکمه اصلی', 'mr-booking' ) ),
	'color_btn_text'      => array( __( 'متن دکمه ادامه', 'mr-booking' ), __( 'رنگ نوشته دکمه اصلی', 'mr-booking' ) ),
	'color_btn_loading'   => array( __( 'لودینگ دکمه ادامه', 'mr-booking' ), __( 'رنگ اسپینر هنگام بارگذاری یا ثبت', 'mr-booking' ) ),
	'color_btn_ghost_bg'      => array( __( 'دکمه قبلی', 'mr-booking' ), __( 'پس‌زمینه دکمه «قبلی»', 'mr-booking' ) ),
	'color_btn_ghost_hover'   => array( __( 'هاور دکمه قبلی', 'mr-booking' ), __( 'حالت hover دکمه قبلی', 'mr-booking' ) ),
	'color_btn_ghost_text'    => array( __( 'متن دکمه قبلی', 'mr-booking' ), __( 'رنگ نوشته دکمه قبلی', 'mr-booking' ) ),
	'color_text'          => array( __( 'متن عمومی', 'mr-booking' ), __( 'متن‌های راهنما، مراحل و بدنه فرم', 'mr-booking' ) ),
	'color_label'         => array( __( 'برچسب و عنوان', 'mr-booking' ), __( 'عنوان فرم، برچسب فیلدها و عناوین بخش‌ها', 'mr-booking' ) ),
	'color_input_text'        => array( __( 'متن داخل فیلد', 'mr-booking' ), __( 'رنگ نوشته داخل input، select و textarea', 'mr-booking' ) ),
	'color_input_placeholder' => array( __( 'Placeholder فیلد', 'mr-booking' ), __( 'رنگ متن راهنمای داخل input قبل از تایپ', 'mr-booking' ) ),
	'color_radio_active'      => array( __( 'رادیو — انتخاب‌شده', 'mr-booking' ), __( 'رنگ دکمه رادیویی «رزرو برای» وقتی فعال است', 'mr-booking' ) ),
	'color_service_text'               => array( __( 'نام خدمت', 'mr-booking' ), __( 'عنوان هر کارت در لیست خدمات', 'mr-booking' ) ),
	'color_service_desc'               => array( __( 'توضیحات خدمت', 'mr-booking' ), __( 'متن توضیح زیر نام خدمت (در صورت وجود)', 'mr-booking' ) ),
	'color_service_card'               => array( __( 'پس‌زمینه کارت', 'mr-booking' ), __( 'رنگ داخل کارت خدمت', 'mr-booking' ) ),
	'color_service_card_selected'      => array( __( 'پس‌زمینه انتخاب‌شده', 'mr-booking' ), __( 'وقتی کاربر یک خدمت را انتخاب کرد', 'mr-booking' ) ),
	'color_service_card_border'        => array( __( 'حاشیه کارت', 'mr-booking' ), __( 'خط دور هر کارت خدمت', 'mr-booking' ) ),
	'color_service_duration_bg'        => array( __( 'پس‌زمینه مدت زمان', 'mr-booking' ), __( 'کادر/برچسب نمایش مدت (مثلاً ۴۵ دقیقه)', 'mr-booking' ) ),
	'color_service_duration_text'      => array( __( 'متن مدت زمان', 'mr-booking' ), __( 'رنگ نوشته داخل کادر مدت', 'mr-booking' ) ),
	'color_service_duration_bg_selected' => array( __( 'کادر مدت — انتخاب‌شده', 'mr-booking' ), __( 'پس‌زمینه برچسب مدت وقتی کارت انتخاب است', 'mr-booking' ) ),
	'color_service_price'              => array( __( 'رنگ قیمت', 'mr-booking' ), __( 'نمایش قیمت روی کارت (اگر فعال باشد)', 'mr-booking' ) ),
	'color_service_check_border'       => array( __( 'حلقه انتخاب', 'mr-booking' ), __( 'دایره انتخاب قبل از active شدن', 'mr-booking' ) ),
	'color_service_check_active'       => array( __( 'تیک انتخاب‌شده', 'mr-booking' ), __( 'رنگ دایره وقتی خدمت انتخاب شد', 'mr-booking' ) ),
	'color_border'        => array( __( 'حاشیه', 'mr-booking' ), __( 'خط‌ها و بوردرها', 'mr-booking' ) ),
	'color_holiday'       => array( __( 'تعطیلات', 'mr-booking' ), __( 'متن روزهای تعطیل در تقویم', 'mr-booking' ) ),
	'color_holiday_bg'    => array( __( 'پس‌زمینه تعطیلات', 'mr-booking' ), __( 'رنگ پس‌زمینه روز تعطیل', 'mr-booking' ) ),
	'color_available'     => array( __( 'تاریخ آزاد', 'mr-booking' ), __( 'روزهای قابل رزرو', 'mr-booking' ) ),
	'color_unavailable'   => array( __( 'تاریخ غیرفعال', 'mr-booking' ), __( 'روزهای بسته', 'mr-booking' ) ),
	'color_fully_booked'  => array( __( 'ظرفیت تکمیل', 'mr-booking' ), __( 'روزهای پر', 'mr-booking' ) ),
	'color_success'       => array( __( 'موفق', 'mr-booking' ), __( 'پیام‌های موفقیت', 'mr-booking' ) ),
	'color_error'         => array( __( 'خطا', 'mr-booking' ), __( 'پیام‌های خطا', 'mr-booking' ) ),
	'color_warning'       => array( __( 'هشدار', 'mr-booking' ), __( 'هشدارها', 'mr-booking' ) ),
);

$color_groups = array(
	'brand' => array(
		'title' => __( 'برند', 'mr-booking' ),
		'desc'  => __( 'رنگ‌های اصلی هویت بصری فرم', 'mr-booking' ),
		'keys'  => array( 'color_primary', 'color_secondary', 'color_accent' ),
	),
	'buttons' => array(
		'title' => __( 'دکمه‌ها', 'mr-booking' ),
		'desc'  => __( 'رنگ دکمه‌های «ادامه»، «ثبت» و «قبلی» در فرم رزرو', 'mr-booking' ),
		'keys'  => array( 'color_button', 'color_button_hover', 'color_btn_text', 'color_btn_loading', 'color_btn_ghost_bg', 'color_btn_ghost_hover', 'color_btn_ghost_text' ),
	),
	'typography' => array(
		'title' => __( 'متن و فیلدها', 'mr-booking' ),
		'desc'  => __( 'رنگ نوشته در بخش‌های مختلف فرم', 'mr-booking' ),
		'keys'  => array( 'color_text', 'color_label', 'color_input_text', 'color_input_placeholder', 'color_radio_active' ),
	),
	'services' => array(
		'title' => __( 'کارت خدمات', 'mr-booking' ),
		'desc'  => __( 'رنگ‌بندی کامل لیست انتخاب خدمت در مرحله ۲ فرم رزرو', 'mr-booking' ),
		'keys'  => array(
			'color_service_card',
			'color_service_card_selected',
			'color_service_card_border',
			'color_service_text',
			'color_service_desc',
			'color_service_duration_bg',
			'color_service_duration_text',
			'color_service_duration_bg_selected',
			'color_service_price',
			'color_service_check_border',
			'color_service_check_active',
		),
	),
	'surface' => array(
		'title' => __( 'حاشیه', 'mr-booking' ),
		'desc'  => __( 'خطوط جداکننده و بوردر المان‌ها', 'mr-booking' ),
		'keys'  => array( 'color_border' ),
	),
	'calendar' => array(
		'title' => __( 'تقویم', 'mr-booking' ),
		'desc'  => __( 'وضعیت روزها در تقویم رزرو', 'mr-booking' ),
		'keys'  => array( 'color_holiday', 'color_holiday_bg', 'color_available', 'color_unavailable', 'color_fully_booked' ),
	),
	'feedback' => array(
		'title' => __( 'پیام‌ها', 'mr-booking' ),
		'desc'  => __( 'رنگ بازخورد موفقیت، خطا و هشدار', 'mr-booking' ),
		'keys'  => array( 'color_success', 'color_error', 'color_warning' ),
	),
);
?>
<div class="wrap mrb-admin mrb-settings" dir="rtl">
	<header class="mrb-settings__hero">
		<div class="mrb-settings__hero-copy">
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
			<h1><?php esc_html_e( 'تنظیمات', 'mr-booking' ); ?></h1>
			<p class="mrb-settings__hero-desc"><?php echo esc_html( $current['desc'] ); ?></p>
		</div>
		<div class="mrb-settings__hero-actions">
			<a class="mrb-btn mrb-btn--ghost" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mr-booking-settings&rerun_setup=1' ), 'mr_booking_rerun_setup' ) ); ?>">
				<span class="dashicons dashicons-admin-generic"></span>
				<?php esc_html_e( 'ویزارد راه‌اندازی', 'mr-booking' ); ?>
			</a>
			<a class="mrb-btn mrb-btn--ghost" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking' ) ); ?>">
				<span class="dashicons dashicons-dashboard"></span>
				<?php esc_html_e( 'داشبورد', 'mr-booking' ); ?>
			</a>
		</div>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
		<div class="mrb-settings__toast" role="status">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php esc_html_e( 'تنظیمات با موفقیت ذخیره شد.', 'mr-booking' ); ?>
		</div>
	<?php endif; ?>

	<?php
	$theme_applied = isset( $_GET['theme_applied'] ) ? sanitize_key( wp_unslash( $_GET['theme_applied'] ) ) : ''; // phpcs:ignore
	if ( in_array( $theme_applied, \MRBooking\Settings\Color_Presets::theme_ids(), true ) ) :
		?>
		<div class="mrb-settings__toast" role="status">
			<span class="dashicons dashicons-yes-alt"></span>
			<?php
			echo esc_html(
				'dark' === $theme_applied
					? __( 'قالب تیره با موفقیت اعمال شد.', 'mr-booking' )
					: __( 'قالب روشن با موفقیت اعمال شد.', 'mr-booking' )
			);
			?>
		</div>
	<?php endif; ?>

	<div class="mrb-settings__layout">
		<aside class="mrb-settings__nav" aria-label="<?php esc_attr_e( 'بخش‌های تنظیمات', 'mr-booking' ); ?>">
			<?php foreach ( $tabs as $key => $meta ) : ?>
				<a
					class="mrb-settings__nav-item <?php echo $tab === $key ? 'is-active' : ''; ?>"
					href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-settings&tab=' . $key ) ); ?>"
				>
					<span class="dashicons <?php echo esc_attr( $meta['icon'] ); ?>"></span>
					<span class="mrb-settings__nav-text">
						<strong><?php echo esc_html( $meta['label'] ); ?></strong>
						<small><?php echo esc_html( $meta['desc'] ); ?></small>
					</span>
				</a>
			<?php endforeach; ?>
		</aside>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-settings__main">
			<?php wp_nonce_field( 'mr_booking_save_settings' ); ?>
			<input type="hidden" name="action" value="mr_booking_save_settings" />
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>" />

			<div class="mrb-settings__panel">
				<header class="mrb-settings__panel-head">
					<div>
						<h2>
							<span class="dashicons <?php echo esc_attr( $current['icon'] ); ?>"></span>
							<?php echo esc_html( $current['label'] ); ?>
						</h2>
						<p><?php echo esc_html( $current['desc'] ); ?></p>
					</div>
				</header>

				<div class="mrb-settings__panel-body">
					<?php if ( 'general' === $tab ) : ?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'هویت کسب‌وکار', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid">
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'نام کسب‌وکار', 'mr-booking' ); ?></span>
									<input type="text" name="settings[business_name]" value="<?php echo esc_attr( (string) $settings['business_name'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'وضعیت پیش‌فرض رزرو', 'mr-booking' ); ?></span>
									<select name="settings[default_status]">
										<?php foreach ( \MRBooking\Helpers::booking_statuses() as $k => $l ) : ?>
											<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $settings['default_status'], $k ); ?>><?php echo esc_html( $l ); ?></option>
										<?php endforeach; ?>
									</select>
									<span class="mrb-field__hint"><?php esc_html_e( 'برای تأیید دستی، روی «در انتظار» بگذارید. بعد از تأیید ادمین، پیام تأیید برای مشتری ارسال می‌شود.', 'mr-booking' ); ?></span>
								</label>
							</div>
						</section>

						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'ساعات و روزهای کاری', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid">
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'نوع ساعات کاری', 'mr-booking' ); ?></span>
									<select name="settings[hours_mode]">
										<option value="global" <?php selected( $settings['hours_mode'] ?? 'global', 'global' ); ?>><?php esc_html_e( 'یکسان برای کل سازمان', 'mr-booking' ); ?></option>
										<option value="per_staff" <?php selected( $settings['hours_mode'] ?? '', 'per_staff' ); ?>><?php esc_html_e( 'جدا برای هر پرسنل', 'mr-booking' ); ?></option>
									</select>
									<span class="mrb-field__hint">
										<?php esc_html_e( 'در حالت «جدا برای هر پرسنل»، ساعات هر نفر از بخش پرسنل یا ساعات کاری تنظیم می‌شود.', 'mr-booking' ); ?>
									</span>
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'استراحت بین نوبت‌ها (دقیقه)', 'mr-booking' ); ?></span>
									<input type="number" name="settings[break_between_appointments]" min="0" step="5" value="<?php echo esc_attr( (string) (int) ( $settings['break_between_appointments'] ?? 0 ) ); ?>" />
									<span class="mrb-field__hint"><?php esc_html_e( 'فاصله پیش‌فرض بعد از هر رزرو. هر پرسنل می‌تواند مقدار جدا داشته باشد.', 'mr-booking' ); ?></span>
								</label>
							</div>
						</section>

						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'گزینه‌های فرم', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__toggles">
								<label class="mrb-switch">
									<input type="checkbox" name="settings[show_prices]" value="1" <?php checked( ! empty( $settings['show_prices'] ) ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'نمایش قیمت به مشتری', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'فقط برای خدماتی که «دارای قیمت» هستند در فرم رزرو نشان داده می‌شود.', 'mr-booking' ); ?></small>
									</span>
								</label>
								<label class="mrb-switch">
									<input type="checkbox" name="settings[enable_multi_service]" value="1" <?php checked( $settings['enable_multi_service'] ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'انتخاب چند خدمت', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'مشتری بتواند چند خدمت را در یک نوبت بگیرد.', 'mr-booking' ); ?></small>
									</span>
								</label>
								<label class="mrb-switch">
									<input type="checkbox" name="settings[enable_staff_selection]" value="1" <?php checked( $settings['enable_staff_selection'] ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'انتخاب پرسنل توسط مشتری', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'نمایش لیست پرسنل در مرحله خدمت.', 'mr-booking' ); ?></small>
									</span>
								</label>
								<label class="mrb-switch">
									<input type="checkbox" name="settings[auto_assign_staff]" value="1" <?php checked( $settings['auto_assign_staff'] ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'تخصیص خودکار پرسنل', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'اگر مشتری انتخاب نکند، سیستم تخصیص می‌دهد.', 'mr-booking' ); ?></small>
									</span>
								</label>
							</div>
						</section>

					<?php elseif ( 'dashboard' === $tab ) : ?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'داشبورد رزرو', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__toggles">
								<label class="mrb-switch">
									<input type="checkbox" name="settings[dashboard_show_form_help]" value="1" <?php checked( ! empty( $settings['dashboard_show_form_help'] ) ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'نمایش راهنمای «فرم رزرو در سایت»', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'باکس راهنمای شورت‌کد و المنتور در بالای صفحه داشبورد رزرو', 'mr-booking' ); ?></small>
									</span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'اعلان زنده رزرو جدید', 'mr-booking' ); ?></h3>
							<p class="mrb-settings__hint"><?php esc_html_e( 'در صفحات MR Booking هر چند ثانیه یک‌بار رزروهای جدید بررسی و با صدا و toast نمایش داده می‌شوند.', 'mr-booking' ); ?></p>
							<div class="mrb-settings__grid">
								<label class="mrb-field">
									<span><?php esc_html_e( 'فاصله بررسی', 'mr-booking' ); ?></span>
									<select name="settings[admin_notify_poll_seconds]">
										<?php
										$poll_opts = array(
											0   => __( 'غیرفعال', 'mr-booking' ),
											15  => __( 'هر ۱۵ ثانیه', 'mr-booking' ),
											30  => __( 'هر ۳۰ ثانیه (پیش‌فرض)', 'mr-booking' ),
											60  => __( 'هر ۱ دقیقه', 'mr-booking' ),
											120 => __( 'هر ۲ دقیقه', 'mr-booking' ),
											300 => __( 'هر ۵ دقیقه', 'mr-booking' ),
										);
										$poll_val = (int) ( $settings['admin_notify_poll_seconds'] ?? 30 );
										foreach ( $poll_opts as $sec => $label ) :
											?>
											<option value="<?php echo esc_attr( (string) $sec ); ?>" <?php selected( $poll_val, $sec ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
									<small><?php esc_html_e( 'بعد از ذخیره، صفحهٔ باز MR Booking را یک‌بار رفرش کنید.', 'mr-booking' ); ?></small>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'ویجت پیشخوان وردپرس', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__toggles">
								<label class="mrb-switch">
									<input type="checkbox" name="settings[dashboard_widget_enabled]" value="1" <?php checked( ! empty( $settings['dashboard_widget_enabled'] ) ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'نمایش ویجت در پیشخوان وردپرس', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'خلاصه فعالیت MR Booking در صفحه اصلی پیشخوان ادمین', 'mr-booking' ); ?></small>
									</span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'موارد قابل نمایش', 'mr-booking' ); ?></h3>
							<p class="mrb-settings__hint"><?php esc_html_e( 'انتخاب کنید کدام آمارها در ویجت پیشخوان دیده شوند.', 'mr-booking' ); ?></p>
							<div class="mrb-settings__toggles">
								<?php foreach ( \MRBooking\Admin\Dashboard_Widget::items() as $item => $meta ) : ?>
									<?php $key = \MRBooking\Admin\Dashboard_Widget::setting_key( $item ); ?>
									<label class="mrb-switch">
										<input type="checkbox" name="settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
										<span class="mrb-switch__ui" aria-hidden="true"></span>
										<span class="mrb-switch__copy">
											<strong><?php echo esc_html( $meta['label'] ); ?></strong>
											<small><?php echo esc_html( $meta['desc'] ); ?></small>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</section>

					<?php elseif ( 'calendar' === $tab ) : ?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'حالت نمایش', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__choices">
								<label class="mrb-choice-card">
									<input type="radio" name="settings[calendar_mode]" value="jalali" <?php checked( $settings['calendar_mode'], 'jalali' ); ?> />
									<strong><?php esc_html_e( 'فقط شمسی', 'mr-booking' ); ?></strong>
									<span><?php esc_html_e( 'مناسب کسب‌وکارهای ایرانی', 'mr-booking' ); ?></span>
								</label>
								<label class="mrb-choice-card">
									<input type="radio" name="settings[calendar_mode]" value="gregorian" <?php checked( $settings['calendar_mode'], 'gregorian' ); ?> />
									<strong><?php esc_html_e( 'فقط میلادی', 'mr-booking' ); ?></strong>
									<span><?php esc_html_e( 'نمایش استاندارد بین‌المللی', 'mr-booking' ); ?></span>
								</label>
								<label class="mrb-choice-card">
									<input type="radio" name="settings[calendar_mode]" value="both" <?php checked( $settings['calendar_mode'], 'both' ); ?> />
									<strong><?php esc_html_e( 'هر دو هم‌زمان', 'mr-booking' ); ?></strong>
									<span><?php esc_html_e( 'نمایش جفت تاریخ در تقویم', 'mr-booking' ); ?></span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'تعطیلات', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid mrb-settings__grid--colors">
								<label class="mrb-color-field">
									<span class="mrb-field__label"><?php esc_html_e( 'رنگ تعطیلات', 'mr-booking' ); ?></span>
									<?php Helpers::color_input( 'settings[color_holiday]', (string) $settings['color_holiday'], array( 'default' => '#f0d875' ) ); ?>
								</label>
								<label class="mrb-color-field">
									<span class="mrb-field__label"><?php esc_html_e( 'پس‌زمینه تعطیلات', 'mr-booking' ); ?></span>
									<?php Helpers::color_input( 'settings[color_holiday_bg]', (string) $settings['color_holiday_bg'], array( 'default' => '#2a2418' ) ); ?>
								</label>
							</div>
							<div class="mrb-settings__toggles" style="margin-top:14px">
								<label class="mrb-switch">
									<input type="checkbox" name="settings[allow_holiday_booking]" value="1" <?php checked( $settings['allow_holiday_booking'] ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'اجازه رزرو در تعطیلات', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'اگر خاموش باشد، تعطیلات غیرقابل انتخاب هستند.', 'mr-booking' ); ?></small>
									</span>
								</label>
							</div>
						</section>

					<?php elseif ( 'rules' === $tab ) : ?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'زمان‌بندی', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid">
								<div class="mrb-field" style="grid-column:1/-1">
									<span class="mrb-field__label"><?php esc_html_e( 'ساعت‌های قابل انتخاب', 'mr-booking' ); ?></span>
									<p class="mrb-settings__hint" style="margin:6px 0 0">
										<?php esc_html_e( 'فاصله بین ساعت‌ها خودکار و برابر مدت خدمت انتخاب‌شده است. مثال: اگر کار از ۹:۰۰ شروع شود و خدمت ۱ ساعت و ۳۰ دقیقه باشد، گزینه‌ها می‌شوند ۹:۰۰، ۱۰:۳۰، ۱۲:۰۰ و … برای خدمت ۳۰ دقیقه‌ای: ۹:۰۰، ۹:۳۰، ۱۰:۰۰ و …', 'mr-booking' ); ?>
									</p>
									<input type="hidden" name="settings[booking_interval]" value="<?php echo esc_attr( (string) max( 5, (int) $settings['booking_interval'] ) ); ?>" />
								</div>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'حداقل زمان قبل از رزرو', 'mr-booking' ); ?></span>
									<input type="number" name="settings[min_notice_minutes]" value="<?php echo esc_attr( (string) $settings['min_notice_minutes'] ); ?>" />
									<span class="mrb-field__hint"><?php esc_html_e( 'تا چند دقیقه آینده ساعت خالی نشان داده نشود. مثلاً ۶۰ = یک ساعت بعد.', 'mr-booking' ); ?></span>
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'حداکثر روزهای آینده', 'mr-booking' ); ?></span>
									<input type="number" name="settings[max_days_ahead]" value="<?php echo esc_attr( (string) $settings['max_days_ahead'] ); ?>" />
									<span class="mrb-field__hint"><?php esc_html_e( 'تا چند روز جلوتر می‌توان رزرو کرد.', 'mr-booking' ); ?></span>
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'یادآوری (ساعت قبل)', 'mr-booking' ); ?></span>
									<input type="number" name="settings[reminder_hours_before]" value="<?php echo esc_attr( (string) $settings['reminder_hours_before'] ); ?>" />
									<span class="mrb-field__hint"><?php esc_html_e( 'چند ساعت قبل از نوبت، یادآوری SMS/ایمیل ارسال شود.', 'mr-booking' ); ?></span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'الزامات و محدودیت‌ها', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__toggles">
								<?php
								$rule_toggles = array(
									'allow_same_day'     => array( __( 'رزرو همان روز', 'mr-booking' ), __( 'اجازه ثبت نوبت برای امروز', 'mr-booking' ) ),
									'require_phone'      => array( __( 'الزام موبایل', 'mr-booking' ), __( 'شماره موبایل اجباری باشد', 'mr-booking' ) ),
									'require_email'      => array( __( 'الزام ایمیل', 'mr-booking' ), __( 'ایمیل اجباری باشد', 'mr-booking' ) ),
									'require_birth_date' => array( __( 'الزام تاریخ تولد', 'mr-booking' ), __( 'تاریخ تولد اجباری باشد', 'mr-booking' ) ),
									'require_staff'      => array( __( 'الزام انتخاب پرسنل', 'mr-booking' ), __( 'مشتری باید پرسنل را انتخاب کند', 'mr-booking' ) ),
									'reminder_enabled'   => array( __( 'ارسال یادآوری', 'mr-booking' ), __( 'یادآوری خودکار قبل از نوبت', 'mr-booking' ) ),
								);
								foreach ( $rule_toggles as $key => $meta ) :
									?>
									<label class="mrb-switch">
										<input type="checkbox" name="settings[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?> />
										<span class="mrb-switch__ui" aria-hidden="true"></span>
										<span class="mrb-switch__copy">
											<strong><?php echo esc_html( $meta[0] ); ?></strong>
											<small><?php echo esc_html( $meta[1] ); ?></small>
										</span>
									</label>
								<?php endforeach; ?>
							</div>
						</section>

					<?php elseif ( 'appearance' === $tab ) :
						$form_theme = (string) ( $settings['form_theme'] ?? 'dark' );
						if ( ! in_array( $form_theme, array_merge( \MRBooking\Settings\Color_Presets::theme_ids(), array( 'custom' ) ), true ) ) {
							$form_theme = 'dark';
						}
						$theme_cards = array(
							'dark'  => array(
								'title'    => __( 'قالب تیره', 'mr-booking' ),
								'desc'     => __( 'پس‌زمینه مشکی با طلایی و آبی تیره — پیش‌فرض برند', 'mr-booking' ),
								'swatches' => array( '#0A0A0A', '#111111', '#D4AF37', '#F0D875', '#142A38' ),
							),
							'light' => array(
								'title'    => __( 'قالب روشن', 'mr-booking' ),
								'desc'     => __( 'پس‌زمینه کرمی با متن سرمه‌ای و اکسنت طلایی', 'mr-booking' ),
								'swatches' => array( '#F7F5F0', '#FFFFFF', '#D4AF37', '#142A38', '#FFF8E7' ),
							),
						);
						?>
						<section class="mrb-settings__section mrb-settings__theme-picker">
							<header class="mrb-settings__palette-head">
								<div>
									<h3><?php esc_html_e( 'قالب رنگ فرم', 'mr-booking' ); ?></h3>
									<p class="mrb-settings__hint"><?php esc_html_e( 'یک قالب آماده انتخاب کنید یا رنگ‌ها را دستی در بخش‌های زیر تنظیم کنید.', 'mr-booking' ); ?></p>
								</div>
							</header>
							<input type="hidden" name="settings[form_theme]" id="mrb-form-theme" value="<?php echo esc_attr( $form_theme ); ?>" />
							<div class="mrb-theme-cards">
								<?php foreach ( $theme_cards as $theme_id => $theme_meta ) : ?>
									<article
										class="mrb-theme-card <?php echo $form_theme === $theme_id ? 'is-active' : ''; ?>"
										data-theme="<?php echo esc_attr( $theme_id ); ?>"
									>
										<div class="mrb-theme-card__preview" aria-hidden="true">
											<?php foreach ( $theme_meta['swatches'] as $swatch ) : ?>
												<span style="background-color: <?php echo esc_attr( $swatch ); ?>;"></span>
											<?php endforeach; ?>
										</div>
										<div class="mrb-theme-card__body">
											<h4><?php echo esc_html( $theme_meta['title'] ); ?></h4>
											<p><?php echo esc_html( $theme_meta['desc'] ); ?></p>
											<div class="mrb-theme-card__actions">
												<button type="button" class="button mrb-theme-card__preview-btn" data-theme="<?php echo esc_attr( $theme_id ); ?>">
													<?php esc_html_e( 'پیش‌نمایش در فرم', 'mr-booking' ); ?>
												</button>
												<a
													class="button button-primary mrb-theme-card__apply"
													href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mr_booking_apply_theme&theme=' . $theme_id ), 'mr_booking_apply_theme' ) ); ?>"
												>
													<?php esc_html_e( 'اعمال و ذخیره', 'mr-booking' ); ?>
												</a>
											</div>
										</div>
									</article>
								<?php endforeach; ?>
								<article class="mrb-theme-card mrb-theme-card--custom <?php echo 'custom' === $form_theme ? 'is-active' : ''; ?>" data-theme="custom">
									<div class="mrb-theme-card__preview mrb-theme-card__preview--custom" aria-hidden="true">
										<span style="background: linear-gradient(135deg, #D4AF37, #142A38);"></span>
										<span style="background: linear-gradient(135deg, #0A0A0A, #F7F5F0);"></span>
									</div>
									<div class="mrb-theme-card__body">
										<h4><?php esc_html_e( 'سفارشی', 'mr-booking' ); ?></h4>
										<p><?php esc_html_e( 'رنگ‌ها را خودتان تغییر داده‌اید — با ذخیره تنظیمات حفظ می‌شود.', 'mr-booking' ); ?></p>
									</div>
								</article>
							</div>
						</section>
						<section class="mrb-settings__section mrb-settings__palette">
							<header class="mrb-settings__palette-head">
								<div>
									<h3><?php esc_html_e( 'قالب و فونت', 'mr-booking' ); ?></h3>
									<p class="mrb-settings__hint"><?php esc_html_e( 'فونت و رنگ پس‌زمینه فرم رزرو در سایت.', 'mr-booking' ); ?></p>
								</div>
							</header>

							<div class="mrb-settings__palette-group" data-palette-group="font">
								<div class="mrb-settings__palette-group-head">
									<div>
										<h4><?php esc_html_e( 'فونت', 'mr-booking' ); ?></h4>
										<p><?php esc_html_e( 'فونت انتخاب‌شده روی تمام متن‌های فرم رزرو اعمال می‌شود.', 'mr-booking' ); ?></p>
									</div>
								</div>
								<div class="mrb-settings__palette-panel">
									<label class="mrb-field mrb-field--palette">
										<span class="mrb-field__label"><?php esc_html_e( 'فونت فرم', 'mr-booking' ); ?></span>
										<select name="settings[font_family]">
											<?php foreach ( \MRBooking\Settings\Settings::font_families() as $font_key => $font_meta ) : ?>
												<option value="<?php echo esc_attr( $font_key ); ?>" <?php selected( $settings['font_family'] ?? 'vazirmatn', $font_key ); ?>>
													<?php echo esc_html( $font_meta['label'] ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</label>
								</div>
							</div>

							<div class="mrb-settings__palette-group" data-palette-group="surface">
								<div class="mrb-settings__palette-group-head">
									<div>
										<h4><?php esc_html_e( 'پس‌زمینه', 'mr-booking' ); ?></h4>
										<p><?php esc_html_e( 'رنگ زمینه بیرونی و سطح داخلی کارت فرم', 'mr-booking' ); ?></p>
									</div>
								</div>
								<div class="mrb-settings__palette-grid">
									<?php foreach ( $theme_settings as $ck => $meta ) : ?>
										<?php Helpers::palette_color_card( $ck, $meta, $settings ); ?>
									<?php endforeach; ?>
								</div>
							</div>

							<div class="mrb-settings__palette-group" data-palette-group="gradient">
								<div class="mrb-settings__palette-group-head">
									<div>
										<h4><?php esc_html_e( 'گرادیان پس‌زمینه', 'mr-booking' ); ?></h4>
										<p><?php esc_html_e( 'هاله‌های رنگی گوشه بالا و پایین فرم', 'mr-booking' ); ?></p>
									</div>
								</div>
								<div class="mrb-settings__palette-panel mrb-settings__palette-panel--toggles">
									<label class="mrb-switch">
										<input type="checkbox" name="settings[bg_gradient_enabled]" value="1" <?php checked( ! empty( $settings['bg_gradient_enabled'] ) ); ?> />
										<span class="mrb-switch__ui" aria-hidden="true"></span>
										<span class="mrb-switch__copy">
											<strong><?php esc_html_e( 'فعال‌سازی گرادیان', 'mr-booking' ); ?></strong>
											<small><?php esc_html_e( 'اگر خاموش باشد فقط پس‌زمینه پایه نمایش داده می‌شود.', 'mr-booking' ); ?></small>
										</span>
									</label>
								</div>
								<div class="mrb-settings__palette-grid">
									<?php foreach ( $gradient_settings as $ck => $meta ) : ?>
										<?php Helpers::palette_color_card( $ck, $meta, $settings ); ?>
									<?php endforeach; ?>
								</div>
								<div class="mrb-settings__palette-mix">
									<label class="mrb-field mrb-field--palette">
										<span class="mrb-field__label"><?php esc_html_e( 'شفافیت گرادیان بالا (%)', 'mr-booking' ); ?></span>
										<input type="number" name="settings[bg_gradient_primary_mix]" min="0" max="100" step="1" value="<?php echo esc_attr( (string) ( $settings['bg_gradient_primary_mix'] ?? 12 ) ); ?>" />
										<small class="mrb-field__hint"><?php esc_html_e( 'هرچه بیشتر، هاله گوشه بالا پررنگ‌تر می‌شود.', 'mr-booking' ); ?></small>
									</label>
									<label class="mrb-field mrb-field--palette">
										<span class="mrb-field__label"><?php esc_html_e( 'شفافیت گرادیان پایین (%)', 'mr-booking' ); ?></span>
										<input type="number" name="settings[bg_gradient_accent_mix]" min="0" max="100" step="1" value="<?php echo esc_attr( (string) ( $settings['bg_gradient_accent_mix'] ?? 10 ) ); ?>" />
										<small class="mrb-field__hint"><?php esc_html_e( 'هرچه بیشتر، هاله گوشه پایین پررنگ‌تر می‌شود.', 'mr-booking' ); ?></small>
									</label>
								</div>
							</div>
						</section>
						<section class="mrb-settings__section mrb-settings__palette">
							<header class="mrb-settings__palette-head">
								<div>
									<h3><?php esc_html_e( 'پالت رنگ فرم', 'mr-booking' ); ?></h3>
									<p class="mrb-settings__hint"><?php esc_html_e( 'این رنگ‌ها فقط روی فرم رزرو در سایت اعمال می‌شوند.', 'mr-booking' ); ?></p>
								</div>
							</header>
							<?php foreach ( $color_groups as $group_key => $group ) : ?>
								<div class="mrb-settings__palette-group" data-palette-group="<?php echo esc_attr( $group_key ); ?>">
									<div class="mrb-settings__palette-group-head">
										<div>
											<h4><?php echo esc_html( $group['title'] ); ?></h4>
											<p><?php echo esc_html( $group['desc'] ); ?></p>
										</div>
									</div>
									<div class="mrb-settings__palette-grid">
										<?php foreach ( $group['keys'] as $ck ) : ?>
											<?php
											if ( empty( $colors[ $ck ] ) ) {
												continue;
											}
											Helpers::palette_color_card( $ck, $colors[ $ck ], $settings );
											?>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</section>

					<?php elseif ( 'texts' === $tab ) : ?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'متن‌های قابل ویرایش', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid">
								<?php foreach ( $text_labels as $tk => $label ) : ?>
									<label class="mrb-field">
										<span class="mrb-field__label"><?php echo esc_html( $label ); ?></span>
										<input type="text" name="settings[<?php echo esc_attr( $tk ); ?>]" value="<?php echo esc_attr( (string) ( $settings[ $tk ] ?? '' ) ); ?>" />
									</label>
								<?php endforeach; ?>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'Placeholder فیلدهای فرم', 'mr-booking' ); ?></h3>
							<p class="description"><?php esc_html_e( 'متن راهنمای داخل inputها قبل از تایپ کاربر.', 'mr-booking' ); ?></p>
							<div class="mrb-settings__grid">
								<?php foreach ( $placeholder_labels as $pk => $label ) : ?>
									<label class="mrb-field">
										<span class="mrb-field__label"><?php echo esc_html( $label ); ?></span>
										<input type="text" name="settings[<?php echo esc_attr( $pk ); ?>]" value="<?php echo esc_attr( (string) ( $settings[ $pk ] ?? '' ) ); ?>" />
									</label>
								<?php endforeach; ?>
							</div>
						</section>

					<?php elseif ( 'sms' === $tab ) : ?>
						<section class="mrb-settings__section">
							<div class="mrb-settings__toggles">
								<label class="mrb-switch">
									<input type="checkbox" name="settings[sms_enabled]" value="1" <?php checked( $settings['sms_enabled'] ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'فعال‌سازی پیامک', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'ارسال اعلان‌های SMS برای مشتری و ادمین', 'mr-booking' ); ?></small>
									</span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'پیکربندی سرویس‌دهنده', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid">
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'سرویس‌دهنده', 'mr-booking' ); ?></span>
									<select name="settings[sms_provider]">
										<?php foreach ( $providers as $slug => $provider ) : ?>
											<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $settings['sms_provider'], $slug ); ?>><?php echo esc_html( $provider->label() ); ?></option>
										<?php endforeach; ?>
									</select>
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'API Key', 'mr-booking' ); ?></span>
									<input type="text" name="settings[sms_api_key]" value="<?php echo esc_attr( (string) $settings['sms_api_key'] ); ?>" autocomplete="off" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'نام کاربری', 'mr-booking' ); ?></span>
									<input type="text" name="settings[sms_username]" value="<?php echo esc_attr( (string) $settings['sms_username'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'رمز عبور', 'mr-booking' ); ?></span>
									<input type="password" name="settings[sms_password]" value="<?php echo esc_attr( (string) $settings['sms_password'] ); ?>" autocomplete="new-password" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'شماره فرستنده', 'mr-booking' ); ?></span>
									<input type="text" name="settings[sms_sender]" value="<?php echo esc_attr( (string) $settings['sms_sender'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'API URL', 'mr-booking' ); ?></span>
									<input type="url" name="settings[sms_api_url]" value="<?php echo esc_attr( (string) $settings['sms_api_url'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'موبایل ادمین', 'mr-booking' ); ?></span>
									<input type="text" name="settings[sms_admin_phone]" value="<?php echo esc_attr( (string) $settings['sms_admin_phone'] ); ?>" placeholder="09xxxxxxxxx" />
									<span class="mrb-field__hint"><?php esc_html_e( 'با ثبت هر رزرو جدید، پیامک اطلاع‌رسانی به این شماره ارسال می‌شود.', 'mr-booking' ); ?></span>
								</label>
								<label class="mrb-field" style="grid-column:1/-1">
									<span class="mrb-field__label"><?php esc_html_e( 'سایر موبایل‌های دریافت‌کننده', 'mr-booking' ); ?></span>
									<textarea name="settings[notify_phones]" rows="3" placeholder="09xxxxxxxxx&#10;09xxxxxxxxx"><?php echo esc_textarea( (string) ( $settings['notify_phones'] ?? '' ) ); ?></textarea>
									<span class="mrb-field__hint"><?php esc_html_e( 'هر خط یا با ویرگول جدا کنید. برای مدیر یا پرسنل‌هایی که باید از رزرو جدید باخبر شوند.', 'mr-booking' ); ?></span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section mrb-sms-connect-section">
							<?php
							$current_provider   = (string) ( $settings['sms_provider'] ?? 'kavenegar' );
							$current_hint       = (string) ( $sms_test_hints[ $current_provider ] ?? '' );
							$show_credit_badge  = ! empty( $sms_credit_support[ $current_provider ] );
							$cached_credit_val  = (
								! empty( $sms_account_credit['ok'] )
								&& (string) ( $sms_account_credit['provider'] ?? '' ) === $current_provider
								&& isset( $sms_account_credit['credit'] )
							)
								? (float) $sms_account_credit['credit']
								: null;
							?>
							<div class="mrb-sms-connect">
								<div class="mrb-sms-connect__icon-wrap" aria-hidden="true">
									<span class="dashicons dashicons-smartphone"></span>
								</div>
								<div class="mrb-sms-connect__body">
									<div class="mrb-sms-connect__head">
										<h3><?php esc_html_e( 'اتصال و اعتبار پیامک', 'mr-booking' ); ?></h3>
										<p class="mrb-sms-connect__hint" id="mrb-sms-test-hint"><?php echo esc_html( $current_hint ); ?></p>
									</div>
									<div
										class="mrb-sms-connect__credit"
										id="mrb-sms-credit-badge"
										data-provider="<?php echo esc_attr( $current_provider ); ?>"
										<?php echo $show_credit_badge ? '' : 'hidden'; ?>
										<?php echo null === $cached_credit_val ? 'data-empty="1"' : ''; ?>
									>
										<span class="mrb-sms-connect__credit-label"><?php esc_html_e( 'اعتبار فعلی', 'mr-booking' ); ?></span>
										<strong id="mrb-sms-credit-value">
											<?php
											if ( null !== $cached_credit_val ) {
												echo esc_html( SMS_Manager::format_credit( $cached_credit_val ) );
												echo ' <small>' . esc_html__( 'ریال', 'mr-booking' ) . '</small>';
											} else {
												esc_html_e( '—', 'mr-booking' );
											}
											?>
										</strong>
									</div>
									<div class="mrb-sms-connect__actions">
										<button type="button" class="button button-secondary" id="mrb-test-sms-connection">
											<span class="dashicons dashicons-update" aria-hidden="true"></span>
											<?php esc_html_e( 'تست اتصال', 'mr-booking' ); ?>
										</button>
									</div>
									<div id="mrb-sms-test-result" class="mrb-sms-test__result" aria-live="polite" hidden></div>
								</div>
							</div>
							<script type="application/json" id="mrb-sms-test-hints"><?php echo wp_json_encode( $sms_test_hints, JSON_UNESCAPED_UNICODE ); ?></script>
							<script type="application/json" id="mrb-sms-credit-support"><?php echo wp_json_encode( $sms_credit_support ); ?></script>
						</section>

					<?php elseif ( 'email' === $tab ) : ?>
						<section class="mrb-settings__section">
							<div class="mrb-settings__toggles">
								<label class="mrb-switch">
									<input type="checkbox" name="settings[email_enabled]" value="1" <?php checked( $settings['email_enabled'] ); ?> />
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'فعال‌سازی ایمیل', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'ارسال اعلان‌های HTML از طریق wp_mail', 'mr-booking' ); ?></small>
									</span>
								</label>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'فرستنده', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__grid">
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'نام فرستنده', 'mr-booking' ); ?></span>
									<input type="text" name="settings[email_from_name]" value="<?php echo esc_attr( (string) $settings['email_from_name'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'ایمیل فرستنده', 'mr-booking' ); ?></span>
									<input type="email" name="settings[email_from_email]" value="<?php echo esc_attr( (string) $settings['email_from_email'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'Reply-To', 'mr-booking' ); ?></span>
									<input type="email" name="settings[email_reply_to]" value="<?php echo esc_attr( (string) $settings['email_reply_to'] ); ?>" />
								</label>
								<label class="mrb-field">
									<span class="mrb-field__label"><?php esc_html_e( 'ایمیل ادمین', 'mr-booking' ); ?></span>
									<input type="email" name="settings[email_admin]" value="<?php echo esc_attr( (string) $settings['email_admin'] ); ?>" />
									<span class="mrb-field__hint"><?php esc_html_e( 'با هر رزرو جدید، جزئیات برای تأیید به این ایمیل می‌رود.', 'mr-booking' ); ?></span>
								</label>
								<label class="mrb-field" style="grid-column:1/-1">
									<span class="mrb-field__label"><?php esc_html_e( 'سایر ایمیل‌های دریافت‌کننده', 'mr-booking' ); ?></span>
									<textarea name="settings[notify_emails]" rows="3" placeholder="manager@example.com&#10;staff@example.com"><?php echo esc_textarea( (string) ( $settings['notify_emails'] ?? '' ) ); ?></textarea>
									<span class="mrb-field__hint"><?php esc_html_e( 'ایمیل افرادی که باید از رزرو جدید باخبر شوند (هر خط یا با ویرگول).', 'mr-booking' ); ?></span>
								</label>
							</div>
						</section>
					<?php elseif ( 'premium' === $tab ) : ?>
						<?php
						$premium_active = \MRBooking\Premium\License::is_active();
						?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'فعال‌سازی پریمیوم', 'mr-booking' ); ?></h3>
							<p class="mrb-settings__hint">
								<?php esc_html_e( 'با وارد کردن کد لایسنس، امکان مخفی‌کردن برند «MR Booking» فقط در فرم رزرو عمومی فعال می‌شود.', 'mr-booking' ); ?>
							</p>
							<div class="mrb-settings__grid">
								<label class="mrb-field" style="grid-column:1/-1">
									<span class="mrb-field__label"><?php esc_html_e( 'کد فعال‌سازی پریمیوم', 'mr-booking' ); ?></span>
									<input type="text" name="settings[premium_key]" value="<?php echo esc_attr( (string) ( $settings['premium_key'] ?? '' ) ); ?>" autocomplete="off" placeholder="<?php esc_attr_e( 'کد لایسنس را وارد کنید', 'mr-booking' ); ?>" />
									<span class="mrb-field__hint"><?php esc_html_e( 'پس از ذخیره، در صورت معتبر بودن کد، وضعیت پریمیوم فعال می‌شود.', 'mr-booking' ); ?></span>
								</label>
							</div>
							<?php if ( $premium_active ) : ?>
								<div class="mrb-premium-badge is-active">
									<span class="dashicons dashicons-yes-alt"></span>
									<strong><?php esc_html_e( 'پریمیوم فعال است', 'mr-booking' ); ?></strong>
								</div>
							<?php else : ?>
								<div class="mrb-premium-badge">
									<span class="dashicons dashicons-lock"></span>
									<strong><?php esc_html_e( 'پریمیوم غیرفعال', 'mr-booking' ); ?></strong>
									<span><?php esc_html_e( 'برای استفاده از امکانات ویژه، کد معتبر وارد کنید.', 'mr-booking' ); ?></span>
								</div>
							<?php endif; ?>
						</section>

						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'وایت‌لیبل', 'mr-booking' ); ?></h3>
							<div class="mrb-settings__toggles">
								<label class="mrb-switch <?php echo $premium_active ? '' : 'is-locked'; ?>">
									<input
										type="checkbox"
										name="settings[hide_branding]"
										value="1"
										<?php checked( ! empty( $settings['hide_branding'] ) && $premium_active ); ?>
									/>
									<span class="mrb-switch__ui" aria-hidden="true"></span>
									<span class="mrb-switch__copy">
										<strong><?php esc_html_e( 'مخفی کردن برند MR Booking در فرم رزرو', 'mr-booking' ); ?> <em class="mrb-premium-tag"><?php esc_html_e( 'پریمیوم', 'mr-booking' ); ?></em></strong>
										<small>
											<?php
											echo $premium_active
												? esc_html__( 'متن «MR Booking» بالای فرم رزرو (شورت‌کد و المنتور) برای بازدیدکنندگان سایت نمایش داده نمی‌شود. منو و تنظیمات پنل بدون تغییر می‌مانند.', 'mr-booking' )
												: esc_html__( 'ابتدا کد پریمیوم را وارد و ذخیره کنید؛ سپس این گزینه اعمال می‌شود. اگر هم‌زمان با کد ذخیره شود هم فعال می‌گردد.', 'mr-booking' );
											?>
										</small>
									</span>
								</label>
							</div>
						</section>
					<?php elseif ( 'access' === $tab ) : ?>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'نقش‌های آماده', 'mr-booking' ); ?></h3>
							<p class="mrb-settings__hint"><?php esc_html_e( 'کاربران را از منوی «کاربران → افزودن» بسازید و یکی از نقش‌های زیر را انتخاب کنید. این نقش‌ها فقط برای مدیریت رزرو طراحی شده‌اند و بقیه منوی وردپرس را نمی‌بینند.', 'mr-booking' ); ?></p>
							<div class="mrb-access-roles">
								<?php foreach ( Roles::definitions() as $role_slug => $role ) : ?>
									<article class="mrb-access-role">
										<h4><?php echo esc_html( $role['label'] ); ?></h4>
										<p><?php echo esc_html( $role['desc'] ); ?></p>
										<ul>
											<?php foreach ( $role['caps'] as $cap ) : ?>
												<?php $section = Capabilities::sections()[ $cap ] ?? null; ?>
												<li><?php echo esc_html( $section['label'] ?? $cap ); ?></li>
											<?php endforeach; ?>
										</ul>
										<p class="description"><code><?php echo esc_html( $role_slug ); ?></code></p>
									</article>
								<?php endforeach; ?>
							</div>
						</section>
						<section class="mrb-settings__section">
							<h3><?php esc_html_e( 'راهنمای سریع', 'mr-booking' ); ?></h3>
							<ol class="mrb-access-steps">
								<li><?php esc_html_e( 'کاربران → افزودن — نام کاربری و رمز بسازید.', 'mr-booking' ); ?></li>
								<li><?php esc_html_e( 'نقش «منشی رزرو» را برای پذیرش تلفنی انتخاب کنید (نوبت‌ها + رزرو تلفنی).', 'mr-booking' ); ?></li>
								<li><?php esc_html_e( 'پس از ورود، فقط منوی رزروها نمایش داده می‌شود و به اولین بخش مجاز هدایت می‌شود.', 'mr-booking' ); ?></li>
								<li><?php esc_html_e( 'برای دسترسی سفارشی از افزونه‌هایی مثل User Role Editor استفاده کنید.', 'mr-booking' ); ?></li>
							</ol>
							<p class="description">
								<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'افزودن کاربر جدید', 'mr-booking' ); ?></a>
								<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>" class="button button-link"><?php esc_html_e( 'مدیریت کاربران', 'mr-booking' ); ?></a>
							</p>
						</section>
					<?php elseif ( 'github' === $tab ) : ?>
						<section class="mrb-settings__section mrb-settings__section--github">
							<h3><?php esc_html_e( 'پروژه متن‌باز MR Booking', 'mr-booking' ); ?></h3>
							<p class="mrb-settings__hint">
								<?php esc_html_e( 'اگر از این افزونه راضی هستید، با ستاره دادن در گیت‌هاب از توسعه آن حمایت کنید.', 'mr-booking' ); ?>
							</p>
							<div class="mrb-github-card">
								<div class="mrb-github-card__icon" aria-hidden="true">
									<span class="dashicons dashicons-star-filled"></span>
								</div>
								<div class="mrb-github-card__body">
									<strong><?php esc_html_e( 'rezaansarirad/mr-booking', 'mr-booking' ); ?></strong>
									<p><?php esc_html_e( 'سورس کامل، گزارش باگ، پیشنهاد قابلیت و به‌روزرسانی‌ها در گیت‌هاب.', 'mr-booking' ); ?></p>
									<a class="button button-primary mrb-github-card__btn" href="https://github.com/rezaansarirad/mr-booking" target="_blank" rel="noopener noreferrer">
										<span class="dashicons dashicons-external"></span>
										<?php esc_html_e( 'رفتن به گیت‌هاب و ستاره دادن', 'mr-booking' ); ?>
									</a>
								</div>
							</div>
						</section>
					<?php endif; ?>
				</div>

				<footer class="mrb-settings__footer">
					<button type="submit" class="mrb-btn mrb-btn--primary">
						<span class="dashicons dashicons-saved"></span>
						<?php esc_html_e( 'ذخیره تنظیمات', 'mr-booking' ); ?>
					</button>
					<span class="mrb-settings__footer-hint"><?php esc_html_e( 'تغییرات فقط پس از ذخیره اعمال می‌شوند.', 'mr-booking' ); ?></span>
				</footer>
			</div>
		</form>
	</div>
</div>
