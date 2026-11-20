<?php
/**
 * Admin CSS/JS.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Admin;

final class Assets
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        $isPlugin = str_contains($hook, 'schema-nova');
        $isPost   = in_array($hook, ['post.php', 'post-new.php'], true);
        if (! $isPlugin && ! $isPost) {
            return;
        }

        wp_enqueue_style(
            'schema-nova-admin',
            SCHEMA_NOVA_URL . 'assets/admin.css',
            [],
            SCHEMA_NOVA_VERSION
        );
        wp_enqueue_script(
            'schema-nova-admin',
            SCHEMA_NOVA_URL . 'assets/admin.js',
            ['jquery'],
            SCHEMA_NOVA_VERSION,
            true
        );
        wp_localize_script('schema-nova-admin', 'schemaNova', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('schema_nova_ajax'),
            'i18n'  => [
                'running' => sn__('Working…'),
                'ok'      => sn__('Done'),
                'fail'    => sn__('Failed'),
            ],
        ]);
    }
}
