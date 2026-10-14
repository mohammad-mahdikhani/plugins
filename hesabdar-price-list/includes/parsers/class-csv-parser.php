<?php
/**
 * CSV / TSV price list parser.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Csv_Parser {

	/**
	 * @param string $path Path.
	 * @return HPL_Parsed_File
	 */
	public static function parse( $path ) {
		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			throw new RuntimeException( 'خواندن فایل CSV ممکن نشد.' );
		}

		$raw = self::decode( $raw );
		$raw = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );

		$delimiter = self::detect_delimiter( $raw );
		$lines     = preg_split( "/\r\n|\n|\r/", $raw );
		$matrix    = array();

		foreach ( $lines as $line ) {
			if ( '' === $line ) {
				continue;
			}
			$matrix[] = str_getcsv( $line, $delimiter );
		}

		$parsed         = new HPL_Parsed_File();
		$parsed->sheets = array( HPL_Parser::sheet_from_matrix( $matrix, 'CSV' ) );
		return $parsed;
	}

	/**
	 * @param string $raw Raw bytes.
	 * @return string UTF-8
	 */
	public static function decode( $raw ) {
		if ( preg_match( '/^[\x00-\x7F]*$/', $raw ) ) {
			return $raw;
		}
		if ( function_exists( 'mb_check_encoding' ) && mb_check_encoding( $raw, 'UTF-8' ) ) {
			return $raw;
		}
		if ( function_exists( 'mb_detect_encoding' ) ) {
			$candidates = array( 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1' );
			$available  = function_exists( 'mb_list_encodings' ) ? mb_list_encodings() : $candidates;
			$encodings  = array_values( array_intersect( $candidates, $available ) );
			if ( $encodings ) {
				$enc = mb_detect_encoding( $raw, $encodings, true );
				if ( $enc && 'UTF-8' !== $enc ) {
					$converted = self::to_utf8( $raw, $enc );
					if ( '' !== $converted ) {
						return $converted;
					}
				}
			}
		}
		foreach ( array( 'WINDOWS-1256', 'CP1256', 'ISO-8859-6' ) as $enc ) {
			$converted = self::to_utf8( $raw, $enc );
			if ( '' !== $converted ) {
				return $converted;
			}
		}
		return $raw;
	}

	/**
	 * @param string $raw Raw.
	 * @param string $enc Encoding.
	 * @return string
	 */
	private static function to_utf8( $raw, $enc ) {
		if ( function_exists( 'iconv' ) ) {
			$converted = @iconv( $enc, 'UTF-8//IGNORE', $raw ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $converted && '' !== $converted ) {
				return $converted;
			}
		}
		if ( function_exists( 'mb_convert_encoding' ) ) {
			$converted = @mb_convert_encoding( $raw, 'UTF-8', $enc ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( is_string( $converted ) && '' !== $converted ) {
				return $converted;
			}
		}
		return '';
	}

	/**
	 * @param string $raw UTF-8 contents.
	 * @return string
	 */
	public static function detect_delimiter( $raw ) {
		$sample = substr( $raw, 0, 4096 );
		$counts = array(
			','  => substr_count( $sample, ',' ),
			';'  => substr_count( $sample, ';' ),
			"\t" => substr_count( $sample, "\t" ),
		);
		arsort( $counts );
		$best = array_key_first( $counts );
		return $counts[ $best ] > 0 ? $best : ',';
	}
}
