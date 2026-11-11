<?php
/**
 * پاصفحه.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
$phone = trim((string) get_theme_mod('moon_skin_phone', ''));
?>
<footer class="moon-foot">
	<div>
		<strong><?php bloginfo('name'); ?></strong>
		<p><?php bloginfo('description'); ?></p>
	</div>
	<nav aria-label="<?php esc_attr_e('پاصفحه', 'moon-skin'); ?>">
		<?php if (has_nav_menu('footer')) : ?>
			<?php wp_nav_menu(array('theme_location' => 'footer', 'container' => false, 'depth' => 1)); ?>
		<?php else : ?>
			<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'moon-skin'); ?></a>
			<a href="<?php echo esc_url(moon_skin_shop_url()); ?>"><?php esc_html_e('فروشگاه', 'moon-skin'); ?></a>
			<a href="<?php echo esc_url(moon_skin_blog_url()); ?>"><?php esc_html_e('مجله', 'moon-skin'); ?></a>
		<?php endif; ?>
	</nav>
	<div>
		<?php if ($phone !== '') : ?>
			<a href="<?php echo esc_url('tel:' . preg_replace('/\s+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
		<?php endif; ?>
		<?php if (is_active_sidebar('footer')) : ?>
			<?php dynamic_sidebar('footer'); ?>
		<?php endif; ?>
	</div>
</footer>
