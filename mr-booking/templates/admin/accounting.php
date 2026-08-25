<?php
/**
 * Accounting template.
 *
 * @package MRBooking
 * @var array $filters
 * @var array $totals
 * @var list<array> $periods
 * @var list<array> $by_service
 * @var list<array> $by_source
 * @var list<array> $by_staff
 * @var list<object> $ledger
 * @var list<object> $services
 * @var list<object> $staff
 * @var array<string,string> $statuses
 * @var array<string,string> $sources
 * @var float $max_period_revenue
 * @var bool $show_prices
 * @var int $unpriced_count
 * @var string $export_url
 */

use MRBooking\Admin\Pages\Accounting;
use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

$money = static function ( float $amount ): string {
	return $amount > 0 ? Helpers::format_price( $amount ) : Helpers::to_persian_digits( '0' ) . ' ' . __( 'تومان', 'mr-booking' );
};
$num   = static fn( int $n ): string => Helpers::to_persian_digits( number_format( $n ) );

$range_label = $filters['date_from'] === $filters['date_to']
	? Helpers::format_admin_date( $filters['date_from'] )
	: Helpers::format_admin_date( $filters['date_from'] ) . ' — ' . Helpers::format_admin_date( $filters['date_to'] );

$has_extra_filters = $filters['service_id'] || $filters['staff_id'] || $filters['source'] || $filters['status'];
$revenue_note      = '' === $filters['status']
	? __( 'فقط رزروهای «تأیید شده» و «انجام شده»', 'mr-booking' )
	: ( 'all' === $filters['status'] ? __( 'همه وضعیت‌ها', 'mr-booking' ) : ( $statuses[ $filters['status'] ] ?? '' ) );
