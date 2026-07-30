<?php
/**
 * New staff modal dialog.
 *
 * @package MRBooking
 * @var list<object>              $services
 * @var string                    $hours_mode
 * @var array<int, list<object>>  $new_hours_grouped
 * @var array<int, string>        $labels
 * @var list<int>                 $order
 */

defined( 'ABSPATH' ) || exit;
?>
<dialog id="mrb-staff-new-dialog" class="mrb-dialog mrb-staff-new-dialog" aria-labelledby="mrb-staff-new-title">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-staff-form mrb-staff-new-form">
		<?php wp_nonce_field( 'mr_booking_save_staff' ); ?>
		<input type="hidden" name="action" value="mr_booking_save_staff" />
		<input type="hidden" name="id" value="0" />

		<header class="mrb-dialog__head">
			<div>
				<h2 id="mrb-staff-new-title"><?php esc_html_e( 'پرسنل جدید', 'mr-booking' ); ?></h2>
				<p><?php esc_html_e( 'اطلاعات پایه، خدمات و ساعات کاری را وارد کنید.', 'mr-booking' ); ?></p>
			</div>
			<button type="button" class="mrb-dialog__close" data-close-dialog="mrb-staff-new-dialog" aria-label="<?php esc_attr_e( 'بستن', 'mr-booking' ); ?>">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</header>

		<div class="mrb-dialog__body">
			<div class="mrb-staff-form__grid">
				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?> *</span>
					<input type="text" name="first_name" required autocomplete="given-name" />
				</label>
				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?> *</span>
					<input type="text" name="last_name" required autocomplete="family-name" />
				</label>
				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></span>
					<input type="tel" name="phone" placeholder="09xxxxxxxxx" autocomplete="tel" />
				</label>
				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></span>
					<input type="email" name="email" autocomplete="email" />
				</label>
				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></span>
					<select name="status">
						<option value="active" selected><?php esc_html_e( 'فعال', 'mr-booking' ); ?></option>
						<option value="inactive"><?php esc_html_e( 'غیرفعال', 'mr-booking' ); ?></option>
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
								<input type="checkbox" name="service_ids[]" value="<?php echo esc_attr( (string) $svc->id ); ?>" />
								<span class="mrb-check-chip__label"><?php echo esc_html( $svc->name ); ?></span>
								<span class="mrb-check-chip__meta"><?php echo esc_html( \MRBooking\Helpers::format_duration( (int) $svc->duration ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="mrb-staff-schedule-box mrb-staff-new-dialog__hours">
				<div class="mrb-staff-schedule-box__head">
					<strong><?php esc_html_e( 'ساعات کاری', 'mr-booking' ); ?></strong>
					<?php if ( 'per_staff' === $hours_mode ) : ?>
						<span><?php esc_html_e( 'روزها و بازه‌های کاری این پرسنل را مشخص کنید.', 'mr-booking' ); ?></span>
					<?php else : ?>
						<span><?php esc_html_e( 'ساعات اولیه پرسنل — در حالت «ساعات کلی» از منوی ساعات کاری هم استفاده می‌شود.', 'mr-booking' ); ?></span>
					<?php endif; ?>
				</div>

				<label class="mrb-check mrb-hours-apply-all">
					<input type="checkbox" name="apply_all" value="1" />
					<span>
						<strong><?php esc_html_e( 'اعمال ساعات یکسان برای همه روزهای باز', 'mr-booking' ); ?></strong>
					</span>
				</label>

				<?php
				$grouped     = $new_hours_grouped;
				$name_prefix = 'days';
				include MR_BOOKING_PATH . 'templates/admin/partials/week-hours-grid.php';
				?>
			</div>
		</div>

		<footer class="mrb-dialog__foot">
			<button type="button" class="button" data-close-dialog="mrb-staff-new-dialog"><?php esc_html_e( 'انصراف', 'mr-booking' ); ?></button>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'ذخیره پرسنل', 'mr-booking' ); ?></button>
		</footer>
	</form>
</dialog>
