<?php
/**
 * Uninstall Hesabdar Price List.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hpl_imports" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}hpl_changes" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

delete_option( 'hpl_settings' );
delete_option( 'hpl_db_version' );

$upload = wp_upload_dir();
$dir    = trailingslashit( $upload['basedir'] ) . 'hesabdar-price-list';
if ( is_dir( $dir ) ) {
	$files = glob( $dir . '/*' );
	if ( is_array( $files ) ) {
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
	}
	@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
}
