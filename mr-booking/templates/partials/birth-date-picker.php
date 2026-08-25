<?php
/**
 * Birth date field + wheel picker dialog.
 *
 * @package MRBooking
 *
 * @var string $prefix
 * @var string $field_name
 * @var bool   $include_field
 * @var bool   $include_dialog
 * @var bool   $show_required
 * @var string $error_id
 */

defined( 'ABSPATH' ) || exit;

$prefix         = isset( $prefix ) ? (string) $prefix : 'mrb-birth';
$include_field  = ! isset( $include_field ) || $include_field;
$include_dialog = ! isset( $include_dialog ) || $include_dialog;
$field_name     = isset( $field_name ) ? (string) $field_name : '';
$show_required  = ! empty( $show_required );
$error_id       = isset( $error_id ) ? (string) $error_id : '';

if ( $include_field ) :
	?>
	<label class="mrb-field mrb-birth-field" data-field="birth_date">
		<span class="mrb-field__label">
			<?php esc_html_e( 'تاریخ تولد', 'mr-booking' ); ?>
			<?php if ( $show_required ) : ?>
				<abbr class="mrb-req mrb-req--birth" title="<?php esc_attr_e( 'الزامی', 'mr-booking' ); ?>">*</abbr>
			<?php endif; ?>
		</span>
		<button
			type="button"
			class="mrb__birth-trigger"
			id="<?php echo esc_attr( $prefix ); ?>-trigger"
			aria-haspopup="dialog"
			<?php echo $error_id ? 'aria-describedby="' . esc_attr( $error_id ) . '"' : ''; ?>
		>
			<span class="mrb__birth-value is-placeholder" id="<?php echo esc_attr( $prefix ); ?>-display">
				<?php esc_html_e( 'انتخاب تاریخ تولد', 'mr-booking' ); ?>
			</span>
		</button>
		<input
			type="hidden"
			id="<?php echo esc_attr( $prefix ); ?>-date"
			<?php echo $field_name ? 'name="' . esc_attr( $field_name ) . '"' : ''; ?>
			value=""
		/>
		<?php if ( $error_id ) : ?>
			<span class="mrb-field__error" id="<?php echo esc_attr( $error_id ); ?>" hidden></span>
		<?php endif; ?>
	</label>
	<?php
endif;

if ( $include_dialog ) :
	?>
	<div class="mrb-wheel" id="<?php echo esc_attr( $prefix ); ?>-picker" hidden aria-hidden="true">
		<div class="mrb-wheel__backdrop" data-wheel-close></div>
		<div class="mrb-wheel__sheet" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $prefix ); ?>-picker-title">
			<header class="mrb-wheel__header">
				<button type="button" class="mrb-wheel__action" data-wheel-close><?php esc_html_e( 'انصراف', 'mr-booking' ); ?></button>
				<strong id="<?php echo esc_attr( $prefix ); ?>-picker-title"><?php esc_html_e( 'تاریخ تولد', 'mr-booking' ); ?></strong>
				<button type="button" class="mrb-wheel__action mrb-wheel__action--done" data-wheel-done><?php esc_html_e( 'تأیید', 'mr-booking' ); ?></button>
			</header>
			<div class="mrb-wheel__columns">
				<div class="mrb-wheel__highlight" aria-hidden="true"></div>
				<?php
				$col = 'day';
				include MR_BOOKING_PATH . 'templates/partials/birth-date-wheel-col.php';
				$col = 'month';
				include MR_BOOKING_PATH . 'templates/partials/birth-date-wheel-col.php';
				$col = 'year';
				include MR_BOOKING_PATH . 'templates/partials/birth-date-wheel-col.php';
				?>
			</div>
		</div>
	</div>
	<?php
endif;
