<?php
/**
 * Plugin Name: AquaSafe Purchase Card
 * Description: کارت خرید صفحه محصول: مبلغ و تومان یک واحد، برچسب کوچک قیمت، تاریخ به‌روزرسانی در یک خط، دکمه سبد و گالری داخل همان کارت.
 * Version: 1.0.0
 * Author: AquaSafe
 * Text Domain: aquasafe-purchase-card
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$style = plugin_dir_path( __FILE__ ) . 'assets/purchase-card.css';
		$script = plugin_dir_path( __FILE__ ) . 'assets/purchase-card.js';
		wp_enqueue_style(
			'as-purchase-card',
			plugin_dir_url( __FILE__ ) . 'assets/purchase-card.css',
			array(),
			file_exists( $style ) ? (string) filemtime( $style ) : '1.0.0'
		);
		wp_enqueue_script(
			'as-purchase-card',
			plugin_dir_url( __FILE__ ) . 'assets/purchase-card.js',
			array(),
			file_exists( $script ) ? (string) filemtime( $script ) : '1.0.0',
			true
		);
	},
	30
);
