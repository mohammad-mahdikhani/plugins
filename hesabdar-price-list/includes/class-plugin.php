<?php
/**
 * Plugin bootstrap.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPL_Plugin {

	/** @var self|null */
	private static $instance = null;

	/** @var bool */
	private $wc_active = false;

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
		$this->wc_active = class_exists( 'WooCommerce' );
		HPL_Install::maybe_upgrade();
		HPL_Admin::instance()->init();
		HPL_Ajax::instance()->init();

		if ( ! $this->wc_active ) {
			add_action( 'admin_notices', array( $this, 'missing_wc_notice' ) );
		}
	}

	/**
	 * @return bool
	 */
	public function wc_active() {
		return $this->wc_active;
	}

	public function missing_wc_notice() {
		echo '<div class="notice notice-error"><p>افزونه <strong>لیست قیمت حسابدار</strong> برای به‌روزرسانی قیمت‌ها به ووکامرس فعال نیاز دارد.</p></div>';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$stored = get_option( 'hpl_settings', array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( HPL_Updater::defaults(), $stored );
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 */
	public static function save_settings( array $settings ) {
		$current = self::settings();
		$merged  = array_merge( $current, $settings );
		if ( isset( $merged['match_order'] ) && is_string( $merged['match_order'] ) ) {
			$merged['match_order'] = array_values( array_filter( array_map( 'trim', explode( ',', $merged['match_order'] ) ) ) );
		}
		foreach ( array( 'update_regular', 'update_sale', 'update_stock', 'skip_empty_price', 'zero_clears_sale' ) as $bool ) {
			if ( isset( $merged[ $bool ] ) ) {
				$merged[ $bool ] = (bool) $merged[ $bool ];
			}
		}
		update_option( 'hpl_settings', $merged );
	}
}
