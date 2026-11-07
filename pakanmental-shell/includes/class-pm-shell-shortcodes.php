<?php
/**
 * Dynamic shortcodes for Elementor HTML widgets (single + archive).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Shell_Shortcodes {

	public static function init(): void {
		add_shortcode( 'pm_single_product', array( __CLASS__, 'single_product' ) );
		add_shortcode( 'pm_product_archive', array( __CLASS__, 'product_archive' ) );
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function single_product( $atts ): string {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}
		$product = null;
		if ( is_singular( 'product' ) ) {
			$product = wc_get_product( get_queried_object_id() );
		}
		if ( ! $product ) {
			global $product;
		}
		if ( ! $product instanceof WC_Product ) {
			return '<p class="pm-empty">' . esc_html__( 'محصولی برای نمایش نیست. این شورتکد روی صفحهٔ تک‌محصول کار می‌کند.', 'pakanmental-shell' ) . '</p>';
		}

		$post_object = get_post( $product->get_id() );
		if ( $post_object instanceof WP_Post ) {
			$GLOBALS['post'] = $post_object;
			setup_postdata( $post_object );
		}
		$GLOBALS['product'] = $product;
		ob_start();
		include PM_SHELL_PATH . 'woocommerce/content-single-product.php';
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function product_archive( $atts ): string {
		$atts = shortcode_atts(
			array(
				'per_page' => '12',
			),
			$atts,
			'pm_product_archive'
		);

		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			$term = null;
		}
		$name     = $term instanceof WP_Term ? $term->name : '';
		$products = array();
		if ( $term instanceof WP_Term && function_exists( 'wc_get_products' ) ) {
			$found = wc_get_products(
				array(
					'status'    => 'publish',
					'limit'     => max( 1, (int) $atts['per_page'] ),
					'orderby'   => 'date',
					'order'     => 'DESC',
					'tax_query' => array(
						array(
							'taxonomy' => $term->taxonomy,
							'field'    => 'term_id',
							'terms'    => array( (int) $term->term_id ),
						),
					),
				)
			);
			$products = is_array( $found ) ? $found : array();
		}
		ob_start();
		?>
		<section class="pm-page-head">
			<div class="pm-container">
				<h1><?php echo esc_html( $name ); ?></h1>
			</div>
		</section>
		<?php if ( $term instanceof WP_Term && term_description( $term ) ) : ?>
		<section class="pm-shop-category">
			<div class="pm-container">
				<div class="pm-shop-category__body"><?php echo term_description( $term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		</section>
		<?php endif; ?>
		<section class="pm-shop">
			<div class="pm-container">
				<?php if ( ! $products ) : ?>
					<p class="pm-empty"><?php esc_html_e( 'در این دسته‌بندی هنوز محصولی نیست.', 'pakanmental-shell' ); ?></p>
				<?php elseif ( function_exists( 'woocommerce_product_loop_start' ) ) : ?>
					<?php woocommerce_product_loop_start(); ?>
					<?php foreach ( $products as $archive_product ) : ?>
						<?php
						if ( ! $archive_product instanceof WC_Product ) {
							continue;
						}
						$archive_post = get_post( $archive_product->get_id() );
						if ( $archive_post instanceof WP_Post ) {
							$GLOBALS['post'] = $archive_post;
							setup_postdata( $archive_post );
						}
						$GLOBALS['product'] = $archive_product;
						wc_get_template_part( 'content', 'product' );
						?>
					<?php endforeach; ?>
					<?php woocommerce_product_loop_end(); ?>
					<?php wp_reset_postdata(); ?>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
