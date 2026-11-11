<?php
/**
 * سربرگ.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php moon_skin_part('header', 'header-bar'); ?>
<?php moon_skin_release_banner(); ?>
<main id="content" class="moon-main">
