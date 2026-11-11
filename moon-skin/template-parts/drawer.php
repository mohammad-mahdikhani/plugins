<?php
/**
 * سبد کناری.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
if (!function_exists('WC')) {
	return;
}
?>
<div class="moon-draw" id="moon-draw" hidden>
	<div class="bg" data-close></div>
	<aside>
		<header><b><?php echo esc_html(moon_skin_mod('moon_skin_cart_title')); ?></b><button class="x" type="button" data-close aria-label="<?php esc_attr_e('بستن', 'moon-skin'); ?>">×</button></header>
		<div class="items" id="moon-draw-items"></div>
		<footer id="moon-draw-foot"></footer>
	</aside>
</div>
