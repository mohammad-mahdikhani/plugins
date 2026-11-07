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

		ob_start();
		$GLOBALS['product'] = $product;
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
		ob_start();
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
		<section class="pm-shop-category">
			<div class="pm-container">
				<div class="pm-shop-category__body"><?php echo term_description(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		</section>
		<?php endif; ?>
		<section class="pm-shop">
			<div class="pm-container">
				<?php
				if ( shortcode_exists( 'pm_shop_loop' ) ) {
					echo do_shortcode( '[pm_shop_loop per_page="' . esc_attr( $atts['per_page'] ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} elseif ( function_exists( 'woocommerce_product_loop_start' ) ) {
					woocommerce_product_loop_start();
					if ( have_posts() ) {
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
		return (string) ob_get_clean();
	}
}
