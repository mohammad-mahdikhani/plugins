<?php
/**
 * ووکامرس: کارت، ارسال از خود ووکامرس، دکمه خرید.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('wp', 'moon_skin_wc_hooks');

/**
 * قلاب‌ها فقط وقتی ووکامرس هست.
 */
function moon_skin_wc_hooks() {
	if (!function_exists('WC')) {
		return;
	}
	remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
	remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
	add_action('woocommerce_before_main_content', 'moon_skin_wc_open', 10);
	add_action('woocommerce_after_main_content', 'moon_skin_wc_close', 10);
	add_action('woocommerce_single_product_summary', 'moon_skin_shipping_box', 25);
	add_filter('woocommerce_loop_add_to_cart_link', 'moon_skin_loop_button', 10, 2);
	add_filter('pre_option_woocommerce_cart_redirect_after_add', 'moon_skin_no_cart_redirect');
}

/**
 * باز کردن پوشش فروشگاه.
 */
function moon_skin_wc_open() {
	echo '<div class="moon-wrap moon-wc">';
}

/**
 * بستن پوشش فروشگاه.
 */
function moon_skin_wc_close() {
	echo '</div>';
}

/**
 * بعد از افزودن به سبد، همان صفحه بماند.
 *
 * @return string
 */
function moon_skin_no_cart_redirect() {
	return 'no';
}

/**
 * دکمه حلقه فروشگاه برای سبد کناری.
 *
 * @param string     $html    Default link.
 * @param WC_Product $product Product.
 * @return string
 */
function moon_skin_loop_button($html, $product) {
	if (!$product || !is_a($product, 'WC_Product')) {
		return $html;
	}
	if (!$product->is_purchasable() || !$product->is_in_stock() || $product->is_type('variable') || $product->is_type('grouped')) {
		return '<a class="button" href="' . esc_url($product->get_permalink()) . '">' . esc_html($product->add_to_cart_text()) . '</a>';
	}
	return '<button type="button" class="button moon-add" data-add="' . esc_attr((string) $product->get_id()) . '">' . esc_html($product->add_to_cart_text()) . '</button>';
}

/**
 * برچسب روش‌های ارسالی که ووکامرس برای این محصول حساب می‌کند.
 *
 * @param WC_Product|null $product Product.
 * @return string[]
 */
function moon_skin_shipping_labels($product = null) {
	if (!function_exists('WC') || !WC()->shipping() || !WC()->customer) {
		return array();
	}
	if ($product && is_a($product, 'WC_Product') && !$product->needs_shipping()) {
		return array();
	}
	$customer = WC()->customer;
	$country  = $customer->get_shipping_country();
	$state    = $customer->get_shipping_state();
	if ($country === '' && function_exists('wc_get_base_location')) {
		$base    = wc_get_base_location();
		$country = isset($base['country']) ? $base['country'] : '';
		$state   = isset($base['state']) ? $base['state'] : '';
	}
	$contents = array();
	$cost     = 0;
	if ($product && is_a($product, 'WC_Product')) {
		$cost       = (float) $product->get_price();
		$contents[] = array(
			'data'               => $product,
			'quantity'           => 1,
			'line_total'         => $cost,
			'line_tax'           => 0,
			'line_subtotal'      => $cost,
			'line_subtotal_tax'  => 0,
		);
	}
	$package = array(
		'contents'        => $contents,
		'contents_cost'   => $cost,
		'applied_coupons' => WC()->cart ? WC()->cart->get_applied_coupons() : array(),
		'user'            => array('ID' => get_current_user_id()),
		'destination'     => array(
			'country'   => $country,
			'state'     => $state,
			'postcode'  => $customer->get_shipping_postcode(),
			'city'      => $customer->get_shipping_city(),
			'address'   => $customer->get_shipping_address(),
			'address_2' => $customer->get_shipping_address_2(),
		),
		'cart_subtotal'   => $cost,
	);
	$shipping   = WC()->shipping();
	$calculated = $shipping->calculate_shipping_for_package($package, 'moon_skin_preview');
	if (isset($shipping->packages['moon_skin_preview'])) {
		unset($shipping->packages['moon_skin_preview']);
	}
	$rates  = (is_array($calculated) && isset($calculated['rates']) && is_array($calculated['rates'])) ? $calculated['rates'] : array();
	$labels = array();
	foreach ($rates as $rate) {
		if (!is_object($rate) || !method_exists($rate, 'get_label')) {
			continue;
		}
		$text = function_exists('wc_cart_totals_shipping_method_label') ? wp_strip_all_tags(wc_cart_totals_shipping_method_label($rate)) : $rate->get_label();
		$text = trim(preg_replace('/\s+/', ' ', (string) $text));
		if ($text !== '') {
			$labels[] = $text;
		}
	}
	return $labels;
}

/**
 * جعبه ارسال روی محصول.
 */
function moon_skin_shipping_box() {
	if (!function_exists('wc_get_product')) {
		return;
	}
	$product = wc_get_product();
	if (!$product) {
		return;
	}
	$labels = moon_skin_shipping_labels($product);
	if (!$labels) {
		return;
	}
	echo '<div class="moon-ship"><div><b>' . esc_html__('ارسال', 'moon-skin') . '</b><ul>';
	foreach ($labels as $label) {
		echo '<li>' . esc_html($label) . '</li>';
	}
	echo '</ul></div></div>';
}
