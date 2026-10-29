<?php
/**
 * Enqueue styles/scripts only on the home template.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Assets {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_head', array( __CLASS__, 'font_preconnect' ), 1 );
	}

	public static function is_pm_home_page(): bool {
		return is_page() && 'templates/pm-home.php' === get_page_template_slug( get_queried_object_id() );
	}

	public static function should_load_pm_shell(): bool {
		if ( ! is_page() ) {
			return false;
		}
		$id = get_queried_object_id();
		if ( 'elementor_canvas' === get_page_template_slug( $id ) ) {
			return true;
		}
		return 'yes' === get_post_meta( $id, '_elementor_edit_mode', true );
	}

	public static function font_preconnect(): void {
		if ( is_admin() ) {
			return;
		}
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}

	private static function pm_cart_config_js(): string {
		if ( ! function_exists( 'wc_get_cart_url' ) ) {
			return 'window.pmCart=window.pmCart||{};';
		}
		$cfg = array(
			'storeApi'    => rest_url( 'wc/store/v1' ),
			'cartUrl'     => wc_get_cart_url(),
			'checkoutUrl' => wc_get_checkout_url(),
			'shopUrl'     => wc_get_page_permalink( 'shop' ),
		);
		return 'window.pmCart=' . wp_json_encode( $cfg ) . ';';
	}

	public static function enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'pm-brand-overrides',
			PM_HOME_URL . 'assets/css/pm-brand-overrides.css',
			array(),
			PM_HOME_VERSION
		);

		if ( self::should_load_pm_shell() && class_exists( 'WooCommerce' ) ) {
			wp_enqueue_style(
				'pm-fonts',
				'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
				array(),
				null
			);
			wp_enqueue_style(
				'pm-site',
				PM_HOME_URL . 'assets/css/pm.css',
				array( 'pm-fonts', 'pm-brand-overrides' ),
				PM_HOME_VERSION
			);
			wp_enqueue_script(
				'pm-cart',
				PM_HOME_URL . 'assets/js/pm-cart.js',
				array(),
				PM_HOME_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
			wp_add_inline_script(
				'pm-cart',
				self::pm_cart_config_js(),
				'before'
			);
		}

		if ( ! self::is_pm_home_page() ) {
			return;
		}

		wp_enqueue_style(
			'pm-fonts',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'pm-site',
			PM_HOME_URL . 'assets/css/pm.css',
			array( 'pm-fonts' ),
			PM_HOME_VERSION
		);

		wp_enqueue_script(
			'pm-home',
			PM_HOME_URL . 'assets/js/home.js',
			array(),
			PM_HOME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
