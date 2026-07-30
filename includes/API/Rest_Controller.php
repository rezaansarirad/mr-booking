<?php
/**
 * REST API controller.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\API;

use MRBooking\Bookings\Booking_Service;
use MRBooking\Bookings\Slot_Engine;
use MRBooking\Calendar\Jalali;
use MRBooking\Customers\Customer_Repository;
use MRBooking\Helpers;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class Rest_Controller {

	public const NAMESPACE = 'mr-booking/v1';

	public function hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/services',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_services' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/staff',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_staff' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/availability/month',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'month_availability' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/availability/slots',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'day_slots' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/book',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/settings/public',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'public_settings' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/admin/customers/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'search_customers' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/admin/book',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_admin_booking' ),
				'permission_callback' => array( $this, 'admin_permission' ),
			)
		);
	}

	public function admin_permission(): bool {
		return current_user_can( Helpers::manage_cap() );
	}

	public function search_customers( WP_REST_Request $request ): WP_REST_Response {
		$q     = sanitize_text_field( (string) $request->get_param( 'q' ) );
		$items = array();

		if ( strlen( $q ) >= 2 ) {
			foreach ( Customer_Repository::query( array( 'search' => $q, 'limit' => 12, 'order' => 'ASC', 'orderby' => 'first_name' ) ) as $c ) {
				$items[] = array(
					'id'         => (int) $c->id,
					'first_name' => $c->first_name,
					'last_name'  => $c->last_name,
					'phone'      => $c->phone,
					'email'      => (string) $c->email,
					'birth_date' => (string) $c->birth_date,
					'label'      => trim( $c->first_name . ' ' . $c->last_name ) . ' — ' . $c->phone,
				);
			}
		}

		return new WP_REST_Response( array( 'customers' => $items ), 200 );
	}

	public function create_admin_booking( WP_REST_Request $request ): WP_REST_Response {
		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			$payload = $request->get_params();
		}

		$payload['source'] = 'phone';

		$result = Booking_Service::create( $payload );

		if ( empty( $result['ok'] ) ) {
			return new WP_REST_Response( $result, 400 );
		}

		return new WP_REST_Response( $this->format_booking_response( $result, __( 'رزرو تلفنی با موفقیت ثبت شد.', 'mr-booking' ) ), 200 );
	}

	/**
	 * @param array{ok:bool,booking_id?:int,booking?:object} $result
	 * @return array<string, mixed>
	 */
	private function format_booking_response( array $result, ?string $message = null ): array {
		$booking  = $result['booking'];
		$services = array();
		foreach ( \MRBooking\Bookings\Booking_Repository::services( (int) $result['booking_id'] ) as $s ) {
			$services[] = array(
				'id'       => (int) $s->service_id,
				'name'     => $s->name,
				'duration' => (int) $s->duration,
				'price'    => (float) $s->price,
			);
		}

		$show_prices = ! empty( Settings::get_value( 'show_prices' ) );
		$payload     = array(
			'id'         => (int) $booking->id,
			'code'       => $booking->booking_code,
			'start'      => $booking->start_datetime,
			'end'        => $booking->end_datetime,
			'status'     => $booking->status,
			'customer'   => trim( $booking->first_name . ' ' . $booking->last_name ),
			'phone'      => $booking->phone,
			'services'   => $services,
			'date_label' => Jalali::dual_label( substr( $booking->start_datetime, 0, 10 ) ),
			'time'       => substr( $booking->start_datetime, 11, 5 ),
			'duration'   => (int) $booking->total_duration,
		);

		if ( $show_prices ) {
			$payload['price']       = (float) $booking->total_price;
			$payload['price_label'] = Helpers::format_price( (float) $booking->total_price );
		}

		return array(
			'ok'          => true,
			'message'     => $message ?? (string) Settings::get_value( 'text_success' ),
			'show_prices' => $show_prices ? 1 : 0,
			'booking'     => $payload,
		);
	}

	public function get_services( WP_REST_Request $request ): WP_REST_Response {
		$show_prices_global = ! empty( Settings::get_value( 'show_prices' ) );
		$staff_id           = absint( $request->get_param( 'staff_id' ) );
		$items              = array();
		$list               = $staff_id
			? Service_Repository::for_staff( $staff_id )
			: Service_Repository::all( 'active' );

		foreach ( $list as $s ) {
			$has_price = Service_Repository::has_price( $s );
			$item      = array(
				'id'             => (int) $s->id,
				'name'           => $s->name,
				'description'    => $s->description,
				'duration'       => (int) $s->duration,
				'duration_label' => Helpers::format_duration( (int) $s->duration ),
				'has_price'      => $has_price ? 1 : 0,
				'color'          => $s->color,
				'image'          => $s->image_id ? wp_get_attachment_image_url( (int) $s->image_id, 'medium' ) : '',
			);

			if ( $show_prices_global && $has_price ) {
				$item['price']       = (float) $s->price;
				$item['price_label'] = Helpers::format_price( (float) $s->price );
			}

			$items[] = $item;
		}

		return new WP_REST_Response(
			array(
				'services'    => $items,
				'show_prices' => $show_prices_global ? 1 : 0,
			),
			200
		);
	}

	public function get_staff( WP_REST_Request $request ): WP_REST_Response {
		$service_ids = array_filter( array_map( 'absint', (array) $request->get_param( 'service_ids' ) ) );
		$list        = $service_ids ? Staff_Repository::for_services( $service_ids ) : Staff_Repository::all( 'active' );
		$items       = array();

		foreach ( $list as $s ) {
			$items[] = array(
				'id'          => (int) $s->id,
				'name'        => Staff_Repository::display_name( $s ),
				'image'       => $s->image_id ? wp_get_attachment_image_url( (int) $s->image_id, 'thumbnail' ) : '',
				'service_ids' => Staff_Repository::service_ids( (int) $s->id ),
			);
		}

		return new WP_REST_Response( array( 'staff' => $items ), 200 );
	}

	public function month_availability( WP_REST_Request $request ): WP_REST_Response {
		$year  = absint( $request->get_param( 'year' ) );
		$month = absint( $request->get_param( 'month' ) );
		$mode  = Settings::get_value( 'calendar_mode', 'jalali' );
		$service_ids = array_filter( array_map( 'absint', (array) $request->get_param( 'service_ids' ) ) );
		$staff_id    = absint( $request->get_param( 'staff_id' ) ) ?: null;

		$totals = Slot_Engine::services_totals( $service_ids );
		$duration = max( 1, $totals['duration'] );

		if ( 'gregorian' === $mode ) {
			$from = sprintf( '%04d-%02d-01', $year, $month );
			$to   = gmdate( 'Y-m-t', strtotime( $from ) );
		} else {
			$days = Jalali::days_in_month( $year, $month );
			[ $gy1, $gm1, $gd1 ] = Jalali::to_gregorian( $year, $month, 1 );
			[ $gy2, $gm2, $gd2 ] = Jalali::to_gregorian( $year, $month, $days );
			$from = sprintf( '%04d-%02d-%02d', $gy1, $gm1, $gd1 );
			$to   = sprintf( '%04d-%02d-%02d', $gy2, $gm2, $gd2 );
		}

		$days = Slot_Engine::month_availability( $from, $to, $duration, $staff_id, $service_ids );

		// Enrich with display labels.
		foreach ( $days as &$day ) {
			$day['jalali']    = Jalali::format_from_date( $day['date'] );
			$day['dual']      = Jalali::dual_label( $day['date'] );
			$ts               = strtotime( $day['date'] . ' 12:00:00' );
			$day['weekday']   = (int) gmdate( 'w', $ts );
			$day['weekday_fa']= Jalali::weekday_fa()[ $day['weekday'] ];
			$j                = Jalali::from_gregorian( (int) substr( $day['date'], 0, 4 ), (int) substr( $day['date'], 5, 2 ), (int) substr( $day['date'], 8, 2 ) );
			$day['j_day']     = $j[2];
			$day['j_month']   = $j[1];
			$day['j_year']    = $j[0];
			$day['g_day']     = (int) substr( $day['date'], 8, 2 );
		}

		return new WP_REST_Response(
			array(
				'days'     => $days,
				'from'     => $from,
				'to'       => $to,
				'duration' => $duration,
			),
			200
		);
	}

	public function day_slots( WP_REST_Request $request ): WP_REST_Response {
		$date        = sanitize_text_field( (string) $request->get_param( 'date' ) );
		$service_ids = array_filter( array_map( 'absint', (array) $request->get_param( 'service_ids' ) ) );
		$staff_id    = absint( $request->get_param( 'staff_id' ) ) ?: null;

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return new WP_REST_Response(
				array(
					'slots'    => array(),
					'duration' => 0,
					'message'  => __( 'تاریخ نامعتبر است.', 'mr-booking' ),
				),
				400
			);
		}

		if ( Slot_Engine::is_past_date( $date ) ) {
			return new WP_REST_Response(
				array(
					'slots'    => array(),
					'duration' => 0,
					'message'  => __( 'رزرو تاریخ گذشته امکان‌پذیر نیست.', 'mr-booking' ),
				),
				200
			);
		}

		$totals = Slot_Engine::services_totals( $service_ids );
		$duration = max( 1, $totals['duration'] );
		$detail   = Slot_Engine::day_slots_detail( $date, $duration, $staff_id, $service_ids );
		$slots    = Slot_Engine::available_slots( $date, $duration, $staff_id, $service_ids );

		return new WP_REST_Response(
			array(
				'slots'    => $slots,
				'detail'   => $detail,
				'duration' => $totals['duration'],
				'message'  => empty( $slots ) ? Settings::get_value( 'text_no_slots' ) : '',
			),
			200
		);
	}

	public function create_booking( WP_REST_Request $request ): WP_REST_Response {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			// Also accept body nonce.
			$body_nonce = $request->get_param( '_wpnonce' );
			if ( ! $body_nonce || ! wp_verify_nonce( (string) $body_nonce, 'wp_rest' ) ) {
				return new WP_REST_Response( array( 'ok' => false, 'error' => __( 'درخواست نامعتبر است.', 'mr-booking' ) ), 403 );
			}
		}

		$payload = $request->get_json_params();
		if ( ! is_array( $payload ) ) {
			$payload = $request->get_params();
		}

		$result = Booking_Service::create( $payload );

		if ( empty( $result['ok'] ) ) {
			return new WP_REST_Response( $result, 400 );
		}

		return new WP_REST_Response( $this->format_booking_response( $result ), 200 );
	}

	public function public_settings(): WP_REST_Response {
		$s = Settings::get();

		return new WP_REST_Response(
			array(
				'business_name'          => $s['business_name'],
				'calendar_mode'          => $s['calendar_mode'],
				'show_prices'            => (int) $s['show_prices'],
				'enable_multi_service'   => (int) $s['enable_multi_service'],
				'enable_staff_selection' => (int) $s['enable_staff_selection'],
				'require_email'          => (int) $s['require_email'],
				'require_birth_date'     => (int) $s['require_birth_date'],
				'require_staff'          => (int) $s['require_staff'],
				'colors'                 => array(
					'primary'       => $s['color_primary'],
					'secondary'     => $s['color_secondary'],
					'accent'        => $s['color_accent'],
					'button'        => $s['color_button'],
					'button_hover'  => $s['color_button_hover'],
					'text'          => $s['color_text'],
					'label'         => $s['color_label'] ?? $s['color_text'],
					'input_text'    => $s['color_input_text'] ?? $s['color_text'],
					'service_text'  => $s['color_service_text'] ?? $s['color_label'] ?? $s['color_text'],
					'background'    => $s['color_background'],
					'card'          => $s['color_card'],
					'border'        => $s['color_border'],
					'holiday'       => $s['color_holiday'],
					'holiday_bg'    => $s['color_holiday_bg'],
					'available'     => $s['color_available'],
					'unavailable'   => $s['color_unavailable'],
					'fully_booked'  => $s['color_fully_booked'],
					'success'       => $s['color_success'],
					'error'         => $s['color_error'],
					'warning'       => $s['color_warning'],
				),
				'texts'                  => array(
					'title'              => $s['text_title'],
					'step_personal'      => $s['text_step_personal'],
					'step_service'       => $s['text_step_service'],
					'step_date'          => $s['text_step_date'],
					'step_time'          => $s['text_step_time'],
					'step_confirm'       => $s['text_step_confirm'],
					'btn_next'           => $s['text_btn_next'],
					'btn_prev'           => $s['text_btn_prev'],
					'btn_submit'         => $s['text_btn_submit'],
					'success'            => $s['text_success'],
					'fully_booked'       => $s['text_fully_booked'],
					'no_slots'           => $s['text_no_slots'],
					'booking_for_myself' => $s['text_booking_for_myself'],
					'booking_for_child'  => $s['text_booking_for_child'],
					'booking_for_other'  => $s['text_booking_for_other'],
					'closed_day'         => $s['text_closed_day'],
					'holiday'            => $s['text_holiday'],
				),
				'jalali_months'          => Jalali::month_names(),
				'weekdays_fa'            => array_values( Jalali::weekday_fa() ),
			),
			200
		);
	}
}
