<?php
/**
 * دیدگاه‌ها.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

if (post_password_required()) {
	return;
}
?>
<section class="moon-comments">
	<?php if (have_comments()) : ?>
		<h2><?php esc_html_e('دیدگاه‌ها', 'moon-skin'); ?></h2>
		<ol><?php wp_list_comments(array('style' => 'ol', 'short_ping' => true)); ?></ol>
	<?php endif; ?>
	<?php comment_form(); ?>
</section>
