<?php
/**
 * Tests that do not need WordPress.
 *
 * Usage: php tests/run.php
 */

require __DIR__ . '/../includes/class-rewriter.php';
require __DIR__ . '/../includes/class-files.php';
require __DIR__ . '/../includes/class-overflow.php';

$failed = 0;
$passed = 0;

function nfds_assert( $cond, $label ) {
	global $failed, $passed;
	if ( $cond ) {
		$passed++;
		echo "  OK  $label\n";
	} else {
		$failed++;
		echo "  FAIL  $label\n";
	}
}

echo "== CSS @font-face ==\n";
$auto = NFDS_Rewriter::css( "@font-face{font-family:x;src:url(x.woff2);font-display:auto}" );
nfds_assert( false !== strpos( $auto, 'font-display:swap' ), 'auto becomes swap' );
nfds_assert( false === stripos( $auto, 'font-display:auto' ), 'auto removed' );

$block = NFDS_Rewriter::css( "@font-face { font-family: y; font-display: block; src: url(y.woff2); }" );
nfds_assert( false !== strpos( $block, 'font-display:swap' ), 'block becomes swap' );

$optional = NFDS_Rewriter::css( "@font-face{font-family:z;font-display:optional}" );
nfds_assert( false !== strpos( $optional, 'font-display:swap' ), 'optional becomes swap' );

$fallback = NFDS_Rewriter::css( "@font-face{font-family:f;font-display:fallback!important;}" );
nfds_assert( false !== strpos( $fallback, 'font-display:swap' ), 'fallback important becomes swap' );

$missing = NFDS_Rewriter::css( "@font-face{font-family:n;src:url(n.woff2)}" );
nfds_assert( false !== strpos( $missing, '{font-display:swap;' ), 'inserts swap when missing' );

$already = NFDS_Rewriter::css( "@font-face{font-family:s;font-display:swap;src:url(s.woff2)}" );
nfds_assert( $already === NFDS_Rewriter::css( $already ), 'swap is idempotent' );

$layout = NFDS_Rewriter::css( '.hero{display:block;font-display:this-is-not-a-face}' );
nfds_assert( false !== strpos( $layout, 'display:block' ), 'does not rewrite element display' );

$multi = NFDS_Rewriter::css( "@font-face{font-family:a;font-display:auto}@font-face{font-family:b;src:url(b)}" );
nfds_assert( 2 === substr_count( $multi, 'font-display:swap' ), 'rewrites two faces' );
nfds_assert( 2 === NFDS_Rewriter::font_face_count( $multi ), 'counts two faces' );

echo "== Google / Bunny URLs ==\n";
$g1 = NFDS_Rewriter::font_css_url( 'https://fonts.googleapis.com/css?family=Roboto:400' );
nfds_assert( false !== strpos( $g1, 'display=swap' ), 'adds display=swap to css v1' );

$g2 = NFDS_Rewriter::font_css_url( 'https://fonts.googleapis.com/css2?family=Roboto:wght@400&display=block' );
nfds_assert( false !== strpos( $g2, 'display=swap' ), 'css2 block becomes swap' );
nfds_assert( false === strpos( $g2, 'display=block' ), 'css2 block removed' );

$amp = NFDS_Rewriter::font_css_url( 'https://fonts.googleapis.com/css?family=Roboto&amp;display=auto' );
nfds_assert( false !== strpos( $amp, 'display=swap' ), 'encoded amp display becomes swap' );
nfds_assert( false === strpos( $amp, 'display=auto' ), 'encoded auto removed' );

$bunny = NFDS_Rewriter::font_css_url( 'https://fonts.bunny.net/css?family=inter:400' );
nfds_assert( false !== strpos( $bunny, 'display=swap' ), 'bunny fonts get swap' );

$woff = NFDS_Rewriter::font_css_url( 'https://fonts.gstatic.com/s/roboto/v30/roboto.woff2' );
nfds_assert( $woff === 'https://fonts.gstatic.com/s/roboto/v30/roboto.woff2', 'does not touch gstatic font files' );

