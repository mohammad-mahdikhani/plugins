<?php
/**
 * نوار پایین موبایل.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
if (!moon_skin_on('moon_skin_show_dock')) {
	return;
}
?>
<nav class="moon-dock" aria-label="<?php esc_attr_e('میانبر', 'moon-skin'); ?>">
	<a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_home')); ?></a>
	<a href="<?php echo esc_url(moon_skin_shop_url()); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_shop')); ?></a>
	<?php if (moon_skin_on('moon_skin_show_cart') && function_exists('WC')) : ?>
		<button type="button" data-moon-cart><?php echo esc_html(moon_skin_mod('moon_skin_label_cart')); ?><i data-moon-count><?php echo esc_html((string) moon_skin_cart_count()); ?></i></button>
	<?php endif; ?>
	<?php if (moon_skin_on('moon_skin_show_account') && function_exists('wc_get_page_permalink')) : ?>
		<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_account')); ?></a>
	<?php else : ?>
		<a href="<?php echo esc_url(moon_skin_blog_url()); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_blog')); ?></a>
	<?php endif; ?>
</nav>
