<?php
/**
 * Title/meta change log.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova\Optimizer;

final class History
{
    public static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'schema_nova_history';
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function add(array $row): int
    {
        global $wpdb;
        $wpdb->insert(
            self::table(),
            [
                'object_id'   => (int) ($row['object_id'] ?? 0),
                'page_url'    => (string) ($row['page_url'] ?? ''),
                'field_name'  => (string) ($row['field_name'] ?? 'title'),
                'old_value'   => (string) ($row['old_value'] ?? ''),
                'new_value'   => (string) ($row['new_value'] ?? ''),
                'reason'      => (string) ($row['reason'] ?? ''),
                'mode'        => (string) ($row['mode'] ?? 'suggest'),
                'applied'     => ! empty($row['applied']) ? 1 : 0,
                'rolled_back' => 0,
                'created_at'  => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function latest(int $limit = 40): array
    {
        global $wpdb;
        $table = self::table();
        $sql   = $wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rows  = $wpdb->get_results($sql, ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public static function markRolledBack(int $id): void
    {
        global $wpdb;
        $wpdb->update(self::table(), ['rolled_back' => 1], ['id' => $id], ['%d'], ['%d']);
    }
}
