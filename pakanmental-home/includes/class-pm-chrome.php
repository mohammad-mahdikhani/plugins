<?php
/**
 * Elementor header/footer sections + full-page shell for WooCommerce routes.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Chrome {

	public const OPTION_HEADER_ID = 'pm_elementor_header_id';
	public const OPTION_FOOTER_ID = 'pm_elementor_footer_id';

	public static function init(): void {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
	}

	public static function header_section_id(): int {
		return (int) get_option( self::OPTION_HEADER_ID, 124 );
	}

	public static function footer_section_id(): int {
		return (int) get_option( self::OPTION_FOOTER_ID, 125 );
	}

	public static function render_header(): void {
		self::render_section( self::header_section_id() );
	}

	public static function render_footer(): void {
		self::render_section( self::footer_section_id() );
	}

	public static function render_section( int $post_id ): void {
		if ( $post_id < 1 || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
		if ( is_string( $content ) && '' !== $content ) {
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor HTML.
		}
	}

	public static function uses_wc_shell_template(): bool {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		if ( is_singular( 'product' ) ) {
			return true;
		}
		if ( is_product_taxonomy() ) {
			return true;
		}
		if ( function_exists( 'is_shop' ) && is_shop() ) {
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
