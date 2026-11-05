<?php
/**
 * Plugin Name: Pakan Mental Home
 * Description: صفحهٔ اصلی سبک و ریسپانسیو پاکن منتال — تبدیل خودکار تصاویر به WebP.
 * Version: 1.1.9
 * Author: Pakan Mental
 * Text Domain: pakanmental-home
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PM_HOME_VERSION', '1.1.9' );
define( 'PM_HOME_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_HOME_URL', plugin_dir_url( __FILE__ ) );

require_once PM_HOME_PATH . 'includes/helpers.php';
require_once PM_HOME_PATH . 'includes/class-pm-webp.php';
require_once PM_HOME_PATH . 'includes/class-pm-template.php';
require_once PM_HOME_PATH . 'includes/class-pm-assets.php';
require_once PM_HOME_PATH . 'includes/class-pm-settings.php';
require_once PM_HOME_PATH . 'includes/class-pm-rest.php';
require_once PM_HOME_PATH . 'includes/class-pm-elementor.php';
require_once PM_HOME_PATH . 'includes/class-pm-woocommerce.php';
require_once PM_HOME_PATH . 'includes/class-pm-shortcodes.php';
require_once PM_HOME_PATH . 'includes/class-pm-chrome.php';
require_once PM_HOME_PATH . 'includes/class-pm-product-content.php';
require_once PM_HOME_PATH . 'includes/class-pm-game-specs.php';

PM_WebP::init();
PM_Template::init();
PM_Assets::init();
PM_Settings::init();
PM_Rest::init();
PM_Elementor::init();
PM_WooCommerce::init();
PM_Shortcodes::init();
PM_Chrome::init();
PM_Product_Content::init();
PM_Game_Specs::init();
