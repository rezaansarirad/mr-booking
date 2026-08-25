<?php
/**
 * Appointments template.
 *
 * @package MRBooking
 */

defined( 'ABSPATH' ) || exit;

use MRBooking\Admin\Pages\Appointments;
use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;
use MRBooking\Holidays\Holiday_Repository;
use MRBooking\Staff\Staff_Repository;

$total_matched = array_sum( $status_counts );
?>
<div class="wrap mrb-admin mrb-appointments-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'مدیریت نوبت‌ها', 'mr-booking' ); ?></h1>
			<p class="mrb-appointments-page__lead">
				<?php esc_html_e( 'جستجو، فیلتر و مدیریت سریع رزروها', 'mr-booking' ); ?>
			</p>
		</div>
		<div class="mrb-header-actions">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-phone' ) ); ?>">
				<?php esc_html_e( '+ رزرو تلفنی', 'mr-booking' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mr-booking-appointments&mr_export=bookings' ), 'mr_export' ) ); ?>">
				<?php esc_html_e( 'خروجی CSV', 'mr-booking' ); ?>
			</a>
		</div>
	</header>

	<?php if ( ! empty( $updated ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'وضعیت نوبت بروزرسانی شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['approved'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'رزرو با موفقیت تأیید شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<?php if ( is_array( $notify_feedback ?? null ) ) : ?>
		<?php
		foreach ( array( 'email', 'sms' ) as $notify_channel ) {
			$notice = \MRBooking\Helpers::notify_feedback_notice(
				$notify_channel,
				is_array( $notify_feedback[ $notify_channel ] ?? null ) ? $notify_feedback[ $notify_channel ] : array()
			);
			if ( ! $notice ) {
				continue;
			}
			$notice_class = 'notice-info';
			if ( 'success' === $notice['type'] ) {
				$notice_class = 'notice-success';
			} elseif ( 'warning' === $notice['type'] ) {
				$notice_class = 'notice-warning';
			} elseif ( 'error' === $notice['type'] ) {
				$notice_class = 'notice-error';
			}
			?>
			<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible"><p><?php echo esc_html( $notice['message'] ); ?></p></div>
			<?php
		}
		?>
	<?php endif; ?>

	<?php if ( ! empty( $_GET['rejected'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'رزرو رد شد، پیام مربوطه برای مشتری ارسال شد و آن زمان برای رزرو بعدی آزاد است.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['slot_freed'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'رزرو لغو شد و آن ساعت دوباره برای رزروهای جدید در دسترس است.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['deleted'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'رزرو حذف شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['customer_saved'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'اطلاعات مشتری ذخیره شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['error'] ) && 'phone' === $_GET['error'] ) : // phpcs:ignore ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'شماره موبایل معتبر نیست.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['error'] ) && 'duplicate' === $_GET['error'] ) : // phpcs:ignore ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'این شماره موبایل قبلاً برای مشتری دیگری ثبت شده است.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $booking ) : ?>
		<?php
		$date_ymd   = substr( (string) $booking->start_datetime, 0, 10 );
		$time_start = substr( (string) $booking->start_datetime, 11, 5 );
		$time_end   = substr( (string) $booking->end_datetime, 11, 5 );
		$holiday    = Holiday_Repository::find_by_date( $date_ymd );
		$is_official_holiday = $holiday && ! empty( $holiday->is_official );
		$svc_names  = array();
		foreach ( $booking_svcs as $svc ) {
			$svc_names[] = $svc->name;
		}
		$can_free_slot = Booking_Repository::is_upcoming( $booking )
			&& Booking_Repository::blocks_slot( (string) $booking->status, $booking );
		$is_walkin     = Booking_Repository::is_walkin( $booking );
		$slot_is_free  = in_array( (string) $booking->status, array( 'cancelled', 'rejected' ), true );
		?>
		<section class="mrb-appt-detail">
			<div class="mrb-appt-detail__top">
				<div>
					<a class="mrb-appt-back" href="<?php echo esc_url( $base_url ); ?>">&rarr; <?php esc_html_e( 'بازگشت به لیست', 'mr-booking' ); ?></a>
					<p class="mrb-appt-detail__code"><?php echo esc_html( $booking->booking_code ); ?></p>
					<h2><?php echo esc_html( trim( $booking->first_name . ' ' . $booking->last_name ) ); ?></h2>
					<span class="mrb-badge mrb-badge--<?php echo esc_attr( $booking->status ); ?>">
						<?php echo esc_html( $statuses[ $booking->status ] ?? $booking->status ); ?>
					</span>
					<?php if ( $is_walkin ) : ?>
						<span class="mrb-badge mrb-badge--walkin"><?php esc_html_e( 'مراجعه حضوری', 'mr-booking' ); ?></span>
					<?php endif; ?>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-appt-status-form">
					<?php wp_nonce_field( 'mr_booking_update_status' ); ?>
					<input type="hidden" name="action" value="mr_booking_update_status" />
					<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking->id ); ?>" />
					<label>
						<span><?php esc_html_e( 'تغییر وضعیت', 'mr-booking' ); ?></span>
						<select name="status">
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $booking->status, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<button class="button button-primary"><?php esc_html_e( 'ذخیره', 'mr-booking' ); ?></button>
				</form>
			</div>

			<?php if ( $booking_customer ) : ?>
				<div class="mrb-appt-actionbar">
					<button
						type="button"
						class="button button-primary mrb-js-open-dialog"
						data-dialog="mrb-edit-customer-dialog"
						commandfor="mrb-edit-customer-dialog"
						command="show-modal"
					>
						<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						<?php esc_html_e( 'ویرایش پروفایل مشتری', 'mr-booking' ); ?>
					</button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers&view=' . (int) $booking_customer->id ) ); ?>">
						<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
						<?php esc_html_e( 'صفحه مشتری', 'mr-booking' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( 'pending' === $booking->status ) : ?>
				<div class="mrb-appt-quick">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'mr_booking_update_status' ); ?>
						<input type="hidden" name="action" value="mr_booking_update_status" />
						<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking->id ); ?>" />
						<input type="hidden" name="status" value="confirmed" />
						<button class="button button-primary"><?php esc_html_e( 'تأیید رزرو و ارسال پیام به مشتری', 'mr-booking' ); ?></button>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'mr_booking_update_status' ); ?>
						<input type="hidden" name="action" value="mr_booking_update_status" />
						<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking->id ); ?>" />
						<input type="hidden" name="status" value="rejected" />
						<button class="button"><?php esc_html_e( 'رد رزرو و آزاد کردن زمان', 'mr-booking' ); ?></button>
					</form>
				</div>
			<?php endif; ?>

			<?php if ( $can_free_slot && 'pending' !== $booking->status ) : ?>
				<div class="mrb-appt-quick mrb-appt-quick--danger">
					<p class="mrb-appt-quick__hint">
						<?php esc_html_e( 'اگر مشتری کنسل کرد یا نمی‌آید، با لغو یا حذف رزرو، این ساعت دوباره برای دیگران قابل رزرو می‌شود.', 'mr-booking' ); ?>
					</p>
					<div class="mrb-appt-quick__actions">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'mr_booking_cancel_booking' ); ?>
							<input type="hidden" name="action" value="mr_booking_cancel_booking" />
							<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking->id ); ?>" />
							<button class="button"><?php esc_html_e( 'لغو رزرو و آزاد کردن زمان', 'mr-booking' ); ?></button>
						</form>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-appt-delete-form" data-confirm="<?php esc_attr_e( 'رزرو به‌طور کامل حذف شود؟ این ساعت برای رزرو بعدی آزاد می‌شود.', 'mr-booking' ); ?>">
							<?php wp_nonce_field( 'mr_booking_delete_booking' ); ?>
							<input type="hidden" name="action" value="mr_booking_delete_booking" />
							<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking->id ); ?>" />
							<button type="submit" class="button button-link-delete"><?php esc_html_e( 'حذف کامل رزرو', 'mr-booking' ); ?></button>
						</form>
					</div>
				</div>
			<?php elseif ( $slot_is_free && Booking_Repository::is_upcoming( $booking ) ) : ?>
				<div class="notice notice-info inline mrb-appt-slot-free">
					<p><?php esc_html_e( 'این زمان دیگر اشغال نیست و برای رزروهای جدید آزاد است.', 'mr-booking' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="mrb-appt-detail__grid">
				<div class="mrb-appt-card">
					<span class="mrb-appt-card__label"><?php echo esc_html( $is_walkin ? __( 'زمان مراجعه', 'mr-booking' ) : __( 'زمان نوبت', 'mr-booking' ) ); ?></span>
					<strong><?php echo esc_html( Helpers::format_admin_dual_date( $date_ymd ) ); ?></strong>
					<p><?php echo esc_html( Helpers::format_admin_time( $booking->start_datetime ) . ' — ' . Helpers::format_admin_time( $booking->end_datetime ) ); ?></p>
					<?php if ( $is_walkin ) : ?>
						<p class="mrb-appt-card__note"><?php esc_html_e( 'رزرو حضوری هیچ ساعتی را در تقویم آنلاین اشغال نمی‌کند.', 'mr-booking' ); ?></p>
					<?php endif; ?>
					<?php if ( $is_official_holiday ) : ?>
						<p class="mrb-appt-holiday-badge-wrap">
							<span class="mrb-badge mrb-badge--holiday" title="<?php echo esc_attr( (string) $holiday->title ); ?>">
								<?php esc_html_e( 'تعطیل رسمی', 'mr-booking' ); ?>
								<?php if ( ! empty( $holiday->title ) ) : ?>
									— <?php echo esc_html( (string) $holiday->title ); ?>
								<?php endif; ?>
							</span>
						</p>
					<?php endif; ?>
				</div>
				<div class="mrb-appt-card">
					<div class="mrb-appt-card__head">
						<span class="mrb-appt-card__label"><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></span>
						<?php if ( $booking_customer ) : ?>
							<button
								type="button"
								class="button button-small mrb-js-open-dialog"
								data-dialog="mrb-edit-customer-dialog"
								commandfor="mrb-edit-customer-dialog"
								command="show-modal"
							>
								<span class="dashicons dashicons-edit" aria-hidden="true"></span>
								<?php esc_html_e( 'ویرایش', 'mr-booking' ); ?>
							</button>
						<?php endif; ?>
					</div>
					<strong><?php echo esc_html( trim( $booking->first_name . ' ' . $booking->last_name ) ); ?></strong>
					<p class="mrb-appt-card__wallet">
						<?php echo \MRBooking\Wallet\Wallet_Repository::badge( \MRBooking\Wallet\Wallet_Repository::balance( (int) $booking->customer_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
						<?php if ( $booking_customer ) : ?>
							<a class="mrb-appt-card__wallet-link" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers&view=' . (int) $booking_customer->id ) ); ?>#mrb-wallet"><?php esc_html_e( 'تغییر موجودی', 'mr-booking' ); ?></a>
						<?php endif; ?>
					</p>
					<p>
						<a href="tel:<?php echo esc_attr( $booking->phone ); ?>"><?php echo esc_html( $booking->phone ); ?></a>
						<?php if ( ! empty( $booking->email ) ) : ?>
							<br /><?php echo esc_html( $booking->email ); ?>
						<?php endif; ?>
					</p>
				</div>
				<div class="mrb-appt-card">
					<span class="mrb-appt-card__label"><?php esc_html_e( 'خدمت / پرسنل', 'mr-booking' ); ?></span>
					<strong><?php echo esc_html( $svc_names ? implode( '، ', $svc_names ) : '—' ); ?></strong>
					<p>
						<?php
						echo esc_html(
							$booking_staff
								? Staff_Repository::display_name( $booking_staff )
								: __( 'بدون پرسنل', 'mr-booking' )
						);
						?>
					</p>
				</div>
				<div class="mrb-appt-card">
					<span class="mrb-appt-card__label"><?php esc_html_e( 'مبلغ', 'mr-booking' ); ?></span>
					<strong><?php echo esc_html( (float) $booking->total_price > 0 ? Helpers::format_price( (float) $booking->total_price ) : __( 'بدون مبلغ', 'mr-booking' ) ); ?></strong>
					<?php $pay = \MRBooking\Payments\Payment_Service::admin_summary( $booking ); ?>
					<?php if ( 'none' !== $pay['status'] || (float) ( $booking->deposit_amount ?? 0 ) > 0 ) : ?>
						<ul class="mrb-appt-card__lines mrb-appt-card__pay">
							<li><span><?php esc_html_e( 'پیش‌پرداخت', 'mr-booking' ); ?></span><span><?php echo esc_html( $pay['deposit'] ?: '—' ); ?></span></li>
							<?php if ( (float) ( $booking->tip_amount ?? 0 ) > 0 ) : ?>
								<li><span><?php esc_html_e( 'انعام', 'mr-booking' ); ?></span><span><?php echo esc_html( $pay['tip'] ); ?></span></li>
							<?php endif; ?>
							<li><span><?php esc_html_e( 'پرداخت', 'mr-booking' ); ?></span><span><span class="mrb-badge mrb-badge--pay-<?php echo esc_attr( $pay['status'] ); ?>"><?php echo esc_html( $pay['status_label'] ); ?></span><?php echo $pay['paid'] ? ' ' . esc_html( $pay['paid'] ) : ''; ?></span></li>
							<?php if ( $pay['method'] ) : ?>
								<li><span><?php esc_html_e( 'روش', 'mr-booking' ); ?></span><span><?php echo esc_html( $pay['method'] ); ?><?php echo $pay['ref'] && 'wallet' !== $pay['ref'] ? ' <code dir="ltr">' . esc_html( $pay['ref'] ) . '</code>' : ''; ?></span></li>
							<?php endif; ?>
						</ul>
					<?php endif; ?>
					<?php if ( count( $booking_svcs ) > 1 ) : ?>
						<ul class="mrb-appt-card__lines">
							<?php foreach ( $booking_svcs as $svc ) : ?>
								<li>
									<span><?php echo esc_html( (string) $svc->name ); ?></span>
									<span><?php echo esc_html( (float) $svc->price > 0 ? Helpers::format_price( (float) $svc->price ) : '—' ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
				<div class="mrb-appt-card">
					<span class="mrb-appt-card__label"><?php esc_html_e( 'جزئیات ثبت', 'mr-booking' ); ?></span>
					<strong><?php echo esc_html( ! empty( $booking->created_at ) ? Helpers::format_admin_datetime( (string) $booking->created_at ) : '—' ); ?></strong>
					<p>
						<?php
						if ( ! empty( $booking->notes ) ) {
							echo esc_html( (string) $booking->notes );
						} else {
							esc_html_e( 'بدون یادداشت', 'mr-booking' );
						}
						?>
					</p>
				</div>
			</div>
		</section>
		<?php if ( $booking_customer ) : ?>
			<dialog
				class="mrb-dialog"
				id="mrb-edit-customer-dialog"
				closedby="any"
				aria-labelledby="mrb-edit-customer-title"
			>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-customer-edit">
					<?php wp_nonce_field( 'mr_booking_save_customer' ); ?>
					<input type="hidden" name="action" value="mr_booking_save_customer" />
					<input type="hidden" name="id" value="<?php echo esc_attr( (string) $booking_customer->id ); ?>" />
					<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $booking->id ); ?>" />
					<input type="hidden" name="redirect" value="appointment" />

					<div class="mrb-dialog__head">
						<div>
							<h2 id="mrb-edit-customer-title"><?php esc_html_e( 'ویرایش پروفایل مشتری', 'mr-booking' ); ?></h2>
							<p><?php esc_html_e( 'نام، موبایل، ایمیل و تاریخ تولد این مشتری را از همین نوبت تغییر دهید.', 'mr-booking' ); ?></p>
						</div>
						<button
							type="button"
							class="mrb-dialog__close"
							commandfor="mrb-edit-customer-dialog"
							command="close"
							aria-label="<?php esc_attr_e( 'بستن', 'mr-booking' ); ?>"
						>
							<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
						</button>
					</div>

					<div class="mrb-dialog__body">
						<div class="mrb-settings__grid">
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?></span>
								<input type="text" name="first_name" autocomplete="given-name" required value="<?php echo esc_attr( (string) $booking_customer->first_name ); ?>" />
							</label>
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?></span>
								<input type="text" name="last_name" autocomplete="family-name" required value="<?php echo esc_attr( (string) $booking_customer->last_name ); ?>" />
							</label>
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></span>
								<input type="tel" name="phone" autocomplete="tel" inputmode="tel" dir="ltr" required value="<?php echo esc_attr( (string) $booking_customer->phone ); ?>" />
							</label>
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></span>
								<input type="email" name="email" autocomplete="email" value="<?php echo esc_attr( (string) $booking_customer->email ); ?>" />
							</label>
							<?php
							$prefix        = 'mrb-appt-customer-birth';
							$input_name    = 'birth_date';
							$show_label    = true;
							$show_required = false;
							$error_id      = '';
							$admin_picker  = true;
							$initial_value = (string) ( $booking_customer->birth_date ?? '' );
							include MR_BOOKING_PATH . 'templates/partials/birth-date-field.php';
							?>
							<label class="mrb-field mrb-field--wide">
								<span class="mrb-field__label"><?php esc_html_e( 'یادداشت', 'mr-booking' ); ?></span>
								<textarea name="notes" rows="3"><?php echo esc_textarea( (string) ( $booking_customer->notes ?? '' ) ); ?></textarea>
							</label>
						</div>
					</div>

					<div class="mrb-dialog__foot">
						<button type="button" class="button" commandfor="mrb-edit-customer-dialog" command="close">
							<?php esc_html_e( 'انصراف', 'mr-booking' ); ?>
						</button>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'ذخیره تغییرات', 'mr-booking' ); ?></button>
					</div>
				</form>
			</dialog>
			<?php
			$prefix = 'mrb-appt-customer-birth';
			include MR_BOOKING_PATH . 'templates/partials/birth-date-dialog.php';
			?>
		<?php endif; ?>
	<?php else : ?>
		<section class="mrb-appt-filters">
			<form method="get" class="mrb-appt-filters__form">
				<input type="hidden" name="page" value="mr-booking-appointments" />
				<input type="hidden" name="sort" value="<?php echo esc_attr( (string) $filters['sort'] ); ?>" />
				<input type="hidden" name="applied" value="1" />

				<div class="mrb-appt-filters__row mrb-appt-filters__row--search">
					<label class="mrb-appt-field mrb-appt-field--grow">
						<span><?php esc_html_e( 'جستجو', 'mr-booking' ); ?></span>
						<input
							type="search"
							name="s"
							value="<?php echo esc_attr( (string) $filters['search'] ); ?>"
							placeholder="<?php esc_attr_e( 'نام مشتری، کد رزرو یا بخشی از موبایل…', 'mr-booking' ); ?>"
						/>
					</label>
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></span>
						<input
							type="text"
							name="phone"
							inputmode="tel"
							value="<?php echo esc_attr( (string) $filters['phone'] ); ?>"
							placeholder="09xxxxxxxxx"
							dir="ltr"
						/>
					</label>
				</div>

				<div class="mrb-appt-filters__row">
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></span>
						<select name="status">
							<option value=""><?php esc_html_e( 'همه وضعیت‌ها', 'mr-booking' ); ?></option>
							<?php foreach ( $statuses as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></span>
						<select name="staff_id">
							<option value=""><?php esc_html_e( 'همه پرسنل', 'mr-booking' ); ?></option>
							<?php foreach ( $staff as $member ) : ?>
								<option value="<?php echo esc_attr( (string) $member->id ); ?>" <?php selected( (int) $filters['staff_id'], (int) $member->id ); ?>>
									<?php echo esc_html( Staff_Repository::display_name( $member ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></span>
						<select name="service_id">
							<option value=""><?php esc_html_e( 'همه خدمات', 'mr-booking' ); ?></option>
							<?php foreach ( $services as $svc ) : ?>
								<option value="<?php echo esc_attr( (string) $svc->id ); ?>" <?php selected( (int) $filters['service_id'], (int) $svc->id ); ?>>
									<?php echo esc_html( $svc->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'از تاریخ', 'mr-booking' ); ?></span>
						<input type="date" name="date_from" value="<?php echo esc_attr( (string) $filters['date_from'] ); ?>" />
					</label>
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'تا تاریخ', 'mr-booking' ); ?></span>
						<input type="date" name="date_to" value="<?php echo esc_attr( (string) $filters['date_to'] ); ?>" />
					</label>
				</div>

				<div class="mrb-appt-filters__actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'اعمال فیلتر', 'mr-booking' ); ?></button>
					<?php if ( $has_filters ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&filters_cleared=1' ) ); ?>"><?php esc_html_e( 'پاک کردن فیلترها', 'mr-booking' ); ?></a>
					<?php endif; ?>
				</div>
			</form>

			<div class="mrb-appt-presets">
				<span class="mrb-appt-presets__label"><?php esc_html_e( 'بازه سریع:', 'mr-booking' ); ?></span>
				<?php
				$presets = array(
					'today'    => __( 'امروز', 'mr-booking' ),
					'tomorrow' => __( 'فردا', 'mr-booking' ),
					'week'     => __( '۷ روز آینده', 'mr-booking' ),
					'month'    => __( 'این ماه', 'mr-booking' ),
				);
				foreach ( $presets as $key => $label ) :
					$url = Appointments::filter_url(
						array(
							'preset'    => $key,
							'date_from' => '',
							'date_to'   => '',
						),
						$filters
					);
					?>
					<a class="mrb-appt-chip<?php echo $filters['preset'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="mrb-appt-presets">
				<span class="mrb-appt-presets__label"><?php esc_html_e( 'مرتب‌سازی:', 'mr-booking' ); ?></span>
				<?php foreach ( Appointments::sort_options() as $key => $label ) : ?>
					<?php $url = Appointments::filter_url( array( 'sort' => $key ), $filters ); ?>
					<a class="mrb-appt-chip<?php echo $filters['sort'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</div>

			<div class="mrb-appt-status-chips">
				<?php
				$all_url = Appointments::filter_url( array( 'status' => '' ), $filters );
				?>
				<a class="mrb-appt-chip<?php echo '' === $filters['status'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $all_url ); ?>">
					<?php esc_html_e( 'همه', 'mr-booking' ); ?>
					<em><?php echo esc_html( (string) $total_matched ); ?></em>
				</a>
				<?php foreach ( $statuses as $key => $label ) : ?>
					<?php
					$count = (int) ( $status_counts[ $key ] ?? 0 );
					$url   = Appointments::filter_url( array( 'status' => $key ), $filters );
					?>
					<a class="mrb-appt-chip mrb-appt-chip--<?php echo esc_attr( $key ); ?><?php echo $filters['status'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
						<?php echo esc_html( $label ); ?>
						<em><?php echo esc_html( (string) $count ); ?></em>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<?php if ( $nearest_booking ) : ?>
			<?php
			$nearest_ymd    = substr( (string) $nearest_booking->start_datetime, 0, 10 );
			$nearest_name   = trim( (string) $nearest_booking->first_name . ' ' . (string) $nearest_booking->last_name );
			$nearest_until  = '';
			$nearest_soon   = false;
			try {
				$nearest_start = new DateTimeImmutable( (string) $nearest_booking->start_datetime, wp_timezone() );
				$nearest_now   = new DateTimeImmutable( 'now', wp_timezone() );
				if ( $nearest_start > $nearest_now ) {
					$nearest_until = human_time_diff( $nearest_now->getTimestamp(), $nearest_start->getTimestamp() );
					$nearest_soon  = ( $nearest_start->getTimestamp() - $nearest_now->getTimestamp() ) <= HOUR_IN_SECONDS;
				}
			} catch ( Exception $e ) {
				$nearest_until = '';
			}
			?>
			<section class="mrb-appt-nearest<?php echo $nearest_soon ? ' is-soon' : ''; ?>">
				<div class="mrb-appt-nearest__time">
					<span class="mrb-appt-nearest__clock"><?php echo esc_html( Helpers::format_admin_time( (string) $nearest_booking->start_datetime ) ); ?></span>
					<span class="mrb-appt-nearest__date"><?php echo esc_html( Helpers::format_admin_date( $nearest_ymd ) ); ?></span>
				</div>

				<div class="mrb-appt-nearest__body">
					<span class="mrb-appt-nearest__label">
						<span class="dashicons dashicons-clock" aria-hidden="true"></span>
						<?php esc_html_e( 'نزدیک‌ترین نوبت', 'mr-booking' ); ?>
					</span>
					<strong class="mrb-appt-nearest__name"><?php echo esc_html( $nearest_name ?: '—' ); ?></strong>
					<p class="mrb-appt-nearest__service">
						<?php echo esc_html( ! empty( $nearest_booking->service_names ) ? (string) $nearest_booking->service_names : '—' ); ?>
						<?php if ( ! empty( $nearest_booking->staff_name ) ) : ?>
							<span><?php echo esc_html( (string) $nearest_booking->staff_name ); ?></span>
						<?php endif; ?>
					</p>
					<div class="mrb-appt-nearest__tags">
						<span class="mrb-badge mrb-badge--<?php echo esc_attr( (string) $nearest_booking->status ); ?>">
							<?php echo esc_html( $statuses[ $nearest_booking->status ] ?? $nearest_booking->status ); ?>
						</span>
						<?php if ( ! empty( $nearest_booking->phone ) ) : ?>
							<a class="mrb-appt-nearest__phone" href="tel:<?php echo esc_attr( (string) $nearest_booking->phone ); ?>" dir="ltr">
								<span class="dashicons dashicons-phone" aria-hidden="true"></span>
								<?php echo esc_html( (string) $nearest_booking->phone ); ?>
							</a>
						<?php endif; ?>
						<?php if ( ! empty( $nearest_booking->booking_code ) ) : ?>
							<code class="mrb-appt-code"><?php echo esc_html( (string) $nearest_booking->booking_code ); ?></code>
						<?php endif; ?>
					</div>
				</div>

				<div class="mrb-appt-nearest__aside">
					<?php if ( $nearest_until ) : ?>
						<span class="mrb-appt-nearest__eta">
							<?php
							printf(
								/* translators: %s: relative time */
								esc_html__( '%s دیگر', 'mr-booking' ),
								esc_html( $nearest_until )
							);
							?>
						</span>
					<?php endif; ?>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . (int) $nearest_booking->id ) ); ?>">
						<?php esc_html_e( 'مشاهده نوبت', 'mr-booking' ); ?>
					</a>
					<?php if ( 'nearest' !== $filters['sort'] ) : ?>
						<a class="mrb-appt-nearest__sort" href="<?php echo esc_url( Appointments::filter_url( array( 'sort' => 'nearest' ), $filters ) ); ?>">
							<?php esc_html_e( 'لیست بر اساس زمان نوبت', 'mr-booking' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="mrb-panel mrb-appt-list">
			<div class="mrb-appt-list__meta">
				<strong>
					<?php
					printf(
						/* translators: %d: booking count */
						esc_html__( '%d نوبت', 'mr-booking' ),
						count( $bookings )
					);
					?>
				</strong>
				<?php if ( 'nearest' === $filters['sort'] ) : ?>
					<span><?php esc_html_e( 'مرتب‌شده بر اساس زمان نوبت — نزدیک‌ترین‌ها بالا', 'mr-booking' ); ?></span>
				<?php elseif ( 'latest_slot' === $filters['sort'] ) : ?>
					<span><?php esc_html_e( 'مرتب‌شده بر اساس زمان نوبت — دیرترین‌ها بالا', 'mr-booking' ); ?></span>
				<?php elseif ( $has_filters ) : ?>
					<span><?php esc_html_e( 'نتایج مطابق فیلترهای انتخاب‌شده', 'mr-booking' ); ?></span>
				<?php else : ?>
					<span><?php esc_html_e( 'آخرین نوبت‌های ثبت‌شده', 'mr-booking' ); ?></span>
				<?php endif; ?>
			</div>

			<?php if ( empty( $bookings ) ) : ?>
				<div class="mrb-appt-empty">
					<strong><?php esc_html_e( 'نوبتی پیدا نشد', 'mr-booking' ); ?></strong>
					<p><?php esc_html_e( 'فیلترها را تغییر دهید یا همه فیلترها را پاک کنید.', 'mr-booking' ); ?></p>
				</div>
			<?php else : ?>
				<div class="mrb-appt-table-wrap">
					<table class="widefat mrb-table mrb-appt-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'کد', 'mr-booking' ); ?></th>
								<th class="<?php echo 'newest' === $filters['sort'] ? 'is-sorted' : ''; ?>">
									<a href="<?php echo esc_url( Appointments::filter_url( array( 'sort' => 'newest' ), $filters ) ); ?>">
										<?php esc_html_e( 'ثبت', 'mr-booking' ); ?>
									</a>
								</th>
								<th><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></th>
								<th class="<?php echo in_array( $filters['sort'], array( 'nearest', 'latest_slot' ), true ) ? 'is-sorted' : ''; ?>">
									<a href="<?php echo esc_url( Appointments::filter_url( array( 'sort' => 'nearest' === $filters['sort'] ? 'latest_slot' : 'nearest' ), $filters ) ); ?>">
										<?php esc_html_e( 'زمان', 'mr-booking' ); ?>
									</a>
								</th>
								<th><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'پرداخت', 'mr-booking' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $bookings as $b ) : ?>
								<?php
								$ymd   = substr( (string) $b->start_datetime, 0, 10 );
								$start = substr( (string) $b->start_datetime, 11, 5 );
								$end   = substr( (string) $b->end_datetime, 11, 5 );
								$row_holiday = Holiday_Repository::find_by_date( $ymd );
								$row_official = $row_holiday && ! empty( $row_holiday->is_official );
								?>
								<tr data-booking-id="<?php echo esc_attr( (string) $b->id ); ?>"<?php echo ( $nearest_booking && (int) $nearest_booking->id === (int) $b->id ) ? ' class="is-nearest"' : ''; ?>>
									<td>
										<code class="mrb-appt-code"><?php echo esc_html( $b->booking_code ); ?></code>
									</td>
									<td class="mrb-appt-created">
										<span><?php echo esc_html( ! empty( $b->created_at ) ? Helpers::format_admin_datetime( (string) $b->created_at ) : '—' ); ?></span>
									</td>
									<td>
										<strong class="mrb-appt-customer"><?php echo esc_html( trim( $b->first_name . ' ' . $b->last_name ) ); ?></strong>
										<a class="mrb-appt-phone" href="tel:<?php echo esc_attr( $b->phone ); ?>" dir="ltr"><?php echo esc_html( $b->phone ); ?></a>
										<?php echo \MRBooking\Wallet\Wallet_Repository::badge( (float) ( $b->wallet_balance ?? 0 ), 'mrb-wallet-chip--inline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
									</td>
									<td><?php echo esc_html( $b->service_names ?: '—' ); ?></td>
									<td><?php echo esc_html( $b->staff_name ?: '—' ); ?></td>
									<td class="mrb-appt-time">
										<strong><?php echo esc_html( Helpers::format_admin_date( $ymd ) ); ?></strong>
										<span><?php echo esc_html( Helpers::format_admin_time( $b->start_datetime ) . ' – ' . Helpers::format_admin_time( $b->end_datetime ) ); ?></span>
										<?php if ( Booking_Repository::is_walkin( $b ) ) : ?>
											<span class="mrb-badge mrb-badge--walkin mrb-appt-time__holiday"><?php esc_html_e( 'حضوری', 'mr-booking' ); ?></span>
										<?php endif; ?>
										<?php if ( $row_official ) : ?>
											<span class="mrb-badge mrb-badge--holiday mrb-appt-time__holiday"><?php esc_html_e( 'تعطیل رسمی', 'mr-booking' ); ?></span>
										<?php endif; ?>
										<?php if ( 'both' === Helpers::admin_calendar_mode() ) : ?>
											<small><?php echo esc_html( $ymd ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="mrb-badge mrb-badge--<?php echo esc_attr( $b->status ); ?>">
											<?php echo esc_html( $statuses[ $b->status ] ?? $b->status ); ?>
										</span>
									</td>
									<td class="mrb-appt-pay">
										<?php $rp = \MRBooking\Payments\Payment_Service::admin_summary( $b ); ?>
										<?php if ( 'none' === $rp['status'] && (float) ( $b->deposit_amount ?? 0 ) <= 0 ) : ?>
											<span class="mrb-appt-pay__none">—</span>
										<?php else : ?>
											<span class="mrb-badge mrb-badge--pay-<?php echo esc_attr( $rp['status'] ); ?>"><?php echo esc_html( $rp['status_label'] ); ?></span>
											<?php if ( $rp['method'] ) : ?>
												<span class="mrb-appt-pay__method mrb-appt-pay__method--<?php echo esc_attr( (string) ( $b->payment_method ?? '' ) ); ?>">
													<span class="dashicons <?php echo 'wallet' === (string) ( $b->payment_method ?? '' ) ? 'dashicons-money-alt' : 'dashicons-cart'; ?>" aria-hidden="true"></span>
													<?php echo esc_html( $rp['method'] ); ?>
												</span>
											<?php endif; ?>
											<?php if ( $rp['paid'] ) : ?>
												<span class="mrb-appt-pay__amount"><?php echo esc_html( $rp['paid'] ); ?></span>
											<?php endif; ?>
										<?php endif; ?>
									</td>
									<td class="mrb-appt-actions">
										<?php if ( 'pending' === $b->status ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-appt-inline-action">
												<?php wp_nonce_field( 'mr_booking_update_status' ); ?>
												<input type="hidden" name="action" value="mr_booking_update_status" />
												<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $b->id ); ?>" />
												<input type="hidden" name="status" value="confirmed" />
												<input type="hidden" name="redirect" value="list" />
												<button type="submit" class="button button-primary button-small"><?php esc_html_e( 'تأیید', 'mr-booking' ); ?></button>
											</form>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-appt-inline-action">
												<?php wp_nonce_field( 'mr_booking_update_status' ); ?>
												<input type="hidden" name="action" value="mr_booking_update_status" />
												<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $b->id ); ?>" />
												<input type="hidden" name="status" value="rejected" />
												<input type="hidden" name="redirect" value="list" />
												<button type="submit" class="button button-small"><?php esc_html_e( 'رد', 'mr-booking' ); ?></button>
											</form>
										<?php elseif ( 'confirmed' === $b->status && Booking_Repository::is_upcoming( $b ) ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-appt-inline-action">
												<?php wp_nonce_field( 'mr_booking_cancel_booking' ); ?>
												<input type="hidden" name="action" value="mr_booking_cancel_booking" />
												<input type="hidden" name="booking_id" value="<?php echo esc_attr( (string) $b->id ); ?>" />
												<input type="hidden" name="redirect" value="list" />
												<button type="submit" class="button button-small"><?php esc_html_e( 'لغو', 'mr-booking' ); ?></button>
											</form>
										<?php endif; ?>
										<a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $b->id ) ); ?>">
											<?php esc_html_e( 'جزئیات', 'mr-booking' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	<?php endif; ?>
</div>
