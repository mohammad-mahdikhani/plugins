<?php
/**
 * Extra types driven by the per-page schema picker: Course, SoftwareApplication,
 * Dataset, Review, Service, QAPage.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Images;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class Custom
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function maybe(Context $ctx): array
    {
        $type = (string) ($ctx->overrides['schema_type'] ?? '');
        return match ($type) {
            'Course'              => [self::course($ctx)],
            'SoftwareApplication' => [self::software($ctx)],
            'Dataset'             => [self::dataset($ctx)],
            'Review'              => [self::review($ctx)],
            'Service'             => [self::service($ctx)],
            'QAPage'              => [self::qa($ctx)],
            default               => [],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function course(Context $ctx): array
    {
        return Node::make('Course', $ctx->entityId('course'), [
            'name'        => $ctx->headline(),
            'description' => $ctx->description,
            'provider'    => Node::ref($ctx->orgId()),
            'url'         => $ctx->url,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function software(Context $ctx): array
    {
        $o = $ctx->overrides;
        return Node::make('SoftwareApplication', $ctx->entityId('software'), [
            'name'                => $ctx->headline(),
            'description'         => $ctx->description,
            'operatingSystem'     => (string) ($o['os'] ?? 'Web'),
            'applicationCategory' => (string) ($o['app_category'] ?? 'BusinessApplication'),
            'offers'              => [
                '@type'         => 'Offer',
                'price'         => (string) ($o['price'] ?? '0'),
                'priceCurrency' => (string) ($o['currency'] ?? 'USD'),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function dataset(Context $ctx): array
    {
        return Node::make('Dataset', $ctx->entityId('dataset'), [
            'name'        => $ctx->headline(),
            'description' => $ctx->description,
            'url'         => $ctx->url,
            'creator'     => Node::ref($ctx->orgId()),
            'license'     => (string) ($ctx->overrides['license'] ?? ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function review(Context $ctx): array
    {
        $o = $ctx->overrides;
        return Node::make('Review', $ctx->entityId('review'), [
            'itemReviewed' => [
                '@type' => (string) ($o['reviewed_type'] ?? 'Product'),
                'name'  => (string) ($o['reviewed_name'] ?? $ctx->title),
            ],
            'reviewRating' => [
                '@type'       => 'Rating',
                'ratingValue' => (string) ($o['rating'] ?? '5'),
                'bestRating'  => '5',
                'worstRating' => '1',
            ],
            'author'       => $ctx->author
                ? Node::ref($ctx->entityId('author'))
                : Node::ref($ctx->orgId()),
            'datePublished'=> $ctx->post ? Node::isoDate($ctx->post->post_date_gmt ?: $ctx->post->post_date) : '',
            'reviewBody'   => $ctx->description,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function service(Context $ctx): array
    {
        return Node::make('Service', $ctx->entityId('service'), [
            'name'        => $ctx->headline(),
            'description' => $ctx->description,
            'provider'    => Node::ref($ctx->orgId()),
            'areaServed'  => (string) $ctx->settings->get('organization.area_served', 'IR'),
            'image'       => Images::urls($ctx->images),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function qa(Context $ctx): array
    {
        $question = (string) ($ctx->overrides['qa_question'] ?? $ctx->title);
        $answer   = (string) ($ctx->overrides['qa_answer'] ?? $ctx->description);

        return Node::make('QAPage', $ctx->entityId('qa'), [
            'mainEntity' => [
                '@type'          => 'Question',
                'name'           => Text::clean($question),
                'text'           => Text::clean($question),
                'answerCount'    => 1,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => Text::clean($answer, 2000),
                    'url'   => $ctx->url,
                ],
            ],
        ]);
    }
}