$theme = NFDS_Rewriter::font_css_url( 'https://nahal.website/wp-content/themes/studiare/style.css' );
nfds_assert( $theme === 'https://nahal.website/wp-content/themes/studiare/style.css', 'does not touch theme css urls' );

echo "== HTML ==\n";
$html = file_get_contents( __DIR__ . '/fixtures/sample.html' );
$out  = NFDS_Rewriter::html( $html );
nfds_assert( false !== strpos( $out, 'display=swap' ), 'html google url has swap' );
nfds_assert( false === strpos( $out, 'display=block' ), 'html block gone' );
nfds_assert( false !== strpos( $out, 'fonts.bunny.net/css?family=inter:400' ) && false !== strpos( $out, 'display=swap' ), 'bunny in html' );
nfds_assert( false !== strpos( $out, 'font-display:swap' ), 'inline @font-face swapped' );
nfds_assert( false === stripos( $out, 'font-display: optional' ), 'optional gone from style tag' );
nfds_assert( false !== strpos( $out, 'display: flex' ), 'layout display flex kept' );

echo "== Files ==\n";
$tmp = sys_get_temp_dir() . '/nfds-' . getmypid();
@mkdir( $tmp );
$src = file_get_contents( __DIR__ . '/fixtures/sample.css' );
file_put_contents( $tmp . '/theme.css', $src );
$res = NFDS_Files::convert_path( $tmp, true );
nfds_assert( 1 === $res['changed'], 'converts one css file' );
nfds_assert( file_exists( $tmp . '/theme.css.nfds-bak' ), 'writes backup' );
$converted = file_get_contents( $tmp . '/theme.css' );
nfds_assert( false !== strpos( $converted, 'font-display:swap' ), 'file has swap' );
nfds_assert( false === stripos( $converted, 'font-display: auto' ), 'file auto gone' );
nfds_assert( false === stripos( $converted, 'font-display:block' ), 'file block gone' );
nfds_assert( false !== strpos( $converted, '{font-display:swap;' ), 'eicons face has swap' );
nfds_assert( false !== strpos( $converted, 'display=swap' ), 'google import in css swapped' );
nfds_assert( false !== strpos( $converted, '.button { display: block; color: #111; }' ), 'element display block kept' );
$again = NFDS_Files::convert_path( $tmp, true );
nfds_assert( 0 === $again['changed'], 'second pass is no-op' );
@unlink( $tmp . '/theme.css' );
@unlink( $tmp . '/theme.css.nfds-bak' );
@rmdir( $tmp );

echo "== Plugin header ==\n";
$main = file_get_contents( __DIR__ . '/../nahal-font-display-swap.php' );
$plug = file_get_contents( __DIR__ . '/../includes/class-plugin.php' );
nfds_assert( false !== strpos( $main, "NFDS_VERSION', '1.0.1'" ), 'version 1.0.1' );
nfds_assert( false !== strpos( $plug, 'pre_option_elementor_font_display' ), 'locks elementor google font display' );
nfds_assert( false !== strpos( $plug, 'elementor_pro/custom_fonts/font_display' ), 'locks elementor pro custom fonts' );
nfds_assert( false !== strpos( $plug, "ob_start( array( 'NFDS_Rewriter', 'html' ) )" ), 'buffers html' );
nfds_assert( false !== strpos( $plug, 'NFDS_Overflow::init' ), 'loads overflow fixer' );
$ov = NFDS_Overflow::css();
nfds_assert( false !== strpos( $ov, 'overflow-x:hidden' ), 'overflow css hides x scroll' );
nfds_assert( false !== strpos( $ov, 'elementor-section-stretched' ), 'fixes elementor stretched sections' );
nfds_assert( false !== strpos( $ov, 'max-width:100%' ), 'caps width at 100%' );

echo "\n$passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );
