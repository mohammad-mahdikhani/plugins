<?php
/**
 * Plugin bootstrap.
 *
 * @package NahalMaintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NM_Plugin {

	/** @var self|null */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		NM_Install::maybe_upgrade();
		NM_Frontend::instance()->init();
		if ( is_admin() ) {
			NM_Admin::instance()->init();
		}
	}
}
