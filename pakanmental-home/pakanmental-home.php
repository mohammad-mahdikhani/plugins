<?php
/**
 * Plugin Name: Pakan Mental Home
 * Description: صفحهٔ اصلی سبک و ریسپانسیو پاکن منتال — تبدیل خودکار تصاویر به WebP.
 * Version: 1.2.4
 * Author: Pakan Mental
 * Text Domain: pakanmental-home
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PM_HOME_VERSION', '1.2.4' );
define( 'PM_HOME_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_HOME_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load a plugin file when it is present. A missing file must not take the shop offline.
 *
 * @param string $relative Path relative to the plugin root.
 * @param string $class    Class that should exist after the file loads.
 */
function pm_home_boot( $relative, $class ) {
	$path = PM_HOME_PATH . $relative;
	if ( is_readable( $path ) ) {
		require_once $path;
	}
	if ( class_exists( $class ) ) {
		$class::init();
	}
}

pm_home_boot( 'includes/helpers.php', 'PM_Home_Helpers' );
pm_home_boot( 'includes/class-pm-webp.php', 'PM_WebP' );
pm_home_boot( 'includes/class-pm-template.php', 'PM_Template' );
pm_home_boot( 'includes/class-pm-assets.php', 'PM_Assets' );
pm_home_boot( 'includes/class-pm-settings.php', 'PM_Settings' );
pm_home_boot( 'includes/class-pm-rest.php', 'PM_Rest' );
pm_home_boot( 'includes/class-pm-elementor.php', 'PM_Elementor' );
pm_home_boot( 'includes/class-pm-deploy.php', 'PM_Deploy' );
pm_home_boot( 'includes/class-pm-woocommerce.php', 'PM_WooCommerce' );
pm_home_boot( 'includes/class-pm-account-orders.php', 'PM_Account_Orders' );
pm_home_boot( 'includes/class-pm-checkout.php', 'PM_Checkout' );
pm_home_boot( 'includes/class-pm-product-gallery.php', 'PM_Product_Gallery' );
pm_home_boot( 'includes/class-pm-shortcodes.php', 'PM_Shortcodes' );
pm_home_boot( 'includes/class-pm-chrome.php', 'PM_Chrome' );
pm_home_boot( 'includes/class-pm-product-content.php', 'PM_Product_Content' );
pm_home_boot( 'includes/class-pm-game-specs.php', 'PM_Game_Specs' );
pm_home_boot( 'includes/class-pm-digits.php', 'PM_Digits' );
