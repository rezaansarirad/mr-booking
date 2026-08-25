<?php
/**
 * Staff recurring blocked time partial.
 *
 * @package MRBooking
 * @var array<int, list<array{start:string,end:string,label:string}>> $blocks_grouped
 * @var array<int, string> $labels
 * @var list<int>          $order
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="mrb-blocks-toolbar">
	<p class="mrb-blocks-toolbar__hint"><?php esc_html_e( 'بازه را در یکی از روزها وارد کنید (مثلاً ناهار ۱۳:۰۰–۱۴:۰۰)، سپس برای کپی روی بقیه روزها دکمه زیر را بزنید.', 'mr-booking' ); ?></p>
	<button type="button" class="button button-secondary mrb-apply-blocks-all">
		<span class="dashicons dashicons-admin-page"></span>
		<?php esc_html_e( 'اعمال روی تمام روزهای هفته', 'mr-booking' ); ?>
	</button>
</div>
<div class="mrb-blocks-week">
	<?php foreach ( $order as $dow ) : ?>
		<?php
		$blocks = $blocks_grouped[ $dow ] ?? array();
		if ( empty( $blocks ) ) {
			$blocks = array( array( 'start' => '', 'end' => '', 'label' => '' ) );
		}
		?>
		<section class="mrb-block-day" data-day="<?php echo esc_attr( (string) $dow ); ?>">
			<header class="mrb-block-day__head">
				<strong><?php echo esc_html( $labels[ $dow ] ); ?></strong>
				<span class="mrb-block-day__hint"><?php esc_html_e( 'ساعات غیرقابل رزرو (مثلاً ناهار)', 'mr-booking' ); ?></span>
				<button type="button" class="button-link mrb-apply-blocks-day" data-day="<?php echo esc_attr( (string) $dow ); ?>">
					<?php esc_html_e( 'اعمال این روز برای همه', 'mr-booking' ); ?>
				</button>
			</header>
			<div class="mrb-block-rows">
				<?php foreach ( $blocks as $block ) : ?>
					<div class="mrb-block-row">
						<label class="mrb-period-field">
							<span><?php esc_html_e( 'از', 'mr-booking' ); ?></span>
							<input type="time" name="blocks[<?php echo esc_attr( (string) $dow ); ?>][start][]" value="<?php echo esc_attr( (string) ( $block['start'] ?? '' ) ); ?>" />
						</label>
						<span class="mrb-period-sep" aria-hidden="true">→</span>
						<label class="mrb-period-field">
							<span><?php esc_html_e( 'تا', 'mr-booking' ); ?></span>
							<input type="time" name="blocks[<?php echo esc_attr( (string) $dow ); ?>][end][]" value="<?php echo esc_attr( (string) ( $block['end'] ?? '' ) ); ?>" />
						</label>
						<label class="mrb-period-field mrb-period-field--label">
							<span><?php esc_html_e( 'عنوان', 'mr-booking' ); ?></span>
							<input type="text" name="blocks[<?php echo esc_attr( (string) $dow ); ?>][label][]" value="<?php echo esc_attr( (string) ( $block['label'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'ناهار', 'mr-booking' ); ?>" />
						</label>
						<button type="button" class="button mrb-remove-block" aria-label="<?php esc_attr_e( 'حذف', 'mr-booking' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
				<?php endforeach; ?>
				<button type="button" class="button button-secondary mrb-add-block" data-day="<?php echo esc_attr( (string) $dow ); ?>">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'بازه مسدود', 'mr-booking' ); ?>
				</button>
			</div>
		</section>
	<?php endforeach; ?>
</div>
