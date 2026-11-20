<?php
/**
 * Remove Schema Nova options, tables, and cron events on uninstall.
 *
 * @package SchemaNova
 */

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('schema_nova_settings');
delete_option('schema_nova_optimizer_state');
delete_transient('schema_nova_connector_status');

global $wpdb;

$table = $wpdb->prefix . 'schema_nova_serp';
$wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$history = $wpdb->prefix . 'schema_nova_history';
$wpdb->query("DROP TABLE IF EXISTS {$history}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

wp_clear_scheduled_hook('schema_nova_sync_serp');
wp_clear_scheduled_hook('schema_nova_optimize');
