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

define('ELINOR_HELLO_VERSION', '1.0.0');
define('ELINOR_HELLO_PATH', get_stylesheet_directory());
define('ELINOR_HELLO_URI', get_stylesheet_directory_uri());

/**
 * Enqueue parent + child styles and Beauties bundle on the front page.
 */
function elinor_hello_enqueue_styles(): void {
	$parent = 'hello-elementor';
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
		[$parent . '-style'],
		ELINOR_HELLO_VERSION
	);

	if (!is_front_page()) {
		return;
	}

	wp_enqueue_style(
		'elinor-beauties-341555',
		ELINOR_HELLO_URI . '/assets/css/beauties-341555.css',
		['elinor-hello-child'],
		ELINOR_HELLO_VERSION
	);

	wp_enqueue_style(
		'elinor-beauties-fonts',
		'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap',
		[],
		null
	);
}
add_action('wp_enqueue_scripts', 'elinor_hello_enqueue_styles', 20);

/**
 * Body class for Beauties CSS scope on homepage.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function elinor_hello_body_class(array $classes): array {
	if (is_front_page()) {
		$classes[] = 'elinor-beauties-page';
	}

	return $classes;
}
add_filter('body_class', 'elinor_hello_body_class');

/**
 * Register Elementor kit colors when Elementor is active.
 */
function elinor_hello_elementor_kit_colors(): void {
	if (!did_action('elementor/loaded')) {
		return;
	}

	// Suggest global primary via theme support doc; kit is edited in Elementor UI.
}
add_action('init', 'elinor_hello_elementor_kit_colors');

/**
 * Wrap Elementor canvas content for shared Beauties component styles.
 */
function elinor_hello_open_wrapper(): void {
	if (!is_front_page()) {
		return;
	}
	echo '<div class="elinor-bh">';
}
add_action('elementor/page_templates/canvas/before_content', 'elinor_hello_open_wrapper');

/**
 * Close wrapper opened for canvas template.
 */
function elinor_hello_close_wrapper(): void {
	if (!is_front_page()) {
		return;
	}
	echo '</div>';
}
add_action('elementor/page_templates/canvas/after_content', 'elinor_hello_close_wrapper');
