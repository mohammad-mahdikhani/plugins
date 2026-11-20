<?php
/**
 * Collect crawlable images for the current page.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

use SchemaNova\Settings\Repository;
use SchemaNova\Support\Url;

final class Images
{
    /**
     * @return list<array{url:string,name:string}>
     */
    public static function collect(): array
    {
        $out = [];

        if (is_singular()) {
            $id = get_queried_object_id();
            if ($id && has_post_thumbnail($id)) {
                $src = wp_get_attachment_image_src((int) get_post_thumbnail_id($id), 'full');
                if (is_array($src) && ! empty($src[0])) {
                    $out[] = ['url' => (string) $src[0], 'name' => get_the_title($id)];
                }
            }
        }

        $logo = self::logo();
        if ($logo !== '') {
            $out[] = ['url' => $logo, 'name' => get_bloginfo('name')];
        }

        if (is_singular()) {
            $post = get_post(get_queried_object_id());
            if ($post) {
                if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $post->post_content, $m)) {
                    foreach ($m[1] as $src) {
                        $out[] = ['url' => Url::abs($src), 'name' => get_the_title($post)];
                    }
                }
            }
        }

        $unique = [];
        $seen   = [];
        foreach ($out as $item) {
            if ($item['url'] === '' || isset($seen[$item['url']])) {
                continue;
            }
            $seen[$item['url']] = true;
            $unique[]           = $item;
        }

        return array_slice($unique, 0, 8);
    }

    public static function logo(): string
    {
        $logo = (string) Repository::instance()->get('organization.logo', '');
        if ($logo !== '') {
            return Url::abs($logo);
        }

        $custom = get_theme_mod('custom_logo');
        if (is_int($custom) && $custom > 0) {
            $src = wp_get_attachment_image_url($custom, 'full');
            if (is_string($src) && $src !== '') {
                return $src;
            }
        }

        $siteIcon = get_site_icon_url(512);
        return is_string($siteIcon) ? $siteIcon : '';
    }

    /**
     * @param list<array{url:string,name?:string}> $images
     * @return list<string>
     */
    public static function urls(array $images): array
    {
        return array_values(array_filter(array_map(static fn ($img) => $img['url'] ?? '', $images)));
    }
}
