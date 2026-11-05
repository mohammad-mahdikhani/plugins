<?php
/**
 * Register page template and render partials.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Template {

	public static function init(): void {
		add_filter( 'theme_page_templates', array( __CLASS__, 'register_template' ) );
		add_filter( 'template_include', array( __CLASS__, 'load_template' ) );
	}

	public static function register_template( array $templates ): array {
		$templates['templates/pm-home.php'] = __( 'پاکن منتال — صفحه اصلی', 'pakanmental-home' );
		return $templates;
	}

	public static function load_template( string $template ): string {
		if ( ! is_page() ) {
			return $template;
		}

		$slug = get_page_template_slug( get_queried_object_id() );
		if ( 'templates/pm-home.php' !== $slug ) {
			return $template;
		}

		$file = PM_HOME_PATH . 'templates/pm-home.php';
		return file_exists( $file ) ? $file : $template;
	}

	/**
	 * Render one product row (carousel track).
	 *
	 * @param array{id:string,title:string,subtitle:string,more:string,slug?:string,orderby?:string,placeholder_prefix:string,badge?:bool,alt?:bool} $config
	 */
	public static function render_product_row( array $config ): void {
		$limit    = 8;
		$products = pm_get_row_products(
			array(
				'slug'    => $config['slug'] ?? '',
				'limit'   => $limit,
				'orderby' => $config['orderby'] ?? 'date',
			)
		);

		$track_id = 'pm-track-' . $config['id'];
		$alt      = ! empty( $config['alt'] );
		?>
		<section class="pm-section<?php echo $alt ? ' pm-section--alt' : ''; ?>" aria-labelledby="<?php echo esc_attr( $config['id'] ); ?>-title">
			<div class="pm-container">
				<header class="pm-section__head">
					<div>
						<h2 id="<?php echo esc_attr( $config['id'] ); ?>-title"><?php echo esc_html( $config['title'] ); ?></h2>
						<p><?php echo esc_html( $config['subtitle'] ); ?></p>
					</div>
					<a class="pm-more" href="<?php echo esc_url( $config['more'] ); ?>"><?php esc_html_e( 'مشاهده همه', 'pakanmental-home' ); ?></a>
				</header>
				<div class="pm-carousel">
					<button type="button" class="pm-carousel__btn pm-carousel__btn--prev" data-pm-scroll="<?php echo esc_attr( $track_id ); ?>" data-pm-dir="prev" aria-label="<?php esc_attr_e( 'قبلی', 'pakanmental-home' ); ?>">‹</button>
					<div class="pm-track" id="<?php echo esc_attr( $track_id ); ?>" tabindex="0">
						<?php
						if ( $products ) {
							foreach ( $products as $product ) {
								self::render_product_card( $product, ! empty( $config['badge'] ) );
							}
						} else {
							foreach ( pm_placeholder_products( 6, $config['placeholder_prefix'], ! empty( $config['badge'] ) ) as $item ) {
								self::render_placeholder_card( $item['title'], $item['badge'] );
							}
						}
						?>
					</div>
					<button type="button" class="pm-carousel__btn pm-carousel__btn--next" data-pm-scroll="<?php echo esc_attr( $track_id ); ?>" data-pm-dir="next" aria-label="<?php esc_attr_e( 'بعدی', 'pakanmental-home' ); ?>">›</button>
				</div>
			</div>
		</section>
		<?php
	}

	private static function render_product_card( $product, bool $badge ): void {
		?>
		<article class="pm-card">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="pm-card__link">
				<?php echo $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'pm-card__img', 'loading' => 'lazy' ) ); ?>
				<h3 class="pm-card__title"><?php echo esc_html( $product->get_name() ); ?></h3>
				<p class="pm-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></p>
				<?php if ( $badge ) : ?>
					<span class="pm-card__badge"><?php esc_html_e( 'ترند', 'pakanmental-home' ); ?></span>
				<?php endif; ?>
			</a>
		</article>
		<?php
	}

	private static function render_placeholder_card( string $title, bool $badge ): void {
		?>
		<article class="pm-card pm-card--empty">
			<div class="pm-card__ph" aria-hidden="true"></div>
			<h3 class="pm-card__title"><?php echo esc_html( $title ); ?></h3>
			<p class="pm-card__price">— <small><?php esc_html_e( 'تومان', 'pakanmental-home' ); ?></small></p>
			<?php if ( $badge ) : ?>
				<span class="pm-card__badge"><?php esc_html_e( 'ترند', 'pakanmental-home' ); ?></span>
			<?php endif; ?>
		</article>
		<?php
	}
}
