<?php
/**
 * Admin AJAX: test connector, sync, optimize, apply proposal.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Admin;

use SchemaNova\Connectors\Manager;
use SchemaNova\Optimizer\Engine;
use SchemaNova\Optimizer\History;
use SchemaNova\Schema\Graph;
use SchemaNova\Schema\Validator;

final class Ajax
{
    public function register(): void
    {
        add_action('wp_ajax_schema_nova_test', [$this, 'test']);
        add_action('wp_ajax_schema_nova_sync', [$this, 'sync']);
        add_action('wp_ajax_schema_nova_optimize', [$this, 'optimize']);
        add_action('wp_ajax_schema_nova_apply', [$this, 'apply']);
        add_action('wp_ajax_schema_nova_preview', [$this, 'preview']);
    }

    public function test(): void
    {
        $this->guard();
        $result = (new Manager())->test();
        wp_send_json($result);
    }

    public function sync(): void
    {
        $this->guard();
        wp_send_json((new Manager())->sync());
    }

    public function optimize(): void
    {
        $this->guard();
        wp_send_json((new Engine())->run(false));
    }

    public function apply(): void
    {
        $this->guard();
        $postId = (int) ($_POST['post_id'] ?? 0);
        $title  = sanitize_text_field((string) ($_POST['title'] ?? ''));
        $desc   = sanitize_textarea_field((string) ($_POST['description'] ?? ''));
        if ($postId <= 0 || $title === '') {
            wp_send_json_error(['message' => 'Missing data']);
        }
        (new Engine())->apply($postId, ['title' => $title, 'description' => $desc]);
        History::add([
            'object_id'  => $postId,
            'page_url'   => (string) get_permalink($postId),
            'field_name' => 'title',
            'old_value'  => get_the_title($postId),
            'new_value'  => $title,
            'reason'     => 'Manual apply from editor',
            'mode'       => 'manual',
            'applied'    => true,
        ]);
        wp_send_json_success(['ok' => true]);
    }

    public function preview(): void
    {
        $this->guard();
        $graph  = Graph::build();
        $issues = Validator::check($graph);
        wp_send_json([
            'json'   => Graph::encode($graph),
            'issues' => $issues,
            'score'  => Validator::score($issues),
        ]);
    }

    private function guard(): void
    {
        if (! current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'forbidden'], 403);
        }
        check_ajax_referer('schema_nova_ajax', 'nonce');
    }
}
