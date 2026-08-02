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
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
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
						<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></span>
						<input type="text" name="phone" required value="<?php echo esc_attr( (string) $customer->phone ); ?>" />
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?></span>
						<input type="email" name="email" value="<?php echo esc_attr( (string) $customer->email ); ?>" />
					</label>
					<label class="mrb-field">
						<span class="mrb-field__label"><?php esc_html_e( 'تاریخ تولد', 'mr-booking' ); ?></span>
						<input type="date" name="birth_date" value="<?php echo esc_attr( (string) $customer->birth_date ); ?>" />
					</label>
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
	<?php else : ?>
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
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $customers as $c ) : ?>
						<tr>
							<td><?php echo esc_html( trim( $c->first_name . ' ' . $c->last_name ) ); ?></td>
							<td><?php echo esc_html( $c->phone ); ?></td>
							<td><?php echo esc_html( (string) $c->email ); ?></td>
							<td><?php echo esc_html( ! empty( $c->birth_date ) ? \MRBooking\Helpers::format_admin_date( (string) $c->birth_date ) : '—' ); ?></td>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers&view=' . $c->id ) ); ?>"><?php esc_html_e( 'ویرایش', 'mr-booking' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>
</div>
