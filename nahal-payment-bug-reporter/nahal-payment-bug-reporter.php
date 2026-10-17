<?php
/**
 * Plugin Name: گزارشگر خطای پرداخت نهال
 * Plugin URI:  https://nahal.website
 * Description: دکمه گزارش خطا در صفحات و فیلدهای پرداخت؛ ثبت کدهای لودنشده، اسکرین‌شات صفحه و ارسال برای مدیر.
 * Version:     1.0.0
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

define( 'NPBR_VERSION', '1.0.0' );
define( 'NPBR_FILE', __FILE__ );
define( 'NPBR_DIR', plugin_dir_path( __FILE__ ) );
define( 'NPBR_URL', plugin_dir_url( __FILE__ ) );
define( 'NPBR_BASENAME', plugin_basename( __FILE__ ) );

require_once NPBR_DIR . 'includes/class-sanitizer.php';
require_once NPBR_DIR . 'includes/class-report.php';
require_once NPBR_DIR . 'includes/class-mailer.php';
require_once NPBR_DIR . 'includes/class-install.php';
require_once NPBR_DIR . 'includes/class-frontend.php';
require_once NPBR_DIR . 'includes/class-admin.php';
require_once NPBR_DIR . 'includes/class-ajax.php';
require_once NPBR_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'NPBR_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NPBR_Install', 'deactivate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', NPBR_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function () {
		NPBR_Plugin::instance()->init();
	}
);
