<?php
/**
 * Working hours template.
 *
 * @package MRBooking
 * @var array<int, list<object>> $grouped
 * @var array<int, string>       $labels
 * @var list<int>                $order
 * @var string                   $hours_mode
 * @var list<object>             $staff_list
 * @var int                      $staff_id
 * @var object|null              $selected
 */

defined( 'ABSPATH' ) || exit;

$is_per_staff = 'per_staff' === $hours_mode;
?>
<div class="wrap mrb-admin mrb-hours-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
			<h1><?php esc_html_e( 'ساعات کاری', 'mr-booking' ); ?></h1>
			<p class="mrb-hours-page__lead">
				<?php if ( $is_per_staff ) : ?>
					<?php esc_html_e( 'ساعات کاری هر پرسنل را جداگانه تنظیم کنید. اگر برای پرسنلی ساعتی تعریف نشود، ساعات کلی سازمان اعمال می‌شود.', 'mr-booking' ); ?>
				<?php else : ?>
					<?php esc_html_e( 'ساعات کاری کلی سازمان برای همه پرسنل. برای هر روز می‌توانید چند بازه (مثلاً صبح و عصر) تعریف کنید.', 'mr-booking' ); ?>
				<?php endif; ?>
			</p>
		</div>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-settings&tab=general' ) ); ?>">
			<?php esc_html_e( 'تنظیمات ساعات', 'mr-booking' ); ?>
		</a>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'ساعات کاری ذخیره شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['error'] ) && 'staff' === $_GET['error'] ) : // phpcs:ignore ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'لطفاً پرسنل را انتخاب کنید.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $is_per_staff && empty( $staff_list ) ) : ?>
		<div class="mrb-panel">
			<p class="mrb-empty"><?php esc_html_e( 'ابتدا از بخش پرسنل، حداقل یک نفر فعال ثبت کنید.', 'mr-booking' ); ?></p>
		</div>
	<?php else : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-hours-form">
			<?php wp_nonce_field( 'mr_booking_save_hours' ); ?>
			<input type="hidden" name="action" value="mr_booking_save_hours" />
			<?php if ( $is_per_staff ) : ?>
				<input type="hidden" name="staff_id" value="<?php echo esc_attr( (string) $staff_id ); ?>" />
				<div class="mrb-hours-toolbar mrb-panel mrb-hours-staff-picker">
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></span>
						<select id="mrb-hours-staff-select" onchange="if(this.value){window.location='<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-hours&staff_id=' ) ); ?>'+this.value;}">
							<?php foreach ( $staff_list as $member ) : ?>
								<option value="<?php echo esc_attr( (string) $member->id ); ?>" <?php selected( $staff_id, (int) $member->id ); ?>>
									<?php echo esc_html( \MRBooking\Staff\Staff_Repository::display_name( $member ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
					<?php if ( $selected ) : ?>
						<p class="mrb-hours-staff-note">
							<?php
							printf(
								/* translators: %s: staff name */
								esc_html__( 'در حال ویرایش ساعات: %s', 'mr-booking' ),
								esc_html( \MRBooking\Staff\Staff_Repository::display_name( $selected ) )
							);
							?>
						</p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="mrb-hours-toolbar mrb-panel">
				<label class="mrb-check mrb-hours-apply-all">
					<input type="checkbox" name="apply_all" value="1" />
					<span>
						<strong><?php esc_html_e( 'اعمال ساعات یکسان برای همه روزهای باز', 'mr-booking' ); ?></strong>
						<small><?php esc_html_e( 'اولین روز باز را به بقیه روزهای غیرتعطیل کپی می‌کند.', 'mr-booking' ); ?></small>
					</span>
				</label>
			</div>

			<?php
			$name_prefix = 'days';
			include MR_BOOKING_PATH . 'templates/admin/partials/week-hours-grid.php';
			?>

			<footer class="mrb-hours-footer">
				<button class="button button-primary button-hero"><?php esc_html_e( 'ذخیره ساعات کاری', 'mr-booking' ); ?></button>
			</footer>
		</form>
	<?php endif; ?>
</div>
