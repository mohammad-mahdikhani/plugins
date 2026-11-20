<?php
/**
 * Minimal WordPress stubs so Schema Nova unit tests run without WP.
 */

declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
define('SCHEMA_NOVA_VERSION', '1.1');
define('SCHEMA_NOVA_DIR', dirname(__DIR__) . '/');
define('SCHEMA_NOVA_URL', 'http://example.com/wp-content/plugins/schema-nova/');
define('SCHEMA_NOVA_FILE', SCHEMA_NOVA_DIR . 'schema-nova.php');
define('SCHEMA_NOVA_BASENAME', 'schema-nova/schema-nova.php');
define('HOUR_IN_SECONDS', 3600);
define('DAY_IN_SECONDS', 86400);

$GLOBALS['sn_options'] = [];
$GLOBALS['sn_filters'] = [];

function add_action($hook, $cb, $pri = 10, $args = 1): void {}
function add_filter($hook, $cb, $pri = 10, $args = 1): void {
    $GLOBALS['sn_filters'][$hook][] = $cb;
}
function apply_filters($hook, $value, ...$args) {
    foreach ($GLOBALS['sn_filters'][$hook] ?? [] as $cb) {
        $value = $cb($value, ...$args);
    }
    return $value;
}
function get_option($key, $default = false) {
    return $GLOBALS['sn_options'][$key] ?? $default;
}
function update_option($key, $value, $autoload = true): bool {
    $GLOBALS['sn_options'][$key] = $value;
    return true;
}
function get_bloginfo($show = '') {
    return match ($show) {
        'name' => 'Demo Shop',
        'description' => 'Persian demo store',
        'language' => 'fa-IR',
        default => 'Demo Shop',
    };
}
function home_url($path = '/') {
    return 'https://example.com' . (str_starts_with((string) $path, '/') ? $path : '/' . $path);
}
function trailingslashit($s) { return rtrim((string) $s, '/') . '/'; }
function untrailingslashit($s) { return rtrim((string) $s, '/'); }
function user_trailingslashit($s) { return trailingslashit($s); }
function wp_json_encode($data, $flags = 0) { return json_encode($data, $flags); }
function wp_strip_all_tags($s) { return trim(strip_tags((string) $s)); }
function wp_parse_url($url) { return parse_url($url); }
function wp_timezone_string() { return 'Asia/Tehran'; }
function sanitize_text_field($s) { return trim(strip_tags((string) $s)); }
function sanitize_textarea_field($s) { return (string) $s; }
function sanitize_key($s) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string) $s)); }
function __($s, $d = '') { return $s; }
function translate($s, $d = '') { return $s; }
function load_plugin_textdomain($d, $a = false, $p = '') {}
function is_admin() { return false; }
function is_front_page() { return true; }
function is_home() { return false; }
function is_singular($t = '') { return false; }
function is_search() { return false; }
function is_author() { return false; }
function is_category() { return false; }
function is_tag() { return false; }
function is_tax() { return false; }
function is_post_type_archive() { return false; }
function is_feed() { return false; }
function is_robots() { return false; }
function is_trackback() { return false; }
function wp_is_json_request() { return false; }
function get_queried_object() { return null; }
function get_queried_object_id() { return 0; }
function get_locale() { return 'fa_IR'; }
function determine_locale() { return 'fa_IR'; }
function is_rtl() { return true; }
function current_time($t) { return gmdate('Y-m-d H:i:s'); }
function wp_http_validate_url($url) { return filter_var($url, FILTER_VALIDATE_URL) ? $url : false; }

require_once SCHEMA_NOVA_DIR . 'includes/Autoloader.php';
SchemaNova\Autoloader::register();
require_once SCHEMA_NOVA_DIR . 'includes/functions.php';
