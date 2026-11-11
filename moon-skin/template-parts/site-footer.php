<?php
/**
 * پاصفحه.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
$phone = trim((string) moon_skin_mod('moon_skin_phone'));
$email = trim((string) moon_skin_mod('moon_skin_email'));
$about = trim((string) moon_skin_mod('moon_skin_footer_about'));
$address = trim((string) moon_skin_mod('moon_skin_address'));
$copy = trim((string) moon_skin_mod('moon_skin_copyright'));
if ($about === '') {
	$about = get_bloginfo('description');
}
if ($copy === '') {
	$copy = trim(get_bloginfo('name') . ' ' . wp_date('Y'));
}
$socials = array(
	'moon_skin_instagram' => __('اینستاگرام', 'moon-skin'),
	'moon_skin_telegram'  => __('تلگرام', 'moon-skin'),
	'moon_skin_whatsapp'  => __('واتساپ', 'moon-skin'),
);
?>
<footer class="moon-foot">
	<div>
		<strong><?php bloginfo('name'); ?></strong>
		<?php if ($about !== '') : ?>
			<p><?php echo esc_html($about); ?></p>
		<?php endif; ?>
	</div>
	<nav aria-label="<?php esc_attr_e('پاصفحه', 'moon-skin'); ?>">
		<?php if (has_nav_menu('footer')) : ?>
			<?php wp_nav_menu(array('theme_location' => 'footer', 'container' => false, 'depth' => 1)); ?>
		<?php else : ?>
			<a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_home')); ?></a>
			<a href="<?php echo esc_url(moon_skin_shop_url()); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_shop')); ?></a>
			<a href="<?php echo esc_url(moon_skin_blog_url()); ?>"><?php echo esc_html(moon_skin_mod('moon_skin_label_blog')); ?></a>
		<?php endif; ?>
	</nav>
	<div>
		<?php if ($phone !== '') : ?>
			<a href="<?php echo esc_url('tel:' . preg_replace('/\s+/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
		<?php endif; ?>
		<?php if ($email !== '') : ?>
			<a href="<?php echo esc_url('mailto:' . $email); ?>"><?php echo esc_html($email); ?></a>
		<?php endif; ?>
		<?php if ($address !== '') : ?>
			<p><?php echo nl2br(esc_html($address)); ?></p>
		<?php endif; ?>
		<?php foreach ($socials as $key => $label) : ?>
			<?php $url = trim((string) moon_skin_mod($key)); ?>
			<?php if ($url !== '') : ?>
				<a href="<?php echo esc_url($url); ?>" rel="noopener"><?php echo esc_html($label); ?></a>
			<?php endif; ?>
		<?php endforeach; ?>
		<?php if (is_active_sidebar('footer')) : ?>
			<?php dynamic_sidebar('footer'); ?>
		<?php endif; ?>
		<p class="moon-copy"><?php echo esc_html($copy); ?></p>
	</div>
</footer>
