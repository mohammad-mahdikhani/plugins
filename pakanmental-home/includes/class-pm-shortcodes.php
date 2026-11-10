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
		$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );

		$items = array(
			array( 'بردگیم', 'بازی-فکری', 'board' ),
			array( 'پازل', 'پازل', 'puzzle' ),
			array( 'اکشن فیگور', 'اکشن-فیگور', 'figure' ),
			array( 'کارتی', 'بازی-فکری-کارتی', 'card' ),
			array( 'بازی منتالی', 'بازی-فکری', 'mental' ),
			array( 'لوازم جانبی', 'لوازم-جانبی', 'accessory' ),
		);

		$html = '<div class="pm-cats">';
		foreach ( $items as $item ) {
			list( $label, $slug, $icon ) = $item;
			$link = $shop_url;
			if ( function_exists( 'get_term_by' ) ) {
				$term = get_term_by( 'slug', $slug, 'product_cat' );
				if ( ! $term ) {
					$term = get_term_by( 'slug', sanitize_title( $label ), 'product_cat' );
				}
				if ( $term && ! is_wp_error( get_term_link( $term ) ) ) {
					$link = get_term_link( $term );
				}
			}
			$html .= sprintf(
				'<a class="pm-cat pm-cat--%s" href="%s"><span class="pm-cat__label">%s</span></a>',
				esc_attr( $icon ),
				esc_url( $link ),
				esc_html( $label )
			);
		}
		$html .= '</div>';
		return $html;
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
			echo '<script>(function(){document.querySelectorAll(".pm-product__buy").forEach(function(form){if(form.dataset.pmQtyBound)return;form.dataset.pmQtyBound="1";form.addEventListener("click",function(e){var btn=e.target.closest("[data-pm-qty-step]");if(!btn||!form.contains(btn))return;e.preventDefault();var input=form.querySelector("input.qty");if(!input)return;var next=(parseInt(input.value,10)||1)+(parseInt(btn.getAttribute("data-pm-qty-step"),10)||0);input.value=String(next<1?1:next);});});})();</script>';
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
			<?php if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) : ?>
				<form class="pm-product__buy cart" method="post" action="<?php echo esc_url( $product->get_permalink() ); ?>">
					<div class="pm-product__stepper">
						<button type="button" data-pm-qty-step="-1" aria-label="<?php esc_attr_e( 'کم کردن', 'pakanmental-home' ); ?>">−</button>
						<input class="qty" type="number" name="quantity" value="1" min="1" inputmode="numeric" aria-label="<?php esc_attr_e( 'تعداد', 'pakanmental-home' ); ?>">
						<button type="button" data-pm-qty-step="1" aria-label="<?php esc_attr_e( 'زیاد کردن', 'pakanmental-home' ); ?>">+</button>
					</div>
					<button type="submit" class="pm-product__add single_add_to_cart_button" name="add-to-cart" value="<?php echo esc_attr( (string) $product->get_id() ); ?>"><?php esc_html_e( 'افزودن به سبد خرید', 'pakanmental-home' ); ?></button>
				</form>
			<?php endif; ?>
		</article>
		<?php
	}
}
