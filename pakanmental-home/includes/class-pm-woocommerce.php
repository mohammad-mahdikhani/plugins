<?php
/**
 * WooCommerce pages: body classes + hide duplicate WC titles on PM shell pages.
 */

defined( 'ABSPATH' ) || exit;

final class PM_WooCommerce {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'woocommerce_before_main_content', array( __CLASS__, 'maybe_hide_wc_wrappers' ), 1 );
		add_filter( 'woocommerce_show_page_title', array( __CLASS__, 'hide_page_title' ) );
	}

	public static function is_pm_wc_page(): bool {
		return function_exists( 'is_account_page' ) && (
			is_account_page() ||
			is_cart() ||
			is_checkout()
		);
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( self::is_pm_wc_page() ) {
			$classes[] = 'pm-wc-page';
		}
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			$classes[] = 'pm-wc-account-page';
		}
		return $classes;
	}

	public static function hide_page_title( bool $show ): bool {
		if ( self::is_pm_wc_page() && self::page_uses_elementor_canvas() ) {
			return false;
		}
		return $show;
	}

	public static function maybe_hide_wc_wrappers(): void {
		if ( ! self::page_uses_elementor_canvas() ) {
			return;
		}
		remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
		remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
	}

	private static function page_uses_elementor_canvas(): bool {
		if ( ! is_singular( 'page' ) ) {
			return false;
		}
		$id = get_queried_object_id();
		return 'elementor_canvas' === get_page_template_slug( $id )
			|| 'yes' === get_post_meta( $id, '_elementor_edit_mode', true );
	}
}
