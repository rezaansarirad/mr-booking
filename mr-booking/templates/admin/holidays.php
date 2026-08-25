<?php
/**
 * Holidays template.
 *
 * @package MRBooking
 * @var list<object> $holidays
 * @var list<object> $specials
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap mrb-admin mrb-holidays-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'تعطیلات و تاریخ‌های خاص', 'mr-booking' ); ?></h1>
			<p class="mrb-holidays-page__lead">
				<?php esc_html_e( 'روزهای تعطیل رسمی و تاریخ‌های استثنا را مدیریت کنید تا در تقویم رزرو اعمال شوند.', 'mr-booking' ); ?>
			</p>
		</div>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'ذخیره شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['deleted'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'حذف شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<div class="mrb-holidays-layout">
		<section class="mrb-panel mrb-holidays-panel">
			<div class="mrb-holidays-panel__head">
				<div>
					<h2><?php esc_html_e( 'تعطیلات', 'mr-booking' ); ?></h2>
					<p><?php esc_html_e( 'روزهایی که به‌طور کامل یا جزئی از رزرو خارج می‌شوند.', 'mr-booking' ); ?></p>
				</div>
				<span class="mrb-count"><?php echo esc_html( (string) count( $holidays ) ); ?></span>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-holidays-form">
				<?php wp_nonce_field( 'mr_booking_save_holiday' ); ?>
				<input type="hidden" name="action" value="mr_booking_save_holiday" />

				<div class="mrb-holidays-form__grid">
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'تاریخ', 'mr-booking' ); ?></span>
						<input type="date" name="holiday_date" required />
						<span class="mrb-field__hint"><?php esc_html_e( 'میلادی — در لیست تاریخ شمسی هم نمایش داده می‌شود.', 'mr-booking' ); ?></span>
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'عنوان', 'mr-booking' ); ?></span>
						<input type="text" name="title" required placeholder="<?php esc_attr_e( 'مثلاً عید فطر', 'mr-booking' ); ?>" />
					</label>
				</div>

				<div class="mrb-holidays-form__toggles">
					<label class="mrb-check">
						<input type="checkbox" name="is_official" value="1" />
						<span>
							<strong><?php esc_html_e( 'تعطیل رسمی', 'mr-booking' ); ?></strong>
							<small><?php esc_html_e( 'در تقویم با برچسب رسمی نمایش داده می‌شود.', 'mr-booking' ); ?></small>
						</span>
					</label>
					<label class="mrb-check">
						<input type="checkbox" name="is_closed" value="1" checked />
						<span>
							<strong><?php esc_html_e( 'بسته (غیرقابل رزرو)', 'mr-booking' ); ?></strong>
							<small><?php esc_html_e( 'اگر خاموش باشد فقط به‌عنوان یادداشت ثبت می‌شود.', 'mr-booking' ); ?></small>
						</span>
					</label>
				</div>

				<button class="button button-primary">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'افزودن تعطیلی', 'mr-booking' ); ?>
				</button>
			</form>

			<?php if ( empty( $holidays ) ) : ?>
				<p class="mrb-empty mrb-holidays-empty"><?php esc_html_e( 'هنوز تعطیلی ثبت نشده است.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<div class="mrb-holiday-cards">
					<?php foreach ( $holidays as $h ) : ?>
						<article class="mrb-holiday-card">
							<div class="mrb-holiday-card__date">
								<strong><?php echo esc_html( \MRBooking\Helpers::format_admin_date( $h->holiday_date ) ); ?></strong>
								<?php if ( 'both' === \MRBooking\Helpers::admin_calendar_mode() ) : ?>
									<span><?php echo esc_html( $h->holiday_date ); ?></span>
								<?php endif; ?>
							</div>
							<div class="mrb-holiday-card__body">
								<h3><?php echo esc_html( $h->title ); ?></h3>
								<div class="mrb-holiday-card__badges">
									<?php if ( ! empty( $h->is_official ) ) : ?>
										<span class="mrb-badge mrb-badge--pending"><?php esc_html_e( 'رسمی', 'mr-booking' ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $h->is_closed ) ) : ?>
										<span class="mrb-badge mrb-badge--cancelled"><?php esc_html_e( 'بسته', 'mr-booking' ); ?></span>
									<?php else : ?>
										<span class="mrb-badge mrb-badge--confirmed"><?php esc_html_e( 'باز', 'mr-booking' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm(mrBookingAdmin.i18n.confirmDelete);">
								<?php wp_nonce_field( 'mr_booking_delete_holiday' ); ?>
								<input type="hidden" name="action" value="mr_booking_delete_holiday" />
								<input type="hidden" name="id" value="<?php echo esc_attr( (string) $h->id ); ?>" />
								<button class="button-link-delete" type="submit"><?php esc_html_e( 'حذف', 'mr-booking' ); ?></button>
							</form>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<section class="mrb-panel mrb-holidays-panel">
			<div class="mrb-holidays-panel__head">
				<div>
					<h2><?php esc_html_e( 'تاریخ‌های خاص', 'mr-booking' ); ?></h2>
					<p><?php esc_html_e( 'برای یک روز مشخص تعطیل کامل یا ساعات کاری متفاوت تعریف کنید.', 'mr-booking' ); ?></p>
				</div>
				<span class="mrb-count"><?php echo esc_html( (string) count( $specials ) ); ?></span>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-holidays-form" id="mrb-special-form">
				<?php wp_nonce_field( 'mr_booking_save_special' ); ?>
				<input type="hidden" name="action" value="mr_booking_save_special" />

				<div class="mrb-holidays-form__grid">
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'تاریخ', 'mr-booking' ); ?></span>
						<input type="date" name="special_date" required />
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'نوع', 'mr-booking' ); ?></span>
						<select name="type" id="mrb-special-type">
							<option value="closed"><?php esc_html_e( 'تعطیل کامل', 'mr-booking' ); ?></option>
							<option value="special"><?php esc_html_e( 'ساعات کاری ویژه', 'mr-booking' ); ?></option>
						</select>
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'دلیل', 'mr-booking' ); ?></span>
						<input type="text" name="reason" placeholder="<?php esc_attr_e( 'مثلاً جابجایی نوبت‌ها', 'mr-booking' ); ?>" />
					</label>
					<label class="mrb-field mrb-field--full">
						<span class="mrb-field__label"><?php esc_html_e( 'یادداشت', 'mr-booking' ); ?></span>
						<textarea name="note" rows="2" placeholder="<?php esc_attr_e( 'توضیح اختیاری برای تیم', 'mr-booking' ); ?>"></textarea>
					</label>
				</div>

				<div class="mrb-special-hours is-hidden" id="mrb-special-hours">
					<div class="mrb-special-hours__head">
						<strong><?php esc_html_e( 'بازه ساعات ویژه', 'mr-booking' ); ?></strong>
						<span><?php esc_html_e( 'فقط در این بازه‌ها رزرو باز است.', 'mr-booking' ); ?></span>
					</div>
					<div class="mrb-period-row">
						<label class="mrb-period-field">
							<span><?php esc_html_e( 'شروع', 'mr-booking' ); ?></span>
							<input type="time" name="start[]" value="10:00" />
						</label>
						<span class="mrb-period-sep" aria-hidden="true">→</span>
						<label class="mrb-period-field">
							<span><?php esc_html_e( 'پایان', 'mr-booking' ); ?></span>
							<input type="time" name="end[]" value="18:00" />
						</label>
					</div>
				</div>

				<button class="button button-primary">
					<span class="dashicons dashicons-saved"></span>
					<?php esc_html_e( 'ذخیره تاریخ خاص', 'mr-booking' ); ?>
				</button>
			</form>

			<?php if ( empty( $specials ) ) : ?>
				<p class="mrb-empty mrb-holidays-empty"><?php esc_html_e( 'تاریخ خاصی ثبت نشده است.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<div class="mrb-holiday-cards">
					<?php foreach ( $specials as $sp ) : ?>
						<?php
						$periods = array();
						if ( ! empty( $sp->periods ) ) {
							$decoded = json_decode( (string) $sp->periods, true );
							if ( is_array( $decoded ) ) {
								$periods = $decoded;
							}
						}
						$is_closed = 'closed' === $sp->type;
						?>
						<article class="mrb-holiday-card mrb-holiday-card--special">
							<div class="mrb-holiday-card__date">
								<strong><?php echo esc_html( \MRBooking\Helpers::format_admin_date( $sp->special_date ) ); ?></strong>
								<?php if ( 'both' === \MRBooking\Helpers::admin_calendar_mode() ) : ?>
									<span><?php echo esc_html( $sp->special_date ); ?></span>
								<?php endif; ?>
							</div>
							<div class="mrb-holiday-card__body">
								<h3><?php echo esc_html( $sp->reason ?: __( 'بدون عنوان', 'mr-booking' ) ); ?></h3>
								<?php if ( ! empty( $sp->note ) ) : ?>
									<p class="mrb-holiday-card__note"><?php echo esc_html( (string) $sp->note ); ?></p>
								<?php endif; ?>
								<div class="mrb-holiday-card__badges">
									<span class="mrb-badge mrb-badge--<?php echo $is_closed ? 'cancelled' : 'completed'; ?>">
										<?php echo $is_closed ? esc_html__( 'تعطیل کامل', 'mr-booking' ) : esc_html__( 'ساعات ویژه', 'mr-booking' ); ?>
									</span>
								</div>
								<?php if ( ! $is_closed && $periods ) : ?>
									<div class="mrb-holiday-card__periods">
										<?php foreach ( $periods as $p ) : ?>
											<span class="mrb-holiday-card__period-tag">
												<?php
												echo esc_html(
													substr( (string) ( $p['start'] ?? '' ), 0, 5 ) .
													' – ' .
													substr( (string) ( $p['end'] ?? '' ), 0, 5 )
												);
												?>
											</span>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</div>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm(mrBookingAdmin.i18n.confirmDelete);">
								<?php wp_nonce_field( 'mr_booking_delete_special' ); ?>
								<input type="hidden" name="action" value="mr_booking_delete_special" />
								<input type="hidden" name="id" value="<?php echo esc_attr( (string) $sp->id ); ?>" />
								<button class="button-link-delete" type="submit"><?php esc_html_e( 'حذف', 'mr-booking' ); ?></button>
							</form>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
</div>
