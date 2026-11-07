<?php
/**
 * Site-wide WooCommerce product/category document (Hello header never used).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Shell_Chrome {

	public static function init(): void {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 100000 );
		add_filter( 'elementor/utils/is_post_type_support', array( __CLASS__, 'disable_elementor_for_products' ), 10, 2 );
		add_filter( 'elementor/utils/is_post_type_supported', array( __CLASS__, 'disable_elementor_for_products' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 40 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	/**
	 * @param bool   $supported Whether Elementor supports the post type.
	 * @param string $post_type Post type.
	 */
	public static function disable_elementor_for_products( $supported, $post_type ) {
		if ( 'product' === $post_type ) {
			return false;
		}
		return $supported;
	}

	public static function uses_shell(): bool {
		if ( class_exists( 'PM_Chrome' ) && is_callable( array( 'PM_Chrome', 'uses_wc_shell_template' ) ) && PM_Chrome::uses_wc_shell_template() ) {
			return false;
		}
		if ( is_admin() || wp_doing_ajax() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
			return false;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		if ( is_product_taxonomy() ) {
			return true;
		}
		return is_singular( 'product' );
	}

	/**
	 * @param string $template Path.
	 */
	public static function template_include( string $template ): string {
		if ( ! self::uses_shell() ) {
			return $template;
		}
		$file = PM_SHELL_PATH . 'templates/pm-wc-app.php';
		return is_readable( $file ) ? $file : $template;
	}

	/**
	 * @param array<int, string> $classes Classes.
	 * @return array<int, string>
	 */
	public static function body_class( array $classes ): array {
		if ( self::uses_shell() ) {
			$classes[] = 'pm-site';
			$classes[] = 'pm-wc-shell';
		}
		return $classes;
	}

	public static function enqueue(): void {
		if ( ! self::uses_shell() ) {
			return;
		}
		$home = 'https://www.pakanmental.com/wp-content/plugins/pakanmental-home/';
		$ver  = defined( 'PM_HOME_VERSION' ) ? PM_HOME_VERSION : '1.1.2';
		wp_enqueue_style(
			'pm-fonts',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);
		wp_enqueue_style( 'pm-brand-overrides', $home . 'assets/css/pm-brand-overrides.css', array(), $ver );
		wp_enqueue_style( 'pm-site', $home . 'assets/css/pm.css', array( 'pm-fonts', 'pm-brand-overrides' ), $ver );
		wp_add_inline_style(
			'pm-site',
			'html body.pm-wc-shell .site-header,html body.pm-wc-shell header#site-header,html body.pm-wc-shell .dynamic-header,html body.pm-wc-shell .site-footer,html body.pm-wc-shell footer#site-footer{display:none!important}'
			. '.pm-product-single .pm-product-single__grid{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr)!important;gap:1.5rem!important;align-items:start!important;width:min(1200px,92vw)!important;margin:0 auto!important}'
			. '.pm-game-specs{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:.55rem!important;list-style:none!important;margin:0 0 1.15rem!important;padding:0!important;width:100%!important}'
			. '.pm-game-spec{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;aspect-ratio:1/1!important;width:100%!important;padding:.45rem .3rem!important;text-align:center!important;box-sizing:border-box!important;background:linear-gradient(180deg,#fff 0%,#e8f6f1 100%)!important;border:1.5px solid #4aa88e!important;border-radius:18px!important}'
			. '@media(max-width:768px){.pm-product-single .pm-product-single__grid{grid-template-columns:1fr!important}}'
		);
		foreach ( array( 'pm-header', 'pm-mega-menu', 'pm-mobile', 'pm-search', 'pm-trust-badges' ) as $handle ) {
			wp_enqueue_script( $handle, $home . 'assets/js/' . $handle . '.js', array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
		if ( function_exists( 'wc_get_cart_url' ) ) {
			$cfg = array(
				'storeApi'    => rest_url( 'wc/store/v1' ),
				'cartUrl'     => wc_get_cart_url(),
				'checkoutUrl' => wc_get_checkout_url(),
				'shopUrl'     => wc_get_page_permalink( 'shop' ),
			);
			wp_register_script( 'pm-shell-cart-cfg', false, array(), PM_SHELL_VERSION, true );
			wp_enqueue_script( 'pm-shell-cart-cfg' );
			wp_add_inline_script( 'pm-shell-cart-cfg', 'window.pmCart=' . wp_json_encode( $cfg ) . ';' );
		}
		if ( is_singular( 'product' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
			wp_enqueue_script( 'woocommerce' );
		}
	}

	public static function render_header(): void {
		include PM_SHELL_PATH . 'templates/partials/pm-header.php';
	}

	public static function render_footer(): void {
		include PM_SHELL_PATH . 'templates/partials/pm-footer.php';
	}

	public static function attributes_html( int $product_id ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return '';
		}
		$rows = array();
		foreach ( $product->get_attributes() as $attribute ) {
			$taxonomy = is_object( $attribute ) ? $attribute->get_name() : '';
			$label    = wc_attribute_label( $taxonomy, $product );
			$value    = '';
			if ( is_object( $attribute ) && $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
				if ( is_array( $terms ) ) {
					$value = implode( '، ', array_map( 'strval', $terms ) );
				}
			} elseif ( is_object( $attribute ) ) {
				$opts = $attribute->get_options();
				if ( is_array( $opts ) ) {
					$value = implode( '، ', array_map( 'strval', $opts ) );
				}
			}
			$value = trim( $value );
			if ( '' === $value || '' === $label ) {
				continue;
			}
			$rows[] = array( $label, $value );
		}
		if ( ! $rows ) {
			return '';
		}
		$html = '<ul class="pm-game-specs" aria-label="' . esc_attr__( 'ویژگی‌های محصول', 'pakanmental-shell' ) . '">';
		foreach ( $rows as $row ) {
			$html .= '<li class="pm-game-spec"><span class="pm-game-spec__label">' . esc_html( $row[0] ) . '</span><span class="pm-game-spec__value">' . esc_html( $row[1] ) . '</span></li>';
		}
		$html .= '</ul>';
		return $html;
	}
}
