<?php
/**
 * Template Name: الینور Beauties — صفحه اصلی
 * Template Post Type: page
 *
 * @package Hello_Elementor_Elinor
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class('elinor-beauties-page'); ?>>
<?php wp_body_open(); ?>
<?php echo elinor_render_homepage(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php wp_footer(); ?>
</body>
</html>
