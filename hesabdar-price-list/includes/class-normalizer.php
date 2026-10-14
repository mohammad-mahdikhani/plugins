<?php
/**
 * Normalize Persian/English digits, prices, SKUs and names.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Normalizer {

	/**
	 * Convert Persian/Arabic digits to Latin digits.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function digits( $value ) {
		$string = self::to_string( $value );
		$fa     = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$ar     = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
		$en     = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$string = str_replace( $fa, $en, $string );
		$string = str_replace( $ar, $en, $string );
		return $string;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function to_string( $value ) {
		if ( is_bool( $value ) || null === $value ) {
			return '';
		}
		if ( is_float( $value ) ) {
			if ( floor( $value ) == $value ) {
				return (string) (int) $value;
			}
			return rtrim( rtrim( sprintf( '%.8F', $value ), '0' ), '.' );
		}
		return trim( (string) $value );
	}

	/**
	 * Normalize SKU / barcode / product id.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sku( $value ) {
		$string = self::digits( $value );
		$string = str_replace( array( "\xE2\x80\x8C", "\xE2\x80\x8F", "\xC2\xA0" ), '', $string );
		$string = preg_replace( '/\s+/u', '', $string );
		return strtoupper( $string );
	}

	/**
	 * Normalize product name for fuzzy matching.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function name( $value ) {
		$string = self::digits( $value );
		$string = str_replace( array( 'ي', 'ك', 'ة', 'ؤ', 'أ', 'إ', 'ٱ' ), array( 'ی', 'ک', 'ه', 'و', 'ا', 'ا', 'ا' ), $string );
		$string = preg_replace( '/[\x{200C}\x{200F}\x{0640}\x{00A0}]/u', ' ', $string );
		$string = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $string );
		$string = preg_replace( '/\s+/u', ' ', $string );
		return trim( mb_strtolower( $string, 'UTF-8' ) );
	}

	/**
	 * Parse a money value into a decimal string WooCommerce understands.
	 *
	 * Empty string means "not provided". False on invalid.
	 *
	 * @param mixed $value Raw value.
	 * @return string|false
	 */
	public static function price( $value ) {
		$string = self::digits( $value );
		if ( '' === $string ) {
			return '';
		}

		$string = str_replace(
			array( 'ریال', 'تومان', 'تومن', 'IRT', 'IRR', 'rial', 'toman', 'Rial', 'Toman' ),
			'',
			$string
		);
		$string = str_replace( array( "\xE2\x80\x8C", "\xC2\xA0" ), '', $string );
		$string = str_replace( array( '٬', '،', '`', ' ' ), '', $string );

		// 1.250.000 or 1,250,000 or 1,250.50
		$has_comma = ( false !== strpos( $string, ',' ) );
		$has_dot   = ( false !== strpos( $string, '.' ) );
		if ( $has_comma && $has_dot ) {
			if ( strrpos( $string, ',' ) > strrpos( $string, '.' ) ) {
				$string = str_replace( '.', '', $string );
				$string = str_replace( ',', '.', $string );
			} else {
				$string = str_replace( ',', '', $string );
			}
		} elseif ( $has_comma ) {
			$parts = explode( ',', $string );
			$last  = end( $parts );
			if ( 1 === count( $parts ) ) {
				$string = $string;
			} elseif ( strlen( $last ) <= 2 && count( $parts ) === 2 ) {
				$string = $parts[0] . '.' . $last;
			} else {
				$string = str_replace( ',', '', $string );
			}
		} elseif ( $has_dot ) {
			$parts = explode( '.', $string );
			$last  = end( $parts );
			if ( count( $parts ) > 2 || ( strlen( $last ) === 3 && count( $parts ) === 2 && strlen( $parts[0] ) <= 3 ) ) {
				$string = str_replace( '.', '', $string );
			}
		}

		$string = preg_replace( '/[^0-9.\-]/', '', $string );
		if ( '' === $string || '-' === $string || '.' === $string ) {
			return false;
		}
		if ( ! is_numeric( $string ) ) {
			return false;
		}

		$number = (float) $string;
		if ( $number < 0 ) {
			return false;
		}
		if ( floor( $number ) == $number ) {
			return (string) (int) $number;
		}
		return rtrim( rtrim( sprintf( '%.8F', $number ), '0' ), '.' );
	}

	/**
	 * @param mixed $value Raw stock.
	 * @return string|false Empty string if not provided.
	 */
	public static function stock( $value ) {
		$string = self::digits( $value );
		if ( '' === $string ) {
			return '';
		}
		$string = str_replace( array( ',', '٬', ' ' ), '', $string );
		if ( ! is_numeric( $string ) ) {
			return false;
		}
		return (string) (int) $string;
	}

	/**
	 * Header key used for column detection.
	 *
	 * @param mixed $value Header cell.
	 * @return string
	 */
	public static function header_key( $value ) {
		$string = self::name( $value );
		$string = str_replace( ' ', '', $string );
		return $string;
	}
}
