<?php
/**
 * Plugin Name:       Schema Nova
 * Plugin URI:        https://webakery.ir
 * Description:       هوش‌اسکیما: اسکیمای JSON-LD کامل مطابق مستندات گوگل، برای هر قالبی، به‌علاوه به‌روزرسانی هوشمند تایتل و متا بر اساس اینتنت جستجو و دادهٔ آنالیتیکس/سرچ کنسول.
 * Version:           1.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            webakery.ir
 * Author URI:        https://webakery.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       schema-nova
 * Domain Path:       /languages
 *
 * @package SchemaNova
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('SCHEMA_NOVA_VERSION', '1.1');
define('SCHEMA_NOVA_FILE', __FILE__);
define('SCHEMA_NOVA_DIR', plugin_dir_path(__FILE__));
define('SCHEMA_NOVA_URL', plugin_dir_url(__FILE__));
define('SCHEMA_NOVA_BASENAME', plugin_basename(__FILE__));

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>Schema Nova نیاز به PHP ۸.۰ یا بالاتر دارد.</p></div>';
    });
    return;
}

require_once SCHEMA_NOVA_DIR . 'includes/Autoloader.php';
SchemaNova\Autoloader::register();

require_once SCHEMA_NOVA_DIR . 'includes/functions.php';

register_activation_hook(__FILE__, [SchemaNova\Install::class, 'activate']);
register_deactivation_hook(__FILE__, [SchemaNova\Install::class, 'deactivate']);

add_action('plugins_loaded', static function (): void {
    SchemaNova\Plugin::instance()->boot();
});
