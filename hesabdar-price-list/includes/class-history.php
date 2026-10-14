<?php
/**
 * Import history and rollback snapshots.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_History {

	/**
	 * @return string
	 */
	public static function imports_table() {
		global $wpdb;
		return $wpdb->prefix . 'hpl_imports';
	}

	/**
	 * @return string
	 */
	public static function changes_table() {
		global $wpdb;
		return $wpdb->prefix . 'hpl_changes';
	}

	/**
	 * @param array<string, mixed> $import Import meta.
	 * @return int
	 */
	public static function create_import( array $import ) {
		global $wpdb;
		$wpdb->insert(
			self::imports_table(),
			array(
				'user_id'    => isset( $import['user_id'] ) ? (int) $import['user_id'] : 0,
				'filename'   => isset( $import['filename'] ) ? substr( (string) $import['filename'], 0, 255 ) : '',
				'file_type'  => isset( $import['file_type'] ) ? substr( (string) $import['file_type'], 0, 20 ) : '',
				'created_at' => current_time( 'mysql' ),
				'stats'      => wp_json_encode( isset( $import['stats'] ) ? $import['stats'] : array() ),
			),
			array( '%d', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * @param int                            $import_id Import id.
	 * @param array<int, array<string,mixed>> $items     Changed items.
	 */
	public static function save_changes( $import_id, array $items ) {
		global $wpdb;
		$table = self::changes_table();
		foreach ( $items as $item ) {
			if ( 'changed' !== $item['status'] ) {
				continue;
			}
			$wpdb->insert(
				$table,
				array(
					'import_id'    => (int) $import_id,
					'product_id'   => (int) $item['product_id'],
					'sku'          => substr( (string) $item['sku'], 0, 191 ),
					'product_name' => isset( $item['product_name'] ) ? (string) $item['product_name'] : '',
					'old_regular'  => (string) $item['old_regular'],
					'new_regular'  => (string) $item['new_regular'],
					'old_sale'     => (string) $item['old_sale'],
					'new_sale'     => (string) $item['new_sale'],
					'old_stock'    => (string) $item['old_stock'],
					'new_stock'    => (string) $item['new_stock'],
					'status'       => 'updated',
				)
			);
		}
	}

	/**
	 * @param int                  $import_id Import id.
	 * @param array<string, mixed> $stats     Stats.
	 */
	public static function mark_applied( $import_id, array $stats ) {
		global $wpdb;
		$wpdb->update(
			self::imports_table(),
			array(
				'applied_at' => current_time( 'mysql' ),
				'stats'      => wp_json_encode( $stats ),
			),
			array( 'id' => (int) $import_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * @param int $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function list_imports( $limit = 30 ) {
		global $wpdb;
		$table = self::imports_table();
		$sql   = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows  = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @param int $import_id Import id.
	 * @return array<string, mixed>|null
	 */
	public static function get_import( $import_id ) {
		global $wpdb;
		$table = self::imports_table();
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $import_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/**
	 * @param int $import_id Import id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_changes( $import_id ) {
		global $wpdb;
		$table = self::changes_table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE import_id = %d ORDER BY id ASC", $import_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @param int $import_id Import id.
	 * @return int Restored count.
	 */
	public static function rollback( $import_id ) {
		$import = self::get_import( $import_id );
		if ( ! $import || ! empty( $import['rolled_back_at'] ) ) {
			throw new RuntimeException( 'این واردات قابل بازگشت نیست.' );
		}
		if ( empty( $import['applied_at'] ) ) {
			throw new RuntimeException( 'این واردات هنوز اعمال نشده است.' );
		}

		$changes  = self::get_changes( $import_id );
		$restored = 0;
		foreach ( $changes as $change ) {
			if ( HPL_Updater::restore_woocommerce( $change ) ) {
				$restored++;
			}
		}

		global $wpdb;
		$wpdb->update(
			self::imports_table(),
			array( 'rolled_back_at' => current_time( 'mysql' ) ),
			array( 'id' => (int) $import_id ),
			array( '%s' ),
			array( '%d' )
		);

		return $restored;
	}
}
