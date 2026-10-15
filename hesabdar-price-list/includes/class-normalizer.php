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
		$string = str_replace( array( 'ي', 'ك' ), array( 'ی', 'ک' ), $string );
		$string = str_replace( array( "\xE2\x80\x8C", "\xE2\x80\x8F", "\xC2\xA0" ), '', $string );
		$string = preg_replace( '/\s+/u', '', $string );
		return strtoupper( $string );
	}

	/**
	 * All lookup variants of a code (SKU / barcode / id).
	 *
	 * @param mixed $value Raw code.
	 * @return string[]
	 */
	public static function code_keys( $value ) {
		$sku = self::sku( $value );
		if ( '' === $sku ) {
			return array();
		}

		$keys   = array( $sku );
		$compact = preg_replace( '/[^A-Z0-9]/', '', $sku );
		if ( '' !== $compact ) {
			$keys[] = $compact;
			$nozero = ltrim( $compact, '0' );
			if ( '' !== $nozero ) {
				$keys[] = $nozero;
			}
		}

		foreach ( array( 'SKU-', 'SKU', 'PROD-', 'PROD', 'P-', 'WC-', 'ID-', '#' ) as $prefix ) {
			if ( 0 === strpos( $sku, $prefix ) && strlen( $sku ) > strlen( $prefix ) + 1 ) {
				$rest = substr( $sku, strlen( $prefix ) );
				$keys = array_merge( $keys, self::code_keys( $rest ) );
			}
		}

		$keys = array_values( array_unique( array_filter( $keys ) ) );
		return $keys;
	}

	/**
	 * Name without quantity/unit noise, for looser matching.
	 *
	 * @param mixed $value Raw name.
	 * @return string
	 */
	public static function name_core( $value ) {
		$string = self::name( $value );
		$noise  = self::name_noise_pattern();
		$string = preg_replace( '/\b' . $noise . '\b/u', ' ', $string );
		$string = preg_replace( '/\s+و\s+/u', ' ', $string );
		$string = preg_replace( '/\s+/u', ' ', $string );
		return trim( $string );
	}

	/**
	 * Name with spaces removed, for contains-matching.
	 *
	 * @param mixed $value Raw name.
	 * @return string
	 */
	public static function name_compact( $value ) {
		return str_replace( ' ', '', self::name_core( $value ) );
	}

	/**
	 * Distinctive tokens, including model numbers. Drops filler words.
	 *
	 * @param mixed $value Raw name.
	 * @return string[]
	 */
	public static function name_tokens( $value ) {
		$core   = self::name_core( $value );
		$parts  = preg_split( '/\s+/u', $core );
		$tokens = array();
		foreach ( (array) $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			if ( preg_match( '/^\d+([.\/]\d+)?$/', $part ) ) {
				$tokens[] = $part;
				continue;
			}
			if ( mb_strlen( $part, 'UTF-8' ) >= 2 ) {
				$tokens[] = $part;
			}
		}
		return array_values( array_unique( $tokens ) );
	}

	/**
	 * Filler words that differ between supplier lists and shop titles.
	 *
	 * @return string
	 */
	public static function name_noise_pattern() {
		return '(دستگاه|مدل|مدلهای|خانگی|اصل|اورجینال|اصلی|جدید|کالا|محصول|برند|نوع|لاکچری|جذاب|رنگ|کیلوگرم|کیلو|kg|گرم|لیتر|میلی[\s]?لیتر|ml|ltr|عدد|بسته|کارتن|جفت|رول|متر)';
	}

	/**
	 * Normalize product name for fuzzy matching.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function name( $value ) {
		$string = self::digits( $value );
		$string = str_replace(
			array( 'ي', 'ى', 'ۍ', 'ې', 'ك', 'ڪ', 'ة', 'ھ', 'ؤ', 'أ', 'إ', 'ٱ', 'آ' ),
			array( 'ی', 'ی', 'ی', 'ی', 'ک', 'ک', 'ه', 'ه', 'و', 'ا', 'ا', 'ا', 'ا' ),
			$string
		);
		$string = preg_replace( '/[\x{064B}-\x{065F}\x{0670}\x{200C}\x{200F}\x{0640}\x{00A0}]/u', ' ', $string );
		$string = preg_replace( '/[^\p{L}\p{N}\s]+/u', ' ', $string );
		$string = preg_replace( '/\bپلت\b/u', 'پالت', $string );
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
