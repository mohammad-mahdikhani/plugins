<?php
/**
 * Article / NewsArticle / BlogPosting.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class Article
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        if (! $ctx->post) {
            return [];
        }

        $mapped = (string) $ctx->settings->get('types.' . $ctx->postType, 'BlogPosting');
        $type   = $ctx->override('schema_type', $mapped);
        if (! in_array($type, ['Article', 'NewsArticle', 'BlogPosting', 'TechArticle'], true)) {
            $kind = (string) $ctx->settings->get('site.kind', 'mixed');
            $type = $kind === 'news' ? 'NewsArticle' : 'BlogPosting';
        }

        $imageUrls = Images::urls($ctx->images);
        // Prefer content/featured images over the logo for Article.image.
        $logo = Images::logo();
        $articleImages = array_values(array_filter($imageUrls, static fn ($u) => $u !== $logo));
        if ($articleImages === [] && $imageUrls !== []) {
            $articleImages = $imageUrls;
        }

        $wordCount = str_word_count(wp_strip_all_tags((string) $ctx->post->post_content));

        $data = [
            'headline'         => $ctx->headline(),
            'alternativeHeadline' => Text::clean((string) $ctx->override('alternative_headline', ''), 110),
            'description'      => $ctx->description,
            'image'            => $articleImages,
            'datePublished'    => Node::isoDate($ctx->post->post_date_gmt ?: $ctx->post->post_date),
            'dateModified'     => Node::isoDate($ctx->post->post_modified_gmt ?: $ctx->post->post_modified),
            'mainEntityOfPage' => Node::ref($ctx->pageId()),
            'isPartOf'         => Node::ref($ctx->pageId()),
            'inLanguage'       => $ctx->language,
            'publisher'        => Node::ref($ctx->orgId()),
            'wordCount'        => $wordCount > 0 ? $wordCount : null,
            'articleSection'   => self::section($ctx),
            'keywords'         => self::keywords($ctx),
        ];

        if ($ctx->author) {
            $data['author'] = [
                '@type' => 'Person',
                '@id'   => $ctx->entityId('author'),
                'name'  => $ctx->author->display_name,
                'url'   => get_author_posts_url($ctx->author->ID),
            ];
        }

        return Node::make((string) $type, $ctx->entityId('article'), $data);
    }

    private static function section(Context $ctx): string
    {
        if (! $ctx->post) {
            return '';
        }
        $cats = get_the_category($ctx->post->ID);
        if (is_array($cats) && isset($cats[0]->name)) {
            return $cats[0]->name;
        }

        return '';
    }

    private static function keywords(Context $ctx): string
    {
        if (! $ctx->post) {
            return '';
        }
        $tags = get_the_tags($ctx->post->ID);
        if (! is_array($tags)) {
            return '';
        }

        return implode(', ', array_map(static fn ($t) => $t->name, $tags));
    }
}
