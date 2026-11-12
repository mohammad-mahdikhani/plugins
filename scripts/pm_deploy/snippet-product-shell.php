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
		$shop    = function_exists( 'is_shop' ) && is_shop();
		$post    = is_singular( 'post' );
		if ( ! $single && ! $archive && ! $shop && ! $post ) {
			return;
		}
		if ( $single ) {
			$which = 'single';
		} elseif ( $archive ) {
			$which = 'archive';
		} elseif ( $shop ) {
			$which = 'shop';
		} else {
			$which = 'post';
		}
		try {
			pm_tpl_document( $which );
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
	if ( 'single' === $which ) {
		$main = pm_tpl_single_product();
	} elseif ( 'archive' === $which ) {
		$main = pm_tpl_product_archive();
	} elseif ( 'shop' === $which ) {
		$main = pm_tpl_shop();
	} else {
		$main = pm_tpl_single_post();
	}
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
		body.pm-wc-shell{--pm-shell-width:min(1200px,92vw)}
		body.pm-wc-shell .pm-header{position:relative;z-index:30;padding:.75rem 0 .35rem}
		body.pm-wc-shell .pm-header__wrap,body.pm-wc-shell .pm-header__bar,body.pm-wc-shell #pm-main,body.pm-wc-shell .pm-footer .pm-container{width:var(--pm-shell-width)!important;max-width:var(--pm-shell-width)!important;margin-inline:auto!important;box-sizing:border-box!important}
		body.pm-wc-shell .pm-header__bar{display:grid!important;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr)!important;align-items:center!important;gap:.75rem 1rem!important;background:#f7f3ec!important;border:0!important;box-shadow:0 10px 30px rgba(30,67,57,.08)!important}
		body.pm-wc-shell #pm-main .pm-container,body.pm-wc-shell #pm-main .pm-product-single__grid,body.pm-wc-shell #pm-main .woocommerce-tabs,body.pm-wc-shell #pm-main .related.products,body.pm-wc-shell #pm-main .woocommerce div.product{width:100%!important;max-width:100%!important;float:none!important}
		body.pm-wc-shell .pm-header input,body.pm-wc-shell .pm-header button,body.pm-wc-shell .pm-search__input{appearance:none!important;-webkit-appearance:none!important}
		body.pm-wc-shell input[type="search"],body.pm-wc-shell input[type="text"],body.pm-wc-shell input[type="email"],body.pm-wc-shell input[type="url"],body.pm-wc-shell input[type="number"],body.pm-wc-shell textarea,body.pm-wc-shell select{border:1px solid #d5ebe3!important;border-radius:12px!important;box-shadow:none!important;outline:none!important;color:#1e4339!important}
		body.pm-wc-shell input:focus,body.pm-wc-shell textarea:focus,body.pm-wc-shell select:focus{border-color:#4aa88e!important;outline:none!important;box-shadow:0 0 0 3px rgba(74,168,142,.18)!important}
		body.pm-wc-shell .pm-search__input{width:100%!important;max-width:none!important;min-height:46px!important;border:none!important;border-radius:999px!important;background:#fff!important;color:#1e4339!important;box-shadow:inset 0 0 0 1.5px #d5ebe3!important;padding-inline:1.15rem 2.8rem!important}
		body.pm-wc-shell .pm-search__input:focus,body.pm-wc-shell .pm-search__input:focus-visible{border:none!important;outline:none!important;box-shadow:inset 0 0 0 1.5px #4aa88e!important}
		body.pm-wc-shell .pm-header .pm-search{width:min(280px,34vw)!important;max-width:280px!important;flex:0 1 280px!important}
		body.pm-wc-shell #pm-main a,body.pm-wc-shell .woocommerce-loop-product__title,body.pm-wc-shell .product_title{color:#1e4339!important}
		body.pm-wc-shell #pm-main a.button,body.pm-wc-shell #pm-main button.button,body.pm-wc-shell .single_add_to_cart_button{background:#4aa88e!important;border:none!important;border-radius:999px!important;color:#faf7f2!important;box-shadow:none!important}
		body.pm-wc-shell .price,body.pm-wc-shell .price *{color:#2a5f52!important}
		.pm-product-single .pm-product-single__grid{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:1.5rem!important;align-items:start!important;margin:0 auto!important}
		body.pm-wc-shell .pm-product-single__crumb{width:100%!important;margin:0 0 .85rem!important;padding:0!important}
		body.pm-wc-shell .pm-product-single__crumb .woocommerce-breadcrumb{display:block!important;width:100%!important;margin:0!important;padding:0!important;text-align:start!important;color:#6d8f84!important;font-size:.86rem!important;line-height:1.6!important}
		body.pm-wc-shell .pm-product-gallery,body.pm-wc-shell .pm-product-summary{width:100%!important;max-width:none!important;min-width:0!important;float:none!important;margin:0!important}
		body.pm-wc-shell .pm-product-summary{display:flex!important;flex-direction:column!important;align-items:stretch!important;align-self:start!important;gap:.85rem!important;padding:0!important;margin:0!important}
		body.pm-wc-shell .pm-product-summary .product_title{margin:0!important;font-size:clamp(1.45rem,2.2vw,2rem)!important;line-height:1.45!important;font-weight:800!important}
		body.pm-wc-shell .pm-product-summary__price,body.pm-wc-shell .pm-product-summary .price{margin:0!important;font-size:1.55rem!important;font-weight:800!important}
		body.pm-wc-shell .woocommerce-product-gallery,body.pm-wc-shell .woocommerce div.product div.images{width:100%!important;max-width:none!important;float:none!important;margin:0!important;opacity:1!important;position:relative!important}
		body.pm-wc-shell .woocommerce-product-gallery__wrapper,body.pm-wc-shell .flex-viewport,body.pm-wc-shell .woocommerce-product-gallery__image,body.pm-wc-shell .woocommerce-product-gallery__image a{width:100%!important;height:auto!important;max-height:none!important;display:block!important;transform:none!important;margin:0!important;left:auto!important;position:relative!important}
		body.pm-wc-shell .flex-viewport{overflow:visible!important}
		body.pm-wc-shell .woocommerce-product-gallery__image:not(:first-child){display:none!important}
		body.pm-wc-shell .woocommerce-product-gallery__image img,body.pm-wc-shell .pm-product-gallery img{width:100%!important;max-width:100%!important;height:auto!important;max-height:none!important;aspect-ratio:1/1!important;object-fit:contain!important;background:#f6f3ee!important;border-radius:18px!important}
		body.pm-wc-shell .pm-game-specs{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:.55rem!important;list-style:none!important;margin:0!important;padding:0!important;width:100%!important;max-width:none!important}
		body.pm-wc-shell .pm-game-spec{display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;aspect-ratio:auto!important;width:100%!important;min-width:0!important;max-width:none!important;min-height:4.6rem!important;height:auto!important;padding:.55rem .4rem!important;text-align:center!important;box-sizing:border-box!important;background:linear-gradient(180deg,#fff 0%,#e8f6f1 100%)!important;border:1px solid #b7ddd1!important;border-radius:14px!important;box-shadow:none!important;gap:.15rem!important}
		body.pm-wc-shell .pm-game-spec__label{font-size:.75rem!important;font-weight:600!important;color:#2a5f52!important;line-height:1.25!important}
		body.pm-wc-shell .pm-game-spec__value{font-size:.95rem!important;font-weight:800!important;color:#1e4339!important;line-height:1.3!important}
		body.pm-wc-shell .pm-qty-row,body.pm-wc-shell .pm-product-summary form.cart{display:flex!important;flex-direction:row!important;align-items:center!important;gap:.65rem!important;width:100%!important;margin:0!important}
		body.pm-wc-shell .pm-product-summary .quantity{float:none!important;margin:0!important;flex:0 0 4.6rem!important}
		body.pm-wc-shell .pm-product-summary .quantity .qty{width:100%!important;height:48px!important;text-align:center!important}
		body.pm-wc-shell .pm-product-summary .single_add_to_cart_button{flex:1 1 auto!important;width:auto!important;min-height:48px!important;font-size:1rem!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs{margin:1.5rem 0 0!important;padding:0!important;border:0!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs{display:flex!important;flex-wrap:wrap!important;gap:.4rem!important;justify-content:flex-start!important;align-items:center!important;list-style:none!important;margin:0 0 .85rem!important;padding:0!important;overflow:visible!important;border:0!important;background:transparent!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs::before,body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs::after,body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs li::before,body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs li::after{display:none!important;content:none!important;border:0!important;box-shadow:none!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs li{display:inline-flex!important;float:none!important;margin:0!important;padding:0!important;border:1px solid #d5ebe3!important;border-radius:999px!important;background:#fff!important;position:relative!important;z-index:1!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs li a{display:inline-flex!important;align-items:center!important;padding:.42rem .95rem!important;color:#2a5f52!important;font-weight:700!important;font-size:.86rem!important;line-height:1.3!important;text-decoration:none!important;background:transparent!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs li.active{background:#e8f6f1!important;border-color:#4aa88e!important}
		body.pm-wc-shell #pm-main .woocommerce-tabs ul.tabs li.active a{color:#1e4339!important}
		body.pm-wc-shell #pm-main .woocommerce-Tabs-panel{background:#fff!important;border-radius:18px!important;padding:1.15rem 1.25rem 1.4rem!important;margin:0!important;box-shadow:0 8px 24px rgba(30,67,57,.05)!important;color:#1e4339!important}
		body.pm-wc-shell #pm-main .woocommerce-Tabs-panel h2{color:#1e4339!important;font-size:1.15rem!important;margin:0 0 .7rem!important}
		body.pm-wc-shell #pm-main .woocommerce-Tabs-panel p,body.pm-wc-shell #pm-main .woocommerce-Tabs-panel li{color:#2a5f52!important;line-height:1.85!important}
		body.pm-wc-shell #pm-main #reviews .star-rating,body.pm-wc-shell #pm-main #reviews .stars a{color:#4aa88e!important}
		body.pm-wc-shell #pm-main #review_form #respond{margin:0!important}
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
			body.pm-wc-shell .pm-header__bar{grid-template-columns:44px minmax(0,1fr) 44px!important}
			body.pm-wc-shell .pm-burger{display:flex!important;grid-column:1;justify-self:start}
			body.pm-wc-shell .pm-header__logo{display:flex!important;grid-column:2;justify-self:center}
			body.pm-wc-shell .pm-header__cart-mobile{display:flex!important;grid-column:3;justify-self:end}
			body.pm-wc-shell .related.products ul.products,.pm-product-single .pm-product-single__grid{grid-template-columns:1fr!important}
			body.pm-wc-shell .related.products ul.products{grid-template-columns:repeat(2,minmax(0,1fr))!important}
			body.pm-wc-shell .pm-product-single__grid{align-items:stretch!important;gap:1rem!important}
			body.pm-wc-shell .pm-product-summary .product_title{font-size:1.4rem!important}
			body.pm-wc-shell .pm-product-summary__price,body.pm-wc-shell .pm-product-summary .price{font-size:1.3rem!important}
			body.pm-wc-shell .pm-game-specs{grid-template-columns:repeat(3,minmax(0,1fr))!important}
			body.pm-wc-shell .pm-product-summary .single_add_to_cart_button{min-height:46px!important}
		}
		@media(max-width:560px){
			body.pm-wc-shell .pm-game-specs{grid-template-columns:repeat(2,minmax(0,1fr))!important}
			body.pm-wc-shell .pm-product-summary form.cart{flex-wrap:wrap!important}
			body.pm-wc-shell .pm-product-summary .single_add_to_cart_button{flex:1 1 100%!important}
		}
	.pm-product{display:flex;flex-direction:column}.pm-product__link{padding-bottom:0!important}.pm-product__buy{display:flex;flex-direction:row;align-items:center;gap:.35rem;margin-top:auto;padding:.45rem .65rem .65rem}.pm-product__stepper{display:inline-flex;flex-flow:row nowrap;direction:ltr;align-items:center;justify-content:space-between;flex:0 0 5.5rem;width:5.5rem;height:32px;padding:0 .1rem;border:1px solid #d5ebe3;border-radius:999px;background:#fff}.pm-product__stepper button{width:22px;height:22px;border:0;background:transparent;color:#1e4339;border-radius:50%;font:700 1rem/1 inherit;cursor:pointer;padding:0}.pm-product__stepper input.qty{width:1.7rem!important;height:auto!important;min-height:0!important;margin:0!important;padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important;text-align:center;font:700 .78rem/1 inherit;color:#1e4339;-moz-appearance:textfield;appearance:textfield}.pm-product__stepper input.qty::-webkit-outer-spin-button,.pm-product__stepper input.qty::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}.pm-product__buy .pm-product__add,.pm-product__buy .single_add_to_cart_button{flex:1 1 auto;min-width:0;width:auto!important;height:32px!important;min-height:32px!important;margin:0!important;padding:0 .45rem!important;border:0!important;border-radius:999px!important;background:#4aa88e!important;color:#fff!important;font-family:inherit!important;font-size:.72rem!important;font-weight:700!important;line-height:1!important;box-shadow:none!important;float:none!important;white-space:nowrap;overflow:hidden}.pm-product__buy .pm-product__add:hover{background:#3d8f78!important}
.pm-product-grid{align-items:stretch}.pm-product{height:100%}.pm-product__link{display:flex;flex-direction:column;flex:1 1 auto}.pm-product__title{min-height:2.7em}
		.pm-pagination{display:flex!important;justify-content:center!important;margin-top:1.35rem!important}
		.pm-pagination ul.page-numbers{display:flex!important;flex-flow:row wrap!important;direction:ltr!important;justify-content:center!important;align-items:center!important;gap:.4rem!important;list-style:none!important;margin:0!important;padding:0!important}
		.pm-pagination ul.page-numbers li{list-style:none!important;margin:0!important}
		.pm-pagination ul.page-numbers li::marker{content:none!important}
		.pm-pagination .page-numbers a,.pm-pagination .page-numbers span.page-numbers{display:inline-flex!important;align-items:center!important;justify-content:center!important;min-width:2.45rem!important;height:2.45rem!important;padding:0 .75rem!important;border-radius:12px!important;background:#fff!important;color:#1e4339!important;font-weight:800!important;text-decoration:none!important;box-shadow:0 1px 4px rgba(30,67,57,.12)!important}
		.pm-pagination .page-numbers span.current{background:#4aa88e!important;color:#fff!important}
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
			<div class="pm-container">
				<div class="pm-product-single__crumb">
					<?php woocommerce_breadcrumb(); ?>
				</div>
				<div class="pm-product-single__grid">
				<div class="pm-product-gallery">
					<?php woocommerce_show_product_sale_flash(); ?>
					<?php woocommerce_show_product_images(); ?>
				</div>
				<div class="pm-product-summary">
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

function pm_tpl_shop(): string {
	$pm_shop_page = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$pm_shop_max  = 1;
	$products     = array();
	if ( function_exists( 'wc_get_products' ) ) {
		$pm_shop_result = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 24,
				'page'     => $pm_shop_page,
				'paginate' => true,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		if ( is_object( $pm_shop_result ) && isset( $pm_shop_result->products ) && is_array( $pm_shop_result->products ) ) {
			$products    = $pm_shop_result->products;
			$pm_shop_max = max( 1, (int) $pm_shop_result->max_num_pages );
		}
	}
	ob_start();
	?>
	<section class="pm-page-head">
		<div class="pm-container">
			<h1><?php echo esc_html__( 'فروشگاه', 'pakanmental' ); ?></h1>
			<p><?php echo esc_html__( 'بازی فکری، پازل، اکشن فیگور و سرگرمی رومیزی.', 'pakanmental' ); ?></p>
		</div>
	</section>
	<section class="pm-section">
		<div class="pm-container">
			<?php if ( ! $products ) : ?>
				<p class="pm-empty"><?php echo esc_html__( 'در این فروشگاه هنوز محصولی نیست.', 'pakanmental' ); ?></p>
			<?php else : ?>
				<div class="pm-product-grid">
					<?php foreach ( $products as $shop_product ) : ?>
						<?php
						if ( ! $shop_product instanceof WC_Product ) {
							continue;
						}
						$image = $shop_product->get_image( 'woocommerce_thumbnail', array( 'class' => 'pm-product__thumb' ) );
						?>
						<article class="pm-product">
							<a class="pm-product__link" href="<?php echo esc_url( $shop_product->get_permalink() ); ?>">
								<?php
								if ( $image ) {
									echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									echo '<div class="pm-product__thumb"></div>';
								}
								?>
								<h3 class="pm-product__title"><?php echo esc_html( $shop_product->get_name() ); ?></h3>
								<p class="pm-product__price"><?php echo wp_kses_post( $shop_product->get_price_html() ); ?></p>
							</a>
							<?php if ( $shop_product->is_purchasable() && $shop_product->is_in_stock() && $shop_product->is_type( 'simple' ) ) : ?>
								<form class="pm-product__buy cart" method="post" action="<?php echo esc_url( $shop_product->get_permalink() ); ?>">
									<div class="pm-product__stepper">
										<button type="button" data-pm-qty-step="-1" aria-label="کم کردن">−</button>
										<input class="qty" type="number" name="quantity" value="1" min="1" inputmode="numeric" aria-label="تعداد">
										<button type="button" data-pm-qty-step="1" aria-label="زیاد کردن">+</button>
									</div>
									<button type="submit" class="pm-product__add single_add_to_cart_button" name="add-to-cart" value="<?php echo esc_attr( (string) $shop_product->get_id() ); ?>">افزودن به سبد خرید</button>
								</form>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
					<script>(function(){document.querySelectorAll(".pm-product__buy").forEach(function(form){if(form.dataset.pmQtyBound)return;form.dataset.pmQtyBound="1";form.addEventListener("click",function(e){var btn=e.target.closest("[data-pm-qty-step]");if(!btn||!form.contains(btn))return;e.preventDefault();var input=form.querySelector("input.qty");if(!input)return;var next=(parseInt(input.value,10)||1)+(parseInt(btn.getAttribute("data-pm-qty-step"),10)||0);input.value=String(next<1?1:next);});});})();</script>
				</div>
				<?php if ( $pm_shop_max > 1 ) : ?>
					<nav class="pm-pagination" aria-label="<?php echo esc_attr__( 'صفحه‌بندی محصولات', 'pakanmental' ); ?>">
						<?php
						echo paginate_links(
							array(
								'total'     => $pm_shop_max,
								'current'   => $pm_shop_page,
								'type'      => 'list',
								'prev_text' => 'قبلی',
								'next_text' => 'بعدی',
								'mid_size'  => 1,
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</nav>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

function pm_tpl_single_post(): string {
	$post_id = (int) get_queried_object_id();
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	$magazine = get_permalink( 20 );
	if ( ! $magazine ) {
		$magazine = home_url( '/' );
	}
	ob_start();
	?>
	<section class="pm-page-head">
		<div class="pm-container">
			<p class="pm-breadcrumb"><a href="<?php echo esc_url( $magazine ); ?>"><?php echo esc_html__( 'مجله', 'pakanmental' ); ?></a></p>
			<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
		</div>
	</section>
	<section class="pm-section">
		<div class="pm-container">
			<article class="pm-article"><?php echo pm_tpl_article_html( $post->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></article>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

function pm_tpl_article_html( string $html ): string {
	$html = preg_replace( '#<style[\s\S]*?</style>#', '', $html ) ?? $html;
	$html = preg_replace( '#<script[\s\S]*?</script>#', '', $html ) ?? $html;
	$html = preg_replace( '#<header[\s\S]*?</header>#', '', $html ) ?? $html;
	$html = preg_replace( '#<footer[\s\S]*?</footer>#', '', $html ) ?? $html;
	$html = preg_replace( '#<nav[\s\S]*?</nav>#', '', $html ) ?? $html;
	preg_match_all( '#<p\b[^>]*>(.*?)</p>#s', $html, $matches );
	$seen  = array();
	$parts = array();
	foreach ( $matches[1] as $inner ) {
		$text = trim( wp_strip_all_tags( $inner ) );
		if ( '' === $text || isset( $seen[ $text ] ) ) {
			continue;
		}
		if ( false !== strpos( $text, '©' ) || false !== strpos( $text, 'بازگشت به مجله' ) || false !== strpos( $text, 'محصولی پیدا نشد' ) ) {
			continue;
		}
		$seen[ $text ] = true;
		$parts[]       = '<p>' . esc_html( $text ) . '</p>';
	}
	if ( ! $parts ) {
		$text = trim( wp_strip_all_tags( $html ) );
		return '' === $text ? '' : '<p>' . esc_html( $text ) . '</p>';
	}
	return implode( '', $parts );
}

function pm_tpl_product_archive(): string {
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		$term = null;
	}
	$name             = $term instanceof WP_Term ? $term->name : '';
	$pm_archive_page = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	$pm_archive_max  = 1;
	$products        = array();
	if ( $term instanceof WP_Term && function_exists( 'wc_get_products' ) ) {
		$found = wc_get_products(
			array(
				'status'    => 'publish',
				'limit'     => 24,
				'page'      => $pm_archive_page,
				'paginate'  => true,
				'orderby'   => 'date',
				'order'     => 'DESC',
				'tax_query' => array(
					array(
						'taxonomy'         => $term->taxonomy,
						'field'            => 'term_id',
						'terms'            => array( (int) $term->term_id ),
						'include_children' => true,
					),
				),
			)
		);
		if ( is_object( $found ) && isset( $found->products ) && is_array( $found->products ) ) {
			$products       = $found->products;
			$pm_archive_max = max( 1, (int) $found->max_num_pages );
		}
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
				<?php if ( $pm_archive_max > 1 ) : ?>
					<nav class="pm-pagination" aria-label="<?php echo esc_attr__( 'صفحه‌بندی محصولات', 'pakanmental' ); ?>">
						<?php
						echo paginate_links(
							array(
								'total'     => $pm_archive_max,
								'current'   => $pm_archive_page,
								'type'      => 'list',
								'prev_text' => 'قبلی',
								'next_text' => 'بعدی',
								'mid_size'  => 1,
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</nav>
				<?php endif; ?>
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
