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
		<header><b><?php esc_html_e('سبد خرید', 'moon-skin'); ?></b><button class="x" type="button" data-close aria-label="<?php esc_attr_e('بستن', 'moon-skin'); ?>">×</button></header>
		<div class="items" id="moon-draw-items"></div>
		<footer id="moon-draw-foot"></footer>
	</aside>
</div>
