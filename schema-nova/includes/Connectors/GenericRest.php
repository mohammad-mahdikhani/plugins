<?php
/**
 * Bring-your-own analytics API. Map JSON fields in settings.
 *
 * This is the primary connector the user asked for: any Analytics-like tool
 * with an HTTP API (Matomo, Plausible, Search Console proxies, Iranian
 * analytics products, custom warehouses).
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Connectors;

use SchemaNova\Settings\Repository;

final class GenericRest implements Contract
{
    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? (array) Repository::instance()->get('connector.generic', []);
    }

    public function test(): array
    {
        $result = $this->request();
        if (! empty($result['error'])) {
            return ['ok' => false, 'message' => (string) $result['error']];
        }

        $rows = $this->extractRows($result['body'] ?? null);
        return [
            'ok'      => true,
            'message' => sprintf('Connected. %d rows in the latest payload.', count($rows)),
        ];
    }

    public function fetch(int $lookbackDays): array
    {
        $result = $this->request();
        if (! empty($result['error'])) {
            return ['rows' => [], 'error' => (string) $result['error']];
        }

        $raw  = $this->extractRows($result['body'] ?? null);
        $brand = (string) Repository::instance()->get('organization.name', '');
        $rows  = [];
        foreach ($raw as $item) {
            if (! is_array($item)) {
                continue;
            }
            $mapped = $this->mapRow($item);
            $norm   = Normalizer::row($mapped, $brand);
            if ($norm !== null) {
                $rows[] = $norm;
            }
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{body?:mixed,error?:string}
     */
    public function request(): array
    {
        $endpoint = trim((string) ($this->config['endpoint'] ?? ''));
        if ($endpoint === '' || ! wp_http_validate_url($endpoint)) {
            return ['error' => 'Set a valid API endpoint URL.'];
        }

        $method  = strtoupper((string) ($this->config['method'] ?? 'GET'));
        $headers = $this->headers();
        $args    = [
            'method'  => in_array($method, ['GET', 'POST'], true) ? $method : 'GET',
            'headers' => $headers,
            'timeout' => 20,
        ];
        $body = (string) ($this->config['body'] ?? '');
        if ($method === 'POST' && $body !== '') {
            $args['body'] = $body;
        }

        $response = wp_remote_request($endpoint, $args);
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw  = (string) wp_remote_retrieve_body($response);
        if ($code < 200 || $code >= 300) {
            return ['error' => 'HTTP ' . $code . ': ' . substr($raw, 0, 180)];
        }

        $json = json_decode($raw, true);
        if (! is_array($json)) {
            return ['error' => 'API did not return JSON.'];
        }

        return ['body' => $json];
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        $headers = ['Accept' => 'application/json'];
        $type    = (string) ($this->config['auth_type'] ?? 'bearer');
        $token   = (string) ($this->config['token'] ?? '');
        $name    = (string) ($this->config['header_name'] ?? 'Authorization');

        if ($token !== '') {
            if ($type === 'bearer') {
                $headers['Authorization'] = 'Bearer ' . $token;
            } elseif ($type === 'header') {
                $headers[$name !== '' ? $name : 'X-API-Key'] = $token;
            } elseif ($type === 'basic') {
                $headers['Authorization'] = 'Basic ' . $token;
            }
        }

        $extra = (string) ($this->config['extra_headers'] ?? '');
        foreach (preg_split('/\r\n|\r|\n/', $extra) ?: [] as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            if ($k !== '') {
                $headers[$k] = $v;
            }
        }

        return $headers;
    }

    /**
     * @param mixed $body
     * @return list<mixed>
     */
    private function extractRows(mixed $body): array
    {
        $path = (string) (($this->config['map']['rows'] ?? 'rows') ?: 'rows');
        $found = Normalizer::path($body, $path);
        if (is_array($found)) {
            if ($found !== [] && array_is_list($found)) {
                return $found;
            }
            // Some APIs return {data:{rows:[...]}} already unwrapped incorrectly.
            foreach (['rows', 'data', 'results', 'items'] as $guess) {
                if (isset($found[$guess]) && is_array($found[$guess]) && array_is_list($found[$guess])) {
                    return $found[$guess];
                }
            }
        }
        if (is_array($body) && array_is_list($body)) {
            return $body;
        }

        return [];
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function mapRow(array $item): array
    {
        $map = is_array($this->config['map'] ?? null) ? $this->config['map'] : [];
        $out = [];
        foreach (['page', 'query', 'clicks', 'impressions', 'ctr', 'position'] as $field) {
            $path = (string) ($map[$field] ?? $field);
            $out[$field] = Normalizer::path($item, $path);
            if ($out[$field] === null && isset($item[$field])) {
                $out[$field] = $item[$field];
            }
        }

        return $out;
    }
}
