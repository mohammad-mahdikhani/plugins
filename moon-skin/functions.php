<?php
/**
 * مون‌اسکین — راه‌اندازی قالب.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

define('MOON_SKIN_VERSION', '1.1.0');

require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/release.php';
require get_template_directory() . '/inc/woocommerce.php';

add_action('after_setup_theme', 'moon_skin_setup');

/**
 * پشتیبانی‌های قالب.
 */
function moon_skin_setup() {
	load_theme_textdomain('moon-skin', get_template_directory() . '/languages');

	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('custom-logo', array(
		'height'      => 80,
		'width'       => 240,
		'flex-height' => true,
		'flex-width'  => true,
	));
	add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
	add_theme_support('automatic-feed-links');
	add_theme_support('responsive-embeds');
	add_theme_support('align-wide');
	add_theme_support('woocommerce', array(
		'thumbnail_image_width' => 640,
		'single_image_width'    => 900,
	));
	add_theme_support('wc-product-gallery-lightbox');
	add_theme_support('wc-product-gallery-slider');

	register_nav_menus(array(
		'primary' => __('منوی اصلی', 'moon-skin'),
		'footer'  => __('منوی پاصفحه', 'moon-skin'),
	));

	add_image_size('moon-card', 640, 640, true);
}

add_action('elementor/theme/register_locations', 'moon_skin_elementor_locations');

/**
 * اگر المنتور روی سایت باشد، جایگاه هدر و فوتر را می‌شناسد.
 *
 * @param object $manager Elementor locations manager.
 */
function moon_skin_elementor_locations($manager) {
	if (is_object($manager) && method_exists($manager, 'register_all_core_location')) {
		$manager->register_all_core_location();
	}
}

add_action('wp_enqueue_scripts', 'moon_skin_assets');

/**
 * سبک و اسکریپت.
 */
function moon_skin_assets() {
	wp_enqueue_style('moon-skin', get_stylesheet_uri(), array(), MOON_SKIN_VERSION);
	wp_enqueue_style('moon-skin-main', get_theme_file_uri('assets/css/theme.css'), array('moon-skin'), MOON_SKIN_VERSION);

	wp_enqueue_script('moon-skin', get_theme_file_uri('assets/js/theme.js'), array(), MOON_SKIN_VERSION, true);

	$cart = '';
	if (function_exists('WC')) {
		$cart = esc_url_raw(rest_url('wc/store/v1/cart'));
	}

	wp_localize_script('moon-skin', 'moonSkin', array(
		'cart'  => $cart,
		'home'  => esc_url_raw(home_url('/')),
		'ajax'  => esc_url_raw(add_query_arg('wc-ajax', '%%endpoint%%', home_url('/'))),
		'steps' => moon_skin_on('moon_skin_checkout_steps') ? 1 : 0,
		'i18n'  => array(
			'night' => moon_skin_mod('moon_skin_night_label'),
			'day'   => moon_skin_mod('moon_skin_day_label'),
			'empty' => moon_skin_mod('moon_skin_cart_empty'),
			'pay'   => moon_skin_mod('moon_skin_pay_label'),
			'cart'  => moon_skin_mod('moon_skin_cart_title'),
			'step1' => moon_skin_mod('moon_skin_step_1'),
			'step2' => moon_skin_mod('moon_skin_step_2'),
			'step3' => moon_skin_mod('moon_skin_step_3'),
			'back'  => moon_skin_mod('moon_skin_back'),
			'next'  => moon_skin_mod('moon_skin_next'),
			'ship'  => moon_skin_mod('moon_skin_ship_label'),
		),
	));
}

add_action('wp_head', 'moon_skin_night_boot', 0);

/**
 * حالت شب قبل از رنگ‌شدن صفحه.
 */
function moon_skin_night_boot() {
	echo "<script>try{var root=document.documentElement;var s=localStorage.getItem('moon_night');var preset=root.getAttribute('data-night');var allow=root.getAttribute('data-night-toggle');if(allow==='1'){if(s==='1'||(s===null&&preset==='1'))root.classList.add('is-night')}else if(preset==='1'){root.classList.add('is-night')}}catch(e){}</script>\n";
}

add_filter('language_attributes', 'moon_skin_html_attrs');

/**
 * حالت شب پیش‌فرض را قبل از رسم صفحه می‌گذارد.
 *
 * @param string $output Language attributes.
 * @return string
 */
function moon_skin_html_attrs($output) {
	$output .= ' data-night="' . (moon_skin_on('moon_skin_night_default') ? '1' : '0') . '"';
	$output .= ' data-night-toggle="' . (moon_skin_on('moon_skin_show_night') ? '1' : '0') . '"';
	return $output;
}

add_action('wp_head', 'moon_skin_accent_css', 20);

/**
 * رنگ اصلی از سفارشی‌سازی.
 */
