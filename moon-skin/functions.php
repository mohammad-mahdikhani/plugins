<?php
/**
 * مون‌اسکین — راه‌اندازی قالب.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

define('MOON_SKIN_VERSION', '1.0.0');

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
		'i18n'  => array(
			'night' => __('حالت شب', 'moon-skin'),
			'day'   => __('حالت روز', 'moon-skin'),
			'empty' => __('سبد خرید خالی است.', 'moon-skin'),
			'pay'   => __('تسویه حساب', 'moon-skin'),
			'cart'  => __('سبد خرید', 'moon-skin'),
		),
	));
}

add_action('wp_head', 'moon_skin_night_boot', 0);

/**
 * حالت شب قبل از رنگ‌شدن صفحه.
 */
function moon_skin_night_boot() {
	echo "<script>try{if(localStorage.getItem('moon_night')==='1')document.documentElement.classList.add('is-night')}catch(e){}</script>\n";
}

add_action('wp_head', 'moon_skin_accent_css', 20);

/**
 * رنگ اصلی از سفارشی‌سازی.
 */
function moon_skin_accent_css() {
	$accent = sanitize_hex_color(get_theme_mod('moon_skin_accent', '#4c1d95'));
	if (!$accent) {
		$accent = '#4c1d95';
	}
	echo '<style>:root{--moon:' . esc_attr($accent) . ';--moon-2:' . esc_attr($accent) . '}</style>';
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
