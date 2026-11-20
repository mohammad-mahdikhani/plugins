<?php
/**
 * Output the optimized document title when enabled.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Frontend;

use SchemaNova\Settings\Repository;

final class Titles
{
    public function register(): void
    {
        if (! Repository::instance()->get('titles.control', true)) {
            return;
        }

        add_filter('pre_get_document_title', [$this, 'filterTitle'], 20);
        add_filter('wp_title', [$this, 'filterTitle'], 20);
    }

    public function filterTitle(string $title): string
    {
        $custom = $this->current();
        return $custom !== '' ? $custom : $title;
    }

    private function current(): string
    {
        if (! is_singular()) {
            return '';
        }
        $id = get_queried_object_id();
        if ($id <= 0) {
            return '';
        }

        $title = (string) get_post_meta($id, '_schema_nova_title', true);
        return $title;
    }
}
