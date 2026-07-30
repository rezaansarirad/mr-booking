<?php
/**
 * Color picker with HEX input.
 *
 * @package MRBooking
 *
 * @var string $name
 * @var string $value
 * @var array  $args
 */

defined( 'ABSPATH' ) || exit;

$input_id = ! empty( $args['id'] ) ? (string) $args['id'] : 'mrb-color-' . wp_unique_id();
$class    = ! empty( $args['class'] ) ? (string) $args['class'] : '';
$hex_label = ! empty( $args['hex_label'] ) ? (string) $args['hex_label'] : __( 'کد HEX', 'mr-booking' );
?>
<div class="mrb-color-control <?php echo esc_attr( $class ); ?>" data-mrb-color-control>
	<input
		type="color"
		id="<?php echo esc_attr( $input_id ); ?>"
		class="mrb-color-control__picker"
		name="<?php echo esc_attr( $name ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
	/>
	<input
		type="text"
		class="mrb-color-control__hex"
		value="<?php echo esc_attr( $value ); ?>"
		maxlength="7"
		spellcheck="false"
		autocapitalize="off"
		autocomplete="off"
		placeholder="#000000"
		aria-label="<?php echo esc_attr( $hex_label ); ?>"
		dir="ltr"
		inputmode="text"
	/>
</div>
