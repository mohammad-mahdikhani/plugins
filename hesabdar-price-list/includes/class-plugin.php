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
		add_action( 'rest_api_init', array( $this, 'register_rest' ) );
		add_action( 'admin_post_nopriv_hpl_telegram_hook', array( $this, 'legacy_webhook' ) );
		add_action( 'admin_post_hpl_telegram_hook', array( $this, 'legacy_webhook' ) );
		add_action( 'hpl_telegram_poll', array( 'HPL_Telegram', 'cron_fetch' ) );
		if ( ! wp_next_scheduled( 'hpl_telegram_poll' ) && HPL_Telegram::token() && empty( self::settings()['telegram_webhook'] ) ) {
			wp_schedule_event( time() + 120, 'hourly', 'hpl_telegram_poll' );
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
		return array_merge( HPL_Updater::defaults(), HPL_Telegram::defaults(), $stored );
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
		foreach ( array( 'update_regular', 'update_sale', 'update_stock', 'skip_empty_price', 'zero_clears_sale', 'telegram_webhook' ) as $bool ) {
			if ( isset( $merged[ $bool ] ) ) {
				$merged[ $bool ] = (bool) $merged[ $bool ];
			}
		}
		if ( isset( $merged['telegram_channel'] ) ) {
			$merged['telegram_channel'] = HPL_Telegram::normalize_channel( $merged['telegram_channel'] );
		}
		update_option( 'hpl_settings', $merged );
	}

	public function register_rest() {
		register_rest_route(
			'hesabdar-price-list/v1',
			'/telegram',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_webhook( $request ) {
		$header = $request->get_header( 'x-telegram-bot-api-secret-token' );
		$query  = $request->get_param( 'secret' );
		$ok     = HPL_Telegram::handle_webhook( (string) $header, (string) $query, $request->get_body() );
		if ( ! $ok ) {
			return new WP_REST_Response( array( 'ok' => false ), 403 );
		}
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	public function legacy_webhook() {
		$header = isset( $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ) ) : '';
		$query  = isset( $_GET['secret'] ) ? sanitize_text_field( wp_unslash( $_GET['secret'] ) ) : '';
		$body   = file_get_contents( 'php://input' );
		$ok     = HPL_Telegram::handle_webhook( $header, $query, (string) $body );
		status_header( $ok ? 200 : 403 );
		echo $ok ? '{"ok":true}' : '{"ok":false}';
		exit;
	}
}
