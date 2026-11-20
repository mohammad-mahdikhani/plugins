<?php
/**
 * Normalize connector rows into a common SERP row shape.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Connectors;

use SchemaNova\Optimizer\Intent;
use SchemaNova\Support\Url;

final class Normalizer
{
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    public static function row(array $row, string $brand = ''): ?array
    {
        $page = (string) ($row['page'] ?? $row['page_url'] ?? $row['url'] ?? $row['landingPage'] ?? '');
        $query = (string) ($row['query'] ?? $row['query_text'] ?? $row['keyword'] ?? $row['searchTerm'] ?? '');
        if ($page === '') {
            return null;
        }

        $clicks      = (int) ($row['clicks'] ?? $row['click'] ?? 0);
        $impressions = (int) ($row['impressions'] ?? $row['impression'] ?? $row['views'] ?? 0);
        $ctr         = isset($row['ctr']) ? (float) $row['ctr'] : ($impressions > 0 ? $clicks / $impressions : 0.0);
        if ($ctr > 1) {
            $ctr = $ctr / 100;
        }
        $position = (float) ($row['position'] ?? $row['avgPosition'] ?? $row['rank'] ?? 0);

        return [
            'page_url'    => Url::normalize($page),
            'query_text'  => $query,
            'clicks'      => $clicks,
            'impressions' => $impressions,
            'ctr'         => $ctr,
            'position'    => $position,
            'intent'      => $query !== '' ? Intent::classify($query, $brand) : '',
        ];
    }

    /**
     * Walk a dotted path. Supports numeric segments.
     *
     * @param mixed $data
     */
    public static function path(mixed $data, string $path): mixed
    {
        if ($path === '') {
            return $data;
        }
        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            if (is_object($data)) {
                $data = (array) $data;
            }
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }

        return $data;
    }
}
