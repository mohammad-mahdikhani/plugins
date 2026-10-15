<?php
/**
 * AJAX endpoints for upload / preview / apply / rollback.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPL_Ajax {

	/** @var self|null */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'wp_ajax_hpl_upload', array( $this, 'upload' ) );
		add_action( 'wp_ajax_hpl_preview', array( $this, 'preview' ) );
		add_action( 'wp_ajax_hpl_apply', array( $this, 'apply' ) );
		add_action( 'wp_ajax_hpl_rollback', array( $this, 'rollback' ) );
		add_action( 'wp_ajax_hpl_history', array( $this, 'history' ) );
		add_action( 'wp_ajax_hpl_screen_action', array( $this, 'screen_action' ) );
		add_action( 'wp_ajax_hpl_search_products', array( $this, 'search_products' ) );
		add_action( 'wp_ajax_hpl_telegram_fetch', array( $this, 'telegram_fetch' ) );
		add_action( 'wp_ajax_hpl_telegram_open', array( $this, 'telegram_open' ) );
	}

	private function guard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی ندارید.' ), 403 );
		}
		check_ajax_referer( 'hpl_nonce', 'nonce' );
		if ( ! class_exists( 'WooCommerce' ) ) {
			wp_send_json_error( array( 'message' => 'ووکامرس فعال نیست.' ), 400 );
		}
	}

	public function upload() {
		$this->guard();

		if ( empty( $_FILES['file'] ) || ! isset( $_FILES['file']['tmp_name'] ) ) {
			wp_send_json_error( array( 'message' => 'فایلی انتخاب نشده است.' ) );
		}

		$file = $_FILES['file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $file['error'] ) ) {
			wp_send_json_error( array( 'message' => 'آپلود فایل ناموفق بود.' ) );
		}

		$size_limit = 20 * 1024 * 1024;
		if ( (int) $file['size'] > $size_limit ) {
			wp_send_json_error( array( 'message' => 'حجم فایل نباید بیشتر از ۲۰ مگابایت باشد.' ) );
		}

		$filename = sanitize_file_name( $file['name'] );
		$type     = HPL_Parser::detect_type( $file['tmp_name'], $filename );
		if ( '' === $type ) {
			wp_send_json_error( array( 'message' => 'فقط فایل‌های xlsx، xls، csv و pdf پذیرفته می‌شوند.' ) );
		}

		try {
			$parsed = HPL_Parser::parse( $file['tmp_name'], $filename );
			wp_send_json_success( $this->session_from_parsed( $parsed, $filename ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public function telegram_fetch() {
		$this->guard();
		if ( ! HPL_Telegram::configured() ) {
			wp_send_json_error( array( 'message' => 'ابتدا در تنظیمات، توکن ربات و نام کانال تلگرام را وارد کنید.' ) );
		}
		try {
			HPL_Telegram::fetch();
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
		$latest = HPL_Telegram::latest();
		wp_send_json_success(
			array(
				'posts'  => HPL_Telegram::inbox_preview(),
				'latest' => $latest ? $latest['id'] : '',
			)
		);
	}

	public function telegram_open() {
		$this->guard();
		$id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
		try {
			if ( '' === $id || 'all' === $id ) {
				HPL_Telegram::fetch();
				$parsed = HPL_Telegram::open_all();
				wp_send_json_success( $this->session_from_parsed( $parsed, 'پست‌های کانال تلگرام' ) );
			}
			$parsed = HPL_Telegram::open( $id );
			$item   = HPL_Telegram::find( $id );
			$name   = $parsed->filename;
			if ( $item && ! empty( $item['filename'] ) ) {
				$name = $item['filename'];
			} elseif ( $item && ! empty( $item['preview'] ) ) {
				$name = 'تلگرام: ' . $item['preview'];
			}
			wp_send_json_success( $this->session_from_parsed( $parsed, $name ) );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * @param HPL_Parsed_File $parsed   Parsed file.
	 * @param string          $filename Filename.
	 * @return array<string, mixed>
	 */
	private function session_from_parsed( HPL_Parsed_File $parsed, $filename ) {
		$sheets  = array();
		$mapping = array();
		foreach ( $parsed->sheets as $index => $sheet ) {
			$detected = HPL_Column_Mapper::detect( $sheet->headers );
			$sheets[] = $sheet->to_preview() + array( 'index' => $index, 'mapping' => $detected );
			if ( 0 === $index ) {
				$mapping = $detected;
			}
		}

		$payload = array(
			'filename' => $filename,
			'type'     => $parsed->type,
			'warning'  => $parsed->warning,
			'sheets'   => array(),
		);
		foreach ( $parsed->sheets as $sheet ) {
			$payload['sheets'][] = array(
				'name'        => $sheet->name,
				'headers'     => $sheet->headers,
				'rows'        => $sheet->rows,
				'header_line' => $sheet->header_line,
			);
		}

		$token = HPL_Storage::put( $payload );
		return array(
			'token'    => $token,
			'filename' => $filename,
			'type'     => $parsed->type,
			'warning'  => $parsed->warning,
			'sheets'   => $sheets,
			'mapping'  => $mapping,
			'labels'   => HPL_Column_Mapper::labels(),
		);
	}

	public function preview() {
		$this->guard();

		$token      = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$sheet_idx  = isset( $_POST['sheet'] ) ? (int) $_POST['sheet'] : 0;
		$mapping    = $this->read_mapping();

		try {
			$payload = HPL_Storage::get( $token );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		if ( empty( $payload['sheets'][ $sheet_idx ] ) ) {
			wp_send_json_error( array( 'message' => 'برگه انتخاب‌شده پیدا نشد.' ) );
		}

		$raw   = $payload['sheets'][ $sheet_idx ];
		$sheet = new HPL_Sheet( $raw['name'], $raw['headers'], $raw['rows'], isset( $raw['header_line'] ) ? (int) $raw['header_line'] : 1 );
		$rows  = HPL_Column_Mapper::apply( $sheet, $mapping );

		$settings = HPL_Plugin::settings();
		$settings = $this->overlay_settings( $settings );
		if ( isset( $payload['type'] ) && 'telegram' === $payload['type'] ) {
			$settings['match_order'] = array( 'name', 'sku', 'barcode', 'product_id' );
		}
		$catalog  = HPL_Catalog::from_woocommerce();
		$updater  = new HPL_Updater( $catalog, $settings );
		$screen   = HPL_Screener::run( $rows, $catalog, $updater );

		$payload['screen']    = $screen;
		$payload['plan']      = array(
			'items' => $screen['plan_items'],
			'stats' => $screen['stats'],
		);
		$payload['rows']      = $rows;
		$payload['mapping']   = $mapping;
		$payload['sheet']     = $sheet_idx;
		$payload['settings']  = $settings;
		$payload['import_id'] = 0;
		HPL_Storage::update( $token, $payload );

		wp_send_json_success( self::screen_response( $token, $screen ) );
	}

	public function apply() {
		$this->guard();

		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;
		if ( isset( $_POST['publish_new'] ) ) {
			try {
				$tmp = HPL_Storage::get( $token );
				$tmp['publish_new'] = '1' === (string) wp_unslash( $_POST['publish_new'] );
				HPL_Storage::update( $token, $tmp );
			} catch ( Exception $e ) {
				unset( $e );
			}
		}

		try {
			$payload = HPL_Storage::get( $token );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		if ( empty( $payload['plan']['items'] ) ) {
			wp_send_json_error( array( 'message' => 'پیش‌نمایش پیدا نشد. دوباره فایل را بررسی کنید.' ) );
		}

		$items    = $payload['plan']['items'];
		$settings = isset( $payload['settings'] ) ? $payload['settings'] : HPL_Plugin::settings();
		$catalog  = HPL_Catalog::from_woocommerce();
		$updater  = new HPL_Updater( $catalog, $settings );

		if ( 0 === $offset ) {
			$payload['import_id'] = HPL_History::create_import(
				array(
					'user_id'   => get_current_user_id(),
					'filename'  => isset( $payload['filename'] ) ? $payload['filename'] : '',
					'file_type' => isset( $payload['type'] ) ? $payload['type'] : '',
					'stats'     => $payload['plan']['stats'],
				)
			);
			HPL_History::save_changes( $payload['import_id'], $items );
			HPL_Storage::update( $token, $payload );
		}

		$publish = ! empty( $payload['publish_new'] );
		$writer  = static function ( $item ) use ( $publish ) {
			if ( isset( $item['status'] ) && 'create' === $item['status'] ) {
				return HPL_Screener::create_woocommerce( $item, (bool) $publish );
			}
			return HPL_Updater::write_woocommerce( $item );
		};
		$result = $updater->apply_slice( $items, $writer, $offset, 40 );

		if ( $result['done'] ) {
			HPL_History::mark_applied( (int) $payload['import_id'], $payload['plan']['stats'] + array( 'applied_offset' => $result['offset'] ) );
			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients();
			}
		}

		wp_send_json_success(
			array(
				'import_id' => isset( $payload['import_id'] ) ? (int) $payload['import_id'] : 0,
				'processed' => $result['processed'],
				'updated'   => $result['updated'],
				'failed'    => $result['failed'],
				'done'      => $result['done'],
				'offset'    => $result['offset'],
				'total'     => $result['total'],
				'stats'     => $payload['plan']['stats'],
			)
		);
	}

	public function rollback() {
		$this->guard();
		$import_id = isset( $_POST['import_id'] ) ? (int) $_POST['import_id'] : 0;
		try {
			$restored = HPL_History::rollback( $import_id );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
		wp_send_json_success( array( 'restored' => $restored ) );
	}

	public function history() {
		$this->guard();
		wp_send_json_success( array( 'imports' => HPL_History::list_imports() ) );
	}

	public function screen_action() {
		$this->guard();
		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$index  = isset( $_POST['index'] ) ? (int) $_POST['index'] : -1;
		$action = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$pid    = isset( $_POST['product_id'] ) ? (int) $_POST['product_id'] : 0;

		try {
			$payload = HPL_Storage::get( $token );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		if ( empty( $payload['screen'] ) || empty( $payload['rows'] ) ) {
			wp_send_json_error( array( 'message' => 'غربالگری پیدا نشد. دوباره فایل را بررسی کنید.' ) );
		}

		$settings = isset( $payload['settings'] ) ? $payload['settings'] : HPL_Plugin::settings();
		$catalog  = HPL_Catalog::from_woocommerce();
		$updater  = new HPL_Updater( $catalog, $settings );
		$screen   = $payload['screen'];

		if ( 'accept_all' === $action ) {
			foreach ( $screen['review'] as $entry ) {
				if ( empty( $entry['candidates'][0] ) ) {
					continue;
				}
				$product = $catalog->by_id[ (string) $entry['candidates'][0]['id'] ];
				if ( ! $product ) {
					continue;
				}
				$item                  = HPL_Screener::link( $entry['row'], $product, $updater, $entry['index'] );
				$entry['bucket']       = HPL_Screener::ON_SITE;
				$entry['item']         = $item;
				$screen['on_site'][]   = $entry;
			}
			$screen['review'] = array();
		} elseif ( 'create_all' === $action ) {
			$keep = array();
			foreach ( $screen['not_on_site'] as $entry ) {
				$item                = HPL_Screener::prepare_create( $entry['row'], $entry['index'] );
				$entry['item']       = $item;
				$entry['bucket']     = HPL_Screener::NOT_ON_SITE;
				$screen['on_site'][] = $entry;
			}
			$screen['not_on_site'] = $keep;
		} elseif ( 'create' === $action ) {
			$screen = self::move_entry( $screen, $index, function ( $entry ) {
				$entry['item']   = HPL_Screener::prepare_create( $entry['row'], $entry['index'] );
				$entry['bucket'] = HPL_Screener::NOT_ON_SITE;
				return $entry;
			}, 'on_site' );
		} elseif ( 'accept' === $action || 'link' === $action ) {
			$product = isset( $catalog->by_id[ (string) $pid ] ) ? $catalog->by_id[ (string) $pid ] : null;
			if ( ! $product ) {
				wp_send_json_error( array( 'message' => 'محصول فروشگاه پیدا نشد.' ) );
			}
			$screen = self::move_entry(
				$screen,
				$index,
				static function ( $entry ) use ( $product, $updater ) {
					$entry['item']   = HPL_Screener::link( $entry['row'], $product, $updater, $entry['index'] );
					$entry['bucket'] = HPL_Screener::ON_SITE;
					return $entry;
				},
				'on_site'
			);
		} elseif ( 'skip' === $action ) {
			$screen = self::remove_entry( $screen, $index );
		} else {
			wp_send_json_error( array( 'message' => 'عملیات نامعتبر است.' ) );
		}

		$plan_items = array();
		foreach ( $screen['on_site'] as $entry ) {
			$plan_items[] = $entry['item'];
		}
		$screen['plan_items']           = $plan_items;
		$screen['stats']['on_site']     = count( $screen['on_site'] );
		$screen['stats']['review']      = count( $screen['review'] );
		$screen['stats']['not_on_site'] = count( $screen['not_on_site'] );

		$payload['screen'] = $screen;
		$payload['plan']   = array(
			'items' => $plan_items,
			'stats' => $screen['stats'],
		);
		HPL_Storage::update( $token, $payload );
		wp_send_json_success( self::screen_response( $token, $screen ) );
	}

	public function search_products() {
		$this->guard();
		$query = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
		if ( '' === $query ) {
			wp_send_json_success( array( 'results' => array() ) );
		}
		$catalog = HPL_Catalog::from_woocommerce();
		$hits    = $catalog->search( $query, 8 );
		$out     = array();
		foreach ( $hits as $hit ) {
			$p     = $hit['product'];
			$out[] = array(
				'id'    => $p['id'],
				'name'  => $p['name'],
				'sku'   => $p['sku'],
				'score' => $hit['score'],
			);
		}
		wp_send_json_success( array( 'results' => $out ) );
	}

	/**
	 * @param string               $token  Token.
	 * @param array<string, mixed> $screen Screen.
	 * @return array<string, mixed>
	 */
	private static function screen_response( $token, array $screen ) {
		$clip = static function ( $list ) {
			$out = array();
			foreach ( array_slice( $list, 0, 250 ) as $entry ) {
				$item = $entry['item'];
				$item['candidates'] = isset( $entry['candidates'] ) ? array_slice( $entry['candidates'], 0, 3 ) : array();
				$out[] = $item;
			}
			return $out;
		};
		return array(
			'token'       => $token,
			'stats'       => $screen['stats'],
			'on_site'     => $clip( $screen['on_site'] ),
			'review'      => $clip( $screen['review'] ),
			'not_on_site' => $clip( $screen['not_on_site'] ),
			'not_in_file' => array_slice( $screen['not_in_file'], 0, 80 ),
			'ready'       => count( $screen['plan_items'] ),
		);
	}

	/**
	 * @param array<string, mixed> $screen Screen.
	 * @param int                  $index  Index.
	 * @param callable             $mut    Mutator.
	 * @param string               $dest   Destination bucket.
	 * @return array<string, mixed>
	 */
	private static function move_entry( array $screen, $index, $mut, $dest ) {
		$moved = null;
		foreach ( array( 'review', 'not_on_site', 'on_site' ) as $bucket ) {
			$keep = array();
			foreach ( $screen[ $bucket ] as $entry ) {
				if ( (int) $entry['index'] === (int) $index ) {
					$moved = call_user_func( $mut, $entry );
				} else {
					$keep[] = $entry;
				}
			}
			$screen[ $bucket ] = $keep;
		}
		if ( $moved ) {
			$screen[ $dest ][] = $moved;
		}
		return $screen;
	}

	/**
	 * @param array<string, mixed> $screen Screen.
	 * @param int                  $index  Index.
	 * @return array<string, mixed>
	 */
	private static function remove_entry( array $screen, $index ) {
		foreach ( array( 'review', 'not_on_site', 'on_site' ) as $bucket ) {
			$keep = array();
			foreach ( $screen[ $bucket ] as $entry ) {
				if ( (int) $entry['index'] !== (int) $index ) {
					$keep[] = $entry;
				}
			}
			$screen[ $bucket ] = $keep;
		}
		return $screen;
	}

	/**
	 * @return array<string, int>
	 */
	private function read_mapping() {
		$mapping = array();
		$raw     = isset( $_POST['mapping'] ) ? wp_unslash( $_POST['mapping'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			$raw     = is_array( $decoded ) ? $decoded : array();
		}
		foreach ( HPL_Column_Mapper::FIELDS as $field ) {
			$mapping[ $field ] = isset( $raw[ $field ] ) ? (int) $raw[ $field ] : -1;
		}
		return $mapping;
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	private function overlay_settings( array $settings ) {
		foreach ( array( 'update_regular', 'update_sale', 'update_stock', 'skip_empty_price', 'zero_clears_sale' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$settings[ $key ] = '1' === (string) wp_unslash( $_POST[ $key ] ) || 'true' === (string) wp_unslash( $_POST[ $key ] );
			}
		}
		return $settings;
	}
}
