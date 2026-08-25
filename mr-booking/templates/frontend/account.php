<?php
/**
 * Customer account page (dashboard layout).
 *
 * @package MRBooking
 * @var array       $settings
 * @var object|null $customer
 * @var bool        $logged_in
 */

defined( 'ABSPATH' ) || exit;

use MRBooking\Helpers;
use MRBooking\Payments\Payment_Service;

$account_title  = __( 'حساب کاربری', 'mr-booking' );
$business_name  = (string) ( $settings['business_name'] ?: get_bloginfo( 'name' ) );
$wallet_on      = Payment_Service::wallet_enabled();
$booking_embed  = ! empty( $settings['account_embed_booking'] ?? 1 );
$first_name     = $customer ? (string) $customer->first_name : '';
$full_name      = $customer ? trim( $customer->first_name . ' ' . $customer->last_name ) : '';
$phone_fa       = $customer ? Helpers::to_persian_digits( (string) $customer->phone ) : '';
$icons          = array(
	'home'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M3 11.5 12 4l9 7.5M5.5 10v9.5h4.8V14h3.4v5.5h4.8V10"/></svg>',
	'book'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M7 3v3M17 3v3M4 8.5h16M6 5h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm6 8v5m-2.5-2.5h5"/></svg>',
	'wallet' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3M4 7.5V17a2 2 0 0 0 2 2h14V9H6.5A2.5 2.5 0 0 1 4 7.5Zm12 6.5h3"/></svg>',
	'user'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0"/></svg>',
	'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4m4-4 4-4-4-4m4 4H9"/></svg>',
	'spark'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2 9.5 9.5 2 12l7.5 2.5L12 22l2.5-7.5L22 12l-7.5-2.5Z"/></svg>',
);
$nav = array(
	'bookings' => array( __( 'نوبت‌های من', 'mr-booking' ), 'home' ),
);
if ( $booking_embed ) {
	$nav['book'] = array( __( 'رزرو نوبت', 'mr-booking' ), 'book' );
}
if ( $wallet_on ) {
	$nav['wallet'] = array( __( 'کیف پول', 'mr-booking' ), 'wallet' );
}
$nav['profile'] = array( __( 'پروفایل', 'mr-booking' ), 'user' );
?>
<div id="mr-booking-account" class="mrb mrb--account" dir="rtl" data-logged-in="<?php echo $logged_in ? '1' : '0'; ?>" data-wallet-result="<?php echo isset( $_GET['mrb_wallet'] ) ? esc_attr( sanitize_key( wp_unslash( $_GET['mrb_wallet'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>" data-booking-result="<?php echo isset( $_GET['mrb_payment'] ) ? esc_attr( sanitize_key( wp_unslash( $_GET['mrb_payment'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>">

	<section class="mrb-account__login" id="mrb-account-login" <?php echo $logged_in ? 'hidden' : ''; ?>>
		<div class="mrb__shell mrb-account__login-shell">
			<header class="mrb__header">
				<?php if ( ! \MRBooking\Premium\License::hide_branding() ) : ?>
					<p class="mrb__brand"><?php echo Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
				<?php endif; ?>
				<h2 class="mrb__title"><?php echo esc_html( $account_title ); ?></h2>
			</header>
			<div class="mrb__body">
				<?php
				$prefix  = 'mrb-account-otp';
				$intro   = __( 'برای مشاهده و مدیریت نوبت‌هایتان، شماره موبایل خود را وارد کنید.', 'mr-booking' );
				$compact = false;
				include MR_BOOKING_PATH . 'templates/partials/otp-login.php';
				?>
			</div>
		</div>
	</section>

	<section class="mrb-account__dash" id="mrb-account-dash" <?php echo $logged_in ? '' : 'hidden'; ?>>
		<aside class="mrb-account__sidebar">
			<div class="mrb-account__brand">
				<span class="mrb-account__brand-icon" aria-hidden="true"><?php echo $icons['spark']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="mrb-account__brand-text">
					<strong><?php echo esc_html( $business_name ); ?></strong>
					<small><?php esc_html_e( 'پنل کاربری', 'mr-booking' ); ?></small>
				</span>
			</div>

			<div class="mrb-account__identity">
				<span class="mrb-account__avatar" aria-hidden="true" data-account-avatar><?php echo esc_html( mb_substr( $first_name, 0, 1 ) ); ?></span>
				<p class="mrb-account__hello"><strong data-account-name><?php echo esc_html( $full_name ); ?></strong></p>
				<p class="mrb-account__phone"><bdi dir="ltr" class="mrb-phone" data-account-phone><?php echo esc_html( $phone_fa ); ?></bdi></p>
			</div>

			<nav class="mrb-account__nav" role="tablist" aria-label="<?php esc_attr_e( 'بخش‌های حساب', 'mr-booking' ); ?>">
				<?php $first = true; foreach ( $nav as $key => $item ) : ?>
					<button type="button" role="tab" class="mrb-account__tab<?php echo $first ? ' is-active' : ''; ?>" id="mrb-tab-<?php echo esc_attr( $key ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" aria-controls="mrb-panel-<?php echo esc_attr( $key ); ?>" data-tab="<?php echo esc_attr( $key ); ?>"<?php echo $first ? '' : ' tabindex="-1"'; ?>>
						<span class="mrb-account__tab-icon"><?php echo $icons[ $item[1] ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span><?php echo esc_html( $item[0] ); ?></span>
					</button>
					<?php $first = false; ?>
				<?php endforeach; ?>
			</nav>

			<button type="button" class="mrb-account__logout" id="mrb-account-logout">
				<span class="mrb-account__tab-icon"><?php echo $icons['logout']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span><?php esc_html_e( 'خروج', 'mr-booking' ); ?></span>
			</button>
		</aside>

		<main class="mrb-account__main">
			<header class="mrb-account__hero">
				<span class="mrb-account__hero-badge"><?php echo esc_html( sprintf( /* translators: %s: business name */ __( 'داشبورد %s', 'mr-booking' ), $business_name ) ); ?></span>
				<h2 class="mrb-account__hero-title"><?php esc_html_e( 'سلام', 'mr-booking' ); ?> <span data-account-first><?php echo esc_html( $first_name ); ?></span></h2>
				<p class="mrb-account__hero-lead"><?php esc_html_e( 'اینجا نوبت‌هایت را می‌بینی، نوبت جدید می‌گیری و کیف پول و پروفایلت همه یک‌جا در دسترس هستند.', 'mr-booking' ); ?></p>
				<span class="mrb-account__hero-icon" aria-hidden="true"><?php echo $icons['spark']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</header>

			<div class="mrb-account__stats">
				<button type="button" class="mrb-account__stat mrb-account__stat--primary" data-goto-tab="bookings">
					<span class="mrb-account__stat-icon"><?php echo $icons['home']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="mrb-account__stat-body"><strong data-stat-upcoming>—</strong><span><?php esc_html_e( 'نوبت پیش‌رو', 'mr-booking' ); ?></span></span>
				</button>
				<?php if ( $wallet_on ) : ?>
					<button type="button" class="mrb-account__stat" data-goto-tab="wallet">
						<span class="mrb-account__stat-icon"><?php echo $icons['wallet']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="mrb-account__stat-body"><strong data-stat-wallet>—</strong><span><?php esc_html_e( 'موجودی کیف پول', 'mr-booking' ); ?></span></span>
					</button>
				<?php endif; ?>
				<?php if ( $booking_embed ) : ?>
					<button type="button" class="mrb-account__stat" data-goto-tab="book">
						<span class="mrb-account__stat-icon"><?php echo $icons['book']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="mrb-account__stat-body"><strong><?php esc_html_e( 'رزرو جدید', 'mr-booking' ); ?></strong><span><?php esc_html_e( 'بدون خروج از پنل', 'mr-booking' ); ?></span></span>
					</button>
				<?php endif; ?>
			</div>

			<div class="mrb-account__panel is-active mrb-account__card" role="tabpanel" id="mrb-panel-bookings" aria-labelledby="mrb-tab-bookings" data-panel="bookings">
				<p class="mrb__hint" id="mrb-account-bookings-status" aria-live="polite"><?php esc_html_e( 'در حال بارگذاری...', 'mr-booking' ); ?></p>
				<p class="mrb__error" id="mrb-account-bookings-error" role="alert" hidden></p>

				<div class="mrb-account__group" id="mrb-account-upcoming" hidden>
					<h3 class="mrb__section-title"><?php esc_html_e( 'نوبت‌های پیش‌رو', 'mr-booking' ); ?></h3>
					<ul class="mrb-account__list" data-list="upcoming"></ul>
				</div>

				<div class="mrb-account__group" id="mrb-account-past" hidden>
					<h3 class="mrb__section-title"><?php esc_html_e( 'نوبت‌های گذشته', 'mr-booking' ); ?></h3>
					<ul class="mrb-account__list" data-list="past"></ul>
				</div>

				<div class="mrb-account__empty" id="mrb-account-empty" hidden>
					<div class="mrb-account__empty-icon" aria-hidden="true">📅</div>
					<p><?php esc_html_e( 'هنوز نوبتی ثبت نکرده‌اید.', 'mr-booking' ); ?></p>
					<?php if ( $booking_embed ) : ?>
						<button type="button" class="mrb__btn mrb__btn--primary mrb__btn--small" data-goto-tab="book"><?php esc_html_e( 'رزرو اولین نوبت', 'mr-booking' ); ?></button>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $booking_embed ) : ?>
				<div class="mrb-account__panel mrb-account__card mrb-account__card--flush" role="tabpanel" id="mrb-panel-book" aria-labelledby="mrb-tab-book" data-panel="book" hidden>
					<?php
					// The full booking wizard, embedded (header/auth chip hidden by the embed flag).
					echo ( new \MRBooking\Frontend\Shortcode() )->render( array( 'embed' => 'account' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcode returns escaped markup.
					?>
				</div>
			<?php endif; ?>

			<?php if ( $wallet_on ) : ?>
				<div class="mrb-account__panel mrb-account__card" role="tabpanel" id="mrb-panel-wallet" aria-labelledby="mrb-tab-wallet" data-panel="wallet" hidden>
					<div class="mrb-wallet-card">
						<span class="mrb-wallet-card__label"><?php esc_html_e( 'موجودی کیف پول', 'mr-booking' ); ?></span>
						<strong class="mrb-wallet-card__balance" data-wallet-balance>—</strong>
						<p class="mrb-wallet-card__hint"><?php esc_html_e( 'پیش‌پرداخت رزروهای بعدی را می‌توانید از این موجودی بپردازید.', 'mr-booking' ); ?></p>
					</div>

					<form class="mrb-topup" id="mrb-topup" novalidate>
						<h3 class="mrb__section-title"><?php esc_html_e( 'افزایش موجودی', 'mr-booking' ); ?></h3>
						<label class="mrb-field" data-field="topup">
							<span class="mrb-field__label"><?php esc_html_e( 'مبلغ (تومان)', 'mr-booking' ); ?></span>
							<span class="mrb-topup__input">
								<input type="text" name="amount" id="mrb-topup-amount" inputmode="numeric" dir="ltr" placeholder="0" autocomplete="off" enterkeyhint="done" aria-describedby="mrb-topup-hint mrb-err-topup" />
								<em><?php esc_html_e( 'تومان', 'mr-booking' ); ?></em>
							</span>
							<span class="mrb-field__hint" id="mrb-topup-hint"><?php echo esc_html( sprintf( /* translators: %s: amount */ __( 'حداقل %s — مبلغ دلخواه را وارد کنید یا یکی از گزینه‌های زیر را انتخاب کنید.', 'mr-booking' ), Helpers::format_price( Payment_Service::topup_min() ) ) ); ?></span>
							<span class="mrb-field__error" id="mrb-err-topup" hidden></span>
						</label>
						<div class="mrb-topup__chips" role="group" aria-label="<?php esc_attr_e( 'مبالغ پیشنهادی', 'mr-booking' ); ?>">
							<?php foreach ( array( 200000, 500000, 1000000 ) as $chip ) : ?>
								<button type="button" class="mrb-topup__chip" data-amount="<?php echo esc_attr( (string) $chip ); ?>" aria-pressed="false"><?php echo esc_html( Helpers::format_price( (float) $chip ) ); ?></button>
							<?php endforeach; ?>
						</div>
						<?php if ( ! Payment_Service::topup_enabled() ) : ?>
							<p class="mrb-account__note mrb-account__note--error"><?php esc_html_e( 'درگاه پرداخت هنوز پیکربندی نشده است؛ افزایش موجودی آنلاین موقتاً در دسترس نیست.', 'mr-booking' ); ?></p>
						<?php endif; ?>
						<button type="submit" class="mrb__btn mrb__btn--primary" id="mrb-topup-submit" <?php disabled( ! Payment_Service::topup_enabled() ); ?>><span class="mrb__btn__label"><?php esc_html_e( 'پرداخت و افزایش موجودی', 'mr-booking' ); ?></span></button>
					</form>

					<p class="mrb__hint" id="mrb-account-wallet-status" aria-live="polite"></p>
					<p class="mrb__error" id="mrb-account-wallet-error" role="alert" hidden></p>
					<p class="mrb-account__saved" id="mrb-account-wallet-saved" role="status" hidden></p>
					<h3 class="mrb__section-title"><?php esc_html_e( 'تراکنش‌ها', 'mr-booking' ); ?></h3>
					<ul class="mrb-account__list mrb-wallet-list" data-list="wallet"></ul>
					<div class="mrb-account__empty" id="mrb-account-wallet-empty" hidden>
						<div class="mrb-account__empty-icon" aria-hidden="true">👛</div>
						<p><?php esc_html_e( 'هنوز تراکنشی ندارید.', 'mr-booking' ); ?></p>
					</div>
				</div>
			<?php endif; ?>

				<div class="mrb-account__panel mrb-account__card" role="tabpanel" id="mrb-panel-profile" aria-labelledby="mrb-tab-profile" data-panel="profile" hidden>
					<form class="mrb-account__profile" id="mrb-account-profile" novalidate>
						<div class="mrb__fields">
							<label class="mrb-field" data-field="first_name">
								<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
								<input type="text" name="first_name" autocomplete="given-name" minlength="2" maxlength="100" required value="<?php echo esc_attr( (string) ( $customer->first_name ?? '' ) ); ?>" />
								<span class="mrb-field__error" id="mrb-err-first_name" hidden></span>
							</label>
							<label class="mrb-field" data-field="last_name">
								<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
								<input type="text" name="last_name" autocomplete="family-name" minlength="2" maxlength="100" required value="<?php echo esc_attr( (string) ( $customer->last_name ?? '' ) ); ?>" />
								<span class="mrb-field__error" id="mrb-err-last_name" hidden></span>
							</label>
							<label class="mrb-field mrb-field--locked" data-field="phone">
								<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?> <span class="mrb-lock" aria-hidden="true">🔒</span></span>
								<input type="tel" name="phone" readonly aria-readonly="true" dir="ltr" value="<?php echo esc_attr( (string) ( $customer->phone ?? '' ) ); ?>" aria-describedby="mrb-hint-phone-locked" />
								<span class="mrb-field__hint" id="mrb-hint-phone-locked"><?php esc_html_e( 'شماره تأییدشده قابل تغییر نیست. برای تغییر با ما تماس بگیرید.', 'mr-booking' ); ?></span>
							</label>
							<label class="mrb-field" data-field="email">
								<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?> <small><?php esc_html_e( '(اختیاری)', 'mr-booking' ); ?></small></span>
								<input type="email" name="email" autocomplete="email" maxlength="190" value="<?php echo esc_attr( (string) ( $customer->email ?? '' ) ); ?>" />
								<span class="mrb-field__error" id="mrb-err-email" hidden></span>
							</label>
							<?php
							$prefix         = 'mrb-acc-birth';
							$input_name     = 'birth_date';
							$show_label     = true;
							$show_required  = false;
							$error_id       = 'mrb-err-birth_date';
							$placeholder    = (string) ( $settings['text_ph_birth_date'] ?? '' );
							$initial_value  = (string) ( $customer->birth_date ?? '' );
							include MR_BOOKING_PATH . 'templates/partials/birth-date-field.php';
							?>
						</div>
						<p class="mrb__error" id="mrb-account-profile-error" role="alert" hidden></p>
						<p class="mrb-account__saved" id="mrb-account-profile-saved" role="status" hidden></p>
						<div class="mrb-account__actions">
							<button type="submit" class="mrb__btn mrb__btn--primary" id="mrb-account-save"><span class="mrb__btn__label"><?php esc_html_e( 'ذخیره تغییرات', 'mr-booking' ); ?></span></button>
						</div>
					</form>
				</div>
		</main>
	</section>

	<?php
	$prefix = 'mrb-acc-birth';
	include MR_BOOKING_PATH . 'templates/partials/birth-date-dialog.php';
	?>
</div>
