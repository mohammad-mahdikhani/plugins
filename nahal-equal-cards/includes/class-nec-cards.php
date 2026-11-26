<?php
defined( 'ABSPATH' ) || exit;

class NEC_Cards {
	const BUTTON_TEXT = 'افزودن به سبد خرید';
	const VIEW_TEXT   = 'مشاهده دوره';

	/**
	 * @var self|null
	 */
	protected static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 40 );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'loop_button' ), 14 );
	}

	public function enqueue() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'nec-cards',
			NEC_URL . 'assets/css/cards.css',
			array(),
			NEC_VERSION
		);

		wp_enqueue_script(
			'nec-cards',
			NEC_URL . 'assets/js/cards.js',
			array( 'jquery' ),
			NEC_VERSION,
			true
		);

		if ( function_exists( 'WC' ) && ! wp_script_is( 'wc-add-to-cart', 'enqueued' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}

		wp_localize_script(
			'nec-cards',
			'necCards',
			array(
				'addToCart' => self::BUTTON_TEXT,
				'viewCourse' => self::VIEW_TEXT,
			)
		);
	}

	public function loop_button() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			$product = wc_get_product( get_the_ID() );
		}
		if ( ! $product ) {
			return;
		}

		echo self::button_markup( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param object $product WooCommerce product.
	 */
	public static function button_markup( $product ) {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return '';
		}

		$id   = (int) $product->get_id();
		$type = method_exists( $product, 'get_type' ) ? (string) $product->get_type() : 'simple';
		$url  = method_exists( $product, 'get_permalink' ) ? (string) $product->get_permalink() : '';
		$ajax = ( 'simple' === $type && method_exists( $product, 'is_purchasable' ) && $product->is_purchasable() && method_exists( $product, 'is_in_stock' ) && $product->is_in_stock() );

		if ( $ajax && method_exists( $product, 'add_to_cart_url' ) ) {
			$url = (string) $product->add_to_cart_url();
		}

		$text  = $ajax ? self::BUTTON_TEXT : self::VIEW_TEXT;
		$class = 'button nec-add-to-cart';
		$attrs = ' data-product_id="' . esc_attr( (string) $id ) . '" data-quantity="1" rel="nofollow"';

		if ( $ajax ) {
			$class .= ' add_to_cart_button ajax_add_to_cart product_type_simple';
			$attrs .= ' data-product_sku="' . esc_attr( method_exists( $product, 'get_sku' ) ? (string) $product->get_sku() : '' ) . '"';
		}

		return '<div class="nec-add-to-cart-wrap">'
			. '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '"' . $attrs . '>'
			. '<i class="fal fa-shopping-bag" aria-hidden="true"></i>'
			. '<span>' . esc_html( $text ) . '</span>'
			. '</a></div>';
	}

	public static function product_id_from_class( $class_attr ) {
		if ( ! is_string( $class_attr ) || '' === $class_attr ) {
			return 0;
		}
		if ( preg_match( '/(?:^|\s)post-(\d+)(?:\s|$)/', $class_attr, $m ) ) {
			return (int) $m[1];
		}
		return 0;
	}
}
