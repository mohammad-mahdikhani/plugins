<?php
/**
 * Admin tool to convert CSS files.
 *
 * @package NahalFontDisplaySwap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NFDS_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_nfds_convert', array( __CLASS__, 'handle' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_filter( 'plugin_action_links_' . NFDS_BASENAME, array( __CLASS__, 'links' ) );
	}

	public static function menu() {
		add_management_page(
			'فونت Display Swap',
			'فونت Display Swap',
			'manage_options',
			'nfds-swap',
			array( __CLASS__, 'page' )
		);
	}

	/**
	 * @param string[] $links Links.
	 * @return string[]
	 */
	public static function links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'tools.php?page=nfds-swap' ) ) . '">تبدیل فونت‌ها</a>';
		return $links;
	}

	public static function notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( empty( $_GET['page'] ) || 'nfds-swap' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$flash = get_transient( 'nfds_flash' );
		if ( ! is_array( $flash ) ) {
			return;
		}
		delete_transient( 'nfds_flash' );
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html(
			sprintf(
				'%d فایل بررسی شد، %d فایل عوض شد، %d قانون @font-face.',
				isset( $flash['files'] ) ? (int) $flash['files'] : 0,
				isset( $flash['changed'] ) ? (int) $flash['changed'] : 0,
				isset( $flash['faces'] ) ? (int) $flash['faces'] : 0
			)
		);
		echo '</p></div>';
	}

	public static function handle() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'forbidden' );
		}
		check_admin_referer( 'nfds_convert' );
		$theme     = ! empty( $_POST['convert_theme'] );
		$elementor = ! empty( $_POST['convert_elementor'] );
		$summary   = array(
			'files'   => 0,
			'faces'   => 0,
			'changed' => 0,
			'errors'  => array(),
		);
		if ( $elementor ) {
			$uploads = wp_upload_dir();
			if ( empty( $uploads['error'] ) ) {
				self::merge( $summary, NFDS_Files::convert_path( trailingslashit( $uploads['basedir'] ) . 'elementor', false ) );
			}
			NFDS_Plugin::convert_elementor_css();
		}
		if ( $theme ) {
			if ( function_exists( 'get_stylesheet_directory' ) ) {
				self::merge( $summary, NFDS_Files::convert_path( get_stylesheet_directory(), true ) );
			}
			if ( function_exists( 'get_template_directory' ) && get_template_directory() !== get_stylesheet_directory() ) {
				self::merge( $summary, NFDS_Files::convert_path( get_template_directory(), true ) );
			}
		}
		set_transient( 'nfds_flash', $summary, 60 );
		wp_safe_redirect( admin_url( 'tools.php?page=nfds-swap' ) );
		exit;
	}

	/**
	 * @param array<string, mixed> $into Acc.
	 * @param array<string, mixed> $add  Add.
	 */
	private static function merge( array &$into, array $add ) {
		$into['files']   += isset( $add['files'] ) ? (int) $add['files'] : 0;
		$into['faces']   += isset( $add['faces'] ) ? (int) $add['faces'] : 0;
		$into['changed'] += isset( $add['changed'] ) ? (int) $add['changed'] : 0;
		if ( ! empty( $add['errors'] ) && is_array( $add['errors'] ) ) {
			$into['errors'] = array_merge( $into['errors'], $add['errors'] );
		}
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap" dir="rtl">';
		echo '<h1>فونت Display Swap</h1>';
		echo '<p>این افزونه <code>font-display</code> را برای فونت‌های قالب، المنتور، گوگل‌فونت و هر @font-face دیگر اجباراً روی <strong>swap</strong> می‌گذارد تا متن موقع لود فونت مخفی نماند.</p>';
		echo '<ul style="list-style:disc;padding-right:20px;line-height:1.9">';
		echo '<li>خروجی HTML و لینک گوگل‌فونت در لحظه بازنویسی می‌شود.</li>';
		echo '<li>تنظیم «Google Fonts Load» المنتور روی swap قفل است.</li>';
		echo '<li>فونت سفارشی Elementor Pro هم swap می‌شود.</li>';
		echo '</ul>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="card" style="padding:16px;max-width:640px">';
		wp_nonce_field( 'nfds_convert' );
		echo '<input type="hidden" name="action" value="nfds_convert">';
		echo '<p><label><input type="checkbox" name="convert_elementor" value="1" checked> فایل‌های CSS کش المنتور در آپلود</label></p>';
		echo '<p><label><input type="checkbox" name="convert_theme" value="1"> فایل‌های CSS قالب (با پشتیبان .nfds-bak)</label></p>';
		echo '<p><button type="submit" class="button button-primary">تبدیل به font-display: swap</button></p>';
		echo '</form>';
		echo '<p class="description">بعد از تبدیل در المنتور: ابزارها ← بازسازی CSS و داده، بعد دوباره همین دکمه را بزنید.</p>';
		echo '</div>';
	}
}
