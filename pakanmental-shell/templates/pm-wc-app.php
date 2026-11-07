<?php
/**
 * Document shell for every product and product category.
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
	<?php
	if ( is_singular( 'product' ) ) {
		while ( have_posts() ) {
			the_post();
			include PM_SHELL_PATH . 'woocommerce/content-single-product.php';
		}
	} else {
		$term = get_queried_object();
		?>
		<section class="pm-page-head">
			<div class="pm-container">
				<?php if ( is_product_category() && $term instanceof WP_Term ) : ?>
					<h1><?php echo esc_html( $term->name ); ?></h1>
				<?php else : ?>
					<h1><?php single_term_title(); ?></h1>
				<?php endif; ?>
			</div>
		</section>
		<section class="pm-shop">
			<div class="pm-container">
				<?php
				if ( shortcode_exists( 'pm_shop_loop' ) ) {
					echo do_shortcode( '[pm_shop_loop per_page="12"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					woocommerce_product_loop_start();
					if ( wc_get_loop_prop( 'total' ) ) {
						while ( have_posts() ) {
							the_post();
							wc_get_template_part( 'content', 'product' );
						}
					}
					woocommerce_product_loop_end();
				}
				?>
			</div>
		</section>
		<?php
	}
	?>
</main>

<?php PM_Shell_Chrome::render_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
