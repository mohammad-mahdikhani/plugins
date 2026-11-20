<?php
/**
 * Google Search Console Search Analytics API.
 *
 * Auth: OAuth refresh token (recommended) or a short-lived access token.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Connectors;

use SchemaNova\Settings\Repository;

final class SearchConsole implements Contract
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (array) Repository::instance()->get('connector.gsc', []);
    }

    public function test(): array
    {
        $token = $this->accessToken();
        if ($token === '') {
            return ['ok' => false, 'message' => 'Add a Search Console access token or OAuth refresh token.'];
        }

        $site = $this->siteUrl();
        $url  = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($site);
        $res  = wp_remote_get($url, [
            'headers' => ['Authorization' => 'Bearer ' . $token],
            'timeout' => 15,
        ]);
        if (is_wp_error($res)) {
            return ['ok' => false, 'message' => $res->get_error_message()];
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'message' => 'Search Console property is reachable: ' . $site];
        }

        return ['ok' => false, 'message' => 'HTTP ' . $code . ': ' . substr((string) wp_remote_retrieve_body($res), 0, 180)];
    }

    public function fetch(int $lookbackDays): array
    {
        $token = $this->accessToken();
        if ($token === '') {
            return ['rows' => [], 'error' => 'Missing Search Console token.'];
        }

        $end   = gmdate('Y-m-d', time() - 2 * DAY_IN_SECONDS);
        $start = gmdate('Y-m-d', time() - ($lookbackDays + 2) * DAY_IN_SECONDS);
        $site  = $this->siteUrl();
        $url   = 'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($site) . '/searchAnalytics/query';

        $payload = [
            'startDate'  => $start,
            'endDate'    => $end,
            'dimensions' => ['page', 'query'],
            'rowLimit'   => 5000,
            'type'       => 'web',
        ];

        $res = wp_remote_post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($res)) {
            return ['rows' => [], 'error' => $res->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($res);
        $body = json_decode((string) wp_remote_retrieve_body($res), true);
        if ($code < 200 || $code >= 300 || ! is_array($body)) {
            return ['rows' => [], 'error' => 'Search Console HTTP ' . $code];
        }

        $brand = (string) Repository::instance()->get('organization.name', '');
        $out   = [];
        foreach ((array) ($body['rows'] ?? []) as $row) {
            $keys = $row['keys'] ?? [];
            $norm = Normalizer::row([
                'page'        => $keys[0] ?? '',
                'query'       => $keys[1] ?? '',
                'clicks'      => $row['clicks'] ?? 0,
                'impressions' => $row['impressions'] ?? 0,
                'ctr'         => $row['ctr'] ?? 0,
                'position'    => $row['position'] ?? 0,
            ], $brand);
            if ($norm !== null) {
                $out[] = $norm;
            }
        }

        return ['rows' => $out];
    }

    private function siteUrl(): string
    {
        $site = (string) ($this->config['site_url'] ?? home_url('/'));
        return untrailingslashit($site) . (str_starts_with($site, 'sc-domain:') ? '' : '/');
    }

    private function accessToken(): string
    {
        $token   = (string) ($this->config['access_token'] ?? '');
        $expires = (int) ($this->config['token_expires'] ?? 0);
        if ($token !== '' && ($expires === 0 || $expires > time() + 60)) {
            return $token;
        }

        $refresh = (string) ($this->config['refresh_token'] ?? '');
        $id      = (string) ($this->config['client_id'] ?? '');
        $secret  = (string) ($this->config['client_secret'] ?? '');
        if ($refresh === '' || $id === '' || $secret === '') {
            return $token;
        }

        $res = wp_remote_post('https://oauth2.googleapis.com/token', [
            'timeout' => 15,
            'body'    => [
                'client_id'     => $id,
                'client_secret' => $secret,
                'refresh_token' => $refresh,
                'grant_type'    => 'refresh_token',
            ],
        ]);
        if (is_wp_error($res)) {
            return $token;
        }
        $body = json_decode((string) wp_remote_retrieve_body($res), true);
        if (! is_array($body) || empty($body['access_token'])) {
            return $token;
        }

        $new = (string) $body['access_token'];
        $ttl = (int) ($body['expires_in'] ?? 3600);
        Repository::instance()->merge([
            'connector' => [
                'gsc' => [
                    'access_token'  => $new,
                    'token_expires' => time() + $ttl,
                ],
            ],
        ]);

        return $new;
    }
}
