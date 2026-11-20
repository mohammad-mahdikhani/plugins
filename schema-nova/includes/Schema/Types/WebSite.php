<?php
/**
 * WebSite + optional Sitelinks search box.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;

final class WebSite
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        $data = [
            'url'         => $ctx->home,
            'name'        => get_bloginfo('name'),
            'alternateName'=> (string) $ctx->settings->get('organization.alternate_name', ''),
            'description' => get_bloginfo('description'),
            'inLanguage'  => $ctx->language,
            'publisher'   => Node::ref($ctx->orgId()),
        ];

        if ((bool) $ctx->settings->get('site.sitelinks_search', true)) {
            $path   = (string) $ctx->settings->get('site.search_path', '?s={search_term_string}');
            $target = $ctx->home . ltrim($path, '/');
            if (! str_contains($target, '{search_term_string}')) {
                $target = trailingslashit($ctx->home) . '?s={search_term_string}';
            }
            $data['potentialAction'] = [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $target,
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        return Node::make('WebSite', $ctx->siteId(), $data);
    }
}
