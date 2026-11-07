<?php
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
<?php pmwc_render_section( PMWC_HEADER_ID ); ?>
<main id="pm-main" class="pm-main">
	<?php
	while ( have_posts() ) {
		the_post();
		wc_get_template_part( 'content', 'single-product' );
	}
	?>
</main>
<?php pmwc_render_section( PMWC_FOOTER_ID ); ?>
<?php wp_footer(); ?>
</body>
</html>
