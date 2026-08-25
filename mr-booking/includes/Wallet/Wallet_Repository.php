<?php
/**
 * Customer wallet — append-only ledger; balance is the sum of rows.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Wallet;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Wallet_Repository {

	public const TYPE_CREDIT  = 'credit';   // manual top-up by admin
	public const TYPE_DEBIT   = 'debit';    // manual deduction by admin
	public const TYPE_PAYMENT = 'payment';  // deposit paid from wallet
	public const TYPE_REFUND  = 'refund';   // deposit returned after cancellation
	public const TYPE_TOPUP   = 'topup';    // customer paid into the wallet via gateway

	/**
	 * @return array<string, string>
	 */
	public static function type_labels(): array {
		return array(
			self::TYPE_CREDIT  => __( 'افزایش موجودی', 'mr-booking' ),
			self::TYPE_DEBIT   => __( 'کسر موجودی', 'mr-booking' ),
			self::TYPE_PAYMENT => __( 'پرداخت پیش‌پرداخت', 'mr-booking' ),
			self::TYPE_REFUND  => __( 'بازگشت وجه لغو نوبت', 'mr-booking' ),
			self::TYPE_TOPUP   => __( 'افزایش موجودی آنلاین', 'mr-booking' ),
		);
	}

	public static function balance( int $customer_id ): float {
		global $wpdb;
		$t = Helpers::table( 'wallet_transactions' );

		return (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$t} WHERE customer_id = %d", $customer_id ) ); // phpcs:ignore
	}

	/**
	 * Balances for many customers in one query.
	 *
	 * @param list<int> $customer_ids
	 * @return array<int, float>
	 */
	public static function balances( array $customer_ids ): array {
		global $wpdb;
		$ids = array_values( array_unique( array_filter( array_map( 'intval', $customer_ids ) ) ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$t     = Helpers::table( 'wallet_transactions' );
		$place = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT customer_id, COALESCE(SUM(amount),0) AS balance FROM {$t} WHERE customer_id IN ({$place}) GROUP BY customer_id", ...$ids ) ) ?: array(); // phpcs:ignore

		$out = array_fill_keys( $ids, 0.0 );
		foreach ( $rows as $r ) {
			$out[ (int) $r->customer_id ] = (float) $r->balance;
		}

		return $out;
	}

	/**
	 * Escaped inline balance chip for admin lists.
	 */
	public static function badge( float $balance, string $extra_class = '' ): string {
		$label = $balance > 0
			? Helpers::format_price( $balance )
			: Helpers::to_persian_digits( '0' ) . ' ' . __( 'تومان', 'mr-booking' );

		return sprintf(
			'<span class="mrb-wallet-chip%1$s%2$s" title="%3$s"><span class="dashicons dashicons-money-alt" aria-hidden="true"></span>%4$s</span>',
			$balance > 0 ? ' is-positive' : ' is-zero',
			$extra_class ? ' ' . esc_attr( $extra_class ) : '',
			esc_attr__( 'موجودی کیف پول', 'mr-booking' ),
			esc_html( $label )
		);
	}

	/**
	 * @return list<object>
	 */
	public static function transactions( int $customer_id, int $limit = 50 ): array {
		global $wpdb;
		$t = Helpers::table( 'wallet_transactions' );
		$b = Helpers::table( 'bookings' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT w.*, b.booking_code FROM {$t} w LEFT JOIN {$b} b ON b.id = w.booking_id
				WHERE w.customer_id = %d ORDER BY w.id DESC LIMIT %d", // phpcs:ignore
				$customer_id,
				max( 1, $limit )
			)
		) ?: array();
	}

	/**
	 * Append a ledger row. Positive amount credits, negative debits.
	 *
	 * @return int Row ID (0 on failure).
	 */
	public static function add( int $customer_id, float $amount, string $type, string $note = '', ?int $booking_id = null ): int {
		global $wpdb;
		if ( $customer_id <= 0 || 0.0 === round( $amount, 2 ) ) {
			return 0;
		}
		if ( ! isset( self::type_labels()[ $type ] ) ) {
			$type = self::TYPE_CREDIT;
		}

		$ok = $wpdb->insert(
			Helpers::table( 'wallet_transactions' ),
			array(
				'customer_id' => $customer_id,
				'amount'      => round( $amount, 2 ),
				'type'        => $type,
				'booking_id'  => $booking_id ?: null,
				'note'        => sanitize_text_field( $note ),
				'created_by'  => get_current_user_id() ?: null,
				'created_at'  => current_time( 'mysql' ),
			)
		);

		if ( ! $ok ) {
			return 0;
		}

		$id = (int) $wpdb->insert_id;

		/**
		 * Fires after a wallet ledger row is written.
		 *
		 * @param int    $id          Row ID.
		 * @param int    $customer_id Customer.
		 * @param float  $amount      Signed amount.
		 * @param string $type        Row type.
		 */
		do_action( 'mr_booking_wallet_changed', $id, $customer_id, $amount, $type );

		return $id;
	}

	/**
	 * Debit for a booking payment if the balance covers it.
	 */
	public static function pay( int $customer_id, float $amount, int $booking_id ): bool {
		if ( $amount <= 0 ) {
			return true;
		}
		if ( self::balance( $customer_id ) + 0.0001 < $amount ) {
			return false;
		}

		return self::add( $customer_id, -1 * $amount, self::TYPE_PAYMENT, '', $booking_id ) > 0;
	}

	/**
	 * Whether a booking has already been refunded (idempotency guard).
	 */
	public static function has_refund( int $booking_id ): bool {
		global $wpdb;
		$t = Helpers::table( 'wallet_transactions' );

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE booking_id = %d AND type = %s", $booking_id, self::TYPE_REFUND ) ) > 0; // phpcs:ignore
	}

	/**
	 * Public shape of a ledger row for the account page.
	 *
	 * @return array<string, mixed>
	 */
	public static function format_public( object $row ): array {
		return array(
			'id'         => (int) $row->id,
			'amount'     => (float) $row->amount,
			'label'      => Helpers::format_price( abs( (float) $row->amount ) ),
			'type'       => (string) $row->type,
			'type_label' => self::type_labels()[ $row->type ] ?? (string) $row->type,
			'note'       => (string) $row->note,
			'booking'    => (string) ( $row->booking_code ?? '' ),
			'date'       => Helpers::format_admin_datetime( (string) $row->created_at ),
		);
	}
}
