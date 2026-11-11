<?php
/**
 * فرم جستجو.
 *
 * @package Moon_Skin
 */
?>
<form class="moon-search moon-search-inline" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="moon-search-field"><?php echo esc_html(moon_skin_mod('moon_skin_search_placeholder')); ?></label>
	<input id="moon-search-field" type="search" name="s" placeholder="<?php echo esc_attr(moon_skin_mod('moon_skin_search_placeholder')); ?>">
	<button type="submit"><?php echo esc_html(moon_skin_mod('moon_skin_search_placeholder')); ?></button>
</form>
