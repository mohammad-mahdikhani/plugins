<?php
/**
 * نوار شیشه‌ای سربرگ.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

$cats = moon_skin_top_cats();
?>
<header class="moon-bar">
	<div class="moon-side start">
		<div class="moon-mega">
			<a class="moon-prod" href="<?php echo esc_url(moon_skin_shop_url()); ?>">
				<svg class="moon-mini" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				<span><?php esc_html_e('محصولات', 'moon-skin'); ?></span>
			</a>
			<nav class="moon-panel" aria-label="<?php esc_attr_e('دسته‌ها', 'moon-skin'); ?>">
				<div class="moon-mob">
					<button class="night" type="button" aria-pressed="false"><span><?php esc_html_e('حالت شب', 'moon-skin'); ?></span></button>
					<?php get_search_form(); ?>
					<a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'moon-skin'); ?></a>
					<a href="<?php echo esc_url(moon_skin_shop_url()); ?>"><?php esc_html_e('فروشگاه', 'moon-skin'); ?></a>
					<a href="<?php echo esc_url(moon_skin_blog_url()); ?>"><?php esc_html_e('مجله', 'moon-skin'); ?></a>
					<?php if (function_exists('wc_get_page_permalink')) : ?>
						<a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"><?php esc_html_e('حساب کاربری', 'moon-skin'); ?></a>
					<?php endif; ?>
				</div>
				<?php foreach ($cats as $cat) : ?>
					<?php
					$children = get_terms(array(
						'taxonomy'   => 'product_cat',
						'parent'     => (int) $cat->term_id,
						'hide_empty' => true,
						'number'     => 8,
					));
					?>
					<div class="col">
						<strong><a href="<?php echo esc_url(get_term_link($cat)); ?>"><?php echo esc_html($cat->name); ?></a></strong>
						<?php if (is_array($children)) : ?>
							<?php foreach ($children as $child) : ?>
								<a href="<?php echo esc_url(get_term_link($child)); ?>"><?php echo esc_html($child->name); ?></a>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<?php if (!$cats && has_nav_menu('primary')) : ?>
					<div class="col"><?php wp_nav_menu(array('theme_location' => 'primary', 'container' => false, 'depth' => 1)); ?></div>
				<?php endif; ?>
			</nav>
		</div>
	</div>
	<div class="moon-brand"><?php moon_skin_logo(); ?></div>
	<div class="moon-side end">
		<form class="moon-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
			<label class="screen-reader-text" for="moon-q"><?php esc_html_e('جستجو', 'moon-skin'); ?></label>
			<input id="moon-q" type="search" name="s" placeholder="<?php esc_attr_e('جستجو', 'moon-skin'); ?>" value="<?php echo esc_attr(get_search_query()); ?>">
			<?php if (function_exists('WC')) : ?>
				<input type="hidden" name="post_type" value="product">
			<?php endif; ?>
			<button type="submit" aria-label="<?php esc_attr_e('جستجو', 'moon-skin'); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="M16 16l4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
			</button>
		</form>
		<?php if (function_exists('wc_get_page_permalink')) : ?>
			<a class="moon-icon" href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" aria-label="<?php esc_attr_e('حساب کاربری', 'moon-skin'); ?>">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M5 19.2c1.4-2.6 3.6-3.8 7-3.8s5.6 1.2 7 3.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
			</a>
		<?php endif; ?>
		<button class="moon-icon moon-cart" type="button" data-moon-cart aria-label="<?php esc_attr_e('سبد خرید', 'moon-skin'); ?>">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 7h15l-1.6 8.2H8.2L6 7z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M6 7L5 4H2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><circle cx="9" cy="19" r="1.3" fill="currentColor"/><circle cx="17" cy="19" r="1.3" fill="currentColor"/></svg>
			<i class="moon-count" data-moon-count><?php echo esc_html((string) moon_skin_cart_count()); ?></i>
		</button>
		<button class="night" type="button" aria-pressed="false" aria-label="<?php esc_attr_e('حالت شب', 'moon-skin'); ?>"><span><?php esc_html_e('حالت شب', 'moon-skin'); ?></span></button>
	</div>
</header>
