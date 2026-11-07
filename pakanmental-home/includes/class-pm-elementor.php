<?php
/**
 * Elementor integration (shortcodes in HTML widgets, WC page helpers).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Elementor {

	public static function init(): void {
		add_filter( 'elementor/widget/render_content', array( __CLASS__, 'html_widget_shortcodes' ), 12, 2 );
		add_filter( 'elementor/utils/get_public_post_types', array( __CLASS__, 'remove_product_type' ) );
	}

	/**
	 * Products use the fixed PM PHP template, not a per-SKU Elementor canvas.
	 *
	 * @param array<string, string> $types Public post types Elementor lists.
	 * @return array<string, string>
	 */
	public static function remove_product_type( array $types ): array {
		unset( $types['product'] );
		return $types;
	}

	/**
	 * @param mixed $content Widget output.
	 * @param mixed $widget  Elementor widget instance.
	 */
	public static function html_widget_shortcodes( $content, $widget ) {
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
			return $content;
		}
		if ( 'html' !== $widget->get_name() ) {
			return $content;
		}
		if ( ! is_string( $content ) || false === strpos( $content, '[' ) ) {
			return $content;
		}
		return do_shortcode( $content );
	}
}
