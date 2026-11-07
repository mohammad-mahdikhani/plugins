<?php
/**
 * REST file sync so the live product shell can be updated without wp-admin zip upload.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Shell_Deploy {

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			'pm-shell/v1',
			'/sync-file',
			array(
				'methods'             => 'POST',
				'permission_callback' => static function (): bool {
					return current_user_can( 'update_plugins' );
				},
				'callback'            => array( __CLASS__, 'sync_file' ),
				'args'                => array(
					'path'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'content' => array(
						'required' => true,
						'type'     => 'string',
					),
				),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request.
	 */
	public static function sync_file( WP_REST_Request $request ): WP_REST_Response {
		$rel     = ltrim( str_replace( '\\', '/', (string) $request->get_param( 'path' ) ), '/' );
		$content = base64_decode( (string) $request->get_param( 'content' ), true );

		if ( false === $content ) {
			return new WP_REST_Response( array( 'error' => 'invalid_base64' ), 400 );
		}

		if ( ! preg_match( '#^(includes|templates|woocommerce)/#', $rel ) && 'pakanmental-shell.php' !== $rel ) {
			return new WP_REST_Response( array( 'error' => 'path_not_allowed' ), 403 );
		}

		$base = realpath( PM_SHELL_PATH );
		if ( ! $base ) {
			return new WP_REST_Response( array( 'error' => 'plugin_path' ), 500 );
		}

		$target = $base . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $rel );
		$dir    = dirname( $target );
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_REST_Response( array( 'error' => 'mkdir_failed' ), 500 );
		}

		$written = file_put_contents( $target, $content );
		if ( false === $written ) {
			return new WP_REST_Response( array( 'error' => 'write_failed' ), 500 );
		}

		return new WP_REST_Response(
			array(
				'ok'   => true,
				'path' => $rel,
				'size' => $written,
			),
			200
		);
	}
}
