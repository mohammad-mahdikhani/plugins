<?php
/**
 * Tests that do not need WordPress.
 *
 * Usage: php tests/run.php
 */

require __DIR__ . '/bootstrap.php';

$failed = 0;
$passed = 0;

function nm_assert( $cond, $label ) {
	global $failed, $passed;
	if ( $cond ) {
		$passed++;
		echo "  OK  $label\n";
	} else {
		$failed++;
		echo "  FAIL  $label\n";
	}
}

echo "== Defaults ==\n";
$defaults = NM_Settings::defaults();
nm_assert( $defaults['enabled'] === false, 'maintenance off by default' );
nm_assert( $defaults['brand'] === 'مجموعه نهال نیشابور', 'nahal brand' );
nm_assert( $defaults['title'] === 'کمی صبر کنید', 'persian title' );
nm_assert( $defaults['bypass_admins'] === true, 'admins bypass' );
nm_assert( $defaults['retry_after'] === 3600, 'retry after one hour' );

echo "== Sanitize ==\n";
$clean = NM_Settings::sanitize(
	array(
		'enabled'       => '1',
		'brand'         => "  نهال <script>alert(1)</script> ",
		'title'         => str_repeat( 'ع', 200 ),
		'logo_url'      => 'javascript:alert(1)',
		'bypass_key'    => 'ok_key-12!!',
		'retry_after'   => 12,
		'bypass_admins' => '',
	)
);
nm_assert( $clean['enabled'] === true, 'enabled becomes bool' );
nm_assert( false === strpos( $clean['brand'], '<script>' ), 'strips tags from brand' );
nm_assert( function_exists( 'mb_strlen' ) ? mb_strlen( $clean['title'] ) === 80 : strlen( $clean['title'] ) === 80, 'title truncated' );
nm_assert( $clean['logo_url'] === '', 'rejects javascript url' );
nm_assert( $clean['bypass_key'] === 'ok_key-12', 'strips key punctuation' );
nm_assert( $clean['retry_after'] === 60, 'retry_after minimum 60' );
nm_assert( $clean['bypass_admins'] === false, 'empty checkbox is false' );

$https = NM_Settings::sanitize( array( 'logo_url' => 'https://nahal.website/logo.png' ) );
nm_assert( $https['logo_url'] === 'https://nahal.website/logo.png', 'keeps https logo' );

echo "== Page ==\n";
$html = NM_Page::html( NM_Settings::page_data( $defaults ) );
nm_assert( false !== strpos( $html, 'lang="fa"' ), 'rtl persian html' );
nm_assert( false !== strpos( $html, 'dir="rtl"' ), 'rtl direction' );
nm_assert( false !== strpos( $html, 'کمی صبر کنید' ), 'title in page' );
nm_assert( false !== strpos( $html, 'مجموعه نهال نیشابور' ), 'brand in page' );
nm_assert( false !== strpos( $html, '05143331449' ), 'phone in page' );
nm_assert( false !== strpos( $html, 'nm-cube-core' ), '3d core cube' );
nm_assert( false !== strpos( $html, 'nm-crystal' ), '3d crystal' );
nm_assert( false !== strpos( $html, 'maintenance.css' ), 'css linked' );
nm_assert( false !== strpos( $html, 'maintenance.js' ), 'js linked' );
nm_assert( false !== strpos( $html, 'noindex' ), 'noindex for crawlers' );
nm_assert( false !== strpos( $html, 'notranslate' ), 'blocks google translate overlay' );
nm_assert( false !== strpos( $html, 'dir="rtl"' ), 'card keeps rtl' );

$cube = NM_Page::cube( 'nm-cube-core' );
nm_assert( substr_count( $cube, 'class="nm-face ' ) === 6, 'cube has six faces' );

echo "== Bypass ==\n";
$front = NM_Frontend::instance();
$_SERVER['SCRIPT_NAME'] = '/wp-login.php';
$_SERVER['REQUEST_URI'] = '/wp-login.php';
nm_assert( $front->is_backend_request() === true, 'login is backend' );

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
nm_assert( $front->is_backend_request() === false, 'home is public' );

$_SERVER['REQUEST_URI'] = '/wp-admin/plugins.php';
nm_assert( $front->is_backend_request() === true, 'wp-admin is backend' );

$_SERVER['REQUEST_URI'] = '/robots.txt';
nm_assert( $front->is_backend_request() === true, 'robots stays reachable' );

$_GET = array( 'nm_key' => 'secret1' );
nm_assert( $front->has_valid_bypass_key( array( 'bypass_key' => 'secret1' ) ) === true, 'valid bypass key' );
nm_assert( $front->has_valid_bypass_key( array( 'bypass_key' => 'other' ) ) === false, 'wrong bypass key' );
nm_assert( $front->has_valid_bypass_key( array( 'bypass_key' => '' ) ) === false, 'empty key never matches' );

$_GET = array();
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
$on = array_merge( $defaults, array( 'enabled' => true, 'bypass_admins' => false ) );
nm_assert( $front->should_bypass( $on ) === false, 'visitor is not bypassed' );

echo "== Preview builder data ==\n";
$page = NM_Settings::page_data( $defaults );
nm_assert( false !== strpos( $page['css_href'], 'maintenance.css' ), 'page data has css' );
nm_assert( $page['phone'] === '05143331449', 'page data keeps phone' );

echo "\n$passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );
