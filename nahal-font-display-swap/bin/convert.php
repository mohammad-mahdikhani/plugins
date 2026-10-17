#!/usr/bin/env php
<?php
/**
 * Convert @font-face / Google Font URLs in a directory to font-display: swap.
 *
 * Usage: php bin/convert.php /path/to/theme-or-css
 */

if ( PHP_SAPI !== 'cli' ) {
	fwrite( STDERR, "CLI only\n" );
	exit( 1 );
}

$root = dirname( __DIR__ );
require_once $root . '/includes/class-rewriter.php';
require_once $root . '/includes/class-files.php';

$path = isset( $argv[1] ) ? $argv[1] : '';
if ( '' === $path ) {
	fwrite( STDERR, "Usage: php bin/convert.php /path/to/theme-or-css\n" );
	exit( 1 );
}

$out = NFDS_Files::convert_path( $path, true );
echo sprintf(
	"files=%d changed=%d faces=%d\n",
	$out['files'],
	$out['changed'],
	$out['faces']
);
if ( ! empty( $out['errors'] ) ) {
	fwrite( STDERR, implode( "\n", $out['errors'] ) . "\n" );
	exit( 1 );
}
exit( 0 );
