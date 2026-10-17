<?php
/**
 * Plugin Name: Nahal 3D Maintenance
 * Plugin URI:  https://nahal.website
 * Description: صفحه تعمیر سه‌بعدی نهال — وقتی سایت در حال به‌روزرسانی است، بازدیدکننده یک صحنه شیشه‌ای و سه‌بعدی می‌بیند.
 * Version:     1.0.0
 * Author:      nahal.website
 * Author URI:  https://nahal.website
 * Text Domain: nahal-maintenance
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NM_VERSION' ) ) {
	define( 'NM_VERSION', '1.0.0' );
}
if ( ! defined( 'NM_FILE' ) ) {
	define( 'NM_FILE', __FILE__ );
}
if ( ! defined( 'NM_DIR' ) ) {
	define( 'NM_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'NM_URL' ) ) {
	define( 'NM_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'NM_BASENAME' ) ) {
	define( 'NM_BASENAME', plugin_basename( __FILE__ ) );
}

$nm_includes = array(
	'includes/class-settings.php',
	'includes/class-page.php',
	'includes/class-install.php',
	'includes/class-frontend.php',
	'includes/class-admin.php',
	'includes/class-plugin.php',
);
foreach ( $nm_includes as $nm_include ) {
	$nm_path = NM_DIR . $nm_include;
	if ( ! is_readable( $nm_path ) ) {
		continue;
	}
	require_once $nm_path;
}

if ( class_exists( 'NM_Install', false ) ) {
	register_activation_hook( __FILE__, array( 'NM_Install', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'NM_Install', 'deactivate' ) );
}

add_action(
	'plugins_loaded',
	static function () {
		if ( class_exists( 'NM_Plugin', false ) ) {
			NM_Plugin::instance()->init();
		}
	}
);
