<?php
/**
 * Notifications templates page.
 *
 * @package MRBooking
 * @var array<string, mixed> $settings
 */

defined( 'ABSPATH' ) || exit;

$sms_templates = array(
	array(
		'key'   => 'tpl_sms_created',
		'label' => __( 'ثبت درخواست', 'mr-booking' ),
		'desc'  => __( 'در انتظار تأیید — بلافاصله بعد از ثبت رزرو', 'mr-booking' ),
		'badge' => 'pending',
	),
	array(
		'key'   => 'tpl_sms_confirmed',
		'label' => __( 'تأیید رزرو', 'mr-booking' ),
		'desc'  => __( 'بعد از تأیید ادمین', 'mr-booking' ),
		'badge' => 'confirmed',
	),
	array(
		'key'   => 'tpl_sms_cancelled',
		'label' => __( 'رد / لغو', 'mr-booking' ),
		'desc'  => __( 'وقتی رزرو لغو یا رد می‌شود', 'mr-booking' ),
		'badge' => 'cancelled',
	),
	array(
		'key'   => 'tpl_sms_reminder',
		'label' => __( 'یادآوری', 'mr-booking' ),
		'desc'  => __( 'قبل از زمان نوبت (طبق تنظیمات)', 'mr-booking' ),
		'badge' => 'completed',
	),
	array(
		'key'        => 'tpl_sms_birthday',
		'label'      => __( 'تولد', 'mr-booking' ),
		'desc'       => __( 'پیام تبریک تولد مشتری', 'mr-booking' ),
		'badge'      => 'completed',
		'badge_text' => __( 'تولد', 'mr-booking' ),
	),
);

$email_templates = array(
	array(
		'subject' => 'tpl_email_created_subject',
		'body'    => 'tpl_email_created_body',
		'label'   => __( 'ثبت درخواست', 'mr-booking' ),
		'desc'    => __( 'ایمیل به مشتری بعد از ثبت رزرو', 'mr-booking' ),
		'badge'   => 'pending',
	),
	array(
		'subject' => 'tpl_email_confirmed_subject',
		'body'    => 'tpl_email_confirmed_body',
		'label'   => __( 'تأیید رزرو', 'mr-booking' ),
		'desc'    => __( 'ایمیل تأیید بعد از OK ادمین', 'mr-booking' ),
		'badge'   => 'confirmed',
	),
	array(
		'subject' => 'tpl_email_cancelled_subject',
		'body'    => 'tpl_email_cancelled_body',
		'label'   => __( 'لغو / رد', 'mr-booking' ),
		'desc'    => __( 'اطلاع‌رسانی لغو به مشتری', 'mr-booking' ),
		'badge'   => 'cancelled',
	),
	array(
		'subject' => 'tpl_email_reminder_subject',
		'body'    => 'tpl_email_reminder_body',
		'label'   => __( 'یادآوری', 'mr-booking' ),
		'desc'    => __( 'یادآوری زمان نوبت', 'mr-booking' ),
		'badge'   => 'completed',
	),
);

$variables = array(
	'{customer_name}'  => __( 'نام مشتری', 'mr-booking' ),
	'{customer_phone}' => __( 'موبایل', 'mr-booking' ),
	'{service_name}'   => __( 'نام خدمت', 'mr-booking' ),
	'{booking_date}'   => __( 'تاریخ نوبت', 'mr-booking' ),
	'{booking_time}'   => __( 'ساعت نوبت', 'mr-booking' ),
	'{staff_name}'     => __( 'نام پرسنل', 'mr-booking' ),
	'{booking_id}'     => __( 'کد رزرو', 'mr-booking' ),
	'{business_name}'  => __( 'نام کسب‌وکار', 'mr-booking' ),
);

