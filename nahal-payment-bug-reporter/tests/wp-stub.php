<?php
/**
 * Minimal WordPress stubs to prove the plugin loads and activates.
 *
 * Usage: php tests/wp-stub.php
 */

namespace {
	define( 'ABSPATH', sys_get_temp_dir() . '/npbr-wp-stub/' );
	define( 'HOUR_IN_SECONDS', 3600 );

	$GLOBALS['npbr_actions']  = array();
	$GLOBALS['npbr_options']  = array();
	$GLOBALS['npbr_post_types'] = array();

	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
	function plugin_dir_url( $file ) {
		return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
	function plugin_basename( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
	function register_activation_hook( $file, $cb ) {
		$GLOBALS['npbr_activate'] = $cb;
	}
	function register_deactivation_hook( $file, $cb ) {
		$GLOBALS['npbr_deactivate'] = $cb;
	}
	function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
		$GLOBALS['npbr_actions'][ $hook ][] = $cb;
	}
	function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
		$GLOBALS['npbr_actions'][ $hook ][] = $cb;
	}
	function add_shortcode( $tag, $cb ) {}
	function do_action( $hook ) {
		if ( empty( $GLOBALS['npbr_actions'][ $hook ] ) ) {
			return;
		}
		foreach ( $GLOBALS['npbr_actions'][ $hook ] as $cb ) {
			call_user_func( $cb );
		}
	}
	function get_option( $key, $default = false ) {
		return array_key_exists( $key, $GLOBALS['npbr_options'] ) ? $GLOBALS['npbr_options'][ $key ] : $default;
	}
	function add_option( $key, $value ) {
		$GLOBALS['npbr_options'][ $key ] = $value;
		return true;
	}
	function update_option( $key, $value ) {
		$GLOBALS['npbr_options'][ $key ] = $value;
		return true;
	}
	function register_post_type( $type, $args = array() ) {
		$GLOBALS['npbr_post_types'][ $type ] = $args;
		return true;
	}
	function flush_rewrite_rules( $hard = true ) {}
	function is_admin() {
		return false;
	}
	function wp_die( $m = '' ) {
		throw new RuntimeException( (string) $m );
	}

	require dirname( __DIR__ ) . '/nahal-payment-bug-reporter.php';

	if ( ! class_exists( 'NPBR_Install', false ) ) {
		fwrite( STDERR, "NPBR_Install missing after include\n" );
		exit( 1 );
	}

	call_user_func( $GLOBALS['npbr_activate'] );
	do_action( 'plugins_loaded' );
	do_action( 'init' );

	if ( empty( $GLOBALS['npbr_options']['npbr_settings'] ) ) {
		fwrite( STDERR, "settings not saved on activate\n" );
		exit( 1 );
	}
	if ( empty( $GLOBALS['npbr_post_types']['npbr_report'] ) ) {
		fwrite( STDERR, "post type not registered on init\n" );
		exit( 1 );
	}

	echo "activate-stub OK\n";
}
