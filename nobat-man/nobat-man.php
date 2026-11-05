<?php
/**
 * Plugin Name: نوبت من | Nobat Man
 * Plugin URI:  https://webakery.ir
 * Description: رزرو نوبت مشاوره با تقویم شمسی ایرانی، دسته‌بندی مشکل، پرداخت ووکامرس و پنل مدیریت.
 * Version:     1.0.5
 * Author:      webakery.ir
 * Author URI:  https://webakery.ir
 * Text Domain: nobat-man
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NOBAT_MAN_VERSION', '1.0.5' );
define( 'NOBAT_MAN_FILE', __FILE__ );
define( 'NOBAT_MAN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOBAT_MAN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOBAT_MAN_BASENAME', plugin_basename( __FILE__ ) );

require_once NOBAT_MAN_DIR . 'includes/helpers.php';
require_once NOBAT_MAN_DIR . 'includes/class-jalali.php';
require_once NOBAT_MAN_DIR . 'includes/class-categories.php';
require_once NOBAT_MAN_DIR . 'includes/class-install.php';
require_once NOBAT_MAN_DIR . 'includes/class-admin.php';
require_once NOBAT_MAN_DIR . 'includes/class-frontend.php';
require_once NOBAT_MAN_DIR . 'includes/class-ajax.php';
require_once NOBAT_MAN_DIR . 'includes/class-woocommerce.php';

register_activation_hook( __FILE__, array( 'Nobat_Man_Install', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		Nobat_Man_Admin::init();
		Nobat_Man_Frontend::init();
		Nobat_Man_Ajax::init();
		Nobat_Man_WooCommerce::init();
	}
);
