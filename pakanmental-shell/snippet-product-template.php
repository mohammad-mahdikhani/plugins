<?php
/**
 * Site-wide product template. One layout for every product and category.
 * Elementor documents: header 124, footer 125.
 * Product and category body come from the current query, not a cached Elementor document.
 */

add_action(
	'init',
	static function (): void {
		add_shortcode( 'pm_single_product', 'pm_tpl_single_product' );
		add_shortcode( 'pm_product_archive', 'pm_tpl_product_archive' );
	}
);

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		$single  = is_singular( 'product' );
		$archive = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
		if ( ! $single && ! $archive ) {
			return;
		}
		try {
			pm_tpl_document( $single ? 'single' : 'archive' );
		} catch ( Throwable $e ) {
			return;
		}
		exit;
	},
	0
);

function pm_tpl_document( string $which ): void {
	// Build the page body before Elementor header/footer. Elementor caches the
	// shared template and would otherwise repeat the last product or category.
	$main = ( 'single' === $which ) ? pm_tpl_single_product() : pm_tpl_product_archive();
	status_header( 200 );
	$home = trailingslashit( plugins_url( 'pakanmental-home' ) );
	$ver  = '1.2.1';
	wp_enqueue_style( 'pm-fonts', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'pm-brand-overrides', $home . 'assets/css/pm-brand-overrides.css', array(), $ver );
	wp_enqueue_style( 'pm-site', $home . 'assets/css/pm.css', array( 'pm-fonts' ), $ver );
	foreach ( array( 'pm-header', 'pm-mega-menu', 'pm-mobile', 'pm-search', 'pm-trust-badges' ) as $handle ) {
		wp_enqueue_script( $handle, $home . 'assets/js/' . $handle . '.js', array(), $ver, true );
	}
	if ( function_exists( 'wc_get_cart_url' ) ) {
		$cfg = array(
			'storeApi'    => rest_url( 'wc/store/v1' ),
			'cartUrl'     => wc_get_cart_url(),
			'checkoutUrl' => function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
			'shopUrl'     => wc_get_page_permalink( 'shop' ),
		);
		wp_add_inline_script( 'pm-header', 'window.pmCart=' . wp_json_encode( $cfg ) . ';', 'before' );
	}
	if ( 'single' === $which ) {
		wp_enqueue_script( 'wc-add-to-cart' );
	}
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style id="pm-product-template-css">
		html body.pm-wc-shell .site-header,html body.pm-wc-shell header#site-header,html body.pm-wc-shell .dynamic-header,html body.pm-wc-shell .site-footer,html body.pm-wc-shell footer#site-footer{display:none!important}
		body.pm-wc-shell .elementor-124,body.pm-wc-shell .elementor-125,body.pm-wc-shell .elementor-124 .e-con,body.pm-wc-shell .elementor-125 .e-con,body.pm-wc-shell .elementor-widget-html,body.pm-wc-shell .elementor-widget-container{display:block!important;width:100%!important;max-width:none!important;padding:0!important;margin:0!important;background:transparent!important;border:0!important}
		body.pm-wc-shell .pm-header{position:relative;z-index:30;padding:.75rem 0 .35rem}
		body.pm-wc-shell .pm-header__bar{display:grid!important;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr)!important;align-items:center!important;gap:.75rem 1rem!important;width:min(1200px,94vw)!important;margin:0 auto!important;background:#f7f3ec!important;border:0!important;box-shadow:0 10px 30px rgba(30,67,57,.08)!important}
		body.pm-wc-shell .pm-header input,body.pm-wc-shell .pm-header button,body.pm-wc-shell .pm-search__input{appearance:none!important;-webkit-appearance:none!important}
		body.pm-wc-shell input[type="search"],body.pm-wc-shell input[type="text"],body.pm-wc-shell input[type="email"],body.pm-wc-shell input[type="url"],body.pm-wc-shell input[type="number"],body.pm-wc-shell textarea,body.pm-wc-shell select{border:1px solid #d5ebe3!important;border-radius:12px!important;box-shadow:none!important;outline:none!important;color:#1e4339!important}
		body.pm-wc-shell input:focus,body.pm-wc-shell textarea:focus,body.pm-wc-shell select:focus{border-color:#4aa88e!important;outline:none!important;box-shadow:0 0 0 3px rgba(74,168,142,.18)!important}
		body.pm-wc-shell .pm-search__input{width:100%!important;max-width:none!important;min-height:46px!important;border:none!important;border-radius:999px!important;background:#fff!important;color:#1e4339!important;box-shadow:inset 0 0 0 1.5px #d5ebe3!important;padding-inline:1.15rem 2.8rem!important}
		body.pm-wc-shell .pm-search__input:focus,body.pm-wc-shell .pm-search__input:focus-visible{border:none!important;outline:none!important;box-shadow:inset 0 0 0 1.5px #4aa88e!important}
		body.pm-wc-shell .pm-header .pm-search{width:min(280px,34vw)!important;max-width:280px!important;flex:0 1 280px!important}
		body.pm-wc-shell #pm-main a,body.pm-wc-shell .woocommerce-loop-product__title,body.pm-wc-shell .product_title{color:#1e4339!important}
		body.pm-wc-shell #pm-main a.button,body.pm-wc-shell #pm-main button.button,body.pm-wc-shell .single_add_to_cart_button{background:#4aa88e!important;border:none!important;border-radius:999px!important;color:#faf7f2!important;box-shadow:none!important}
		body.pm-wc-shell .price,body.pm-wc-shell .price *{color:#2a5f52!important}
		.pm-product-single .pm-product-single__grid{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1.05fr)!important;gap:1.5rem!important;align-items:start!important;width:min(1200px,92vw)!important;margin:0 auto!important}
		.pm-game-specs{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:.55rem!important;list-style:none!important;margin:0 0 1.15rem!important;padding:0!important;width:100%!important}
		.pm-game-spec{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;aspect-ratio:1/1!important;width:100%!important;padding:.45rem .3rem!important;text-align:center!important;box-sizing:border-box!important;background:linear-gradient(180deg,#fff 0%,#e8f6f1 100%)!important;border:1.5px solid #4aa88e!important;border-radius:18px!important}
		.pm-game-spec__label{font-size:.68rem!important;font-weight:600!important;color:#2a5f52!important}
		.pm-game-spec__value{font-size:.86rem!important;font-weight:800!important;color:#1e4339!important}
		body.pm-wc-shell .related.products{margin:0!important}
		body.pm-wc-shell .related.products > h2,body.pm-wc-shell .pm-related > .pm-container > h2{margin:0 0 1rem!important;color:#1e4339!important;font-size:1.35rem!important}
		body.pm-wc-shell .related.products ul.products{display:grid!important;grid-template-columns:repeat(4,minmax(0,1fr))!important;gap:1rem!important;margin:0!important;padding:0!important;list-style:none!important;clear:both!important}
		body.pm-wc-shell .related.products ul.products::before,body.pm-wc-shell .related.products ul.products::after{display:none!important}
		body.pm-wc-shell .related.products li.product{width:auto!important;float:none!important;margin:0!important;padding:.75rem!important;background:#fff!important;border-radius:18px!important;box-shadow:0 8px 24px rgba(30,67,57,.06)!important;text-align:center!important}
		body.pm-wc-shell .related.products li.product img{width:100%!important;height:auto!important;aspect-ratio:1!important;object-fit:cover!important;border-radius:14px!important;background:#f4f7f6!important;margin:0 0 .65rem!important}
		body.pm-wc-shell .related.products .button{display:inline-flex!important;margin-top:.65rem!important}
		body.pm-wc-shell #pm-enamad{min-height:0!important;background:#fff!important;border:0!important;border-radius:16px!important;padding:.55rem!important}
		body.pm-wc-shell #pm-enamad img{width:120px!important;max-width:140px!important;height:auto!important;border:0!important;box-shadow:none!important;background:#fff!important}
		body.pm-wc-shell #pm-enamad a{border:0!important;box-shadow:none!important}
		@media(min-width:901px){
			body.pm-wc-shell .pm-header .pm-mobile-only{display:none!important}
			body.pm-wc-shell .pm-header__nav{display:block!important;grid-column:1;justify-self:start}
			body.pm-wc-shell .pm-header__logo{display:flex!important;grid-column:2;justify-self:center}
			body.pm-wc-shell .pm-header__tools{display:flex!important;grid-column:3;justify-self:end;align-items:center}
		}
		@media(max-width:900px){
			body.pm-wc-shell .pm-header__nav,body.pm-wc-shell .pm-header__tools,body.pm-wc-shell .pm-header .pm-desktop-only{display:none!important}
			body.pm-wc-shell .pm-header__bar{grid-template-columns:44px minmax(0,1fr) 44px!important;width:min(1200px,94vw)!important}
			body.pm-wc-shell .pm-burger{display:flex!important;grid-column:1;justify-self:start}
			body.pm-wc-shell .pm-header__logo{display:flex!important;grid-column:2;justify-self:center}
			body.pm-wc-shell .pm-header__cart-mobile{display:flex!important;grid-column:3;justify-self:end}
			body.pm-wc-shell .related.products ul.products,.pm-product-single .pm-product-single__grid{grid-template-columns:1fr!important}
			body.pm-wc-shell .related.products ul.products{grid-template-columns:repeat(2,minmax(0,1fr))!important}
		}
	</style>
</head>
<body <?php body_class( array( 'pm-site', 'pm-wc-shell' ) ); ?>>
	<?php
	wp_body_open();
	pm_tpl_elementor( 124 );
	echo '<main id="pm-main" class="pm-main">' . $main . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	pm_tpl_elementor( 125 );
	wp_footer();
	?>
</body>
</html>
	<?php
}

function pm_tpl_elementor( int $post_id ): void {
	if ( $post_id < 1 || ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	$html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
	if ( ! is_string( $html ) || '' === $html ) {
		return;
	}
	if ( 125 === $post_id ) {
		$html = pm_tpl_fill_enamad( $html );
	}
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function pm_tpl_fill_enamad( string $html ): string {
	$badge = trim( (string) get_option( 'pm_trust_badges_html', '' ) );
	if ( '' === $badge || false === strpos( $html, 'id="pm-enamad"' ) ) {
		return $html;
	}
	$filled = preg_replace(
		'/(<div\b[^>]*\bid="pm-enamad"[^>]*>)(.*?)(<\/div>)/s',
		'$1' . $badge . '$3',
		$html,
		1
	);
	return is_string( $filled ) ? $filled : $html;
}

function pm_tpl_disable_elementor_content(): void {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}
	$frontend = \Elementor\Plugin::$instance->frontend;
	$priority = 9;
	if ( defined( '\Elementor\Frontend::THE_CONTENT_FILTER_PRIORITY' ) ) {
		$priority = (int) \Elementor\Frontend::THE_CONTENT_FILTER_PRIORITY;
	}
	remove_filter( 'the_content', array( $frontend, 'apply_builder_in_content' ), $priority );
	global $wp_filter;
	if ( ! isset( $wp_filter['the_content'] ) ) {
		return;
	}
	foreach ( $wp_filter['the_content']->callbacks as $prio => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$fn = $callback['function'] ?? null;
			if ( is_array( $fn ) && isset( $fn[1] ) && 'apply_builder_in_content' === $fn[1] ) {
				remove_filter( 'the_content', $fn, (int) $prio );
			}
		}
	}
}

function pm_tpl_elementor_or_shortcode( int $post_id, string $shortcode ): void {
	$html = '';
	if ( $post_id > 0 && class_exists( '\Elementor\Plugin' ) ) {
		$html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
	}
	if ( ! is_string( $html ) || '' === trim( wp_strip_all_tags( $html ) ) ) {
		echo do_shortcode( $shortcode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}
	echo do_shortcode( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function pm_tpl_single_product(): string {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	$product_id = is_singular( 'product' ) ? (int) get_queried_object_id() : 0;
	$product    = $product_id ? wc_get_product( $product_id ) : null;
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$post_object = get_post( $product_id );
	if ( $post_object instanceof WP_Post ) {
		$GLOBALS['post'] = $post_object;
		setup_postdata( $post_object );
	}
	$GLOBALS['product'] = $product;
	pm_tpl_disable_elementor_content();
	ob_start();
	do_action( 'woocommerce_before_single_product' );
	if ( post_password_required() ) {
		echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return (string) ob_get_clean();
	}
	?>
	<div id="product-<?php echo (int) $product->get_id(); ?>" <?php wc_product_class( 'pm-wc-single-product', $product ); ?>>
		<section class="pm-product-single">
			<div class="pm-container pm-product-single__grid">
				<div class="pm-product-gallery">
					<?php woocommerce_show_product_sale_flash(); ?>
					<?php woocommerce_show_product_images(); ?>
				</div>
				<div class="pm-product-summary">
					<?php woocommerce_breadcrumb(); ?>
					<h1 class="product_title entry-title"><?php echo esc_html( $product->get_name() ); ?></h1>
					<?php woocommerce_template_single_rating(); ?>
					<div class="pm-product-summary__price"><?php woocommerce_template_single_price(); ?></div>
					<?php if ( $product->get_short_description() ) : ?>
						<div class="pm-product-summary__excerpt"><?php woocommerce_template_single_excerpt(); ?></div>
					<?php endif; ?>
					<?php echo pm_tpl_attributes_html( (int) $product->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="pm-qty-row"><?php woocommerce_template_single_add_to_cart(); ?></div>
				</div>
			</div>
		</section>
		<?php woocommerce_output_product_data_tabs(); ?>
		<section class="pm-section pm-section--alt pm-related">
			<div class="pm-container">
				<?php woocommerce_output_related_products(); ?>
			</div>
		</section>
	</div>
	<?php
	do_action( 'woocommerce_after_single_product' );
	return (string) ob_get_clean();
}

function pm_tpl_product_archive(): string {
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		$term = null;
	}
	$name     = $term instanceof WP_Term ? $term->name : '';
	$products = array();
	if ( $term instanceof WP_Term && function_exists( 'wc_get_products' ) ) {
		$found = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 24,
				'paginate' => false,
				'orderby'  => 'date',
				'order'    => 'DESC',
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
	<section class="pm-shop">
		<div class="pm-container">
			<?php if ( ! $products ) : ?>
				<p class="pm-empty"><?php echo esc_html__( 'در این دسته‌بندی هنوز محصولی نیست.', 'pakanmental' ); ?></p>
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

function pm_tpl_attributes_html( int $product_id ): string {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$rows = array();
	foreach ( $product->get_attributes() as $attribute ) {
		$taxonomy = is_object( $attribute ) ? $attribute->get_name() : '';
		$label    = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $taxonomy, $product ) : $taxonomy;
		$value    = '';
		if ( is_object( $attribute ) && $attribute->is_taxonomy() ) {
			$terms = wc_get_product_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
			if ( is_array( $terms ) ) {
				$value = implode( '، ', array_map( 'strval', $terms ) );
			}
		} elseif ( is_object( $attribute ) ) {
			$opts  = $attribute->get_options();
			$value = is_array( $opts ) ? implode( '، ', array_map( 'strval', $opts ) ) : '';
		}
		$value = trim( (string) $value );
		if ( '' === $value || '' === $label ) {
			continue;
		}
		$rows[] = array( $label, $value );
	}
	if ( ! $rows ) {
		return '';
	}
	$html = '<ul class="pm-game-specs" aria-label="ویژگی‌های محصول">';
	foreach ( $rows as $row ) {
		$html .= '<li class="pm-game-spec"><span class="pm-game-spec__label">' . esc_html( $row[0] ) . '</span><span class="pm-game-spec__value">' . esc_html( $row[1] ) . '</span></li>';
	}
	$html .= '</ul>';
	return $html;
}
