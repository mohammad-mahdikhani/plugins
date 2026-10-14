<?php
/**
 * In-memory product index for matching price-list rows.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Catalog {

	/** @var array<string, array<string, mixed>> */
	public $by_id = array();

	/** @var array<string, string> sku => id */
	public $by_sku = array();

	/** @var array<string, string> barcode => id */
	public $by_barcode = array();

	/** @var array<string, string> normalized name => id */
	public $by_name = array();

	/**
	 * @param array<int, array<string, mixed>> $products Products.
	 * @return self
	 */
	public static function from_products( array $products ) {
		$catalog = new self();
		foreach ( $products as $product ) {
			$id = (string) $product['id'];
			$catalog->by_id[ $id ] = array(
				'id'             => $id,
				'sku'            => isset( $product['sku'] ) ? HPL_Normalizer::sku( $product['sku'] ) : '',
				'barcode'        => isset( $product['barcode'] ) ? HPL_Normalizer::sku( $product['barcode'] ) : '',
				'name'           => isset( $product['name'] ) ? (string) $product['name'] : '',
				'name_key'       => isset( $product['name'] ) ? HPL_Normalizer::name( $product['name'] ) : '',
				'regular_price'  => isset( $product['regular_price'] ) ? (string) $product['regular_price'] : '',
				'sale_price'     => isset( $product['sale_price'] ) ? (string) $product['sale_price'] : '',
				'stock'          => isset( $product['stock'] ) ? (string) $product['stock'] : '',
				'type'           => isset( $product['type'] ) ? (string) $product['type'] : 'simple',
				'parent_id'      => isset( $product['parent_id'] ) ? (string) $product['parent_id'] : '',
				'manage_stock'   => ! empty( $product['manage_stock'] ),
			);

			$sku = $catalog->by_id[ $id ]['sku'];
			if ( '' !== $sku && ! isset( $catalog->by_sku[ $sku ] ) ) {
				$catalog->by_sku[ $sku ] = $id;
			}
			$barcode = $catalog->by_id[ $id ]['barcode'];
			if ( '' !== $barcode && ! isset( $catalog->by_barcode[ $barcode ] ) ) {
				$catalog->by_barcode[ $barcode ] = $id;
			}
			$name_key = $catalog->by_id[ $id ]['name_key'];
			if ( '' !== $name_key && ! isset( $catalog->by_name[ $name_key ] ) ) {
				$catalog->by_name[ $name_key ] = $id;
			}
		}
		return $catalog;
	}

	/**
	 * Load sellable WooCommerce products and variations.
	 *
	 * @return self
	 */
	public static function from_woocommerce() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return new self();
		}

		$products = array();
		$page     = 1;
		do {
			$batch = wc_get_products(
				array(
					'status' => array( 'publish', 'private', 'draft' ),
					'type'   => array( 'simple', 'variable', 'variation', 'subscription', 'variable-subscription' ),
					'limit'  => 200,
					'page'   => $page,
					'return' => 'objects',
				)
			);
			if ( empty( $batch ) ) {
				break;
			}
			foreach ( $batch as $product ) {
				$products[] = self::wc_product_to_array( $product );
				if ( $product->is_type( 'variable' ) && method_exists( $product, 'get_children' ) ) {
					foreach ( $product->get_children() as $child_id ) {
						$child = wc_get_product( $child_id );
						if ( $child ) {
							$products[] = self::wc_product_to_array( $child );
						}
					}
				}
			}
			$page++;
		} while ( count( $batch ) === 200 );

		return self::from_products( $products );
	}

	/**
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	public static function wc_product_to_array( $product ) {
		$barcode = $product->get_meta( '_barcode', true );
		if ( '' === $barcode ) {
			$barcode = $product->get_meta( 'barcode', true );
		}
		return array(
			'id'            => $product->get_id(),
			'sku'           => $product->get_sku(),
			'barcode'       => $barcode,
			'name'          => $product->get_name(),
			'regular_price' => $product->get_regular_price(),
			'sale_price'    => $product->get_sale_price(),
			'stock'         => $product->get_stock_quantity(),
			'type'          => $product->get_type(),
			'parent_id'     => $product->get_parent_id(),
			'manage_stock'  => $product->get_manage_stock(),
		);
	}

	/**
	 * @param array<string, mixed> $row      Price row.
	 * @param string[]             $order    Match order.
	 * @return array<string, mixed>|null
	 */
	public function match( array $row, array $order = array( 'sku', 'product_id', 'barcode', 'name' ) ) {
		foreach ( $order as $method ) {
			$found = $this->match_by( $row, $method );
			if ( $found ) {
				$found['matched_by'] = $method;
				return $found;
			}
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $row    Row.
	 * @param string               $method Method.
	 * @return array<string, mixed>|null
	 */
	private function match_by( array $row, $method ) {
		switch ( $method ) {
			case 'sku':
				$sku = isset( $row['sku'] ) ? HPL_Normalizer::sku( $row['sku'] ) : '';
				if ( '' !== $sku && isset( $this->by_sku[ $sku ] ) ) {
					return $this->by_id[ $this->by_sku[ $sku ] ];
				}
				break;
			case 'product_id':
				$id = isset( $row['product_id'] ) ? HPL_Normalizer::sku( $row['product_id'] ) : '';
				if ( '' !== $id && isset( $this->by_id[ $id ] ) ) {
					return $this->by_id[ $id ];
				}
				break;
			case 'barcode':
				$code = isset( $row['barcode'] ) ? HPL_Normalizer::sku( $row['barcode'] ) : '';
				if ( '' === $code && isset( $row['sku'] ) ) {
					$code = HPL_Normalizer::sku( $row['sku'] );
				}
				if ( '' !== $code && isset( $this->by_barcode[ $code ] ) ) {
					return $this->by_id[ $this->by_barcode[ $code ] ];
				}
				break;
			case 'name':
				$name = isset( $row['name'] ) ? HPL_Normalizer::name( $row['name'] ) : '';
				if ( '' !== $name && isset( $this->by_name[ $name ] ) ) {
					return $this->by_id[ $this->by_name[ $name ] ];
				}
				break;
		}
		return null;
	}

	/**
	 * @return int
	 */
	public function count() {
		return count( $this->by_id );
	}
}
