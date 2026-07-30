<?php
/**
 * Jalali / Gregorian calendar converter.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Calendar;

defined( 'ABSPATH' ) || exit;

final class Jalali {

	/**
	 * Convert Gregorian Y-m-d to Jalali array [jy, jm, jd].
	 *
	 * @return array{0:int,1:int,2:int}
	 */
	public static function from_gregorian( int $gy, int $gm, int $gd ): array {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );

		$gy2 = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days = 355666 + ( 365 * $gy ) + (int) floor( ( $gy2 + 3 ) / 4 ) - (int) floor( ( $gy2 + 99 ) / 100 )
			+ (int) floor( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];

		$jy   = -1595 + ( 33 * (int) floor( $days / 12053 ) );
		$days %= 12053;
		$jy  += 4 * (int) floor( $days / 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy   += (int) floor( ( $days - 1 ) / 365 );
			$days  = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + (int) floor( $days / 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + (int) floor( ( $days - 186 ) / 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return array( $jy, $jm, $jd );
	}

	/**
	 * Convert Jalali to Gregorian [gy, gm, gd].
	 *
	 * @return array{0:int,1:int,2:int}
	 */
	public static function to_gregorian( int $jy, int $jm, int $jd ): array {
		$jy += 1595;
		$days = -355668 + ( 365 * $jy ) + ( (int) floor( $jy / 33 ) * 8 ) + (int) floor( ( ( $jy % 33 ) + 3 ) / 4 )
			+ $jd + ( ( $jm < 7 ) ? ( ( $jm - 1 ) * 31 ) : ( ( ( $jm - 7 ) * 30 ) + 186 ) );

		$gy = 400 * (int) floor( $days / 146097 );
		$days %= 146097;

		if ( $days > 36524 ) {
			$gy   += 100 * (int) floor( --$days / 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				++$days;
			}
		}

		$gy   += 4 * (int) floor( $days / 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$gy   += (int) floor( ( $days - 1 ) / 365 );
			$days  = ( $days - 1 ) % 365;
		}

		$gd = $days + 1;
		$sal_a = array(
			0,
			31,
			( ( ( $gy % 4 === 0 ) && ( $gy % 100 !== 0 ) ) || ( $gy % 400 === 0 ) ) ? 29 : 28,
			31,
			30,
			31,
			30,
			31,
			31,
			30,
			31,
			30,
			31,
		);

		$gm = 0;
		for ( $gm = 1; $gm <= 12 && $gd > $sal_a[ $gm ]; $gm++ ) {
			$gd -= $sal_a[ $gm ];
		}

		return array( $gy, $gm, $gd );
	}

	/**
	 * Parse Y-m-d Gregorian to Jalali Y/m/d string.
	 */
	public static function format_from_date( string $ymd, string $sep = '/' ): string {
		$parts = explode( '-', $ymd );
		if ( 3 !== count( $parts ) ) {
			return $ymd;
		}

		[ $jy, $jm, $jd ] = self::from_gregorian( (int) $parts[0], (int) $parts[1], (int) $parts[2] );

		return sprintf( '%04d%s%02d%s%02d', $jy, $sep, $jm, $sep, $jd );
	}

	/**
	 * Jalali month names.
	 *
	 * @return array<int, string>
	 */
	public static function month_names(): array {
		return array(
			1  => 'فروردین',
			2  => 'اردیبهشت',
			3  => 'خرداد',
			4  => 'تیر',
			5  => 'مرداد',
			6  => 'شهریور',
			7  => 'مهر',
			8  => 'آبان',
			9  => 'آذر',
			10 => 'دی',
			11 => 'بهمن',
			12 => 'اسفند',
		);
	}

	/**
	 * English weekday name for PHP date('w').
	 *
	 * @return array<int, string>
	 */
	public static function weekday_en(): array {
		return array(
			0 => 'Sunday',
			1 => 'Monday',
			2 => 'Tuesday',
			3 => 'Wednesday',
			4 => 'Thursday',
			5 => 'Friday',
			6 => 'Saturday',
		);
	}

	/**
	 * Persian weekday for PHP date('w').
	 *
	 * @return array<int, string>
	 */
	public static function weekday_fa(): array {
		return array(
			0 => 'یکشنبه',
			1 => 'دوشنبه',
			2 => 'سه‌شنبه',
			3 => 'چهارشنبه',
			4 => 'پنجشنبه',
			5 => 'جمعه',
			6 => 'شنبه',
		);
	}

	/**
	 * Days in Jalali month.
	 */
	public static function days_in_month( int $jy, int $jm ): int {
		if ( $jm <= 6 ) {
			return 31;
		}
		if ( $jm <= 11 ) {
			return 30;
		}

		return self::is_leap( $jy ) ? 30 : 29;
	}

	/**
	 * Jalali leap year check.
	 */
	public static function is_leap( int $jy ): bool {
		$breaks = array(
			-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210,
			1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178,
		);

		$bl   = count( $breaks );
		$gy   = $jy + 621;
		$leap_j = -14;
		$jp   = $breaks[0];

		for ( $i = 1; $i < $bl; $i++ ) {
			$jm  = $breaks[ $i ];
			$jump = $jm - $jp;
			if ( $jy < $jm ) {
				break;
			}
			$leap_j += (int) floor( $jump / 33 ) * 8 + (int) floor( ( $jump % 33 ) / 4 );
			$jp = $jm;
		}

		$n = $jy - $jp;
		$leap_j += (int) floor( $n / 33 ) * 8 + (int) floor( ( ( $n % 33 ) + 3 ) / 4 );

		if ( ( ( $jump % 33 ) === 4 ) && ( ( $jump - $n ) === 4 ) ) {
			++$leap_j;
		}

		$leap_g = (int) floor( $gy / 4 ) - (int) floor( ( (int) floor( ( $gy / 100 ) + 1 ) * 3 ) / 4 ) - 150;
		$leap   = ( ( ( ( $n + 1 ) % 33 ) - 1 ) % 4 );

		return 0 === (int) $leap;
	}

	/**
	 * Human-readable dual date label.
	 */
	public static function dual_label( string $ymd ): string {
		$ts = strtotime( $ymd . ' 12:00:00' );
		if ( ! $ts ) {
			return $ymd;
		}

		$w      = (int) gmdate( 'w', $ts );
		$parts  = explode( '-', $ymd );
		[ $jy, $jm, $jd ] = self::from_gregorian( (int) $parts[0], (int) $parts[1], (int) $parts[2] );
		$months = self::month_names();
		$fa     = self::weekday_fa()[ $w ] . ' ' . $jd . ' ' . $months[ $jm ] . ' ' . $jy;
		$en     = self::weekday_en()[ $w ] . ', ' . gmdate( 'F j, Y', $ts );

		return $fa . "\n" . $en;
	}

	/**
	 * Convert Jalali Y/m/d or Y-m-d string to Gregorian Y-m-d.
	 */
	public static function jalali_string_to_gregorian( string $input ): string {
		$input = \MRBooking\Helpers::to_english_digits( $input );
		$input = str_replace( array( '/', '.' ), '-', $input );
		$parts = array_map( 'intval', explode( '-', $input ) );

		if ( 3 !== count( $parts ) ) {
			return '';
		}

		[ $gy, $gm, $gd ] = self::to_gregorian( $parts[0], $parts[1], $parts[2] );

		return sprintf( '%04d-%02d-%02d', $gy, $gm, $gd );
	}
}
