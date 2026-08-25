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
<?php $embed = isset( $embed ) ? (string) $embed : ''; ?>
<div
	id="mr-booking-app"
	class="mrb<?php echo $embed ? ' mrb--embedded mrb--embedded-' . esc_attr( $embed ) : ''; ?>"
	dir="rtl"
	data-service="<?php echo esc_attr( (string) $preselect_service ); ?>"
	data-staff="<?php echo esc_attr( (string) $preselect_staff ); ?>"
	data-deposit="<?php echo \MRBooking\Payments\Payment_Service::deposit_enabled() ? '1' : '0'; ?>"
	data-payment-result="<?php echo isset( $_GET['mrb_payment'] ) ? esc_attr( sanitize_key( wp_unslash( $_GET['mrb_payment'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"
	data-payment-code="<?php echo isset( $_GET['mrb_code'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['mrb_code'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"
	aria-live="polite"
>
	<div class="mrb__shell">
			<header class="mrb__header"<?php echo $embed ? ' hidden' : ''; ?>>
				<?php if ( ! \MRBooking\Premium\License::hide_branding() ) : ?>
					<p class="mrb__brand"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
				<?php endif; ?>
				<h2 class="mrb__title"><?php echo esc_html( (string) $settings['text_title'] ); ?></h2>
			</header>

		<nav class="mrb__steps" aria-label="<?php esc_attr_e( 'مراحل رزرو', 'mr-booking' ); ?>">
			<button type="button" class="mrb__step is-active" data-step="1"><span>۱</span><?php echo esc_html( (string) $settings['text_step_personal'] ); ?></button>
			<button type="button" class="mrb__step" data-step="2"><span>۲</span><?php echo esc_html( (string) $settings['text_step_service'] ); ?></button>
			<button type="button" class="mrb__step" data-step="3"><span>۳</span><?php echo esc_html( (string) $settings['text_step_date'] ); ?></button>
			<button type="button" class="mrb__step" data-step="4"><span>۴</span><?php echo esc_html( (string) $settings['text_step_time'] ); ?></button>
			<button type="button" class="mrb__step" data-step="5"><span>۵</span><?php echo esc_html( (string) $settings['text_step_confirm'] ); ?></button>
			<?php if ( \MRBooking\Payments\Payment_Service::deposit_enabled() ) : ?>
				<button type="button" class="mrb__step" data-step="6" id="mrb-step-payment"><span>۶</span><?php echo esc_html( (string) ( $settings['text_step_payment'] ?? 'پرداخت' ) ); ?></button>
			<?php endif; ?>
		</nav>

		<div class="mrb__body">
			<section class="mrb__panel is-active" data-panel="1">
				<?php if ( \MRBooking\Auth\Customer_Auth::is_enabled() ) : ?>
					<div class="mrb-auth" id="mrb-auth" data-mode="<?php echo esc_attr( \MRBooking\Auth\Customer_Auth::login_mode() ); ?>">
						<div class="mrb-auth__chip" id="mrb-auth-chip" hidden>
							<span class="mrb-auth__avatar" aria-hidden="true" data-auth-avatar></span>
							<span class="mrb-auth__who">
								<strong data-auth-name></strong>
								<span dir="ltr" data-auth-phone></span>
							</span>
							<span class="mrb-auth__links">
								<?php if ( \MRBooking\Auth\Customer_Auth::account_url() ) : ?>
									<a href="<?php echo esc_url( \MRBooking\Auth\Customer_Auth::account_url() ); ?>"><?php esc_html_e( 'حساب کاربری', 'mr-booking' ); ?></a>
								<?php endif; ?>
								<button type="button" class="mrb-auth__logout" id="mrb-auth-logout"><?php esc_html_e( 'خروج', 'mr-booking' ); ?></button>
							</span>
						</div>

						<div class="mrb-auth__prompt" id="mrb-auth-prompt" hidden>
							<p><?php esc_html_e( 'قبلاً رزرو کرده‌اید؟ با کد پیامکی وارد شوید تا اطلاعات‌تان پر شود.', 'mr-booking' ); ?></p>
							<button type="button" class="mrb__btn mrb__btn--ghost mrb__btn--small" id="mrb-auth-open" aria-expanded="false" aria-controls="mrb-auth-login"><?php esc_html_e( 'ورود با کد پیامکی', 'mr-booking' ); ?></button>
						</div>

						<div class="mrb-auth__login" id="mrb-auth-login" hidden>
							<?php
							$prefix  = 'mrb-form-otp';
							$intro   = \MRBooking\Auth\Customer_Auth::is_required()
								? __( 'برای ثبت رزرو، شماره موبایل خود را تأیید کنید.', 'mr-booking' )
								: __( 'شماره موبایل خود را وارد کنید؛ کد تأیید پیامک می‌شود.', 'mr-booking' );
							$compact = true;
							include MR_BOOKING_PATH . 'templates/partials/otp-login.php';
							?>
							<?php if ( ! \MRBooking\Auth\Customer_Auth::is_required() ) : ?>
								<button type="button" class="mrb-otp__link mrb-auth__guest" id="mrb-auth-guest"><?php esc_html_e( 'ادامه بدون ورود', 'mr-booking' ); ?></button>
							<?php endif; ?>
						</div>
					</div>
				<?php endif; ?>
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

				<?php if ( ! empty( $settings['require_terms'] ) || '' !== trim( (string) ( $settings['text_terms'] ?? '' ) ) ) : ?>
					<?php
					$terms_title = (string) ( $settings['text_terms_title'] ?? 'قوانین و شرایط رزرو' );
					$terms_body  = (string) ( $settings['text_terms'] ?? '' );
					?>
					<div class="mrb-terms" id="mrb-terms" data-field="terms">
						<?php if ( ! empty( $settings['require_terms'] ) ) : ?>
							<div class="mrb-terms__accept">
								<input type="checkbox" name="terms_accepted" id="mrb-terms-accept" value="1" required aria-required="true" aria-describedby="mrb-err-terms" />
								<label for="mrb-terms-accept" class="mrb-terms__accept-text">
									<?php
									// Turn the title inside the accept sentence into a link; fall back to appending it.
									$accept_text = (string) ( $settings['text_terms_accept'] ?? 'قوانین و شرایط رزرو را خوانده‌ام و می‌پذیرم.' );
									$link        = '<button type="button" class="mrb-terms__open" id="mrb-terms-open" aria-haspopup="dialog" aria-controls="mrb-terms-dialog">' . esc_html( $terms_title ) . '</button>';
									if ( '' !== $terms_title && false !== mb_strpos( $accept_text, $terms_title ) ) {
										echo str_replace( $terms_title, $link, esc_html( $accept_text ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text escaped, link built from escaped parts.
									} else {
										echo esc_html( $accept_text ) . ' ' . $link; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									}
									?>
								</label>
							</div>
							<span class="mrb-field__error mrb-field__error--block" id="mrb-err-terms" hidden></span>
						<?php else : ?>
							<button type="button" class="mrb-terms__open mrb-terms__open--standalone" id="mrb-terms-open" aria-haspopup="dialog" aria-controls="mrb-terms-dialog"><?php echo esc_html( $terms_title ); ?></button>
						<?php endif; ?>

						<dialog class="mrb-terms-dialog" id="mrb-terms-dialog" aria-labelledby="mrb-terms-dialog-title">
							<div class="mrb-terms-dialog__head">
								<h3 id="mrb-terms-dialog-title"><?php echo esc_html( $terms_title ); ?></h3>
								<button type="button" class="mrb-terms-dialog__close" data-terms-close aria-label="<?php esc_attr_e( 'بستن', 'mr-booking' ); ?>">×</button>
							</div>
							<div class="mrb-terms-dialog__body"><?php echo wp_kses_post( wpautop( $terms_body ) ); ?></div>
							<div class="mrb-terms-dialog__foot">
								<button type="button" class="mrb__btn mrb__btn--ghost mrb__btn--small" data-terms-close><?php esc_html_e( 'بستن', 'mr-booking' ); ?></button>
								<?php if ( ! empty( $settings['require_terms'] ) ) : ?>
									<button type="button" class="mrb__btn mrb__btn--primary mrb__btn--small" data-terms-accept><?php esc_html_e( 'خواندم و می‌پذیرم', 'mr-booking' ); ?></button>
								<?php endif; ?>
							</div>
						</dialog>
					</div>
				<?php endif; ?>
			</section>

			<?php if ( \MRBooking\Payments\Payment_Service::deposit_enabled() ) : ?>
				<section class="mrb__panel" data-panel="6">
					<div class="mrb-pay">
						<div class="mrb-pay__lines" id="mrb-pay-lines"></div>

						<?php if ( \MRBooking\Payments\Payment_Service::tip_enabled() ) : ?>
							<label class="mrb-field mrb-pay__tip" data-field="tip">
								<span class="mrb-field__label"><?php echo esc_html( (string) ( $settings['text_tip_label'] ?? 'انعام (اختیاری)' ) ); ?></span>
								<span class="mrb-pay__tip-input">
									<input type="text" name="tip_amount" id="mrb-tip" inputmode="numeric" dir="ltr" placeholder="0" autocomplete="off" aria-describedby="mrb-tip-hint" />
									<em><?php esc_html_e( 'تومان', 'mr-booking' ); ?></em>
								</span>
								<span class="mrb-field__hint" id="mrb-tip-hint"><?php esc_html_e( 'اگر مایل باشید مبلغی به‌عنوان انعام به پیش‌پرداخت اضافه می‌شود.', 'mr-booking' ); ?></span>
							</label>
						<?php endif; ?>

						<div class="mrb-pay__total" id="mrb-pay-total" aria-live="polite">
							<span><?php esc_html_e( 'مبلغ قابل پرداخت', 'mr-booking' ); ?></span>
							<strong data-pay-total>—</strong>
						</div>

						<fieldset class="mrb-pay__methods" id="mrb-pay-methods" data-field="payment">
							<legend class="mrb__section-title"><?php esc_html_e( 'روش پرداخت', 'mr-booking' ); ?></legend>
							<?php if ( \MRBooking\Payments\Payment_Service::wallet_enabled() ) : ?>
								<label class="mrb-pay__method" id="mrb-pay-wallet-option">
									<input type="radio" name="payment_method" value="wallet" />
									<span class="mrb-pay__method-body">
										<strong><?php esc_html_e( 'پرداخت با کیف پول', 'mr-booking' ); ?></strong>
										<small data-wallet-hint></small>
									</span>
								</label>
							<?php endif; ?>
							<?php if ( \MRBooking\Payments\Payment_Service::online_enabled() ) : ?>
								<label class="mrb-pay__method" id="mrb-pay-online-option">
									<input type="radio" name="payment_method" value="online" />
									<span class="mrb-pay__method-body">
										<strong><?php esc_html_e( 'پرداخت آنلاین', 'mr-booking' ); ?></strong>
										<small><?php esc_html_e( 'انتقال امن به درگاه زرین‌پال', 'mr-booking' ); ?></small>
									</span>
								</label>
							<?php endif; ?>
							<?php if ( ! \MRBooking\Payments\Payment_Service::wallet_enabled() && ! \MRBooking\Payments\Payment_Service::online_enabled() ) : ?>
								<p class="mrb__hint"><?php esc_html_e( 'در حال حاضر هیچ روش پرداختی فعال نیست. لطفاً با ما تماس بگیرید.', 'mr-booking' ); ?></p>
							<?php endif; ?>
							<span class="mrb-field__error mrb-field__error--block" id="mrb-err-payment" hidden></span>
						</fieldset>
					</div>
				</section>
			<?php endif; ?>

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
