<?php
/**
 * Product content + magazine shortcodes (WooCommerce + WordPress posts).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Product_Content {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_shortcode( 'pm_product_excerpt', array( __CLASS__, 'excerpt' ) );
		add_shortcode( 'pm_product_content', array( __CLASS__, 'content' ) );
		add_shortcode( 'pm_product_magazine', array( __CLASS__, 'magazine' ) );
	}

	public static function register_meta(): void {
		register_post_meta(
			'product',
			'pm_related_post_ids',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_post_ids' ),
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_products' );
				},
			)
		);
	}

	/**
	 * @param mixed $value Raw meta.
	 */
	public static function sanitize_post_ids( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$ids = array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $value ) ) );
		return implode( ',', $ids );
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function excerpt( $atts ): string {
		$product = pm_get_product_for_shortcode( 0 );
		if ( ! $product ) {
			return '';
		}
		$text = $product->get_short_description();
		if ( ! $text ) {
			return '';
		}
		return '<div class="pm-product-summary__lead">' . wp_kses_post( wpautop( $text ) ) . '</div>';
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function content( $atts ): string {
		$atts = shortcode_atts(
			array(
				'title'    => __( 'توضیحات محصول', 'pakanmental-home' ),
				'subtitle' => __( 'جزئیات، مشخصات و نکات نگهداری', 'pakanmental-home' ),
				'id'       => '',
			),
			$atts,
			'pm_product_content'
		);

		$product_id = (int) $atts['id'];
		$product    = pm_get_product_for_shortcode( $product_id );
		if ( ! $product ) {
			return '';
		}

		$raw = $product->get_description();
		if ( ! $raw ) {
			return '';
		}

		$body = apply_filters( 'the_content', $raw );

		ob_start();
		?>
		<section class="pm-product-content-section pm-section" aria-labelledby="pm-product-content-title">
			<div class="pm-container">
				<header class="pm-section__head">
					<div>
						<h2 id="pm-product-content-title"><?php echo esc_html( $atts['title'] ); ?></h2>
						<?php if ( $atts['subtitle'] ) : ?>
							<p><?php echo esc_html( $atts['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
				</header>
				<article class="pm-product-content entry-content">
					<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filters. ?>
				</article>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function magazine( $atts ): string {
		$atts = shortcode_atts(
			array(
				'title'    => __( 'از مجله پاکن منتال', 'pakanmental-home' ),
				'subtitle' => __( 'راهنما و مطالب مرتبط', 'pakanmental-home' ),
				'limit'    => '3',
				'id'       => '',
			),
			$atts,
			'pm_product_magazine'
		);

		$product_id = (int) $atts['id'];
		$product    = pm_get_product_for_shortcode( $product_id );
		$limit      = max( 1, min( 6, (int) $atts['limit'] ) );
		$post_ids   = array();

		if ( $product ) {
			$meta = (string) get_post_meta( $product->get_id(), 'pm_related_post_ids', true );
			if ( $meta ) {
				$post_ids = array_filter( array_map( 'absint', explode( ',', $meta ) ) );
			}
		}

		$query_args = array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( $post_ids ) {
			$query_args['post__in'] = $post_ids;
			$query_args['orderby']  = 'post__in';
		}

		$posts = get_posts( $query_args );
		if ( ! $posts ) {
			return '';
		}

		$blog_url = get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' );

		ob_start();
		?>
		<section class="pm-product-magazine pm-section pm-section--alt" aria-labelledby="pm-product-magazine-title">
			<div class="pm-container">
				<header class="pm-section__head">
					<div>
						<h2 id="pm-product-magazine-title"><?php echo esc_html( $atts['title'] ); ?></h2>
						<?php if ( $atts['subtitle'] ) : ?>
							<p><?php echo esc_html( $atts['subtitle'] ); ?></p>
						<?php endif; ?>
					</div>
					<a class="pm-more" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'همه مطالب', 'pakanmental-home' ); ?></a>
				</header>
				<div class="pm-blog pm-blog--product">
					<?php
					foreach ( $posts as $post ) {
						self::render_magazine_card( $post );
					}
					?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	private static function render_magazine_card( WP_Post $post ): void {
		$link = get_permalink( $post );
		?>
		<article class="pm-post">
			<a href="<?php echo esc_url( $link ); ?>">
				<?php
				if ( has_post_thumbnail( $post ) ) {
					echo get_the_post_thumbnail(
						$post,
						'medium_large',
						array(
							'class'   => 'pm-post__img',
							'loading' => 'lazy',
						)
					);
				} else {
					echo '<div class="pm-post__ph" aria-hidden="true"></div>';
				}
				?>
				<h3><?php echo esc_html( get_the_title( $post ) ); ?></h3>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 16 ) ); ?></p>
			</a>
		</article>
		<?php
	}
}
