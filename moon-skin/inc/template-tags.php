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
 * مقدارهای پیش‌فرض سفارشی‌سازی.
 *
 * @return array<string,mixed>
 */
function moon_skin_defaults() {
	return array(
		'moon_skin_accent'            => '#4c1d95',
		'moon_skin_on'                => '#ffffff',
		'moon_skin_ink'               => '#1b1230',
		'moon_skin_muted'             => '#7a7088',
		'moon_skin_bg'                => '#f5f0f8',
		'moon_skin_card'              => '#ffffff',
		'moon_skin_soft'              => '#f6f1fb',
		'moon_skin_line'              => '#eadff5',
		'moon_skin_bar'               => '#ffffff',
		'moon_skin_footer_bg'         => '#1b1028',
		'moon_skin_footer_ink'        => '#ffffff',
		'moon_skin_night_bg'          => '#120b1c',
		'moon_skin_night_card'        => '#241833',
		'moon_skin_night_ink'         => '#f6f1fb',
		'moon_skin_night_soft'        => '#2a1844',
		'moon_skin_width'             => 1220,
		'moon_skin_radius'            => 28,
		'moon_skin_button_radius'     => 999,
		'moon_skin_shop_columns'      => '4',
		'moon_skin_blog_columns'      => '3',
		'moon_skin_font'              => 'vazir',
		'moon_skin_products_label'    => 'محصولات',
		'moon_skin_search_placeholder'=> 'جستجو',
		'moon_skin_show_search'       => 1,
		'moon_skin_show_account'      => 1,
		'moon_skin_show_cart'         => 1,
		'moon_skin_show_night'        => 1,
		'moon_skin_night_default'     => 0,
		'moon_skin_show_mega'         => 1,
		'moon_skin_menu_count'        => 6,
		'moon_skin_label_home'        => 'خانه',
		'moon_skin_label_shop'        => 'فروشگاه',
		'moon_skin_label_blog'        => 'مجله',
		'moon_skin_label_account'     => 'حساب کاربری',
		'moon_skin_label_cart'        => 'سبد',
		'moon_skin_show_hero'         => 1,
		'moon_skin_kicker'            => '',
		'moon_skin_hero_title'        => '',
		'moon_skin_hero_text'         => '',
		'moon_skin_show_hero_button'  => 1,
		'moon_skin_hero_button'       => 'مشاهده فروشگاه',
		'moon_skin_hero_url'          => '',
		'moon_skin_show_cats'         => 1,
		'moon_skin_show_products'     => 1,
		'moon_skin_home_count'        => 8,
		'moon_skin_show_shipping'     => 1,
		'moon_skin_shipping_title'    => 'ارسال',
		'moon_skin_show_breadcrumb'   => 1,
		'moon_skin_show_post_image'   => 1,
		'moon_skin_show_date'         => 1,
		'moon_skin_footer_about'      => '',
		'moon_skin_phone'             => '',
		'moon_skin_email'             => '',
		'moon_skin_address'           => '',
		'moon_skin_instagram'         => '',
		'moon_skin_telegram'          => '',
		'moon_skin_whatsapp'          => '',
		'moon_skin_copyright'         => '',
		'moon_skin_show_dock'         => 1,
		'moon_skin_cart_title'        => 'سبد خرید',
		'moon_skin_cart_empty'        => 'سبد خرید خالی است.',
		'moon_skin_pay_label'         => 'تسویه حساب',
		'moon_skin_checkout_steps'    => 1,
		'moon_skin_step_1'            => 'مشخصات',
		'moon_skin_step_2'            => 'ارسال',
		'moon_skin_step_3'            => 'پرداخت',
		'moon_skin_back'              => 'بازگشت',
		'moon_skin_next'              => 'ادامه',
		'moon_skin_ship_label'        => 'روش ارسال',
		'moon_skin_night_label'       => 'حالت شب',
		'moon_skin_day_label'         => 'حالت روز',
	);
}

/**
 * یک تنظیم قالب.
 *
 * @param string $key Setting id.
 * @return mixed
 */
function moon_skin_mod($key) {
	$defaults = moon_skin_defaults();
	$default  = array_key_exists($key, $defaults) ? $defaults[$key] : '';
	$value    = get_theme_mod($key, $default);
	if (null === $value || false === $value) {
		return $default;
	}
	return $value;
}

/**
 * تنظیم روشن است یا نه.
 *
 * @param string $key Setting id.
 * @return bool
 */
function moon_skin_on($key) {
	return (int) moon_skin_mod($key) === 1;
}

/**
 * رنگ معتبر، وگرنه پیش‌فرض.
 *
 * @param string $key Setting id.
 * @return string
 */
function moon_skin_color($key) {
	$color = sanitize_hex_color((string) moon_skin_mod($key));
	if (!$color) {
		$defaults = moon_skin_defaults();
		$color    = isset($defaults[$key]) ? (string) $defaults[$key] : '#4c1d95';
	}
	return $color;
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
	if (!moon_skin_on('moon_skin_show_breadcrumb')) {
		return;
	}
	echo '<nav class="moon-bc" aria-label="' . esc_attr__('مسیر', 'moon-skin') . '">';
	echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html(moon_skin_mod('moon_skin_label_home')) . '</a>';
	echo '<span aria-hidden="true">/</span>';
	echo '<a href="' . esc_url(moon_skin_blog_url()) . '">' . esc_html(moon_skin_mod('moon_skin_label_blog')) . '</a>';
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
		'number'     => max(1, (int) moon_skin_mod('moon_skin_menu_count')),
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
