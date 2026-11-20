<?php
/**
 * Theme-agnostic breadcrumb trail. Prefers native WP, then popular SEO
 * plugins, then a sensible URL/taxonomy fallback.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

final class Breadcrumbs
{
    /**
     * @return list<array{name:string,url:string}>
     */
    public static function collect(): array
    {
        $home = [
            'name' => get_bloginfo('name') ?: __('Home', 'schema-nova'),
            'url'  => home_url('/'),
        ];

        $trail = [$home];

        if (is_front_page()) {
            return $trail;
        }

        if (is_home() && ! is_front_page()) {
            $pageId = (int) get_option('page_for_posts');
            $trail[] = [
                'name' => $pageId ? get_the_title($pageId) : __('Blog', 'schema-nova'),
                'url'  => get_permalink($pageId) ?: get_post_type_archive_link('post') ?: home_url('/'),
            ];
            return $trail;
        }

        if (is_singular()) {
            $post = get_queried_object();
            if ($post && isset($post->post_type) && $post->post_type !== 'page' && $post->post_type !== 'post') {
                $archive = get_post_type_archive_link($post->post_type);
                $object  = get_post_type_object($post->post_type);
                if ($archive && $object) {
                    $trail[] = ['name' => $object->labels->name, 'url' => $archive];
                }
            }

            if (isset($post->post_type) && $post->post_type === 'post') {
                $cats = get_the_category($post->ID);
                if (is_array($cats) && $cats !== []) {
                    $cat     = $cats[0];
                    $parents = array_reverse(get_ancestors($cat->term_id, 'category'));
                    foreach ($parents as $parentId) {
                        $term = get_term((int) $parentId, 'category');
                        if ($term && ! is_wp_error($term)) {
                            $trail[] = ['name' => $term->name, 'url' => get_term_link($term)];
                        }
                    }
                    $trail[] = ['name' => $cat->name, 'url' => get_term_link($cat)];
                }
            }

            if (isset($post->post_parent) && (int) $post->post_parent > 0) {
                $ancestors = array_reverse(get_post_ancestors($post));
                foreach ($ancestors as $ancestorId) {
                    $trail[] = [
                        'name' => get_the_title((int) $ancestorId),
                        'url'  => get_permalink((int) $ancestorId),
                    ];
                }
            }

            $trail[] = [
                'name' => get_the_title($post),
                'url'  => get_permalink($post),
            ];

            return self::normalize($trail);
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && isset($term->term_id)) {
                $taxonomy = $term->taxonomy ?? 'category';
                $parents  = array_reverse(get_ancestors((int) $term->term_id, $taxonomy));
                foreach ($parents as $parentId) {
                    $parent = get_term((int) $parentId, $taxonomy);
                    if ($parent && ! is_wp_error($parent)) {
                        $link = get_term_link($parent);
                        if (! is_wp_error($link)) {
                            $trail[] = ['name' => $parent->name, 'url' => $link];
                        }
                    }
                }
                $link = get_term_link($term);
                if (! is_wp_error($link)) {
                    $trail[] = ['name' => $term->name, 'url' => $link];
                }
            }
            return self::normalize($trail);
        }

        if (is_search()) {
            $trail[] = [
                'name' => sprintf(__('Search: %s', 'schema-nova'), get_search_query()),
                'url'  => get_search_link(),
            ];
        } elseif (is_author()) {
            $author = get_queried_object();
            $trail[] = [
                'name' => $author->display_name ?? __('Author', 'schema-nova'),
                'url'  => get_author_posts_url((int) get_queried_object_id()),
            ];
        } elseif (is_post_type_archive()) {
            $object = get_queried_object();
            $trail[] = [
                'name' => $object->labels->name ?? wp_title('', false),
                'url'  => get_post_type_archive_link($object->name ?? ''),
            ];
        }

        return self::normalize($trail);
    }

    /**
     * @param list<array{name:string,url:string}> $trail
     * @return list<array{name:string,url:string}>
     */
    private static function normalize(array $trail): array
    {
        $out  = [];
        $seen = [];
        foreach ($trail as $crumb) {
            $url  = is_string($crumb['url'] ?? null) ? $crumb['url'] : '';
            $name = is_string($crumb['name'] ?? null) ? wp_strip_all_tags($crumb['name']) : '';
            if ($url === '' || $name === '' || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $out[]      = ['name' => $name, 'url' => $url];
        }

        return $out;
    }
}
