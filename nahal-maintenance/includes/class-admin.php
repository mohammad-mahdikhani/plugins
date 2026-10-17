<?php
/**
 * Admin settings for the 3D maintenance page.
 *
 * @package NahalMaintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NM_Admin {

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
		add_filter( 'plugin_action_links_' . NM_BASENAME, array( $this, 'action_links' ) );
		add_action( 'admin_post_nm_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_notices', array( $this, 'enabled_notice' ) );
		add_action( 'admin_head', array( $this, 'admin_bar_style' ) );
	}

	public function menu() {
		add_menu_page(
			'صفحه تعمیر سه‌بعدی',
			'صفحه تعمیر',
			'manage_options',
			'nm-maintenance',
			array( $this, 'render_settings' ),
			'dashicons-art',
			58
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		$hook = (string) $hook;
		if ( false === strpos( $hook, 'nm-maintenance' ) ) {
			return;
		}
		wp_enqueue_style( 'nm-admin', NM_URL . 'assets/admin/admin.css', array(), NM_VERSION );
	}

	/**
	 * @param array<int, string> $links Links.
	 * @return array<int, string>
	 */
	public function action_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=nm-maintenance' ) ) . '">تنظیمات</a>';
		$links[] = '<a href="' . esc_url( home_url( '/?nm_preview=1' ) ) . '" target="_blank" rel="noopener">پیش‌نمایش</a>';
		return $links;
	}

	public function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$settings = NM_Settings::get();
		include NM_DIR . 'templates/page-settings.php';
	}

	public function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'nm_save_settings' );

		$raw = array(
			'enabled'       => ! empty( $_POST['enabled'] ),
			'bypass_admins' => ! empty( $_POST['bypass_admins'] ),
			'brand'         => isset( $_POST['brand'] ) ? wp_unslash( $_POST['brand'] ) : '',
			'kicker'        => isset( $_POST['kicker'] ) ? wp_unslash( $_POST['kicker'] ) : '',
			'title'         => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
			'message'       => isset( $_POST['message'] ) ? wp_unslash( $_POST['message'] ) : '',
			'eta_text'      => isset( $_POST['eta_text'] ) ? wp_unslash( $_POST['eta_text'] ) : '',
			'phone'         => isset( $_POST['phone'] ) ? wp_unslash( $_POST['phone'] ) : '',
			'address'       => isset( $_POST['address'] ) ? wp_unslash( $_POST['address'] ) : '',
			'logo_url'      => isset( $_POST['logo_url'] ) ? wp_unslash( $_POST['logo_url'] ) : '',
			'bypass_key'    => isset( $_POST['bypass_key'] ) ? wp_unslash( $_POST['bypass_key'] ) : '',
			'retry_after'   => isset( $_POST['retry_after'] ) ? $_POST['retry_after'] : 3600,
		);

		update_option( 'nm_settings', NM_Settings::sanitize( $raw ) );
		wp_safe_redirect( admin_url( 'admin.php?page=nm-maintenance&saved=1' ) );
		exit;
	}

	public function enabled_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && isset( $screen->id ) && strpos( (string) $screen->id, 'nm-maintenance' ) !== false ) {
			return;
		}
		$settings = NM_Settings::get();
		if ( empty( $settings['enabled'] ) ) {
			return;
		}
		$url = admin_url( 'admin.php?page=nm-maintenance' );
		echo '<div class="notice notice-warning"><p>صفحه تعمیر سه‌بعدی <strong>روشن</strong> است. بازدیدکنندگان سایت را نمی‌بینند. <a href="' . esc_url( $url ) . '">تنظیمات</a></p></div>';
	}

	public function admin_bar_style() {
		echo '<style>#wpadminbar .nm-bar-on .ab-item{color:#fff4d6!important;background:#8a4b12!important}</style>';
	}
}
