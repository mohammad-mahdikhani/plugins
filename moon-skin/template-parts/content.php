<?php
/**
 * محتوای ساده.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
?>
<article <?php post_class('moon-card'); ?>>
	<a href="<?php the_permalink(); ?>"><b><?php the_title(); ?></b></a>
</article>
