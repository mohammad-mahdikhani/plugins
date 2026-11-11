<?php
class AS_Advisor_Plugin {

	public static function init() {
		add_shortcode( 'as_product_advisor', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'fab' ), 30 );
		AS_Advisor_REST::init();
		if ( is_admin() ) {
			AS_Advisor_Admin::init();
		}
	}

	public static function assets() {
		if ( is_admin() ) {
			return;
		}
		$base = plugins_url( '../assets/', __FILE__ );
		if ( defined( 'AS_ADVISOR_FILE' ) ) {
			$base = plugins_url( 'assets/', AS_ADVISOR_FILE );
		}
		wp_enqueue_style( 'as-advisor', $base . 'advisor.css', array(), '1.0.0' );
		wp_enqueue_script( 'as-advisor', $base . 'advisor.js', array(), '1.0.0', true );
		wp_localize_script(
			'as-advisor',
			'asAdvisor',
			array(
				'rest'  => esc_url_raw( rest_url( 'as-advisor/v1/' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}

	public static function shortcode() {
		self::assets();
		return '<div class="as-advisor" data-as-advisor="embedded"></div>';
	}

	public static function fab() {
		if ( is_admin() || is_checkout() || is_cart() || is_account_page() ) {
			return;
		}
		echo '<div class="as-advisor-fab-host"><button type="button" class="as-adv-fab" data-as-open>دستیار انتخاب محصول</button><div class="as-advisor as-advisor-modal" data-as-advisor="modal" hidden></div></div>';
	}
}
