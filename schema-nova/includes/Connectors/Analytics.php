<?php
/**
 * Google Analytics 4 Data API — used as a fallback when Search Console is
 * unavailable. GA4 has landing pages and (if configured) search-term data,
 * but Search Console remains the better intent source.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Connectors;

use SchemaNova\Settings\Repository;

final class Analytics implements Contract
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (array) Repository::instance()->get('connector.ga4', []);
    }

    public function test(): array
    {
        $property = $this->property();
        $token    = (string) ($this->config['access_token'] ?? '');
        if ($property === '' || $token === '') {
            return ['ok' => false, 'message' => 'Add a GA4 property ID and access token.'];
        }

        $url = 'https://analyticsdata.googleapis.com/v1beta/' . $property . ':runReport';
        $res = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 15,
            'body'    => wp_json_encode([
                'dateRanges' => [['startDate' => '7daysAgo', 'endDate' => 'today']],
                'metrics'    => [['name' => 'sessions']],
                'limit'      => 1,
            ]),
        ]);
        if (is_wp_error($res)) {
            return ['ok' => false, 'message' => $res->get_error_message()];
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'message' => 'GA4 property is reachable.'];
        }

        return ['ok' => false, 'message' => 'HTTP ' . $code];
    }

    public function fetch(int $lookbackDays): array
    {
        $property = $this->property();
        $token    = (string) ($this->config['access_token'] ?? '');
        if ($property === '' || $token === '') {
            return ['rows' => [], 'error' => 'Missing GA4 credentials.'];
        }

        $url = 'https://analyticsdata.googleapis.com/v1beta/' . $property . ':runReport';
        $res = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
            'body'    => wp_json_encode([
                'dateRanges'  => [[
                    'startDate' => $lookbackDays . 'daysAgo',
                    'endDate'   => 'yesterday',
                ]],
                'dimensions'  => [
                    ['name' => 'landingPagePlusQueryString'],
                    ['name' => 'sessionGoogleAdsQuery'],
                ],
                'metrics'     => [
                    ['name' => 'sessions'],
                    ['name' => 'engagementRate'],
                ],
                'limit'       => 5000,
            ]),
        ]);

        if (is_wp_error($res)) {
            return ['rows' => [], 'error' => $res->get_error_message()];
        }

        $body = json_decode((string) wp_remote_retrieve_body($res), true);
        if (! is_array($body)) {
            return ['rows' => [], 'error' => 'GA4 returned invalid JSON.'];
        }

        $brand = (string) Repository::instance()->get('organization.name', '');
        $home  = home_url();
        $out   = [];
        foreach ((array) ($body['rows'] ?? []) as $row) {
            $dims = $row['dimensionValues'] ?? [];
            $mets = $row['metricValues'] ?? [];
            $path = (string) ($dims[0]['value'] ?? '');
            $query = (string) ($dims[1]['value'] ?? '');
            $sessions = (int) ($mets[0]['value'] ?? 0);
            $eng      = (float) ($mets[1]['value'] ?? 0);
            $norm     = Normalizer::row([
                'page'        => $home . $path,
                'query'       => $query,
                'clicks'      => (int) round($sessions * $eng),
                'impressions' => $sessions,
                'ctr'         => $eng,
                'position'    => 0,
            ], $brand);
            if ($norm !== null) {
                $out[] = $norm;
            }
        }

        return ['rows' => $out];
    }

    private function property(): string
    {
        $id = trim((string) ($this->config['property_id'] ?? ''));
        if ($id === '') {
            return '';
        }
        return str_starts_with($id, 'properties/') ? $id : 'properties/' . $id;
    }
}
