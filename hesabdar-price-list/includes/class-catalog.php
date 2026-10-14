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

	/** @var array<string, string> any code variant => id */
	public $by_code = array();

	/** @var array<string, int> */
	private $code_priority = array();

	/** @var array<string, string> name_core => id */
	public $by_name_core = array();

	/** @var array<string, string[]> name_core => ids when duplicates */
	private $name_core_dups = array();

	/** @var array<string, array<string, bool>> token => product id set */
	public $inv = array();

	/**
	 * @param array<int, array<string, mixed>> $products Products.
	 * @return self
	 */
	public static function from_products( array $products ) {
		$catalog = new self();
		foreach ( $products as $product ) {
			$id = (string) $product['id'];
			$type = isset( $product['type'] ) ? (string) $product['type'] : 'simple';
			$catalog->by_id[ $id ] = array(
				'id'            => $id,
				'sku'           => isset( $product['sku'] ) ? HPL_Normalizer::sku( $product['sku'] ) : '',
				'barcode'       => isset( $product['barcode'] ) ? HPL_Normalizer::sku( $product['barcode'] ) : '',
				'name'          => isset( $product['name'] ) ? (string) $product['name'] : '',
				'name_key'      => isset( $product['name'] ) ? HPL_Normalizer::name( $product['name'] ) : '',
				'name_core'     => isset( $product['name'] ) ? HPL_Normalizer::name_core( $product['name'] ) : '',
				'name_compact'  => isset( $product['name'] ) ? HPL_Normalizer::name_compact( $product['name'] ) : '',
				'name_tokens'   => isset( $product['name'] ) ? HPL_Normalizer::name_tokens( $product['name'] ) : array(),
				'regular_price' => isset( $product['regular_price'] ) ? (string) $product['regular_price'] : '',
				'sale_price'    => isset( $product['sale_price'] ) ? (string) $product['sale_price'] : '',
				'stock'         => isset( $product['stock'] ) ? (string) $product['stock'] : '',
				'type'          => $type,
				'parent_id'     => isset( $product['parent_id'] ) ? (string) $product['parent_id'] : '',
				'manage_stock'  => ! empty( $product['manage_stock'] ),
			);

			$priority = self::type_priority( $type );
			$record   = $catalog->by_id[ $id ];

			if ( '' !== $record['sku'] && ! isset( $catalog->by_sku[ $record['sku'] ] ) ) {
				$catalog->by_sku[ $record['sku'] ] = $id;
			}
			if ( '' !== $record['barcode'] && ! isset( $catalog->by_barcode[ $record['barcode'] ] ) ) {
				$catalog->by_barcode[ $record['barcode'] ] = $id;
			}

			$catalog->index_code( $id, $id, $priority + 5 );
			foreach ( HPL_Normalizer::code_keys( $record['sku'] ) as $key ) {
				$catalog->index_code( $key, $id, $priority );
			}
			foreach ( HPL_Normalizer::code_keys( $record['barcode'] ) as $key ) {
				$catalog->index_code( $key, $id, $priority );
			}
			if ( ! empty( $product['extra_codes'] ) && is_array( $product['extra_codes'] ) ) {
				foreach ( $product['extra_codes'] as $extra ) {
					foreach ( HPL_Normalizer::code_keys( $extra ) as $key ) {
						$catalog->index_code( $key, $id, $priority );
					}
				}
			}

			$catalog->index_search_tokens( $id, $record );

			if ( self::is_parent_variable( $type ) ) {
				continue;
			}

			if ( '' !== $record['name_key'] && ! isset( $catalog->by_name[ $record['name_key'] ] ) ) {
				$catalog->by_name[ $record['name_key'] ] = $id;
			}

			$core = $record['name_core'];
			if ( '' !== $core ) {
				if ( isset( $catalog->by_name_core[ $core ] ) && $catalog->by_name_core[ $core ] !== $id ) {
					$catalog->name_core_dups[ $core ] = true;
					unset( $catalog->by_name_core[ $core ] );
				} elseif ( empty( $catalog->name_core_dups[ $core ] ) ) {
					$catalog->by_name_core[ $core ] = $id;
				}
			}
		}
		return $catalog;
	}

	/**
	 * @param string $type Type.
	 * @return bool
	 */
	private static function is_parent_variable( $type ) {
		return 'variable' === $type || 'variable-subscription' === $type;
	}

	/**
	 * @param string $type Type.
	 * @return int
	 */
	private static function type_priority( $type ) {
		if ( 'variation' === $type || 'subscription_variation' === $type ) {
			return 3;
		}
		if ( self::is_parent_variable( $type ) ) {
			return 1;
		}
		return 2;
	}

	/**
	 * @param string $key      Code.
	 * @param string $id       Product id.
	 * @param int    $priority Priority.
	 */
	/**
	 * @param string               $id     Product id.
	 * @param array<string, mixed> $record Product.
	 */
	private function index_search_tokens( $id, array $record ) {
		$tokens = isset( $record['name_tokens'] ) ? $record['name_tokens'] : array();
		foreach ( array( $record['sku'], $record['barcode'], $id ) as $code ) {
			foreach ( HPL_Normalizer::code_keys( $code ) as $key ) {
				$tokens[] = mb_strtolower( $key, 'UTF-8' );
			}
		}
		foreach ( $tokens as $token ) {
			$token = trim( (string) $token );
			if ( mb_strlen( $token, 'UTF-8' ) < 2 ) {
				continue;
			}
			if ( ! isset( $this->inv[ $token ] ) ) {
				$this->inv[ $token ] = array();
			}
			$this->inv[ $token ][ $id ] = true;
		}
	}

	private function index_code( $key, $id, $priority ) {
		if ( '' === $key ) {
			return;
		}
		if ( ! isset( $this->by_code[ $key ] ) || $priority > $this->code_priority[ $key ] ) {
			$this->by_code[ $key ]       = $id;
			$this->code_priority[ $key ] = $priority;
		}
	}

	/**
	 * Load sellable WooCommerce products and variations.
	 *
	 * @return self
	 */
	public static function from_woocommerce() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return new self();
		}

		$ids = array();
		if ( function_exists( 'get_posts' ) ) {
			$ids = get_posts(
				array(
					'post_type'              => array( 'product', 'product_variation' ),
					'post_status'            => array( 'publish', 'private', 'draft', 'pending' ),
					'numberposts'            => -1,
					'fields'                 => 'ids',
					'suppress_filters'       => false,
					'no_found_rows'          => true,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false,
				)
			);
		}
		if ( empty( $ids ) && function_exists( 'wc_get_products' ) ) {
			$page = 1;
			do {
				$batch = wc_get_products(
					array(
						'status' => array( 'publish', 'private', 'draft', 'pending' ),
						'type'   => array( 'simple', 'variable', 'variation', 'external', 'subscription', 'variable-subscription' ),
						'limit'  => 200,
						'page'   => $page,
						'return' => 'ids',
					)
				);
				if ( empty( $batch ) ) {
					break;
				}
				$ids = array_merge( $ids, $batch );
				$page++;
			} while ( count( $batch ) === 200 );
		}

		$products = array();
		$seen     = array();
		foreach ( (array) $ids as $id ) {
			$id = (int) $id;
			if ( $id <= 0 || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			$product     = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$products[] = self::wc_product_to_array( $product );
			if ( $product->is_type( 'variable' ) && method_exists( $product, 'get_children' ) ) {
				foreach ( $product->get_children() as $child_id ) {
					$child_id = (int) $child_id;
					if ( isset( $seen[ $child_id ] ) ) {
						continue;
					}
					$seen[ $child_id ] = true;
					$child             = wc_get_product( $child_id );
					if ( $child ) {
						$products[] = self::wc_product_to_array( $child );
					}
				}
			}
		}

		return self::from_products( $products );
	}

	/**
	 * @param WC_Product $product Product.
	 * @return array<string, mixed>
	 */
	public static function wc_product_to_array( $product ) {
		$extra = array();
		$keys  = array(
			'_barcode',
			'barcode',
			'_gtin',
			'gtin',
			'_ean',
			'ean',
			'_upc',
			'upc',
			'_alg_ean',
			'_wpm_gtin_code',
			'hwp_product_code',
			'_product_code',
			'product_code',
			'_sku_old',
			'_wc_barcode',
		);
		foreach ( $keys as $meta_key ) {
			$val = $product->get_meta( $meta_key, true );
			if ( is_string( $val ) && '' !== trim( $val ) ) {
				$extra[] = $val;
			}
		}
		if ( method_exists( $product, 'get_global_unique_id' ) ) {
			$guid = $product->get_global_unique_id();
			if ( $guid ) {
				$extra[] = $guid;
			}
		}

		$barcode = '';
		foreach ( $extra as $code ) {
			$barcode = HPL_Normalizer::sku( $code );
			if ( '' !== $barcode ) {
				break;
			}
		}

		return array(
			'id'            => $product->get_id(),
			'sku'           => $product->get_sku(),
			'barcode'       => $barcode,
			'extra_codes'   => $extra,
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
	 * @param string[]             $order    Match order (kept for compatibility).
	 * @return array<string, mixed>|null
	 */
	public function match( array $row, array $order = array( 'sku', 'product_id', 'barcode', 'name' ) ) {
		unset( $order );
		$found = $this->match_codes( $row );
		if ( $found ) {
			return $found;
		}
		return $this->match_name( $row );
	}

	/**
	 * Try every identifier on the row against every indexed code.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>|null
	 */
	private function match_codes( array $row ) {
		$raws = array();
		foreach ( array( 'sku', 'product_id', 'barcode' ) as $field ) {
			if ( ! empty( $row[ $field ] ) ) {
				$raws[] = $row[ $field ];
			}
		}
		if ( ! empty( $row['identifiers'] ) && is_array( $row['identifiers'] ) ) {
			$raws = array_merge( $raws, $row['identifiers'] );
		}

		$keys = array();
		foreach ( $raws as $raw ) {
			foreach ( HPL_Normalizer::code_keys( $raw ) as $key ) {
				$keys[ $key ] = true;
			}
		}
		$keys = array_keys( $keys );

		foreach ( $keys as $key ) {
			if ( isset( $this->by_sku[ $key ] ) ) {
				$product               = $this->by_id[ $this->by_sku[ $key ] ];
				$product['matched_by'] = 'sku';
				return $product;
			}
		}
		foreach ( $keys as $key ) {
			if ( isset( $this->by_id[ $key ] ) && ! self::is_parent_variable( $this->by_id[ $key ]['type'] ) ) {
				$product               = $this->by_id[ $key ];
				$product['matched_by'] = 'product_id';
				return $product;
			}
		}
		foreach ( $keys as $key ) {
			if ( isset( $this->by_barcode[ $key ] ) ) {
				$product               = $this->by_id[ $this->by_barcode[ $key ] ];
				$product['matched_by'] = 'barcode';
				return $product;
			}
		}
		foreach ( $keys as $key ) {
			if ( isset( $this->by_code[ $key ] ) ) {
				$product = $this->by_id[ $this->by_code[ $key ] ];
				if ( self::is_parent_variable( $product['type'] ) ) {
					continue;
				}
				$product['matched_by'] = 'code';
				return $product;
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
				return $this->match_name( $row );
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return array<string, mixed>|null
	 */
	private function match_name( array $row ) {
		$raw = isset( $row['name'] ) ? $row['name'] : '';
		if ( '' === $raw ) {
			return null;
		}

		$key = HPL_Normalizer::name( $raw );
		if ( '' !== $key && isset( $this->by_name[ $key ] ) ) {
			$product               = $this->by_id[ $this->by_name[ $key ] ];
			$product['matched_by'] = 'name';
			return $product;
		}

		$core = HPL_Normalizer::name_core( $raw );
		if ( '' !== $core && isset( $this->by_name_core[ $core ] ) ) {
			$product               = $this->by_id[ $this->by_name_core[ $core ] ];
			$product['matched_by'] = 'name';
			return $product;
		}

		$compact = HPL_Normalizer::name_compact( $raw );
		if ( mb_strlen( $compact, 'UTF-8' ) >= 6 ) {
			$contains = array();
			foreach ( $this->by_id as $id => $product ) {
				if ( self::is_parent_variable( $product['type'] ) || '' === $product['name_compact'] ) {
					continue;
				}
				$hay = $product['name_compact'];
				if ( false !== mb_strpos( $hay, $compact, 0, 'UTF-8' ) || false !== mb_strpos( $compact, $hay, 0, 'UTF-8' ) ) {
					$contains[ $id ] = true;
				}
			}
			if ( 1 === count( $contains ) ) {
				$id                    = (string) array_key_first( $contains );
				$product               = $this->by_id[ $id ];
				$product['matched_by'] = 'name';
				return $product;
			}
		}

		return $this->match_name_scored( $raw );
	}

	/**
	 * Score shop titles against a supplier name. Model numbers must match.
	 *
	 * @param string $raw Raw excel name.
	 * @return array<string, mixed>|null
	 */
	private function match_name_scored( $raw ) {
		$query_tokens = HPL_Normalizer::name_tokens( $raw );
		if ( count( $query_tokens ) < 2 ) {
			return null;
		}

		$numbers = array();
		$strong  = array();
		foreach ( $query_tokens as $token ) {
			if ( preg_match( '/^\d+([.\/]\d+)?$/', $token ) ) {
				$numbers[] = $token;
			}
			if ( preg_match( '/^\d+([.\/]\d+)?$/', $token ) || mb_strlen( $token, 'UTF-8' ) >= 4 ) {
				$strong[] = $token;
			}
		}

		$best    = array();
		$best_sc = 0;
		$second  = 0;

		foreach ( $this->by_id as $id => $product ) {
			if ( self::is_parent_variable( $product['type'] ) ) {
				continue;
			}
			$hay_tokens = ! empty( $product['name_tokens'] ) ? $product['name_tokens'] : HPL_Normalizer::name_tokens( $product['name'] );
			$hay_set    = array_fill_keys( $hay_tokens, true );
			$hay_join   = implode( ' ', $hay_tokens );

			$ok = true;
			foreach ( $numbers as $num ) {
				if ( ! isset( $hay_set[ $num ] ) && false === mb_strpos( $hay_join, $num, 0, 'UTF-8' ) && false === mb_strpos( (string) $product['sku'], $num, 0, 'UTF-8' ) ) {
					$ok = false;
					break;
				}
			}
			if ( ! $ok ) {
				continue;
			}
			foreach ( $strong as $token ) {
				if ( preg_match( '/^\d+([.\/]\d+)?$/', $token ) ) {
					continue;
				}
				if ( ! isset( $hay_set[ $token ] ) && false === mb_strpos( $hay_join, $token, 0, 'UTF-8' ) ) {
					$ok = false;
					break;
				}
			}
			if ( ! $ok ) {
				continue;
			}

			$hit = 0;
			foreach ( $query_tokens as $token ) {
				if ( isset( $hay_set[ $token ] ) || false !== mb_strpos( $hay_join, $token, 0, 'UTF-8' ) ) {
					$hit++;
				}
			}
			$score = $hit / count( $query_tokens );
			if ( $score < 0.66 ) {
				continue;
			}
			if ( $score > $best_sc ) {
				$second  = $best_sc;
				$best_sc = $score;
				$best    = array( $id );
			} elseif ( abs( $score - $best_sc ) < 0.0001 ) {
				$best[] = $id;
			} elseif ( $score > $second ) {
				$second = $score;
			}
		}

		if ( 1 === count( $best ) && ( $best_sc - $second ) >= 0.08 ) {
			$product               = $this->by_id[ (string) $best[0] ];
			$product['matched_by'] = 'name';
			return $product;
		}
		if ( 1 === count( $best ) && $best_sc >= 0.8 ) {
			$product               = $this->by_id[ (string) $best[0] ];
			$product['matched_by'] = 'name';
			return $product;
		}

		return null;
	}

	/**
	 * Closest shop product for an unmatched row.
	 *
	 * @param array<string, mixed> $row Row.
	 * @return string
	 */
	public function suggest( array $row ) {
		$name = isset( $row['name'] ) ? HPL_Normalizer::name( $row['name'] ) : '';
		if ( '' === $name ) {
			return '';
		}
		$best_id  = '';
		$best_pct = 0;
		foreach ( $this->by_id as $id => $product ) {
			if ( self::is_parent_variable( $product['type'] ) || '' === $product['name_key'] ) {
				continue;
			}
			similar_text( $name, $product['name_key'], $pct );
			if ( $pct > $best_pct ) {
				$best_pct = $pct;
				$best_id  = $id;
			}
		}
		if ( $best_pct < 50 || '' === $best_id ) {
			return '';
		}
		$product = $this->by_id[ $best_id ];
		$label   = $product['name'];
		if ( '' !== $product['sku'] ) {
			$label .= ' — ' . $product['sku'];
		}
		return $label;
	}

	/**
	 * Rank site products against one file row using every code and name signal.
	 *
	 * @param array<string, mixed> $row   Row.
	 * @param int                  $limit Max results.
	 * @return array<int, array<string, mixed>>
	 */
	public function rank( array $row, $limit = 5 ) {
		$direct = $this->match( $row );
		$ranked = array();
		if ( $direct ) {
			$ranked[] = array(
				'product' => $direct,
				'score'   => 1,
				'reason'  => isset( $direct['matched_by'] ) ? $direct['matched_by'] : 'code',
			);
		}

		$tokens = self::row_tokens( $row );
		$ids    = array();
		foreach ( $tokens as $token ) {
			if ( isset( $this->inv[ $token ] ) ) {
				foreach ( $this->inv[ $token ] as $id => $flag ) {
					$ids[ $id ] = true;
				}
			}
		}
		if ( empty( $ids ) ) {
			foreach ( $this->by_id as $id => $product ) {
				if ( ! self::is_parent_variable( $product['type'] ) ) {
					$ids[ $id ] = true;
				}
			}
		}

		$query_compact = HPL_Normalizer::name_compact( isset( $row['name'] ) ? $row['name'] : '' );
		$query_tokens  = HPL_Normalizer::name_tokens( isset( $row['name'] ) ? $row['name'] : '' );
		$numbers       = array();
		foreach ( $query_tokens as $token ) {
			if ( preg_match( '/^\d+([.\/]\d+)?$/', $token ) ) {
				$numbers[] = $token;
			}
		}

		foreach ( array_keys( $ids ) as $id ) {
			if ( $direct && (string) $id === (string) $direct['id'] ) {
				continue;
			}
			if ( ! isset( $this->by_id[ $id ] ) ) {
				continue;
			}
			$product = $this->by_id[ $id ];
			if ( self::is_parent_variable( $product['type'] ) ) {
				continue;
			}
			$score = $this->score_product( $row, $product, $tokens, $query_compact, $query_tokens, $numbers );
			if ( $score < 0.42 ) {
				continue;
			}
			$ranked[] = array(
				'product' => $product,
				'score'   => $score,
				'reason'  => 'engine',
			);
		}

		usort(
			$ranked,
			static function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] > $b['score'] ) ? -1 : 1;
			}
		);

		return array_slice( $ranked, 0, $limit );
	}

	/**
	 * @param array<string, mixed> $row Row.
	 * @return string[]
	 */
	public static function row_tokens( array $row ) {
		$tokens = HPL_Normalizer::name_tokens( isset( $row['name'] ) ? $row['name'] : '' );
		$raws   = array();
		foreach ( array( 'sku', 'product_id', 'barcode' ) as $field ) {
			if ( ! empty( $row[ $field ] ) ) {
				$raws[] = $row[ $field ];
			}
		}
		if ( ! empty( $row['identifiers'] ) && is_array( $row['identifiers'] ) ) {
			$raws = array_merge( $raws, $row['identifiers'] );
		}
		foreach ( $raws as $raw ) {
			foreach ( HPL_Normalizer::code_keys( $raw ) as $key ) {
				$tokens[] = mb_strtolower( $key, 'UTF-8' );
			}
		}
		return array_values( array_unique( array_filter( $tokens ) ) );
	}

	/**
	 * @param array<string, mixed> $row           Row.
	 * @param array<string, mixed> $product       Product.
	 * @param string[]             $row_tokens    Tokens.
	 * @param string               $query_compact Compact name.
	 * @param string[]             $query_tokens  Name tokens.
	 * @param string[]             $numbers       Model numbers.
	 * @return float
	 */
	private function score_product( array $row, array $product, array $row_tokens, $query_compact, array $query_tokens, array $numbers ) {
		$score = 0.0;

		foreach ( array( isset( $row['sku'] ) ? $row['sku'] : '', isset( $row['product_id'] ) ? $row['product_id'] : '', isset( $row['barcode'] ) ? $row['barcode'] : '' ) as $code ) {
			if ( ! $code ) {
				continue;
			}
			foreach ( HPL_Normalizer::code_keys( $code ) as $key ) {
				if ( $key && ( $key === $product['sku'] || $key === $product['barcode'] || $key === $product['id'] ) ) {
					return 0.99;
				}
				if ( $key && isset( $this->by_code[ $key ] ) && (string) $this->by_code[ $key ] === (string) $product['id'] ) {
					return 0.97;
				}
			}
		}

		if ( $query_compact && ! empty( $product['name_compact'] ) ) {
			if ( $query_compact === $product['name_compact'] ) {
				$score = max( $score, 0.94 );
			} elseif ( false !== mb_strpos( $product['name_compact'], $query_compact, 0, 'UTF-8' ) || false !== mb_strpos( $query_compact, $product['name_compact'], 0, 'UTF-8' ) ) {
				$score = max( $score, 0.88 );
			}
		}

		$hay_tokens = ! empty( $product['name_tokens'] ) ? $product['name_tokens'] : array();
		$hay_set    = array_fill_keys( $hay_tokens, true );
		foreach ( $numbers as $num ) {
			if ( ! isset( $hay_set[ $num ] ) && false === strpos( (string) $product['sku'], (string) $num ) ) {
				return min( $score, 0.35 );
			}
		}

		if ( ! empty( $query_tokens ) ) {
			$hit = 0;
			foreach ( $query_tokens as $token ) {
				if ( isset( $hay_set[ $token ] ) ) {
					$hit++;
				}
			}
			$score = max( $score, $hit / count( $query_tokens ) );
		}

		if ( ! empty( $row_tokens ) && ! empty( $hay_tokens ) ) {
			$hit = 0;
			foreach ( $row_tokens as $token ) {
				if ( isset( $hay_set[ $token ] ) || isset( $this->inv[ $token ][ $product['id'] ] ) ) {
					$hit++;
				}
			}
			$score = max( $score, 0.75 * ( $hit / count( $row_tokens ) ) );
		}

		return (float) $score;
	}

	/**
	 * @param string $query Query.
	 * @param int    $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public function search( $query, $limit = 8 ) {
		$row = array(
			'sku'        => $query,
			'product_id' => $query,
			'barcode'    => $query,
			'name'       => $query,
		);
		return $this->rank( $row, $limit );
	}

	/**
	 * @return int
	 */
	public function count() {
		return count( $this->by_id );
	}
}