function moon_skin_accent_css() {
	$vars = array(
		'--moon'         => moon_skin_color('moon_skin_accent'),
		'--moon-2'       => moon_skin_color('moon_skin_accent'),
		'--on'           => moon_skin_color('moon_skin_on'),
		'--ink'          => moon_skin_color('moon_skin_ink'),
		'--muted'        => moon_skin_color('moon_skin_muted'),
		'--bg'           => moon_skin_color('moon_skin_bg'),
		'--card'         => moon_skin_color('moon_skin_card'),
		'--soft'         => moon_skin_color('moon_skin_soft'),
		'--line'         => moon_skin_color('moon_skin_line'),
		'--bar'          => moon_skin_color('moon_skin_bar'),
		'--footer'       => moon_skin_color('moon_skin_footer_bg'),
		'--footer-ink'   => moon_skin_color('moon_skin_footer_ink'),
		'--night-bg'     => moon_skin_color('moon_skin_night_bg'),
		'--night-card'   => moon_skin_color('moon_skin_night_card'),
		'--night-ink'    => moon_skin_color('moon_skin_night_ink'),
		'--night-soft'   => moon_skin_color('moon_skin_night_soft'),
		'--moon-width'   => (int) moon_skin_mod('moon_skin_width') . 'px',
		'--radius'       => (int) moon_skin_mod('moon_skin_radius') . 'px',
		'--btn-radius'   => (int) moon_skin_mod('moon_skin_button_radius') . 'px',
		'--shop-cols'    => (int) moon_skin_mod('moon_skin_shop_columns'),
		'--blog-cols'    => (int) moon_skin_mod('moon_skin_blog_columns'),
	);
	$rules = array();
	foreach ($vars as $name => $value) {
		$rules[] = $name . ':' . $value;
	}
	$css = ':root{' . implode(';', $rules) . '}';
	if ('system' === moon_skin_mod('moon_skin_font')) {
		$css .= 'body{--font:Tahoma,sans-serif}';
	}
	echo '<style id="moon-skin-custom">' . $css . '</style>';
}

add_filter('body_class', 'moon_skin_body_class');

/**
 * کلاس بدنه.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function moon_skin_body_class($classes) {
	$classes[] = 'moon-skin';
	if (!moon_skin_on('moon_skin_show_search')) {
		$classes[] = 'moon-no-search';
	}
	if (!moon_skin_on('moon_skin_show_account')) {
		$classes[] = 'moon-no-account';
	}
	if (!moon_skin_on('moon_skin_show_cart')) {
		$classes[] = 'moon-no-cart';
	}
	if (!moon_skin_on('moon_skin_show_night')) {
		$classes[] = 'moon-no-night';
	}
	if (!moon_skin_on('moon_skin_show_mega')) {
		$classes[] = 'moon-no-mega';
	}
	if (!moon_skin_on('moon_skin_show_dock')) {
		$classes[] = 'moon-no-dock';
	}
	if (moon_skin_on('moon_skin_checkout_steps')) {
		$classes[] = 'moon-steps-on';
	}
	return $classes;
}

add_action('widgets_init', 'moon_skin_widgets');

/**
 * ستون پاصفحه.
 */
function moon_skin_widgets() {
	register_sidebar(array(
		'name'          => __('پاصفحه', 'moon-skin'),
		'id'            => 'footer',
		'before_widget' => '<section class="moon-widget">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2>',
		'after_title'   => '</h2>',
	));
}

/**
 * اگر المنتور این جایگاه را نساخته باشد، قطعه قالب را نشان بده.
 *
 * @param string $location Elementor location.
 * @param string $slug     Template part slug.
 */
function moon_skin_part($location, $slug) {
	if (function_exists('elementor_theme_do_location') && elementor_theme_do_location($location)) {
		return;
	}
	get_template_part('template-parts/' . $slug);
}

add_action('init', 'moon_skin_bind_demo', 40);

/**
 * اگر فایل دمو وارد شده باشد و جایگاه‌ها خالی باشند، خانه و فهرست‌ها را می‌گذارد.
 */
function moon_skin_bind_demo() {
	if (get_option('moon_skin_demo_bound')) {
		return;
	}
	$home    = get_page_by_path('moon-home');
	$primary = wp_get_nav_menu_object('منوی اصلی');
	$footer  = wp_get_nav_menu_object('منوی پاصفحه');
	if (!$home || !$primary || !$footer) {
		return;
	}

	$locations = get_theme_mod('nav_menu_locations', array());
	if (!is_array($locations)) {
		$locations = array();
	}
	if (empty($locations['primary'])) {
		$locations['primary'] = (int) $primary->term_id;
	}
	if (empty($locations['footer'])) {
		$locations['footer'] = (int) $footer->term_id;
	}
	set_theme_mod('nav_menu_locations', $locations);

	if ('posts' === get_option('show_on_front') && !(int) get_option('page_on_front')) {
		update_option('show_on_front', 'page');
		update_option('page_on_front', (int) $home->ID);
		$blog = get_page_by_path('moon-magazine');
		if ($blog && !(int) get_option('page_for_posts')) {
			update_option('page_for_posts', (int) $blog->ID);
		}
	}

	update_option('moon_skin_demo_bound', '1', false);
}
