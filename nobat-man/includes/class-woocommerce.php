<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_WooCommerce {
	public static function init() {
		add_action(
			'before_woocommerce_init',
			static function () {
				if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', NOBAT_MAN_FILE, true );
				}
			}
		);
	}

	public static function maybe_checkout( $booking ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return '';
		}
		$product_id = absint( get_option( 'nobat_man_wc_product', 0 ) );
		if ( ! $product_id ) {
			$product_id = self::ensure_product();
		}
		if ( ! $product_id ) {
			return '';
		}
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart(
			$product_id,
			1,
			0,
			array(),
			array(
				'nobat_code' => $booking['code'],
				'nobat_date' => $booking['date'],
			)
		);
		return wc_get_checkout_url();
	}

	private static function ensure_product() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return 0;
		}
		$post_id = wp_insert_post(
			array(
				'post_title'  => 'جلسه مشاوره نوبت من',
				'post_status' => 'publish',
				'post_type'   => 'product',
			)
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}
		update_post_meta( $post_id, '_price', nobat_man_settings()['price'] );
		update_post_meta( $post_id, '_regular_price', nobat_man_settings()['price'] );
		update_post_meta( $post_id, '_virtual', 'yes' );
		update_post_meta( $post_id, '_sold_individually', 'yes' );
		update_option( 'nobat_man_wc_product', $post_id );
		return $post_id;
	}
}
