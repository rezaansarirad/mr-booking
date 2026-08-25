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
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
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
	<?php
	$deposit_updated_id = isset( $_GET['deposit_updated'] ) ? absint( $_GET['deposit_updated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$deposit_updated    = $deposit_updated_id ? \MRBooking\Services\Service_Repository::find( $deposit_updated_id ) : null;
	$deposit_mode       = \MRBooking\Payments\Payment_Service::deposit_enabled();
	if ( $deposit_updated ) :
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					(float) $deposit_updated->deposit > 0
						? sprintf( /* translators: 1: service, 2: amount */ __( 'پیش‌پرداخت «%1$s» به %2$s تغییر کرد.', 'mr-booking' ), $deposit_updated->name, \MRBooking\Helpers::format_price( (float) $deposit_updated->deposit ) )
						: sprintf( /* translators: %s: service */ __( '«%s» اکنون بدون پیش‌پرداخت است.', 'mr-booking' ), $deposit_updated->name )
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	<?php
	$price_updated_id = isset( $_GET['price_updated'] ) ? absint( $_GET['price_updated'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$price_updated_id = $price_updated_id ?: $deposit_updated_id;
	$price_updated    = $price_updated_id ? \MRBooking\Services\Service_Repository::find( $price_updated_id ) : null;
	if ( $price_updated ) :
		?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php
				echo esc_html(
					\MRBooking\Services\Service_Repository::has_price( $price_updated )
						? sprintf(
							/* translators: 1: service name, 2: formatted price */
							__( 'قیمت «%1$s» به %2$s تغییر کرد.', 'mr-booking' ),
							$price_updated->name,
							\MRBooking\Helpers::format_price( (float) $price_updated->price )
						)
						: sprintf(
							/* translators: %s: service name */
							__( '«%s» اکنون بدون قیمت است.', 'mr-booking' ),
							$price_updated->name
						)
				);
				?>
			</p>
		</div>
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
						<article class="mrb-service-card <?php echo $edit_id === (int) $s->id ? 'is-active' : ''; ?> <?php echo $active ? '' : 'is-inactive'; ?> <?php echo $price_updated_id === (int) $s->id ? 'is-just-updated' : ''; ?>" id="mrb-service-card-<?php echo esc_attr( (string) $s->id ); ?>">
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
								<?php if ( $deposit_mode ) : ?>
									<span class="mrb-price-badge mrb-price-badge--deposit" title="<?php esc_attr_e( 'پیش‌پرداختی که مشتری هنگام رزرو می‌پردازد', 'mr-booking' ); ?>">
										<span class="dashicons dashicons-money-alt"></span>
										<?php echo (float) ( $s->deposit ?? 0 ) > 0 ? esc_html( __( 'پیش‌پرداخت', 'mr-booking' ) . ' ' . \MRBooking\Helpers::format_price( (float) $s->deposit ) ) : esc_html__( 'بدون پیش‌پرداخت', 'mr-booking' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<footer class="mrb-service-card__actions">
								<a class="button button-small button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-services&edit=' . $s->id ) ); ?>">
									<?php esc_html_e( 'ویرایش کامل', 'mr-booking' ); ?>
								</a>

								<button
									type="button"
									class="button button-small mrb-js-quick-price"
									data-id="<?php echo esc_attr( (string) $s->id ); ?>"
									data-name="<?php echo esc_attr( (string) $s->name ); ?>"
									data-price="<?php echo esc_attr( $priced ? \MRBooking\Helpers::format_money_input( (float) $s->price ) : '' ); ?>"
									aria-haspopup="dialog"
									aria-controls="mrb-quick-price-dialog"
								>
									<span class="dashicons dashicons-tag" aria-hidden="true"></span>
									<?php esc_html_e( 'ویرایش قیمت', 'mr-booking' ); ?>
								</button>
								<?php if ( $deposit_mode ) : ?>
									<button
										type="button"
										class="button button-small mrb-js-quick-price"
										data-id="<?php echo esc_attr( (string) $s->id ); ?>"
										data-name="<?php echo esc_attr( (string) $s->name ); ?>"
										data-field="deposit"
										data-price="<?php echo esc_attr( \MRBooking\Helpers::format_money_input( (float) ( $s->deposit ?? 0 ) ) ); ?>"
										aria-haspopup="dialog"
										aria-controls="mrb-quick-price-dialog"
									>
										<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
										<?php esc_html_e( 'ویرایش پیش‌پرداخت', 'mr-booking' ); ?>
									</button>
								<?php endif; ?>

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
								<input type="text" name="price" inputmode="numeric" dir="ltr" data-mrb-money placeholder="0" value="<?php echo esc_attr( \MRBooking\Helpers::format_money_input( (float) ( $service?->price ?? 0 ) ) ); ?>" />
								<span class="mrb-field__hint"><?php esc_html_e( 'قیمت اصلی فقط برای حسابداری شماست و به مشتری نشان داده نمی‌شود (مگر نمایش قیمت در تنظیمات فعال و پیش‌پرداخت غیرفعال باشد).', 'mr-booking' ); ?></span>
							</label>
						</div>

						<div class="mrb-deposit-box">
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'پیش‌پرداخت (تومان)', 'mr-booking' ); ?></span>
								<span class="mrb-deposit-box__row">
									<input type="text" name="deposit" id="mrb-service-deposit" inputmode="numeric" dir="ltr" data-mrb-money placeholder="0" value="<?php echo esc_attr( \MRBooking\Helpers::format_money_input( (float) ( $service?->deposit ?? 0 ) ) ); ?>" data-auto="<?php echo ( ! $service || (float) ( $service->deposit ?? 0 ) === \MRBooking\Services\Service_Repository::default_deposit( (float) $service->price ) ) ? '1' : '0'; ?>" />
									<button type="button" class="button" id="mrb-deposit-half"><?php esc_html_e( '۵۰٪ قیمت', 'mr-booking' ); ?></button>
								</span>
								<span class="mrb-field__hint">
									<?php
									echo esc_html(
										\MRBooking\Payments\Payment_Service::deposit_enabled()
											? __( 'مبلغی که مشتری هنگام رزرو می‌بیند و می‌پردازد. صفر یعنی این خدمت پیش‌پرداخت ندارد.', 'mr-booking' )
											: __( 'نمایش پیش‌پرداخت خاموش است؛ برای فعال‌سازی به تنظیمات → پرداخت بروید.', 'mr-booking' )
									);
									?>
								</span>
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

	<?php if ( ! empty( $services ) ) : ?>
		<dialog class="mrb-dialog mrb-quick-price-dialog" id="mrb-quick-price-dialog" closedby="any" aria-labelledby="mrb-quick-price-title">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-quick-price-form" id="mrb-quick-price-form">
				<?php wp_nonce_field( 'mr_booking_quick_price' ); ?>
				<input type="hidden" name="action" value="mr_booking_quick_price" />
				<input type="hidden" name="id" id="mrb-quick-price-id" value="" />
				<input type="hidden" name="field" id="mrb-quick-price-field" value="price" />

				<div class="mrb-dialog__head">
					<div>
						<h2 id="mrb-quick-price-title" data-title-price="<?php esc_attr_e( 'ویرایش قیمت', 'mr-booking' ); ?>" data-title-deposit="<?php esc_attr_e( 'ویرایش پیش‌پرداخت', 'mr-booking' ); ?>"><?php esc_html_e( 'ویرایش قیمت', 'mr-booking' ); ?></h2>
						<p id="mrb-quick-price-service"></p>
					</div>
					<button type="button" class="mrb-dialog__close" commandfor="mrb-quick-price-dialog" command="close" aria-label="<?php esc_attr_e( 'بستن', 'mr-booking' ); ?>">
						<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
					</button>
				</div>

				<div class="mrb-dialog__body">
					<label class="mrb-field mrb-quick-price-field">
						<span class="mrb-field__label"><?php esc_html_e( 'مبلغ (تومان)', 'mr-booking' ); ?></span>
						<span class="mrb-quick-price-input">
							<input type="text" name="price" id="mrb-quick-price-amount" inputmode="numeric" dir="ltr" data-mrb-money placeholder="0" autocomplete="off" enterkeyhint="done" />
							<em><?php esc_html_e( 'تومان', 'mr-booking' ); ?></em>
						</span>
						<span class="mrb-field__hint" id="mrb-quick-price-hint" data-hint-price="<?php esc_attr_e( 'مبلغ صفر یعنی «بدون قیمت». نوبت‌های قبلاً ثبت‌شده با مبلغ زمان خودشان می‌مانند.', 'mr-booking' ); ?>" data-hint-deposit="<?php esc_attr_e( 'مبلغی که مشتری هنگام رزرو می‌پردازد. صفر یعنی بدون پیش‌پرداخت.', 'mr-booking' ); ?>"><?php esc_html_e( 'مبلغ صفر یعنی «بدون قیمت». نوبت‌های قبلاً ثبت‌شده با مبلغ زمان خودشان می‌مانند.', 'mr-booking' ); ?></span>
					</label>
					<p class="mrb-quick-price-preview" id="mrb-quick-price-preview" aria-live="polite"></p>
				</div>

				<div class="mrb-dialog__foot">
					<button type="button" class="button" commandfor="mrb-quick-price-dialog" command="close"><?php esc_html_e( 'انصراف', 'mr-booking' ); ?></button>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'ذخیره قیمت', 'mr-booking' ); ?></button>
				</div>
			</form>
		</dialog>
	<?php endif; ?>
</div>
