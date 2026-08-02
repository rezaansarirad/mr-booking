<?php
/**
 * Services admin template.
 *
 * @package MRBooking
 */

defined( 'ABSPATH' ) || exit;

use MRBooking\Helpers;

$duration_presets = array( 15, 20, 30, 45, 60, 90, 120 );
$current_duration = (int) ( $service?->duration ?? 30 );
$has_price_val    = $service ? \MRBooking\Services\Service_Repository::has_price( $service ) : false;
$global_prices    = ! empty( \MRBooking\Settings\Settings::get_value( 'show_prices' ) );
$list_url         = admin_url( 'admin.php?page=mr-booking-services' );
$new_service_url  = admin_url( 'admin.php?page=mr-booking-services&new=1' );
?>
<div class="wrap mrb-admin mrb-services-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
			<h1><?php esc_html_e( 'خدمات', 'mr-booking' ); ?></h1>
			<p class="mrb-services-page__lead">
				<?php esc_html_e( 'هر خدمت را کامل ویرایش کنید؛ بازه زمانی و وضعیت قیمت (دارد / ندارد) برای هر مورد جداگانه مشخص می‌شود.', 'mr-booking' ); ?>
			</p>
		</div>
		<?php if ( ! $creating ) : ?>
			<a class="button button-primary" href="<?php echo esc_url( $new_service_url ); ?>">
				<?php esc_html_e( '+ خدمت جدید', 'mr-booking' ); ?>
			</a>
		<?php endif; ?>
	</header>

	<?php if ( ! empty( $_GET['saved'] ) || ! empty( $_GET['updated'] ) ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'تغییرات ذخیره شد.', 'mr-booking' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $global_prices ) : ?>
		<div class="notice notice-info">
			<p>
				<?php esc_html_e( 'نمایش قیمت در فرم مشتری خاموش است.', 'mr-booking' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-settings&tab=general' ) ); ?>"><?php esc_html_e( 'فعال‌سازی از تنظیمات', 'mr-booking' ); ?></a>
				<?php esc_html_e( '— با این حال می‌توانید برای هر خدمت مشخص کنید قیمت دارد یا نه.', 'mr-booking' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="mrb-services-layout <?php echo $show_form ? 'is-editing' : ''; ?> <?php echo $creating ? 'is-creating' : ''; ?>">
		<section class="mrb-panel mrb-services-list-panel">
			<h2><?php esc_html_e( 'لیست خدمات', 'mr-booking' ); ?> <span class="mrb-count"><?php echo esc_html( (string) count( $services ) ); ?></span></h2>

			<?php if ( empty( $services ) ) : ?>
				<p class="mrb-empty"><?php esc_html_e( 'هنوز خدمتی ثبت نشده است.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<div class="mrb-service-cards">
					<?php foreach ( $services as $s ) : ?>
						<?php
						$priced = \MRBooking\Services\Service_Repository::has_price( $s );
						$active = 'active' === $s->status;
						?>
						<article class="mrb-service-card <?php echo $edit_id === (int) $s->id ? 'is-active' : ''; ?> <?php echo $active ? '' : 'is-inactive'; ?>">
							<header class="mrb-service-card__top">
								<div>
									<h3><?php echo esc_html( $s->name ); ?></h3>
									<?php if ( ! empty( $s->description ) ) : ?>
										<p><?php echo esc_html( wp_html_excerpt( (string) $s->description, 80, '…' ) ); ?></p>
									<?php endif; ?>
								</div>
								<span class="mrb-badge mrb-badge--<?php echo $active ? 'confirmed' : 'cancelled'; ?>">
									<?php echo $active ? esc_html__( 'فعال', 'mr-booking' ) : esc_html__( 'غیرفعال', 'mr-booking' ); ?>
								</span>
							</header>

							<div class="mrb-service-card__meta">
								<span class="mrb-duration-badge">
									<span class="dashicons dashicons-clock"></span>
									<?php echo esc_html( \MRBooking\Helpers::format_duration( (int) $s->duration ) ); ?>
								</span>

								<?php if ( $priced ) : ?>
									<span class="mrb-price-badge mrb-price-badge--yes">
										<span class="dashicons dashicons-tag"></span>
										<?php
										echo (float) $s->price > 0
											? esc_html( \MRBooking\Helpers::format_price( (float) $s->price ) )
											: esc_html__( 'دارای قیمت', 'mr-booking' );
										?>
									</span>
								<?php else : ?>
									<span class="mrb-price-badge mrb-price-badge--no">
										<span class="dashicons dashicons-dismiss"></span>
										<?php esc_html_e( 'بدون قیمت', 'mr-booking' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<footer class="mrb-service-card__actions">
								<a class="button button-small button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-services&edit=' . $s->id ) ); ?>">
									<?php esc_html_e( 'ویرایش کامل', 'mr-booking' ); ?>
								</a>

								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'mr_booking_service_toggle' ); ?>
									<input type="hidden" name="action" value="mr_booking_service_toggle" />
									<input type="hidden" name="id" value="<?php echo esc_attr( (string) $s->id ); ?>" />
									<input type="hidden" name="field" value="has_price" />
									<button class="button button-small" type="submit">
										<?php echo $priced ? esc_html__( 'حذف قیمت', 'mr-booking' ) : esc_html__( 'فعال‌سازی قیمت', 'mr-booking' ); ?>
									</button>
								</form>

								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'mr_booking_service_toggle' ); ?>
									<input type="hidden" name="action" value="mr_booking_service_toggle" />
									<input type="hidden" name="id" value="<?php echo esc_attr( (string) $s->id ); ?>" />
									<input type="hidden" name="field" value="status" />
									<button class="button button-small" type="submit">
										<?php echo $active ? esc_html__( 'غیرفعال', 'mr-booking' ) : esc_html__( 'فعال', 'mr-booking' ); ?>
									</button>
								</form>

								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm(mrBookingAdmin.i18n.confirmDelete);">
									<?php wp_nonce_field( 'mr_booking_delete_service' ); ?>
									<input type="hidden" name="action" value="mr_booking_delete_service" />
									<input type="hidden" name="id" value="<?php echo esc_attr( (string) $s->id ); ?>" />
									<button class="button-link-delete" type="submit"><?php esc_html_e( 'حذف', 'mr-booking' ); ?></button>
								</form>
							</footer>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php if ( $show_form ) : ?>
			<section class="mrb-panel mrb-service-editor" id="mrb-service-editor">
				<div class="mrb-service-editor__head">
					<h2>
						<?php
						echo $creating
							? esc_html__( 'خدمت جدید', 'mr-booking' )
							: esc_html__( 'ویرایش خدمت', 'mr-booking' );
						?>
					</h2>
					<a href="<?php echo esc_url( $list_url ); ?>"><?php esc_html_e( 'بستن', 'mr-booking' ); ?></a>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form" id="mrb-service-form">
					<?php wp_nonce_field( 'mr_booking_save_service' ); ?>
					<input type="hidden" name="action" value="mr_booking_save_service" />
					<input type="hidden" name="id" value="<?php echo esc_attr( (string) ( $service?->id ?? 0 ) ); ?>" />

					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'نام خدمت', 'mr-booking' ); ?></span>
						<input type="text" name="name" required value="<?php echo esc_attr( (string) ( $service?->name ?? '' ) ); ?>" />
					</label>

					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'توضیحات', 'mr-booking' ); ?></span>
						<textarea name="description" rows="3"><?php echo esc_textarea( (string) ( $service?->description ?? '' ) ); ?></textarea>
					</label>

					<div class="mrb-duration-box">
						<div class="mrb-duration-box__head">
							<strong><?php esc_html_e( 'بازه زمانی خدمت', 'mr-booking' ); ?></strong>
							<span><?php esc_html_e( 'مدت انجام این خدمت برای محاسبه نوبت‌ها', 'mr-booking' ); ?></span>
						</div>
						<div class="mrb-duration-presets" role="group">
							<?php foreach ( $duration_presets as $mins ) : ?>
								<button type="button" class="mrb-duration-chip <?php echo $current_duration === $mins ? 'is-active' : ''; ?>" data-duration="<?php echo esc_attr( (string) $mins ); ?>">
									<?php echo esc_html( \MRBooking\Helpers::format_duration( $mins ) ); ?>
								</button>
							<?php endforeach; ?>
						</div>
						<label class="mrb-field mrb-duration-custom">
							<span class="mrb-field__label"><?php esc_html_e( 'مدت سفارشی (دقیقه)', 'mr-booking' ); ?></span>
							<div class="mrb-duration-input">
								<input type="number" name="duration" id="mrb-service-duration" min="5" step="5" required value="<?php echo esc_attr( (string) $current_duration ); ?>" />
								<em><?php esc_html_e( 'دقیقه', 'mr-booking' ); ?></em>
							</div>
						</label>
						<p class="mrb-duration-preview" id="mrb-duration-preview"></p>
					</div>

					<div class="mrb-price-box">
						<div class="mrb-price-box__head">
							<strong><?php esc_html_e( 'وضعیت قیمت', 'mr-booking' ); ?></strong>
							<span><?php esc_html_e( 'مشخص کنید این خدمت قیمت دارد یا رایگان / بدون قیمت است', 'mr-booking' ); ?></span>
						</div>

						<div class="mrb-price-box__toggle-row">
							<label class="mrb-switch mrb-price-toggle <?php echo $has_price_val ? 'is-on' : ''; ?>">
								<input type="checkbox" name="has_price" value="1" id="mrb-has-price" <?php checked( $has_price_val ); ?> />
								<span class="mrb-switch__ui" aria-hidden="true"></span>
								<span class="mrb-switch__copy">
									<strong><?php esc_html_e( 'این خدمت قیمت دارد', 'mr-booking' ); ?></strong>
									<small><?php esc_html_e( 'اگر خاموش باشد، در فرم رزرو قیمتی نشان داده نمی‌شود.', 'mr-booking' ); ?></small>
								</span>
							</label>
						</div>

						<div class="mrb-price-amount <?php echo $has_price_val ? '' : 'is-hidden'; ?>" id="mrb-price-amount">
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'مبلغ (تومان)', 'mr-booking' ); ?></span>
								<input type="number" name="price" min="0" step="1000" value="<?php echo esc_attr( (string) ( $service?->price ?? 0 ) ); ?>" />
							</label>
						</div>
					</div>

					<div class="mrb-settings__grid">
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'رنگ', 'mr-booking' ); ?></span>
							<?php Helpers::color_input( 'color', (string) ( $service?->color ?? '#0f766e' ), array( 'default' => '#0f766e' ) ); ?>
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'ترتیب نمایش', 'mr-booking' ); ?></span>
							<input type="number" name="sort_order" value="<?php echo esc_attr( (string) ( $service?->sort_order ?? 0 ) ); ?>" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'شناسه تصویر', 'mr-booking' ); ?></span>
							<input type="number" name="image_id" value="<?php echo esc_attr( (string) ( $service?->image_id ?? '' ) ); ?>" />
						</label>
						<label class="mrb-field">
							<span class="mrb-field__label"><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></span>
							<select name="status">
								<option value="active" <?php selected( $service?->status ?? 'active', 'active' ); ?>><?php esc_html_e( 'فعال', 'mr-booking' ); ?></option>
								<option value="inactive" <?php selected( $service?->status ?? '', 'inactive' ); ?>><?php esc_html_e( 'غیرفعال', 'mr-booking' ); ?></option>
							</select>
						</label>
					</div>

					<button class="button button-primary button-hero" style="margin-top:14px"><?php esc_html_e( 'ذخیره خدمت', 'mr-booking' ); ?></button>
				</form>
			</section>
		<?php endif; ?>
	</div>
</div>
