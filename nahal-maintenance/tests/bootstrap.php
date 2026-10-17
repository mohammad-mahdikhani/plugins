<?php
/**
 * Load plugin classes without WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/nm-wp/' );
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string ) {
		return trim( preg_replace( '/<[^>]*>/', '', (string) $string ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		$str = is_string( $str ) ? $str : '';
		$str = preg_replace( '/[\r\n\t]+/', ' ', $str );
		return trim( $str );
	}
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		return array_key_exists( $key, $GLOBALS['nm_options'] ) ? $GLOBALS['nm_options'][ $key ] : $default;
	}
}
if ( ! function_exists( 'add_option' ) ) {
	function add_option( $key, $value ) {
		$GLOBALS['nm_options'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value ) {
		$GLOBALS['nm_options'][ $key ] = $value;
		return true;
	}
}

if ( ! isset( $GLOBALS['nm_options'] ) ) {
	$GLOBALS['nm_options'] = array();
}

require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-page.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';
