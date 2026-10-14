<?php
/**
 * Detect and apply column mapping for price lists.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Column_Mapper {

	const FIELDS = array( 'sku', 'product_id', 'barcode', 'name', 'regular_price', 'sale_price', 'stock' );

	/**
	 * Aliases are normalized header keys (no space, lowercase, Persian unified).
	 *
	 * @return array<string, string[]>
	 */
	public static function aliases() {
		return array(
			'sku'            => array(
				'sku', 'skucode', 'productsku', 'itemsku', 'code', 'itemcode',
				'کد', 'کدکالا', 'کدمحصول', 'کداقلام', 'شناسهکالا', 'کدجنس',
				'کدانبار', 'شمارهکالا', 'کدفنی', 'کدحسابداری', 'کدسیستم',
				'کدسایت', 'کدووکامرس', 'partno', 'partnumber', 'itemno',
			),
			'product_id'     => array(
				'id', 'productid', 'wc_id', 'wcid', 'postid',
				'شناسه', 'شناسهمحصول', 'آیدی', 'ایدی',
			),
			'barcode'        => array(
				'barcode', 'ean', 'gtin', 'upc', 'بارکد', 'بارکدکالا',
			),
			'name'           => array(
				'name', 'title', 'product', 'productname', 'item', 'itemname', 'description',
				'نام', 'نامکالا', 'ناممحصول', 'محصول', 'کالا', 'شرح', 'شرحکالا', 'عنوان',
			),
			'regular_price'  => array(
				'price', 'regularprice', 'regular_price', 'unitprice', 'amount', 'rate',
				'قیمت', 'قیمتفروش', 'قیمتواحد', 'فی', 'نرخ', 'مبلغ', 'قیمتاصلی',
				'قیمتمصرفکننده', 'قیمتمصرف‌کننده', 'قیمتمصرفکننده',
			),
			'sale_price'     => array(
				'sale', 'saleprice', 'sale_price', 'specialprice', 'discountprice',
				'قیمتویژه', 'قیمتتخفیف', 'حراج', 'قیمتباتخفیف', 'قیمتفروشویژه',
			),
			'stock'          => array(
				'stock', 'qty', 'quantity', 'inventory',
				'موجودی', 'تعداد', 'موجودیانبار', 'موجودی فعلی', 'موجودیفعلی',
			),
		);
	}

	/**
	 * Auto-detect mapping from headers. Values are column indexes or -1 if missing.
	 *
	 * @param string[] $headers Headers.
	 * @return array<string, int>
	 */
	public static function detect( $headers ) {
		$mapping = array();
		foreach ( self::FIELDS as $field ) {
			$mapping[ $field ] = -1;
		}

		$aliases = self::aliases();
		$used    = array();
		$scores  = array();

		foreach ( $headers as $index => $header ) {
			$key = HPL_Normalizer::header_key( $header );
			if ( '' === $key ) {
				continue;
			}
			foreach ( $aliases as $field => $list ) {
				$score = self::alias_score( $key, $list );
				if ( $score <= 0 ) {
					continue;
				}
				$scores[] = array(
					'score' => $score,
					'field' => $field,
					'index' => (int) $index,
				);
			}
		}

		usort(
			$scores,
			static function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return $a['index'] - $b['index'];
				}
				return $b['score'] - $a['score'];
			}
		);

		foreach ( $scores as $hit ) {
			if ( -1 !== $mapping[ $hit['field'] ] ) {
				continue;
			}
			if ( in_array( $hit['index'], $used, true ) ) {
				continue;
			}
			$mapping[ $hit['field'] ] = $hit['index'];
			$used[]                   = $hit['index'];
		}

		if ( -1 === $mapping['regular_price'] ) {
			foreach ( $headers as $index => $header ) {
				$key = HPL_Normalizer::header_key( $header );
				if ( $key && false !== strpos( $key, 'قیمت' ) && (int) $index !== $mapping['sale_price'] && ! in_array( (int) $index, $used, true ) ) {
					$mapping['regular_price'] = (int) $index;
					break;
				}
			}
		}

		return $mapping;
	}

	/**
	 * Higher is better. Exact long aliases beat generic substrings.
	 *
	 * @param string   $key  Normalized header.
	 * @param string[] $list Aliases.
	 * @return int
	 */
	private static function alias_score( $key, array $list ) {
		$best = 0;
		foreach ( $list as $alias ) {
			if ( $key === $alias ) {
				$best = max( $best, 100 + strlen( $alias ) );
				continue;
			}
			if ( strlen( $alias ) < 3 ) {
				continue;
			}
			if ( false !== strpos( $key, $alias ) ) {
				$best = max( $best, 40 + strlen( $alias ) );
			} elseif ( strlen( $key ) >= 3 && false !== strpos( $alias, $key ) ) {
				$best = max( $best, 20 + strlen( $key ) );
			}
		}
		return $best;
	}

	/**
	 * Convert a sheet into normalized price rows using a mapping.
	 *
	 * @param HPL_Sheet          $sheet   Sheet.
	 * @param array<string, int> $mapping Column indexes.
	 * @return array<int, array<string, mixed>>
	 */
	public static function apply( HPL_Sheet $sheet, array $mapping ) {
		$out = array();
		foreach ( $sheet->rows as $offset => $cells ) {
			$row = array(
				'sku'           => '',
				'product_id'    => '',
				'barcode'       => '',
				'name'          => '',
				'regular_price' => '',
				'sale_price'    => '',
				'stock'         => '',
				'source_row'    => $sheet->header_line + $offset + 1,
			);

			foreach ( self::FIELDS as $field ) {
				$col = isset( $mapping[ $field ] ) ? (int) $mapping[ $field ] : -1;
				if ( $col < 0 ) {
					continue;
				}
				$raw = isset( $cells[ $col ] ) ? $cells[ $col ] : '';
				if ( 'regular_price' === $field || 'sale_price' === $field ) {
					$parsed = HPL_Normalizer::price( $raw );
					$row[ $field ] = ( false === $parsed ) ? '__invalid__' : $parsed;
				} elseif ( 'stock' === $field ) {
					$parsed        = HPL_Normalizer::stock( $raw );
					$row[ $field ] = ( false === $parsed ) ? '__invalid__' : $parsed;
				} elseif ( 'product_id' === $field ) {
					$row[ $field ] = HPL_Normalizer::sku( $raw );
				} elseif ( 'name' === $field ) {
					$row[ $field ] = HPL_Normalizer::to_string( $raw );
				} else {
					$row[ $field ] = HPL_Normalizer::sku( $raw );
				}
			}

			$row['identifiers'] = array();
			foreach ( array( $row['sku'], $row['product_id'], $row['barcode'] ) as $ident ) {
				if ( '' !== $ident ) {
					$row['identifiers'][] = $ident;
				}
			}
			$price_cols = array();
			foreach ( array( 'regular_price', 'sale_price', 'stock' ) as $field ) {
				if ( isset( $mapping[ $field ] ) && (int) $mapping[ $field ] >= 0 ) {
					$price_cols[ (int) $mapping[ $field ] ] = true;
				}
			}
			foreach ( $cells as $col => $raw ) {
				if ( isset( $price_cols[ (int) $col ] ) ) {
					continue;
				}
				$code = HPL_Normalizer::sku( $raw );
				if ( strlen( $code ) >= 2 && strlen( $code ) <= 64 ) {
					$row['identifiers'][] = $code;
				}
			}
			$row['identifiers'] = array_values( array_unique( array_filter( $row['identifiers'] ) ) );

			if ( self::is_empty_row( $row ) ) {
				continue;
			}
			$out[] = $row;
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return bool
	 */
	public static function is_empty_row( array $row ) {
		$keys = array( 'sku', 'product_id', 'barcode', 'name', 'regular_price', 'sale_price' );
		foreach ( $keys as $key ) {
			if ( isset( $row[ $key ] ) && '' !== $row[ $key ] && '__invalid__' !== $row[ $key ] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Labels for the mapping UI.
	 *
	 * @return array<string, string>
	 */
	public static function labels() {
		return array(
			'sku'           => 'کد کالا (SKU)',
			'product_id'    => 'شناسه محصول',
			'barcode'       => 'بارکد',
			'name'          => 'نام کالا',
			'regular_price' => 'قیمت',
			'sale_price'    => 'قیمت ویژه',
			'stock'         => 'موجودی',
		);
	}
}
