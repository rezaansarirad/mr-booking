<?php
/**
 * Dynamic availability & slot calculation engine.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Bookings;

use MRBooking\Holidays\Holiday_Repository;
use MRBooking\Services\Service_Repository;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;
use MRBooking\WorkingHours\Hours_Repository;
use MRBooking\WorkingHours\Staff_Time_Block_Repository;

defined( 'ABSPATH' ) || exit;

final class Slot_Engine {

	/**
	 * Working periods for a Gregorian Y-m-d.
	 *
	 * @return list<array{start:string,end:string}>
	 */
	public static function periods_for_date( string $ymd, ?int $staff_id = null ): array {
		$settings = Settings::get();
		$special  = Hours_Repository::special_date( $ymd );

		if ( $special ) {
			if ( 'closed' === $special->type ) {
				return array();
			}
			if ( 'special' === $special->type && $special->periods ) {
				$decoded = json_decode( (string) $special->periods, true );
				if ( is_array( $decoded ) ) {
					return self::normalize_periods( $decoded );
				}
			}
		}

		$holiday = Holiday_Repository::find_by_date( $ymd );
		if ( $holiday && (int) $holiday->is_closed && empty( $settings['allow_holiday_booking'] ) ) {
			return array();
		}

		$dow  = (int) ( new \DateTimeImmutable( $ymd . ' 12:00:00', wp_timezone() ) )->format( 'w' );
		$rows = Hours_Repository::for_day( $dow, $staff_id );
		$out  = array();

		foreach ( $rows as $row ) {
			if ( (int) $row->is_closed ) {
				continue;
			}
			$start = substr( (string) $row->start_time, 0, 5 );
			$end   = substr( (string) $row->end_time, 0, 5 );
			if ( '00:00' === $start && '00:00' === $end ) {
				continue;
			}
			$out[] = array(
				'start' => $start,
				'end'   => $end,
			);
		}

		if ( $staff_id && ! empty( $out ) ) {
			$blocks = Staff_Time_Block_Repository::for_day( $staff_id, $dow );
			if ( $blocks ) {
				$out = self::subtract_ranges( $out, $blocks );
			}
		}

		return $out;
	}

	/**
	 * Working periods for availability checks (respects per-staff mode & service staff).
	 *
	 * @param list<int> $service_ids
	 * @return list<array{start:string,end:string}>
	 */
	public static function periods_for_booking( string $ymd, ?int $staff_id = null, array $service_ids = array() ): array {
		if ( $staff_id ) {
			$periods = self::periods_for_date( $ymd, $staff_id );
			if ( ! empty( $periods ) ) {
				return $periods;
			}
		}

		$mode = (string) Settings::get_value( 'hours_mode', 'global' );
		if ( 'per_staff' === $mode && ! empty( $service_ids ) ) {
			$merged = array();
			foreach ( self::staff_candidates( null, $service_ids ) as $sid ) {
				if ( $sid <= 0 ) {
					continue;
				}
				$merged = self::merge_periods( array_merge( $merged, self::periods_for_date( $ymd, $sid ) ) );
			}
			if ( ! empty( $merged ) ) {
				return $merged;
			}
		}

		return self::periods_for_date( $ymd, null );
	}

	/**
	 * Merge overlapping or adjacent time ranges.
	 *
	 * @param list<array{start:string,end:string}> $periods
	 * @return list<array{start:string,end:string}>
	 */
	private static function merge_periods( array $periods ): array {
		if ( empty( $periods ) ) {
			return array();
		}

		usort(
			$periods,
			static function ( array $a, array $b ): int {
				return self::time_to_minutes( $a['start'] ) <=> self::time_to_minutes( $b['start'] );
			}
		);

		$merged  = array();
		$current = $periods[0];

		for ( $i = 1, $count = count( $periods ); $i < $count; $i++ ) {
			$next = $periods[ $i ];
			if ( self::time_to_minutes( $next['start'] ) <= self::time_to_minutes( $current['end'] ) ) {
				$current['end'] = self::minutes_to_time(
					max( self::time_to_minutes( $current['end'] ), self::time_to_minutes( $next['end'] ) )
				);
				continue;
			}
			$merged[] = $current;
			$current  = $next;
		}

		$merged[] = $current;

		return array_values(
			array_filter(
				$merged,
				static function ( array $p ): bool {
					return self::time_to_minutes( $p['start'] ) < self::time_to_minutes( $p['end'] );
				}
			)
		);
	}

	/**
	 * Remove blocked intervals from working periods.
	 *
	 * @param list<array{start:string,end:string}> $periods
	 * @param list<array{start:string,end:string}> $blocks
	 * @return list<array{start:string,end:string}>
	 */
	private static function subtract_ranges( array $periods, array $blocks ): array {
		$result = $periods;

		foreach ( $blocks as $block ) {
			$bs = self::time_to_minutes( $block['start'] );
			$be = self::time_to_minutes( $block['end'] );
			if ( $be <= $bs ) {
				continue;
			}

			$next = array();
			foreach ( $result as $period ) {
				$ps = self::time_to_minutes( $period['start'] );
				$pe = self::time_to_minutes( $period['end'] );

				if ( $be <= $ps || $bs >= $pe ) {
					$next[] = $period;
					continue;
				}

				if ( $ps < $bs ) {
					$next[] = array(
						'start' => self::minutes_to_time( $ps ),
						'end'   => self::minutes_to_time( min( $bs, $pe ) ),
					);
				}
				if ( $pe > $be ) {
					$next[] = array(
						'start' => self::minutes_to_time( max( $be, $ps ) ),
						'end'   => self::minutes_to_time( $pe ),
					);
				}
			}
			$result = $next;
		}

		return array_values(
			array_filter(
				$result,
				static function ( array $p ): bool {
					return self::time_to_minutes( $p['start'] ) < self::time_to_minutes( $p['end'] );
				}
			)
		);
	}

	/**
	 * Break buffer minutes for a staff member (0 = use global setting).
	 */
	public static function break_minutes_for_staff( ?int $staff_id ): int {
		$global = max( 0, (int) Settings::get_value( 'break_between_appointments', 0 ) );
		if ( ! $staff_id ) {
			return $global;
		}
		$staff = Staff_Repository::find( $staff_id );
		if ( ! $staff ) {
			return $global;
		}
		$staff_break = isset( $staff->break_minutes ) ? (int) $staff->break_minutes : 0;
		return $staff_break > 0 ? $staff_break : $global;
	}

	/**
	 * @param list<array<string,mixed>> $periods
	 * @return list<array{start:string,end:string}>
	 */
	private static function normalize_periods( array $periods ): array {
		$out = array();
		foreach ( $periods as $p ) {
			if ( empty( $p['start'] ) || empty( $p['end'] ) ) {
				continue;
			}
			$out[] = array(
				'start' => substr( (string) $p['start'], 0, 5 ),
				'end'   => substr( (string) $p['end'], 0, 5 ),
			);
		}
		return $out;
	}

	/**
	 * Site-local "today" as Y-m-d.
	 */
	public static function today(): string {
		return ( new \DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m-d' );
	}

	/**
	 * Earliest bookable instant (now + min notice), in site timezone.
	 */
	public static function earliest_bookable(): \DateTimeImmutable {
		$min_notice = (int) Settings::get_value( 'min_notice_minutes', 0 );
		$now        = new \DateTimeImmutable( 'now', wp_timezone() );
		if ( $min_notice > 0 ) {
			return $now->modify( '+' . $min_notice . ' minutes' );
		}
		return $now;
	}

	/**
	 * Whether a Gregorian date is strictly before today (site timezone).
	 */
	public static function is_past_date( string $ymd ): bool {
		return $ymd < self::today();
	}

	/**
	 * Whether a date/time slot is in the past (or before min notice).
	 */
	public static function is_past_slot( string $ymd, string $time ): bool {
		try {
			$slot = new \DateTimeImmutable( $ymd . ' ' . $time . ':00', wp_timezone() );
		} catch ( \Exception $e ) {
			return true;
		}
		return $slot < self::earliest_bookable();
	}

	/**
	 * Whether a date is bookable at all (has periods / not closed).
	 */
	public static function is_date_available( string $ymd, int $duration, ?int $staff_id = null, array $service_ids = array() ): bool {
		$slots = self::available_slots( $ymd, $duration, $staff_id, $service_ids );
		return ! empty( $slots );
	}

	/**
	 * Calendar day meta for a month range.
	 *
	 * @param list<int> $service_ids
	 * @return list<array<string,mixed>>
	 */
	public static function month_availability( string $from, string $to, int $duration, ?int $staff_id = null, array $service_ids = array() ): array {
		$settings = Settings::get();
		$holidays = Holiday_Repository::map_for_month( $from, $to );
		$days     = array();
		$tz       = wp_timezone();
		$today    = self::today();
		$max_days = max( 0, (int) $settings['max_days_ahead'] );
		$max_date = ( new \DateTimeImmutable( $today . ' 12:00:00', $tz ) )
			->modify( '+' . $max_days . ' days' )
			->format( 'Y-m-d' );

		try {
			$cursor = new \DateTimeImmutable( $from . ' 12:00:00', $tz );
			$end    = new \DateTimeImmutable( $to . ' 12:00:00', $tz );
		} catch ( \Exception $e ) {
			return array();
		}

		while ( $cursor <= $end ) {
			$ymd     = $cursor->format( 'Y-m-d' );
			$periods = array();
			$meta    = array(
				'date'             => $ymd,
				'available'        => false,
				'fully_booked'     => false,
				'no_future_slots'  => false,
				'closed'           => false,
				'same_day_blocked' => false,
				'closed_reason'    => '',
				'has_periods'      => false,
				'selectable'       => false,
				'holiday'          => false,
				'holiday_title'    => '',
				'special'          => false,
				'past'             => $ymd < $today,
				'beyond'           => $ymd > $max_date,
				'today'            => $ymd === $today,
			);

			if ( isset( $holidays[ $ymd ] ) ) {
				$meta['holiday']       = true;
				$meta['holiday_title'] = $holidays[ $ymd ]->title;
			}

			$special = Hours_Repository::special_date( $ymd );
			if ( $special ) {
				$meta['special'] = true;
				if ( 'closed' === $special->type ) {
					$meta['closed']        = true;
					$meta['closed_reason'] = 'special';
				}
			}

			if ( ! (int) $settings['allow_same_day'] && $ymd === $today ) {
				$meta['same_day_blocked'] = true;
				$meta['closed']           = true;
				$meta['closed_reason']    = 'same_day';
			}

			if ( ! $meta['past'] && ! $meta['beyond'] && ! $meta['closed'] ) {
				$periods = self::periods_for_booking( $ymd, $staff_id, $service_ids );
				$meta['has_periods'] = ! empty( $periods );

				if ( empty( $periods ) ) {
					$meta['closed']        = true;
					$meta['closed_reason'] = $meta['holiday'] ? 'holiday' : 'no_hours';
				} else {
					$slots = self::available_slots( $ymd, $duration, $staff_id, $service_ids );
					if ( ! empty( $slots ) ) {
						$meta['available'] = true;
					} else {
						$future_times = self::future_slot_times( $ymd, $duration, $staff_id, $service_ids );
						if ( empty( $future_times ) ) {
							$meta['no_future_slots'] = true;
						} else {
							$meta['fully_booked'] = true;
						}
					}
				}
			}

			$meta['selectable'] = ! $meta['past']
				&& ! $meta['beyond']
				&& ! $meta['same_day_blocked']
				&& $meta['has_periods'];

			$days[] = $meta;
			$cursor = $cursor->modify( '+1 day' );
		}

		return $days;
	}

	/**
	 * All slot times for a day with status (past / booked / available).
	 *
	 * @param list<int> $service_ids
	 * @return list<array{time:string,status:string}>
	 */
	public static function day_slots_detail( string $ymd, int $duration, ?int $staff_id = null, array $service_ids = array() ): array {
		if ( $duration <= 0 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return array();
		}

		$settings = Settings::get();
		$interval = max( 5, $duration );

		if ( self::is_past_date( $ymd ) ) {
			return array();
		}

		if ( empty( $settings['allow_same_day'] ) && $ymd === self::today() ) {
			return array();
		}

		$periods = self::periods_for_booking( $ymd, $staff_id, $service_ids );
		if ( empty( $periods ) ) {
			return array();
		}

		$earliest   = self::earliest_bookable();
		$candidates = self::staff_candidates( $staff_id, $service_ids );
		$out        = array();

		foreach ( $periods as $period ) {
			$start_m = self::time_to_minutes( $period['start'] );
			$end_m   = self::time_to_minutes( $period['end'] );

			for ( $m = $start_m; $m + $duration <= $end_m; $m += $interval ) {
				$time = self::minutes_to_time( $m );

				try {
					$slot_dt = new \DateTimeImmutable( $ymd . ' ' . $time . ':00', wp_timezone() );
				} catch ( \Exception $e ) {
					continue;
				}

				if ( $slot_dt < $earliest ) {
					$status = 'past';
				} elseif ( ! self::slot_is_free( $ymd, $m, $duration, $candidates ) ) {
					$status = 'booked';
				} else {
					$status = 'available';
				}

				$out[] = array(
					'time'   => $time,
					'status' => $status,
				);
			}
		}

		return $out;
	}

	/**
	 * Generate available HH:MM start slots.
	 *
	 * @param list<int> $service_ids
	 * @return list<string>
	 */
	public static function available_slots( string $ymd, int $duration, ?int $staff_id = null, array $service_ids = array() ): array {
		$earliest = self::earliest_bookable();
		$times    = array();

		foreach ( self::day_slots_detail( $ymd, $duration, $staff_id, $service_ids ) as $slot ) {
			if ( 'available' !== $slot['status'] ) {
				continue;
			}
			try {
				$slot_dt = new \DateTimeImmutable( $ymd . ' ' . $slot['time'] . ':00', wp_timezone() );
			} catch ( \Exception $e ) {
				continue;
			}
			if ( $slot_dt < $earliest ) {
				continue;
			}
			$times[] = $slot['time'];
		}

		return array_values( array_unique( $times ) );
	}

	/**
	 * Slot start times still in the future (ignores existing bookings).
	 *
	 * @return list<string>
	 */
	public static function future_slot_times( string $ymd, int $duration, ?int $staff_id = null, array $service_ids = array() ): array {
		if ( $duration <= 0 || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
			return array();
		}

		$settings = Settings::get();
		$interval = max( 5, $duration );

		if ( self::is_past_date( $ymd ) ) {
			return array();
		}

		if ( empty( $settings['allow_same_day'] ) && $ymd === self::today() ) {
			return array();
		}

		$periods = self::periods_for_booking( $ymd, $staff_id, $service_ids );
		if ( empty( $periods ) ) {
			return array();
		}

		$earliest = self::earliest_bookable();
		$times    = array();

		foreach ( $periods as $period ) {
			$start_m = self::time_to_minutes( $period['start'] );
			$end_m   = self::time_to_minutes( $period['end'] );

			for ( $m = $start_m; $m + $duration <= $end_m; $m += $interval ) {
				$time = self::minutes_to_time( $m );
				try {
					$slot_dt = new \DateTimeImmutable( $ymd . ' ' . $time . ':00', wp_timezone() );
				} catch ( \Exception $e ) {
					continue;
				}
				if ( $slot_dt >= $earliest ) {
					$times[] = $time;
				}
			}
		}

		return array_values( array_unique( $times ) );
	}

	/**
	 * @param list<int> $service_ids
	 * @return list<int>
	 */
	private static function staff_candidates( ?int $staff_id, array $service_ids ): array {
		if ( $staff_id ) {
			return array( $staff_id );
		}

		if ( ! empty( $service_ids ) ) {
			$staff_list = Staff_Repository::for_services( $service_ids );
			$ids        = array();
			foreach ( $staff_list as $s ) {
				$ids[] = (int) $s->id;
			}
			if ( ! empty( $ids ) ) {
				return $ids;
			}
		}

		return array( 0 );
	}

	/**
	 * @param list<int> $candidates_staff
	 */
	private static function slot_is_free( string $ymd, int $start_m, int $duration, array $candidates_staff ): bool {
		$time       = self::minutes_to_time( $start_m );
		$slot_start = $ymd . ' ' . $time . ':00';
		$slot_end   = $ymd . ' ' . self::minutes_to_time( $start_m + $duration ) . ':00';

		foreach ( $candidates_staff as $sid ) {
			$sid_null = $sid > 0 ? $sid : null;
			$padding  = $sid_null ? self::break_minutes_for_staff( $sid_null ) : 0;
			$overlap  = Booking_Repository::overlapping( $slot_start, $slot_end, $sid_null, 0, $padding );
			if ( ! empty( $overlap ) ) {
				continue;
			}
			if ( $sid_null ) {
				$staff_periods = self::periods_for_date( $ymd, $sid_null );
				if ( empty( $staff_periods ) || ! self::fits_in_periods( $start_m, $duration, $staff_periods ) ) {
					continue;
				}
			}
			return true;
		}

		return false;
	}

	/**
	 * @param list<array{start:string,end:string}> $periods
	 */
	private static function fits_in_periods( int $start_m, int $duration, array $periods ): bool {
		$end_m = $start_m + $duration;
		foreach ( $periods as $p ) {
			$ps = self::time_to_minutes( $p['start'] );
			$pe = self::time_to_minutes( $p['end'] );
			if ( $start_m >= $ps && $end_m <= $pe ) {
				return true;
			}
		}
		return false;
	}

	public static function time_to_minutes( string $time ): int {
		$parts = explode( ':', $time );
		return ( (int) $parts[0] * 60 ) + (int) ( $parts[1] ?? 0 );
	}

	public static function minutes_to_time( int $minutes ): string {
		$h = intdiv( $minutes, 60 );
		$m = $minutes % 60;
		return sprintf( '%02d:%02d', $h, $m );
	}

	/**
	 * Total duration for services.
	 *
	 * @param list<int> $service_ids
	 * @return array{duration:int,price:float,services:list<object>}
	 */
	public static function services_totals( array $service_ids ): array {
		$services = Service_Repository::find_many( $service_ids );
		$duration = 0;
		$price    = 0.0;

		foreach ( $services as $s ) {
			$duration += (int) $s->duration;
			if ( Service_Repository::has_price( $s ) ) {
				$price += (float) $s->price;
			}
		}

		return array(
			'duration' => $duration,
			'price'    => $price,
			'services' => $services,
		);
	}

	/**
	 * Pick first available staff for a slot.
	 *
	 * @param list<int> $service_ids
	 */
	public static function assign_staff( string $start, string $end, array $service_ids ): ?int {
		$staff_list = Staff_Repository::for_services( $service_ids );
		foreach ( $staff_list as $s ) {
			$sid     = (int) $s->id;
			$padding = self::break_minutes_for_staff( $sid );
			$overlap = Booking_Repository::overlapping( $start, $end, $sid, 0, $padding );
			if ( empty( $overlap ) ) {
				$ymd = substr( $start, 0, 10 );
				$periods = self::periods_for_date( $ymd, (int) $s->id );
				$start_m = self::time_to_minutes( substr( $start, 11, 5 ) );
				$dur     = (int) ( ( strtotime( $end ) - strtotime( $start ) ) / 60 );
				if ( empty( $periods ) || ! self::fits_in_periods( $start_m, $dur, $periods ) ) {
					continue;
				}
				return (int) $s->id;
			}
		}
		return null;
	}

	/**
	 * Validate a booking request server-side.
	 *
	 * @param array<string, mixed> $payload
	 * @return array{ok:bool,error?:string,data?:array<string,mixed>}
	 */
	public static function validate_booking( array $payload ): array {
		$settings    = Settings::get();
		$service_ids = array_map( 'absint', (array) ( $payload['service_ids'] ?? array() ) );
		$service_ids = array_values( array_filter( $service_ids ) );

		if ( empty( $service_ids ) ) {
			return array( 'ok' => false, 'error' => __( 'لطفاً حداقل یک خدمت انتخاب کنید.', 'mr-booking' ) );
		}

		if ( empty( $settings['enable_multi_service'] ) && count( $service_ids ) > 1 ) {
			return array( 'ok' => false, 'error' => __( 'انتخاب چند خدمت مجاز نیست.', 'mr-booking' ) );
		}

		$totals = self::services_totals( $service_ids );
		if ( empty( $totals['services'] ) || $totals['duration'] <= 0 ) {
			return array( 'ok' => false, 'error' => __( 'خدمت انتخاب‌شده معتبر نیست.', 'mr-booking' ) );
		}

		$date = sanitize_text_field( (string) ( $payload['date'] ?? '' ) );
		$time = sanitize_text_field( (string) ( $payload['time'] ?? '' ) );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			return array( 'ok' => false, 'error' => __( 'تاریخ یا ساعت نامعتبر است.', 'mr-booking' ) );
		}

		if ( self::is_past_date( $date ) ) {
			return array( 'ok' => false, 'error' => __( 'رزرو تاریخ گذشته امکان‌پذیر نیست.', 'mr-booking' ) );
		}

		if ( empty( $settings['allow_same_day'] ) && $date === self::today() ) {
			return array( 'ok' => false, 'error' => __( 'رزرو برای امروز مجاز نیست.', 'mr-booking' ) );
		}

		if ( self::is_past_slot( $date, $time ) ) {
			return array( 'ok' => false, 'error' => __( 'ساعت انتخاب‌شده گذشته است.', 'mr-booking' ) );
		}

		$max_days = max( 0, (int) $settings['max_days_ahead'] );
		$max_date = ( new \DateTimeImmutable( self::today() . ' 12:00:00', wp_timezone() ) )
			->modify( '+' . $max_days . ' days' )
			->format( 'Y-m-d' );
		if ( $date > $max_date ) {
			return array( 'ok' => false, 'error' => __( 'این تاریخ خارج از بازه مجاز رزرو است.', 'mr-booking' ) );
		}

		$staff_id = ! empty( $payload['staff_id'] ) ? absint( $payload['staff_id'] ) : null;
		$slots    = self::available_slots( $date, $totals['duration'], $staff_id, $service_ids );

		if ( ! in_array( $time, $slots, true ) ) {
			return array( 'ok' => false, 'error' => __( 'این زمان دیگر در دسترس نیست.', 'mr-booking' ) );
		}

		$start = $date . ' ' . $time . ':00';
		$end_m = self::time_to_minutes( $time ) + $totals['duration'];
		$end   = $date . ' ' . self::minutes_to_time( $end_m ) . ':00';

		if ( ! $staff_id && ! empty( $settings['auto_assign_staff'] ) ) {
			$staff_id = self::assign_staff( $start, $end, $service_ids );
		}

		if ( ! empty( $settings['require_staff'] ) && ! $staff_id ) {
			return array( 'ok' => false, 'error' => __( 'لطفاً پرسنل را انتخاب کنید.', 'mr-booking' ) );
		}

		if ( $staff_id ) {
			$padding = self::break_minutes_for_staff( $staff_id );
			$overlap = Booking_Repository::overlapping( $start, $end, $staff_id, 0, $padding );
			if ( ! empty( $overlap ) ) {
				return array( 'ok' => false, 'error' => __( 'تداخل زمانی با رزرو دیگر وجود دارد.', 'mr-booking' ) );
			}
		}

		return array(
			'ok'   => true,
			'data' => array(
				'service_ids'    => $service_ids,
				'services'       => $totals['services'],
				'duration'       => $totals['duration'],
				'price'          => $totals['price'],
				'start_datetime' => $start,
				'end_datetime'   => $end,
				'staff_id'       => $staff_id,
			),
		);
	}
}
