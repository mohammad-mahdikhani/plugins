<?php
/**
 * صفحه پیدا نشد.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>
<div class="moon-wrap">
	<section class="moon-missing">
		<p class="moon-kicker"><?php esc_html_e('۴۰۴', 'moon-skin'); ?></p>
		<h1><?php esc_html_e('این صفحه پیدا نشد', 'moon-skin'); ?></h1>
		<p><?php esc_html_e('از فروشگاه یا خانه ادامه دهید.', 'moon-skin'); ?></p>
		<p class="moon-missing-links">
			<a class="button" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('خانه', 'moon-skin'); ?></a>
			<?php if (function_exists('wc_get_page_permalink')) : ?>
				<a class="button button-ghost" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('فروشگاه', 'moon-skin'); ?></a>
			<?php endif; ?>
		</p>
	</section>
</div>
<?php
get_footer();
