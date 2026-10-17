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
		if ( class_exists( 'NPBR_Stats', false ) ) {
			NPBR_Stats::init_cron();
		}
	}

	public static function deactivate() {
		if ( class_exists( 'NPBR_Stats', false ) ) {
			NPBR_Stats::clear_cron();
		}
	}

	public static function maybe_upgrade() {
		$stored = get_option( 'npbr_version' );
		if ( $stored === NPBR_VERSION ) {
			return;
		}
		if ( $stored && version_compare( (string) $stored, '1.0.7', '<' ) ) {
			$s = get_option( 'npbr_settings', array() );
			if ( is_array( $s ) ) {
				$s['show_on_cart']    = true;
				$s['show_on_shop']    = true;
				$s['show_on_product'] = true;
				$s['show_sitewide']   = true;
				$s['show_near_cart']  = true;
				update_option( 'npbr_settings', $s );
			}
		}
		if ( $stored && version_compare( (string) $stored, '1.0.8', '<' ) ) {
			$s = get_option( 'npbr_settings', array() );
			if ( is_array( $s ) && ! isset( $s['digest_email'] ) ) {
				$s['digest_email'] = true;
				update_option( 'npbr_settings', $s );
			}
		}
		if ( $stored && version_compare( (string) $stored, '1.0.10', '<' ) ) {
			$s = get_option( 'npbr_settings', array() );
			if ( is_array( $s ) ) {
				$s['require_login']    = true;
				$s['require_verified'] = true;
				update_option( 'npbr_settings', $s );
			}
		}
		update_option( 'npbr_version', NPBR_VERSION );
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
