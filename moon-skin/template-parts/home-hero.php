<?php
/**
 * معرفی بالای خانه.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
if (!moon_skin_on('moon_skin_show_hero')) {
	return;
}
$title = trim((string) moon_skin_mod('moon_skin_hero_title'));
$text  = trim((string) moon_skin_mod('moon_skin_hero_text'));
$kicker = trim((string) moon_skin_mod('moon_skin_kicker'));
if ($title === '') {
	$title = get_bloginfo('name');
}
if ($text === '') {
	$text = get_bloginfo('description');
}
if ($kicker === '') {
	$kicker = get_bloginfo('name');
}
$url = trim((string) moon_skin_mod('moon_skin_hero_url'));
if ($url === '') {
	$url = moon_skin_shop_url();
}
?>
<section class="moon-hero">
	<div class="moon-wrap">
		<p class="moon-kicker"><?php echo esc_html($kicker); ?></p>
		<h1><?php echo esc_html($title); ?></h1>
		<?php if ($text !== '') : ?>
			<p><?php echo esc_html($text); ?></p>
		<?php endif; ?>
		<?php if (moon_skin_on('moon_skin_show_hero_button')) : ?>
			<a class="button" href="<?php echo esc_url($url); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_hero_button')); ?></a>
		<?php endif; ?>
	</div>
</section>
