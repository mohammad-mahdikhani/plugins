<?php
/**
 * Plugin Name: Elinor Beauties Homepage
 * Description: صفحه اصلی فروشگاه با الهام از تم Beauties و رنگ اصلی #341555
 * Version: 1.0.0
 * Author: Elinor Store
 * Text Domain: elinor-beauties-homepage
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

define('ELINOR_BH_VERSION', '1.0.0');
define('ELINOR_BH_PATH', plugin_dir_path(__FILE__));
define('ELINOR_BH_URL', plugin_dir_url(__FILE__));

require_once ELINOR_BH_PATH . 'includes/class-elinor-beauties-homepage.php';

Elinor_Beauties_Homepage::init();