?>
<div class="wrap mrb-admin mrb-accounting-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'حسابداری', 'mr-booking' ); ?></h1>
			<p class="mrb-phone-book-page__lead">
				<?php esc_html_e( 'درآمد بر اساس مبلغی که هنگام ثبت هر نوبت ذخیره شده محاسبه می‌شود؛ تغییر بعدی قیمت خدمت روی گذشته اثر ندارد.', 'mr-booking' ); ?>
			</p>
		</div>
		<div class="mrb-header-actions">
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-walkin' ) ); ?>">
				<?php esc_html_e( '+ مراجعه حضوری', 'mr-booking' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( $export_url ); ?>">
				<?php esc_html_e( 'خروجی CSV', 'mr-booking' ); ?>
			</a>
		</div>
	</header>

	<?php if ( $unpriced_count > 0 ) : ?>
		<div class="notice notice-info inline mrb-accounting__notice">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: number of services */
						_n( '%s خدمت فعال هنوز مبلغ ندارد و در درآمد صفر محاسبه می‌شود.', '%s خدمت فعال هنوز مبلغ ندارند و در درآمد صفر محاسبه می‌شوند.', $unpriced_count, 'mr-booking' ),
						Helpers::to_persian_digits( (string) $unpriced_count )
					)
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-services' ) ); ?>"><?php esc_html_e( 'تعیین مبلغ خدمات', 'mr-booking' ); ?></a>
				<?php if ( ! $show_prices ) : ?>
					— <?php esc_html_e( 'نمایش قیمت به مشتری خاموش است؛ مبالغ فقط در پنل دیده می‌شوند.', 'mr-booking' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-settings&tab=general' ) ); ?>"><?php esc_html_e( 'تنظیمات', 'mr-booking' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<section class="mrb-panel mrb-accounting__filters">
		<div class="mrb-appt-presets">
			<span class="mrb-appt-presets__label"><?php esc_html_e( 'بازه سریع:', 'mr-booking' ); ?></span>
			<?php foreach ( Accounting::presets() as $key => $label ) : ?>
				<a class="mrb-appt-chip<?php echo $filters['preset'] === $key ? ' is-active' : ''; ?>" href="<?php echo esc_url( Accounting::filter_url( array( 'preset' => $key, 'date_from' => '', 'date_to' => '', 'group' => '' ), $filters ) ); ?>"<?php echo $filters['preset'] === $key ? ' aria-current="page"' : ''; ?>>
					<?php echo esc_html( $label ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<form class="mrb-accounting__form" method="get">
			<input type="hidden" name="page" value="mr-booking-accounting" />
			<div class="mrb-accounting__form-grid">
				<label class="mrb-appt-field">
					<span><?php esc_html_e( 'از تاریخ', 'mr-booking' ); ?></span>
					<input type="date" name="date_from" value="<?php echo esc_attr( (string) $filters['date_from'] ); ?>" />
				</label>
				<label class="mrb-appt-field">
					<span><?php esc_html_e( 'تا تاریخ', 'mr-booking' ); ?></span>
					<input type="date" name="date_to" value="<?php echo esc_attr( (string) $filters['date_to'] ); ?>" />
				</label>
				<label class="mrb-appt-field">
					<span><?php esc_html_e( 'تفکیک', 'mr-booking' ); ?></span>
					<select name="group">
						<?php foreach ( Accounting::group_options() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['group'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="mrb-appt-field">
					<span><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></span>
					<select name="service_id">
						<option value=""><?php esc_html_e( 'همه خدمات', 'mr-booking' ); ?></option>
						<?php foreach ( $services as $svc ) : ?>
							<option value="<?php echo esc_attr( (string) $svc->id ); ?>" <?php selected( (int) $filters['service_id'], (int) $svc->id ); ?>><?php echo esc_html( $svc->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php if ( ! empty( $staff ) ) : ?>
					<label class="mrb-appt-field">
						<span><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></span>
						<select name="staff_id">
							<option value=""><?php esc_html_e( 'همه پرسنل', 'mr-booking' ); ?></option>
							<?php foreach ( $staff as $member ) : ?>
								<option value="<?php echo esc_attr( (string) $member->id ); ?>" <?php selected( (int) $filters['staff_id'], (int) $member->id ); ?>><?php echo esc_html( \MRBooking\Staff\Staff_Repository::display_name( $member ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>
				<label class="mrb-appt-field">
					<span><?php esc_html_e( 'نوع ثبت', 'mr-booking' ); ?></span>
					<select name="source">
						<option value=""><?php esc_html_e( 'همه', 'mr-booking' ); ?></option>
						<?php foreach ( $sources as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['source'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="mrb-appt-field">
					<span><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></span>
					<select name="status">
						<option value=""><?php esc_html_e( 'درآمد قطعی (تأیید/انجام شده)', 'mr-booking' ); ?></option>
						<option value="all" <?php selected( $filters['status'], 'all' ); ?>><?php esc_html_e( 'همه وضعیت‌ها', 'mr-booking' ); ?></option>
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $filters['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</div>
			<div class="mrb-appt-filters__actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'اعمال فیلتر', 'mr-booking' ); ?></button>
				<?php if ( $has_extra_filters || ! $filters['preset'] ) : ?>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-accounting' ) ); ?>"><?php esc_html_e( 'پاک کردن فیلترها', 'mr-booking' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
	</section>

	<p class="mrb-accounting__range">
		<strong><?php echo esc_html( $range_label ); ?></strong>
		<span>· <?php echo esc_html( $revenue_note ); ?></span>
	</p>

	<div class="mrb-stats mrb-accounting__stats">
		<div class="mrb-stat mrb-stat--primary">
			<span><?php echo esc_html( $money( (float) $totals['revenue'] ) ); ?></span>
			<small><?php esc_html_e( 'درآمد کل', 'mr-booking' ); ?></small>
		</div>
		<div class="mrb-stat">
			<span><?php echo esc_html( $num( (int) $totals['count'] ) ); ?></span>
			<small><?php esc_html_e( 'تعداد نوبت', 'mr-booking' ); ?></small>
		</div>
		<div class="mrb-stat">
			<span><?php echo esc_html( $money( (float) $totals['average'] ) ); ?></span>
			<small><?php esc_html_e( 'میانگین هر نوبت', 'mr-booking' ); ?></small>
		</div>
		<?php if ( \MRBooking\Payments\Payment_Service::deposit_enabled() || (float) ( $totals['deposits'] ?? 0 ) > 0 ) : ?>
			<div class="mrb-stat">
				<span><?php echo esc_html( $money( (float) ( $totals['deposits'] ?? 0 ) ) ); ?></span>
				<small><?php esc_html_e( 'پیش‌پرداخت دریافتی', 'mr-booking' ); ?></small>
			</div>
		<?php endif; ?>
		<div class="mrb-stat">
			<span><?php echo esc_html( $money( (float) $totals['walkin_revenue'] ) ); ?></span>
			<small>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: count */
						__( 'مراجعه حضوری (%s مورد)', 'mr-booking' ),
						$num( (int) $totals['walkin_count'] )
					)
				);
				?>
			</small>
		</div>
	</div>

	<?php if ( (int) $totals['count'] < 1 ) : ?>
		<section class="mrb-panel mrb-accounting__empty">
			<h2><?php esc_html_e( 'در این بازه درآمدی ثبت نشده', 'mr-booking' ); ?></h2>
			<p class="mrb-empty"><?php esc_html_e( 'بازه یا فیلترها را تغییر دهید، یا یک مراجعه حضوری ثبت کنید.', 'mr-booking' ); ?></p>
			<div class="mrb-header-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-walkin' ) ); ?>"><?php esc_html_e( 'ثبت مراجعه حضوری', 'mr-booking' ); ?></a>
				<a class="button" href="<?php echo esc_url( Accounting::filter_url( array( 'preset' => 'month', 'date_from' => '', 'date_to' => '', 'group' => '' ), $filters ) ); ?>"><?php esc_html_e( 'مشاهده این ماه', 'mr-booking' ); ?></a>
			</div>
		</section>
	<?php else : ?>

	<div class="mrb-grid-2 mrb-accounting__grid">
		<section class="mrb-panel">
			<h2><?php echo esc_html( 'month' === $filters['group'] ? __( 'درآمد ماهانه', 'mr-booking' ) : __( 'درآمد روزانه', 'mr-booking' ) ); ?></h2>
			<div class="mrb-table-scroll">
				<table class="widefat mrb-table mrb-accounting__table">
					<thead>
						<tr>
							<th><?php echo esc_html( 'month' === $filters['group'] ? __( 'ماه', 'mr-booking' ) : __( 'روز', 'mr-booking' ) ); ?></th>
							<th><?php esc_html_e( 'نوبت', 'mr-booking' ); ?></th>
							<th><?php esc_html_e( 'درآمد', 'mr-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $periods as $p ) : ?>
							<?php $ratio = $max_period_revenue > 0 ? (float) $p['revenue'] / $max_period_revenue : 0; ?>
							<tr>
								<td>
									<?php if ( 'day' === $filters['group'] && $filters['date_from'] !== $filters['date_to'] ) : ?>
										<a href="<?php echo esc_url( Accounting::filter_url( array( 'preset' => '', 'date_from' => $p['key'], 'date_to' => $p['key'], 'group' => 'day' ), $filters ) ); ?>"><?php echo esc_html( $p['label'] ); ?></a>
									<?php else : ?>
										<?php echo esc_html( $p['label'] ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( $num( (int) $p['count'] ) ); ?></td>
								<td class="mrb-accounting__amount">
									<span class="mrb-accounting__bar" style="--mrb-ratio:<?php echo esc_attr( number_format( $ratio, 3, '.', '' ) ); ?>" aria-hidden="true"></span>
									<strong><?php echo esc_html( $money( (float) $p['revenue'] ) ); ?></strong>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th><?php esc_html_e( 'جمع', 'mr-booking' ); ?></th>
							<th><?php echo esc_html( $num( (int) $totals['count'] ) ); ?></th>
							<th><?php echo esc_html( $money( (float) $totals['revenue'] ) ); ?></th>
						</tr>
					</tfoot>
				</table>
			</div>
		</section>

		<section class="mrb-panel">
			<h2><?php esc_html_e( 'درآمد به تفکیک خدمت', 'mr-booking' ); ?></h2>
			<?php if ( empty( $by_service ) ) : ?>
				<p class="mrb-empty"><?php esc_html_e( 'داده‌ای نیست.', 'mr-booking' ); ?></p>
			<?php else : ?>
				<?php
				$max_service = 0.0;
				foreach ( $by_service as $row ) {
					$max_service = max( $max_service, (float) $row['revenue'] );
				}
				?>
				<div class="mrb-table-scroll">
					<table class="widefat mrb-table mrb-accounting__table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'تعداد', 'mr-booking' ); ?></th>
								<th><?php esc_html_e( 'درآمد', 'mr-booking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $by_service as $row ) : ?>
								<?php $ratio = $max_service > 0 ? (float) $row['revenue'] / $max_service : 0; ?>
								<tr>
									<td>
										<?php if ( (int) $filters['service_id'] !== (int) $row['service_id'] ) : ?>
											<a href="<?php echo esc_url( Accounting::filter_url( array( 'service_id' => $row['service_id'] ), $filters ) ); ?>"><?php echo esc_html( $row['name'] ); ?></a>
										<?php else : ?>
											<?php echo esc_html( $row['name'] ); ?>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( $num( (int) $row['count'] ) ); ?></td>
									<td class="mrb-accounting__amount">
										<span class="mrb-accounting__bar" style="--mrb-ratio:<?php echo esc_attr( number_format( $ratio, 3, '.', '' ) ); ?>" aria-hidden="true"></span>
										<strong><?php echo esc_html( $money( (float) $row['revenue'] ) ); ?></strong>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</section>
	</div>

	<div class="mrb-grid-2 mrb-accounting__grid">
		<section class="mrb-panel">
			<h2><?php esc_html_e( 'به تفکیک نوع ثبت', 'mr-booking' ); ?></h2>
			<ul class="mrb-accounting__list">
				<?php foreach ( $by_source as $row ) : ?>
					<li>
						<span class="mrb-badge mrb-badge--<?php echo esc_attr( $row['source'] ); ?>"><?php echo esc_html( $row['label'] ); ?></span>
						<span class="mrb-accounting__list-count"><?php echo esc_html( $num( (int) $row['count'] ) ); ?> <?php esc_html_e( 'نوبت', 'mr-booking' ); ?></span>
						<strong><?php echo esc_html( $money( (float) $row['revenue'] ) ); ?></strong>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="mrb-panel">
			<h2><?php esc_html_e( 'به تفکیک پرسنل', 'mr-booking' ); ?></h2>
			<ul class="mrb-accounting__list">
				<?php foreach ( $by_staff as $row ) : ?>
					<li>
						<span><?php echo esc_html( $row['name'] ); ?></span>
						<span class="mrb-accounting__list-count"><?php echo esc_html( $num( (int) $row['count'] ) ); ?> <?php esc_html_e( 'نوبت', 'mr-booking' ); ?></span>
						<strong><?php echo esc_html( $money( (float) $row['revenue'] ) ); ?></strong>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	</div>

	<section class="mrb-panel">
		<h2>
			<?php esc_html_e( 'ریز تراکنش‌ها', 'mr-booking' ); ?>
			<small class="mrb-accounting__subtle"><?php echo esc_html( sprintf( /* translators: %s: count */ __( '%s مورد', 'mr-booking' ), $num( count( $ledger ) ) ) ); ?></small>
		</h2>
		<div class="mrb-table-scroll">
			<table class="widefat mrb-table mrb-accounting__table mrb-accounting__ledger">
				<thead>
					<tr>
						<th><?php esc_html_e( 'زمان', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'نوع', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></th>
						<th><?php esc_html_e( 'مبلغ', 'mr-booking' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ledger as $b ) : ?>
						<tr>
							<td class="mrb-appt-time">
								<strong><?php echo esc_html( Helpers::format_admin_date( substr( (string) $b->start_datetime, 0, 10 ) ) ); ?></strong>
								<span><?php echo esc_html( Helpers::format_admin_time( (string) $b->start_datetime ) ); ?></span>
							</td>
							<td>
								<strong class="mrb-appt-customer"><?php echo esc_html( trim( $b->first_name . ' ' . $b->last_name ) ); ?></strong>
								<span class="mrb-accounting__subtle" dir="ltr"><?php echo esc_html( $b->phone ); ?></span>
								<?php echo \MRBooking\Wallet\Wallet_Repository::badge( (float) ( $b->wallet_balance ?? 0 ), 'mrb-wallet-chip--inline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
							</td>
							<td><?php echo esc_html( $b->service_names ?: '—' ); ?></td>
							<td><?php echo esc_html( $b->staff_name ?: '—' ); ?></td>
							<td><span class="mrb-badge mrb-badge--<?php echo esc_attr( (string) $b->source ); ?>"><?php echo esc_html( $sources[ $b->source ] ?? $b->source ); ?></span></td>
							<td><span class="mrb-badge mrb-badge--<?php echo esc_attr( $b->status ); ?>"><?php echo esc_html( $statuses[ $b->status ] ?? $b->status ); ?></span></td>
							<td class="mrb-accounting__amount"><strong><?php echo esc_html( $money( (float) $b->total_price ) ); ?></strong></td>
							<td><a class="button button-small" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . (int) $b->id ) ); ?>"><?php esc_html_e( 'جزئیات', 'mr-booking' ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( count( $ledger ) >= 300 ) : ?>
			<p class="mrb-field__hint"><?php esc_html_e( '۳۰۰ مورد آخر نمایش داده شد. برای لیست کامل از خروجی CSV استفاده کنید.', 'mr-booking' ); ?></p>
		<?php endif; ?>
	</section>

	<?php endif; ?>
</div>
