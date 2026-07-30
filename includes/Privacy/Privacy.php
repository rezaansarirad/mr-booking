<?php
/**
 * GDPR / privacy integration.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Privacy;

use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Privacy {

	public function hooks(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * @param array<string, mixed> $exporters Exporters.
	 * @return array<string, mixed>
	 */
	public function register_exporter( array $exporters ): array {
		$exporters['mr-booking'] = array(
			'exporter_friendly_name' => __( 'MR Booking', 'mr-booking' ),
			'callback'               => array( $this, 'export' ),
		);
		return $exporters;
	}

	/**
	 * @param array<string, mixed> $erasers Erasers.
	 * @return array<string, mixed>
	 */
	public function register_eraser( array $erasers ): array {
		$erasers['mr-booking'] = array(
			'eraser_friendly_name' => __( 'MR Booking', 'mr-booking' ),
			'callback'             => array( $this, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * @return array{data:list<array<string,mixed>>,done:bool}
	 */
	public function export( string $email ): array {
		global $wpdb;
		$table = Helpers::table( 'customers' );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE email = %s", $email ) ); // phpcs:ignore
		$data  = array();

		foreach ( $rows ?: array() as $c ) {
			$data[] = array(
				'group_id'    => 'mr-booking-customer',
				'group_label' => __( 'مشتریان MR Booking', 'mr-booking' ),
				'item_id'     => 'customer-' . $c->id,
				'data'        => array(
					array( 'name' => __( 'نام', 'mr-booking' ), 'value' => $c->first_name ),
					array( 'name' => __( 'نام خانوادگی', 'mr-booking' ), 'value' => $c->last_name ),
					array( 'name' => __( 'موبایل', 'mr-booking' ), 'value' => $c->phone ),
					array( 'name' => __( 'ایمیل', 'mr-booking' ), 'value' => $c->email ),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	/**
	 * @return array{items_removed:bool,items_retained:bool,messages:list<string>,done:bool}
	 */
	public function erase( string $email ): array {
		global $wpdb;
		$table = Helpers::table( 'customers' );
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$table} WHERE email = %s", $email ) ); // phpcs:ignore
		$removed = false;

		foreach ( $rows ?: array() as $c ) {
			Customer_Repository::delete( (int) $c->id );
			$removed = true;
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
