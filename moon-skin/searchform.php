<?php
/**
 * فرم جستجو.
 *
 * @package Moon_Skin
 */
?>
<form class="moon-search moon-search-inline" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
	<label class="screen-reader-text" for="moon-search-field"><?php esc_html_e('جستجو', 'moon-skin'); ?></label>
	<input id="moon-search-field" type="search" name="s" placeholder="<?php esc_attr_e('جستجو', 'moon-skin'); ?>">
	<button type="submit"><?php esc_html_e('جستجو', 'moon-skin'); ?></button>
</form>
