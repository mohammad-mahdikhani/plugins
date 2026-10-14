<?php
/**
 * Screening engine: every file row vs every shop product.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Screener {

	const ON_SITE     = 'on_site';
	const REVIEW      = 'review';
	const NOT_ON_SITE = 'not_on_site';

	/**
	 * @param array<int, array<string, mixed>> $rows     File rows.
	 * @param HPL_Catalog                      $catalog  Shop catalog.
	 * @param HPL_Updater                      $updater  Updater.
	 * @return array<string, mixed>
	 */
	public static function run( array $rows, HPL_Catalog $catalog, HPL_Updater $updater ) {
		$on_site     = array();
		$review      = array();
		$not_on_site = array();
		$used_ids    = array();

		foreach ( $rows as $index => $row ) {
			$entry = self::classify( $row, $catalog, $updater, (int) $index );
			if ( self::ON_SITE === $entry['bucket'] ) {
				$on_site[] = $entry;
				if ( ! empty( $entry['item']['product_id'] ) ) {
					$used_ids[ (string) $entry['item']['product_id'] ] = true;
				}
			} elseif ( self::REVIEW === $entry['bucket'] ) {
				$review[] = $entry;
			} else {
				$not_on_site[] = $entry;
			}
		}

		$not_in_file = array();
		foreach ( $catalog->by_id as $id => $product ) {
			if ( isset( $used_ids[ (string) $id ] ) ) {
				continue;
			}
			if ( 'variable' === $product['type'] || 'variable-subscription' === $product['type'] ) {
				continue;
			}
			$not_in_file[] = array(
				'id'            => $product['id'],
				'name'          => $product['name'],
				'sku'           => $product['sku'],
				'regular_price' => $product['regular_price'],
				'stock'         => $product['stock'],
			);
			if ( count( $not_in_file ) >= 300 ) {
				break;
			}
		}

		$plan_items = array();
		foreach ( $on_site as $entry ) {
			$plan_items[] = $entry['item'];
		}

		return array(
			'stats' => array(
				'file_rows'     => count( $rows ),
				'site_products' => $catalog->count(),
				'on_site'       => count( $on_site ),
				'review'        => count( $review ),
				'not_on_site'   => count( $not_on_site ),
				'not_in_file'   => count( $not_in_file ),
				'changed'       => self::count_status( $on_site, 'changed' ),
				'unchanged'     => self::count_status( $on_site, 'unchanged' ),
			),
			'on_site'     => $on_site,
			'review'      => $review,
			'not_on_site' => $not_on_site,
			'not_in_file' => $not_in_file,
			'plan_items'  => $plan_items,
		);
	}

	/**
	 * @param array<string, mixed> $row     Row.
	 * @param HPL_Catalog          $catalog Catalog.
	 * @param HPL_Updater          $updater Updater.
	 * @param int                  $index   Row index.
	 * @return array<string, mixed>
	 */
	public static function classify( array $row, HPL_Catalog $catalog, HPL_Updater $updater, $index = 0 ) {
		$ranked = $catalog->rank( $row, 4 );
		$top    = isset( $ranked[0] ) ? $ranked[0] : null;
		$score  = $top ? (float) $top['score'] : 0.0;

		$candidates = array();
		foreach ( $ranked as $hit ) {
			$p            = $hit['product'];
			$candidates[] = array(
				'id'            => $p['id'],
				'name'          => $p['name'],
				'sku'           => $p['sku'],
				'regular_price' => $p['regular_price'],
				'stock'         => $p['stock'],
				'score'         => round( (float) $hit['score'], 3 ),
				'reason'        => $hit['reason'],
			);
		}

		$bucket = self::NOT_ON_SITE;
		if ( $score >= 0.86 ) {
			$bucket = self::ON_SITE;
		} elseif ( $score >= 0.5 ) {
			$bucket = self::REVIEW;
		}

		if ( self::ON_SITE === $bucket && $top ) {
			$row['product_id'] = $top['product']['id'];
			if ( empty( $row['sku'] ) && ! empty( $top['product']['sku'] ) ) {
				$row['sku'] = $top['product']['sku'];
			}
		}

		$item = $updater->plan_row( $row );
		$item['row_index']   = $index;
		$item['confidence']  = $score;
		$item['bucket']      = $bucket;
		$item['action']      = ( self::ON_SITE === $bucket ) ? 'update' : 'skip';
		$item['candidates']  = $candidates;
		if ( self::ON_SITE !== $bucket ) {
			$item['status'] = ( self::REVIEW === $bucket ) ? 'review' : 'missing';
			if ( $top ) {
				$item['hint'] = $top['product']['name'] . ( $top['product']['sku'] ? ' — ' . $top['product']['sku'] : '' );
			}
		}

		return array(
			'index'       => $index,
			'bucket'      => $bucket,
			'item'        => $item,
			'row'         => $row,
			'candidates'  => $candidates,
		);
	}

	/**
	 * Accept a suggested shop product for a file row.
	 *
	 * @param array<string, mixed> $row       Row.
	 * @param array<string, mixed> $product   Product.
	 * @param HPL_Updater          $updater   Updater.
	 * @param int                  $index     Index.
	 * @return array<string, mixed>
	 */
	public static function link( array $row, array $product, HPL_Updater $updater, $index ) {
		$row['product_id'] = $product['id'];
		if ( ! empty( $product['sku'] ) ) {
			$row['sku'] = $product['sku'];
		}
		$item = $updater->plan_row( $row );
		$item['row_index']  = $index;
		$item['confidence'] = 1;
		$item['bucket']     = self::ON_SITE;
		$item['action']     = 'update';
		$item['matched_by'] = 'manual';
		return $item;
	}

	/**
	 * Build a create-action item from a file row.
	 *
	 * @param array<string, mixed> $row   Row.
	 * @param int                  $index Index.
	 * @return array<string, mixed>
	 */
	public static function prepare_create( array $row, $index ) {
		$name = isset( $row['name'] ) ? trim( (string) $row['name'] ) : '';
		if ( '' === $name ) {
			$name = isset( $row['sku'] ) && $row['sku'] ? 'کالا ' . $row['sku'] : 'کالای فایل #' . ( $index + 1 );
		}
		$sku = isset( $row['sku'] ) ? HPL_Normalizer::sku( $row['sku'] ) : '';
		return array(
			'source_row'    => isset( $row['source_row'] ) ? $row['source_row'] : $index + 1,
			'row_index'     => $index,
			'sku'           => $sku,
			'name'          => $name,
			'product_id'    => '',
			'product_name'  => $name,
			'matched_by'    => 'create',
			'old_regular'   => '',
			'new_regular'   => isset( $row['regular_price'] ) && '__invalid__' !== $row['regular_price'] ? $row['regular_price'] : '',
			'old_sale'      => '',
			'new_sale'      => isset( $row['sale_price'] ) && '__invalid__' !== $row['sale_price'] ? $row['sale_price'] : '',
			'old_stock'     => '',
			'new_stock'     => isset( $row['stock'] ) && '__invalid__' !== $row['stock'] ? $row['stock'] : '',
			'status'        => 'create',
			'action'        => 'create',
			'bucket'        => self::NOT_ON_SITE,
			'changes'       => array( 'regular', 'stock' ),
			'confidence'    => 0,
		);
	}

	/**
	 * Create a simple WooCommerce product from a screening item.
	 *
	 * @param array<string, mixed> $item Item.
	 * @param bool                 $publish Publish.
	 * @return bool
	 */
	public static function create_woocommerce( array $item, $publish = false ) {
		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return false;
		}
		$name = isset( $item['name'] ) ? $item['name'] : ( isset( $item['product_name'] ) ? $item['product_name'] : '' );
		if ( '' === $name ) {
			return false;
		}
		$product = new WC_Product_Simple();
		$product->set_name( $name );
		$sku = isset( $item['sku'] ) ? HPL_Normalizer::sku( $item['sku'] ) : '';
		if ( '' !== $sku ) {
			if ( function_exists( 'wc_get_product_id_by_sku' ) && wc_get_product_id_by_sku( $sku ) ) {
				$sku = $sku . '-' . wp_generate_password( 4, false, false );
			}
			try {
				$product->set_sku( $sku );
			} catch ( Exception $e ) {
				$product->set_sku( '' );
			}
		}
		if ( isset( $item['new_regular'] ) && '' !== $item['new_regular'] ) {
			$product->set_regular_price( $item['new_regular'] );
		}
		if ( isset( $item['new_sale'] ) && '' !== $item['new_sale'] ) {
			$product->set_sale_price( $item['new_sale'] );
		}
		if ( isset( $item['new_stock'] ) && '' !== $item['new_stock'] ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $item['new_stock'] );
			$product->set_stock_status( (int) $item['new_stock'] > 0 ? 'instock' : 'outofstock' );
		}
		$product->set_status( $publish ? 'publish' : 'draft' );
		$product->save();
		return $product->get_id() > 0;
	}

	/**
	 * @param array<int, array<string, mixed>> $entries Entries.
	 * @param string                           $status  Status.
	 * @return int
	 */
	private static function count_status( array $entries, $status ) {
		$n = 0;
		foreach ( $entries as $entry ) {
			if ( isset( $entry['item']['status'] ) && $entry['item']['status'] === $status ) {
				$n++;
			}
		}
		return $n;
	}
}
