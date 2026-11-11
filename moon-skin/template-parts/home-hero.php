<?php
/**
 * معرفی بالای خانه.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
$title = trim((string) get_theme_mod('moon_skin_hero_title', ''));
$text  = trim((string) get_theme_mod('moon_skin_hero_text', ''));
if ($title === '') {
	$title = get_bloginfo('name');
}
if ($text === '') {
	$text = get_bloginfo('description');
}
?>
<section class="moon-hero">
	<div class="moon-wrap">
		<p class="moon-kicker"><?php bloginfo('name'); ?></p>
		<h1><?php echo esc_html($title); ?></h1>
		<?php if ($text !== '') : ?>
			<p><?php echo esc_html($text); ?></p>
		<?php endif; ?>
		<a class="button" href="<?php echo esc_url(moon_skin_shop_url()); ?>"><?php esc_html_e('مشاهده فروشگاه', 'moon-skin'); ?></a>
	</div>
</section>
