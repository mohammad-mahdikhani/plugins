<?php
/**
 * Plugin Name: Nahal Payment Bug Reporter
 * Plugin URI:  https://nahal.website
 * Description: گزارشگر خطای پرداخت نهال — دکمه گزارش در صفحات و فیلدهای پرداخت، اسکرین صفحه و ارسال برای مدیر.
 * Version:     1.0.7
 * Author:      nahal.website
 * Author URI:  https://nahal.website
 * Text Domain: nahal-payment-bug-reporter
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

if ( ! defined( 'NPBR_VERSION' ) ) {
	define( 'NPBR_VERSION', '1.0.7' );
}
if ( ! defined( 'NPBR_FILE' ) ) {
	define( 'NPBR_FILE', __FILE__ );
}
if ( ! defined( 'NPBR_DIR' ) ) {
	define( 'NPBR_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'NPBR_URL' ) ) {
	define( 'NPBR_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'NPBR_BASENAME' ) ) {
	define( 'NPBR_BASENAME', plugin_basename( __FILE__ ) );
}

$npbr_includes = array(
	'includes/class-sanitizer.php',
	'includes/class-report.php',
	'includes/class-customer.php',
	'includes/class-zip.php',
	'includes/class-mailer.php',
	'includes/class-telegram.php',
	'includes/class-install.php',
	'includes/class-frontend.php',
	'includes/class-admin.php',
	'includes/class-ajax.php',
	'includes/class-plugin.php',
);
foreach ( $npbr_includes as $npbr_include ) {
	$npbr_path = NPBR_DIR . $npbr_include;
	if ( ! is_readable( $npbr_path ) ) {
		continue;
	}
	require_once $npbr_path;
}

if ( class_exists( 'NPBR_Install', false ) ) {
	register_activation_hook( __FILE__, array( 'NPBR_Install', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'NPBR_Install', 'deactivate' ) );
}

add_action(
	'before_woocommerce_init',
	static function () {
		if ( ! defined( 'NPBR_FILE' ) ) {
			return;
		}
		$feature = 'Automattic\\WooCommerce\\Utilities\\FeaturesUtil';
		if ( class_exists( $feature, false ) ) {
			$feature::declare_compatibility( 'custom_order_tables', NPBR_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'NPBR_Plugin', false ) ) {
			NPBR_Plugin::instance()->init();
		}
	}
);
