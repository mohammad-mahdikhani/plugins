<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_Jalali {
	public static function gregorian_to_jalali( $gy, $gm, $gd ) {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
		$days %= 12053;
		$jy   += 4 * (int) ( $days / 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			$jy   += (int) ( ( $days - 1 ) / 365 );
			$days  = ( $days - 1 ) % 365;
		}
		$jm = ( $days < 186 ) ? 1 + (int) ( $days / 31 ) : 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days < 186 ) ? ( $days % 31 ) : ( ( $days - 186 ) % 30 ) );
		return array( $jy, $jm, $jd );
	}

	public static function jalali_to_gregorian( $jy, $jm, $jd ) {
		$jy += 1595;
		$days = -355668 + ( 365 * $jy ) + ( (int) ( $jy / 33 ) * 8 ) + (int) ( ( ( $jy % 33 ) + 3 ) / 4 ) + $jd + ( ( $jm < 7 ) ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 ) + 186 );
		$gy   = 400 * (int) ( $days / 146097 );
		$days %= 146097;
		if ( $days > 36524 ) {
			$gy   += 100 * (int) ( --$days / 36524 );
			$days %= 36524;
			if ( $days >= 365 ) {
				$days++;
			}
		}
		$gy   += 4 * (int) ( $days / 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			$gy   += (int) ( ( $days - 1 ) / 365 );
			$days  = ( $days - 1 ) % 365;
		}
		$gd = $days + 1;
		$sal_a = array( 0, 31, ( ( $gy % 4 === 0 && $gy % 100 !== 0 ) || ( $gy % 400 === 0 ) ) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 );
		$gm    = 0;
		for ( $i = 1; $i <= 12 && $gd > $sal_a[ $i ]; $i++ ) {
			$gd -= $sal_a[ $i ];
			$gm  = $i;
		}
		$gm++;
		return array( $gy, $gm, $gd );
	}

	public static function format_jalali( $timestamp ) {
		$dt = getdate( (int) $timestamp );
		list( $jy, $jm, $jd ) = self::gregorian_to_jalali( $dt['year'], $dt['mon'], $dt['mday'] );
		return sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );
	}

	public static function month_matrix( $jy, $jm ) {
		list( $gy, $gm, $gd ) = self::jalali_to_gregorian( $jy, $jm, 1 );
		$ts   = gmmktime( 12, 0, 0, $gm, $gd, $gy );
		$wday = (int) gmdate( 'w', $ts );
		$wday = ( $wday + 1 ) % 7;
		$days = ( $jm <= 6 ) ? 31 : ( ( $jm <= 11 ) ? 30 : 29 );
		$grid = array();
		$week = array_fill( 0, 7, 0 );
		for ( $i = 0; $i < $wday; $i++ ) {
			$week[ $i ] = 0;
		}
		$d = 1;
		for ( $i = $wday; $i < 7; $i++, $d++ ) {
			$week[ $i ] = $d;
		}
		$grid[] = $week;
		while ( $d <= $days ) {
			$week = array_fill( 0, 7, 0 );
			for ( $i = 0; $i < 7 && $d <= $days; $i++, $d++ ) {
				$week[ $i ] = $d;
			}
			$grid[] = $week;
		}
		return $grid;
	}

	public static function month_name( $jm ) {
		$names = array( 1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
		return isset( $names[ $jm ] ) ? $names[ $jm ] : '';
	}
}
