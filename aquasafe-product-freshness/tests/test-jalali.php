<?php
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/' );
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter() {}
	function add_action() {}
	function esc_attr( $s ) { return $s; }
	function esc_html( $s ) { return $s; }
	function is_admin() { return false; }
}
require_once dirname( __DIR__ ) . '/aquasafe-product-freshness.php';

function as_assert( $ok, $msg ) {
	if ( ! $ok ) {
		fwrite( STDERR, "FAIL: $msg\n" );
		exit( 1 );
	}
	echo "ok: $msg\n";
}

$j = as_fresh_gregorian_to_jalali( 2026, 11, 11 );
as_assert( 1405 === $j[0] && 8 === $j[1] && 20 === $j[2], '2026-11-11 is 20 Aban 1405' );
as_assert( '۲۰' === as_fresh_fa_digits( 20 ), 'persian digits' );
echo "ALL_PASS\n";
