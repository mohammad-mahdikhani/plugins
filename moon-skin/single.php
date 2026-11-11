<?php
/**
 * نوشته تکی: بردکرامب و تصویر شاخص مستطیلی.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();

if (function_exists('elementor_theme_do_location') && elementor_theme_do_location('single')) {
	get_footer();
	return;
}

while (have_posts()) :
	the_post();
	?>
	<article <?php post_class('moon-article'); ?>>
		<div class="moon-wrap">
			<?php moon_skin_breadcrumb(); ?>
			<?php if (has_post_thumbnail() && moon_skin_on('moon_skin_show_post_image')) : ?>
				<figure class="moon-post-hero">
					<?php the_post_thumbnail('large', array('class' => 'moon-post-hero-img')); ?>
				</figure>
			<?php endif; ?>
			<h1 class="entry-title"><?php the_title(); ?></h1>
			<?php if (moon_skin_on('moon_skin_show_date')) : ?>
				<p class="moon-meta"><?php echo esc_html(get_the_date()); ?></p>
			<?php endif; ?>
		</div>
		<div class="moon-wrap moon-prose entry-content">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
