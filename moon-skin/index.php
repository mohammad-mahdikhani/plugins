<?php
/**
 * حلقه نوشته‌ها.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>
<div class="moon-wrap">
	<?php if (is_home() && !is_paged()) : ?>
		<?php get_template_part('template-parts/home', 'hero'); ?>
	<?php endif; ?>
	<header class="moon-page-head">
		<?php if (is_home() && !is_front_page()) : ?>
			<h1><?php single_post_title(); ?></h1>
		<?php elseif (is_archive()) : ?>
			<h1><?php the_archive_title(); ?></h1>
			<?php the_archive_description('<p class="moon-lead">', '</p>'); ?>
		<?php elseif (is_search()) : ?>
			<h1><?php printf(esc_html__('نتیجه جستجو: %s', 'moon-skin'), esc_html(get_search_query())); ?></h1>
		<?php endif; ?>
	</header>
	<?php if (have_posts()) : ?>
		<div class="moon-posts">
			<?php
			while (have_posts()) :
				the_post();
				get_template_part('template-parts/content', get_post_type() === 'post' ? 'card' : null);
			endwhile;
			?>
		</div>
		<nav class="moon-pager"><?php the_posts_pagination(array('mid_size' => 1)); ?></nav>
	<?php else : ?>
		<p class="moon-empty"><?php esc_html_e('چیزی برای نمایش نیست.', 'moon-skin'); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
