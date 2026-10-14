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
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

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

		try {
			$token = HPL_Storage::put( $payload );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}

		wp_send_json_success(
			array(
				'token'    => $token,
				'filename' => $filename,
				'type'     => $parsed->type,
				'warning'  => $parsed->warning,
				'sheets'   => $sheets,
				'mapping'  => $mapping,
				'labels'   => HPL_Column_Mapper::labels(),
			)
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
		$catalog  = HPL_Catalog::from_woocommerce();
		$updater  = new HPL_Updater( $catalog, $settings );
		$plan     = $updater->plan( $rows );

		$payload['plan']       = $plan;
		$payload['mapping']    = $mapping;
		$payload['sheet']      = $sheet_idx;
		$payload['settings']   = $settings;
		$payload['import_id']  = 0;
		HPL_Storage::update( $token, $payload );

		$priority = array();
		$rest     = array();
		foreach ( $plan['items'] as $item ) {
			if ( in_array( $item['status'], array( 'missing', 'changed', 'invalid' ), true ) ) {
				$priority[] = $item;
			} else {
				$rest[] = $item;
			}
		}
		$preview_rows = array_slice( array_merge( $priority, $rest ), 0, 500 );
		wp_send_json_success(
			array(
				'token'     => $token,
				'stats'     => $plan['stats'],
				'items'     => $preview_rows,
				'truncated' => count( $plan['items'] ) > 500,
				'total'     => count( $plan['items'] ),
			)
		);
	}

	public function apply() {
		$this->guard();

		$token  = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;

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

		$result = $updater->apply_slice( $items, array( 'HPL_Updater', 'write_woocommerce' ), $offset, 40 );

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
