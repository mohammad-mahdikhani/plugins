<?php
/**
 * Boot the swap enforcer.
 *
 * @package NahalFontDisplaySwap
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NFDS_Plugin {

	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'boot' ), 1 );
	}

	public static function boot() {
		// Elementor Google Fonts Load dropdown — force swap even if set to auto/block.
		add_filter( 'pre_option_elementor_font_display', array( __CLASS__, 'swap_option' ), 10, 3 );
		add_filter( 'option_elementor_font_display', array( __CLASS__, 'swap_value' ) );
		add_filter( 'default_option_elementor_font_display', array( __CLASS__, 'swap_value' ) );

		// Elementor Pro custom / Adobe / TypeKit faces.
		add_filter( 'elementor_pro/custom_fonts/font_display', array( __CLASS__, 'swap_value' ), 999, 3 );
		add_filter( 'elementor_pro/fonts/font_display', array( __CLASS__, 'swap_value' ), 999, 3 );
		add_filter( 'elementor/frontend/google_fonts/display', array( __CLASS__, 'swap_value' ), 999 );
		add_filter( 'elementor/fonts/google_fonts/display', array( __CLASS__, 'swap_value' ), 999 );

		add_filter( 'style_loader_src', array( __CLASS__, 'filter_src' ), 999 );
		add_filter( 'script_loader_src', array( __CLASS__, 'filter_src' ), 999 );
		add_filter( 'wp_get_custom_css', array( 'NFDS_Rewriter', 'css' ), 999 );
		add_filter( 'safecss', array( 'NFDS_Rewriter', 'css' ), 999 );

		NFDS_Overflow::init();
		add_action( 'template_redirect', array( __CLASS__, 'buffer' ), 0 );
		add_action( 'elementor/core/files/clear_cache', array( __CLASS__, 'convert_elementor_css' ) );
		add_action( 'shutdown', array( __CLASS__, 'maybe_convert_elementor_css' ), 20 );

		if ( is_admin() ) {
			NFDS_Admin::init();
		}
	}

	/**
	 * @return string
	 */
	public static function swap_value() {
		return 'swap';
	}

	/**
	 * @return string
	 */
	public static function swap_option() {
		return 'swap';
	}

	/**
	 * @param string $src URL.
	 * @return string
	 */
	public static function filter_src( $src ) {
		return NFDS_Rewriter::font_css_url( (string) $src );
	}

	public static function buffer() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}
		ob_start( array( 'NFDS_Rewriter', 'html' ) );
	}

	public static function convert_elementor_css() {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return;
		}
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return;
		}
		$dir = trailingslashit( $uploads['basedir'] ) . 'elementor';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		NFDS_Files::convert_path( $dir, false );
		update_option( 'nfds_last_elementor_convert', time(), false );
	}

	public static function maybe_convert_elementor_css() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$last = (int) get_option( 'nfds_last_elementor_convert', 0 );
		if ( $last && ( time() - $last ) < 300 ) {
			return;
		}
		self::convert_elementor_css();
	}

	public static function activate() {
		if ( function_exists( 'update_option' ) ) {
			update_option( 'elementor_font_display', 'swap' );
		}
	}
}
