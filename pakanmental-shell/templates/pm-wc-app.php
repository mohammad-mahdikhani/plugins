<?php
/**
 * Document shell: Elementor header + Elementor main template + Elementor footer.
 */

defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( array( 'pm-site', 'pm-wc-shell' ) ); ?>>
<?php wp_body_open(); ?>

<?php PM_Shell_Chrome::render_header(); ?>

<main id="pm-main" class="pm-main">
	<?php PM_Shell_Chrome::render_main(); ?>
</main>

<?php PM_Shell_Chrome::render_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
