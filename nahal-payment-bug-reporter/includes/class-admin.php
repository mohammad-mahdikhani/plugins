<?php
/**
 * Admin inbox and settings.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Admin {

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
		add_filter( 'plugin_action_links_' . NPBR_BASENAME, array( $this, 'action_links' ) );
		add_action( 'admin_post_npbr_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_npbr_test_telegram', array( $this, 'test_telegram' ) );
		add_action( 'admin_post_npbr_delete_report', array( $this, 'delete_report' ) );
	}

	public function menu() {
		$cap = $this->cap();
		add_menu_page(
			'گزارش خطای پرداخت',
			'خطای پرداخت',
			$cap,
			'npbr-reports',
			array( $this, 'render_reports' ),
			'dashicons-warning',
			57
		);
		add_submenu_page(
			'npbr-reports',
			'گزارش‌های دریافتی',
			'گزارش‌ها',
			$cap,
			'npbr-reports',
			array( $this, 'render_reports' )
		);
		add_submenu_page(
			'npbr-reports',
			'تنظیمات گزارشگر',
			'تنظیمات',
			$cap,
			'npbr-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'npbr-' ) ) {
			return;
		}
		wp_enqueue_style( 'npbr-admin', NPBR_URL . 'assets/admin/admin.css', array(), NPBR_VERSION );
	}

	/**
	 * @param array<int, string> $links Links.
	 * @return array<int, string>
	 */
	public function action_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=npbr-settings' ) ) . '">تنظیمات</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=npbr-reports' ) ) . '">گزارش‌ها</a>';
		return $links;
	}

	public function render_reports() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$single = isset( $_GET['report'] ) ? (int) $_GET['report'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $single ) {
			$post = get_post( $single );
			if ( ! $post || 'npbr_report' !== $post->post_type ) {
				echo '<div class="notice notice-error"><p>گزارش پیدا نشد.</p></div>';
				return;
			}
			include NPBR_DIR . 'templates/page-report.php';
			return;
		}
		$query = new WP_Query(
			array(
				'post_type'      => 'npbr_report',
				'post_status'    => array( 'private', 'publish', 'draft' ),
				'posts_per_page' => 40,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);
		include NPBR_DIR . 'templates/page-reports.php';
	}

	public function render_settings() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$settings = NPBR_Plugin::settings();
		$tg_test  = get_transient( 'npbr_tg_test_' . get_current_user_id() );
		if ( is_array( $tg_test ) ) {
			delete_transient( 'npbr_tg_test_' . get_current_user_id() );
		} else {
			$tg_test = null;
		}
		include NPBR_DIR . 'templates/page-settings.php';
	}

	public function save_settings() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_save_settings' );
		NPBR_Plugin::save_settings(
			array(
				'manager_email'       => isset( $_POST['manager_email'] ) ? sanitize_email( wp_unslash( $_POST['manager_email'] ) ) : '',
				'button_label'        => isset( $_POST['button_label'] ) ? sanitize_text_field( wp_unslash( $_POST['button_label'] ) ) : 'گزارش خطا',
				'show_on_checkout'    => ! empty( $_POST['show_on_checkout'] ),
				'show_on_order_pay'   => ! empty( $_POST['show_on_order_pay'] ),
				'show_on_cart'        => ! empty( $_POST['show_on_cart'] ),
				'show_on_thankyou'    => ! empty( $_POST['show_on_thankyou'] ),
				'show_near_payment'   => false,
				'show_floating'       => ! empty( $_POST['show_floating'] ),
				'require_description' => ! empty( $_POST['require_description'] ),
				'telegram_enabled'     => ! empty( $_POST['telegram_enabled'] ),
				'telegram_bot_token'   => isset( $_POST['telegram_bot_token'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_bot_token'] ) ) : '',
				'telegram_chat_id'     => isset( $_POST['telegram_chat_id'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_chat_id'] ) ) : '',
				'telegram_api_base'    => isset( $_POST['telegram_api_base'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_api_base'] ) ) : '',
				'telegram_http_proxy'  => isset( $_POST['telegram_http_proxy'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_http_proxy'] ) ) : '',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-settings&saved=1' ) );
		exit;
	}

	public function test_telegram() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_test_telegram' );
		$result = class_exists( 'NPBR_Telegram', false ) ? NPBR_Telegram::test() : array(
			'ok'      => false,
			'message' => 'کلاس تلگرام بارگذاری نشد.',
		);
		set_transient( 'npbr_tg_test_' . get_current_user_id(), $result, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-settings' ) );
		exit;
	}

	public function delete_report() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_delete_report' );
		$id = isset( $_POST['report_id'] ) ? (int) $_POST['report_id'] : 0;
		if ( $id ) {
			$shot = (int) get_post_meta( $id, '_npbr_screenshot_id', true );
			if ( $shot ) {
				wp_delete_attachment( $shot, true );
			}
			wp_delete_post( $id, true );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-reports&deleted=1' ) );
		exit;
	}

	/**
	 * @return string
	 */
	private function cap() {
		return current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';
	}
}
