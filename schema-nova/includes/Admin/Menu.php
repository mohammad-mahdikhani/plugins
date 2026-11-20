<?php
/**
 * Admin screens.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Admin;

final class Menu
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'pages']);
        add_action('admin_init', [$this, 'handlePost']);
        add_filter('plugin_action_links_' . SCHEMA_NOVA_BASENAME, [$this, 'links']);
    }

    public function pages(): void
    {
        add_menu_page(
            sn__('Schema Nova'),
            sn__('هوش‌اسکیما'),
            'manage_options',
            'schema-nova',
            [$this, 'render'],
            'dashicons-visibility',
            58
        );

        add_submenu_page('schema-nova', sn__('Dashboard'), sn__('Dashboard'), 'manage_options', 'schema-nova', [$this, 'render']);
        add_submenu_page('schema-nova', sn__('Organization'), sn__('Organization'), 'manage_options', 'schema-nova-org', [$this, 'render']);
        add_submenu_page('schema-nova', sn__('Schema types'), sn__('Schema types'), 'manage_options', 'schema-nova-types', [$this, 'render']);
        add_submenu_page('schema-nova', sn__('SERP optimizer'), sn__('SERP optimizer'), 'manage_options', 'schema-nova-optimizer', [$this, 'render']);
        add_submenu_page('schema-nova', sn__('Data source'), sn__('Data source'), 'manage_options', 'schema-nova-connect', [$this, 'render']);
        add_submenu_page('schema-nova', sn__('Tools'), sn__('Tools'), 'manage_options', 'schema-nova-tools', [$this, 'render']);
    }

    /**
     * @param list<string> $links
     * @return list<string>
     */
    public function links(array $links): array
    {
        array_unshift(
            $links,
            '<a href="' . esc_url(admin_url('admin.php?page=schema-nova')) . '">' . esc_html(sn__('Open')) . '</a>'
        );
        return $links;
    }

    public function handlePost(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        if (empty($_POST['schema_nova_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['schema_nova_nonce'])), 'schema_nova_save')) {
            return;
        }

        $page = sanitize_key((string) ($_POST['schema_nova_page'] ?? ''));
        Pages::save($page, wp_unslash($_POST));
        wp_safe_redirect(add_query_arg(['page' => $page ?: 'schema-nova', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $page = sanitize_key((string) ($_GET['page'] ?? 'schema-nova'));
        echo '<div class="wrap schema-nova-wrap" dir="auto">';
        Pages::render($page);
        echo '</div>';
    }
}
