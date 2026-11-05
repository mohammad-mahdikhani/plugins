<?php
/**
 * Hello Pakan — 404 → Elementor 404-pm; WooCommerce product/category PM shell.
 */

defined( 'ABSPATH' ) || exit;

define( 'HELLO_PAKAN_404_SLUG', '404-pm' );
define( 'HELLO_PAKAN_HEADER_ID', 124 );
define( 'HELLO_PAKAN_FOOTER_ID', 125 );

/**
 * @return int Published 404 page ID or 0.
 */
function hello_pakan_404_page_id(): int {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$page = get_page_by_path( HELLO_PAKAN_404_SLUG );
	$id   = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
	return $id;
}

/**
 * @param int $post_id Elementor library section ID.
 */
function hello_pakan_render_section( int $post_id ): void {
	if ( $post_id < 1 || ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
	if ( is_string( $content ) && '' !== $content ) {
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

function hello_pakan_uses_wc_shell(): bool {
	return class_exists( 'WooCommerce' ) && ( is_singular( 'product' ) || is_product_taxonomy() );
}

add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
			return $template;
		}
		if ( hello_pakan_uses_wc_shell() ) {
			$shell = get_stylesheet_directory() . '/pm-wc-shell.php';
			if ( is_readable( $shell ) ) {
				return $shell;
			}
		}
		if ( is_404() ) {
			if ( hello_pakan_404_page_id() < 1 ) {
				return $template;
			}
			$shell = get_stylesheet_directory() . '/pm-404-shell.php';
			return is_readable( $shell ) ? $shell : $template;
		}
		return $template;
	},
	99
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_404() ) {
			$classes[] = 'pm-error-404';
		}
		if ( hello_pakan_uses_wc_shell() ) {
			$classes[] = 'pm-wc-single';
			if ( is_product_taxonomy() ) {
				$classes[] = 'pm-wc-archive';
			}
		}
		return $classes;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || ! hello_pakan_uses_wc_shell() ) {
			return;
		}
		$base = plugins_url( 'pakanmental-home' );
		$ver  = defined( 'PM_HOME_VERSION' ) ? PM_HOME_VERSION : '1.1.6';
		wp_enqueue_style(
			'pm-fonts',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'pm-brand-overrides', $base . '/assets/css/pm-brand-overrides.css', array(), $ver );
		wp_enqueue_style( 'pm-site', $base . '/assets/css/pm.css', array( 'pm-fonts', 'pm-brand-overrides' ), $ver );
		$scripts = array(
			'pm-header'       => 'assets/js/pm-header.js',
			'pm-mega-menu'    => 'assets/js/pm-mega-menu.js',
			'pm-mobile'       => 'assets/js/pm-mobile.js',
			'pm-search'       => 'assets/js/pm-search.js',
			'pm-trust-badges' => 'assets/js/pm-trust-badges.js',
			'pm-cart'         => 'assets/js/pm-cart.js',
		);
		foreach ( $scripts as $handle => $rel ) {
			wp_enqueue_script( $handle, $base . '/' . $rel, array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
		if ( function_exists( 'wc_get_cart_url' ) ) {
			$cfg = array(
				'storeApi'    => rest_url( 'wc/store/v1' ),
				'cartUrl'     => wc_get_cart_url(),
				'checkoutUrl' => wc_get_checkout_url(),
				'shopUrl'     => wc_get_page_permalink( 'shop' ),
			);
			wp_add_inline_script( 'pm-cart', 'window.pmCart=' . wp_json_encode( $cfg ) . ';', 'before' );
		}
		if ( is_singular( 'product' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}
	},
	25
);
