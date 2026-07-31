<?php
/**
 * Admin dashboard template.
 *
 * @package MRBooking
 * @var array $stats
 * @var array $today_bookings
 * @var array $upcoming
 * @var array $recent
 * @var bool  $show_form_help
 */

defined( 'ABSPATH' ) || exit;

$statuses = \MRBooking\Helpers::booking_statuses();
?>
<div class="wrap mrb-admin" dir="rtl">
	<?php if ( ! empty( $_GET['setup'] ) && 'done' === $_GET['setup'] ) : // phpcs:ignore ?>
		<div class="notice notice-success is-dismissible"><p>
			<?php esc_html_e( 'راه‌اندازی اولیه با موفقیت انجام شد. شورت‌کد [mr_booking] را در یک صفحه قرار دهید.', 'mr-booking' ); ?>
		</p></div>
	<?php endif; ?>

	<?php if ( ! empty( $show_form_help ) ) : ?>
	<section class="mrb-panel mrb-shortcode-help">
		<div class="mrb-shortcode-help__head">
			<h2><?php esc_html_e( 'نمایش فرم رزرو در سایت', 'mr-booking' ); ?></h2>
			<a class="mrb-shortcode-help__hide" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mr_booking_hide_form_help' ), 'mr_booking_hide_form_help' ) ); ?>">
				<?php esc_html_e( 'غیرفعال کردن', 'mr-booking' ); ?>
			</a>
		</div>
		<p><?php esc_html_e( 'یکی از روش‌های زیر را انتخاب کنید تا بازدیدکنندگان بتوانند نوبت رزرو کنند:', 'mr-booking' ); ?></p>
		<div class="mrb-shortcode-help__grid">
			<div>
				<strong><?php esc_html_e( '۱) شورت‌کد وردپرس', 'mr-booking' ); ?></strong>
				<p><?php esc_html_e( 'یک برگه جدید بسازید و این کد را داخل محتوا بگذارید:', 'mr-booking' ); ?></p>
				<code class="mrb-shortcode-help__code">[mr_booking]</code>
				<p class="description"><?php esc_html_e( 'اختیاری:', 'mr-booking' ); ?> <code>[mr_booking service="1" staff="2"]</code></p>
			</div>
			<div>
				<strong><?php esc_html_e( '۲) ویجت المنتور', 'mr-booking' ); ?></strong>
				<?php if ( \MRBooking\Premium\License::hide_branding() ) : ?>
					<p><?php esc_html_e( 'صفحه را با Elementor باز کنید → از پنل ویجت‌ها «فرم رزرو» را بکشید داخل صفحه.', 'mr-booking' ); ?></p>
					<p class="description"><?php esc_html_e( 'دسته: رزرو (یا جستجو: رزرو / booking)', 'mr-booking' ); ?></p>
				<?php else : ?>
					<p><?php esc_html_e( 'صفحه را با Elementor باز کنید → از پنل ویجت‌ها «فرم رزرو MR Booking» را بکشید داخل صفحه.', 'mr-booking' ); ?></p>
					<p class="description"><?php esc_html_e( 'دسته: MR Booking (یا جستجو: رزرو / booking)', 'mr-booking' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php esc_html_e( 'MR Booking', 'mr-booking' ); ?></p>
			<h1><?php esc_html_e( 'داشبورد رزرو', 'mr-booking' ); ?></h1>
		</div>
		<div class="mrb-header-actions">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-phone' ) ); ?>">
				<?php esc_html_e( '+ رزرو تلفنی', 'mr-booking' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments' ) ); ?>">
				<?php esc_html_e( 'همه نوبت‌ها', 'mr-booking' ); ?>
			</a>
		</div>
	</header>

	<div class="mrb-stats">
		<div class="mrb-stat"><span><?php echo esc_html( (string) \MRBooking\Bookings\Booking_Repository::count_today() ); ?></span><small><?php esc_html_e( 'امروز', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( (string) ( $stats['total'] ?? 0 ) ); ?></span><small><?php esc_html_e( 'این ماه', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( (string) ( $stats['confirmed'] ?? 0 ) ); ?></span><small><?php esc_html_e( 'تأیید شده', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( (string) ( $stats['pending'] ?? 0 ) ); ?></span><small><?php esc_html_e( 'در انتظار', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( number_format( (float) ( $stats['revenue'] ?? 0 ) ) ); ?></span><small><?php esc_html_e( 'درآمد (تومان)', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( (string) \MRBooking\Customers\Customer_Repository::count() ); ?></span><small><?php esc_html_e( 'مشتریان', 'mr-booking' ); ?></small></div>
	</div>

	<div class="mrb-grid-2">
		<section class="mrb-panel">
			<h2><?php esc_html_e( 'رزروهای امروز', 'mr-booking' ); ?></h2>
			<?php if ( empty( $today_bookings ) ) : ?>
				<p class="mrb-empty"><?php esc_html_e( 'رزروی برای امروز ثبت نشده است.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<table class="widefat mrb-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ساعت', 'mr-booking' ); ?></th>
							<th><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></th>
							<th><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></th>
							<th><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $today_bookings as $b ) : ?>
							<tr>
								<td><?php echo esc_html( substr( $b->start_datetime, 11, 5 ) ); ?></td>
								<td><?php echo esc_html( trim( $b->first_name . ' ' . $b->last_name ) ); ?></td>
								<td><?php echo esc_html( $b->phone ); ?></td>
								<td><span class="mrb-badge mrb-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( $statuses[ $b->status ] ?? $b->status ); ?></span></td>
								<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $b->id ) ); ?>"><?php esc_html_e( 'جزئیات', 'mr-booking' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="mrb-panel">
			<h2><?php esc_html_e( 'نوبت‌های پیش‌رو', 'mr-booking' ); ?></h2>
			<?php if ( empty( $upcoming ) ) : ?>
				<p class="mrb-empty"><?php esc_html_e( 'نوبت پیش‌رویی وجود ندارد.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<ul class="mrb-list">
					<?php foreach ( $upcoming as $b ) : ?>
						<li>
							<strong><?php echo esc_html( \MRBooking\Helpers::format_booking_datetime( (string) $b->start_datetime ) ); ?></strong>
							<span><?php echo esc_html( trim( $b->first_name . ' ' . $b->last_name ) ); ?></span>
							<span class="mrb-badge mrb-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( $statuses[ $b->status ] ?? $b->status ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>

	<section class="mrb-panel mrb-panel--recent">
		<div class="mrb-panel__head">
			<h2><?php esc_html_e( 'آخرین رزروهای ثبت‌شده', 'mr-booking' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments' ) ); ?>"><?php esc_html_e( 'مشاهده همه', 'mr-booking' ); ?></a>
		</div>
		<?php if ( empty( $recent ) ) : ?>
			<p class="mrb-empty"><?php esc_html_e( 'هنوز رزروی ثبت نشده است.', 'mr-booking' ); ?></p>
		<?php else : ?>
			<table class="widefat mrb-table mrb-table--recent">
				<thead>
					<tr>
						<th><?php esc_html_e( 'ثبت', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'نوبت', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'موبایل', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $recent as $b ) : ?>
						<tr data-booking-id="<?php echo esc_attr( (string) $b->id ); ?>">
							<td><?php echo esc_html( substr( (string) ( $b->created_at ?? '' ), 0, 16 ) ); ?></td>
							<td><?php echo esc_html( \MRBooking\Helpers::format_booking_datetime( (string) $b->start_datetime ) ); ?></td>
							<td><?php echo esc_html( trim( $b->first_name . ' ' . $b->last_name ) ); ?></td>
							<td dir="ltr"><?php echo esc_html( $b->phone ); ?></td>
							<td><span class="mrb-badge mrb-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( $statuses[ $b->status ] ?? $b->status ); ?></span></td>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $b->id ) ); ?>"><?php esc_html_e( 'جزئیات', 'mr-booking' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
</div>
