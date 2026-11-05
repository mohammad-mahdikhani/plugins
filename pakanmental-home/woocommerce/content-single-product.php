<?php
/**
 * Single product content — PM layout.
 *
 * @package PakanMentalHome
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'pm-wc-single-product', $product ); ?>>
	<section class="pm-product-single">
		<div class="pm-container pm-product-single__grid">
			<div class="pm-product-gallery">
				<?php woocommerce_show_product_sale_flash(); ?>
				<?php woocommerce_show_product_images(); ?>
			</div>
			<div class="pm-product-summary">
				<?php woocommerce_breadcrumb(); ?>
				<?php woocommerce_template_single_title(); ?>
				<?php woocommerce_template_single_rating(); ?>
				<div class="pm-product-summary__price">
					<?php woocommerce_template_single_price(); ?>
				</div>
				<div class="pm-product-summary__lead">
					<?php woocommerce_template_single_excerpt(); ?>
				</div>
				<div class="pm-qty-row">
					<?php woocommerce_template_single_add_to_cart(); ?>
				</div>
				<?php woocommerce_template_single_meta(); ?>
			</div>
		</div>
	</section>

	<?php echo do_shortcode( '[pm_product_content]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php echo do_shortcode( '[pm_product_magazine limit="3"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<section class="pm-section pm-section--alt pm-related">
		<div class="pm-container">
			<h2><?php esc_html_e( 'محصولات مرتبط', 'pakanmental-home' ); ?></h2>
			<?php woocommerce_output_related_products(); ?>
		</div>
	</section>
</div>
<?php
do_action( 'woocommerce_after_single_product' );
