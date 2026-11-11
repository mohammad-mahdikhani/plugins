<?php
class AS_Advisor_REST {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			'as-advisor/v1',
			'/bootstrap',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'bootstrap' ),
			)
		);
		register_rest_route(
			'as-advisor/v1',
			'/recommend',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'recommend' ),
			)
		);
		register_rest_route(
			'as-advisor/v1',
			'/models',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'models' ),
			)
		);
		register_rest_route(
			'as-advisor/v1',
			'/event',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( __CLASS__, 'event' ),
			)
		);
	}

	public static function bootstrap() {
		$models = AS_Advisor_Catalog::models();
		return rest_ensure_response(
			array(
				'support_tel'  => '09373211344',
				'support_url'  => home_url( '/%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d8%a7/' ),
				'shop_url'     => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ),
				'models'       => $models,
				'other_kinds'  => AS_Advisor_Catalog::other_kinds(),
				'questions'    => AS_Advisor_Questions::tree(),
				'budgets'      => AS_Advisor_Catalog::budgets(),
			)
		);
	}

	public static function models( WP_REST_Request $req ) {
		$q    = strtolower( (string) $req->get_param( 'q' ) );
		$type = (string) $req->get_param( 'type' );
		$kind = ( 'air' === $type ) ? 'air_device' : ( ( 'water' === $type ) ? 'water_device' : '' );
		$out  = array();
		foreach ( AS_Advisor_Catalog::models() as $m ) {
			if ( $kind && $m['kind'] !== $kind ) {
				continue;
			}
			$hay = strtolower( $m['name'] . ' ' . $m['model'] );
			if ( $q && false === strpos( $hay, $q ) ) {
				continue;
			}
			$out[] = $m;
			if ( count( $out ) >= 12 ) {
				break;
			}
		}
		return rest_ensure_response( $out );
	}

	public static function recommend( WP_REST_Request $req ) {
		$body    = $req->get_json_params();
		$answers = is_array( $body ) ? $body : array();
		if ( isset( $answers['answers'] ) && is_array( $answers['answers'] ) ) {
			$answers = $answers['answers'];
		}
		unset( $answers['name'], $answers['phone'], $answers['email'] );
		$engine = new AS_Advisor_Engine();
		$result = $engine->recommend( $answers, AS_Advisor_Catalog::all(), AS_Advisor_Catalog::compat() );
		$result['share'] = rawurlencode( base64_encode( wp_json_encode( $answers ) ) );
		return rest_ensure_response( $result );
	}

	public static function event( WP_REST_Request $req ) {
		$params = $req->get_json_params();
		$name   = isset( $params['name'] ) ? $params['name'] : ( isset( $params['event'] ) ? $params['event'] : '' );
		$name   = sanitize_key( $name );
		$allowed = array( 'start', 'drop_step', 'dropoff', 'complete', 'view_product', 'add_to_cart', 'no_result', 'compat_unknown' );
		if ( in_array( $name, $allowed, true ) ) {
			$key   = 'as_advisor_stat_' . $name;
			$count = (int) get_option( $key, 0 );
			update_option( $key, $count + 1, false );
		}
		return rest_ensure_response( array( 'ok' => true ) );
	}
}
