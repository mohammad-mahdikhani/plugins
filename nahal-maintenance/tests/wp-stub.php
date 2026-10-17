<?php
/**
 * Minimal WordPress stubs to prove the plugin loads and activates.
 *
 * Usage: php tests/wp-stub.php
 */

namespace {
	define( 'ABSPATH', sys_get_temp_dir() . '/nm-wp-stub/' );

	$GLOBALS['nm_actions'] = array();
	$GLOBALS['nm_options'] = array();

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
		$GLOBALS['nm_activate'] = $cb;
	}
	function register_deactivation_hook( $file, $cb ) {
		$GLOBALS['nm_deactivate'] = $cb;
	}
	function add_action( $hook, $cb, $priority = 10, $args = 1 ) {
		$GLOBALS['nm_actions'][ $hook ][] = $cb;
	}
	function add_filter( $hook, $cb, $priority = 10, $args = 1 ) {
		$GLOBALS['nm_actions'][ $hook ][] = $cb;
	}
	function do_action( $hook ) {
		if ( empty( $GLOBALS['nm_actions'][ $hook ] ) ) {
			return;
		}
		foreach ( $GLOBALS['nm_actions'][ $hook ] as $cb ) {
			call_user_func( $cb );
		}
	}
	function get_option( $key, $default = false ) {
		return array_key_exists( $key, $GLOBALS['nm_options'] ) ? $GLOBALS['nm_options'][ $key ] : $default;
	}
	function add_option( $key, $value ) {
		$GLOBALS['nm_options'][ $key ] = $value;
		return true;
	}
	function update_option( $key, $value ) {
		$GLOBALS['nm_options'][ $key ] = $value;
		return true;
	}
	function is_admin() {
		return false;
	}
	function wp_die( $m = '' ) {
		throw new RuntimeException( (string) $m );
	}

	require dirname( __DIR__ ) . '/nahal-maintenance.php';

	if ( ! class_exists( 'NM_Install', false ) ) {
		fwrite( STDERR, "NM_Install missing\n" );
		exit( 1 );
	}
	if ( empty( $GLOBALS['nm_activate'] ) ) {
		fwrite( STDERR, "activation hook missing\n" );
		exit( 1 );
	}

	call_user_func( $GLOBALS['nm_activate'] );
	do_action( 'plugins_loaded' );

	if ( empty( $GLOBALS['nm_options']['nm_settings'] ) ) {
		fwrite( STDERR, "settings not stored\n" );
		exit( 1 );
	}
	if ( ! class_exists( 'NM_Plugin', false ) ) {
		fwrite( STDERR, "plugin class missing\n" );
		exit( 1 );
	}

	echo "OK plugin loads and activates\n";
}
