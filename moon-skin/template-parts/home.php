<?php
/**
 * خانه پیش‌فرض وقتی برگه نخست خالی است.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
get_template_part('template-parts/home', 'hero');

if (function_exists('wc_get_products')) {
	$cats = moon_skin_top_cats();
	if ($cats) {
		echo '<section class="moon-wrap moon-cats">';
		foreach ($cats as $cat) {
			echo '<a href="' . esc_url(get_term_link($cat)) . '">' . esc_html($cat->name) . '</a>';
		}
		echo '</section>';
	}
	echo '<section class="moon-wrap">';
	echo do_shortcode('[products limit="8" columns="4" orderby="date"]');
	echo '</section>';
}
