<?php
/**
 * Admin screens.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPL_Admin {

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
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . HPL_BASENAME, array( $this, 'action_links' ) );
		add_action( 'admin_post_hpl_export_current', array( $this, 'export_current' ) );
		add_action( 'admin_post_hpl_sample_xlsx', array( $this, 'sample_xlsx' ) );
		add_action( 'admin_post_hpl_sample_csv', array( $this, 'sample_csv' ) );
		add_action( 'admin_post_hpl_save_settings', array( $this, 'save_settings' ) );
	}

	public function menu() {
		$cap = 'manage_woocommerce';
		add_menu_page(
			'لیست قیمت',
			'لیست قیمت',
			$cap,
			'hesabdar-price-list',
			array( $this, 'render' ),
			'dashicons-update',
			56
		);
		add_submenu_page(
			'hesabdar-price-list',
			'آپلود لیست قیمت',
			'آپلود و اعمال',
			$cap,
			'hesabdar-price-list',
			array( $this, 'render' )
		);
		add_submenu_page(
			'hesabdar-price-list',
			'تاریخچه قیمت',
			'تاریخچه و بازگشت',
			$cap,
			'hesabdar-price-list-history',
			array( $this, 'render_history' )
		);
		add_submenu_page(
			'hesabdar-price-list',
			'تنظیمات لیست قیمت',
			'تنظیمات',
			$cap,
			'hesabdar-price-list-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'hesabdar-price-list' ) ) {
			return;
		}
		wp_enqueue_style(
			'hpl-admin',
			HPL_URL . 'assets/admin/admin.css',
			array(),
			HPL_VERSION
		);
		wp_enqueue_style(
			'hpl-vazirmatn',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap',
			array(),
			null
		);
		wp_enqueue_script(
			'hpl-admin',
			HPL_URL . 'assets/admin/admin.js',
			array(),
			HPL_VERSION,
			true
		);
		wp_localize_script(
			'hpl-admin',
			'HPL',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'hpl_nonce' ),
				'i18n'  => array(
					'uploading'  => 'در حال بارگذاری فایل…',
					'parsing'    => 'در حال خواندن لیست قیمت…',
					'previewing' => 'در حال تطبیق با محصولات فروشگاه…',
					'applying'   => 'در حال اعمال قیمت‌های جدید…',
					'done'       => 'قیمت‌ها به‌روز شد.',
					'error'      => 'خطا رخ داد.',
					'drop'       => 'فایل اکسل یا PDF را اینجا رها کنید',
					'choose'     => 'انتخاب فایل',
				),
			)
		);
	}

	/**
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$url     = admin_url( 'admin.php?page=hesabdar-price-list' );
		$links[] = '<a href="' . esc_url( $url ) . '">آپلود لیست قیمت</a>';
		return $links;
	}

	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$settings = HPL_Plugin::settings();
		$labels   = HPL_Column_Mapper::labels();
		include HPL_DIR . 'templates/page-upload.php';
	}

	public function render_history() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$imports = HPL_History::list_imports();
		include HPL_DIR . 'templates/page-history.php';
	}

	public function render_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$settings = HPL_Plugin::settings();
		include HPL_DIR . 'templates/page-settings.php';
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'hpl_save_settings' );
		HPL_Plugin::save_settings(
			array(
				'update_regular'   => ! empty( $_POST['update_regular'] ),
				'update_sale'      => ! empty( $_POST['update_sale'] ),
				'update_stock'     => ! empty( $_POST['update_stock'] ),
				'skip_empty_price' => ! empty( $_POST['skip_empty_price'] ),
				'zero_clears_sale' => ! empty( $_POST['zero_clears_sale'] ),
				'match_order'      => isset( $_POST['match_order'] ) ? sanitize_text_field( wp_unslash( $_POST['match_order'] ) ) : 'sku,product_id,barcode,name',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=hesabdar-price-list-settings&updated=1' ) );
		exit;
	}

	public function export_current() {
		$this->guard_download();
		$catalog = HPL_Catalog::from_woocommerce();
		$bin     = HPL_Export::catalog_xlsx( $catalog );
		$this->send_file( 'woocommerce-prices.xlsx', $bin, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	}

	public function sample_xlsx() {
		$this->guard_download();
		$this->send_file( 'sample-price-list.xlsx', HPL_Export::sample_xlsx(), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
	}

	public function sample_csv() {
		$this->guard_download();
		$this->send_file( 'sample-price-list.csv', HPL_Export::sample_csv(), 'text/csv' );
	}

	private function guard_download() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'hpl_download' );
	}

	/**
	 * @param string $name Name.
	 * @param string $body Body.
	 * @param string $mime Mime.
	 */
	private function send_file( $name, $body, $mime ) {
		nocache_headers();
		header( 'Content-Type: ' . $mime );
		header( 'Content-Disposition: attachment; filename="' . $name . '"' );
		header( 'Content-Length: ' . strlen( $body ) );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}
