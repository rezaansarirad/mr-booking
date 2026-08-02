<?php
/**
 * Reusable week hours grid partial.
 *
 * @package MRBooking
 * @var array<int, list<object>> $grouped
 * @var array<int, string>       $labels
 * @var list<int>                $order
 * @var string                   $name_prefix
 */

defined( 'ABSPATH' ) || exit;

$name_prefix = $name_prefix ?? 'days';
?>
<div class="mrb-hours-week">
	<?php foreach ( $order as $dow ) : ?>
		<?php
		$rows   = $grouped[ $dow ] ?? array();
		$closed = false;
		if ( ! empty( $rows ) ) {
			$first = $rows[0];
			if ( is_object( $first ) && isset( $first->is_closed ) ) {
				$closed = (int) $first->is_closed === 1;
			}
		}
		$periods = $closed ? array() : $rows;
		if ( empty( $periods ) && ! $closed ) {
			$periods = array( (object) array( 'start_time' => '09:00:00', 'end_time' => '13:00:00' ) );
		}
		?>
		<section class="mrb-day-block <?php echo $closed ? 'is-closed' : 'is-open'; ?>" data-day="<?php echo esc_attr( (string) $dow ); ?>">
			<header class="mrb-day-block__head">
				<div class="mrb-day-block__title">
					<span class="mrb-day-block__badge"><?php echo esc_html( mb_substr( $labels[ $dow ], 0, 1 ) ); ?></span>
					<strong><?php echo esc_html( $labels[ $dow ] ); ?></strong>
				</div>
				<label class="mrb-day-closed-toggle">
					<input
						type="checkbox"
						name="<?php echo esc_attr( $name_prefix ); ?>[<?php echo esc_attr( (string) $dow ); ?>][closed]"
						value="1"
						<?php checked( $closed ); ?>
						class="mrb-day-closed"
					/>
					<span class="mrb-day-closed-toggle__ui" aria-hidden="true"></span>
					<span><?php esc_html_e( 'تعطیل', 'mr-booking' ); ?></span>
				</label>
			</header>

			<div class="mrb-periods">
				<?php foreach ( $periods as $p ) : ?>
					<div class="mrb-period-row">
						<label class="mrb-period-field">
							<span><?php esc_html_e( 'شروع', 'mr-booking' ); ?></span>
							<input type="time" name="<?php echo esc_attr( $name_prefix ); ?>[<?php echo esc_attr( (string) $dow ); ?>][start][]" value="<?php echo esc_attr( substr( (string) $p->start_time, 0, 5 ) ); ?>" />
						</label>
						<span class="mrb-period-sep" aria-hidden="true">→</span>
						<label class="mrb-period-field">
							<span><?php esc_html_e( 'پایان', 'mr-booking' ); ?></span>
							<input type="time" name="<?php echo esc_attr( $name_prefix ); ?>[<?php echo esc_attr( (string) $dow ); ?>][end][]" value="<?php echo esc_attr( substr( (string) $p->end_time, 0, 5 ) ); ?>" />
						</label>
						<button type="button" class="button mrb-remove-period" aria-label="<?php esc_attr_e( 'حذف بازه', 'mr-booking' ); ?>">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
					</div>
				<?php endforeach; ?>

				<button type="button" class="button button-secondary mrb-add-period" data-day="<?php echo esc_attr( (string) $dow ); ?>">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'بازه کاری', 'mr-booking' ); ?>
				</button>
			</div>

			<p class="mrb-day-block__closed-note"><?php esc_html_e( 'این روز تعطیل است و در تقویم رزرو نمایش داده نمی‌شود.', 'mr-booking' ); ?></p>
		</section>
	<?php endforeach; ?>
</div>
