<?php
/**
 * Frontend booking form shell.
 *
 * @package MRBooking
 * @var array $settings
 * @var int   $preselect_service
 * @var int   $preselect_staff
 */

defined( 'ABSPATH' ) || exit;
?>
<div
	id="mr-booking-app"
	class="mrb"
	dir="rtl"
	data-service="<?php echo esc_attr( (string) $preselect_service ); ?>"
	data-staff="<?php echo esc_attr( (string) $preselect_staff ); ?>"
	aria-live="polite"
>
	<div class="mrb__shell">
			<header class="mrb__header">
				<?php if ( ! \MRBooking\Premium\License::hide_branding() ) : ?>
					<p class="mrb__brand">MR Booking</p>
				<?php endif; ?>
				<h2 class="mrb__title"><?php echo esc_html( (string) $settings['text_title'] ); ?></h2>
			</header>

		<nav class="mrb__steps" aria-label="<?php esc_attr_e( 'مراحل رزرو', 'mr-booking' ); ?>">
			<button type="button" class="mrb__step is-active" data-step="1"><span>۱</span><?php echo esc_html( (string) $settings['text_step_personal'] ); ?></button>
			<button type="button" class="mrb__step" data-step="2"><span>۲</span><?php echo esc_html( (string) $settings['text_step_service'] ); ?></button>
			<button type="button" class="mrb__step" data-step="3"><span>۳</span><?php echo esc_html( (string) $settings['text_step_date'] ); ?></button>
			<button type="button" class="mrb__step" data-step="4"><span>۴</span><?php echo esc_html( (string) $settings['text_step_time'] ); ?></button>
			<button type="button" class="mrb__step" data-step="5"><span>۵</span><?php echo esc_html( (string) $settings['text_step_confirm'] ); ?></button>
		</nav>

		<div class="mrb__body">
			<section class="mrb__panel is-active" data-panel="1">
				<div class="mrb__fields">
					<label class="mrb-field" data-field="first_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
						<input type="text" name="first_name" id="mrb-first-name" autocomplete="given-name" maxlength="100" minlength="2" required enterkeyhint="next" aria-required="true" placeholder="<?php echo esc_attr( (string) ( $settings['text_ph_first_name'] ?? '' ) ); ?>" />
						<span class="mrb-field__error" id="mrb-err-first_name" hidden></span>
					</label>
					<label class="mrb-field" data-field="last_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
						<input type="text" name="last_name" id="mrb-last-name" autocomplete="family-name" maxlength="100" minlength="2" required enterkeyhint="next" aria-required="true" placeholder="<?php echo esc_attr( (string) ( $settings['text_ph_last_name'] ?? '' ) ); ?>" />
						<span class="mrb-field__error" id="mrb-err-last_name" hidden></span>
					</label>
					<label class="mrb-field" data-field="phone">
						<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
						<input type="tel" name="phone" id="mrb-phone" inputmode="numeric" autocomplete="tel" placeholder="<?php echo esc_attr( (string) ( $settings['text_ph_phone'] ?? '09121234567' ) ); ?>" maxlength="13" required pattern="09[0-9]{9}" enterkeyhint="next" aria-required="true" aria-describedby="mrb-hint-phone" />
						<span class="mrb-field__hint" id="mrb-hint-phone"><?php esc_html_e( 'مثال: ۰۹۱۲۱۲۳۴۵۶۷', 'mr-booking' ); ?></span>
						<span class="mrb-field__error" id="mrb-err-phone" hidden></span>
					</label>
					<label class="mrb-field mrb-email-field" data-field="email">
						<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?><abbr class="mrb-req mrb-req--email" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>" hidden>*</abbr></span>
						<input type="email" name="email" id="mrb-email" autocomplete="email" maxlength="190" enterkeyhint="next" placeholder="<?php echo esc_attr( (string) ( $settings['text_ph_email'] ?? '' ) ); ?>" />
						<span class="mrb-field__error" id="mrb-err-email" hidden></span>
					</label>
					<?php
					$prefix = 'mrb-birth';
					$input_name = 'birth_date';
					$show_label = true;
					$show_required = false;
					$error_id = 'mrb-err-birth_date';
					$birth_placeholder = (string) ( $settings['text_ph_birth_date'] ?? '' );
					include MR_BOOKING_PATH . 'templates/partials/birth-date-field.php';
					?>
					<fieldset class="mrb__booking-for">
						<legend><?php esc_html_e( 'رزرو برای', 'mr-booking' ); ?></legend>
						<label><input type="radio" name="booking_for" value="myself" checked /> <?php echo esc_html( (string) $settings['text_booking_for_myself'] ); ?></label>
						<label><input type="radio" name="booking_for" value="child" /> <?php echo esc_html( (string) $settings['text_booking_for_child'] ); ?></label>
						<label><input type="radio" name="booking_for" value="other" /> <?php echo esc_html( (string) $settings['text_booking_for_other'] ); ?></label>
					</fieldset>
					<label class="mrb-field mrb-for-name is-hidden" data-field="booking_for_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام فرد', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
						<input type="text" name="booking_for_name" id="mrb-booking-for-name" maxlength="120" enterkeyhint="next" placeholder="<?php echo esc_attr( (string) ( $settings['text_ph_booking_for_name'] ?? '' ) ); ?>" />
						<span class="mrb-field__error" id="mrb-err-booking_for_name" hidden></span>
					</label>
				</div>
			</section>

			<section class="mrb__panel" data-panel="2">
				<div class="mrb__staff is-hidden" id="mrb-staff-wrap">
					<label class="mrb-field mrb-field--staff" data-field="staff" for="mrb-staff-select">
						<span class="mrb-field__label" id="mrb-staff-title"><?php esc_html_e( 'انتخاب پرسنل', 'mr-booking' ); ?></span>
						<select id="mrb-staff-select" class="mrb__staff-select" aria-describedby="mrb-staff-hint mrb-err-staff"></select>
						<span class="mrb-field__hint" id="mrb-staff-hint"><?php esc_html_e( 'پرسنل را انتخاب کنید تا خدمات مرتبط نمایش داده شود.', 'mr-booking' ); ?></span>
						<span class="mrb-field__error" id="mrb-err-staff" hidden></span>
					</label>
				</div>
				<h3 class="mrb__section-title" id="mrb-services-title"><?php esc_html_e( 'انتخاب خدمت', 'mr-booking' ); ?></h3>
				<div class="mrb__services" id="mrb-services" role="list" aria-describedby="mrb-err-services"></div>
				<p class="mrb__hint" id="mrb-services-empty" hidden><?php esc_html_e( 'برای این پرسنل خدمتی تعریف نشده است.', 'mr-booking' ); ?></p>
				<span class="mrb-field__error mrb-field__error--block" id="mrb-err-services" hidden></span>
			</section>

			<section class="mrb__panel" data-panel="3">
				<div class="mrb__selected-date" id="mrb-selected-date" hidden>
					<strong class="mrb__selected-date__value" data-selected-date-text></strong>
					<button type="button" class="mrb__selected-date__edit" id="mrb-edit-date"><?php esc_html_e( 'تغییر', 'mr-booking' ); ?></button>
				</div>

				<div class="mrb__cal-head">
					<button type="button" class="mrb__icon-btn mrb__icon-btn--prev" id="mrb-prev-month" aria-label="<?php esc_attr_e( 'ماه قبل', 'mr-booking' ); ?>"></button>
					<div class="mrb__cal-title" id="mrb-cal-title"></div>
					<button type="button" class="mrb__icon-btn mrb__icon-btn--next" id="mrb-next-month" aria-label="<?php esc_attr_e( 'ماه بعد', 'mr-booking' ); ?>"></button>
				</div>
				<div class="mrb__weekdays" id="mrb-weekdays"></div>
				<div class="mrb__calendar-scroll" id="mrb-calendar-scroll">
					<div class="mrb__calendar" id="mrb-calendar" role="grid" aria-describedby="mrb-err-date"></div>
				</div>
				<p class="mrb__hint" id="mrb-cal-hint"></p>
				<span class="mrb-field__error mrb-field__error--block" id="mrb-err-date" hidden></span>
			</section>

			<section class="mrb__panel" data-panel="4">
				<div class="mrb__selected-date mrb__selected-date--compact" id="mrb-selected-date-time" hidden>
					<strong class="mrb__selected-date__value" data-selected-date-text></strong>
				</div>
				<div class="mrb__slots" id="mrb-slots" role="listbox" aria-describedby="mrb-err-time"></div>
				<p class="mrb__hint" id="mrb-slots-hint"></p>
				<span class="mrb-field__error mrb-field__error--block" id="mrb-err-time" hidden></span>
			</section>

			<section class="mrb__panel" data-panel="5">
				<div class="mrb__summary" id="mrb-summary"></div>
			</section>

			<section class="mrb__panel" data-panel="success" id="mrb-success" hidden>
				<div class="mrb__success">
					<div class="mrb__success-icon" aria-hidden="true">✓</div>
					<h3 id="mrb-success-title"></h3>
					<div id="mrb-success-body"></div>
				</div>
			</section>
		</div>

		<p class="mrb__error" id="mrb-error" role="alert" hidden></p>

		<footer class="mrb__footer">
			<button type="button" class="mrb__btn mrb__btn--ghost" id="mrb-prev"><?php echo esc_html( (string) $settings['text_btn_prev'] ); ?></button>
			<button type="button" class="mrb__btn mrb__btn--primary" id="mrb-next"><span class="mrb__btn__label"><?php echo esc_html( (string) $settings['text_btn_next'] ); ?></span></button>
		</footer>
	</div>

	<?php
	$prefix = 'mrb-birth';
	include MR_BOOKING_PATH . 'templates/partials/birth-date-dialog.php';
	?>
</div>
