<?php
/**
 * Activation / tables.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HPL_Install {

	const DB_VERSION = '1.0.0';

	public static function activate() {
		self::create_tables();
		if ( ! get_option( 'hpl_settings' ) ) {
			update_option( 'hpl_settings', HPL_Updater::defaults() );
		}
		update_option( 'hpl_db_version', self::DB_VERSION );
	}

	public static function deactivate() {
		// Keep history tables; they are dropped on uninstall.
	}

	public static function maybe_upgrade() {
		if ( get_option( 'hpl_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			update_option( 'hpl_db_version', self::DB_VERSION );
		}
	}

	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$imports = $wpdb->prefix . 'hpl_imports';
		$changes = $wpdb->prefix . 'hpl_changes';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql1 = "CREATE TABLE {$imports} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			filename varchar(255) NOT NULL DEFAULT '',
			file_type varchar(20) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			applied_at datetime DEFAULT NULL,
			rolled_back_at datetime DEFAULT NULL,
			stats longtext NULL,
			PRIMARY KEY  (id),
			KEY created_at (created_at)
		) {$charset};";

		$sql2 = "CREATE TABLE {$changes} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			import_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			sku varchar(191) NOT NULL DEFAULT '',
			product_name text NULL,
			old_regular varchar(50) NOT NULL DEFAULT '',
			new_regular varchar(50) NOT NULL DEFAULT '',
			old_sale varchar(50) NOT NULL DEFAULT '',
			new_sale varchar(50) NOT NULL DEFAULT '',
			old_stock varchar(50) NOT NULL DEFAULT '',
			new_stock varchar(50) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'updated',
			PRIMARY KEY  (id),
			KEY import_id (import_id),
			KEY product_id (product_id)
		) {$charset};";

		dbDelta( $sql1 );
		dbDelta( $sql2 );
	}
}
