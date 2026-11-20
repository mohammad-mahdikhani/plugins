<?php
/**
 * Optional duplicate JSON-LD suppression for Rank Math, Yoast, AIOSEO, SEOPress.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Compat;

use SchemaNova\Settings\Repository;

final class Competitors
{
    public function register(): void
    {
        if (! Repository::instance()->get('output.disable_competitors', false)) {
            return;
        }

        add_filter('rank_math/json_ld', static fn () => [], 99);
        add_filter('wpseo_json_ld_output', '__return_false', 99);
        add_filter('aioseo_schema_disable', '__return_true', 99);
        add_filter('seopress_schemas_enable', '__return_false', 99);
        add_filter('wp_schema_pro_enabled', '__return_false', 99);
    }
}
