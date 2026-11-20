<?php
/**
 * Build a single connected @graph JSON-LD document for the current page.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

use SchemaNova\Schema\Types\Article;
use SchemaNova\Schema\Types\CollectionPage;
use SchemaNova\Schema\Types\Custom;
use SchemaNova\Schema\Types\Event;
use SchemaNova\Schema\Types\FAQPage;
use SchemaNova\Schema\Types\JobPosting;
use SchemaNova\Schema\Types\Organization;
use SchemaNova\Schema\Types\Person;
use SchemaNova\Schema\Types\Product;
use SchemaNova\Schema\Types\ProfilePage;
use SchemaNova\Schema\Types\Recipe;
use SchemaNova\Schema\Types\Video;
use SchemaNova\Schema\Types\WebPage;
use SchemaNova\Schema\Types\WebSite;
use SchemaNova\Support\Arr;

final class Graph
{
    /**
     * @return array<string, mixed>
     */
    public static function build(?Context $ctx = null): array
    {
        $ctx   = $ctx ?? Context::fromWp();
        $nodes = [];

        if ($ctx->feature('organization') && ($ctx->kind === 'home' || (bool) $ctx->settings->get('features.organization', true))) {
            $org = Organization::build($ctx);
            if ($org !== []) {
                $nodes[] = $org;
            }
        }

        if ($ctx->feature('website')) {
            $site = WebSite::build($ctx);
            if ($site !== []) {
                $nodes[] = $site;
            }
        }

        if ($ctx->feature('webpage') && ! in_array($ctx->kind, ['collection', 'profile'], true)) {
            $page = WebPage::build($ctx);
            if ($page !== []) {
                $nodes[] = $page;
            }
        }

        if ($ctx->feature('breadcrumb') && count($ctx->crumbs) >= 2) {
            $nodes[] = self::breadcrumbs($ctx);
        }

        if ($ctx->kind === 'article' && $ctx->feature('article')) {
            $article = Article::build($ctx);
            if ($article !== []) {
                $nodes[] = $article;
            }
        }

        if ($ctx->kind === 'product' && $ctx->feature('product')) {
            $product = Product::build($ctx);
            if ($product !== []) {
                $nodes[] = $product;
            }
        }

        if ($ctx->kind === 'collection') {
            $collection = CollectionPage::build($ctx);
            if ($collection !== []) {
                $nodes[] = $collection;
            }
        }

        if ($ctx->kind === 'profile' && $ctx->feature('profile')) {
            $profile = ProfilePage::build($ctx);
            if ($profile !== []) {
                $nodes[] = $profile;
            }
        }

        if ($ctx->kind === 'home' || ($ctx->author && $ctx->kind === 'article')) {
            $person = Person::build($ctx);
            if ($person !== []) {
                $nodes[] = $person;
            }
        }

        if ($ctx->feature('faq_auto') && $ctx->faqs !== []) {
            $faq = FAQPage::build($ctx);
            if ($faq !== []) {
                $nodes[] = $faq;
            }
        }

        if ($ctx->feature('video_auto') && $ctx->videos !== []) {
            $nodes = array_merge($nodes, Video::buildAll($ctx));
        }

        $nodes = array_merge($nodes, Event::maybe($ctx));
        $nodes = array_merge($nodes, JobPosting::maybe($ctx));
        $nodes = array_merge($nodes, Recipe::maybe($ctx));
        $nodes = array_merge($nodes, Custom::maybe($ctx));

        $nodes = array_values(array_filter($nodes, static fn ($n) => is_array($n) && $n !== []));

        /**
         * Filter the connected JSON-LD graph before output.
         *
         * @param list<array<string, mixed>> $nodes
         * @param Context                    $ctx
         */
        $nodes = apply_filters('schema_nova_graph_nodes', $nodes, $ctx);

        return Arr::compact([
            '@context' => 'https://schema.org',
            '@graph'   => $nodes,
        ]);
    }

    public static function encode(array $graph): string
    {
        $json = wp_json_encode(
            $graph,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_LINE_TERMINATORS
        );

        return is_string($json) ? $json : '{}';
    }

    /**
     * @return array<string, mixed>
     */
    private static function breadcrumbs(Context $ctx): array
    {
        $elements = [];
        foreach ($ctx->crumbs as $i => $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
            ];
            if ($i < count($ctx->crumbs) - 1) {
                $item['item'] = $crumb['url'];
            }
            $elements[] = $item;
        }

        return Node::make('BreadcrumbList', $ctx->entityId('breadcrumb'), [
            'itemListElement' => $elements,
        ]);
    }
}
