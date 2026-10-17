<?php
/**
 * Remove plugin options. Reports stay unless the site deletes them.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'npbr_settings' );
delete_option( 'npbr_version' );
