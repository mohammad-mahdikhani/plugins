<?php
/**
 * Use the Elementor PM 404 page for real WordPress 404 responses.
 */

defined( 'ABSPATH' ) || exit;

final class PM_404 {

	public const PAGE_SLUG = '404-pm';

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_pm_404' ), 0 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function get_page_id(): int {
		$page = get_page_by_path( self::PAGE_SLUG );
		return $page instanceof WP_Post ? (int) $page->ID : 0;
	}

	public static function body_class( array $classes ): array {
		if ( is_404() ) {
			$classes[] = 'error404';
			$classes[] = 'pm-error-404';
		}
		return $classes;
	}

	public static function maybe_render_pm_404(): void {
		if ( ! is_404() || is_admin() ) {
			return;
		}

		$page_id = self::get_page_id();
		if ( $page_id < 1 ) {
			return;
		}

		global $wp_query, $post;

		$post = get_post( $page_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$wp_query->posts         = array( $post );
		$wp_query->post          = $post;
		$wp_query->post_count    = 1;
		$wp_query->found_posts   = 1;
		$wp_query->max_num_pages = 1;
		$wp_query->is_404        = true;
		$wp_query->is_page       = true;
		$wp_query->is_singular   = true;
		$wp_query->queried_object    = $post;
		$wp_query->queried_object_id = $page_id;

		setup_postdata( $post );
		status_header( 404 );
		nocache_headers();

		$template = get_page_template();
		if ( ! $template ) {
			$template = locate_template( array( 'page.php', 'singular.php', 'index.php' ) );
		}
		if ( $template ) {
			include $template;
			exit;
		}
	}
}
