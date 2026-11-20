<?php
/**
 * Lightweight Google-oriented JSON-LD checks (required/recommended fields).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema;

final class Validator
{
    /**
     * Rules keyed by @type. Required fields must be present; recommended
     * fields improve rich-result eligibility per Search Central docs.
     *
     * @var array<string, array{required: list<string>, recommended: list<string>}>
     */
    public const RULES = [
        'Organization' => [
            'required'    => ['name'],
            'recommended' => ['url', 'logo', 'sameAs', 'address', 'telephone', 'email'],
        ],
        'OnlineStore' => [
            'required'    => ['name'],
            'recommended' => ['url', 'logo', 'hasMerchantReturnPolicy', 'hasShippingService'],
        ],
        'LocalBusiness' => [
            'required'    => ['name', 'address'],
            'recommended' => ['telephone', 'openingHoursSpecification', 'geo', 'image', 'url'],
        ],
        'WebSite' => [
            'required'    => ['name', 'url'],
            'recommended' => ['publisher', 'inLanguage', 'potentialAction'],
        ],
        'WebPage' => [
            'required'    => ['name', 'url'],
            'recommended' => ['isPartOf', 'breadcrumb', 'datePublished', 'dateModified'],
        ],
        'Article' => [
            'required'    => ['headline'],
            'recommended' => ['image', 'datePublished', 'dateModified', 'author'],
        ],
        'NewsArticle' => [
            'required'    => ['headline'],
            'recommended' => ['image', 'datePublished', 'dateModified', 'author'],
        ],
        'BlogPosting' => [
            'required'    => ['headline'],
            'recommended' => ['image', 'datePublished', 'dateModified', 'author'],
        ],
        'BreadcrumbList' => [
            'required'    => ['itemListElement'],
            'recommended' => [],
        ],
        'Product' => [
            'required'    => ['name', 'image'],
            'recommended' => ['description', 'offers', 'sku', 'brand', 'aggregateRating'],
        ],
        'Offer' => [
            'required'    => ['priceCurrency', 'price'],
            'recommended' => ['availability', 'url'],
        ],
        'FAQPage' => [
            'required'    => ['mainEntity'],
            'recommended' => [],
        ],
        'Event' => [
            'required'    => ['name', 'startDate', 'location'],
            'recommended' => ['image', 'description', 'offers', 'organizer'],
        ],
        'JobPosting' => [
            'required'    => ['title', 'description', 'datePosted', 'hiringOrganization', 'jobLocation'],
            'recommended' => ['validThrough', 'employmentType', 'baseSalary'],
        ],
        'Recipe' => [
            'required'    => ['name', 'image'],
            'recommended' => ['recipeIngredient', 'recipeInstructions', 'author', 'datePublished'],
        ],
        'VideoObject' => [
            'required'    => ['name', 'description', 'thumbnailUrl', 'uploadDate'],
            'recommended' => ['contentUrl', 'embedUrl'],
        ],
        'Course' => [
            'required'    => ['name', 'description', 'provider'],
            'recommended' => [],
        ],
        'SoftwareApplication' => [
            'required'    => ['name'],
            'recommended' => ['offers', 'aggregateRating', 'applicationCategory', 'operatingSystem'],
        ],
        'Dataset' => [
            'required'    => ['name', 'description'],
            'recommended' => ['url', 'creator', 'license'],
        ],
        'Review' => [
            'required'    => ['itemReviewed', 'reviewRating', 'author'],
            'recommended' => ['datePublished'],
        ],
        'Person' => [
            'required'    => ['name'],
            'recommended' => ['url', 'image'],
        ],
        'ProfilePage' => [
            'required'    => ['mainEntity'],
            'recommended' => ['name', 'url'],
        ],
        'QAPage' => [
            'required'    => ['mainEntity'],
            'recommended' => [],
        ],
    ];

    /**
     * @param array<string, mixed> $graph
     * @return list<array{severity:string,type:string,field:string,message:string}>
     */
    public static function check(array $graph): array
    {
        $nodes = $graph['@graph'] ?? [$graph];
        if (! is_array($nodes)) {
            return [[
                'severity' => 'error',
                'type'     => 'Graph',
                'field'    => '@graph',
                'message'  => 'JSON-LD graph is empty.',
            ]];
        }

        $issues = [];
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            $types = $node['@type'] ?? '';
            $types = is_array($types) ? $types : [$types];
            foreach ($types as $type) {
                if (! is_string($type) || ! isset(self::RULES[$type])) {
                    continue;
                }
                $issues = array_merge($issues, self::checkNode($type, $node));
            }
        }

        return $issues;
    }

    /**
     * @param array<string, mixed> $node
     * @return list<array{severity:string,type:string,field:string,message:string}>
     */
    private static function checkNode(string $type, array $node): array
    {
        $issues = [];
        $rules  = self::RULES[$type];

        foreach ($rules['required'] as $field) {
            if (! self::has($node, $field)) {
                $issues[] = [
                    'severity' => 'error',
                    'type'     => $type,
                    'field'    => $field,
                    'message'  => sprintf('%s is missing required property "%s".', $type, $field),
                ];
            }
        }

        foreach ($rules['recommended'] as $field) {
            if (! self::has($node, $field)) {
                $issues[] = [
                    'severity' => 'warning',
                    'type'     => $type,
                    'field'    => $field,
                    'message'  => sprintf('%s is missing recommended property "%s".', $type, $field),
                ];
            }
        }

        if ($type === 'BreadcrumbList') {
            $items = $node['itemListElement'] ?? [];
            if (is_array($items) && count($items) < 2) {
                $issues[] = [
                    'severity' => 'error',
                    'type'     => $type,
                    'field'    => 'itemListElement',
                    'message'  => 'BreadcrumbList needs at least two ListItem entries.',
                ];
            }
        }

        if (in_array($type, ['Article', 'NewsArticle', 'BlogPosting'], true)) {
            $headline = (string) ($node['headline'] ?? '');
            if (function_exists('mb_strlen') ? mb_strlen($headline, 'UTF-8') > 110 : strlen($headline) > 110) {
                $issues[] = [
                    'severity' => 'warning',
                    'type'     => $type,
                    'field'    => 'headline',
                    'message'  => 'headline is longer than 110 characters and may be truncated.',
                ];
            }
        }

        return $issues;
    }

    /**
     * @param array<string, mixed> $node
     */
    private static function has(array $node, string $field): bool
    {
        if (! array_key_exists($field, $node)) {
            return false;
        }
        $value = $node[$field];
        if ($value === null || $value === '' || $value === []) {
            return false;
        }

        return true;
    }

    /**
     * @param list<array{severity:string,type:string,field:string,message:string}> $issues
     */
    public static function score(array $issues): int
    {
        $errors   = count(array_filter($issues, static fn ($i) => $i['severity'] === 'error'));
        $warnings = count(array_filter($issues, static fn ($i) => $i['severity'] === 'warning'));
        $score    = 100 - ($errors * 18) - ($warnings * 4);

        return max(0, min(100, $score));
    }
}
