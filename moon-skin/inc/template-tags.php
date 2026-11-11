<?php
/**
 * قطعه‌های مشترک.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * شماره سبد.
 *
 * @return int
 */
function moon_skin_cart_count() {
	if (!function_exists('WC') || !WC()->cart) {
		return 0;
	}
	return (int) WC()->cart->get_cart_contents_count();
}

/**
 * نشانی فروشگاه.
 *
 * @return string
 */
function moon_skin_shop_url() {
	if (function_exists('wc_get_page_permalink')) {
		$url = wc_get_page_permalink('shop');
		if ($url) {
			return $url;
		}
	}
	return home_url('/');
}

/**
 * نشانی مجله.
 *
 * @return string
 */
function moon_skin_blog_url() {
	$posts = (int) get_option('page_for_posts');
	if ($posts) {
		return get_permalink($posts);
	}
	return get_post_type_archive_link('post') ?: home_url('/');
}

/**
 * بردکرامب نوشته.
 */
function moon_skin_breadcrumb() {
	if (!is_singular('post')) {
		return;
	}
	echo '<nav class="moon-bc" aria-label="' . esc_attr__('مسیر', 'moon-skin') . '">';
	echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('خانه', 'moon-skin') . '</a>';
	echo '<span aria-hidden="true">/</span>';
	echo '<a href="' . esc_url(moon_skin_blog_url()) . '">' . esc_html__('مجله', 'moon-skin') . '</a>';
	echo '<span aria-hidden="true">/</span>';
	echo '<b>' . esc_html(get_the_title()) . '</b>';
	echo '</nav>';
}

/**
 * دسته‌های بالای فروشگاه برای منو.
 *
 * @return WP_Term[]
 */
function moon_skin_top_cats() {
	if (!taxonomy_exists('product_cat')) {
		return array();
	}
	$terms = get_terms(array(
		'taxonomy'   => 'product_cat',
		'parent'     => 0,
		'hide_empty' => true,
		'number'     => 6,
	));
	return is_array($terms) ? $terms : array();
}

/**
 * لوگو یا واژه‌نشان.
 */
function moon_skin_logo() {
	if (has_custom_logo()) {
		the_custom_logo();
		return;
	}
	$name = get_bloginfo('name');
	echo '<a class="moon-logo" href="' . esc_url(home_url('/')) . '"><b>' . esc_html($name ? $name : 'MOON SKIN') . '</b></a>';
}
