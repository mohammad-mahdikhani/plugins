<?php
/**
 * Checkout required labels were showing two asterisks.
 * Keep the one WooCommerce prints and drop the generated extra.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		$checkout = function_exists( 'is_checkout' ) && is_checkout();
		$account  = function_exists( 'is_account_page' ) && is_account_page();
		if ( ! $checkout && ! $account ) {
			return;
		}
		echo '<style id="pm-single-required-star">'
			. '.woocommerce form .form-row label.required_field::before,.woocommerce form .form-row label.required_field::after,.woocommerce form .form-row .required::before,.woocommerce form .form-row .required::after,.woocommerce form .form-row abbr.required::before,.woocommerce form .form-row abbr.required::after{content:none!important;display:none!important}'
			. '.woocommerce form .form-row .required,.woocommerce form .form-row abbr.required{visibility:visible!important;color:#c23b3b;font-weight:700}'
			. '</style>';
	},
	99
);
