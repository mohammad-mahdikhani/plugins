<?php
/**
 * صفحه نخست. اگر برگه محتوا داشته باشد همان را نشان می‌دهد؛ وگرنه خانه قالب.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();

if (have_posts()) {
	while (have_posts()) {
		the_post();
		$raw = (string) get_post()->post_content;
		$plain = trim(wp_strip_all_tags($raw));
		if ($plain !== '' || has_shortcode($raw, 'elementor-template')) {
			echo '<div class="moon-wrap moon-front-content">';
			the_content();
			echo '</div>';
		} else {
			get_template_part('template-parts/home');
		}
	}
} else {
	get_template_part('template-parts/home');
}

get_footer();
