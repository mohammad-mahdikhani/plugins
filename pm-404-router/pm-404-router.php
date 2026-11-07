<?php
/**
 * Plugin Name: PM 404 Router
 * Description: Shows the Elementor page «404-pm» for all missing URLs with HTTP 404 status.
 * Version: 1.0.0
 * Author: Pakan Mental
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

final class PM_404_Router {

	private const PAGE_SLUG = '404-pm';

	public static function init(): void {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
	}

	private static function page_id(): int {
		static $id = null;
		if ( null !== $id ) {
			return $id;
		}
		$page = get_page_by_path( self::PAGE_SLUG );
		$id    = ( $page instanceof WP_Post && 'publish' === $page->post_status ) ? (int) $page->ID : 0;
		return $id;
	}

	public static function template_include( string $template ): string {
		if ( is_admin() || ! is_404() || wp_doing_ajax() || wp_is_json_request() ) {
			return $template;
		}
		$page_id = self::page_id();
		if ( $page_id < 1 ) {
			return $template;
		}
		$shell = plugin_dir_path( __FILE__ ) . 'shell.php';
		return is_readable( $shell ) ? $shell : $template;
	}
}

PM_404_Router::init();
