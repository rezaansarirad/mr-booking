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
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
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
			&& Booking_Repository::blocks_slot( (string) $booking->status );
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
					<span class="mrb-appt-card__label"><?php esc_html_e( 'زمان نوبت', 'mr-booking' ); ?></span>
					<strong><?php echo esc_html( Helpers::format_admin_dual_date( $date_ymd ) ); ?></strong>
					<p><?php echo esc_html( Helpers::format_admin_time( $booking->start_datetime ) . ' — ' . Helpers::format_admin_time( $booking->end_datetime ) ); ?></p>
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
					<span class="mrb-appt-card__label"><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></span>
					<strong><?php echo esc_html( trim( $booking->first_name . ' ' . $booking->last_name ) ); ?></strong>
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
	<?php else : ?>
		<section class="mrb-appt-filters">
			<form method="get" class="mrb-appt-filters__form">
				<input type="hidden" name="page" value="mr-booking-appointments" />

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
						<a class="button" href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'پاک کردن فیلترها', 'mr-booking' ); ?></a>
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
				<?php if ( $has_filters ) : ?>
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
								<th><?php esc_html_e( 'ثبت', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'زمان', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></th>
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
								<tr data-booking-id="<?php echo esc_attr( (string) $b->id ); ?>">
									<td>
										<code class="mrb-appt-code"><?php echo esc_html( $b->booking_code ); ?></code>
									</td>
									<td class="mrb-appt-created">
										<span><?php echo esc_html( ! empty( $b->created_at ) ? Helpers::format_admin_datetime( (string) $b->created_at ) : '—' ); ?></span>
									</td>
									<td>
										<strong class="mrb-appt-customer"><?php echo esc_html( trim( $b->first_name . ' ' . $b->last_name ) ); ?></strong>
										<a class="mrb-appt-phone" href="tel:<?php echo esc_attr( $b->phone ); ?>" dir="ltr"><?php echo esc_html( $b->phone ); ?></a>
									</td>
									<td><?php echo esc_html( $b->service_names ?: '—' ); ?></td>
									<td><?php echo esc_html( $b->staff_name ?: '—' ); ?></td>
									<td class="mrb-appt-time">
										<strong><?php echo esc_html( Helpers::format_admin_date( $ymd ) ); ?></strong>
										<span><?php echo esc_html( Helpers::format_admin_time( $b->start_datetime ) . ' – ' . Helpers::format_admin_time( $b->end_datetime ) ); ?></span>
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
