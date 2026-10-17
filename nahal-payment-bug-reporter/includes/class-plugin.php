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
		if ( class_exists( 'NPBR_Stats', false ) ) {
			NPBR_Stats::init_cron();
		}

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
			'manager_email'       => '',
			'show_on_checkout'    => true,
			'show_on_order_pay'   => true,
			'show_on_cart'        => true,
			'show_on_shop'        => true,
			'show_on_product'     => true,
			'show_on_thankyou'    => true,
			'show_sitewide'       => true,
			'show_near_payment'   => false,
			'show_near_cart'      => true,
			'show_floating'       => true,
			'require_description' => true,
			'button_label'        => 'گزارش خطا',
			'telegram_enabled'     => false,
			'telegram_bot_token'   => '',
			'telegram_chat_id'     => '',
			'telegram_api_base'    => '',
			'telegram_http_proxy'  => '',
			'digest_email'         => true,
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
		foreach ( array( 'show_on_checkout', 'show_on_order_pay', 'show_on_cart', 'show_on_shop', 'show_on_product', 'show_on_thankyou', 'show_sitewide', 'show_near_payment', 'show_near_cart', 'show_floating', 'require_description', 'telegram_enabled', 'digest_email' ) as $bool ) {
			$merged[ $bool ] = ! empty( $merged[ $bool ] );
		}
		$merged['manager_email']      = sanitize_email( (string) $merged['manager_email'] );
		$merged['button_label']       = $merged['button_label'] ? sanitize_text_field( (string) $merged['button_label'] ) : 'گزارش خطا';
		$merged['telegram_bot_token']  = self::sanitize_token( isset( $merged['telegram_bot_token'] ) ? $merged['telegram_bot_token'] : '' );
		$merged['telegram_chat_id']    = self::sanitize_chat_id( isset( $merged['telegram_chat_id'] ) ? $merged['telegram_chat_id'] : '' );
		$merged['telegram_api_base']   = self::sanitize_api_base( isset( $merged['telegram_api_base'] ) ? $merged['telegram_api_base'] : '' );
		$merged['telegram_http_proxy'] = self::sanitize_http_proxy( isset( $merged['telegram_http_proxy'] ) ? $merged['telegram_http_proxy'] : '' );
		$merged['show_near_payment']   = false;
		return $merged;
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 */
	public static function save_settings( array $settings ) {
		$current = self::settings();
		$merged  = array_merge( $current, $settings );
		foreach ( array( 'show_on_checkout', 'show_on_order_pay', 'show_on_cart', 'show_on_shop', 'show_on_product', 'show_on_thankyou', 'show_sitewide', 'show_near_payment', 'show_near_cart', 'show_floating', 'require_description', 'telegram_enabled', 'digest_email' ) as $bool ) {
			$merged[ $bool ] = ! empty( $merged[ $bool ] );
		}
		$merged['manager_email'] = sanitize_email( isset( $merged['manager_email'] ) ? (string) $merged['manager_email'] : '' );
		$label                   = isset( $merged['button_label'] ) ? sanitize_text_field( (string) $merged['button_label'] ) : 'گزارش خطا';
		$merged['button_label']  = $label ? $label : 'گزارش خطا';
		if ( isset( $settings['telegram_bot_token'] ) && '' === trim( (string) $settings['telegram_bot_token'] ) ) {
			$merged['telegram_bot_token'] = $current['telegram_bot_token'];
		} else {
			$merged['telegram_bot_token'] = self::sanitize_token( isset( $merged['telegram_bot_token'] ) ? $merged['telegram_bot_token'] : '' );
		}
		$merged['telegram_chat_id']    = self::sanitize_chat_id( isset( $merged['telegram_chat_id'] ) ? $merged['telegram_chat_id'] : '' );
		$merged['telegram_api_base']   = self::sanitize_api_base( isset( $merged['telegram_api_base'] ) ? $merged['telegram_api_base'] : '' );
		$merged['telegram_http_proxy'] = self::sanitize_http_proxy( isset( $merged['telegram_http_proxy'] ) ? $merged['telegram_http_proxy'] : '' );
		$merged['show_near_payment']   = false;
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

	/**
	 * @param mixed $token Token.
	 * @return string
	 */
	public static function sanitize_token( $token ) {
		return preg_replace( '/[^A-Za-z0-9:_-]/', '', (string) $token );
	}

	/**
	 * @param mixed $chat Chat id.
	 * @return string
	 */
	public static function sanitize_chat_id( $chat ) {
		return preg_replace( '/[^A-Za-z0-9_\-@]/', '', (string) $chat );
	}

	/**
	 * @param mixed $url API base.
	 * @return string
	 */
	public static function sanitize_api_base( $url ) {
		if ( class_exists( 'NPBR_Telegram', false ) ) {
			return NPBR_Telegram::sanitize_api_base( $url );
		}
		return '';
	}

	/**
	 * @param mixed $proxy HTTP proxy.
	 * @return string
	 */
	public static function sanitize_http_proxy( $proxy ) {
		if ( class_exists( 'NPBR_Telegram', false ) ) {
			return NPBR_Telegram::sanitize_http_proxy( $proxy );
		}
		return '';
	}
}
