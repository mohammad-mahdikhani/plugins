<?php
/**
 * Activation and upgrades.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Install {

	public static function activate() {
		if ( ! get_option( 'npbr_settings' ) ) {
			add_option( 'npbr_settings', NPBR_Plugin::defaults() );
		}
		update_option( 'npbr_version', NPBR_VERSION );
	}

	public static function deactivate() {
	}

	public static function maybe_upgrade() {
		$stored = get_option( 'npbr_version' );
		if ( $stored !== NPBR_VERSION ) {
			update_option( 'npbr_version', NPBR_VERSION );
		}
	}

	public static function register_type() {
		register_post_type(
			'npbr_report',
			array(
				'labels'              => array(
					'name'          => 'گزارش‌های خطای پرداخت',
					'singular_name' => 'گزارش خطا',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
			)
		);
	}
}