$badge_labels = array(
	'pending'   => __( 'در انتظار', 'mr-booking' ),
	'confirmed' => __( 'تأیید شده', 'mr-booking' ),
	'cancelled' => __( 'لغو', 'mr-booking' ),
	'completed' => __( 'یادآوری', 'mr-booking' ),
);
?>
<div class="wrap mrb-admin mrb-notifications-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
			<h1><?php esc_html_e( 'قالب‌های اعلان', 'mr-booking' ); ?></h1>
			<p class="mrb-notifications-page__lead">
				<?php esc_html_e( 'متن پیامک و ایمیل ارسالی به مشتری را برای هر مرحله از رزرو شخصی‌سازی کنید.', 'mr-booking' ); ?>
			</p>
		</div>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'ذخیره شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-notifications-form" id="mrb-notifications-form">
		<?php wp_nonce_field( 'mr_booking_save_settings' ); ?>
		<input type="hidden" name="action" value="mr_booking_save_settings" />
		<input type="hidden" name="tab" value="notifications" />

		<section class="mrb-panel mrb-notif-vars">
			<div class="mrb-notif-vars__head">
				<span class="dashicons dashicons-shortcode"></span>
				<div>
					<strong><?php esc_html_e( 'متغیرهای قابل استفاده', 'mr-booking' ); ?></strong>
					<p><?php esc_html_e( 'روی هر متغیر کلیک کنید تا در کلیپ‌بورد کپی شود.', 'mr-booking' ); ?></p>
				</div>
			</div>
			<div class="mrb-notif-vars__list">
				<?php foreach ( $variables as $token => $hint ) : ?>
					<button type="button" class="mrb-notif-var" data-copy="<?php echo esc_attr( $token ); ?>" title="<?php echo esc_attr( $hint ); ?>">
						<code><?php echo esc_html( $token ); ?></code>
						<span><?php echo esc_html( $hint ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<div class="mrb-notifications-layout">
			<section class="mrb-panel mrb-notif-panel mrb-notif-panel--sms">
				<div class="mrb-notif-panel__head">
					<div class="mrb-notif-panel__title">
						<span class="mrb-notif-panel__icon mrb-notif-panel__icon--sms" aria-hidden="true">
							<span class="dashicons dashicons-smartphone"></span>
						</span>
						<div>
							<h2><?php esc_html_e( 'پیامک', 'mr-booking' ); ?></h2>
							<p><?php esc_html_e( 'متن کوتاه — حداکثر حدود ۱۶۰ کاراکتر توصیه می‌شود.', 'mr-booking' ); ?></p>
						</div>
					</div>
					<span class="mrb-count"><?php echo esc_html( (string) count( $sms_templates ) ); ?></span>
				</div>

				<div class="mrb-notif-templates">
					<?php foreach ( $sms_templates as $tpl ) : ?>
						<article class="mrb-notif-card">
							<header class="mrb-notif-card__head">
								<div>
									<h3><?php echo esc_html( $tpl['label'] ); ?></h3>
									<p><?php echo esc_html( $tpl['desc'] ); ?></p>
								</div>
								<span class="mrb-badge mrb-badge--<?php echo esc_attr( $tpl['badge'] ); ?>">
									<?php
									echo esc_html(
										$tpl['badge_text'] ?? ( $badge_labels[ $tpl['badge'] ] ?? $tpl['label'] )
									);
									?>
								</span>
							</header>
							<label class="mrb-field mrb-field--full">
								<span class="mrb-field__label"><?php esc_html_e( 'متن پیامک', 'mr-booking' ); ?></span>
								<textarea
									name="settings[<?php echo esc_attr( $tpl['key'] ); ?>]"
									rows="3"
									class="mrb-notif-textarea"
									placeholder="<?php esc_attr_e( 'مثلاً: {customer_name} عزیز، نوبت شما برای {service_name} در {booking_date} ساعت {booking_time} ثبت شد.', 'mr-booking' ); ?>"
								><?php echo esc_textarea( (string) ( $settings[ $tpl['key'] ] ?? '' ) ); ?></textarea>
							</label>
						</article>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="mrb-panel mrb-notif-panel mrb-notif-panel--email">
				<div class="mrb-notif-panel__head">
					<div class="mrb-notif-panel__title">
						<span class="mrb-notif-panel__icon mrb-notif-panel__icon--email" aria-hidden="true">
							<span class="dashicons dashicons-email"></span>
						</span>
						<div>
							<h2><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></h2>
							<p><?php esc_html_e( 'موضوع و بدنه برای هر نوع اعلان.', 'mr-booking' ); ?></p>
						</div>
					</div>
					<span class="mrb-count"><?php echo esc_html( (string) count( $email_templates ) ); ?></span>
				</div>

				<div class="mrb-notif-templates">
					<?php foreach ( $email_templates as $tpl ) : ?>
						<article class="mrb-notif-card mrb-notif-card--email">
							<header class="mrb-notif-card__head">
								<div>
									<h3><?php echo esc_html( $tpl['label'] ); ?></h3>
									<p><?php echo esc_html( $tpl['desc'] ); ?></p>
								</div>
								<span class="mrb-badge mrb-badge--<?php echo esc_attr( $tpl['badge'] ); ?>">
									<?php
									echo esc_html(
										$tpl['badge_text'] ?? ( $badge_labels[ $tpl['badge'] ] ?? $tpl['label'] )
									);
									?>
								</span>
							</header>
							<div class="mrb-notif-card__fields">
								<label class="mrb-field mrb-field--full">
									<span class="mrb-field__label"><?php esc_html_e( 'موضوع ایمیل', 'mr-booking' ); ?></span>
									<input
										type="text"
										name="settings[<?php echo esc_attr( $tpl['subject'] ); ?>]"
										value="<?php echo esc_attr( (string) ( $settings[ $tpl['subject'] ] ?? '' ) ); ?>"
										placeholder="<?php esc_attr_e( 'مثلاً: تأیید نوبت — {business_name}', 'mr-booking' ); ?>"
									/>
								</label>
								<label class="mrb-field mrb-field--full">
									<span class="mrb-field__label"><?php esc_html_e( 'بدنه ایمیل', 'mr-booking' ); ?></span>
									<textarea
										name="settings[<?php echo esc_attr( $tpl['body'] ); ?>]"
										rows="4"
										class="mrb-notif-textarea"
										placeholder="<?php esc_attr_e( 'متن کامل ایمیل — می‌توانید HTML ساده استفاده کنید.', 'mr-booking' ); ?>"
									><?php echo esc_textarea( (string) ( $settings[ $tpl['body'] ] ?? '' ) ); ?></textarea>
								</label>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</section>
		</div>

		<footer class="mrb-notif-footer">
			<p class="mrb-notif-footer__hint">
				<?php esc_html_e( 'تغییرات بلافاصله روی اعلان‌های بعدی اعمال می‌شود.', 'mr-booking' ); ?>
			</p>
			<button type="submit" class="button button-primary mrb-notif-footer__btn">
				<span class="dashicons dashicons-saved"></span>
				<?php esc_html_e( 'ذخیره قالب‌ها', 'mr-booking' ); ?>
			</button>
		</footer>
	</form>
</div>
