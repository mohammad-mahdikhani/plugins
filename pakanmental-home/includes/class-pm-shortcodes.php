<?php
/**
 * Shortcodes for Elementor HTML widgets (home rows, shop loop, categories).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Shortcodes {

	public static function init(): void {
		add_shortcode( 'pm_product_row', array( __CLASS__, 'product_row' ) );
		add_shortcode( 'pm_home_categories', array( __CLASS__, 'home_categories' ) );
		add_shortcode( 'pm_shop_loop', array( __CLASS__, 'shop_loop' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function product_row( $atts ): string {
		if ( ! class_exists( 'PM_Template' ) ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'id'                 => 'pm-row',
				'title'              => '',
				'subtitle'           => '',
				'slug'               => '',
				'orderby'            => 'date',
				'badge'              => '',
				'alt'                => '',
				'placeholder_prefix' => '',
			),
			$atts,
			'pm_product_row'
		);

		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
		$more     = $shop_url;
		if ( $atts['slug'] ) {
			$term = get_term_by( 'slug', sanitize_title( $atts['slug'] ), 'product_cat' );
			if ( ! $term ) {
				$term = get_term_by( 'slug', $atts['slug'], 'product_cat' );
			}
			if ( $term && ! is_wp_error( get_term_link( $term ) ) ) {
				$more = get_term_link( $term );
			}
		}

		$prefix = $atts['placeholder_prefix'] ?: $atts['title'];

		ob_start();
		PM_Template::render_product_row(
			array(
				'id'                 => sanitize_html_class( $atts['id'] ),
				'title'              => $atts['title'],
				'subtitle'           => $atts['subtitle'],
				'more'               => $more,
				'slug'               => $atts['slug'],
				'orderby'            => $atts['orderby'],
				'placeholder_prefix' => $prefix,
				'badge'              => filter_var( $atts['badge'], FILTER_VALIDATE_BOOLEAN ),
				'alt'                => filter_var( $atts['alt'], FILTER_VALIDATE_BOOLEAN ),
			)
		);
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function home_categories( $atts ): string {
		return pm_home_parent_categories_html();
	}

	/**
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function shop_loop( $atts ): string {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return '<p class="pm-empty">' . esc_html__( 'ووکامرس فعال نیست.', 'pakanmental-home' ) . '</p>';
		}

		$atts = shortcode_atts(
			array(
				'per_page' => '12',
				'columns'  => '4',
			),
			$atts,
			'pm_shop_loop'
		);

		$page     = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		$per_page = max( 1, (int) $atts['per_page'] );

		$query_args = array(
			'status'   => 'publish',
			'limit'    => $per_page,
			'paginate' => true,
			'page'     => $page,
			'orderby'  => 'date',
			'order'    => 'DESC',
		);

		if ( is_product_category() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$query_args['tax_query'] = array(
					array(
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => (int) $term->term_id,
						'include_children' => true,
					),
				);
			}
		}

		$result = wc_get_products( $query_args );

		ob_start();
		echo '<div class="pm-product-grid">';
		if ( ! empty( $result->products ) ) {
			foreach ( $result->products as $product ) {
				self::render_shop_product_card( $product );
			}
		} else {
			echo '<p class="pm-empty">' . esc_html__( 'هنوز محصولی منتشر نشده است.', 'pakanmental-home' ) . '</p>';
		}
		echo '</div>';

		if ( ! empty( $result->max_num_pages ) && $result->max_num_pages > 1 ) {
			echo '<nav class="pm-pagination" aria-label="' . esc_attr__( 'صفحه‌بندی', 'pakanmental-home' ) . '">';
			echo paginate_links(
				array(
					'total'   => (int) $result->max_num_pages,
					'current' => $page,
					'type'    => 'list',
				)
			);
			echo '</nav>';
		}

		return ob_get_clean();
	}

	private static function render_shop_product_card( $product ): void {
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_permalink' ) ) {
			return;
		}
		?>
		<article class="pm-product">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="pm-product__link">
				<?php
				echo $product->get_image(
					'woocommerce_thumbnail',
					array(
						'class'   => 'pm-product__thumb',
						'loading' => 'lazy',
					)
				);
				?>
				<h3 class="pm-product__title"><?php echo esc_html( $product->get_name() ); ?></h3>
				<p class="pm-product__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
			</a>
		</article>
		<?php
	}
}
