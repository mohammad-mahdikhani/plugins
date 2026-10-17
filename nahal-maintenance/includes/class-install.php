<?php
/**
 * Activation and upgrades.
 *
 * @package NahalMaintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NM_Install {

	public static function activate() {
		if ( ! get_option( 'nm_settings' ) ) {
			add_option( 'nm_settings', NM_Settings::defaults() );
		}
		update_option( 'nm_version', defined( 'NM_VERSION' ) ? NM_VERSION : '1.0.0' );
	}

	public static function deactivate() {
		$settings            = NM_Settings::get();
		$settings['enabled'] = false;
		update_option( 'nm_settings', $settings );
	}

	public static function maybe_upgrade() {
		$stored  = get_option( 'nm_version' );
		$current = defined( 'NM_VERSION' ) ? NM_VERSION : '1.0.0';
		if ( $stored === $current ) {
			return;
		}
		update_option( 'nm_version', $current );
	}
}
