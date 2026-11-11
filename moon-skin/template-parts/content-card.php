<?php
/**
 * کارت نوشته.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
?>
<article <?php post_class('moon-card'); ?>>
	<a href="<?php the_permalink(); ?>">
		<?php if (has_post_thumbnail()) : ?>
			<span class="moon-card-shot"><?php the_post_thumbnail('medium_large'); ?></span>
		<?php endif; ?>
		<b><?php the_title(); ?></b>
		<em><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></em>
		<span class="moon-meta"><?php echo esc_html(get_the_date()); ?></span>
	</a>
</article>
