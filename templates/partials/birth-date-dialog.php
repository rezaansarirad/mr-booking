<?php
/**
 * Birth date wheel dialog.
 *
 * @package MRBooking
 *
 * @var string $prefix
 */

defined( 'ABSPATH' ) || exit;

$prefix   = $prefix ?? 'mrb-birth';
$picker_id = $prefix . '-picker';
$title_id  = $prefix . '-picker-title';
?>
<div class="mrb-wheel" id="<?php echo esc_attr( $picker_id ); ?>" hidden aria-hidden="true">
	<div class="mrb-wheel__backdrop" data-wheel-close></div>
	<div class="mrb-wheel__sheet" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
		<header class="mrb-wheel__header">
			<button type="button" class="mrb-wheel__action" data-wheel-close><?php esc_html_e( 'انصراف', 'mr-booking' ); ?></button>
			<strong id="<?php echo esc_attr( $title_id ); ?>"><?php esc_html_e( 'تاریخ تولد', 'mr-booking' ); ?></strong>
			<button type="button" class="mrb-wheel__action mrb-wheel__action--done" data-wheel-done><?php esc_html_e( 'تأیید', 'mr-booking' ); ?></button>
		</header>
		<div class="mrb-wheel__columns">
			<div class="mrb-wheel__highlight" aria-hidden="true"></div>
			<div class="mrb-wheel__col" data-col="day">
				<div class="mrb-wheel__scroller" data-wheel="day"></div>
			</div>
			<div class="mrb-wheel__col" data-col="month">
				<div class="mrb-wheel__scroller" data-wheel="month"></div>
			</div>
			<div class="mrb-wheel__col" data-col="year">
				<div class="mrb-wheel__scroller" data-wheel="year"></div>
			</div>
		</div>
	</div>
</div>
