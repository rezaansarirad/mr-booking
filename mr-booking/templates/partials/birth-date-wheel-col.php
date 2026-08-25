<?php
/**
 * One birth-date wheel column with up/down arrows.
 *
 * @package MRBooking
 *
 * @var string $col day|month|year
 */

defined( 'ABSPATH' ) || exit;

$col = isset( $col ) ? (string) $col : 'day';
$labels = array(
	'day'   => array(
		'up'   => __( 'روز بالاتر', 'mr-booking' ),
		'down' => __( 'روز پایین‌تر', 'mr-booking' ),
	),
	'month' => array(
		'up'   => __( 'ماه بالاتر', 'mr-booking' ),
		'down' => __( 'ماه پایین‌تر', 'mr-booking' ),
	),
	'year'  => array(
		'up'   => __( 'سال بالاتر', 'mr-booking' ),
		'down' => __( 'سال پایین‌تر', 'mr-booking' ),
	),
);
$label = $labels[ $col ] ?? $labels['day'];
?>
<div class="mrb-wheel__col" data-col="<?php echo esc_attr( $col ); ?>">
	<button type="button" class="mrb-wheel__step mrb-wheel__step--up" data-wheel-step="-1" aria-label="<?php echo esc_attr( $label['up'] ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7.41 15.41 12 10.83l4.59 4.58L18 14l-6-6-6 6z"/></svg>
	</button>
	<div class="mrb-wheel__scroller" data-wheel="<?php echo esc_attr( $col ); ?>" tabindex="0" role="listbox"></div>
	<button type="button" class="mrb-wheel__step mrb-wheel__step--down" data-wheel-step="1" aria-label="<?php echo esc_attr( $label['down'] ); ?>">
		<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7.41 8.59 12 13.17l4.59-4.58L18 10l-6 6-6-6z"/></svg>
	</button>
</div>
