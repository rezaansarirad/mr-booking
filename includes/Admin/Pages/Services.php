<?php
/**
 * Services admin page.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Admin\Pages;

use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;

defined( 'ABSPATH' ) || exit;

final class Services {

	public static function render(): void {
		$services = Service_Repository::all();
		$edit_id  = isset( $_GET['edit'] ) ? absint( $_GET['edit'] ) : 0;
		$creating = ! empty( $_GET['new'] ) && 0 === $edit_id; // phpcs:ignore
		$editing  = $edit_id > 0;
		$show_form = $creating || $editing;
		$service  = $edit_id ? Service_Repository::find( $edit_id ) : null;

		include MR_BOOKING_PATH . 'templates/admin/services.php';
	}

	public static function save(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_save_service' );

		$id       = absint( $_POST['id'] ?? 0 );
		$has_price = ! empty( $_POST['has_price'] ) ? 1 : 0;
		$duration  = max( 5, absint( $_POST['duration'] ?? 30 ) );

		$saved_id = Service_Repository::save(
			array(
				'name'        => wp_unslash( $_POST['name'] ?? '' ),
				'description' => wp_unslash( $_POST['description'] ?? '' ),
				'duration'    => $duration,
				'has_price'   => $has_price,
				'price'       => $has_price ? (float) ( $_POST['price'] ?? 0 ) : 0,
				'status'      => sanitize_text_field( wp_unslash( $_POST['status'] ?? 'active' ) ),
				'image_id'    => absint( $_POST['image_id'] ?? 0 ),
				'color'       => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '' ) ),
				'sort_order'  => (int) ( $_POST['sort_order'] ?? 0 ),
			),
			$id
		);

		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-services&edit=' . $saved_id . '&saved=1' ) );
		exit;
	}

	public static function delete(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_delete_service' );
		Service_Repository::delete( absint( $_POST['id'] ?? 0 ) );
		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-services&deleted=1' ) );
		exit;
	}

	public static function quick_toggle(): void {
		if ( ! current_user_can( Helpers::manage_cap() ) ) {
			wp_die( 'Forbidden' );
		}
		check_admin_referer( 'mr_booking_service_toggle' );

		$id   = absint( $_POST['id'] ?? 0 );
		$field = sanitize_key( (string) ( $_POST['field'] ?? '' ) );
		$svc  = $id ? Service_Repository::find( $id ) : null;

		if ( ! $svc || ! in_array( $field, array( 'has_price', 'status' ), true ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-services' ) );
			exit;
		}

		$data = array(
			'name'        => $svc->name,
			'description' => $svc->description,
			'duration'    => $svc->duration,
			'has_price'   => (int) ( $svc->has_price ?? 0 ),
			'price'       => (float) $svc->price,
			'status'      => $svc->status,
			'image_id'    => $svc->image_id,
			'color'       => $svc->color,
			'sort_order'  => $svc->sort_order,
		);

		if ( 'has_price' === $field ) {
			$data['has_price'] = empty( $svc->has_price ) ? 1 : 0;
			if ( empty( $data['has_price'] ) ) {
				$data['price'] = 0;
			}
		}

		if ( 'status' === $field ) {
			$data['status'] = 'active' === $svc->status ? 'inactive' : 'active';
		}

		Service_Repository::save( $data, $id );
		wp_safe_redirect( admin_url( 'admin.php?page=mr-booking-services&updated=1' ) );
		exit;
	}
}
