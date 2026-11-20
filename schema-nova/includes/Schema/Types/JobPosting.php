<?php
/**
 * JobPosting markup from per-page overrides.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Schema\Types;

use SchemaNova\Schema\Context;
use SchemaNova\Schema\Node;
use SchemaNova\Support\Text;

final class JobPosting
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function maybe(Context $ctx): array
    {
        $o = $ctx->overrides;
        if (($o['schema_type'] ?? '') !== 'JobPosting' && empty($o['job_title'])) {
            return [];
        }
        if (empty($o['job_title'])) {
            return [];
        }

        $node = Node::make('JobPosting', $ctx->entityId('job'), [
            'title'               => Text::clean((string) $o['job_title']),
            'description'         => Text::clean((string) ($o['job_description'] ?? $ctx->description), 5000),
            'datePosted'          => (string) ($o['date_posted'] ?? ($ctx->post ? substr((string) $ctx->post->post_date, 0, 10) : gmdate('Y-m-d'))),
            'validThrough'        => (string) ($o['valid_through'] ?? ''),
            'employmentType'      => (string) ($o['employment_type'] ?? 'FULL_TIME'),
            'hiringOrganization'  => Node::ref($ctx->orgId()),
            'jobLocation'         => [
                '@type'   => 'Place',
                'address' => [
                    '@type'          => 'PostalAddress',
                    'addressCountry' => (string) $ctx->settings->get('organization.address.country', 'IR'),
                    'addressLocality'=> (string) ($o['job_city'] ?? $ctx->settings->get('organization.address.locality', '')),
                ],
            ],
            'baseSalary'          => ! empty($o['salary']) ? [
                '@type'    => 'MonetaryAmount',
                'currency' => (string) ($o['currency'] ?? 'IRR'),
                'value'    => [
                    '@type'    => 'QuantitativeValue',
                    'value'    => (string) $o['salary'],
                    'unitText' => (string) ($o['salary_unit'] ?? 'MONTH'),
                ],
            ] : '',
        ]);

        return [$node];
    }
}
