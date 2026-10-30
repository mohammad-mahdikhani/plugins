<?php
/**
 * Hello Pakan — site-wide 404 → Elementor page slug 404-pm (no extra plugin required).
 */

defined( 'ABSPATH' ) || exit;

define( 'HELLO_PAKAN_404_SLUG', '404-pm' );

/**
 * @return int Published 404 page ID or 0.
 */
function hello_pakan_404_page_id(): int {
	static $id = null;
	if ( null !== $id ) {
		return $id;
	}
	$page = get_page_by_path( HELLO_PAKAN_404_SLUG );
	$id    = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
	return $id;
}

add_filter(
	'template_include',
	static function ( string $template ): string {
		if ( is_admin() || ! is_404() || wp_doing_ajax() || wp_is_json_request() ) {
			return $template;
		}
		if ( hello_pakan_404_page_id() < 1 ) {
			return $template;
		}
		$shell = get_stylesheet_directory() . '/pm-404-shell.php';
		return is_readable( $shell ) ? $shell : $template;
	},
	99
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_404() ) {
			$classes[] = 'pm-error-404';
		}
		return $classes;
	}
);
