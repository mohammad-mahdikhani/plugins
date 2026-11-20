<?php
/**
 * CollectionPage + ItemList for archives.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class CollectionPage
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        $items = [];
        global $wp_query;
        if (isset($wp_query->posts) && is_array($wp_query->posts)) {
            $i = 1;
            foreach (array_slice($wp_query->posts, 0, 20) as $post) {
                $url = get_permalink($post);
                if (! is_string($url) || $url === '') {
                    continue;
                }
                $items[] = [
                    '@type'    => 'ListItem',
                    'position' => $i++,
                    'url'      => $url,
                    'name'     => Text::clean(get_the_title($post)),
                ];
            }
        }

        $data = [
            'url'         => $ctx->url,
            'name'        => $ctx->title,
            'description' => $ctx->description,
            'isPartOf'    => Node::ref($ctx->siteId()),
        ];

        if ($items !== []) {
            $data['mainEntity'] = [
                '@type'           => 'ItemList',
                'numberOfItems'   => count($items),
                'itemListElement' => $items,
            ];
        }

        return Node::make('CollectionPage', $ctx->entityId('collection'), $data);
    }
}
