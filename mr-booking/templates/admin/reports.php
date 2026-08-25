<?php
/**
 * Reports template.
 *
 * @package MRBooking
 */

defined( 'ABSPATH' ) || exit;
$statuses = \MRBooking\Helpers::booking_statuses();
?>
<div class="wrap mrb-admin" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'گزارش‌ها', 'mr-booking' ); ?></h1>
		</div>
	</header>

	<form class="mrb-filters" method="get">
		<input type="hidden" name="page" value="mr-booking-reports" />
		<input type="date" name="from" value="<?php echo esc_attr( $from ); ?>" />
		<input type="date" name="to" value="<?php echo esc_attr( $to ); ?>" />
		<button class="button"><?php esc_html_e( 'اعمال بازه', 'mr-booking' ); ?></button>
	</form>

	<div class="mrb-stats">
		<div class="mrb-stat"><span><?php echo esc_html( (string) $stats['total'] ); ?></span><small><?php esc_html_e( 'کل رزروها', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( (string) $today ); ?></span><small><?php esc_html_e( 'امروز', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( (string) $customers ); ?></span><small><?php esc_html_e( 'مشتریان', 'mr-booking' ); ?></small></div>
		<div class="mrb-stat"><span><?php echo esc_html( number_format( (float) $stats['revenue'] ) ); ?></span><small><?php esc_html_e( 'درآمد', 'mr-booking' ); ?></small></div>
		<?php foreach ( $statuses as $key => $label ) : ?>
			<div class="mrb-stat"><span><?php echo esc_html( (string) ( $stats[ $key ] ?? 0 ) ); ?></span><small><?php echo esc_html( $label ); ?></small></div>
		<?php endforeach; ?>
	</div>

	<section class="mrb-panel">
		<h2><?php esc_html_e( 'محبوب‌ترین خدمات', 'mr-booking' ); ?></h2>
		<table class="widefat mrb-table">
			<thead><tr><th><?php esc_html_e( 'خدمت', 'mr-booking' ); ?></th><th><?php esc_html_e( 'تعداد', 'mr-booking' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $popular ?: array() as $row ) : ?>
					<tr><td><?php echo esc_html( $row->name ); ?></td><td><?php echo esc_html( (string) $row->cnt ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
</div>
