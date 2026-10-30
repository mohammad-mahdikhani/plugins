<?php
defined( 'ABSPATH' ) || exit;

$page = get_page_by_path( '404-pm' );
if ( ! $page instanceof WP_Post ) {
	return;
}
$page_id = (int) $page->ID;

global $post, $wp_query;

$post = $page;
$wp_query->posts             = array( $page );
$wp_query->post              = $page;
$wp_query->post_count        = 1;
$wp_query->found_posts       = 1;
$wp_query->max_num_pages     = 1;
$wp_query->is_404            = true;
$wp_query->is_page           = true;
$wp_query->is_singular       = true;
$wp_query->queried_object    = $page;
$wp_query->queried_object_id = $page_id;

setup_postdata( $page );
status_header( 404 );
nocache_headers();

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, follow">
	<?php wp_head(); ?>
</head>
<body <?php body_class( array( 'error404', 'pm-error-404', 'elementor-default' ) ); ?>>
<?php
if ( class_exists( '\Elementor\Plugin' ) ) {
	echo \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $page_id, true );
} else {
	echo wp_kses_post( apply_filters( 'the_content', $page->post_content ) );
}
wp_footer();
?>
</body>
</html>
