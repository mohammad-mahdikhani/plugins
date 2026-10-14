<?php
/**
 * XLSX parser via SimpleXLSX.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Xlsx_Parser {

	/**
	 * @param string $path Path.
	 * @return HPL_Parsed_File
	 */
	public static function parse( $path ) {
		self::load();
		$xlsx = \Shuchkin\SimpleXLSX::parse( $path );
		if ( ! $xlsx ) {
			throw new RuntimeException( 'خواندن فایل اکسل ممکن نشد: ' . \Shuchkin\SimpleXLSX::parseError() );
		}

		$parsed = new HPL_Parsed_File();
		$names  = $xlsx->sheetNames();
		foreach ( $names as $index => $name ) {
			$matrix = $xlsx->rows( $index );
			if ( ! is_array( $matrix ) || empty( $matrix ) ) {
				continue;
			}
			$parsed->sheets[] = HPL_Parser::sheet_from_matrix( $matrix, (string) $name );
		}
		if ( empty( $parsed->sheets ) ) {
			throw new RuntimeException( 'برگه‌های فایل اکسل خالی هستند.' );
		}
		return $parsed;
	}

	public static function load() {
		if ( class_exists( '\Shuchkin\SimpleXLSX' ) ) {
			return;
		}
		$dir = defined( 'HPL_DIR' ) ? HPL_DIR : dirname( __DIR__, 2 ) . '/';
		require_once $dir . 'vendor/SimpleXLSX.php';
	}
}
