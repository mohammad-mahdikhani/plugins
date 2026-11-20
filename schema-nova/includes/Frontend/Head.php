<?php
/**
 * Print a single JSON-LD graph. Theme-agnostic: hooks wp_head (or wp_footer).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Frontend;

use SchemaNova\Schema\Graph;
use SchemaNova\Settings\Repository;

final class Head
{
    public function register(): void
    {
        $hook = Repository::instance()->get('output.in_footer', false) ? 'wp_footer' : 'wp_head';
        add_action($hook, [$this, 'render'], 92);
    }

    public function render(): void
    {
        if (is_admin() || wp_is_json_request() || is_feed() || is_robots() || is_trackback()) {
            return;
        }

        if (apply_filters('schema_nova_skip_output', false)) {
            return;
        }

        $graph = Graph::build();
        if (empty($graph['@graph'])) {
            return;
        }

        $json = Graph::encode($graph);
        echo '<script type="application/ld+json" class="schema-nova-graph">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $json . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD, encoded via wp_json_encode.
        echo '</script>' . "\n";
    }
}
