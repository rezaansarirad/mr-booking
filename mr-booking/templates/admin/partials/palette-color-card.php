<?php
/**
 * Palette color card for settings appearance.
 *
 * @package MRBooking
 *
 * @var string               $key
 * @var array{0:string,1:string} $meta
 * @var string               $color_val
 */

defined( 'ABSPATH' ) || exit;

use MRBooking\Helpers;
?>
<label class="mrb-palette-card">
	<span class="mrb-palette-card__swatch" style="background-color: <?php echo esc_attr( $color_val ); ?>;" aria-hidden="true"></span>
	<span class="mrb-palette-card__body">
		<strong><?php echo esc_html( $meta[0] ); ?></strong>
		<small><?php echo esc_html( $meta[1] ); ?></small>
	</span>
	<?php
	Helpers::color_input(
		'settings[' . $key . ']',
		$color_val,
		array( 'default' => $color_val )
	);
	?>
</label>
