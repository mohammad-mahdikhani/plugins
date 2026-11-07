<?php
/**
 * Plugin Name: Pakan Mental WC Shell
 * Description: قالب PM برای تک‌محصول و آرشیو دسته — هدر/فوتر Elementor (124/125).
 * Version: 1.0.0
 * Author: Pakan Mental
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'PMWC_HEADER_ID', 124 );
define( 'PMWC_FOOTER_ID', 125 );

function pmwc_uses_shell(): bool {
	return class_exists( 'WooCommerce' ) && ( is_singular( 'product' ) || is_product_taxonomy() );
}

function pmwc_render_section( int $id ): void {
	if ( $id < 1 || ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $id, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || ! pmwc_uses_shell() ) {
			return $template;
		}
		$shell = plugin_dir_path( __FILE__ ) . 'shell.php';
		return is_readable( $shell ) ? $shell : $template;
	},
	99
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( pmwc_uses_shell() ) {
			$classes[] = 'pm-site';
			$classes[] = 'pm-wc-shell';
			$classes[] = is_singular( 'product' ) ? 'pm-wc-single' : 'pm-wc-archive';
		}
		return $classes;
	}
);

add_filter(
	'woocommerce_locate_template',
	static function ( string $template, string $template_name, string $template_path ): string {
		if ( 'content-single-product.php' !== $template_name ) {
			return $template;
		}
		$custom = plugin_dir_path( __FILE__ ) . 'woocommerce/content-single-product.php';
		return is_readable( $custom ) ? $custom : $template;
	},
	12,
	3
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || ! pmwc_uses_shell() ) {
			return;
		}
		$base = plugins_url( 'pakanmental-home' );
		if ( ! file_exists( WP_PLUGIN_DIR . '/pakanmental-home/pakanmental-home.php' ) ) {
			return;
		}
		$ver = defined( 'PM_HOME_VERSION' ) ? PM_HOME_VERSION : '1.1.6';
		wp_enqueue_style(
			'pm-fonts',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'pm-brand-overrides', $base . '/assets/css/pm-brand-overrides.css', array(), $ver );
		wp_enqueue_style( 'pm-site', $base . '/assets/css/pm.css', array( 'pm-fonts', 'pm-brand-overrides' ), $ver );
		foreach (
			array(
				'pm-header'       => 'assets/js/pm-header.js',
				'pm-mega-menu'    => 'assets/js/pm-mega-menu.js',
				'pm-mobile'       => 'assets/js/pm-mobile.js',
				'pm-search'       => 'assets/js/pm-search.js',
				'pm-trust-badges' => 'assets/js/pm-trust-badges.js',
				'pm-cart'         => 'assets/js/pm-cart.js',
			) as $handle => $rel
		) {
			wp_enqueue_script( $handle, $base . '/' . $rel, array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
		if ( function_exists( 'wc_get_cart_url' ) ) {
			wp_add_inline_script(
				'pm-cart',
				'window.pmCart=' . wp_json_encode(
					array(
						'storeApi'    => rest_url( 'wc/store/v1' ),
						'cartUrl'     => wc_get_cart_url(),
						'checkoutUrl' => wc_get_checkout_url(),
						'shopUrl'     => wc_get_page_permalink( 'shop' ),
					)
				) . ';',
				'before'
			);
		}
		if ( is_singular( 'product' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}
	},
	25
);
