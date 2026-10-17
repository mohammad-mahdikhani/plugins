<?php
/**
 * Write a standalone HTML preview of the 3D maintenance page.
 *
 * Usage: php tests/build-preview.php
 */

require __DIR__ . '/bootstrap.php';

$data = NM_Settings::page_data( NM_Settings::defaults() );
$data['css_href'] = '../assets/frontend/maintenance.css';
$data['js_src']   = '../assets/frontend/maintenance.js';

$html = NM_Page::html( $data );
$out  = __DIR__ . '/preview-maintenance.html';
file_put_contents( $out, $html );

echo "Wrote $out\n";
