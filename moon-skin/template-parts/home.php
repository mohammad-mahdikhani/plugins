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
	if (moon_skin_on('moon_skin_show_cats')) {
		$cats = moon_skin_top_cats();
		if ($cats) {
			echo '<section class="moon-wrap moon-cats">';
			foreach ($cats as $cat) {
				$link = get_term_link($cat);
				if (is_wp_error($link)) {
					continue;
				}
				echo '<a href="' . esc_url($link) . '">' . esc_html($cat->name) . '</a>';
			}
			echo '</section>';
		}
	}
	if (moon_skin_on('moon_skin_show_products')) {
		$count = max(1, (int) moon_skin_mod('moon_skin_home_count'));
		echo '<section class="moon-wrap">';
		echo do_shortcode('[products limit="' . $count . '" columns="4" orderby="date"]');
		echo '</section>';
	}
}
