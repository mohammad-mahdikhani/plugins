<?php
/**
 * Load plugin classes without WordPress.
 */

define( 'NPBR_TEST', true );
define( 'NPBR_VERSION', '1.0.0' );

require __DIR__ . '/../includes/class-sanitizer.php';
require __DIR__ . '/../includes/class-report.php';
require __DIR__ . '/../includes/class-zip.php';
require __DIR__ . '/../includes/class-telegram.php';
