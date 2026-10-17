<?php
/**
 * Plugin bootstrap.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Plugin {

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
		NPBR_Install::maybe_upgrade();
		add_action( 'init', array( 'NPBR_Install', 'register_type' ), 5 );
		NPBR_Frontend::instance()->init();
		NPBR_Admin::instance()->init();
		NPBR_Ajax::instance()->init();

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
		echo '<div class="notice notice-warning"><p>افزونه <strong>گزارشگر خطای پرداخت نهال</strong> روی صفحات پرداخت ووکامرس کار می‌کند. ووکامرس را فعال کنید.</p></div>';
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'manager_email'      => '',
			'show_on_checkout'   => true,
			'show_on_order_pay'  => true,
			'show_on_cart'       => false,
			'show_on_thankyou'   => true,
			'show_near_payment'  => true,
			'show_floating'      => true,
			'require_description'=> true,
			'button_label'       => 'گزارش خطا',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$stored = get_option( 'npbr_settings', array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$merged = array_merge( self::defaults(), $stored );
		foreach ( array( 'show_on_checkout', 'show_on_order_pay', 'show_on_cart', 'show_on_thankyou', 'show_near_payment', 'show_floating', 'require_description' ) as $bool ) {
			$merged[ $bool ] = ! empty( $merged[ $bool ] );
		}
		$merged['manager_email'] = sanitize_email( (string) $merged['manager_email'] );
		$merged['button_label']  = $merged['button_label'] ? sanitize_text_field( (string) $merged['button_label'] ) : 'گزارش خطا';
		return $merged;
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 */
	public static function save_settings( array $settings ) {
		$current = self::settings();
		$merged  = array_merge( $current, $settings );
		foreach ( array( 'show_on_checkout', 'show_on_order_pay', 'show_on_cart', 'show_on_thankyou', 'show_near_payment', 'show_floating', 'require_description' ) as $bool ) {
			$merged[ $bool ] = ! empty( $merged[ $bool ] );
		}
		$merged['manager_email'] = sanitize_email( isset( $merged['manager_email'] ) ? (string) $merged['manager_email'] : '' );
		$label                   = isset( $merged['button_label'] ) ? sanitize_text_field( (string) $merged['button_label'] ) : 'گزارش خطا';
		$merged['button_label']  = $label ? $label : 'گزارش خطا';
		update_option( 'npbr_settings', $merged );
	}

	/**
	 * @return string
	 */
	public static function manager_email() {
		$settings = self::settings();
		if ( ! empty( $settings['manager_email'] ) && is_email( $settings['manager_email'] ) ) {
			return $settings['manager_email'];
		}
		return (string) get_option( 'admin_email' );
	}
}
