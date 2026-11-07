<?php
/**
 * Fixed document for every WooCommerce product and category archive.
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
		<?php if ( is_product_category() && $term instanceof WP_Term && term_description() ) : ?>
		<section class="pm-shop-category" aria-label="<?php esc_attr_e( 'توضیحات دسته‌بندی', 'pakanmental-home' ); ?>">
			<div class="pm-container">
				<div class="pm-shop-category__content">
					<h2 class="pm-shop-category__title"><?php echo esc_html( $term->name ); ?></h2>
					<div class="pm-shop-category__body"><?php echo term_description(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</div>
			</div>
		</section>
		<?php endif; ?>
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
