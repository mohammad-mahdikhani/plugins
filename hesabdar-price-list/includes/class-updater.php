<?php
/**
 * Compare price-list rows with the catalog and apply updates.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Updater {

	/** @var HPL_Catalog */
	private $catalog;

	/** @var array<string, mixed> */
	private $settings;

	/**
	 * @param HPL_Catalog          $catalog  Catalog.
	 * @param array<string, mixed> $settings Settings.
	 */
	public function __construct( HPL_Catalog $catalog, array $settings = array() ) {
		$this->catalog  = $catalog;
		$this->settings = array_merge( self::defaults(), $settings );
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'match_order'      => array( 'sku', 'product_id', 'barcode', 'name' ),
			'update_regular'   => true,
			'update_sale'      => true,
			'update_stock'     => false,
			'skip_empty_price' => true,
			'zero_clears_sale' => true,
		);
	}

	/**
	 * Build preview/apply plan without touching products.
	 *
	 * @param array<int, array<string, mixed>> $rows Price rows.
	 * @return array{items: array, stats: array}
	 */
	public function plan( array $rows ) {
		$items = array();
		$stats = array(
			'total'      => 0,
			'matched'    => 0,
			'missing'    => 0,
			'changed'    => 0,
			'unchanged'  => 0,
			'invalid'    => 0,
			'skipped'    => 0,
		);

		foreach ( $rows as $row ) {
			$stats['total']++;
			$item = $this->plan_row( $row );
			$stats[ $item['status'] ] = isset( $stats[ $item['status'] ] ) ? $stats[ $item['status'] ] + 1 : 1;
			if ( 'changed' === $item['status'] || 'unchanged' === $item['status'] ) {
				$stats['matched']++;
			}
			$items[] = $item;
		}

		return array(
			'items' => $items,
			'stats' => $stats,
		);
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>
	 */
	public function plan_row( array $row ) {
		$item = array(
			'source_row'    => isset( $row['source_row'] ) ? $row['source_row'] : 0,
			'sku'           => isset( $row['sku'] ) ? $row['sku'] : '',
			'name'          => isset( $row['name'] ) ? $row['name'] : '',
			'product_id'    => '',
			'product_name'  => '',
			'matched_by'    => '',
			'old_regular'   => '',
			'new_regular'   => '',
			'old_sale'      => '',
			'new_sale'      => '',
			'old_stock'     => '',
			'new_stock'     => '',
			'status'        => 'missing',
			'changes'       => array(),
		);

		if ( ( isset( $row['regular_price'] ) && '__invalid__' === $row['regular_price'] )
			|| ( isset( $row['sale_price'] ) && '__invalid__' === $row['sale_price'] )
			|| ( isset( $row['stock'] ) && '__invalid__' === $row['stock'] ) ) {
			$item['status'] = 'invalid';
			return $item;
		}

		$product = $this->catalog->match( $row, $this->settings['match_order'] );
		if ( ! $product ) {
			$item['status'] = 'missing';
			return $item;
		}

		$item['product_id']   = $product['id'];
		$item['product_name'] = $product['name'];
		$item['matched_by']   = $product['matched_by'];
		$item['old_regular']  = (string) $product['regular_price'];
		$item['old_sale']     = (string) $product['sale_price'];
		$item['old_stock']    = (string) $product['stock'];
		$item['new_regular']  = $item['old_regular'];
		$item['new_sale']     = $item['old_sale'];
		$item['new_stock']    = $item['old_stock'];

		$changes = array();

		if ( $this->settings['update_regular'] ) {
			$incoming = isset( $row['regular_price'] ) ? $row['regular_price'] : '';
			if ( '' === $incoming && $this->settings['skip_empty_price'] ) {
				// keep current
			} elseif ( '' !== $incoming && $incoming !== (string) $product['regular_price'] ) {
				$item['new_regular'] = $incoming;
				$changes[]           = 'regular';
			}
		}

		if ( $this->settings['update_sale'] ) {
			$incoming = isset( $row['sale_price'] ) ? $row['sale_price'] : '';
			if ( '' === $incoming ) {
				// keep current sale
			} elseif ( '0' === $incoming && $this->settings['zero_clears_sale'] ) {
				if ( '' !== (string) $product['sale_price'] ) {
					$item['new_sale'] = '';
					$changes[]        = 'sale';
				}
			} elseif ( $incoming !== (string) $product['sale_price'] ) {
				$item['new_sale'] = $incoming;
				$changes[]        = 'sale';
			}
		}

		if ( $this->settings['update_stock'] ) {
			$incoming = isset( $row['stock'] ) ? $row['stock'] : '';
			if ( '' !== $incoming && $incoming !== (string) $product['stock'] ) {
				$item['new_stock'] = $incoming;
				$changes[]         = 'stock';
			}
		}

		$item['changes'] = $changes;
		$item['status']  = empty( $changes ) ? 'unchanged' : 'changed';
		return $item;
	}

	/**
	 * Apply planned changes through a writer callback.
	 *
	 * Writer signature: function(array $item): bool
	 *
	 * @param array<int, array<string, mixed>> $items  Plan items.
	 * @param callable                         $writer Writer.
	 * @param int                              $offset Offset.
	 * @param int                              $limit  Limit.
	 * @return array{processed:int,updated:int,failed:int,done:bool,offset:int}
	 */
	public function apply_slice( array $items, $writer, $offset = 0, $limit = 50 ) {
		$slice     = array_slice( $items, $offset, $limit );
		$updated   = 0;
		$failed    = 0;
		$processed = 0;

		foreach ( $slice as $item ) {
			$processed++;
			if ( 'changed' !== $item['status'] ) {
				continue;
			}
			try {
				$ok = call_user_func( $writer, $item );
				if ( $ok ) {
					$updated++;
				} else {
					$failed++;
				}
			} catch ( Exception $e ) {
				$failed++;
			}
		}

		$next = $offset + count( $slice );
		return array(
			'processed' => $processed,
			'updated'   => $updated,
			'failed'    => $failed,
			'done'      => $next >= count( $items ),
			'offset'    => $next,
			'total'     => count( $items ),
		);
	}

	/**
	 * WooCommerce product writer.
	 *
	 * @param array<string, mixed> $item Plan item.
	 * @return bool
	 */
	public static function write_woocommerce( array $item ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return false;
		}
		$product = wc_get_product( (int) $item['product_id'] );
		if ( ! $product ) {
			return false;
		}
		if ( $product->is_type( array( 'variable', 'grouped' ) ) ) {
			return false;
		}

		$changed = false;
		if ( in_array( 'regular', $item['changes'], true ) ) {
			$product->set_regular_price( $item['new_regular'] );
			$changed = true;
		}
		if ( in_array( 'sale', $item['changes'], true ) ) {
			$product->set_sale_price( $item['new_sale'] );
			$changed = true;
		}
		if ( in_array( 'stock', $item['changes'], true ) ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $item['new_stock'] );
			$changed = true;
		}

		if ( ! $changed ) {
			return true;
		}

		// Keep sale valid relative to regular.
		$regular = $product->get_regular_price();
		$sale    = $product->get_sale_price();
		if ( '' !== $sale && '' !== $regular && (float) $sale >= (float) $regular ) {
			$product->set_sale_price( '' );
		}

		$product->save();
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $product->get_id() );
		}
		return true;
	}

	/**
	 * Restore previous prices for a rollback snapshot.
	 *
	 * @param array<string, mixed> $change History row.
	 * @return bool
	 */
	public static function restore_woocommerce( array $change ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return false;
		}
		$product = wc_get_product( (int) $change['product_id'] );
		if ( ! $product ) {
			return false;
		}
		if ( array_key_exists( 'old_regular', $change ) ) {
			$product->set_regular_price( $change['old_regular'] );
		}
		if ( array_key_exists( 'old_sale', $change ) ) {
			$product->set_sale_price( $change['old_sale'] );
		}
		if ( array_key_exists( 'old_stock', $change ) && '' !== $change['old_stock'] && null !== $change['old_stock'] ) {
			$product->set_manage_stock( true );
			$product->set_stock_quantity( (int) $change['old_stock'] );
		}
		$product->save();
		return true;
	}
}
