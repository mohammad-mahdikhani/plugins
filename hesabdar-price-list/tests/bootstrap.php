<?php
/**
 * Headless bootstrap for parser / matcher tests (no WordPress).
 */

define( 'HPL_TEST', true );
define( 'HPL_DIR', dirname( __DIR__ ) . '/' );

require_once HPL_DIR . 'includes/class-normalizer.php';
require_once HPL_DIR . 'includes/class-sheet.php';
require_once HPL_DIR . 'includes/class-column-mapper.php';
require_once HPL_DIR . 'includes/class-parser.php';
require_once HPL_DIR . 'includes/parsers/class-csv-parser.php';
require_once HPL_DIR . 'includes/parsers/class-xlsx-parser.php';
require_once HPL_DIR . 'includes/parsers/class-xls-parser.php';
require_once HPL_DIR . 'includes/parsers/class-pdf-parser.php';
require_once HPL_DIR . 'includes/parsers/class-telegram-parser.php';
require_once HPL_DIR . 'includes/class-catalog.php';
require_once HPL_DIR . 'includes/class-updater.php';
require_once HPL_DIR . 'includes/class-screener.php';
require_once HPL_DIR . 'includes/class-export.php';
require_once HPL_DIR . 'includes/class-telegram.php';
require_once HPL_DIR . 'vendor/SimpleXLSX.php';
require_once HPL_DIR . 'vendor/SimpleXLSXGen.php';
