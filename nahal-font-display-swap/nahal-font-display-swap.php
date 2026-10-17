<?php
/**
 * Plugin Name: Nahal Font Display Swap
 * Plugin URI:  https://nahal.website
 * Description: نوع font-display فونت‌های قالب، المنتور و هر منبع دیگر را اجباراً به swap تبدیل می‌کند.
 * Version:     1.0.0
 * Author:      nahal.website
 * Author URI:  https://nahal.website
 * Text Domain: nahal-font-display-swap
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NFDS_VERSION' ) ) {
	define( 'NFDS_VERSION', '1.0.0' );
}
if ( ! defined( 'NFDS_FILE' ) ) {
	define( 'NFDS_FILE', __FILE__ );
}
if ( ! defined( 'NFDS_DIR' ) ) {
	define( 'NFDS_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'NFDS_BASENAME' ) ) {
	define( 'NFDS_BASENAME', plugin_basename( __FILE__ ) );
}

require_once NFDS_DIR . 'includes/class-rewriter.php';
require_once NFDS_DIR . 'includes/class-files.php';
require_once NFDS_DIR . 'includes/class-admin.php';
require_once NFDS_DIR . 'includes/class-plugin.php';

NFDS_Plugin::init();

register_activation_hook( __FILE__, array( 'NFDS_Plugin', 'activate' ) );
