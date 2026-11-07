<?php
/**
 * Real WordPress 404 → Elementor PM page (404-pm) with HTTP 404 status.
 */

defined( 'ABSPATH' ) || exit;

final class PM_404 {

	public const PAGE_SLUG = '404-pm';

	public static function init(): void {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function get_page_id(): int {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}
		$page = get_page_by_path( self::PAGE_SLUG );
		$cached = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
		return $cached;
	}

	/**
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		if ( is_404() ) {
			$classes[] = 'error404';
			$classes[] = 'pm-error-404';
		}
		return $classes;
	}

	/**
	 * @param string $template Theme template path.
	 */
	public static function template_include( string $template ): string {
		if ( is_admin() || ! is_404() || wp_doing_ajax() || wp_is_json_request() ) {
			return $template;
		}

		if ( self::get_page_id() < 1 ) {
			return $template;
		}

		$shell = PM_HOME_PATH . 'templates/pm-404-shell.php';
		return is_readable( $shell ) ? $shell : $template;
	}
}
