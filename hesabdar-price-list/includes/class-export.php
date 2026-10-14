<?php
/**
 * Export current WooCommerce prices and sample templates.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Export {

	public static function load_gen() {
		if ( class_exists( '\Shuchkin\SimpleXLSXGen' ) ) {
			return;
		}
		$dir = defined( 'HPL_DIR' ) ? HPL_DIR : dirname( __DIR__ ) . '/';
		require_once $dir . 'vendor/SimpleXLSXGen.php';
	}

	/**
	 * Sample template rows.
	 *
	 * @return array<int, array<int, mixed>>
	 */
	public static function sample_rows() {
		return array(
			array( 'کد کالا', 'نام کالا', 'قیمت', 'قیمت ویژه', 'موجودی' ),
			array( 'SKU-100', 'روغن مایع ۱.۸ لیتر', '185000', '169000', '40' ),
			array( 'SKU-101', 'برنج ایرانی ۱۰ کیلو', '920000', '', '12' ),
			array( 'SKU-102', 'چای سیاه ۵۰۰ گرم', '210000', '189000', '25' ),
		);
	}

	/**
	 * @return string Binary xlsx.
	 */
	public static function sample_xlsx() {
		self::load_gen();
		$xlsx = \Shuchkin\SimpleXLSXGen::fromArray( self::sample_rows() );
		$xlsx->setDefaultFont( 'Tahoma' );
		if ( method_exists( $xlsx, 'rightToLeft' ) ) {
			$xlsx->rightToLeft();
		}
		return $xlsx->__toString();
	}

	/**
	 * @return string CSV.
	 */
	public static function sample_csv() {
		$out = fopen( 'php://temp', 'r+' );
		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		foreach ( self::sample_rows() as $row ) {
			fputcsv( $out, $row );
		}
		rewind( $out );
		$csv = stream_get_contents( $out );
		fclose( $out );
		return $csv;
	}

	/**
	 * Current catalog as rows.
	 *
	 * @param HPL_Catalog $catalog Catalog.
	 * @return array<int, array<int, mixed>>
	 */
	public static function catalog_rows( HPL_Catalog $catalog ) {
		$rows = array(
			array( 'شناسه', 'کد کالا', 'بارکد', 'نام کالا', 'قیمت', 'قیمت ویژه', 'موجودی' ),
		);
		foreach ( $catalog->by_id as $product ) {
			if ( 'variable' === $product['type'] ) {
				continue;
			}
			$rows[] = array(
				$product['id'],
				$product['sku'],
				$product['barcode'],
				$product['name'],
				$product['regular_price'],
				$product['sale_price'],
				$product['stock'],
			);
		}
		return $rows;
	}

	/**
	 * @param HPL_Catalog $catalog Catalog.
	 * @return string
	 */
	public static function catalog_xlsx( HPL_Catalog $catalog ) {
		self::load_gen();
		$xlsx = \Shuchkin\SimpleXLSXGen::fromArray( self::catalog_rows( $catalog ) );
		$xlsx->setDefaultFont( 'Tahoma' );
		if ( method_exists( $xlsx, 'rightToLeft' ) ) {
			$xlsx->rightToLeft();
		}
		return $xlsx->__toString();
	}
}
