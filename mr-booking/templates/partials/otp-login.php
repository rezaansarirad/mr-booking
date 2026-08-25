<?php
/**
 * OTP login widget (phone → code → optional profile).
 *
 * @package MRBooking
 *
 * @var string $prefix   Unique id prefix (e.g. 'mrb-login').
 * @var string $intro    Optional lead text.
 * @var bool   $compact  Smaller heading for inline use.
 */

defined( 'ABSPATH' ) || exit;

$prefix  = isset( $prefix ) ? (string) $prefix : 'mrb-login';
$intro   = isset( $intro ) ? (string) $intro : '';
$compact = ! empty( $compact );
?>
<div class="mrb-otp <?php echo $compact ? 'mrb-otp--compact' : ''; ?>" id="<?php echo esc_attr( $prefix ); ?>" data-mrb-otp data-step="phone">
	<div class="mrb-otp__head">
		<?php if ( $compact ) : ?>
			<h3 class="mrb-otp__title" id="<?php echo esc_attr( $prefix ); ?>-title"><?php esc_html_e( 'ورود با کد پیامکی', 'mr-booking' ); ?></h3>
		<?php else : ?>
			<h2 class="mrb-otp__title" id="<?php echo esc_attr( $prefix ); ?>-title"><?php esc_html_e( 'ورود یا ثبت‌نام', 'mr-booking' ); ?></h2>
		<?php endif; ?>
		<p class="mrb-otp__intro" data-otp-intro><?php echo esc_html( $intro ?: __( 'شماره موبایل خود را وارد کنید؛ کد تأیید پیامک می‌شود.', 'mr-booking' ) ); ?></p>
	</div>

	<form class="mrb-otp__step" data-otp-step="phone" novalidate>
		<label class="mrb-field" data-field="otp_phone">
			<span class="mrb-field__label"><?php esc_html_e( 'شماره موبایل', 'mr-booking' ); ?></span>
			<input type="tel" name="otp_phone" inputmode="numeric" autocomplete="tel" maxlength="13" placeholder="09121234567" enterkeyhint="send" aria-describedby="<?php echo esc_attr( $prefix ); ?>-err-phone" />
			<span class="mrb-field__error" id="<?php echo esc_attr( $prefix ); ?>-err-phone" data-otp-error="phone" hidden></span>
		</label>
		<button type="submit" class="mrb__btn mrb__btn--primary mrb-otp__submit" data-otp-action="request">
			<span class="mrb__btn__label"><?php esc_html_e( 'ارسال کد', 'mr-booking' ); ?></span>
		</button>
	</form>

	<form class="mrb-otp__step" data-otp-step="code" hidden novalidate>
		<p class="mrb-otp__sent" data-otp-sent-to></p>
		<label class="mrb-field" data-field="otp_code">
			<span class="mrb-field__label"><?php esc_html_e( 'کد تأیید', 'mr-booking' ); ?></span>
			<input type="text" name="otp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="mrb-otp__code" enterkeyhint="done" aria-describedby="<?php echo esc_attr( $prefix ); ?>-err-code" />
			<span class="mrb-field__error" id="<?php echo esc_attr( $prefix ); ?>-err-code" data-otp-error="code" hidden></span>
		</label>
		<button type="submit" class="mrb__btn mrb__btn--primary mrb-otp__submit" data-otp-action="verify">
			<span class="mrb__btn__label"><?php esc_html_e( 'تأیید و ورود', 'mr-booking' ); ?></span>
		</button>
		<div class="mrb-otp__links">
			<button type="button" class="mrb-otp__link" data-otp-action="resend" disabled>
				<span data-otp-resend-label><?php esc_html_e( 'ارسال مجدد کد', 'mr-booking' ); ?></span>
			</button>
			<button type="button" class="mrb-otp__link" data-otp-action="change-phone"><?php esc_html_e( 'تغییر شماره', 'mr-booking' ); ?></button>
		</div>
	</form>

	<form class="mrb-otp__step" data-otp-step="profile" hidden novalidate>
		<p class="mrb-otp__sent"><?php esc_html_e( 'شماره تأیید شد. برای تکمیل حساب، نام خود را وارد کنید.', 'mr-booking' ); ?></p>
		<div class="mrb__fields mrb-otp__fields">
			<label class="mrb-field" data-field="otp_first_name">
				<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
				<input type="text" name="otp_first_name" autocomplete="given-name" minlength="2" maxlength="100" enterkeyhint="next" />
				<span class="mrb-field__error" data-otp-error="first_name" hidden></span>
			</label>
			<label class="mrb-field" data-field="otp_last_name">
				<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
				<input type="text" name="otp_last_name" autocomplete="family-name" minlength="2" maxlength="100" enterkeyhint="done" />
				<span class="mrb-field__error" data-otp-error="last_name" hidden></span>
			</label>
		</div>
		<button type="submit" class="mrb__btn mrb__btn--primary mrb-otp__submit" data-otp-action="complete">
			<span class="mrb__btn__label"><?php esc_html_e( 'تکمیل ثبت‌نام', 'mr-booking' ); ?></span>
		</button>
	</form>

	<p class="mrb__error mrb-otp__error" role="alert" data-otp-error="global" hidden></p>
</div>
