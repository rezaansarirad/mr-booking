<?php
/**
 * Customers template.
 *
 * @package MRBooking
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap mrb-admin" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'مشتریان', 'mr-booking' ); ?></h1>
		</div>
		<div class="mrb-header-actions">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-phone' ) ); ?>"><?php esc_html_e( '+ رزرو تلفنی', 'mr-booking' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers&birthday_today=1' ) ); ?>"><?php esc_html_e( 'تولد امروز', 'mr-booking' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers&birthday_week=1' ) ); ?>"><?php esc_html_e( 'تولد این هفته', 'mr-booking' ); ?></a>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mr-booking-customers&mr_export=customers' ), 'mr_export' ) ); ?>"><?php esc_html_e( 'خروجی CSV', 'mr-booking' ); ?></a>
		</div>
	</header>

	<?php if ( $customer ) : ?>
		<?php if ( ! empty( $_GET['saved'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'اطلاعات مشتری ذخیره شد.', 'mr-booking' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['error'] ) && 'phone' === $_GET['error'] ) : // phpcs:ignore ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'شماره موبایل معتبر نیست.', 'mr-booking' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['error'] ) && 'duplicate' === $_GET['error'] ) : // phpcs:ignore ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'این شماره موبایل قبلاً برای مشتری دیگری ثبت شده است.', 'mr-booking' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['wallet_saved'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'موجودی کیف پول به‌روز شد.', 'mr-booking' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['wallet_error'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-error"><p><?php echo 'balance' === $_GET['wallet_error'] ? esc_html__( 'موجودی کیف پول برای این کسر کافی نیست.', 'mr-booking' ) : esc_html__( 'مبلغ باید بزرگ‌تر از صفر باشد.', 'mr-booking' ); // phpcs:ignore ?></p></div>
		<?php endif; ?>

		<section class="mrb-panel">
			<h2><?php echo esc_html( trim( $customer->first_name . ' ' . $customer->last_name ) ); ?></h2>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form mrb-customer-edit">
				<?php wp_nonce_field( 'mr_booking_save_customer' ); ?>
				<input type="hidden" name="action" value="mr_booking_save_customer" />
				<input type="hidden" name="id" value="<?php echo esc_attr( (string) $customer->id ); ?>" />

				<div class="mrb-settings__grid">
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?></span>
						<input type="text" name="first_name" required value="<?php echo esc_attr( (string) $customer->first_name ); ?>" />
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?></span>
						<input type="text" name="last_name" required value="<?php echo esc_attr( (string) $customer->last_name ); ?>" />
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label">
							<?php esc_html_e( 'موبایل', 'mr-booking' ); ?>
							<?php if ( ! empty( $customer->user_id ) ) : ?>
								<span class="mrb-badge mrb-badge--confirmed" title="<?php esc_attr_e( 'این مشتری با کد پیامکی وارد سایت می‌شود', 'mr-booking' ); ?>"><?php esc_html_e( 'حساب کاربری دارد', 'mr-booking' ); ?></span>
							<?php endif; ?>
						</span>
						<input type="text" name="phone" required value="<?php echo esc_attr( (string) $customer->phone ); ?>" />
						<?php if ( ! empty( $customer->user_id ) ) : ?>
							<span class="mrb-field__hint"><?php esc_html_e( 'مشتری نمی‌تواند شماره را خودش عوض کند؛ فقط از اینجا قابل تغییر است و ورود بعدی با شماره جدید انجام می‌شود.', 'mr-booking' ); ?></span>
						<?php endif; ?>
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></span>
						<input type="email" name="email" value="<?php echo esc_attr( (string) $customer->email ); ?>" />
					</label>
					<?php
					$prefix        = 'mrb-customer-birth';
					$input_name    = 'birth_date';
					$show_label    = true;
					$show_required = false;
					$error_id      = '';
					$admin_picker  = true;
					$initial_value = (string) ( $customer->birth_date ?? '' );
					include MR_BOOKING_PATH . 'templates/partials/birth-date-field.php';
					?>
					<label class="mrb-field mrb-field--wide">
						<span class="mrb-field__label"><?php esc_html_e( 'یادداشت', 'mr-booking' ); ?></span>
						<textarea name="notes" rows="3"><?php echo esc_textarea( (string) ( $customer->notes ?? '' ) ); ?></textarea>
					</label>
				</div>

				<div class="mrb-customer-edit__stats">
					<span><?php esc_html_e( 'تعداد رزرو:', 'mr-booking' ); ?> <strong><?php echo esc_html( (string) ( $stats['total'] ?? 0 ) ); ?></strong></span>
					<span><?php esc_html_e( 'عدم حضور:', 'mr-booking' ); ?> <strong><?php echo esc_html( (string) ( $stats['no_show'] ?? 0 ) ); ?></strong></span>
				</div>

				<button type="submit" class="button button-primary"><?php esc_html_e( 'ذخیره تغییرات', 'mr-booking' ); ?></button>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-message-form">
				<?php wp_nonce_field( 'mr_booking_send_message' ); ?>
				<input type="hidden" name="action" value="mr_booking_send_message" />
				<input type="hidden" name="customer_id" value="<?php echo esc_attr( (string) $customer->id ); ?>" />
				<h3><?php esc_html_e( 'ارسال پیام', 'mr-booking' ); ?></h3>
				<select name="channel">
					<option value="sms"><?php esc_html_e( 'پیامک', 'mr-booking' ); ?></option>
					<option value="email"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></option>
				</select>
				<input type="text" name="subject" placeholder="<?php esc_attr_e( 'موضوع ایمیل', 'mr-booking' ); ?>" />
				<textarea name="message" rows="4" required placeholder="<?php esc_attr_e( 'متن پیام — متغیرها: {customer_name} {business_name}', 'mr-booking' ); ?>"></textarea>
				<button class="button button-primary"><?php esc_html_e( 'ارسال', 'mr-booking' ); ?></button>
			</form>

			<h3><?php esc_html_e( 'تاریخچه رزرو', 'mr-booking' ); ?></h3>
			<table class="widefat mrb-table">
				<thead><tr><th><?php esc_html_e( 'کد', 'mr-booking' ); ?></th><th><?php esc_html_e( 'زمان', 'mr-booking' ); ?></th><th><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></th></tr></thead>
				<tbody>
					<?php foreach ( $history as $b ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $b->id ) ); ?>"><?php echo esc_html( $b->booking_code ); ?></a></td>
							<td><?php echo esc_html( \MRBooking\Helpers::format_admin_datetime( (string) $b->start_datetime ) ); ?></td>
							<td><?php echo esc_html( $b->status ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers' ) ); ?>">&larr; <?php esc_html_e( 'بازگشت', 'mr-booking' ); ?></a></p>
		</section>

		<section class="mrb-panel mrb-wallet-panel" id="mrb-wallet">
			<div class="mrb-wallet-panel__head">
				<div>
					<h2><?php esc_html_e( 'کیف پول', 'mr-booking' ); ?></h2>
					<p class="mrb-wallet-panel__balance">
						<span><?php esc_html_e( 'موجودی فعلی', 'mr-booking' ); ?></span>
						<strong><?php echo esc_html( $wallet_balance > 0 ? \MRBooking\Helpers::format_price( (float) $wallet_balance ) : \MRBooking\Helpers::to_persian_digits( '0' ) . ' ' . __( 'تومان', 'mr-booking' ) ); ?></strong>
					</p>
				</div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-wallet-adjust">
					<?php wp_nonce_field( 'mr_booking_wallet_adjust' ); ?>
					<input type="hidden" name="action" value="mr_booking_wallet_adjust" />
					<input type="hidden" name="customer_id" value="<?php echo esc_attr( (string) $customer->id ); ?>" />
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'عملیات', 'mr-booking' ); ?></span>
						<select name="type">
							<option value="credit"><?php esc_html_e( 'افزایش موجودی', 'mr-booking' ); ?></option>
							<option value="debit"><?php esc_html_e( 'کسر موجودی', 'mr-booking' ); ?></option>
						</select>
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'مبلغ (تومان)', 'mr-booking' ); ?></span>
						<input type="text" name="amount" inputmode="numeric" dir="ltr" data-mrb-money placeholder="0" required />
					</label>
					<label class="mrb-field mrb-wallet-adjust__note">
						<span class="mrb-field__label"><?php esc_html_e( 'توضیح', 'mr-booking' ); ?></span>
						<input type="text" name="note" maxlength="190" placeholder="<?php esc_attr_e( 'مثلاً: هدیه، جبران خطا...', 'mr-booking' ); ?>" />
					</label>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'ثبت', 'mr-booking' ); ?></button>
				</form>
			</div>

			<?php if ( empty( $wallet_rows ) ) : ?>
				<p class="mrb-empty"><?php esc_html_e( 'هنوز تراکنشی ثبت نشده است.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<div class="mrb-table-scroll">
					<table class="widefat mrb-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'تاریخ', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'نوع', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'توضیح', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'نوبت', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'مبلغ', 'mr-booking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $wallet_rows as $tx ) : ?>
								<tr>
									<td><?php echo esc_html( \MRBooking\Helpers::format_admin_datetime( (string) $tx->created_at ) ); ?></td>
									<td><?php echo esc_html( \MRBooking\Wallet\Wallet_Repository::type_labels()[ $tx->type ] ?? $tx->type ); ?></td>
									<td><?php echo esc_html( (string) $tx->note ); ?></td>
									<td><?php echo $tx->booking_id ? '<a href="' . esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . (int) $tx->booking_id ) ) . '"><code>' . esc_html( (string) $tx->booking_code ) . '</code></a>' : '—'; ?></td>
									<td class="mrb-wallet-amount <?php echo (float) $tx->amount < 0 ? 'is-negative' : 'is-positive'; ?>">
										<?php echo esc_html( ( (float) $tx->amount < 0 ? '−' : '+' ) . \MRBooking\Helpers::format_price( abs( (float) $tx->amount ) ) ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>

		<?php
		$prefix = 'mrb-customer-birth';
		include MR_BOOKING_PATH . 'templates/partials/birth-date-dialog.php';
		?>
	<?php else : ?>
		<?php if ( ! empty( $_GET['wallet_saved'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'موجودی کیف پول به‌روز شد.', 'mr-booking' ); ?></p></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['wallet_error'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-error"><p><?php echo 'balance' === $_GET['wallet_error'] ? esc_html__( 'موجودی کیف پول برای این کسر کافی نیست.', 'mr-booking' ) : esc_html__( 'مبلغ باید بزرگ‌تر از صفر باشد.', 'mr-booking' ); // phpcs:ignore ?></p></div>
		<?php endif; ?>
		<form class="mrb-filters" method="get">
			<input type="hidden" name="page" value="mr-booking-customers" />
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'جستجوی نام، موبایل، ایمیل...', 'mr-booking' ); ?>" />
			<button class="button"><?php esc_html_e( 'جستجو', 'mr-booking' ); ?></button>
		</form>
		<section class="mrb-panel">
			<table class="widefat mrb-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'نام', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'تولد', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'کیف پول', 'mr-booking' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php $hl_id = isset( $_GET['hl'] ) ? absint( $_GET['hl'] ) : 0; // phpcs:ignore ?>
					<?php foreach ( $customers as $c ) : ?>
						<?php $bal = (float) ( $balances[ (int) $c->id ] ?? 0 ); ?>
						<tr<?php echo $hl_id === (int) $c->id ? ' class="is-just-updated"' : ''; ?>>
							<td><?php echo esc_html( trim( $c->first_name . ' ' . $c->last_name ) ); ?></td>
							<td dir="ltr"><?php echo esc_html( $c->phone ); ?></td>
							<td><?php echo esc_html( (string) $c->email ); ?></td>
							<td><?php echo esc_html( ! empty( $c->birth_date ) ? \MRBooking\Helpers::format_admin_date( (string) $c->birth_date ) : '—' ); ?></td>
							<td>
								<?php echo \MRBooking\Wallet\Wallet_Repository::badge( $bal ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
								<button
									type="button"
									class="button button-small mrb-js-wallet-adjust"
									data-id="<?php echo esc_attr( (string) $c->id ); ?>"
									data-name="<?php echo esc_attr( trim( $c->first_name . ' ' . $c->last_name ) ); ?>"
									data-balance="<?php echo esc_attr( \MRBooking\Helpers::format_price( $bal ) ?: '۰ تومان' ); ?>"
									aria-haspopup="dialog"
									aria-controls="mrb-wallet-dialog"
								><?php esc_html_e( 'تغییر موجودی', 'mr-booking' ); ?></button>
							</td>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers&view=' . $c->id ) ); ?>"><?php esc_html_e( 'ویرایش', 'mr-booking' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<?php if ( ! empty( $customers ) ) : ?>
			<dialog class="mrb-dialog mrb-quick-price-dialog" id="mrb-wallet-dialog" closedby="any" aria-labelledby="mrb-wallet-dialog-title">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mrb-form" id="mrb-wallet-dialog-form">
					<?php wp_nonce_field( 'mr_booking_wallet_adjust' ); ?>
					<input type="hidden" name="action" value="mr_booking_wallet_adjust" />
					<input type="hidden" name="redirect" value="list" />
					<input type="hidden" name="customer_id" id="mrb-wallet-dialog-id" value="" />
					<div class="mrb-dialog__head">
						<div>
							<h2 id="mrb-wallet-dialog-title"><?php esc_html_e( 'تغییر موجودی کیف پول', 'mr-booking' ); ?></h2>
							<p><span id="mrb-wallet-dialog-name"></span> · <?php esc_html_e( 'موجودی فعلی:', 'mr-booking' ); ?> <strong id="mrb-wallet-dialog-balance"></strong></p>
						</div>
						<button type="button" class="mrb-dialog__close" commandfor="mrb-wallet-dialog" command="close" aria-label="<?php esc_attr_e( 'بستن', 'mr-booking' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
					</div>
					<div class="mrb-dialog__body">
						<div class="mrb-settings__grid">
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'عملیات', 'mr-booking' ); ?></span>
								<select name="type" id="mrb-wallet-dialog-type">
									<option value="credit"><?php esc_html_e( 'افزایش موجودی', 'mr-booking' ); ?></option>
									<option value="debit"><?php esc_html_e( 'کسر موجودی', 'mr-booking' ); ?></option>
								</select>
							</label>
							<label class="mrb-field">
								<span class="mrb-field__label"><?php esc_html_e( 'مبلغ (تومان)', 'mr-booking' ); ?></span>
								<span class="mrb-quick-price-input">
									<input type="text" name="amount" id="mrb-wallet-dialog-amount" inputmode="numeric" dir="ltr" data-mrb-money placeholder="0" autocomplete="off" required />
									<em><?php esc_html_e( 'تومان', 'mr-booking' ); ?></em>
								</span>
							</label>
							<label class="mrb-field mrb-field--wide">
								<span class="mrb-field__label"><?php esc_html_e( 'توضیح', 'mr-booking' ); ?></span>
								<input type="text" name="note" maxlength="190" placeholder="<?php esc_attr_e( 'مثلاً: هدیه، جبران خطا...', 'mr-booking' ); ?>" />
							</label>
						</div>
					</div>
					<div class="mrb-dialog__foot">
						<button type="button" class="button" commandfor="mrb-wallet-dialog" command="close"><?php esc_html_e( 'انصراف', 'mr-booking' ); ?></button>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'ثبت', 'mr-booking' ); ?></button>
					</div>
				</form>
			</dialog>
		<?php endif; ?>
	<?php endif; ?>
</div>
