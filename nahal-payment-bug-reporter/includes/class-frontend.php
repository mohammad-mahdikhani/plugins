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
		$auto_watch = false;
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
			$auto_watch = true;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-pay' ) ) {
			$auto_watch = true;
		}
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
				'autoWatch'    => $auto_watch,
				'requireNote'  => ! empty( $settings['require_description'] ),
				'site'         => home_url( '/' ),
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
		echo '<header class="npbr-head"><h2 id="npbr-title">گزارش خطای پرداخت</h2><button type="button" class="npbr-x" data-npbr-close aria-label="بستن">×</button></header>';
		echo '<div class="npbr-body">';
		echo '<p class="npbr-lead">اگر فیلد پرداخت لود نشد همین مورد را بنویسید. اگر المان حیاتی صفحه لود نشود، گزارش فهرست‌شده همراه پرامپت ایجنت به‌صورت خودکار برای مدیر می‌رود.</p>';
		echo '<label class="npbr-label" for="npbr-note">شرح خطا <span>*</span></label>';
		echo '<textarea id="npbr-note" class="npbr-note" rows="5" maxlength="4000" placeholder="مثلاً: فیلدهای کارت دیجی‌پی خالی ماند / دکمه پرداخت کار نکرد / صفحه سفید شد"></textarea>';
		echo '<div class="npbr-grid">';
		echo '<label>نام (اختیاری)<input type="text" id="npbr-name" maxlength="80"></label>';
		echo '<label>موبایل (اختیاری)<input type="tel" id="npbr-phone" maxlength="20" dir="ltr"></label>';
		echo '</div>';
		echo '<div id="npbr-diag" class="npbr-diag"></div>';
		echo '<figure class="npbr-shot-wrap"><figcaption>پیش‌نمایش اسکرین‌شات</figcaption><img id="npbr-shot" alt="اسکرین‌شات صفحه" hidden><p id="npbr-shot-empty">هنوز گرفته نشده. با دانلود یا ارسال، اسکرین همین صفحه ذخیره می‌شود.</p></figure>';
		echo '<div id="npbr-downloads" class="npbr-downloads" hidden></div>';
		echo '<div id="npbr-prompt-wrap" class="npbr-prompt-wrap" hidden>';
		echo '<label class="npbr-label" for="npbr-prompt">پرامپت آماده برای ایجنت هوش مصنوعی</label>';
		echo '<textarea id="npbr-prompt" class="npbr-prompt" rows="10" readonly></textarea>';
		echo '<p class="npbr-prompt-hint">این متن را کپی کنید و همراه اسکرین همان صفحه برای ایجنت بفرستید تا خطا را روی همان URL بررسی کند.</p>';
		echo '<button type="button" class="npbr-btn npbr-btn-dark" data-npbr-copy-prompt>کپی پرامپت</button>';
		echo '</div>';
		echo '<iframe id="npbr-dl-iframe" name="npbr-dl-iframe" title="دانلود" hidden></iframe>';
		echo '<p id="npbr-status" class="npbr-status" role="status"></p>';
		echo '</div>';
		echo '<footer class="npbr-actions">';
		echo '<button type="button" class="npbr-btn npbr-btn-light" data-npbr-download>دانلود گزارش و اسکرین</button>';
		echo '<button type="button" class="npbr-btn npbr-btn-light" data-npbr-prompt>پرامپت ایجنت</button>';
		echo '<button type="button" class="npbr-btn npbr-btn-dark" data-npbr-send>ارسال برای مدیر و تلگرام</button>';
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

		if ( is_singular() ) {
			$post = get_post();
			if ( $post ) {
				$content = (string) $post->post_content;
				$slug    = (string) $post->post_name;
				if ( has_shortcode( $content, 'nahal_bug_report' ) ) {
					return true;
				}
				if ( has_shortcode( $content, 'woocommerce_checkout' ) ) {
					return true;
				}
				if ( preg_match( '/checkout|order-pay|pardakht|پرداخت/u', $slug ) ) {
					return true;
				}
			}
		}

		return (bool) apply_filters( 'npbr_should_load', false, $settings );
	}
}
