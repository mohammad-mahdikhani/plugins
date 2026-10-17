<?php
/**
 * Serve the 3D maintenance page to visitors.
 *
 * @package NahalMaintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NM_Frontend {

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
		add_action( 'template_redirect', array( $this, 'maybe_serve' ), 0 );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 80 );
	}

	public function maybe_serve() {
		$settings = NM_Settings::get();
		$force    = $this->wants_force_preview();
		if ( ! $force && empty( $settings['enabled'] ) ) {
			return;
		}
		if ( ! $force && $this->should_bypass( $settings ) ) {
			return;
		}
		if ( $force && ! $this->can_preview() ) {
			return;
		}

		$this->send_headers( $settings, $force );
		NM_Page::render( NM_Settings::page_data( $settings ) );
		exit;
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 * @param bool                 $preview  Forced preview.
	 */
	public function send_headers( $settings, $preview = false ) {
		if ( ! $preview ) {
			if ( function_exists( 'status_header' ) ) {
				status_header( 503 );
			} else {
				header( 'HTTP/1.1 503 Service Unavailable' );
			}
			$retry = isset( $settings['retry_after'] ) ? (int) $settings['retry_after'] : 3600;
			header( 'Retry-After: ' . $retry );
		} else {
			if ( function_exists( 'status_header' ) ) {
				status_header( 200 );
			}
		}
		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}
		header( 'Content-Type: text/html; charset=UTF-8' );
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	public function should_bypass( $settings ) {
		if ( $this->is_backend_request() ) {
			return true;
		}
		if ( $this->has_valid_bypass_key( $settings ) ) {
			return true;
		}
		if ( ! empty( $settings['bypass_admins'] ) && $this->current_user_can_manage() ) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function is_backend_request() {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}
		if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
			return true;
		}
		if ( function_exists( 'is_admin' ) && is_admin() ) {
			return true;
		}

		$script = '';
		if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && is_string( $_SERVER['SCRIPT_NAME'] ) ) {
			$script = strtolower( (string) $_SERVER['SCRIPT_NAME'] );
		}
		$basename = $script !== '' ? basename( $script ) : '';
		$allowed  = array(
			'wp-login.php',
			'wp-register.php',
			'wp-cron.php',
			'admin-ajax.php',
			'admin-post.php',
			'async-upload.php',
		);
		if ( in_array( $basename, $allowed, true ) ) {
			return true;
		}

		$uri = '';
		if ( ! empty( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ) {
			$uri = strtolower( (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) );
		}
		if ( $uri === '/favicon.ico' || $uri === '/robots.txt' ) {
			return true;
		}
		if ( $uri !== '' && ( strpos( $uri, '/wp-admin' ) === 0 || strpos( $uri, '/wp-login.php' ) !== false ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $settings Settings.
	 * @return bool
	 */
	public function has_valid_bypass_key( $settings ) {
		$key = isset( $settings['bypass_key'] ) ? (string) $settings['bypass_key'] : '';
		if ( $key === '' ) {
			return false;
		}
		$given = '';
		if ( isset( $_GET['nm_key'] ) && is_string( $_GET['nm_key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$given = NM_Settings::key( wp_unslash( $_GET['nm_key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		return $given !== '' && hash_equals( $key, $given );
	}

	/**
	 * @return bool
	 */
	public function wants_force_preview() {
		if ( empty( $_GET['nm_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return false;
		}
		return $this->can_preview();
	}

	/**
	 * @return bool
	 */
	public function can_preview() {
		return $this->current_user_can_manage();
	}

	/**
	 * @return bool
	 */
	public function current_user_can_manage() {
		if ( function_exists( 'current_user_can' ) ) {
			return current_user_can( 'manage_options' );
		}
		return false;
	}

	/**
	 * @param WP_Admin_Bar $bar Admin bar.
	 */
	public function admin_bar( $bar ) {
		if ( ! $this->current_user_can_manage() || ! is_object( $bar ) || ! method_exists( $bar, 'add_node' ) ) {
			return;
		}
		$settings = NM_Settings::get();
		$on       = ! empty( $settings['enabled'] );
		$bar->add_node(
			array(
				'id'    => 'nm-maintenance',
				'title' => $on ? 'تعمیر سایت روشن است' : 'صفحه تعمیر',
				'href'  => admin_url( 'admin.php?page=nm-maintenance' ),
				'meta'  => array(
					'class' => $on ? 'nm-bar-on' : 'nm-bar-off',
				),
			)
		);
	}
}
