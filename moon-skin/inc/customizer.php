<?php
/**
 * سفارشی‌سازی.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('customize_register', 'moon_skin_customize');

/**
 * تنظیم تلفن و رنگ.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function moon_skin_customize($wp_customize) {
	$wp_customize->add_section('moon_skin', array(
		'title'    => __('مون‌اسکین', 'moon-skin'),
		'priority' => 30,
	));

	$wp_customize->add_setting('moon_skin_phone', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	));
	$wp_customize->add_control('moon_skin_phone', array(
		'label'   => __('تلفن پشتیبانی', 'moon-skin'),
		'section' => 'moon_skin',
		'type'    => 'text',
	));

	$wp_customize->add_setting('moon_skin_accent', array(
		'default'           => '#4c1d95',
		'sanitize_callback' => 'sanitize_hex_color',
	));
	$wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'moon_skin_accent', array(
		'label'   => __('رنگ اصلی', 'moon-skin'),
		'section' => 'moon_skin',
	)));

	$wp_customize->add_setting('moon_skin_hero_title', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
	));
	$wp_customize->add_control('moon_skin_hero_title', array(
		'label'   => __('عنوان صفحه خانه', 'moon-skin'),
		'section' => 'moon_skin',
		'type'    => 'text',
	));

	$wp_customize->add_setting('moon_skin_hero_text', array(
		'default'           => '',
		'sanitize_callback' => 'sanitize_textarea_field',
	));
	$wp_customize->add_control('moon_skin_hero_text', array(
		'label'   => __('متن صفحه خانه', 'moon-skin'),
		'section' => 'moon_skin',
		'type'    => 'textarea',
	));
}
