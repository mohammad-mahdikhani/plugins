<?php
/**
 * Fixed PM shell for every product and every product category (not per-item Elementor).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Chrome {

	public const OPTION_HEADER_ID = 'pm_elementor_header_id';
	public const OPTION_FOOTER_ID = 'pm_elementor_footer_id';

	public static function init(): void {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 100000 );
		add_filter( 'elementor/utils/is_post_type_support', array( __CLASS__, 'disable_elementor_for_products' ), 10, 2 );
		add_filter( 'elementor/utils/is_post_type_supported', array( __CLASS__, 'disable_elementor_for_products' ), 10, 2 );
	}

	/**
	 * @param bool   $supported Whether Elementor supports the post type.
	 * @param string $post_type Post type.
	 */
	public static function disable_elementor_for_products( $supported, $post_type ) {
		if ( 'product' === $post_type ) {
			return false;
		}
		return $supported;
	}

	public static function header_section_id(): int {
		return (int) get_option( self::OPTION_HEADER_ID, 124 );
	}

	public static function footer_section_id(): int {
		return (int) get_option( self::OPTION_FOOTER_ID, 125 );
	}

	public static function render_header(): void {
		include PM_HOME_PATH . 'templates/partials/pm-header.php';
	}

	public static function render_footer(): void {
		include PM_HOME_PATH . 'templates/partials/pm-footer.php';
	}

	public static function uses_wc_shell_template(): bool {
		if ( is_admin() || wp_doing_ajax() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
			return false;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		if ( is_singular( 'product' ) ) {
			return true;
		}
		if ( is_product_taxonomy() ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $template Path to template.
	 */
	public static function template_include( string $template ): string {
		if ( ! self::uses_wc_shell_template() ) {
			return $template;
		}
		$file = PM_HOME_PATH . 'templates/pm-wc-app.php';
		return file_exists( $file ) ? $file : $template;
	}
}
