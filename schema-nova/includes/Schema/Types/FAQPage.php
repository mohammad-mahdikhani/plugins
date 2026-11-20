<?php
/**
 * FAQPage. Google currently limits FAQ rich results to a small set of
 * government/health sites, but the markup still helps understand the page
 * and remains valid schema.org.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;

final class FAQPage
{
    /**
     * @return array<string, mixed>
     */
    public static function build(Context $ctx): array
    {
        if ($ctx->faqs === []) {
            return [];
        }

        $entities = [];
        foreach ($ctx->faqs as $row) {
            $entities[] = [
                '@type'          => 'Question',
                'name'           => $row['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $row['answer'],
                ],
            ];
        }

        return Node::make('FAQPage', $ctx->entityId('faq'), [
            'mainEntity'       => $entities,
            'isPartOf'         => Node::ref($ctx->pageId()),
            'inLanguage'       => $ctx->language,
        ]);
    }
}
