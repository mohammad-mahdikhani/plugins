<?php
/**
 * Plugin Name: Pakan Mental Home
 * Description: صفحهٔ اصلی سبک و ریسپانسیو پاکن منتال — تبدیل خودکار تصاویر به WebP.
 * Version: 1.1.5
 * Author: Pakan Mental
 * Text Domain: pakanmental-home
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PM_HOME_VERSION', '1.1.5' );
define( 'PM_HOME_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_HOME_URL', plugin_dir_url( __FILE__ ) );

require_once PM_HOME_PATH . 'includes/helpers.php';
require_once PM_HOME_PATH . 'includes/class-pm-webp.php';
require_once PM_HOME_PATH . 'includes/class-pm-template.php';
require_once PM_HOME_PATH . 'includes/class-pm-assets.php';
require_once PM_HOME_PATH . 'includes/class-pm-settings.php';
require_once PM_HOME_PATH . 'includes/class-pm-rest.php';
require_once PM_HOME_PATH . 'includes/class-pm-404.php';
require_once PM_HOME_PATH . 'includes/class-pm-deploy.php';
require_once PM_HOME_PATH . 'includes/class-pm-elementor.php';
require_once PM_HOME_PATH . 'includes/class-pm-woocommerce.php';

PM_WebP::init();
PM_Template::init();
PM_Assets::init();
PM_Settings::init();
PM_Rest::init();
PM_404::init();
PM_Deploy::init();
PM_Elementor::init();
PM_WooCommerce::init();
