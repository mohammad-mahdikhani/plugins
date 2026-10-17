<?php
/**
 * Checkout / payment-page reporter.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Frontend {

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
		add_action( 'wp_head', array( $this, 'collector' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_footer', array( $this, 'markup' ) );
		add_shortcode( 'nahal_bug_report', array( $this, 'shortcode' ) );
	}

	/**
	 * Tiny collector in head so failed scripts are recorded even if later JS dies.
	 */
	public function collector() {
		if ( is_admin() || ! $this->should_load() ) {
			return;
		}
		$path = NPBR_DIR . 'assets/frontend/collector.js';
		if ( ! is_readable( $path ) ) {
			return;
		}
		echo "<script id=\"npbr-collector\">\n";
		echo file_get_contents( $path ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin JS
		echo "\n</script>\n";
	}

	public function assets() {
		if ( ! $this->should_load() ) {
			return;
		}
		$settings   = NPBR_Plugin::settings();
		$page_kind  = $this->page_kind();
		$auto_watch = in_array( $page_kind, array( 'checkout', 'order-pay', 'cart', 'product', 'shop' ), true );
		if ( isset( $_GET['npbr_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$auto_watch = true;
		}
		wp_enqueue_style(
			'npbr-reporter',
			NPBR_URL . 'assets/frontend/reporter.css',
			array(),
			NPBR_VERSION
		);
		wp_enqueue_script(
			'npbr-html2canvas',
			NPBR_URL . 'assets/frontend/html2canvas.min.js',
			array(),
			'1.4.1',
			true
		);
		wp_enqueue_script(
			'npbr-reporter',
			NPBR_URL . 'assets/frontend/reporter.js',
			array(),
			NPBR_VERSION,
			true
		);
		wp_localize_script(
			'npbr-reporter',
			'NPBR_CFG',
			array(
				'ajax'         => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'npbr_report' ),
				'buttonLabel'  => $settings['button_label'],
				'showFloating' => ! empty( $settings['show_floating'] ),
				'showNearPay'  => false,
				'showNearCart' => ! empty( $settings['show_near_cart'] ),
				'autoWatch'    => $auto_watch,
				'pageKind'     => $page_kind,
				'requireNote'  => ! empty( $settings['require_description'] ),
				'site'         => home_url( '/' ),
				'customer'     => class_exists( 'NPBR_Customer', false ) ? NPBR_Customer::preview() : array(),
			)
		);
	}

	public function markup() {
		if ( ! $this->should_load() ) {
			return;
		}
		$label = NPBR_Plugin::settings()['button_label'];
		echo '<div id="npbr-root" class="npbr-root" hidden>';
		echo '<button type="button" class="npbr-fab" data-npbr-open>' . esc_html( $label ) . '</button>';
		echo '<div class="npbr-overlay" data-npbr-close hidden></div>';
		echo '<section class="npbr-modal" role="dialog" aria-modal="true" aria-labelledby="npbr-title" hidden>';
		echo '<header class="npbr-head"><h2 id="npbr-title">گزارش خطا در خرید و پرداخت</h2><button type="button" class="npbr-x" data-npbr-close aria-label="بستن">×</button></header>';
		echo '<div class="npbr-body">';
		echo '<p class="npbr-lead">اگر سبد خرید، دکمه خرید، فروشگاه، صفحه محصول یا درگاه پرداخت لود نشد همین مورد را بنویسید تا برای پشتیبانی ارسال شود.</p>';
		echo '<label class="npbr-label" for="npbr-note">شرح خطا <span>*</span></label>';
		echo '<textarea id="npbr-note" class="npbr-note" rows="5" maxlength="4000" placeholder="مثلاً: سبد خرید خالی ماند / دکمه خرید کار نکرد / فیلدهای کارت لود نشد"></textarea>';
		echo '<div class="npbr-grid npbr-grid-3">';
		echo '<label>نام (اختیاری)<input type="text" id="npbr-name" maxlength="80" autocomplete="given-name"></label>';
		echo '<label>نام خانوادگی (اختیاری)<input type="text" id="npbr-last" maxlength="80" autocomplete="family-name"></label>';
		echo '<label>موبایل (اختیاری)<input type="tel" id="npbr-phone" maxlength="20" dir="ltr" autocomplete="tel"></label>';
		echo '</div>';
		echo '<div id="npbr-diag" class="npbr-diag"></div>';
		echo '<p id="npbr-status" class="npbr-status" role="status"></p>';
		echo '</div>';
		echo '<footer class="npbr-actions">';
		echo '<button type="button" class="npbr-btn npbr-btn-dark npbr-btn-block" data-npbr-send>' . esc_html( $label ) . '</button>';
		echo '</footer>';
		echo '</section></div>';
	}

	/**
	 * @return string
	 */
	public function shortcode() {
		$label = NPBR_Plugin::settings()['button_label'];
		return '<button type="button" class="npbr-inline-btn" data-npbr-open>' . esc_html( $label ) . '</button>';
	}

	/**
	 * @return bool
	 */
	public function should_load() {
		if ( isset( $_GET['npbr_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return true;
		}
		$settings = NPBR_Plugin::settings();

		if ( ! empty( $settings['show_sitewide'] ) ) {
			return true;
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! empty( $settings['show_on_checkout'] ) ) {
			return true;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) && ! empty( $settings['show_on_order_pay'] ) ) {
			return true;
		}
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() && ! empty( $settings['show_on_thankyou'] ) ) {
			return true;
		}
		if ( function_exists( 'is_cart' ) && is_cart() && ! empty( $settings['show_on_cart'] ) ) {
			return true;
		}
		if ( function_exists( 'is_shop' ) && is_shop() && ! empty( $settings['show_on_shop'] ) ) {
			return true;
		}
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() && ! empty( $settings['show_on_shop'] ) ) {
			return true;
		}
		if ( function_exists( 'is_product' ) && is_product() && ! empty( $settings['show_on_product'] ) ) {
			return true;
		}

		if ( is_singular() ) {
			$post = get_post();
			if ( $post ) {
				$content = (string) $post->post_content;
				$slug    = (string) $post->post_name;
				if ( has_shortcode( $content, 'nahal_bug_report' ) ) {
					return true;
				}
				if ( has_shortcode( $content, 'woocommerce_checkout' ) || has_shortcode( $content, 'woocommerce_cart' ) ) {
					return true;
				}
				if ( preg_match( '/checkout|order-pay|pardakht|پرداخت|cart|sabad|فروشگاه|shop/u', $slug ) ) {
					return true;
				}
			}
		}

		return (bool) apply_filters( 'npbr_should_load', false, $settings );
	}

	/**
	 * @return string
	 */
	public function page_kind() {
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			return 'order-pay';
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
			return 'checkout';
		}
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			return 'cart';
		}
		if ( function_exists( 'is_product' ) && is_product() ) {
			return 'product';
		}
		if ( ( function_exists( 'is_shop' ) && is_shop() ) || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) ) {
			return 'shop';
		}
		if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			return 'thankyou';
		}
		return 'other';
	}
}
