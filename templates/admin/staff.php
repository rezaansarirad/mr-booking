<?php
/**
 * Staff admin template.
 *
 * @package MRBooking
 * @var list<object> $staff_list
 * @var list<object> $services
 * @var list<int>    $linked
 * @var object|null  $staff
 * @var int          $edit_id
 * @var bool         $editing
 * @var string       $hours_mode
 * @var array<int, list<object>> $hours_grouped
 * @var array<int, list<array{start:string,end:string,label:string}>> $blocks_grouped
 * @var array<int, string> $labels
 * @var list<int>    $order
 * @var bool         $open_new
 * @var int          $global_break
 * @var array<int, list<object>> $new_hours_grouped
 */

defined( 'ABSPATH' ) || exit;

$service_map = array();
foreach ( $services as $svc ) {
	$service_map[ (int) $svc->id ] = $svc;
}
?>
<div class="wrap mrb-admin mrb-staff-page" dir="rtl"<?php echo ! empty( $open_new ) ? ' data-open-staff-dialog="1"' : ''; ?>>
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
			<h1><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></h1>
			<p class="mrb-staff-page__lead"><?php esc_html_e( 'پرسنل را مدیریت کنید و خدمات قابل ارائه هر نفر را مشخص کنید.', 'mr-booking' ); ?></p>
		</div>
		<button type="button" class="button button-primary" id="mrb-staff-new-open">
			<?php esc_html_e( '+ پرسنل جدید', 'mr-booking' ); ?>
		</button>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تغییرات ذخیره شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['deleted'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'پرسنل حذف شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<div class="mrb-staff-layout <?php echo $editing ? 'is-editing' : ''; ?>">
		<section class="mrb-panel mrb-staff-list-panel">
			<h2>
				<?php esc_html_e( 'لیست پرسنل', 'mr-booking' ); ?>
				<span class="mrb-count"><?php echo esc_html( (string) count( $staff_list ) ); ?></span>
			</h2>

			<?php if ( empty( $staff_list ) ) : ?>
				<p class="mrb-empty"><?php esc_html_e( 'هنوز پرسنلی ثبت نشده است.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<div class="mrb-staff-cards">
					<?php foreach ( $staff_list as $s ) : ?>
						<?php
						$active       = 'active' === $s->status;
						$linked_ids   = \MRBooking\Staff\Staff_Repository::service_ids( (int) $s->id );
						$linked_names = array();
						foreach ( $linked_ids as $sid ) {
							if ( isset( $service_map[ $sid ] ) ) {
								$linked_names[] = $service_map[ $sid ]->name;
							}
						}
						$initials = mb_substr( (string) $s->first_name, 0, 1 ) . mb_substr( (string) $s->last_name, 0, 1 );
						?>
						<article class="mrb-staff-card <?php echo $edit_id === (int) $s->id ? 'is-active' : ''; ?> <?php echo $active ? '' : 'is-inactive'; ?>">
							<header class="mrb-staff-card__top">
								<div class="mrb-staff-card__identity">
									<span class="mrb-staff-card__avatar" aria-hidden="true"><?php echo esc_html( $initials ); ?></span>
									<div>
										<h3><?php echo esc_html( \MRBooking\Staff\Staff_Repository::display_name( $s ) ); ?></h3>
										<?php if ( ! empty( $s->phone ) ) : ?>
											<p><?php echo esc_html( (string) $s->phone ); ?></p>
										<?php elseif ( ! empty( $s->email ) ) : ?>
											<p><?php echo esc_html( (string) $s->email ); ?></p>
										<?php endif; ?>
									</div>
								</div>
								<span class="mrb-badge mrb-badge--<?php echo $active ? 'confirmed' : 'cancelled'; ?>">
									<?php echo $active ? esc_html__( 'فعال', 'mr-booking' ) : esc_html__( 'غیرفعال', 'mr-booking' ); ?>
								</span>
							</header>

							<div class="mrb-staff-card__meta">
								<span class="mrb-staff-card__stat">
									<span class="dashicons dashicons-admin-tools"></span>
									<?php
									echo esc_html(
										$linked_names
											? sprintf(
												/* translators: %d: service count */
												_n( '%d خدمت', '%d خدمت', count( $linked_names ), 'mr-booking' ),
												count( $linked_names )
											)
											: __( 'بدون خدمت', 'mr-booking' )
									);
									?>
								</span>
							</div>

							<?php if ( $linked_names ) : ?>
								<div class="mrb-staff-card__services">
									<?php foreach ( array_slice( $linked_names, 0, 4 ) as $name ) : ?>
										<span class="mrb-staff-card__service-tag"><?php echo esc_html( $name ); ?></span>
									<?php endforeach; ?>
									<?php if ( count( $linked_names ) > 4 ) : ?>
										<span class="mrb-staff-card__service-tag is-more">+<?php echo esc_html( (string) ( count( $linked_names ) - 4 ) ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>

							<footer class="mrb-staff-card__actions">
								<a class="button button-small button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-staff&edit=' . $s->id ) ); ?>">
									<?php esc_html_e( 'ویرایش', 'mr-booking' ); ?>
								</a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm(mrBookingAdmin.i18n.confirmDelete);">
									<?php wp_nonce_field( 'mr_booking_delete_staff' ); ?>
									<input type="hidden" name="action" value="mr_booking_delete_staff" />
									<input type="hidden" name="id" value="<?php echo esc_attr( (string) $s->id ); ?>" />
									<button class="button-link-delete" type="submit"><?php esc_html_e( 'حذف', 'mr-booking' ); ?></button>
								</form>
							</footer>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( $editing ) : ?>
			<section class="mrb-panel mrb-staff-editor">
				<div class="mrb-staff-editor__head">
					<h2><?php esc_html_e( 'ویرایش پرسنل', 'mr-booking' ); ?></h2>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-staff' ) ); ?>"><?php esc_html_e( 'بستن', 'mr-booking' ); ?></a>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-staff-form">
					<?php wp_nonce_field( 'mr_booking_save_staff' ); ?>
					<input type="hidden" name="action" value="mr_booking_save_staff" />
					<input type="hidden" name="id" value="<?php echo esc_attr( (string) ( $staff?->id ?? 0 ) ); ?>" />

					<div class="mrb-staff-form__grid">
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?></span>
							<input type="text" name="first_name" required value="<?php echo esc_attr( (string) ( $staff?->first_name ?? '' ) ); ?>" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?></span>
							<input type="text" name="last_name" required value="<?php echo esc_attr( (string) ( $staff?->last_name ?? '' ) ); ?>" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></span>
							<input type="text" name="phone" value="<?php echo esc_attr( (string) ( $staff?->phone ?? '' ) ); ?>" placeholder="09xxxxxxxxx" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></span>
							<input type="email" name="email" value="<?php echo esc_attr( (string) ( $staff?->email ?? '' ) ); ?>" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'شناسه تصویر', 'mr-booking' ); ?></span>
							<input type="number" name="image_id" value="<?php echo esc_attr( (string) ( $staff?->image_id ?? '' ) ); ?>" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></span>
							<select name="status">
								<option value="active" <?php selected( $staff?->status ?? 'active', 'active' ); ?>><?php esc_html_e( 'فعال', 'mr-booking' ); ?></option>
								<option value="inactive" <?php selected( $staff?->status ?? '', 'inactive' ); ?>><?php esc_html_e( 'غیرفعال', 'mr-booking' ); ?></option>
							</select>
						</label>
					</div>

					<div class="mrb-staff-services-box">
						<div class="mrb-staff-services-box__head">
							<strong><?php esc_html_e( 'خدمات قابل ارائه', 'mr-booking' ); ?></strong>
							<span><?php esc_html_e( 'خدماتی که این پرسنل می‌تواند انجام دهد را انتخاب کنید.', 'mr-booking' ); ?></span>
						</div>

						<?php if ( empty( $services ) ) : ?>
							<p class="mrb-empty"><?php esc_html_e( 'ابتدا از بخش خدمات، حداقل یک خدمت فعال تعریف کنید.', 'mr-booking' ); ?></p>
						<?php else : ?>
							<div class="mrb-check-grid" role="group" aria-label="<?php esc_attr_e( 'خدمات پرسنل', 'mr-booking' ); ?>">
								<?php foreach ( $services as $svc ) : ?>
									<label class="mrb-check-chip">
										<input
											type="checkbox"
											name="service_ids[]"
											value="<?php echo esc_attr( (string) $svc->id ); ?>"
											<?php checked( in_array( (int) $svc->id, $linked, true ) ); ?>
										/>
										<span class="mrb-check-chip__label"><?php echo esc_html( $svc->name ); ?></span>
										<span class="mrb-check-chip__meta"><?php echo esc_html( \MRBooking\Helpers::format_duration( (int) $svc->duration ) ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<?php if ( $staff ) : ?>
						<div class="mrb-staff-schedule-box">
							<div class="mrb-staff-schedule-box__head">
								<strong><?php esc_html_e( 'زمان استراحت بین نوبت‌ها', 'mr-booking' ); ?></strong>
								<span><?php esc_html_e( 'بعد از هر رزرو، چند دقیقه فاصله تا نوبت بعدی لازم است.', 'mr-booking' ); ?></span>
							</div>
							<label class="mrb-field mrb-field--inline">
								<span class="mrb-field__label"><?php esc_html_e( 'دقیقه استراحت', 'mr-booking' ); ?></span>
								<input type="number" name="break_minutes" min="0" step="5" value="<?php echo esc_attr( (string) (int) ( $staff->break_minutes ?? 0 ) ); ?>" />
								<span class="mrb-field__hint">
									<?php
									if ( $global_break > 0 ) {
										printf(
											/* translators: %d: minutes */
											esc_html__( '۰ = استفاده از مقدار کلی سازمان (%d دقیقه)', 'mr-booking' ),
											(int) $global_break
										);
									} else {
										esc_html_e( '۰ = بدون استراحت اجباری بین نوبت‌ها', 'mr-booking' );
									}
									?>
								</span>
							</label>
						</div>

						<?php if ( 'per_staff' === $hours_mode ) : ?>
							<div class="mrb-staff-schedule-box">
								<div class="mrb-staff-schedule-box__head">
									<strong><?php esc_html_e( 'روزها و ساعات کاری این پرسنل', 'mr-booking' ); ?></strong>
									<span><?php esc_html_e( 'ساعات جدا از سایر پرسنل. می‌توانید چند بازه در روز تعریف کنید.', 'mr-booking' ); ?></span>
								</div>
								<label class="mrb-check mrb-hours-apply-all" style="margin-bottom:12px">
									<input type="checkbox" name="apply_all" value="1" />
									<span>
										<strong><?php esc_html_e( 'اعمال ساعات یکسان برای همه روزهای باز', 'mr-booking' ); ?></strong>
									</span>
								</label>
								<?php
								$grouped     = $hours_grouped;
								$name_prefix = 'days';
								include MR_BOOKING_PATH . 'templates/admin/partials/week-hours-grid.php';
								?>
							</div>
						<?php else : ?>
							<p class="mrb-settings__hint">
								<?php esc_html_e( 'ساعات کاری کلی سازمان از منوی «ساعات کاری» تنظیم می‌شود.', 'mr-booking' ); ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-hours' ) ); ?>"><?php esc_html_e( 'مشاهده ساعات کلی', 'mr-booking' ); ?></a>
							</p>
						<?php endif; ?>

						<div class="mrb-staff-schedule-box">
							<div class="mrb-staff-schedule-box__head">
								<strong><?php esc_html_e( 'ساعات مسدود (غیرقابل رزرو)', 'mr-booking' ); ?></strong>
								<span><?php esc_html_e( 'بازه‌هایی مثل ناهار که هر هفته تکرار می‌شوند و نباید رزرو شوند.', 'mr-booking' ); ?></span>
							</div>
							<?php include MR_BOOKING_PATH . 'templates/admin/partials/staff-time-blocks.php'; ?>
						</div>
					<?php endif; ?>

					<button class="button button-primary button-hero" style="margin-top:14px"><?php esc_html_e( 'ذخیره پرسنل', 'mr-booking' ); ?></button>
				</form>
			</section>
		<?php endif; ?>
	</div>

	<?php include MR_BOOKING_PATH . 'templates/admin/partials/staff-new-dialog.php'; ?>
</div>
