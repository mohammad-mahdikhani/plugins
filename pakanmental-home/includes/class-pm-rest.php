<?php
/**
 * Public REST for frontend (trust badges slot).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Rest {

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			'pm/v1',
			'/trust-badges',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'get_trust_badges' ),
			)
		);
	}

	public static function get_trust_badges(): WP_REST_Response {
		$html = PM_Settings::get_trust_html();
		return new WP_REST_Response(
			array(
				'html' => $html,
			),
			200
		);
	}
}
