<?php
defined( 'ABSPATH' ) || exit;

class NCK_Plugin {

	/** @var self|null */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->license();
		NCK_Install::maybe_upgrade();
		NCK_Ajax::hooks();
		NCK_Print::hooks();
		NCK_Frontend::hooks();
		NCK_Admin::hooks();
		NCK_Pay::hooks();

		if ( did_action( 'elementor/loaded' ) ) {
			$this->load_elementor();
		} else {
			add_action( 'elementor/loaded', array( $this, 'load_elementor' ) );
		}

		add_filter( 'plugin_row_meta', array( $this, 'row_meta' ), 10, 2 );
	}

	public function load_elementor() {
		require_once NCK_PATH . 'includes/class-nck-elementor.php';
		NCK_Elementor::hooks();
	}

	private function license() {
		// License removed by request
	}

	public static function is_usable() {
		return true;
	}

	public function row_meta( $links, $file ) {
		if ( plugin_basename( NCK_FILE ) !== $file ) {
			return $links;
		}
		$links[] = '<a href="https://webakery.ir" target="_blank" rel="noopener">webakery.ir</a>';
		return $links;
	}
}
