<?php
/**
 * Uninstall.
 *
 * @package NahalFontDisplaySwap
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nfds_last_elementor_convert' );
delete_transient( 'nfds_flash' );
