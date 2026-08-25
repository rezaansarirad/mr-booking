<?php
/**
 * WP Dashboard widget template.
 *
 * @package MRBooking
 * @var array $visible
 * @var array $stats
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mrb-dash-widget" dir="rtl">
	<?php if ( empty( $visible ) ) : ?>
		<p class="mrb-dash-widget__empty">
			<?php esc_html_e( 'هیچ موردی برای نمایش انتخاب نشده است.', 'mr-booking' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-settings&tab=dashboard' ) ); ?>">
				<?php esc_html_e( 'تنظیمات ویجت', 'mr-booking' ); ?>
			</a>
		</p>
	<?php else : ?>
		<div class="mrb-dash-widget__grid">
			<?php foreach ( $visible as $key => $row ) : ?>
				<div class="mrb-dash-widget__item mrb-dash-widget__item--<?php echo esc_attr( $key ); ?>">
					<span class="mrb-dash-widget__value">
						<?php
						if ( 'revenue_month' === $key ) {
							echo esc_html( number_format( (float) $row['value'] ) );
						} else {
							echo esc_html( (string) $row['value'] );
						}
						?>
					</span>
					<span class="mrb-dash-widget__label">
						<?php
						echo esc_html( $row['label'] );
						if ( 'revenue_month' === $key ) {
							echo ' <small>(' . esc_html__( 'تومان', 'mr-booking' ) . ')</small>';
						}
						?>
					</span>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="mrb-dash-widget__links">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking' ) ); ?>"><?php esc_html_e( 'داشبورد', 'mr-booking' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments' ) ); ?>"><?php esc_html_e( 'نوبت‌ها', 'mr-booking' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-customers' ) ); ?>"><?php esc_html_e( 'مشتریان', 'mr-booking' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-settings&tab=dashboard' ) ); ?>"><?php esc_html_e( 'تنظیمات ویجت', 'mr-booking' ); ?></a>
	</div>
</div>
