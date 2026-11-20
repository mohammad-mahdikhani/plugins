<?php
/**
 * Detect page kind, FAQs, videos, and default titles without depending on a theme.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

use SchemaNova\Settings\Repository;
use SchemaNova\Support\Text;
use SchemaNova\Support\Url;

final class Detector
{
    public static function kind(): string
    {
        if (is_front_page()) {
            return 'home';
        }
        if (function_exists('is_product') && is_product()) {
            return 'product';
        }
        if (function_exists('is_shop') && is_shop()) {
            return 'collection';
        }
        if (is_singular('product')) {
            return 'product';
        }
        if (is_author()) {
            return 'profile';
        }
        if (is_search()) {
            return 'search';
        }
        if (is_category() || is_tag() || is_tax() || is_post_type_archive() || is_home()) {
            return 'collection';
        }
        if (is_singular()) {
            $post = get_queried_object();
            $type = $post->post_type ?? 'post';
            $map  = Repository::instance()->get('types', []);
            $mapped = is_array($map) ? ($map[$type] ?? '') : '';
            if ($mapped === 'Product') {
                return 'product';
            }
            if (in_array($mapped, ['NewsArticle', 'BlogPosting', 'Article', 'TechArticle'], true)) {
                return 'article';
            }
            if ($type === 'page') {
                return 'page';
            }
            return 'article';
        }

        return 'webpage';
    }

    public static function title(): string
    {
        if (is_singular()) {
            return Text::clean(get_the_title(get_queried_object_id()));
        }
        if (is_front_page()) {
            return Text::clean(get_bloginfo('name'));
        }
        if (function_exists('wp_get_document_title')) {
            return Text::clean(wp_get_document_title());
        }

        return Text::clean(wp_title('', false) ?: get_bloginfo('name'));
    }

    public static function description(): string
    {
        if (is_singular()) {
            $id = get_queried_object_id();
            $meta = (string) get_post_meta($id, '_schema_nova_meta_description', true);
            if ($meta !== '') {
                return Text::clean($meta, 300);
            }
            $excerpt = get_the_excerpt($id);
            if (is_string($excerpt) && $excerpt !== '') {
                return Text::clean($excerpt, 300);
            }
            $post = get_post($id);
            if ($post) {
                return Text::clean((string) $post->post_content, 300);
            }
        }

        if (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term && ! empty($term->description)) {
                return Text::clean((string) $term->description, 300);
            }
        }

        return Text::clean(get_bloginfo('description'), 300);
    }

    /**
     * Pull FAQ pairs from our block, Gutenberg FAQ patterns, and heading+answer pairs.
     *
     * @return list<array{question:string,answer:string}>
     */
    public static function faqs(): array
    {
        if (! is_singular()) {
            return [];
        }

        $post = get_post(get_queried_object_id());
        if (! $post) {
            return [];
        }

        $stored = get_post_meta($post->ID, '_schema_nova', true);
        $items  = [];
        if (is_array($stored) && ! empty($stored['faq']) && is_array($stored['faq'])) {
            foreach ($stored['faq'] as $row) {
                $q = Text::clean((string) ($row['question'] ?? ''));
                $a = Text::clean((string) ($row['answer'] ?? ''));
                if ($q !== '' && $a !== '') {
                    $items[] = ['question' => $q, 'answer' => $a];
                }
            }
        }

        $content = (string) $post->post_content;
        if (has_blocks($content)) {
            $blocks = parse_blocks($content);
            $items  = array_merge($items, self::faqsFromBlocks($blocks));
        }

        $items = array_merge($items, self::faqsFromHeadings($content));

        $unique = [];
        $seen   = [];
        foreach ($items as $item) {
            $key = Text::lower($item['question']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[]   = $item;
        }

        return array_slice($unique, 0, 30);
    }

    /**
     * @param list<array<string, mixed>> $blocks
     * @return list<array{question:string,answer:string}>
     */
    private static function faqsFromBlocks(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $block) {
            $name = $block['blockName'] ?? '';
            $inner = $block['innerBlocks'] ?? [];
            if ($name === 'schema-nova/faq' || $name === 'yoast/faq-block' || $name === 'rank-math/faq-block' || $name === 'yoast/faq') {
                $q = Text::clean((string) ($block['attrs']['question'] ?? ''));
                $a = Text::clean((string) ($block['attrs']['answer'] ?? ''));
                if ($q !== '' && $a !== '') {
                    $out[] = ['question' => $q, 'answer' => $a];
                }
                if (! empty($block['attrs']['questions']) && is_array($block['attrs']['questions'])) {
                    foreach ($block['attrs']['questions'] as $row) {
                        $qq = Text::clean((string) ($row['question'] ?? $row['title'] ?? ''));
                        $aa = Text::clean((string) ($row['answer'] ?? $row['content'] ?? ''));
                        if ($qq !== '' && $aa !== '') {
                            $out[] = ['question' => $qq, 'answer' => $aa];
                        }
                    }
                }
            }
            if (is_array($inner) && $inner !== []) {
                $out = array_merge($out, self::faqsFromBlocks($inner));
            }
        }

        return $out;
    }

    /**
     * @return list<array{question:string,answer:string}>
     */
    private static function faqsFromHeadings(string $html): array
    {
        $out = [];
        if (! preg_match_all('/<h([2-4])[^>]*>(.*?)<\/h\1>\s*(<p[^>]*>.*?<\/p>)/isu', $html, $m, PREG_SET_ORDER)) {
            return $out;
        }

        foreach ($m as $row) {
            $question = Text::clean($row[2]);
            $answer   = Text::clean($row[3], 500);
            if ($question === '' || $answer === '') {
                continue;
            }
            if (! str_contains($question, '?') && ! str_contains($question, '؟')) {
                continue;
            }
            $out[] = ['question' => $question, 'answer' => $answer];
        }

        return $out;
    }

    /**
     * Detect YouTube, Vimeo, Aparat, and core video blocks.
     *
     * @return list<array<string, mixed>>
     */
    public static function videos(): array
    {
        if (! is_singular()) {
            return [];
        }

        $post = get_post(get_queried_object_id());
        if (! $post) {
            return [];
        }

        $html = (string) $post->post_content;
        $out  = [];

        $patterns = [
            'youtube' => '/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([A-Za-z0-9_-]{6,})/i',
            'vimeo'   => '/vimeo\.com\/(?:video\/)?(\d+)/i',
            'aparat'  => '/aparat\.com\/(?:v\/|video\/video\/embed\/videohash\/)([A-Za-z0-9]+)/i',
        ];

        foreach ($patterns as $provider => $pattern) {
            if (! preg_match_all($pattern, $html, $m)) {
                continue;
            }
            foreach (array_unique($m[1]) as $id) {
                $out[] = self::videoNode($provider, (string) $id, $post);
            }
        }

        if (preg_match_all('/<video[^>]+src=["\']([^"\']+)["\']/i', $html, $vm)) {
            foreach ($vm[1] as $src) {
                $out[] = [
                    'name'         => get_the_title($post),
                    'description'  => Text::clean(get_the_excerpt($post) ?: (string) $post->post_content, 200),
                    'thumbnailUrl' => get_the_post_thumbnail_url($post, 'full') ?: '',
                    'uploadDate'   => Node::isoDate($post->post_date_gmt ?: $post->post_date),
                    'contentUrl'   => Url::abs($src),
                ];
            }
        }

        return array_slice($out, 0, 10);
    }

    /**
     * @return array<string, mixed>
     */
    private static function videoNode(string $provider, string $id, \WP_Post $post): array
    {
        $embed = match ($provider) {
            'youtube' => 'https://www.youtube.com/embed/' . rawurlencode($id),
            'vimeo'   => 'https://player.vimeo.com/video/' . rawurlencode($id),
            'aparat'  => 'https://www.aparat.com/video/video/embed/videohash/' . rawurlencode($id) . '/vt/frame',
            default   => '',
        };

        $thumb = match ($provider) {
            'youtube' => 'https://i.ytimg.com/vi/' . rawurlencode($id) . '/hqdefault.jpg',
            default   => get_the_post_thumbnail_url($post, 'full') ?: '',
        };

        return [
            'name'         => get_the_title($post),
            'description'  => Text::clean(get_the_excerpt($post) ?: (string) $post->post_content, 200),
            'thumbnailUrl' => $thumb,
            'uploadDate'   => Node::isoDate($post->post_date_gmt ?: $post->post_date),
            'embedUrl'     => $embed,
        ];
    }
}
