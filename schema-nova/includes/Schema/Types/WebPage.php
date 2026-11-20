<?php
/**
 * WebPage / SearchResultsPage / CollectionPage wrapper.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;

final class WebPage
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        $type = $ctx->kind === 'search' ? 'SearchResultsPage' : 'WebPage';

        $imageUrls = Images::urls($ctx->images);
        $primary   = $imageUrls[0] ?? '';

        $data = [
            'url'                 => $ctx->url,
            'name'                => $ctx->headline(),
            'description'         => $ctx->description,
            'inLanguage'          => $ctx->language,
            'isPartOf'            => Node::ref($ctx->siteId()),
            'about'               => Node::ref($ctx->orgId()),
            'primaryImageOfPage'  => $primary !== ''
                ? Node::make('ImageObject', $ctx->entityId('primaryimage'), array_filter([
                    'url'            => $primary,
                    'contentUrl'     => $primary,
                    'creator'        => $ctx->feature('image_metadata') ? Node::ref($ctx->orgId()) : null,
                    'creditText'     => $ctx->feature('image_metadata') ? get_bloginfo('name') : null,
                    'copyrightNotice'=> $ctx->feature('image_metadata') ? get_bloginfo('name') : null,
                ]))
                : '',
        ];

        if (count($ctx->crumbs) >= 2) {
            $data['breadcrumb'] = Node::ref($ctx->entityId('breadcrumb'));
        }

        if ($ctx->post) {
            $data['datePublished'] = Node::isoDate($ctx->post->post_date_gmt ?: $ctx->post->post_date);
            $data['dateModified']  = Node::isoDate($ctx->post->post_modified_gmt ?: $ctx->post->post_modified);
            if ($ctx->author) {
                $data['author'] = Node::ref($ctx->entityId('author'));
            }
        }

        if ($ctx->kind === 'article') {
            $data['mainEntity'] = Node::ref($ctx->entityId('article'));
        } elseif ($ctx->kind === 'product') {
            $data['mainEntity'] = Node::ref($ctx->entityId('product'));
        } elseif ($ctx->kind === 'profile') {
            $data['mainEntity'] = Node::ref($ctx->entityId('person'));
        }

        if ((bool) $ctx->settings->get('features.speakable', false) && $ctx->kind === 'article') {
            $data['speakable'] = [
                '@type' => 'SpeakableSpecification',
                'cssSelector' => ['h1', '.entry-content p', 'article p'],
            ];
        }

        return Node::make($type, $ctx->pageId(), $data);
    }
}
