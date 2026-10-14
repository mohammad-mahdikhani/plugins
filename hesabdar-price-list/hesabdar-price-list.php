<?php
/**
 * Plugin Name: Hesabdar Price List
 * Plugin URI:  https://webakery.ir
 * Description: به‌روزرسانی یکجای قیمت محصولات ووکامرس با آپلود لیست قیمت اکسل یا PDF.
 * Version:     1.1.0
 * Author:      webakery
 * Author URI:  https://webakery.ir
 * Text Domain: hesabdar-price-list
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 9.3
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HPL_VERSION', '1.1.0' );
define( 'HPL_FILE', __FILE__ );
define( 'HPL_DIR', plugin_dir_path( __FILE__ ) );
define( 'HPL_URL', plugin_dir_url( __FILE__ ) );
define( 'HPL_BASENAME', plugin_basename( __FILE__ ) );

require_once HPL_DIR . 'includes/class-normalizer.php';
require_once HPL_DIR . 'includes/class-sheet.php';
require_once HPL_DIR . 'includes/class-column-mapper.php';
require_once HPL_DIR . 'includes/class-parser.php';
require_once HPL_DIR . 'includes/parsers/class-csv-parser.php';
require_once HPL_DIR . 'includes/parsers/class-xlsx-parser.php';
require_once HPL_DIR . 'includes/parsers/class-xls-parser.php';
require_once HPL_DIR . 'includes/parsers/class-pdf-parser.php';
require_once HPL_DIR . 'includes/class-catalog.php';
require_once HPL_DIR . 'includes/class-updater.php';
require_once HPL_DIR . 'includes/class-screener.php';
require_once HPL_DIR . 'includes/class-history.php';
require_once HPL_DIR . 'includes/class-storage.php';
require_once HPL_DIR . 'includes/class-export.php';
require_once HPL_DIR . 'includes/class-install.php';
require_once HPL_DIR . 'includes/class-admin.php';
require_once HPL_DIR . 'includes/class-ajax.php';
require_once HPL_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'HPL_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HPL_Install', 'deactivate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', HPL_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		HPL_Plugin::instance()->init();
	}
);
