<?php
/**
 * Elinor Hello Elementor Child
 *
 * @package Hello_Elementor_Elinor
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

define('ELINOR_HELLO_VERSION', '1.1.0');
define('ELINOR_HELLO_PATH', get_stylesheet_directory());
define('ELINOR_HELLO_URI', get_stylesheet_directory_uri());

require_once ELINOR_HELLO_PATH . '/includes/class-elinor-home-data.php';
require_once ELINOR_HELLO_PATH . '/includes/render-homepage.php';

/**
 * Enqueue parent + child styles.
 */
function elinor_hello_enqueue_styles(): void {
	$parent     = 'hello-elementor';
	$parent_ver = wp_get_theme($parent)->get('Version');

	wp_enqueue_style(
		$parent . '-style',
		get_template_directory_uri() . '/style.css',
		[],
		$parent_ver
	);

	wp_enqueue_style(
		'elinor-hello-child',
		ELINOR_HELLO_URI . '/style.css',
		[ $parent . '-style' ],
		ELINOR_HELLO_VERSION
	);
}
add_action('wp_enqueue_scripts', 'elinor_hello_enqueue_styles', 20);

/**
 * Body class for Beauties CSS on homepage views.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function elinor_hello_body_class(array $classes): array {
	if (is_front_page() || is_page_template('page-templates/beauties-home.php')) {
		$classes[] = 'elinor-beauties-page';
	}

	return $classes;
}
add_filter('body_class', 'elinor_hello_body_class');

add_shortcode('elinor_beauties_home', 'elinor_homepage_shortcode');
add_filter('template_include', 'elinor_homepage_page_template', 99);

/**
 * Auto-inject homepage on front page when enabled (theme mod).
 */
function elinor_maybe_replace_front_page_content(string $content): string {
	if (!is_main_query() || !in_the_loop() || !is_front_page()) {
		return $content;
	}

	if (!get_theme_mod('elinor_auto_home', false)) {
		return $content;
	}

	return elinor_render_homepage();
}
add_filter('the_content', 'elinor_maybe_replace_front_page_content', 1);

/**
 * Register page template for WordPress picker.
 *
 * @param string[] $templates Templates.
 * @return string[]
 */
function elinor_register_page_templates(array $templates): array {
	$templates['page-templates/beauties-home.php'] = __('الینور Beauties — صفحه اصلی', 'hello-elementor-elinor');

	return $templates;
}
add_filter('theme_page_templates', 'elinor_register_page_templates');
