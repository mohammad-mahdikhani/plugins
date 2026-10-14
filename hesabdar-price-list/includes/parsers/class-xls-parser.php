<?php
/**
 * Legacy XLS parser via SimpleXLS.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Xls_Parser {

	/**
	 * @param string $path Path.
	 * @return HPL_Parsed_File
	 */
	public static function parse( $path ) {
		self::load();
		$xls = \Shuchkin\SimpleXLS::parse( $path );
		if ( ! $xls ) {
			throw new RuntimeException( 'خواندن فایل اکسل قدیمی ممکن نشد: ' . \Shuchkin\SimpleXLS::parseError() );
		}

		$parsed = new HPL_Parsed_File();
		$names  = method_exists( $xls, 'sheetNames' ) ? $xls->sheetNames() : array( 'Sheet1' );
		if ( empty( $names ) ) {
			$names = array( 'Sheet1' );
		}
		foreach ( $names as $index => $name ) {
			$matrix = $xls->rows( $index );
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
		if ( class_exists( '\Shuchkin\SimpleXLS' ) ) {
			return;
		}
		$dir = defined( 'HPL_DIR' ) ? HPL_DIR : dirname( __DIR__, 2 ) . '/';
		require_once $dir . 'vendor/SimpleXLS.php';
	}
}
