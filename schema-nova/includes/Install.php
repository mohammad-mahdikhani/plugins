<?php
/**
 * Activation, deactivation, and database schema.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

namespace SchemaNova;

use SchemaNova\Settings\Repository;

final class Install
{
    public static function activate(): void
    {
        Repository::instance()->ensureDefaults();
        self::createTables();

        if (! wp_next_scheduled('schema_nova_sync_serp')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'twicedaily', 'schema_nova_sync_serp');
        }

        if (! wp_next_scheduled('schema_nova_optimize')) {
            wp_schedule_event(time() + (2 * HOUR_IN_SECONDS), 'daily', 'schema_nova_optimize');
        }

        flush_rewrite_rules(false);
    }

    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('schema_nova_sync_serp');
        wp_clear_scheduled_hook('schema_nova_optimize');
    }

    public static function createTables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $serp    = $wpdb->prefix . 'schema_nova_serp';
        $history = $wpdb->prefix . 'schema_nova_history';

        dbDelta(
            "CREATE TABLE {$serp} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                page_url varchar(191) NOT NULL,
                query_text varchar(191) NOT NULL,
                clicks int unsigned NOT NULL DEFAULT 0,
                impressions int unsigned NOT NULL DEFAULT 0,
                ctr float NOT NULL DEFAULT 0,
                position float NOT NULL DEFAULT 0,
                intent varchar(32) NOT NULL DEFAULT '',
                window_start date DEFAULT NULL,
                window_end date DEFAULT NULL,
                synced_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY page_url (page_url),
                KEY query_text (query_text)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$history} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                object_id bigint(20) unsigned NOT NULL DEFAULT 0,
                page_url varchar(191) NOT NULL,
                field_name varchar(32) NOT NULL,
                old_value text NULL,
                new_value text NULL,
                reason varchar(191) NOT NULL DEFAULT '',
                mode varchar(16) NOT NULL DEFAULT 'suggest',
                applied tinyint(1) NOT NULL DEFAULT 0,
                rolled_back tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY object_id (object_id),
                KEY page_url (page_url)
            ) {$charset};"
        );
    }
}
