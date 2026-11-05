<?php
/**
 * Full-page PM shell for single product and product category archives.
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

<?php PM_Chrome::render_header(); ?>

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
					<h1><?php esc_html_e( 'فروشگاه', 'pakanmental-home' ); ?></h1>
				<?php endif; ?>
			</div>
		</section>
		<section class="pm-shop">
			<div class="pm-container">
				<?php echo do_shortcode( '[pm_shop_loop per_page="12"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</section>
		<?php
	}
	?>
</main>

<?php PM_Chrome::render_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
