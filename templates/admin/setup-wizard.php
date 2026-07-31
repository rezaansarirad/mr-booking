<?php
/**
 * Setup wizard template.
 *
 * @package MRBooking
 * @var int   $step
 * @var array $settings
 * @var array $services
 * @var array $staff
 * @var array $hours
 * @var array $labels
 * @var array $order
 * @var array $active_services
 * @var array $staff_service_map
 */

defined( 'ABSPATH' ) || exit;

use MRBooking\Helpers;

$steps = array(
	1 => __( 'خوش‌آمد', 'mr-booking' ),
	2 => __( 'تقویم', 'mr-booking' ),
	3 => __( 'ساعات کاری', 'mr-booking' ),
	4 => __( 'خدمات', 'mr-booking' ),
	5 => __( 'پرسنل', 'mr-booking' ),
	6 => __( 'قوانین', 'mr-booking' ),
);
?>
<div class="wrap mrb-admin mrb-setup" dir="rtl">
	<div class="mrb-setup__card">
		<header class="mrb-setup__header">
			<p class="mrb-admin__eyebrow">MR Booking</p>
			<h1><?php esc_html_e( 'راه‌اندازی اولیه', 'mr-booking' ); ?></h1>
			<p class="mrb-setup__lead"><?php esc_html_e( 'فقط چند مرحله تا آماده شدن سیستم رزرو فاصله دارید.', 'mr-booking' ); ?></p>
		</header>

		<nav class="mrb-setup__steps" aria-label="<?php esc_attr_e( 'مراحل راه‌اندازی', 'mr-booking' ); ?>">
			<?php foreach ( $steps as $n => $label ) : ?>
				<span class="mrb-setup__step <?php echo $n === $step ? 'is-active' : ( $n < $step ? 'is-done' : '' ); ?>">
					<em><?php echo esc_html( (string) $n ); ?></em>
					<?php echo esc_html( $label ); ?>
				</span>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-setup__body mrb-form" id="mrb-setup-form">
			<?php wp_nonce_field( 'mr_booking_setup_wizard' ); ?>
			<input type="hidden" name="action" value="mr_booking_setup_wizard" />
			<input type="hidden" name="step" value="<?php echo esc_attr( (string) $step ); ?>" />

			<?php if ( 1 === $step ) : ?>
				<div class="mrb-setup__welcome">
					<h2><?php esc_html_e( 'کسب‌وکار خود را معرفی کنید', 'mr-booking' ); ?></h2>
					<p><?php esc_html_e( 'این نام در فرم رزرو، ایمیل‌ها و پیامک‌ها نمایش داده می‌شود.', 'mr-booking' ); ?></p>
					<label>
						<?php esc_html_e( 'نام کسب‌وکار', 'mr-booking' ); ?>
						<input type="text" name="business_name" required value="<?php echo esc_attr( (string) $settings['business_name'] ); ?>" placeholder="<?php esc_attr_e( 'مثلاً سالن زیبایی گلدیس', 'mr-booking' ); ?>" />
					</label>
					<ol class="mrb-setup__roadmap">
						<li><?php esc_html_e( 'تعریف خدمات', 'mr-booking' ); ?></li>
						<li><?php esc_html_e( 'افزودن پرسنل و اتصال هر خدمت به پرسنل', 'mr-booking' ); ?></li>
						<li><?php esc_html_e( 'مشتری در رزرو، پرسنل مناسب همان خدمت را می‌بیند', 'mr-booking' ); ?></li>
					</ol>
				</div>

			<?php elseif ( 2 === $step ) : ?>
				<h2><?php esc_html_e( 'نوع تقویم', 'mr-booking' ); ?></h2>
				<div class="mrb-setup__info">
					<?php esc_html_e( 'این تنظیم فقط نحوهٔ نمایش تاریخ به مشتری را عوض می‌کند. ذخیرهٔ نوبت‌ها همیشه با تاریخ میلادی انجام می‌شود.', 'mr-booking' ); ?>
				</div>
				<div class="mrb-setup__choices">
					<label class="mrb-choice">
						<input type="radio" name="calendar_mode" value="jalali" <?php checked( $settings['calendar_mode'], 'jalali' ); ?> />
						<strong><?php esc_html_e( 'فقط شمسی', 'mr-booking' ); ?></strong>
						<span><?php esc_html_e( 'مناسب کسب‌وکارهای ایرانی', 'mr-booking' ); ?></span>
					</label>
					<label class="mrb-choice">
						<input type="radio" name="calendar_mode" value="gregorian" <?php checked( $settings['calendar_mode'], 'gregorian' ); ?> />
						<strong><?php esc_html_e( 'فقط میلادی', 'mr-booking' ); ?></strong>
						<span><?php esc_html_e( 'نمایش استاندارد بین‌المللی', 'mr-booking' ); ?></span>
					</label>
					<label class="mrb-choice">
						<input type="radio" name="calendar_mode" value="both" <?php checked( $settings['calendar_mode'], 'both' ); ?> />
						<strong><?php esc_html_e( 'هر دو هم‌زمان', 'mr-booking' ); ?></strong>
						<span><?php esc_html_e( 'مثلاً: شنبه ۱۰ مرداد + Saturday August 1', 'mr-booking' ); ?></span>
					</label>
				</div>
				<label class="mrb-check">
					<input type="checkbox" name="allow_holiday_booking" value="1" <?php checked( ! empty( $settings['allow_holiday_booking'] ) ); ?> />
					<span>
						<strong><?php esc_html_e( 'اجازه رزرو در تعطیلات رسمی', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'اگر خاموش باشد، روزهای تعطیل در تقویم رزرو غیرفعال می‌شوند.', 'mr-booking' ); ?></small>
					</span>
				</label>
				<label>
					<?php esc_html_e( 'رنگ نمایش تعطیلات', 'mr-booking' ); ?>
					<?php Helpers::color_input( 'color_holiday', (string) $settings['color_holiday'], array( 'default' => '#dc2626' ) ); ?>
					<span class="mrb-setup__hint"><?php esc_html_e( 'رنگ روزهای تعطیل در تقویم فرم رزرو.', 'mr-booking' ); ?></span>
				</label>

			<?php elseif ( 3 === $step ) : ?>
				<h2><?php esc_html_e( 'ساعات کاری هفته', 'mr-booking' ); ?></h2>
				<div class="mrb-setup__info">
					<?php esc_html_e( 'ساعات کاری مشخص می‌کند در چه بازه‌هایی سیستم اجازهٔ رزرو بدهد. مثلاً ۹ تا ۱۳ و ۱۶ تا ۲۱. بعداً می‌توانید برای هر روز یا هر پرسنل جداگانه تغییر دهید.', 'mr-booking' ); ?>
				</div>

				<input type="hidden" name="apply_all" value="1" />

				<div class="mrb-setup__hours">
					<label>
						<?php esc_html_e( 'شروع بازه اول', 'mr-booking' ); ?>
						<input type="time" name="start1" value="09:00" required />
					</label>
					<label>
						<?php esc_html_e( 'پایان بازه اول', 'mr-booking' ); ?>
						<input type="time" name="end1" value="13:00" required />
					</label>
				</div>

				<label class="mrb-check">
					<input type="checkbox" name="use_second_period" value="1" checked id="mrb-second-period" />
					<span>
						<strong><?php esc_html_e( 'بازه عصر هم دارم', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'برای استراحت ظهر؛ دو بازه جداگانه تعریف می‌شود.', 'mr-booking' ); ?></small>
					</span>
				</label>
				<div class="mrb-setup__hours" id="mrb-second-hours">
					<label>
						<?php esc_html_e( 'شروع بازه دوم', 'mr-booking' ); ?>
						<input type="time" name="start2" value="16:00" />
					</label>
					<label>
						<?php esc_html_e( 'پایان بازه دوم', 'mr-booking' ); ?>
						<input type="time" name="end2" value="21:00" />
					</label>
				</div>

				<fieldset class="mrb-setup__closed">
					<legend><?php esc_html_e( 'روزهای تعطیل هفته', 'mr-booking' ); ?></legend>
					<p class="mrb-setup__hint" style="margin:0 0 8px;width:100%"><?php esc_html_e( 'روزهایی که کسب‌وکار کلاً بسته است (مثل جمعه). این روزها در تقویم رزرو غیرفعال می‌شوند.', 'mr-booking' ); ?></p>
					<?php foreach ( $order as $dow ) : ?>
						<label class="mrb-check">
							<input type="checkbox" name="closed_days[]" value="<?php echo esc_attr( (string) $dow ); ?>" <?php checked( 5 === $dow ); ?> />
							<?php echo esc_html( $labels[ $dow ] ); ?>
						</label>
					<?php endforeach; ?>
				</fieldset>

			<?php elseif ( 4 === $step ) : ?>
				<h2><?php esc_html_e( 'تعریف خدمات', 'mr-booking' ); ?></h2>
				<div class="mrb-setup__info">
					<?php esc_html_e( 'خدمت یعنی کاری که مشتری رزرو می‌کند (مثلاً کوتاهی مو). «مدت» مشخص می‌کند هر نوبت چند دقیقه طول می‌کشد و روی زمان‌های خالی تأثیر مستقیم دارد.', 'mr-booking' ); ?>
				</div>

				<input type="hidden" name="manage_samples" value="1" />
				<?php if ( ! empty( $services ) ) : ?>
					<p class="mrb-setup__hint"><?php esc_html_e( 'نمونه‌های زیر از قبل ساخته شده‌اند. مواردی که نیاز ندارید را از حالت انتخاب خارج کنید.', 'mr-booking' ); ?></p>
					<div class="mrb-setup__service-grid">
						<?php foreach ( $services as $svc ) : ?>
							<label class="mrb-choice mrb-choice--sm">
								<input type="checkbox" name="keep_services[]" value="<?php echo esc_attr( (string) $svc->id ); ?>" <?php checked( 'active' === $svc->status ); ?> />
								<strong><?php echo esc_html( $svc->name ); ?></strong>
								<span><?php echo esc_html( \MRBooking\Helpers::format_duration( (int) $svc->duration ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<div class="mrb-setup__service-add">
					<div class="mrb-setup__service-add__head">
						<h3><?php esc_html_e( 'افزودن خدمت جدید', 'mr-booking' ); ?></h3>
						<p class="mrb-setup__hint"><?php esc_html_e( 'نام، مدت و در صورت نیاز قیمت هر خدمت را مشخص کنید.', 'mr-booking' ); ?></p>
					</div>

					<div id="mrb-service-rows" class="mrb-setup__service-cards" data-next-index="1">
						<article class="mrb-setup__service-card" data-index="1">
							<header class="mrb-setup__service-card__head">
								<strong class="mrb-setup__service-card__title"><?php esc_html_e( 'خدمت ۱', 'mr-booking' ); ?></strong>
								<button type="button" class="button-link-delete mrb-remove-service is-hidden" aria-label="<?php esc_attr_e( 'حذف این خدمت', 'mr-booking' ); ?>">
									<?php esc_html_e( 'حذف', 'mr-booking' ); ?>
								</button>
							</header>
							<div class="mrb-setup__service-card__fields">
								<label class="mrb-setup__field mrb-setup__field--wide">
									<span class="mrb-setup__field-label"><?php esc_html_e( 'نام خدمت', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
									<input type="text" name="new_service_name[]" placeholder="<?php esc_attr_e( 'مثال: کوتاهی مو', 'mr-booking' ); ?>" autocomplete="off" />
								</label>
								<label class="mrb-setup__field">
									<span class="mrb-setup__field-label"><?php esc_html_e( 'مدت (دقیقه)', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
									<input type="number" name="new_service_duration[]" min="5" step="5" value="30" inputmode="numeric" />
									<small class="mrb-setup__field-hint"><?php esc_html_e( 'تأثیر مستقیم روی ساعت‌های قابل رزرو', 'mr-booking' ); ?></small>
								</label>
								<label class="mrb-setup__field mrb-price-field-wrap<?php echo empty( $settings['show_prices'] ) ? ' is-hidden' : ''; ?>">
									<span class="mrb-setup__field-label"><?php esc_html_e( 'قیمت (تومان)', 'mr-booking' ); ?></span>
									<input type="number" name="new_service_price[]" min="0" step="1000" value="0" inputmode="numeric" class="mrb-price-field" placeholder="<?php esc_attr_e( 'اختیاری', 'mr-booking' ); ?>" />
									<small class="mrb-setup__field-hint"><?php esc_html_e( 'در صورت فعال بودن نمایش قیمت به مشتری', 'mr-booking' ); ?></small>
								</label>
							</div>
						</article>
					</div>

					<button type="button" class="button button-secondary mrb-setup__add-service-btn" id="mrb-add-service">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'افزودن خدمت دیگر', 'mr-booking' ); ?>
					</button>
				</div>

				<template id="mrb-service-row-template">
					<article class="mrb-setup__service-card" data-index="__INDEX__">
						<header class="mrb-setup__service-card__head">
							<strong class="mrb-setup__service-card__title"><?php esc_html_e( 'خدمت', 'mr-booking' ); ?> __INDEX__</strong>
							<button type="button" class="button-link-delete mrb-remove-service" aria-label="<?php esc_attr_e( 'حذف این خدمت', 'mr-booking' ); ?>">
								<?php esc_html_e( 'حذف', 'mr-booking' ); ?>
							</button>
						</header>
						<div class="mrb-setup__service-card__fields">
							<label class="mrb-setup__field mrb-setup__field--wide">
								<span class="mrb-setup__field-label"><?php esc_html_e( 'نام خدمت', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
								<input type="text" name="new_service_name[]" placeholder="<?php esc_attr_e( 'مثال: کوتاهی مو', 'mr-booking' ); ?>" autocomplete="off" />
							</label>
							<label class="mrb-setup__field">
								<span class="mrb-setup__field-label"><?php esc_html_e( 'مدت (دقیقه)', 'mr-booking' ); ?> <abbr class="mrb-req" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr></span>
								<input type="number" name="new_service_duration[]" min="5" step="5" value="30" inputmode="numeric" />
								<small class="mrb-setup__field-hint"><?php esc_html_e( 'تأثیر مستقیم روی ساعت‌های قابل رزرو', 'mr-booking' ); ?></small>
							</label>
							<label class="mrb-setup__field mrb-price-field-wrap is-hidden">
								<span class="mrb-setup__field-label"><?php esc_html_e( 'قیمت (تومان)', 'mr-booking' ); ?></span>
								<input type="number" name="new_service_price[]" min="0" step="1000" value="0" inputmode="numeric" class="mrb-price-field" placeholder="<?php esc_attr_e( 'اختیاری', 'mr-booking' ); ?>" />
								<small class="mrb-setup__field-hint"><?php esc_html_e( 'در صورت فعال بودن نمایش قیمت به مشتری', 'mr-booking' ); ?></small>
							</label>
						</div>
					</article>
				</template>

				<div class="mrb-setup__toggles mrb-setup__toggles--services">
				<label class="mrb-check">
					<input type="checkbox" name="show_prices" value="1" id="mrb-show-prices" <?php checked( ! empty( $settings['show_prices'] ) ); ?> />
					<span>
						<strong><?php esc_html_e( 'نمایش قیمت خدمات به مشتری در فرم رزرو', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'اگر خاموش باشد، قیمت در فرم رزرو دیده نمی‌شود.', 'mr-booking' ); ?></small>
					</span>
				</label>
				<label class="mrb-check">
					<input type="checkbox" name="enable_multi_service" value="1" <?php checked( ! empty( $settings['enable_multi_service'] ) ); ?> />
					<span>
						<strong><?php esc_html_e( 'اجازه انتخاب چند خدمت در یک نوبت', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'مدت کل نوبت برابر جمع مدت خدمات انتخاب‌شده می‌شود.', 'mr-booking' ); ?></small>
					</span>
				</label>

				</div>

			<?php elseif ( 5 === $step ) : ?>
				<h2><?php esc_html_e( 'پرسنل و تخصیص خدمات', 'mr-booking' ); ?></h2>
				<div class="mrb-setup__info">
					<?php esc_html_e( 'هر پرسنل فقط خدماتی را می‌بیند/ارائه می‌دهد که اینجا به او اضافه کنید. در فرم رزرو، بعد از انتخاب خدمت، فقط پرسنل مرتبط نمایش داده می‌شوند.', 'mr-booking' ); ?>
				</div>

				<?php if ( empty( $active_services ) ) : ?>
					<div class="notice notice-warning inline">
						<p>
							<?php esc_html_e( 'هنوز خدمت فعالی ندارید.', 'mr-booking' ); ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-setup&step=4' ) ); ?>">
								<?php esc_html_e( 'بازگشت به تعریف خدمات', 'mr-booking' ); ?>
							</a>
						</p>
					</div>
				<?php else : ?>
					<div class="mrb-setup__assign-legend">
						<strong><?php esc_html_e( 'خدمات فعال:', 'mr-booking' ); ?></strong>
						<?php echo esc_html( implode( '، ', array_map( static fn( $s ) => $s->name, $active_services ) ) ); ?>
					</div>

					<?php if ( ! empty( $staff ) ) : ?>
						<h3><?php esc_html_e( 'پرسنل فعلی — خدمات را بررسی/اصلاح کنید', 'mr-booking' ); ?></h3>
						<div class="mrb-setup__staff-cards">
							<?php foreach ( $staff as $member ) : ?>
								<?php
								$linked = $staff_service_map[ (int) $member->id ] ?? array();
								$sid    = (int) $member->id;
								?>
								<div class="mrb-setup__staff-card" data-assign-key="existing_<?php echo esc_attr( (string) $sid ); ?>">
									<input type="hidden" name="existing_staff_id[]" value="<?php echo esc_attr( (string) $sid ); ?>" />
									<div class="mrb-setup__staff-card__head">
										<strong><?php echo esc_html( \MRBooking\Staff\Staff_Repository::display_name( $member ) ); ?></strong>
										<span><?php echo esc_html( (string) $member->phone ); ?></span>
									</div>
									<div class="mrb-setup__assign">
										<span class="mrb-setup__assign-title"><?php esc_html_e( 'خدمات این پرسنل', 'mr-booking' ); ?></span>
										<div class="mrb-setup__picker mrb-setup__picker--multi">
											<div class="mrb-setup__service-checklist" role="group" aria-label="<?php esc_attr_e( 'انتخاب چند خدمت', 'mr-booking' ); ?>">
												<?php foreach ( $active_services as $svc ) : ?>
													<label class="mrb-setup__service-option<?php echo in_array( (int) $svc->id, $linked, true ) ? ' is-used' : ''; ?>">
														<input
															type="checkbox"
															class="mrb-setup__service-check"
															value="<?php echo esc_attr( (string) $svc->id ); ?>"
															data-name="<?php echo esc_attr( $svc->name ); ?>"
															<?php disabled( in_array( (int) $svc->id, $linked, true ) ); ?>
														/>
														<span><?php echo esc_html( $svc->name ); ?></span>
													</label>
												<?php endforeach; ?>
											</div>
											<div class="mrb-setup__picker-actions">
												<button type="button" class="button button-primary mrb-setup__add-service-btn"><?php esc_html_e( 'افزودن انتخاب‌شده‌ها', 'mr-booking' ); ?></button>
												<button type="button" class="button mrb-setup__add-all-btn"><?php esc_html_e( 'افزودن همه', 'mr-booking' ); ?></button>
											</div>
											<p class="mrb-setup__picker-hint description"><?php esc_html_e( 'چند خدمت را تیک بزنید و یک‌جا اضافه کنید.', 'mr-booking' ); ?></p>
										</div>
										<div class="mrb-setup__chips" data-input-name="existing_staff_services[<?php echo esc_attr( (string) $sid ); ?>][]">
											<?php foreach ( $active_services as $svc ) : ?>
												<?php if ( ! in_array( (int) $svc->id, $linked, true ) ) : ?>
													<?php continue; ?>
												<?php endif; ?>
												<span class="mrb-setup__chip" data-id="<?php echo esc_attr( (string) $svc->id ); ?>">
													<?php echo esc_html( $svc->name ); ?>
													<button type="button" class="mrb-setup__chip-remove" aria-label="<?php esc_attr_e( 'حذف', 'mr-booking' ); ?>">&times;</button>
													<input type="hidden" name="existing_staff_services[<?php echo esc_attr( (string) $sid ); ?>][]" value="<?php echo esc_attr( (string) $svc->id ); ?>" />
												</span>
											<?php endforeach; ?>
										</div>
										<p class="mrb-setup__chips-empty description<?php echo empty( $linked ) ? '' : ' is-hidden'; ?>">
											<?php esc_html_e( 'هنوز خدمتی اضافه نشده. از لیست بالا چند مورد را انتخاب کنید.', 'mr-booking' ); ?>
										</p>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<h3><?php esc_html_e( 'افزودن پرسنل جدید', 'mr-booking' ); ?></h3>
					<div id="mrb-staff-rows" class="mrb-setup__staff-cards" data-next-index="0">
						<div class="mrb-setup__staff-card mrb-setup__staff-card--new" data-index="0">
							<div class="mrb-setup__row">
								<input type="text" name="staff_first[0]" placeholder="<?php esc_attr_e( 'نام', 'mr-booking' ); ?>" <?php echo empty( $staff ) ? 'required' : ''; ?> />
								<input type="text" name="staff_last[0]" placeholder="<?php esc_attr_e( 'نام خانوادگی', 'mr-booking' ); ?>" <?php echo empty( $staff ) ? 'required' : ''; ?> />
								<input type="text" name="staff_phone[0]" placeholder="<?php esc_attr_e( 'موبایل (اختیاری)', 'mr-booking' ); ?>" />
							</div>
							<div class="mrb-setup__assign">
								<span class="mrb-setup__assign-title"><?php esc_html_e( 'خدمات این پرسنل', 'mr-booking' ); ?></span>
								<div class="mrb-setup__picker mrb-setup__picker--multi">
									<div class="mrb-setup__service-checklist" role="group" aria-label="<?php esc_attr_e( 'انتخاب چند خدمت', 'mr-booking' ); ?>">
										<?php foreach ( $active_services as $svc ) : ?>
											<label class="mrb-setup__service-option">
												<input
													type="checkbox"
													class="mrb-setup__service-check"
													value="<?php echo esc_attr( (string) $svc->id ); ?>"
													data-name="<?php echo esc_attr( $svc->name ); ?>"
												/>
												<span><?php echo esc_html( $svc->name ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<div class="mrb-setup__picker-actions">
										<button type="button" class="button button-primary mrb-setup__add-service-btn"><?php esc_html_e( 'افزودن انتخاب‌شده‌ها', 'mr-booking' ); ?></button>
										<button type="button" class="button mrb-setup__add-all-btn"><?php esc_html_e( 'افزودن همه', 'mr-booking' ); ?></button>
									</div>
									<p class="mrb-setup__picker-hint description"><?php esc_html_e( 'چند خدمت را تیک بزنید و یک‌جا اضافه کنید.', 'mr-booking' ); ?></p>
								</div>
								<div class="mrb-setup__chips" data-input-name="staff_services[0][]"></div>
								<p class="mrb-setup__chips-empty description">
									<?php esc_html_e( 'هنوز خدمتی اضافه نشده. از لیست بالا چند مورد را انتخاب کنید.', 'mr-booking' ); ?>
								</p>
							</div>
						</div>
					</div>
					<button type="button" class="button" id="mrb-add-staff"><?php esc_html_e( '+ پرسنل دیگر', 'mr-booking' ); ?></button>

					<template id="mrb-staff-row-template">
						<div class="mrb-setup__staff-card mrb-setup__staff-card--new" data-index="__INDEX__">
							<div class="mrb-setup__row">
								<input type="text" name="staff_first[__INDEX__]" placeholder="<?php esc_attr_e( 'نام', 'mr-booking' ); ?>" />
								<input type="text" name="staff_last[__INDEX__]" placeholder="<?php esc_attr_e( 'نام خانوادگی', 'mr-booking' ); ?>" />
								<input type="text" name="staff_phone[__INDEX__]" placeholder="<?php esc_attr_e( 'موبایل (اختیاری)', 'mr-booking' ); ?>" />
							</div>
							<div class="mrb-setup__assign">
								<span class="mrb-setup__assign-title"><?php esc_html_e( 'خدمات این پرسنل', 'mr-booking' ); ?></span>
								<div class="mrb-setup__picker mrb-setup__picker--multi">
									<div class="mrb-setup__service-checklist" role="group" aria-label="<?php esc_attr_e( 'انتخاب چند خدمت', 'mr-booking' ); ?>">
										<?php foreach ( $active_services as $svc ) : ?>
											<label class="mrb-setup__service-option">
												<input
													type="checkbox"
													class="mrb-setup__service-check"
													value="<?php echo esc_attr( (string) $svc->id ); ?>"
													data-name="<?php echo esc_attr( $svc->name ); ?>"
												/>
												<span><?php echo esc_html( $svc->name ); ?></span>
											</label>
										<?php endforeach; ?>
									</div>
									<div class="mrb-setup__picker-actions">
										<button type="button" class="button button-primary mrb-setup__add-service-btn"><?php esc_html_e( 'افزودن انتخاب‌شده‌ها', 'mr-booking' ); ?></button>
										<button type="button" class="button mrb-setup__add-all-btn"><?php esc_html_e( 'افزودن همه', 'mr-booking' ); ?></button>
									</div>
									<p class="mrb-setup__picker-hint description"><?php esc_html_e( 'چند خدمت را تیک بزنید و یک‌جا اضافه کنید.', 'mr-booking' ); ?></p>
								</div>
								<div class="mrb-setup__chips" data-input-name="staff_services[__INDEX__][]"></div>
								<p class="mrb-setup__chips-empty description">
									<?php esc_html_e( 'هنوز خدمتی اضافه نشده. از لیست بالا چند مورد را انتخاب کنید.', 'mr-booking' ); ?>
								</p>
							</div>
						</div>
					</template>
				<?php endif; ?>

				<label class="mrb-check">
					<input type="checkbox" name="enable_staff_selection" value="1" <?php checked( ! empty( $settings['enable_staff_selection'] ) ); ?> />
					<span>
						<strong><?php esc_html_e( 'مشتری بتواند پرسنل را انتخاب کند (پیشنهادی)', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'در فرم رزرو لیست پرسنل مرتبط با خدمت نمایش داده می‌شود.', 'mr-booking' ); ?></small>
					</span>
				</label>
				<label class="mrb-check">
					<input type="checkbox" name="require_staff" value="1" <?php checked( ! empty( $settings['require_staff'] ) ); ?> />
					<span>
						<strong><?php esc_html_e( 'انتخاب پرسنل در رزرو الزامی باشد', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'بدون انتخاب پرسنل، مشتری نمی‌تواند مرحله بعد برود.', 'mr-booking' ); ?></small>
					</span>
				</label>
				<label class="mrb-check">
					<input type="checkbox" name="auto_assign_staff" value="1" <?php checked( ! empty( $settings['auto_assign_staff'] ) ); ?> />
					<span>
						<strong><?php esc_html_e( 'اگر مشتری پرسنل انتخاب نکرد، به‌صورت خودکار تخصیص یابد', 'mr-booking' ); ?></strong>
						<small class="mrb-setup__hint"><?php esc_html_e( 'اولین پرسنل آزاد برای آن زمان انتخاب می‌شود.', 'mr-booking' ); ?></small>
					</span>
				</label>

			<?php else : ?>
				<h2><?php esc_html_e( 'قوانین رزرو و ظاهر', 'mr-booking' ); ?></h2>
				<div class="mrb-setup__info">
					<?php esc_html_e( 'این قوانین مشخص می‌کنند زمان‌های قابل رزرو چطور ساخته شوند و مشتری چه اطلاعاتی را باید وارد کند. بعداً از تنظیمات هم قابل تغییر است.', 'mr-booking' ); ?>
				</div>

				<div class="mrb-setup__fields">
					<div class="mrb-setup__info" style="margin-bottom:12px">
						<?php esc_html_e( 'ساعت‌های رزرو بر اساس مدت هر خدمت ساخته می‌شوند. مثال: خدمت ۱ ساعت و ۳۰ دقیقه از ۹:۰۰ → ۹:۰۰، ۱۰:۳۰، ۱۲:۰۰.', 'mr-booking' ); ?>
					</div>
					<input type="hidden" name="booking_interval" value="<?php echo esc_attr( (string) max( 5, (int) $settings['booking_interval'] ) ); ?>" />

					<label class="mrb-setup__field">
						<span class="mrb-setup__field-label"><?php esc_html_e( 'حداقل زمان قبل از رزرو (دقیقه)', 'mr-booking' ); ?></span>
						<input type="number" name="min_notice_minutes" min="0" value="<?php echo esc_attr( (string) $settings['min_notice_minutes'] ); ?>" />
						<span class="mrb-setup__hint">
							<?php esc_html_e( 'چقدر زودتر از الان می‌توان نوبت گرفت. مثلاً ۶۰ یعنی تا یک ساعت آینده ساعتی نشان داده نمی‌شود.', 'mr-booking' ); ?>
						</span>
					</label>

					<label class="mrb-setup__field">
						<span class="mrb-setup__field-label"><?php esc_html_e( 'حداکثر روزهای آینده', 'mr-booking' ); ?></span>
						<input type="number" name="max_days_ahead" min="1" value="<?php echo esc_attr( (string) $settings['max_days_ahead'] ); ?>" />
						<span class="mrb-setup__hint">
							<?php esc_html_e( 'تا چند روز جلوتر می‌توان رزرو کرد. مثلاً ۶۰ یعنی مشتری حداکثر تا ۲ ماه آینده را می‌بیند.', 'mr-booking' ); ?>
						</span>
					</label>

					<label class="mrb-setup__field">
						<span class="mrb-setup__field-label"><?php esc_html_e( 'وضعیت پیش‌فرض نوبت جدید', 'mr-booking' ); ?></span>
						<select name="default_status">
							<option value="pending" <?php selected( $settings['default_status'], 'pending' ); ?>><?php esc_html_e( 'در انتظار', 'mr-booking' ); ?></option>
							<option value="confirmed" <?php selected( $settings['default_status'], 'confirmed' ); ?>><?php esc_html_e( 'تأیید شده', 'mr-booking' ); ?></option>
						</select>
						<span class="mrb-setup__hint">
							<?php esc_html_e( '«در انتظار» یعنی شما باید نوبت را تأیید کنید. «تأیید شده» یعنی به‌محض ثبت، نوبت قطعی است.', 'mr-booking' ); ?>
						</span>
					</label>
				</div>

				<div class="mrb-setup__toggles">
					<label class="mrb-check">
						<input type="checkbox" name="allow_same_day" value="1" <?php checked( ! empty( $settings['allow_same_day'] ) ); ?> />
						<span>
							<strong><?php esc_html_e( 'رزرو همان روز مجاز باشد', 'mr-booking' ); ?></strong>
							<small class="mrb-setup__hint"><?php esc_html_e( 'اگر خاموش باشد، امروز در تقویم غیرفعال است و فقط از فردا به بعد می‌توان رزرو کرد.', 'mr-booking' ); ?></small>
						</span>
					</label>
					<label class="mrb-check">
						<input type="checkbox" name="require_phone" value="1" <?php checked( ! empty( $settings['require_phone'] ) ); ?> />
						<span>
							<strong><?php esc_html_e( 'موبایل الزامی باشد', 'mr-booking' ); ?></strong>
							<small class="mrb-setup__hint"><?php esc_html_e( 'برای تماس و پیامک ضروری است؛ معمولاً روشن بماند.', 'mr-booking' ); ?></small>
						</span>
					</label>
					<label class="mrb-check">
						<input type="checkbox" name="require_email" value="1" <?php checked( ! empty( $settings['require_email'] ) ); ?> />
						<span>
							<strong><?php esc_html_e( 'ایمیل الزامی باشد', 'mr-booking' ); ?></strong>
							<small class="mrb-setup__hint"><?php esc_html_e( 'فقط اگر اعلان ایمیلی برایتان مهم است روشن کنید.', 'mr-booking' ); ?></small>
						</span>
					</label>
				</div>

				<h3><?php esc_html_e( 'ظاهر فرم', 'mr-booking' ); ?></h3>
				<div class="mrb-setup__fields mrb-setup__fields--2">
					<label class="mrb-setup__field">
						<span class="mrb-setup__field-label"><?php esc_html_e( 'رنگ اصلی', 'mr-booking' ); ?></span>
						<?php Helpers::color_input( 'color_primary', (string) $settings['color_primary'], array( 'default' => '#d4af37' ) ); ?>
						<span class="mrb-setup__hint"><?php esc_html_e( 'رنگ برجسته تقویم، مراحل و عناصر اصلی فرم.', 'mr-booking' ); ?></span>
					</label>
					<label class="mrb-setup__field">
						<span class="mrb-setup__field-label"><?php esc_html_e( 'رنگ دکمه', 'mr-booking' ); ?></span>
						<?php Helpers::color_input( 'color_button', (string) $settings['color_button'], array( 'default' => '#d4af37' ) ); ?>
						<span class="mrb-setup__hint"><?php esc_html_e( 'رنگ دکمه «ادامه» و «ثبت رزرو».', 'mr-booking' ); ?></span>
					</label>
				</div>

				<div class="mrb-setup__done-hint">
					<p><?php esc_html_e( 'پس از اتمام، این شورت‌کد را در یک صفحه قرار دهید:', 'mr-booking' ); ?></p>
					<code>[mr_booking]</code>
					<?php if ( \MRBooking\Premium\License::hide_branding() ) : ?>
						<p class="mrb-setup__hint"><?php esc_html_e( 'یا در Elementor ویجت «فرم رزرو» را اضافه کنید.', 'mr-booking' ); ?></p>
					<?php else : ?>
						<p class="mrb-setup__hint"><?php esc_html_e( 'یا در Elementor ویجت «فرم رزرو MR Booking» را اضافه کنید.', 'mr-booking' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<footer class="mrb-setup__footer">
				<?php if ( $step > 1 ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-setup&step=' . ( $step - 1 ) ) ); ?>"><?php esc_html_e( 'قبلی', 'mr-booking' ); ?></a>
				<?php else : ?>
					<span></span>
				<?php endif; ?>

				<div class="mrb-setup__footer-actions">
					<?php if ( 1 === $step ) : ?>
						<a class="mrb-setup__skip" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mr_booking_setup_skip' ), 'mr_booking_setup_skip' ) ); ?>">
							<?php esc_html_e( 'فعلاً رد شو', 'mr-booking' ); ?>
						</a>
					<?php endif; ?>
					<button type="submit" class="button button-primary button-hero">
						<?php echo 6 === $step ? esc_html__( 'اتمام راه‌اندازی', 'mr-booking' ) : esc_html__( 'ادامه', 'mr-booking' ); ?>
					</button>
				</div>
			</footer>
		</form>
	</div>
</div>
