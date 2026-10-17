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
		self::register_type();
		flush_rewrite_rules( false );
		update_option( 'npbr_version', NPBR_VERSION );
	}

	public static function deactivate() {
		flush_rewrite_rules( false );
	}

	public static function maybe_upgrade() {
		$stored = get_option( 'npbr_version' );
		if ( $stored !== NPBR_VERSION ) {
			self::register_type();
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
				'show_ui'             => false,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
			)
		);
	}
}
