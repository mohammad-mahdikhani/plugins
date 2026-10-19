<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Enqueue homepage styles and scripts.
 */
function elinor_homepage_enqueue_assets(): void {
	wp_enqueue_style(
		'elinor-beauties-fonts',
		'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'elinor-beauties-341555',
		ELINOR_HELLO_URI . '/assets/css/beauties-341555.css',
		['elinor-beauties-fonts'],
		ELINOR_HELLO_VERSION
	);

	wp_enqueue_script(
		'elinor-beauties-homepage',
		ELINOR_HELLO_URI . '/assets/js/homepage.js',
		[],
		ELINOR_HELLO_VERSION,
		true
	);
}

/**
 * Render Beauties homepage markup.
 */
function elinor_render_homepage(): string {
	$products     = Elinor_Home_Data::get_featured_products(8);
	$deal_product = Elinor_Home_Data::get_on_sale_products(1);
	$deal         = $deal_product[0] ?? ($products[0] ?? null);
	$categories   = Elinor_Home_Data::get_category_cards(4);
	$posts        = Elinor_Home_Data::get_magazine_posts(4);

	$shop_url    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
	$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
	$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
	$logo_id     = (int) get_theme_mod('custom_logo');
	$logo_url    = $logo_id ? (string) wp_get_attachment_image_url($logo_id, 'medium') : 'https://elinorstore.com/wp-content/uploads/2026/08/%D9%84%D9%88%DA%AF%D9%88.jpg';
	$hero_image  = 'https://elinorstore.com/wp-content/uploads/2026/01/IMG_3467-2-1024x533.jpeg';

	$reviews = [
		[
			'text'  => 'کیفیت محصولات عالیه و بسته‌بندی خیلی شیک بود. حتماً دوباره خرید می‌کنم.',
			'name'  => 'سارا محمدی',
			'stars' => 5,
		],
		[
			'text'  => 'ارسال سریع و مشاوره خوب قبل از خرید. رژ لب‌ها کاملاً مطابق توضیحات سایت بود.',
			'name'  => 'نیلوفر رضایی',
			'stars' => 5,
		],
		[
			'text'  => 'برای پوست حساسم سرم آنوا رو گرفتم؛ بعد از یک هفته تفاوت محسوس بود.',
			'name'  => 'مریم کاظمی',
			'stars' => 5,
		],
	];

	elinor_homepage_enqueue_assets();

	ob_start();
	include ELINOR_HELLO_PATH . '/template-parts/homepage-content.php';

	return (string) ob_get_clean();
}

/**
 * Shortcode for Elementor / classic editor.
 */
function elinor_homepage_shortcode(): string {
	return elinor_render_homepage();
}

/**
 * Page template loader.
 */
function elinor_homepage_page_template(string $template): string {
	if (!is_page()) {
		return $template;
	}

	$slug = get_page_template_slug();
	if ($slug === 'page-templates/beauties-home.php') {
		$custom = ELINOR_HELLO_PATH . 'page-templates/beauties-home.php';
		if (file_exists($custom)) {
			return $custom;
		}
	}

	return $template;
}
