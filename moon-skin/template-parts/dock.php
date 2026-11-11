<?php
/**
 * نوار پایین موبایل.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
?>
<nav class="moon-dock" aria-label="<?php esc_attr_e('میانبر', 'moon-skin'); ?>">
	<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'moon-skin'); ?></a>
	<a href="<?php echo esc_url(moon_skin_shop_url()); ?>"><?php esc_html_e('فروشگاه', 'moon-skin'); ?></a>
	<button type="button" data-moon-cart><?php esc_html_e('سبد', 'moon-skin'); ?><i data-moon-count><?php echo esc_html((string) moon_skin_cart_count()); ?></i></button>
	<?php if (function_exists('wc_get_page_permalink')) : ?>
		<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('حساب', 'moon-skin'); ?></a>
	<?php else : ?>
		<a href="<?php echo esc_url(moon_skin_blog_url()); ?>"><?php esc_html_e('مجله', 'moon-skin'); ?></a>
	<?php endif; ?>
</nav>
