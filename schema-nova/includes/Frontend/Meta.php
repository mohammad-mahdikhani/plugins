<?php
/**
 * Meta description + Open Graph alignment.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Frontend;

use SchemaNova\Schema\Detector;
use SchemaNova\Settings\Repository;
use SchemaNova\Support\Text;

final class Meta
{
    public function register(): void
    {
        if (! Repository::instance()->get('titles.control', true)) {
            return;
        }
        add_action('wp_head', [$this, 'render'], 1);
    }

    public function render(): void
    {
        if (is_admin() || is_feed()) {
            return;
        }

        $description = $this->description();
        if ($description === '') {
            return;
        }

        printf(
            '<meta name="description" content="%s" />' . "\n",
            esc_attr($description)
        );

        if (Repository::instance()->get('titles.sync_og', true)) {
            $title = is_singular() ? (string) get_post_meta(get_queried_object_id(), '_schema_nova_title', true) : '';
            if ($title === '') {
                $title = Detector::title();
            }
            printf('<meta property="og:title" content="%s" />' . "\n", esc_attr($title));
            printf('<meta property="og:description" content="%s" />' . "\n", esc_attr($description));
            printf('<meta property="og:url" content="%s" />' . "\n", esc_url(is_singular() ? (string) get_permalink() : home_url('/')));
            printf('<meta name="twitter:card" content="summary_large_image" />' . "\n");
            printf('<meta name="twitter:title" content="%s" />' . "\n", esc_attr($title));
            printf('<meta name="twitter:description" content="%s" />' . "\n", esc_attr($description));
        }
    }

    private function description(): string
    {
        if (is_singular()) {
            $id   = get_queried_object_id();
            $meta = (string) get_post_meta($id, '_schema_nova_meta_description', true);
            if ($meta !== '') {
                return Text::clean($meta, 180);
            }
        }

        return Text::clean(Detector::description(), 180);
    }
}
