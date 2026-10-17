<?php
/**
 * Remove plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nm_settings' );
delete_option( 'nm_version' );
