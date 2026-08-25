<?php
/**
 * Calendar admin template.
 *
 * @package MRBooking
 */

defined( 'ABSPATH' ) || exit;

$statuses = \MRBooking\Helpers::booking_statuses();
$prev     = gmdate( 'Y-m-d', strtotime( '-1 month', strtotime( $date ) ) );
$next     = gmdate( 'Y-m-d', strtotime( '+1 month', strtotime( $date ) ) );
?>
<div class="wrap mrb-admin" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'تقویم نوبت‌ها', 'mr-booking' ); ?></h1>
		</div>
		<div class="mrb-header-actions">
			<a class="button <?php echo 'month' === $view ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-calendar&view=month&date=' . $date ) ); ?>"><?php esc_html_e( 'ماه', 'mr-booking' ); ?></a>
			<a class="button <?php echo 'week' === $view ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-calendar&view=week&date=' . $date ) ); ?>"><?php esc_html_e( 'هفته', 'mr-booking' ); ?></a>
			<a class="button <?php echo 'day' === $view ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-calendar&view=day&date=' . $date ) ); ?>"><?php esc_html_e( 'روز', 'mr-booking' ); ?></a>
		</div>
	</header>

	<div class="mrb-cal-nav">
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-calendar&view=' . $view . '&date=' . $prev ) ); ?>">&rarr;</a>
		<strong><?php echo esc_html( $from ); ?> — <?php echo esc_html( $to ); ?></strong>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-calendar&view=' . $view . '&date=' . $next ) ); ?>">&larr;</a>
	</div>

	<section class="mrb-panel">
		<div class="mrb-cal-grid">
			<?php
			$cursor = strtotime( $from );
			$end_ts = strtotime( $to );
			while ( $cursor && $end_ts && $cursor <= $end_ts ) :
				$d    = gmdate( 'Y-m-d', $cursor );
				$items = $by_date[ $d ] ?? array();
				?>
				<div class="mrb-cal-day">
					<header>
						<strong><?php echo esc_html( \MRBooking\Helpers::format_admin_date( $d ) ); ?></strong>
						<small><?php echo esc_html( $d ); ?></small>
					</header>
					<ul>
						<?php foreach ( $items as $b ) : ?>
							<li class="mrb-cal-item mrb-badge--<?php echo esc_attr( $b->status ); ?>">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments&view=' . $b->id ) ); ?>">
									<?php echo esc_html( \MRBooking\Helpers::format_admin_time( (string) $b->start_datetime ) . ' — ' . trim( $b->first_name . ' ' . $b->last_name ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
				<?php
				$cursor = strtotime( '+1 day', $cursor );
			endwhile;
			?>
		</div>
	</section>
</div>
