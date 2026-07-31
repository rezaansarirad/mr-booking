<?php
/**
 * Birth date trigger field (wheel picker opens separately).
 *
 * @package MRBooking
 *
 * @var string $prefix
 * @var string $input_name
 * @var bool   $show_label
 * @var bool   $show_required
 * @var string $error_id
 * @var string $placeholder
 */

defined( 'ABSPATH' ) || exit;

$prefix         = $prefix ?? 'mrb-birth';
$input_name     = $input_name ?? 'birth_date';
$show_label     = $show_label ?? true;
$show_required  = $show_required ?? false;
$error_id       = $error_id ?? '';
$placeholder    = $placeholder ?? __( 'انتخاب تاریخ تولد', 'mr-booking' );
$trigger_id     = $prefix . '-trigger';
$display_id     = $prefix . '-display';
$hidden_id      = $prefix . '-date';
$describedby    = $error_id ? ' aria-describedby="' . esc_attr( $error_id ) . '"' : '';
?>
<label class="mrb-field mrb-birth-field" data-field="birth_date">
	<?php if ( $show_label ) : ?>
		<span class="mrb-field__label">
			<?php esc_html_e( 'تاریخ تولد', 'mr-booking' ); ?>
			<abbr class="mrb-req mrb-req--birth" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>"<?php echo $show_required ? '' : ' hidden'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>*</abbr>
		</span>
	<?php endif; ?>
	<button type="button" class="mrb__birth-trigger" id="<?php echo esc_attr( $trigger_id ); ?>" aria-haspopup="dialog"<?php echo $describedby; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<span class="mrb__birth-value is-placeholder" id="<?php echo esc_attr( $display_id ); ?>"><?php echo esc_html( $placeholder ); ?></span>
	</button>
	<input type="hidden"<?php echo $input_name ? ' name="' . esc_attr( $input_name ) . '"' : ''; ?> id="<?php echo esc_attr( $hidden_id ); ?>" value="" />
	<?php if ( $error_id ) : ?>
		<span class="mrb-field__error" id="<?php echo esc_attr( $error_id ); ?>" hidden></span>
	<?php endif; ?>
</label>
