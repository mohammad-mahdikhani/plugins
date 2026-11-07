<?php
/**
 * Full-document WooCommerce shell — header/footer Elementor sections + PM layout.
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

<?php hello_pakan_render_section( HELLO_PAKAN_HEADER_ID ); ?>

<main id="pm-main" class="pm-main">
	<?php
	if ( is_singular( 'product' ) ) {
		while ( have_posts() ) {
			the_post();
			wc_get_template_part( 'content', 'single-product' );
		}
	} else {
		?>
		<section class="pm-page-head">
			<div class="pm-container">
				<?php if ( is_product_category() ) : ?>
					<h1><?php single_term_title(); ?></h1>
					<?php if ( term_description() ) : ?>
						<div class="pm-shop-category__body"><?php echo term_description(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php endif; ?>
				<?php else : ?>
					<h1><?php esc_html_e( 'فروشگاه', 'hello-pakan' ); ?></h1>
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

<?php hello_pakan_render_section( HELLO_PAKAN_FOOTER_ID ); ?>
<?php wp_footer(); ?>
</body>
</html>
