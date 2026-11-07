<?php
/**
 * Plugin Name: Pakan Mental Product Shell
 * Description: قالب ثابت پاکان برای همه محصولات و دسته‌بندی‌های ووکامرس (بدون المنتور تکی).
 * Version: 1.0.0
 * Author: Pakan Mental
 * Text Domain: pakanmental-shell
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PM_SHELL_VERSION', '1.0.0' );
define( 'PM_SHELL_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_SHELL_URL', plugin_dir_url( __FILE__ ) );
define( 'PM_SHELL_LOGO', 'https://www.pakanmental.com/wp-content/uploads/2026/10/logo-pakanmental.webp' );

require_once PM_SHELL_PATH . 'includes/class-pm-shell-deploy.php';
require_once PM_SHELL_PATH . 'includes/class-pm-shell-chrome.php';

PM_Shell_Deploy::init();
PM_Shell_Chrome::init();
