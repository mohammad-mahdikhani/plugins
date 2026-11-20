<?php
/**
 * Pick a connector, fetch rows, persist them.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Connectors;

use SchemaNova\Install;
use SchemaNova\Settings\Repository;

final class Manager
{
    public function register(): void
    {
        // Placeholder for future webhook ingest routes.
    }

    public function driver(): Contract
    {
        $provider = (string) Repository::instance()->get('connector.provider', 'generic');

        return match ($provider) {
            'gsc'  => new SearchConsole(),
            'ga4'  => new Analytics(),
            default => new GenericRest(),
        };
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function test(): array
    {
        return $this->driver()->test();
    }

    /**
     * @return array{rows:int,error?:string}
     */
    public function sync(): array
    {
        Install::createTables();
        $lookback = (int) Repository::instance()->get('optimizer.lookback_days', 28);
        $fetched  = $this->driver()->fetch(max(7, $lookback));
        if (! empty($fetched['error'])) {
            return ['rows' => 0, 'error' => (string) $fetched['error']];
        }

        $count = $this->store($fetched['rows'] ?? []);
        set_transient('schema_nova_connector_status', [
            'ok'      => true,
            'rows'    => $count,
            'at'      => time(),
            'provider'=> (string) Repository::instance()->get('connector.provider', 'generic'),
        ], DAY_IN_SECONDS);

        return ['rows' => $count];
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function store(array $rows): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'schema_nova_serp';
        $wpdb->query("TRUNCATE TABLE {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $now   = current_time('mysql');
        $count = 0;
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['page_url'])) {
                continue;
            }
            $wpdb->insert(
                $table,
                [
                    'page_url'    => substr((string) $row['page_url'], 0, 191),
                    'query_text'  => substr((string) ($row['query_text'] ?? ''), 0, 191),
                    'clicks'      => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'ctr'         => (float) ($row['ctr'] ?? 0),
                    'position'    => (float) ($row['position'] ?? 0),
                    'intent'      => substr((string) ($row['intent'] ?? ''), 0, 32),
                    'synced_at'   => $now,
                ],
                ['%s', '%s', '%d', '%d', '%f', '%f', '%s', '%s']
            );
            $count++;
        }

        return $count;
    }
}
