<?php
/**
 * Phone booking admin template.
 *
 * @package MRBooking
 * @var array $settings
 * @var list<object> $services
 * @var list<object> $staff
 * @var array<string, string> $statuses
 */

defined( 'ABSPATH' ) || exit;

$has_staff = ! empty( $staff );
?>
<div class="wrap mrb-admin mrb-phone-book-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'رزرو تلفنی', 'mr-booking' ); ?></h1>
			<p class="mrb-phone-book-page__lead">
				<?php esc_html_e( 'ثبت دستی نوبت تلفنی — ساعت‌ها همانند فرم سایت محاسبه می‌شوند و پس از ثبت، آن بازه از رزروهای آنلاین حذف می‌شود.', 'mr-booking' ); ?>
			</p>
		</div>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments' ) ); ?>">
			<?php esc_html_e( 'لیست نوبت‌ها', 'mr-booking' ); ?>
		</a>
	</header>

	<div id="mrb-phone-book-app" class="mrb-phone-book">
		<div class="mrb-phone-book__layout">
			<section class="mrb-panel mrb-phone-book__panel">
				<h2><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></h2>

				<label class="mrb-field mrb-phone-book__search-wrap">
					<span class="mrb-field__label"><?php esc_html_e( 'جستجوی مشتری', 'mr-booking' ); ?></span>
					<input type="search" id="mrb-customer-search" autocomplete="off" placeholder="<?php esc_attr_e( 'نام، نام خانوادگی یا شماره موبایل...', 'mr-booking' ); ?>" />
					<small class="mrb-field__hint"><?php esc_html_e( 'حداقل ۲ حرف — مشتریان قبلی سایت نمایش داده می‌شوند.', 'mr-booking' ); ?></small>
					<div id="mrb-customer-results" class="mrb-autocomplete" hidden></div>
				</label>

				<input type="hidden" id="mrb-customer-id" value="" />

				<div class="mrb-settings__grid mrb-phone-book__customer-fields">
					<label class="mrb-field" data-field="first_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?> *</span>
						<input type="text" id="mrb-first-name" autocomplete="off" />
						<span class="mrb-field__error" id="mrb-err-first_name" hidden></span>
					</label>
					<label class="mrb-field" data-field="last_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?> *</span>
						<input type="text" id="mrb-last-name" autocomplete="off" />
						<span class="mrb-field__error" id="mrb-err-last_name" hidden></span>
					</label>
					<label class="mrb-field" data-field="phone">
						<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?> *</span>
						<input type="tel" id="mrb-phone" inputmode="numeric" placeholder="09xxxxxxxxx" />
						<span class="mrb-field__error" id="mrb-err-phone" hidden></span>
					</label>
					<label class="mrb-field" data-field="email">
						<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></span>
						<input type="email" id="mrb-email" autocomplete="off" />
						<span class="mrb-field__error" id="mrb-err-email" hidden></span>
					</label>
					<?php
					$prefix = 'mrb-phone-birth';
					$input_name = '';
					$show_label = true;
					$show_required = false;
					$error_id = 'mrb-err-birth_date';
					include MR_BOOKING_PATH . 'templates/partials/birth-date-field.php';
					?>
				</div>

				<h2><?php esc_html_e( 'خدمت و پرسنل', 'mr-booking' ); ?></h2>

				<?php if ( empty( $services ) ) : ?>
					<p class="mrb-empty"><?php esc_html_e( 'ابتدا حداقل یک خدمت فعال تعریف کنید.', 'mr-booking' ); ?></p>
				<?php else : ?>
					<div class="mrb-check-grid mrb-phone-book__services" data-field="services" role="group">
						<?php foreach ( $services as $svc ) : ?>
							<label class="mrb-check-chip">
								<input type="checkbox" class="mrb-service-check" value="<?php echo esc_attr( (string) $svc->id ); ?>" <?php checked( ! empty( $settings['enable_multi_service'] ) ? false : false ); ?> />
								<span class="mrb-check-chip__label"><?php echo esc_html( $svc->name ); ?></span>
								<span class="mrb-check-chip__meta"><?php echo esc_html( \MRBooking\Helpers::format_duration( (int) $svc->duration ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<span class="mrb-field__error mrb-field__error--block" id="mrb-err-services" hidden></span>
				<?php endif; ?>

				<?php if ( $has_staff ) : ?>
					<label class="mrb-field" style="margin-top:12px">
						<span class="mrb-field__label"><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></span>
						<select id="mrb-staff-id">
							<option value=""><?php esc_html_e( '— انتخاب پرسنل —', 'mr-booking' ); ?></option>
							<?php foreach ( $staff as $member ) : ?>
								<option value="<?php echo esc_attr( (string) $member->id ); ?>">
									<?php echo esc_html( \MRBooking\Staff\Staff_Repository::display_name( $member ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<label class="mrb-field" style="margin-top:12px">
					<span class="mrb-field__label"><?php esc_html_e( 'یادداشت داخلی', 'mr-booking' ); ?></span>
					<textarea id="mrb-notes" rows="2" placeholder="<?php esc_attr_e( 'مثلاً: تماس تلفنی، درخواست خاص...', 'mr-booking' ); ?>"></textarea>
				</label>

				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'وضعیت رزرو', 'mr-booking' ); ?></span>
					<select id="mrb-booking-status">
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, 'confirmed' ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</section>

			<section class="mrb-panel mrb-phone-book__panel mrb-phone-book__schedule">
				<h2><?php esc_html_e( 'تاریخ و ساعت', 'mr-booking' ); ?></h2>

				<div class="mrb-phone-book__cal" data-field="date">
					<div class="mrb__cal-head">
						<button type="button" class="mrb__icon-btn mrb__icon-btn--prev" id="mrb-prev-month" aria-label="<?php esc_attr_e( 'ماه قبل', 'mr-booking' ); ?>"></button>
						<div class="mrb__cal-title" id="mrb-cal-title"></div>
						<button type="button" class="mrb__icon-btn mrb__icon-btn--next" id="mrb-next-month" aria-label="<?php esc_attr_e( 'ماه بعد', 'mr-booking' ); ?>"></button>
					</div>
					<div class="mrb__weekdays" id="mrb-weekdays"></div>
					<div class="mrb__calendar" id="mrb-calendar"></div>
					<p class="mrb__hint" id="mrb-cal-hint"></p>
					<span class="mrb-field__error mrb-field__error--block" id="mrb-err-date" hidden></span>
				</div>

				<div class="mrb-phone-book__slots-wrap" data-field="time">
					<h3><?php esc_html_e( 'ساعت', 'mr-booking' ); ?></h3>
					<div class="mrb__slots" id="mrb-slots"></div>
					<p class="mrb__hint" id="mrb-slots-hint"><?php esc_html_e( 'ابتدا خدمت و تاریخ را انتخاب کنید.', 'mr-booking' ); ?></p>
					<span class="mrb-field__error mrb-field__error--block" id="mrb-err-time" hidden></span>
				</div>

				<p class="mrb-phone-book__error" id="mrb-phone-error" role="alert" hidden></p>
				<p class="mrb-phone-book__success" id="mrb-phone-success" hidden></p>

				<button type="button" class="button button-primary button-hero" id="mrb-phone-submit" <?php disabled( empty( $services ) ); ?>>
					<?php esc_html_e( 'ثبت رزرو تلفنی', 'mr-booking' ); ?>
				</button>
			</section>
		</div>
	</div>

	<?php
	$prefix = 'mrb-phone-birth';
	include MR_BOOKING_PATH . 'templates/partials/birth-date-dialog.php';
	?>
</div>
