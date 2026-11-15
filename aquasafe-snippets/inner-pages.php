add_filter( 'woolentor_product_limit', function () { return 15; }, 99 );
add_filter( 'woocommerce_shortcode_products_query', function ( $args ) {
	if ( is_shop() || is_product_taxonomy() ) {
		$args['posts_per_page'] = 15;
		$args['limit'] = 15;
	}
	return $args;
}, 99 );


add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}
	if ( $q->is_post_type_archive( 'product' ) || $q->is_tax( get_object_taxonomies( 'product' ) ) ) {
		$q->set( 'posts_per_page', 15 );
	}
}, 99 );
add_filter( 'loop_shop_per_page', function () { return 15; }, 99 );
/** AquaSafe SEO: one H1 per page */
add_action( 'wp_head', function () {
  echo '<style id="as-one-h1">.single-post .page-header{display:none!important;}.tax-product_cat .woocommerce-products-header{display:none!important;}</style>' . "
";
}, 99 );
add_filter( 'loop_shop_columns', function () { return 3; }, 99 );

/**
 * AquaSafe inner pages: quieter hirabsun-like overlay.
 */
$as_icon = 'https://aquasafe.ir/wp-content/uploads/2026/10/aquasafe-icon-512.png';

add_filter( 'get_site_icon_url', function ( $url ) use ( $as_icon ) {
	return $as_icon ? $as_icon : $url;
}, 99 );

add_action( 'wp_head', function () use ( $as_icon ) {
	if ( ! $as_icon ) {
		return;
	}
	echo '<link rel="icon" href="' . esc_url( $as_icon ) . '" sizes="32x32" />' . "\n";
	echo '<link rel="icon" href="' . esc_url( $as_icon ) . '" sizes="192x192" />' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $as_icon ) . '" />' . "\n";
	echo '<link rel="shortcut icon" href="' . esc_url( $as_icon ) . '" />' . "\n";
	echo '<meta name="msapplication-TileImage" content="' . esc_url( $as_icon ) . '" />' . "\n";
}, 1 );

add_action( 'wp', function () {
	if ( is_front_page() ) {
		return;
	}
	if ( ! get_option( 'as_inner_style_v47' ) ) {
		do_action( 'litespeed_purging_all' );
		update_option( 'as_inner_style_v47', 1 );
	}
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_front_page() ) {
		return;
	}
	wp_enqueue_style( 'as-vazirmatn', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css', array(), '33.003' );
	wp_enqueue_style( 'as-peyda', 'https://fonts.googleapis.com/css2?family=Peyda:wght@300;400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'as-montserrat', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600&display=swap', array(), null );
}, 5 );

add_filter( 'body_class', function ( $c ) {
	if ( ! is_front_page() ) {
		$c[] = 'as-inner';
	}
	return $c;
} );

add_action( 'wp_head', function () {
	if ( is_front_page() ) {
		return;
	}
	echo <<<'CSS'
<style id="as-inner-css">
:root{--as-navy:#1e4b9a;--as-ink:#44566a;--as-mute:#8aa0b3;--as-mint:#f4fbfb;--as-mint2:#eef8f9;--as-teal:#76cdd9;--as-line:#e7eef2;--as-bg:#fbfefe;--as-font:"Peyda","Vazirmatn",Tahoma,sans-serif}
html{scroll-behavior:smooth}
body.as-inner{font-family:var(--as-font)!important;background:var(--as-bg)!important;color:var(--as-ink)!important;letter-spacing:.01em;padding-top:118px!important;overflow-x:hidden}
body.as-inner *,body.as-inner button,body.as-inner input,body.as-inner select,body.as-inner textarea,body.as-inner h1,body.as-inner h2,body.as-inner h3,body.as-inner h4,body.as-inner .button,body.as-inner .elementor-heading-title,body.as-inner .woocommerce-loop-product__title{font-family:var(--as-font)!important}
body.as-inner a{color:var(--as-navy);text-decoration:none}
body.as-inner #masthead,body.as-inner #site-header,body.as-inner header.site-header,body.as-inner .ast-mobile-header-wrap{display:none!important}
body.as-inner #colophon,body.as-inner footer#colophon,body.as-inner .hfe-before-footer-hook,body.as-inner .elementor-location-footer,body.as-inner .hfe-scroll-to-top-wrap,body.as-inner .ehf-footer,body.as-inner .elementor-element-07b35c0,body.as-inner .elementor-element-bdc5b37{display:none!important}
body.as-inner #site-footer,body.as-inner footer.site-footer,body.as-inner .site-description,body.as-inner .footer-inner .site-branding{display:none!important}
body.as-inner .site-content,body.as-inner #content{padding-top:0!important;background:transparent!important}
body.as-inner .ast-container,body.as-inner .site-content > .ast-container{max-width:1180px;padding-left:24px;padding-right:24px}
body.woocommerce .entry-header,body.as-inner .ast-archive-description,body.woocommerce-shop .page-header,body.as-inner .woocommerce-products-header,body.woocommerce .page-title,body.as-inner .woocommerce-result-count,body.as-inner .woocommerce-ordering,body.as-inner .ast-woocommerce-container > header{display:none!important}
body.as-inner .chaty-widget,body.as-inner .chaty-i{z-index:40!important}
#as-top{position:fixed;top:0;left:0;right:0;z-index:10050;background:#fff;border-bottom:1px solid var(--as-line);box-shadow:0 10px 40px rgba(30,75,154,.06);font-family:var(--as-font)}
#as-top.is-stuck{box-shadow:0 14px 36px rgba(30,75,154,.1)}
.as-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:7px clamp(16px,3.2vw,48px);background:linear-gradient(90deg,#f4fbfb 0%,#eef8f9 100%);border-bottom:1px solid rgba(118,205,217,.25);font-size:11.5px;color:#6b8193}
.as-topbar b{color:var(--as-navy);font-weight:600}
.as-topbar a{color:var(--as-navy);font-weight:600;direction:ltr;letter-spacing:.3px}
.as-head-main{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:10px;padding:14px clamp(16px,3.2vw,48px) 12px;max-width:1280px;margin:0 auto;width:100%}
#as-top a{color:#6b7c8d;font-size:12px;font-weight:500}
#as-top a:hover{color:var(--as-navy)}
.as-nav ul{display:flex;align-items:flex-end;gap:18px;list-style:none;margin:0;padding:0}
.as-nav-r ul{justify-content:flex-end}
.as-nav-l ul{justify-content:flex-start;align-items:flex-end}
.as-nav a{display:flex;flex-direction:column;align-items:center;gap:6px;min-width:52px;padding:4px 6px 6px;border-radius:14px;transition:color .25s,background .25s,transform .25s}
.as-nav a svg{width:22px;height:22px;opacity:.45;stroke:currentColor;fill:none;stroke-width:1.5;transition:opacity .25s,transform .25s}
.as-nav a:hover,.as-nav a.is-on{color:var(--as-navy)!important;background:rgba(118,205,217,.12);transform:translateY(-1px)}
.as-nav a:hover svg,.as-nav a.is-on svg{opacity:1;color:var(--as-navy)}
.as-nav a.is-on{box-shadow:inset 0 -2px 0 var(--as-teal)}
.as-logo{display:flex;align-items:center;justify-content:center;width:196px;margin:0 auto;padding:0 8px}
.as-logo img{width:100%;height:auto;display:block;min-height:44px;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(30,75,154,.08))}
header .as-head-cta, header button.as-head-cta, .as-head-cta{
display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:6px;padding:10px 16px!important;
border-radius:999px!important;background:#1e4b9a!important;color:#fff!important;font-size:12px!important;font-weight:700!important;
white-space:nowrap;margin-inline-start:8px;flex-direction:row!important;min-width:0!important;min-height:40px!important;
border:0!important;outline:none!important;box-shadow:0 10px 24px rgba(30,75,154,.18)!important;appearance:none;-webkit-appearance:none;
font-family:inherit;line-height:1.6!important;letter-spacing:0!important;cursor:pointer}
header .as-head-cta:hover, header .as-head-cta:focus, header .as-head-cta:active, .as-head-cta:hover, .as-head-cta:focus{
background:#163d7a!important;color:#fff!important;border:0!important;outline:2px solid #76cdd9!important;outline-offset:2px!important;
box-shadow:0 10px 24px rgba(30,75,154,.18)!important}
header button.as-head-cta-ghost, .as-head-cta-ghost{
background:#eefbfc!important;color:#1e4b9a!important;box-shadow:none!important;border:1px solid #76cdd9!important}
header button.as-head-cta-ghost:hover, header button.as-head-cta-ghost:focus, .as-head-cta-ghost:hover{
background:#d8f4f8!important;color:#1e4b9a!important;border-color:#76cdd9!important;outline:2px solid #76cdd9!important}
.as-drawer button{background:none;border:0;color:#fff;font-size:20px;cursor:pointer;font-family:inherit}
#crisp-chatbox,.crisp-client,[data-crisp],iframe[title="Crisp chat bubble"],iframe#crisp-chatbox-container,
.cc-1aa11,.crisp-1rjpbb7{display:none!important;pointer-events:none!important;opacity:0!important;visibility:hidden!important}

.as-cart{position:relative;display:flex;flex-direction:column;align-items:center;gap:7px;min-width:48px}
.as-cart b{position:absolute;top:-4px;left:8px;min-width:16px;height:16px;border-radius:99px;background:var(--as-teal);color:#04333c;font-size:10px;display:flex;align-items:center;justify-content:center;padding:0 4px;font-weight:700}
.as-burger{display:none;width:42px;height:42px;border:0;border-radius:12px;background:#f3f8f9;color:var(--as-navy);font-size:20px;cursor:pointer}
.as-drawer{display:none;position:fixed;inset:0;background:rgba(8,22,32,.45);backdrop-filter:blur(16px);z-index:10060;align-items:center;justify-content:center;flex-direction:column;gap:18px}
.as-drawer.open{display:flex}
.as-drawer a{color:#fff;font-size:20px}
.as-has-mega{position:static}
.as-mega{position:absolute;top:100%;left:0;right:0;padding:8px 3.2vw 22px;opacity:0;pointer-events:none;transform:translateY(8px);transition:opacity .28s,transform .28s;z-index:10051}
.as-mega:before{content:"";position:absolute;top:-12px;left:0;right:0;height:16px}
.as-has-mega:hover .as-mega,.as-has-mega:focus-within .as-mega,.as-mega:hover{opacity:1;pointer-events:auto;transform:none}
.as-mega-hd{grid-column:1/-1;display:flex;align-items:center;justify-content:space-between;padding:4px 6px 12px;border-bottom:1px solid var(--as-line);margin-bottom:4px}.as-mega-hd strong{color:var(--as-navy);font-size:14px;font-weight:700}.as-mega-hd span{font-size:11.5px;color:var(--as-mute)}.as-mega-in{max-width:1140px;margin:0 auto;background:#fff;border:1px solid var(--as-line);border-radius:28px;box-shadow:0 32px 80px rgba(30,75,154,.14);padding:22px;display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.as-mega-in a{min-width:0!important;flex-direction:column;align-items:flex-start;gap:6px;padding:16px 14px;border-radius:16px;background:var(--as-mint);color:var(--as-navy)!important;font-size:13px;font-weight:600;transform:none}
.as-mega-in a svg{width:22px;height:22px;opacity:.85!important;color:var(--as-navy)}
.as-mega-in a small{display:block;font-size:11px;font-weight:400;color:var(--as-mute)}
.as-mega-in a:hover{background:#e7f6f8;transform:translateY(-2px)}
.as-mega-all{grid-column:1/-1;background:transparent!important;border:1px dashed var(--as-line);text-align:center;align-items:center!important;flex-direction:row!important;justify-content:center;color:#6b7c8d!important}
#as-foot{position:relative;margin-top:88px;color:#c9dbe6;background:linear-gradient(165deg,#1a4d72 0%,#123a58 42%,#0c2a44 100%);overflow:hidden;font-family:var(--as-font)}
#as-foot *{box-sizing:border-box}
#as-foot .as-wave{position:absolute;top:-1px;left:0;width:100%;height:78px;color:var(--as-bg)}
#as-foot .as-drop{position:absolute;top:18px;left:50%;transform:translateX(-50%);z-index:3;width:78px;height:78px;border-radius:50%;background:#fbfefe;display:flex;align-items:center;justify-content:center;box-shadow:0 14px 32px rgba(8,30,48,.18)}
#as-foot .as-drop i{width:54px;height:54px;border-radius:50%;background:radial-gradient(circle at 32% 28%,#b9f0f6 0%,#5fc7d4 55%,#2f8ea3 100%);display:flex;align-items:center;justify-content:center;color:#fff}
#as-foot .as-drop svg{width:26px;height:26px}
#as-foot .as-in{position:relative;z-index:2;max-width:1180px;margin:0 auto;padding:108px clamp(16px,3.2vw,48px) 42px;display:grid;grid-template-columns:1.2fr .85fr .85fr 1fr;gap:36px;align-items:start}
#as-foot h3{color:#fff;font-size:20px;font-weight:700;margin:0 0 14px;letter-spacing:.2px}
#as-foot p{margin:0;font-size:13.5px;line-height:2.05;color:#c5d6e2;font-weight:400;max-width:36em}
#as-foot .as-word{width:170px;height:auto;display:block;margin-bottom:14px;opacity:.98}
#as-foot .as-cta{display:inline-flex;align-items:center;gap:8px;margin-top:18px;padding:9px 18px;border-radius:999px;border:1px solid rgba(155,224,232,.45);color:#d7f6fa!important;font-size:12.5px;background:rgba(255,255,255,.04);backdrop-filter:blur(8px);transition:background .25s,transform .25s}
#as-foot .as-cta:hover{background:rgba(118,205,217,.16);transform:translateY(-1px)}
#as-foot .as-links{list-style:none;margin:0;padding:0}
#as-foot .as-links a{display:block;color:#d5e4ee;font-size:13.5px;line-height:2.35;padding:0;transition:color .2s}
#as-foot .as-links a:hover{color:#76cdd9}
#as-foot .as-k{display:block;font-size:11px;color:#8fb0c2;margin-bottom:2px}
#as-foot .as-ph{display:block;color:#fff;font-size:22px;font-weight:600;letter-spacing:.6px;direction:ltr;text-align:right;margin:0 0 8px}
#as-foot .as-ph small{font-size:13px;font-weight:500;color:#b7ccd8;margin-right:8px}
#as-foot .as-addr{font-size:13px;line-height:1.9;color:#c5d6e2;margin-top:10px}
#as-foot .as-soc{display:flex;gap:8px;margin-top:16px}
#as-foot .as-soc a{width:36px;height:36px;border-radius:50%;border:1px solid rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;color:#e7f4f8;transition:background .2s,border-color .2s}
#as-foot .as-soc a:hover{background:rgba(118,205,217,.18);border-color:#76cdd9;color:#fff}
#as-foot .as-soc svg{width:15px;height:15px;fill:currentColor}
#as-foot .as-bar{position:relative;z-index:2;border-top:1px solid rgba(255,255,255,.08);padding:16px 28px 22px;display:flex;justify-content:space-between;align-items:center;max-width:1120px;margin:0 auto;font-size:12px;color:#8aa8b8}
#as-foot .as-bar b{color:#d5eaf0;font-weight:500}
body.woocommerce-shop .elementor-element-504b606d,body.woocommerce-shop .elementor-element-bcb4b4a,body.woocommerce-shop .elementor-element-1f8b42fb,body.tax-product_cat .elementor-element-1f8b42fb,body.woocommerce-shop .uael-woo-categories,body.woocommerce-shop .woolentor-filter-wrap,body.tax-product_cat .woolentor-filter-wrap,body.as-inner ul.products li.product-category{display:none!important}
body.woocommerce-shop .elementor-element-77f32d65,body.tax-product_cat .elementor-element-77f32d65{width:100%!important;max-width:100%!important;flex:0 0 100%!important}
body.woocommerce-shop ul.products,body.tax-product_cat ul.products,body.tax-product_tag ul.products,body.post-type-archive-product ul.products,body.as-inner .wl-filterable-products-content ul.products,body.as-inner ul.products{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;gap:28px 18px!important;align-items:stretch!important;margin:0!important;padding:28px 0 40px!important;list-style:none;float:none!important}
body.as-inner ul.products > li{float:none!important;clear:none!important;width:100%!important;max-width:none!important;margin:0!important}
body.woocommerce-shop .wl-filterable-products-wrap,body.tax-product_cat .wl-filterable-products-wrap{max-width:1180px;margin:12px auto 60px;width:100%}
body.as-inner ul.products li.product,body.as-inner ul.products li.product-category,body.as-inner ul.products li.product.first,body.as-inner ul.products li.product.last{width:100%!important;max-width:none!important;float:none!important;clear:none!important;grid-column:auto!important;margin:0!important;padding:18px 10px 22px!important;background:transparent!important;border:0!important;box-shadow:none!important;text-align:center!important}
body.as-inner ul.products li.product > a,body.as-inner .woocommerce-LoopProduct-link,body.as-inner li.product-category > a{display:flex;flex-direction:column;align-items:center}
body.as-inner ul.products li.product img,body.as-inner .uael-product-cat-inner img,body.as-inner .astra-shop-thumbnail-wrap img{width:100%!important;max-width:100%!important;height:auto!important;min-height:160px;aspect-ratio:1;display:block!important;opacity:1!important;visibility:visible!important;object-fit:contain!important;background:linear-gradient(165deg,#f7fcfd 0%,#e7f4f6 100%)!important;border-radius:32px!important;padding:18px;box-shadow:inset 0 0 0 1px rgba(118,205,217,.22);mix-blend-mode:normal!important;filter:none!important;transition:transform .4s cubic-bezier(.22,1,.36,1),box-shadow .35s}
body.as-inner .astra-shop-thumbnail-wrap,body.as-inner .uael-product-cat-inner{width:auto;height:auto;margin:0 auto 14px;background:transparent;border-radius:0;overflow:visible;display:block}
body.as-inner li.product:hover img,body.as-inner li.product-category:hover img{transform:translateY(-7px);box-shadow:0 18px 34px rgba(30,75,154,.12),inset 0 0 0 1px rgba(118,205,217,.32)}
body.as-inner .woocommerce-loop-product__title,body.as-inner .woocommerce-loop-category__title{display:block!important;font-size:14px!important;font-weight:700!important;color:#1e4b9a!important;line-height:1.7!important;margin:12px 8px 0!important;max-width:none!important}
body.as-inner li.product .price{display:block;margin-top:8px;font-size:13px!important;color:var(--as-navy)!important;font-weight:600}
body.as-inner li.product .star-rating,body.as-inner .ast-on-card-button,body.as-inner .ast-woo-product-category,body.as-inner .uael-count,body.as-inner .ast-shop-toolbar,body.as-inner .added_to_cart{display:none!important}
body.as-inner .woocommerce-pagination{margin:10px 0 50px;text-align:center}
body.as-inner .woocommerce-pagination .page-numbers{border:0!important;background:transparent!important;color:var(--as-mute)!important;min-width:36px;height:36px;line-height:36px;border-radius:99px}
body.as-inner .woocommerce-pagination .page-numbers.current{background:var(--as-mint2)!important;color:var(--as-navy)!important}
.as-crumbs{display:flex;flex-wrap:wrap;align-items:center;justify-content:flex-start;gap:6px;padding:8px clamp(16px,3.2vw,48px) 18px;max-width:1180px;margin:0 auto}
.as-crumbs a,.as-crumbs .now{display:inline-flex;align-items:center;max-width:240px;padding:0;color:#8aa0b3;font-size:12.5px;font-weight:500;background:transparent;border:0;box-shadow:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.as-crumbs a:hover{color:var(--as-navy)}
.as-crumbs .now{color:var(--as-navy);font-weight:700;max-width:420px}
.as-crumbs .sep{width:14px;height:14px;flex:none;opacity:.45;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%238aa0b3' stroke-width='1.6' viewBox='0 0 24 24'%3E%3Cpath d='m15 6-6 6 6 6'/%3E%3C/svg%3E") center/contain no-repeat}
body.single-product .elementor-element-3496b01c{display:none!important}
body.single-product .site-content{padding-bottom:40px}
body.single-product .elementor-element-3ca5dac7,body.single-product .elementor-element-3ca5dac7 > .elementor-widget-wrap,body.single-product .elementor-element-1ef1b460,body.single-product .elementor-element-1ef1b460 .elementor-widget-container,body.single-product .elementor-element-78144be5,body.single-product .elementor-element-78144be5 .elementor-widget-wrap,body.single-product .elementor-element-23eeb16 .elementor-column,body.single-product .elementor-element-23eeb16 .elementor-widget-wrap{background:transparent!important;background-image:none!important;box-shadow:none!important;border:0!important}
body.single-product .product_title,body.single-product .elementor-widget-wl-single-product-title,body.single-product .elementor-element-1ef1b460,body.single-product .elementor-element-1ef1b460 .elementor-widget-container{width:100%!important;max-width:100%!important;display:block!important}
body.single-product .product_title{font-size:clamp(22px,2.4vw,32px)!important;font-weight:700!important;color:var(--as-navy)!important;line-height:1.75!important;margin:0 0 22px!important;background:transparent!important;padding:0!important;border:0!important;white-space:normal!important;word-break:normal!important;overflow-wrap:anywhere;hyphens:none}
body.single-product .elementor-element-69a420b5 .price,body.single-product p.price{font-size:22px!important;color:var(--as-navy)!important;font-weight:700!important;margin:8px 0 14px!important}
body.single-product .elementor-element-23eeb16 > .elementor-container{align-items:center}
body.single-product .woolentor_product_sku_info,body.single-product .woolentor_product_categories_info,body.single-product .star-rating,body.single-product .woocommerce-product-rating,body.single-product .elementor-element-453dbf9f,body.single-product .elementor-element-63068de,body.single-product .elementor-element-179234d7{display:none!important}
body.single-product .woocommerce-product-details__short-description{display:none!important}
body.single-product .elementor-element-78144be5 .whitebox,body.single-product .elementor-element-39c34ea8,body.single-product .elementor-element-247efe26,body.single-product .elementor-element-61cab0fa{background:transparent!important;box-shadow:none!important;border:0!important}
body.single-product .as-well,body.single-product .woocommerce-product-gallery{position:relative;background:linear-gradient(165deg,#f7fcfd 0%,#e8f5f7 100%);border-radius:40px;width:min(480px,92vw)!important;min-height:440px;margin:8px auto 22px!important;display:flex!important;align-items:center;justify-content:center;overflow:visible!important;opacity:1!important;box-shadow:0 22px 54px rgba(30,75,154,.08)}
body.single-product .woocommerce-product-gallery:before{content:"";position:absolute;inset:12% 16%;border-radius:48% 52% 46% 54%/54% 46% 54% 46%;background:radial-gradient(circle at 38% 32%,#fff 0%,rgba(118,205,217,.2) 72%);pointer-events:none;z-index:0}
body.single-product .woocommerce-product-gallery__wrapper,body.single-product .woocommerce-product-gallery__image,body.single-product .woocommerce-product-gallery__image a{width:100%!important;height:auto!important;display:flex!important;align-items:center;justify-content:center;background:transparent!important;overflow:visible!important;position:relative;z-index:1}
body.single-product .woocommerce-product-gallery img{width:78%!important;height:auto!important;max-height:380px!important;object-fit:contain!important;mix-blend-mode:normal!important;filter:drop-shadow(0 26px 34px rgba(30,75,154,.16));background:transparent!important;border-radius:18px!important}
body.single-product .flex-control-thumbs,body.single-product .woocommerce-product-gallery__trigger,body.single-product ol.flex-control-nav{display:none!important}

.as-spec-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:4px 0 14px;flex-wrap:wrap}
.as-spec-head .t{font-size:14px;font-weight:700;color:var(--as-navy)}
.as-spec-head a{font-size:12px;font-weight:700;color:var(--as-teal);display:inline-flex;align-items:center;gap:6px}
.as-spec-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 48px;margin:8px 0 28px}
.as-spec-grid ul{list-style:none;margin:0;padding:0}
.as-spec-grid li{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:baseline;gap:14px;padding:11px 0;border-bottom:1px dotted #d7e3ea;font-size:13.5px}
.as-spec-grid .k{color:#7f93a6;font-weight:500;white-space:normal}
.as-spec-grid .v{color:var(--as-navy);font-weight:700;text-align:left;direction:ltr}
.as-pd-rate{display:inline-flex;align-items:center;gap:8px;background:#eefbf3;border:1px solid #d5f0de;border-radius:14px;padding:6px 12px;margin-bottom:12px;font-size:12px;color:#2f7a4c;font-weight:700}
.as-pd-rate b{font-size:16px;color:#1f9d55}
.as-buy .as-gallery-btn{display:flex;align-items:center;justify-content:center;width:100%;max-width:none;margin:0;min-height:44px;padding:10px 16px;box-sizing:border-box;border-radius:999px;border:1px solid var(--as-teal);background:var(--as-mint);color:var(--as-navy);font-family:inherit;font-size:13px;font-weight:700;line-height:1.6;white-space:nowrap;word-break:keep-all;box-shadow:none;cursor:pointer}
.as-trust-strip{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin:8px 0 26px}
.as-trust-strip article{background:var(--as-mint);border-radius:16px;padding:12px 8px;text-align:center;min-height:88px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px}
.as-trust-strip b{font-size:22px;line-height:1;color:var(--as-navy)}
.as-trust-strip small{font-size:11px;color:#7f93a6;line-height:1.5;font-weight:600}
body.single-product .elementor-element-23eeb16 > .elementor-container{display:grid!important;grid-template-columns:minmax(360px,.95fr) minmax(480px,1.35fr)!important;gap:28px 48px!important;align-items:start!important;max-width:1240px;margin:0 auto;padding:8px 28px 12px!important;flex-direction:unset!important;flex-wrap:unset!important}
body.single-product .elementor-element-78144be5{grid-column:1;grid-row:1;width:auto!important;max-width:none!important}
body.single-product .elementor-element-3ca5dac7{grid-column:2;grid-row:1;width:auto!important;max-width:none!important}
body.single-product .elementor-element-69a420b5{display:none!important}
body.single-product .elementor-element-23eeb16{width:100%!important;max-width:100%!important}
body.single-product .elementor-element-23eeb16 > .elementor-container{width:100%!important;max-width:1180px!important}
body.single-product .elementor-element-23eeb16 .elementor-column.elementor-col-50{width:100%!important;max-width:100%!important;flex:unset!important}
body.single-product .elementor-element-3ca5dac7 .elementor-widget-wrap,body.single-product .elementor-element-78144be5 .elementor-widget-wrap{width:100%!important;max-width:100%!important}
body.single-product .elementor-inner-section,body.single-product .elementor-element-1bf24a9a,body.single-product .elementor-element-1bf24a9a .elementor-container,body.single-product .elementor-element-18716a1c{width:100%!important;max-width:100%!important;display:block!important}
body.single-product .elementor-element-23eeb16 .elementor-inner-section .elementor-container{max-width:none!important;padding:0!important}
.as-specs,.as-spec-head,.as-spec-grid{width:100%!important;max-width:100%!important;min-width:0!important}
.as-hi{display:none!important}
body.single-product .elementor-element-43148cdf{padding:12px clamp(16px,3.2vw,48px) 56px!important;max-width:1280px;margin:0 auto!important;width:100%!important;position:relative}
body.single-product .elementor-element-43148cdf .elementor-heading-title{color:var(--as-navy)!important;font-size:18px!important;font-weight:700!important;margin:0 0 18px!important}
body.single-product .elementor-element-43148cdf .uael-woocommerce,body.single-product .elementor-element-43148cdf .uael-woo-products-inner,body.single-product .elementor-element-43148cdf .uael-woo-products-grid{width:100%!important;max-width:100%!important;overflow:visible!important}
body.single-product .elementor-element-43148cdf ul.products{display:flex!important;flex-wrap:nowrap!important;gap:18px!important;width:auto!important;max-width:none!important;margin:0!important;padding:6px 2px 18px!important;overflow-x:auto!important;overflow-y:hidden!important;scroll-snap-type:x mandatory;scroll-behavior:smooth;-webkit-overflow-scrolling:touch;scrollbar-width:thin;scrollbar-color:#c5dbe6 transparent}
body.single-product .elementor-element-43148cdf ul.products::-webkit-scrollbar{height:6px}
body.single-product .elementor-element-43148cdf ul.products::-webkit-scrollbar-thumb{background:#c5dbe6;border-radius:99px}
body.single-product .elementor-element-43148cdf ul.products>li.product{flex:0 0 min(240px,72vw)!important;width:min(240px,72vw)!important;max-width:min(240px,72vw)!important;float:none!important;margin:0!important;padding:0!important;scroll-snap-align:start;position:relative!important;left:auto!important;top:auto!important;transform:none!important}
body.single-product .elementor-element-43148cdf .uael-woo-product-wrapper{text-align:center;background:#fff;border:1px solid var(--as-line);border-radius:24px;padding:14px 12px 16px;height:100%;transition:box-shadow .2s,transform .2s}
body.single-product .elementor-element-43148cdf .uael-woo-product-wrapper:hover{box-shadow:0 16px 36px rgba(30,75,154,.1);transform:translateY(-2px)}
body.single-product .elementor-element-43148cdf .woocommerce-loop-product__title{font-size:13px!important;line-height:1.7!important;color:var(--as-navy)!important;margin:8px 0 4px!important}
body.single-product .elementor-element-43148cdf .uael-woo-product-category{display:block;font-size:11px;color:var(--as-mute);margin-top:8px}
body.single-product .elementor-element-43148cdf .price{font-size:13px!important;color:var(--as-navy)!important;font-weight:700!important}
body.single-product .elementor-element-43148cdf img{border-radius:20px!important;background:linear-gradient(165deg,#f7fcfd,#e7f4f6)!important;padding:12px!important;object-fit:contain!important;width:100%!important;height:180px!important}
body.single-product .elementor-element-43148cdf .uael-product-actions,.as-rel-nav{display:none}
.as-rel-wrap{position:relative}
.as-rel-nav{display:block!important;position:absolute;top:42%;left:0;right:0;pointer-events:none;z-index:3}
.as-rel-nav button{pointer-events:auto;position:absolute;width:40px;height:40px;border:1px solid var(--as-line);border-radius:50%;background:#fff;color:var(--as-navy);box-shadow:0 8px 20px rgba(30,75,154,.12);cursor:pointer;font-size:18px;line-height:1}
.as-rel-nav .as-rel-next{left:-6px}
.as-rel-nav .as-rel-prev{right:-6px}
.as-rel-nav button:hover{background:var(--as-navy);color:#fff}
body.single-product .elementor-element-c3e0e74{display:none!important}
body.single-product .elementor-element-4a79ee0a,body.single-product .elementor-element-34fb1f31 .whitebox{max-width:1180px;margin:0 auto;padding:0 20px 32px!important}

#as-spec-table{max-width:1280px;width:100%;margin:36px auto 20px;padding:8px clamp(16px,3.2vw,48px) 28px;box-sizing:border-box}
#as-spec-table h3{font-size:18px;color:var(--as-navy);margin:0 0 18px;font-weight:700}
#as-spec-table .as-specs{margin:0;max-width:100%}
.as-specs{margin:6px 0 22px}
.as-specs .row{display:grid;grid-template-columns:minmax(120px,max-content) 1fr minmax(80px,max-content);align-items:baseline;gap:12px;padding:10px 0;font-size:13.5px;border-bottom:1px solid #eef3f6}
.as-specs .k{color:#7f93a6;font-weight:500;white-space:nowrap}
.as-specs .dots{border-bottom:1px dotted #cfdbe3;height:10px;min-width:24px}
.as-specs .v{color:var(--as-navy);font-weight:600;white-space:normal;max-width:none;text-align:left;direction:ltr}
.as-hi{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:8px 0 22px}
.as-hi article{background:var(--as-mint);border-radius:22px;padding:22px 16px;text-align:center}
.as-hi small{display:block;color:#8aa0b3;font-size:12px;margin-bottom:6px}
.as-hi b{display:block;color:var(--as-navy);font-size:18px;font-weight:700}
.as-stages{max-width:720px;margin:48px auto;text-align:center}
.as-stages .mark{width:56px;height:56px;margin:0 auto 10px;border-radius:50%;background:var(--as-navy);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700}
.as-stages h3{color:var(--as-navy);font-size:20px;margin:0 0 28px}
.as-fil{position:relative;padding:0 12px 36px}
.as-fil:before{content:"";position:absolute;top:0;bottom:0;right:50%;width:2px;background:#e4eef2;transform:translateX(50%)}
.as-fil i{position:relative;z-index:1;display:inline-block;width:34px;height:22px;background:#fff;color:#b7c6d2;font-style:normal;letter-spacing:2px}
.as-fil h4{position:relative;z-index:1;color:var(--as-navy);font-size:18px;margin:8px 0 10px}
.as-fil p{position:relative;z-index:1;color:#7d90a2;font-size:13.5px;line-height:2;max-width:640px;margin:0 auto}
.as-buy{direction:rtl;display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:16px;width:min(100%,420px);max-width:420px;margin:16px auto 0;padding:22px 22px 18px;box-sizing:border-box;background:#fff;border:1px solid var(--as-line);border-radius:28px;box-shadow:0 16px 40px rgba(30,75,154,.08);text-align:right}
.as-buy .as-purchase__label{display:block;margin:0;color:var(--as-mute);font-size:12px;font-weight:600;line-height:1.4;white-space:nowrap}
.as-buy .as-purchase__amount{display:inline-flex;align-items:baseline;gap:8px;margin:4px 0 0;color:var(--as-navy);white-space:nowrap;line-height:1.15}
.as-buy .as-purchase__num{font-size:30px;font-weight:800;letter-spacing:0}
.as-buy .as-purchase__currency{font-size:15px;font-weight:700;line-height:1}
.as-buy .as-purchase__updated{display:flex;flex-wrap:nowrap;align-items:baseline;gap:.4em;margin:6px 0 0;color:#5d7082;font-size:13px;font-weight:600;line-height:1.7;white-space:nowrap}
.as-buy .as-purchase__updated-label,.as-buy .as-purchase__updated time{white-space:nowrap;word-break:keep-all;overflow-wrap:normal}
.as-buy .as-purchase__actions{display:flex;flex-direction:column;align-items:stretch;gap:10px;width:100%}
.as-buy form.cart,.as-buy .wl-cart-wrap,.as-buy .wl-addto-cart{display:block;width:100%;max-width:none;margin:0;padding:0;float:none;height:auto}
.as-buy .single_add_to_cart_button{width:100%!important;max-width:none!important;justify-content:center!important;white-space:nowrap!important;word-break:keep-all;box-sizing:border-box}
body.single-product .wl-quantity-wrap{display:none!important}
body.single-product .single_add_to_cart_button,body.single-product .wl-addto-cart .button{background:var(--as-navy)!important;color:#fff!important;border:0!important;border-radius:999px!important;height:48px!important;padding:0 22px!important;font-size:14px!important;font-weight:600!important;box-shadow:none!important;display:inline-flex!important;align-items:center;gap:8px}
body.single-product .single_add_to_cart_button:after{content:"";width:18px;height:18px;display:inline-block;background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' stroke='%23fff' stroke-width='2' viewBox='0 0 24 24'><circle cx='9' cy='20' r='1.4'/><circle cx='18' cy='20' r='1.4'/><path d='M3 4h2l2.4 11h9.2L21 7H7'/></svg>") center/contain no-repeat}
body.single-product .elementor-element-729d4d2c{margin-top:28px}
body.single-product .elementor-element-729d4d2c .elementor-icon-box-wrapper{background:var(--as-mint);border-radius:18px;padding:14px 12px;gap:10px}
body.single-product .elementor-icon-box-icon .elementor-icon{background:#fff!important;border-radius:16px!important;width:48px;height:48px;display:flex;align-items:center;justify-content:center;box-shadow:none!important}
body.single-product .elementor-icon-box-icon svg{width:26px;height:26px}
body.single-product .elementor-icon-box-icon svg path{fill:var(--as-navy)!important}
body.single-product .elementor-icon-box-title{font-size:13px!important;color:var(--as-navy)!important;font-weight:600!important}
body.single-product .elementor-icon-box-description{font-size:12px!important;color:var(--as-mute)!important}
body.single-product .woocommerce-tabs-list,body.single-product .elementor-element-56da84a8,body.single-product .elementor-element-2e0cb4cd{display:none!important}
body.single-product .elementor-element-c3e0e74{display:none!important}
body.single-product .elementor-element-c3e0e74 .elementor-heading-title{color:var(--as-navy)!important;font-size:22px!important;font-weight:700!important;text-align:right;margin-bottom:18px!important}
body.single-product .elementor-element-c3e0e74 li.product img{background:linear-gradient(165deg,#f7fcfd,#e7f4f6)!important;border-radius:28px!important;padding:16px!important;object-fit:contain!important}
body.single-product .whitebox{background:transparent!important;box-shadow:none!important}
body.woocommerce-cart .entry-title,body.woocommerce-checkout .entry-title,body.woocommerce-account .entry-title{display:none!important}
body.woocommerce-cart .site-content,body.woocommerce-checkout .site-content{padding:20px 0 80px}
.wp-block-woocommerce-cart,.wp-block-woocommerce-checkout,.wc-block-cart,.wc-block-checkout{max-width:980px!important;margin:0 auto!important;font-family:var(--as-font)!important}
.wc-block-cart__empty-cart__title,.wp-block-woocommerce-empty-cart-block h2{font-size:20px!important;color:var(--as-navy)!important;font-weight:600!important}
.wp-block-woocommerce-empty-cart-block{padding:48px 16px!important}
.wp-block-separator,.wc-block-cart .with-empty-cart-icon:before{display:none!important}
.wc-block-grid__products{display:grid!important;grid-template-columns:repeat(4,1fr);gap:22px}
.wc-block-grid__product{background:transparent!important;border:0!important;text-align:center;padding:18px 8px}
.wc-block-grid__product-image{width:160px;height:160px;margin:0 auto 12px;background:linear-gradient(165deg,#f7fcfd,#e7f4f6);border-radius:28px;display:flex;align-items:center;justify-content:center;overflow:visible}
.wc-block-grid__product-image img{width:72%!important;mix-blend-mode:normal!important;object-fit:contain}
.wc-block-grid__product-title{font-size:13px!important;color:#5d7082!important}
.wc-block-grid__product-add-to-cart .wp-block-button__link,.wc-block-grid__product-price{display:none!important}
.wc-block-cart-items,.wc-block-cart-items__header,.wc-block-cart-items__row{border-color:var(--as-line)!important;background:transparent!important;box-shadow:none!important}
table.wc-block-cart-items{border:1px solid var(--as-line)!important;border-radius:16px;overflow:hidden;border-collapse:separate!important}
.wc-block-cart-item__wrap{padding:18px 12px!important}
.wc-block-cart-item__image img{border-radius:14px;background:var(--as-mint);padding:8px}
.wc-block-components-product-name{color:var(--as-navy)!important;font-weight:600!important;font-size:14px!important}
.wc-block-components-product-price,.wc-block-cart-item__total{color:var(--as-ink)!important;font-size:13px!important}
.wc-block-cart-item__remove-link{color:#c9d4dc!important}
.wc-block-cart__submit-button,.wc-block-components-checkout-place-order-button,.wc-block-components-button:not(.is-link){background:var(--as-navy)!important;color:#fff!important;border:0!important;border-radius:14px!important;height:52px!important;font-size:15px!important;font-weight:600!important;box-shadow:none!important;width:100%}
.wc-block-components-notice-banner.is-success,.wc-block-components-notice-banner{background:#eefbf3!important;border:0!important;border-radius:14px!important;color:#3d6b55!important;box-shadow:none!important}
.wc-block-cart .wc-block-cart__sidebar,.wp-block-woocommerce-cart-totals-block{background:#fff;border:1px solid var(--as-line);border-radius:18px;padding:18px!important;box-shadow:none!important}
.wc-block-components-totals-wrapper{border-color:var(--as-line)!important}
.wc-block-components-text-input input,.wc-block-components-text-input textarea,.wc-block-components-select,.wc-block-components-combobox input,body.as-inner input[type=text],body.as-inner input[type=email],body.as-inner input[type=tel],body.as-inner input[type=password],body.as-inner textarea,body.as-inner select,.woocommerce form .form-row input.input-text,.woocommerce form .form-row textarea,.select2-container .select2-selection--single{background:#fff!important;border:1px solid var(--as-line)!important;border-radius:12px!important;min-height:48px!important;box-shadow:none!important;color:var(--as-ink)!important;font-size:14px!important}
.wc-block-components-text-input label,.woocommerce form .form-row label{color:var(--as-mute)!important;font-size:12px!important}
.wc-block-components-radio-control__option,.wc-block-components-radio-control-accordion-option{border:1px solid var(--as-line)!important;border-radius:14px!important;background:#fff!important;margin-bottom:8px}
#payment,.wc-block-components-checkout-step,#customer_details,.woocommerce-checkout #order_review{background:#fff!important;border:1px solid var(--as-line)!important;border-radius:18px!important;padding:18px!important;box-shadow:none!important}
#place_order,.woocommerce #payment #place_order,body.as-inner button[name=woocommerce_checkout_place_order]{background:var(--as-navy)!important;color:#fff!important;border-radius:14px!important;height:52px!important;font-weight:600!important;border:0!important}
.woocommerce-checkout-review-order-table,.shop_table{border:1px solid var(--as-line)!important;border-radius:14px!important}
body.as-inner .woocommerce-info,body.as-inner .woocommerce-message,body.as-inner .woocommerce-error{background:#eef8f9!important;border:0!important;border-radius:14px!important;color:var(--as-ink)!important;box-shadow:none!important}
body.as-inner .woocommerce-info:before,body.as-inner .woocommerce-message:before{display:none!important}
.wc-block-components-payment-method-icons,.wp-block-woocommerce-cart-accepted-payment-methods-block,.wp-block-woocommerce-cart-express-payment-block,.wp-block-woocommerce-checkout-express-payment-block,.wp-block-woocommerce-cart-cross-sells-block{display:none!important}
body.woocommerce-account .woocommerce{max-width:920px;margin:20px auto 60px;background:#fff;border:1px solid var(--as-line);border-radius:20px;padding:22px}
body.woocommerce-account .woocommerce-MyAccount-navigation ul{display:flex;flex-wrap:wrap;gap:8px;list-style:none;padding:0;margin:0 0 18px;border:0}
body.woocommerce-account .woocommerce-MyAccount-navigation li{border:0!important}
body.woocommerce-account .woocommerce-MyAccount-navigation a{display:block;padding:8px 14px;border-radius:999px;background:var(--as-mint);color:var(--as-ink);font-size:13px}
body.woocommerce-account .woocommerce-MyAccount-navigation .is-active a{background:var(--as-navy);color:#fff}
body.as-inner .button,body.as-inner .woocommerce a.button,body.as-inner .woocommerce button.button{background:var(--as-navy)!important;color:#fff!important;border:0!important;border-radius:12px!important;box-shadow:none!important}
body.as-inner a.button.alt,body.as-inner .checkout-button{background:var(--as-navy)!important}
.as-hide{display:none!important}
.as-page-head{max-width:1180px;margin:8px auto 8px;padding:8px 24px 4px;text-align:center}
.as-page-head p{margin:0;font-size:12px;letter-spacing:.18em;color:var(--as-mute);font-weight:500}
.as-page-head h1{margin:6px 0 0;font-size:26px;font-weight:700;color:var(--as-navy)}
.as-cat-prose{max-width:1280px;margin:48px auto 8px;padding:28px clamp(16px,3.2vw,48px) 36px;color:#536273;font-size:15.5px;line-height:2.05;text-align:right}
.as-cat-prose h2{font-size:28px;color:var(--as-navy);font-weight:800;margin:8px 0 16px;line-height:1.7;text-align:right}
.as-cat-prose h3{font-size:20px;color:var(--as-navy);font-weight:700;margin:28px 0 10px;line-height:1.65;text-align:right}
.as-cat-prose h4{font-size:16px;color:var(--as-navy);font-weight:600;margin:18px 0 8px;text-align:right}
.as-cat-prose p{margin:0 0 12px;max-width:none}
.as-cat-prose ul,.as-cat-prose ol{margin:0 0 14px 1.4em;padding:0}
.as-cat-prose li{margin:0 0 6px}
.as-cat-prose a{color:var(--as-navy);text-decoration:underline}
.as-cat-prose img{border-radius:16px;max-width:100%;height:auto}

.as-page-head--cat{display:flex;align-items:center;gap:14px}
.as-page-head--cat .as-cat-feat{width:72px;height:72px;object-fit:contain;background:#eefbfc;border:1px solid var(--as-line);border-radius:18px;padding:8px;flex:none}
body.tax-product_cat ul.products,body.woocommerce-shop ul.products{overflow:visible!important;height:auto!important}
body.tax-product_cat ul.products > li.product,body.woocommerce-shop ul.products > li.product{overflow:visible!important;height:auto!important}

.as-cats{display:flex;flex-wrap:nowrap;gap:8px;overflow-x:auto;max-width:1180px;margin:4px auto 8px;padding:4px 24px 14px;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.as-cats::-webkit-scrollbar{display:none}
.as-cats a{flex:none;display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:#fff;border:1px solid var(--as-line);color:#5d7082;font-size:12.5px;font-weight:500;white-space:nowrap;transition:border-color .2s,color .2s,transform .2s;line-height:1.5}
.as-cats a svg{width:16px;height:16px;flex:none}
.as-cats a small{font-weight:400;opacity:.8;padding-right:2px}
.as-cats a small::before{content:"· ";opacity:.7}
.as-cats a:hover{border-color:var(--as-teal);color:var(--as-navy);transform:translateY(-1px)}
body.blog .elementor-element-f9c6107,body.blog .elementor-element-bcb4b4a{display:none!important}
body.blog .elementor-element-b0a5337,body.blog .elementor-element-e52a622 .elementor-container{width:100%!important;max-width:1100px;margin:0 auto}
body.blog .elementor-element-e52a622{padding:8px 18px 40px}
body.as-inner .uael-post-grid__inner{display:grid!important;grid-template-columns:repeat(auto-fit,minmax(260px,340px));justify-content:center;gap:22px!important;margin:0 auto!important;max-width:1180px}
body.as-inner .uael-post-wrapper{width:auto!important;float:none!important;margin:0!important;padding:0!important}
body.as-inner .uael-post__inner-wrap,body.as-inner .uael-post__bg-wrap{background:#fff!important;border:1px solid var(--as-line)!important;border-radius:22px!important;overflow:hidden;box-shadow:none!important;height:100%;display:flex;flex-direction:column}
body.as-inner .uael-post__thumbnail{margin:0!important}
body.as-inner .uael-post__thumbnail a,body.as-inner .uael-post__thumbnail img{display:block;width:100%!important;height:220px!important;object-fit:cover!important;border-radius:0!important}
body.as-inner .uael-post__content-wrap{padding:16px 18px 20px!important;display:flex;flex-direction:column;gap:10px;flex:1}
body.as-inner .uael-post__title,body.as-inner .uael-post__title a{font-size:16px!important;line-height:1.75!important;font-weight:650!important;color:var(--as-navy)!important;margin:0!important}
body.as-inner .uael-post__meta-data,body.as-inner .uael-post__date{color:var(--as-mute)!important;font-size:12px!important}
body.as-inner .uael-post__separator{display:none!important}
body.as-inner .uael-post__read-more,body.as-inner .uael-post__read-more.elementor-button,body.as-inner a.elementor-button{
margin-top:auto!important;align-self:flex-start;display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:6px!important;
background:#fff!important;color:var(--as-navy)!important;border:1px solid var(--as-teal)!important;border-radius:999px!important;
height:auto!important;min-height:42px!important;padding:8px 18px!important;box-shadow:none!important;font-size:13px!important;font-weight:700!important;
line-height:1.7!important;white-space:nowrap!important;letter-spacing:0!important}
body.as-inner .uael-post__read-more .elementor-button-content-wrapper,body.as-inner a.elementor-button .elementor-button-content-wrapper{
display:inline-flex!important;align-items:center!important;gap:6px!important;flex-direction:row!important}
body.as-inner .uael-post__read-more .elementor-button-icon:empty,body.as-inner .uael-post__read-more .elementor-button-icon:has(:only-child:empty){display:none!important}
body.as-inner .uael-post__read-more .elementor-button-text{display:inline!important;color:inherit!important;line-height:1.7!important}
body.single-post #content,body.single-post .site-main{max-width:760px;margin:0 auto;padding:8px 20px 72px}
body.single-post .page-header{margin:8px 0 22px;text-align:right}
body.single-post .entry-title{display:block!important;font-size:30px!important;line-height:1.65!important;font-weight:800!important;color:var(--as-navy)!important;margin:0}
body.single-post .page-content{font-size:16.5px;line-height:2.05;color:#536273}
body.single-post .page-content h2,body.single-post .page-content h3{color:var(--as-navy);font-weight:700;line-height:1.7;margin:28px 0 10px}
body.single-post .page-content h2{font-size:22px}
body.single-post .page-content h3{font-size:18px}
body.single-post .page-content img,body.single-post .wp-block-image img{border-radius:18px;max-width:100%;height:auto}
body.single-post .wp-block-separator{border:0;height:1px;background:var(--as-line);margin:28px 0}
body.single-post .wp-block-image figcaption{color:var(--as-mute);font-size:12px;text-align:center}
@media (max-width:1100px){
body.as-inner ul.products{grid-template-columns:repeat(3,1fr)!important}
body.as-inner .uael-post-grid__inner{grid-template-columns:repeat(2,minmax(0,1fr))!important}
}
@media (max-width:980px){
body.single-product .elementor-element-43148cdf ul.products>li.product{flex-basis:min(220px,70vw)!important}
.as-spec-grid{grid-template-columns:1fr}
.as-trust-strip{grid-template-columns:repeat(3,minmax(0,1fr))}
body.single-product .elementor-element-23eeb16 > .elementor-container{grid-template-columns:1fr!important}
body.single-product .elementor-element-78144be5,body.single-product .elementor-element-3ca5dac7{grid-column:1}
.as-nav-r,.as-nav-l li:not(.as-li-cart),.as-mega{display:none!important}
.as-burger{display:block;justify-self:start}
.as-head-main{grid-template-columns:42px 1fr auto;align-items:center;padding:10px 4vw 12px}
.as-head-cta{display:inline-flex!important;padding:8px 10px;font-size:11px;margin-inline-start:4px}.as-head-cta-ghost{background:#eefbfc!important;color:var(--as-navy)!important;box-shadow:none;border:1px solid var(--as-teal)}
.as-logo{display:block;width:132px;margin:0 auto}
.as-nav-l{display:block;justify-self:end}
.as-nav-l ul{display:flex;justify-content:flex-end}
body.as-inner{padding-top:96px!important}
body.as-inner ul.products,.wc-block-grid__products{grid-template-columns:repeat(2,1fr)!important;gap:16px 10px!important}
body.as-inner ul.products li.product{padding:8px 4px 16px!important}
.as-hi{grid-template-columns:1fr}
body.single-product .product_title{font-size:22px!important}
body.single-product .woocommerce-product-gallery,body.single-product .as-well{min-height:280px;border-radius:28px;width:min(92vw,380px)!important}
body.single-product .elementor-element-3ca5dac7,body.single-product .elementor-element-78144be5{width:100%!important;max-width:100%!important}
body.single-product .elementor-element-23eeb16 > .elementor-container{display:flex!important;flex-direction:column!important;flex-wrap:nowrap!important;grid-template-columns:unset!important}
.as-buy{width:100%;max-width:none;border-radius:24px;flex-direction:column;align-items:stretch;flex-wrap:nowrap;justify-content:flex-start;padding:18px 16px 16px}.as-buy .as-purchase__num{font-size:26px}.as-buy .as-purchase__updated{font-size:12.5px}
.as-specs .row{grid-template-columns:1fr;gap:2px 0;padding:10px 0;border-bottom:1px solid var(--as-line)}
.as-specs .dots{display:none}
.as-specs .v{max-width:none;white-space:normal}
.as-crumbs{padding:8px 12px 16px;justify-content:flex-start}
.as-page-head h1{font-size:22px}
body.single-post .entry-title{font-size:24px!important}
body.single-post .page-content{font-size:15.5px}
#as-foot .as-in{grid-template-columns:1fr;gap:28px;padding:96px 22px 28px;text-align:center}
#as-foot .as-word,#as-foot .as-cta,#as-foot .as-soc{margin-left:auto;margin-right:auto}
#as-foot .as-ph{text-align:center}
#as-foot .as-bar{flex-direction:column;gap:6px;text-align:center}
.as-mega-in{grid-template-columns:repeat(2,1fr)}
}
@media (max-width:640px){
body.single-product .elementor-element-43148cdf ul.products>li.product{flex-basis:min(200px,78vw)!important}

body.as-inner ul.products{grid-template-columns:repeat(2,1fr)!important;gap:12px 8px!important}
body.as-inner ul.products li.product img{padding:12px;border-radius:22px!important}
body.as-inner .uael-post-grid__inner{grid-template-columns:1fr!important}
body.as-inner .uael-post__thumbnail img{height:200px!important}
.as-crumbs a,.as-crumbs .now{max-width:46vw;font-size:11.5px;padding:6px 12px}
.as-page-head{padding:8px 16px}
.as-cats{padding:4px 16px 12px}
}
body.woocommerce-shop .elementor-element-1ff3c165 > .elementor-container,body.tax-product_cat .elementor-element-1ff3c165 > .elementor-container{display:block!important}
body.woocommerce-shop .elementor-element-1f8b42fb,body.tax-product_cat .elementor-element-1f8b42fb{display:none!important}
body.woocommerce-shop .elementor-element-77f32d65,body.tax-product_cat .elementor-element-77f32d65{display:block!important;width:100%!important;max-width:1180px!important;flex:none!important;float:none!important;margin:0 auto!important}
body.as-inner ul.products,body.as-inner ul.products.columns-4,.woocommerce body.as-inner ul.products,body.woocommerce-shop ul.products,body.tax-product_cat ul.products,body.as-inner .wl-filterable-products-content ul.products{display:grid!important;grid-template-columns:repeat(3,minmax(0,1fr))!important;float:none!important;width:100%!important;max-width:100%!important;font-size:13.5px!important;overflow:visible!important;height:auto!important}
body.as-inner ul.products > li,body.as-inner .wl-filterable-products-content ul.products > li.product,body.as-inner ul.products.columns-4 > li.product{display:block!important;float:none!important;width:auto!important;max-width:100%!important;min-width:0!important;position:static!important;left:auto!important;top:auto!important;transform:none!important;box-sizing:border-box!important;font-size:13.5px!important}
@media (max-width:1100px){body.as-inner ul.products,body.as-inner ul.products.columns-4,body.as-inner .wl-filterable-products-content ul.products{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media (max-width:980px){body.as-inner ul.products,body.as-inner ul.products.columns-4,body.as-inner .wl-filterable-products-content ul.products{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
@media (max-width:480px){body.as-inner ul.products,body.as-inner ul.products.columns-4,body.as-inner .wl-filterable-products-content ul.products{grid-template-columns:1fr!important}}


body.woocommerce-shop ul.products > li.product,
body.tax-product_cat ul.products > li.product,
body.as-inner .wl-filterable-products-content ul.products > li.product{
  width:100%!important;max-width:none!important;flex:none!important;float:none!important;min-width:0!important
}
body.woocommerce-shop ul.products .price,
body.tax-product_cat ul.products .price{
  display:flex!important;flex-wrap:wrap;justify-content:center;align-items:baseline;gap:6px;text-align:center
}
@media (max-width:900px){
  body.woocommerce-shop ul.products,body.tax-product_cat ul.products,body.as-inner .wl-filterable-products-content ul.products{
    grid-template-columns:repeat(2,minmax(0,1fr))!important
  }
}
@media (max-width:560px){
  body.woocommerce-shop ul.products,body.tax-product_cat ul.products,body.as-inner .wl-filterable-products-content ul.products{
    grid-template-columns:1fr!important
  }
}
/* --- mobile responsive fixes v21 --- */
html.as-inner-scroll,body.as-inner{max-width:100%;overflow-x:hidden!important}
body.as-inner .elementor-section,body.as-inner .site-content,body.as-inner #content{width:100%!important;max-width:100%!important}
body.as-inner .elementor-container,body.as-inner .ast-container{width:100%!important;max-width:1240px!important;padding-left:16px!important;padding-right:16px!important;box-sizing:border-box!important}
body.single-product .elementor-inner-section > .elementor-container{max-width:none!important}
body.as-inner .elementor-column,body.as-inner .elementor-widget-wrap{min-width:0!important}
body.as-inner img,body.as-inner video,body.as-inner iframe{max-width:100%!important;height:auto}
body.as-inner .bnav_bottom_nav_wrapper,body.as-inner .bnav_main_menu_container{display:none!important}
body.as-inner .wl-filterable-products-wrap,body.as-inner .wl-filterable-products-content{width:100%!important;max-width:100%!important;overflow:visible!important}
@media (max-width:980px){
.as-topbar{flex-wrap:wrap;justify-content:center;text-align:center;line-height:1.6;padding:6px 12px}
body.as-inner{padding-top:132px!important;padding-bottom:24px!important}
.as-head-main{grid-template-columns:44px minmax(0,1fr) auto;gap:6px;padding:10px 12px 12px}
.as-logo{width:min(132px,42vw)!important;max-width:100%}
.as-nav-l ul{gap:4px}
.as-nav-l li:not(.as-li-cart){display:none!important}
.as-cart b{left:4px}
.as-burger{justify-self:start}
.as-page-head{padding:10px 16px 6px}
.as-page-head h1{font-size:20px!important;line-height:1.5}
.as-cats{margin-left:0;margin-right:0;padding:4px 12px 12px}
body.single-product .elementor-element-23eeb16 > .elementor-container{gap:12px!important}
body.single-product .elementor-element-c3e0e74{padding-left:12px!important;padding-right:12px!important}
body.single-product .elementor-element-c3e0e74 .slick-slider{margin:0 -4px}
}
@media (max-width:480px){
body.as-inner{padding-top:124px!important}
.as-topbar span{display:block;width:100%}
.as-logo{width:min(118px,46vw)!important}
.as-crumbs{padding:6px 12px 12px}
.as-crumbs a,.as-crumbs .now{max-width:38vw;font-size:11px}
}


body.woocommerce-shop .wl-filterable-products-content ul.products > li.product.first,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product.first,
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product.last,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product.last,
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product.product-category,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product.product-category{
  clear:none!important;float:none!important;width:100%!important;max-width:none!important;grid-column:auto!important
}
@media(max-width:1100px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{grid-template-columns:repeat(3,minmax(0,1fr))!important}
}
@media(max-width:760px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{grid-template-columns:repeat(2,minmax(0,1fr))!important}
}
@media(max-width:560px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{grid-template-columns:minmax(0,1fr)!important}
}

/* archive grid lock */
body.woocommerce-shop .elementor-widget-uael-woo-categories,
body.tax-product_cat .elementor-widget-uael-woo-categories,
body.woocommerce-shop .uael-woo-category-slider,
body.tax-product_cat .uael-woo-category-slider,
body.woocommerce-shop .slick-arrow,
body.tax-product_cat .slick-arrow,
body.woocommerce-shop ul.products li.product-category,
body.tax-product_cat ul.products li.product-category{
  display:none!important;height:0!important;width:0!important;overflow:hidden!important;padding:0!important;margin:0!important;border:0!important;opacity:0!important;pointer-events:none!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products,
body.tax-product_cat .wl-filterable-products-content ul.products,
body.post-type-archive-product .wl-filterable-products-content ul.products{
  display:grid!important;
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
  grid-auto-flow:row!important;
  justify-content:start!important;
  align-items:stretch!important;
  gap:28px 20px!important;
  width:100%!important;
  max-width:1180px!important;
  margin:0 auto 48px!important;
  padding:12px 16px 40px!important;
  overflow:visible!important;
  height:auto!important;
  float:none!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product{
  display:flex!important;
  flex-direction:column!important;
  float:none!important;
  clear:none!important;
  width:100%!important;
  max-width:none!important;
  min-width:0!important;
  margin:0!important;
  padding:8px 6px 18px!important;
  position:static!important;
  left:auto!important;
  top:auto!important;
  transform:none!important;
  flex:none!important;
  grid-column:auto!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product img,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product img{
  width:100%!important;
  max-width:100%!important;
  height:220px!important;
  object-fit:contain!important;
  display:block!important
}
@media(max-width:900px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{
    grid-template-columns:repeat(2,minmax(0,1fr))!important
  }
}
@media(max-width:560px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{
    grid-template-columns:minmax(0,1fr)!important
  }
}


body.woocommerce-shop .wl-filterable-products-content ul.products,
body.tax-product_cat .wl-filterable-products-content ul.products{
  display:grid!important;
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
  grid-auto-rows:auto!important;
  height:auto!important;
  min-height:0!important;
  overflow:visible!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product{
  display:flex!important;
  visibility:visible!important;
  opacity:1!important;
  height:auto!important;
  position:static!important
}


body.as-inner .wl-filterable-products-content .as-card-buy{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:12px;width:100%}
body.as-inner .wl-filterable-products-content .as-qty{display:inline-flex;align-items:center;border:1px solid #d7e4ea;border-radius:999px;background:#fff;overflow:hidden;flex:0 0 auto}
body.as-inner .wl-filterable-products-content .as-qty button{width:32px;height:36px;border:0;background:#eefbfc;color:#1e4b9a;font-size:18px;line-height:1;cursor:pointer;padding:0}
body.as-inner .wl-filterable-products-content .as-qty input{width:34px;height:36px;border:0;text-align:center;font-weight:700;color:#1e4b9a;font-size:14px;padding:0;background:#fff;-moz-appearance:textfield}
body.as-inner .wl-filterable-products-content .as-qty input::-webkit-outer-spin-button,body.as-inner .wl-filterable-products-content .as-qty input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
body.as-inner .wl-filterable-products-content a.add_to_cart_button,body.as-inner .wl-filterable-products-content a.button.product_type_simple{display:inline-flex!important;align-items:center;justify-content:center;min-height:36px;padding:0 14px!important;border-radius:999px!important;background:#1e4b9a!important;color:#fff!important;border:0!important;font-size:12.5px!important;font-weight:700!important;line-height:1.4!important;white-space:nowrap}

/* کارت یکپارچه */

body.woocommerce-shop .wl-filterable-products-content ul.products > li.product,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product{
  background:#fff!important;
  border:1px solid #e3eef2!important;
  border-radius:28px!important;
  padding:12px 12px 14px!important;
  box-shadow:0 12px 32px rgba(30,75,154,.06)!important;
  overflow:hidden!important;
  display:flex!important;
  flex-direction:column!important;
  align-items:stretch!important;
  height:100%!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product > a,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product > a.woocommerce-loop-product__link{
  display:flex!important;
  flex-direction:column!important;
  align-items:center!important;
  width:100%!important;
  background:transparent!important;
  border:0!important;
  box-shadow:none!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product img,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product img{
  width:100%!important;
  max-width:100%!important;
  height:200px!important;
  object-fit:contain!important;
  background:#f4fbfb!important;
  border-radius:18px!important;
  padding:16px!important;
  box-shadow:none!important;
  margin:0!important
}
body.woocommerce-shop .wl-filterable-products-content .woocommerce-loop-product__title,
body.tax-product_cat .wl-filterable-products-content .woocommerce-loop-product__title{
  margin:12px 6px 0!important;
  text-align:center!important
}
body.woocommerce-shop .wl-filterable-products-content li.product .price,
body.tax-product_cat .wl-filterable-products-content li.product .price{
  margin:8px 0 0!important;
  text-align:center!important;
  justify-content:center!important
}
body.woocommerce-shop .wl-filterable-products-content .as-card-buy,
body.tax-product_cat .wl-filterable-products-content .as-card-buy{
  margin-top:auto!important;
  padding-top:12px!important;
  width:100%!important
}


@media(min-width:641px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{
    grid-template-columns:repeat(3,minmax(0,1fr))!important
  }
}
@media(max-width:640px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{
    grid-template-columns:minmax(0,1fr)!important
  }
}


.as-page-head,.as-page-head--cat{text-align:right!important;justify-content:flex-start!important;align-items:flex-start!important}
.as-page-head p,.as-page-head h1,.as-page-head--cat p,.as-page-head--cat h1{text-align:right!important;width:100%}
.as-page-head--cat > div{text-align:right;width:100%}


body.woocommerce-shop .wl-filterable-products-content ul.products,
body.tax-product_cat .wl-filterable-products-content ul.products{
  display:grid!important;
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
  grid-auto-flow:row!important;
  width:100%!important;
  max-width:1180px!important;
  margin-left:auto!important;
  margin-right:auto!important
}
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product,
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product.first,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product.first,
body.woocommerce-shop .wl-filterable-products-content ul.products > li.product.last,
body.tax-product_cat .wl-filterable-products-content ul.products > li.product.last{
  width:auto!important;
  max-width:none!important;
  min-width:0!important;
  float:none!important;
  clear:none!important;
  grid-column:auto!important;
  grid-row:auto!important;
  margin:0!important;
  position:static!important;
  left:auto!important;
  right:auto!important;
  top:auto!important;
  flex:none!important
}
@media(max-width:640px){
  body.woocommerce-shop .wl-filterable-products-content ul.products,
  body.tax-product_cat .wl-filterable-products-content ul.products{
    grid-template-columns:minmax(0,1fr)!important
  }
}


body.woocommerce-shop ul.products::before,
body.woocommerce-shop ul.products::after,
body.tax-product_cat ul.products::before,
body.tax-product_cat ul.products::after,
body.as-inner ul.products::before,
body.as-inner ul.products::after{
  content:none!important;
  display:none!important;
}


@media(min-width:641px){
  body.woocommerce-shop .wl-filterable-products-content ul.products > li.product:nth-child(3n+1):last-child,
  body.woocommerce-shop .wl-filterable-products-content ul.products > li.product:nth-child(3n+2):last-child,
  body.woocommerce-shop .wl-filterable-products-content ul.products > li.product:nth-child(3n+1):nth-last-child(2),
  body.tax-product_cat .wl-filterable-products-content ul.products > li.product:nth-child(3n+1):last-child,
  body.tax-product_cat .wl-filterable-products-content ul.products > li.product:nth-child(3n+2):last-child,
  body.tax-product_cat .wl-filterable-products-content ul.products > li.product:nth-child(3n+1):nth-last-child(2){
    display:none!important
  }
}

</style>
CSS;
} );

if ( ! function_exists( 'as_inner_header_html' ) ) {
	function as_inner_header_html() {
		if ( is_front_page() ) {
			return;
		}
		static $done = false;
		if ( $done ) {
			return;
		}
		$done = true;
		$logo = 'https://aquasafe.ir/wp-content/uploads/2026/11/aquasafe-wordmark-color-1.png';
		echo <<<HTML
<header id="as-top">
  <div class="as-topbar"><span><b>آکواسیف</b> · نمایندگی نیشابور</span><a href="tel:09373211344">0937 321 1344</a></div>
  <div class="as-head-main">
  <button class="as-burger" type="button" aria-label="menu">☰</button>
  <nav class="as-nav as-nav-r">
    <ul>
      <li><a href="https://aquasafe.ir/"><svg viewBox="0 0 24 24"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1z"/></svg>خانه</a></li>
      <li class="as-has-mega">
        <a href="https://aquasafe.ir/shop/"><svg viewBox="0 0 24 24"><path d="M4 8h16l-1.2 11H5.2L4 8z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/></svg>محصولات</a>
        <div class="as-mega" role="menu">
          <div class="as-mega-in">
            <div class="as-mega-hd"><strong>دسته‌بندی محصولات</strong><span>دسترسی سریع به فیلترها و دستگاه‌ها</span></div>
            <a href="https://aquasafe.ir/product-category/%d8%af%d8%b3%d9%86%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/"><svg viewBox="0 0 24 24"><path d="M7 20V9l5-4 5 4v11"/><path d="M10 20v-6h4v6"/></svg>دستگاه تصفیه آب · <small>خانگی آکواجوی</small></a>
            <a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/"><svg viewBox="0 0 24 24"><path d="M4 5h16l-6 8v6l-4 2v-8z"/></svg>فیلتر دستگاه · <small>تعویض دوره‌ای</small></a>
            <a href="https://aquasafe.ir/product-category/%da%a9%d8%a7%d8%b1%d8%aa%d8%b1%db%8c%d8%ac-%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/"><svg viewBox="0 0 24 24"><rect x="8" y="3" width="8" height="18" rx="3"/><path d="M8 8h8M8 12h8"/></svg>کارتریج · <small>فیلتر خانگی</small></a>
            <a href="https://aquasafe.ir/product-category/%d9%82%d8%b7%d8%b9%d8%a7%d8%aa-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3M6 6l2 2M16 16l2 2M18 6l-2 2M8 16l-2 2"/></svg>قطعات · <small>اتصالات و هوزینگ</small></a>
            <a href="https://aquasafe.ir/product-category/%d9%85%d9%86%d8%a8%d8%b9-%d8%a2%d8%a8-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/"><svg viewBox="0 0 24 24"><path d="M7 8h10v10a4 4 0 0 1-4 4h-2a4 4 0 0 1-4-4z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>منبع آب · <small>مخزن تحت فشار</small></a>
            <a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%da%a9%d8%b1%d8%a8%d9%86/"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M8 12h8M12 8v8"/></svg>فیلتر کربن · <small>طعم و کلر</small></a>
            <a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d9%87%d9%88%d8%a7%db%8c-%d8%ae%d8%a7%d9%86%da%af%db%8c/"><svg viewBox="0 0 24 24"><path d="M4 12h3l2-6 3 12 2-8 2 4h4"/></svg>فیلتر هوا · <small>خانگی</small></a>
            <a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d9%88%d8%b4-%d8%ad%d9%85%d8%a7%d9%85/"><svg viewBox="0 0 24 24"><path d="M12 3v8"/><path d="M8 11c0 4 8 4 8 0"/><path d="M9 19h6"/></svg>فیلتر دوش · <small>حمام</small></a>
            <a class="as-mega-all" href="https://aquasafe.ir/shop/">مشاهده همه محصولات</a>
          </div>
        </div>
      </li>
      <li><a href="https://aquasafe.ir/%d9%88%d8%a8%d9%84%d8%a7%da%af/"><svg viewBox="0 0 24 24"><path d="M5 5h14v14H5z"/><path d="M8 9h8M8 12h8M8 15h5"/></svg>وبلاگ</a></li>
    </ul>
  </nav>
  <a class="as-logo" href="https://aquasafe.ir/"><img src="{$logo}" alt="aquasafe"></a>
  <nav class="as-nav as-nav-l">
    <ul>
      <li><a href="https://aquasafe.ir/%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d8%a7/"><svg viewBox="0 0 24 24"><path d="M8 5h8v14H8z"/><circle cx="12" cy="17" r=".8" fill="currentColor" stroke="none"/></svg>تماس با ما</a></li>
      <li><a href="https://aquasafe.ir/%d8%af%d8%b1%d8%a8%d8%a7%d8%b1%d9%87-%d9%85%d8%a7/"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 19c1.4-3.2 3.8-5 7-5s5.6 1.8 7 5"/></svg>درباره ما</a></li>
      <li><button type="button" class="as-head-cta" data-as-open>دستیار انتخاب</button></li>
      <li class="as-li-cart"><a class="as-cart" href="https://aquasafe.ir/cart/"><svg viewBox="0 0 24 24"><circle cx="9" cy="20" r="1.2" fill="currentColor" stroke="none"/><circle cx="18" cy="20" r="1.2" fill="currentColor" stroke="none"/><path d="M4 5h2l2.2 10h9.3L20 8H7"/></svg><b>0</b>سبد خرید</a></li>
    </ul>
  </nav>
  </div>
</header>
<div class="as-drawer" id="as-drawer">
  <a href="https://aquasafe.ir/">خانه</a>
  <a href="https://aquasafe.ir/shop/">محصولات</a>
  <a href="https://aquasafe.ir/product-category/%d8%af%d8%b3%d9%86%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">دستگاه تصفیه آب</a>
  <a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">فیلتر دستگاه</a>
  <a href="https://aquasafe.ir/%d9%88%d8%a8%d9%84%d8%a7%da%af/">وبلاگ</a>
  <a href="https://aquasafe.ir/%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d8%a7/">تماس با ما</a>
  <a href="https://aquasafe.ir/%d8%af%d8%b1%d8%a8%d8%a7%d8%b1%d9%87-%d9%85%d8%a7/">درباره ما</a>
  <a href="https://aquasafe.ir/cart/">سبد خرید</a>
  <button type="button" class="as-head-cta" data-as-open>دستیار انتخاب</button>
</div>
HTML;
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			echo '<div class="as-page-head"><p>فروشگاه</p><h1>محصولات آکواسیف</h1></div>';
			echo '<nav class="as-cats" aria-label="دسته‌ها">';
			echo '<a href="https://aquasafe.ir/product-category/%d8%af%d8%b3%d9%86%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">دستگاه تصفیه آب</a>';
			echo '<a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">فیلتر دستگاه</a>';
			echo '<a href="https://aquasafe.ir/product-category/%da%a9%d8%a7%d8%b1%d8%aa%d8%b1%db%8c%d8%ac-%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">کارتریج</a>';
			echo '<a href="https://aquasafe.ir/product-category/%d9%82%d8%b7%d8%b9%d8%a7%d8%aa-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">قطعات</a>';
			echo '<a href="https://aquasafe.ir/product-category/%d9%85%d9%86%d8%a8%d8%b9-%d8%a2%d8%a8-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">منبع آب</a>';
			echo '<a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%da%a9%d8%b1%d8%a8%d9%86/">فیلتر کربن</a>';
			echo '</nav>';
		} elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
			$as_term_id = get_queried_object_id();
			$as_thumb_id = $as_term_id ? get_term_meta( $as_term_id, 'thumbnail_id', true ) : 0;
			$as_thumb = $as_thumb_id ? wp_get_attachment_image( (int) $as_thumb_id, 'medium', false, array( 'class' => 'as-cat-feat' ) ) : '';
			echo '<div class="as-page-head as-page-head--cat">' . $as_thumb . '<div><p>دسته‌بندی</p><h1>' . esc_html( single_term_title( '', false ) ) . '</h1></div></div>';
		} elseif ( is_home() ) {
			echo '<div class="as-page-head"><p>مجله</p><h1>وبلاگ آکواسیف</h1></div>';
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			echo '<nav class="as-crumbs" aria-label="breadcrumb"><a href="https://aquasafe.ir/">خانه</a><i class="sep"></i><a href="https://aquasafe.ir/shop/">فروشگاه</a><i class="sep"></i><span class="now">' . esc_html( get_the_title() ) . '</span></nav>';
		} elseif ( is_singular( 'post' ) ) {
			echo '<nav class="as-crumbs" aria-label="breadcrumb"><a href="https://aquasafe.ir/">خانه</a><i class="sep"></i><a href="https://aquasafe.ir/%d9%88%d8%a8%d9%84%d8%a7%da%af/">وبلاگ</a><i class="sep"></i><span class="now">' . esc_html( get_the_title() ) . '</span></nav>';
		}
	}
}

add_action( 'wp_body_open', 'as_inner_header_html', 1 );

add_action( 'wp_footer', function () {
	if ( is_front_page() ) {
		return;
	}
	as_inner_header_html();
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$as_cat_id   = get_queried_object_id();
		$as_cat_desc = $as_cat_id ? get_term_meta( $as_cat_id, 'as_cat_prose', true ) : '';
		if ( ! $as_cat_desc ) {
			$as_cat_desc = term_description();
		}
		if ( $as_cat_desc ) {
			$as_cat_desc = preg_replace( '/<\/?h1\b[^>]*>/i', static function ( $m ) {
				return str_ireplace( 'h1', 'h2', $m[0] );
			}, $as_cat_desc );
			echo '<div class="as-cat-prose">' . wp_kses_post( $as_cat_desc ) . '</div>';
		}
	}
	echo <<<'HTML'
<footer id="as-foot">
  <svg class="as-wave" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true"><path fill="currentColor" d="M0,0 H1440 V38 C1220,38 980,38 820,38 C760,38 742,78 720,78 C698,78 680,38 620,38 C460,38 220,38 0,38 Z"></path></svg>
  <div class="as-drop" aria-hidden="true"><i><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.7"><path d="M12 3c3.2 4.2 6.5 7.8 6.5 11.2A6.5 6.5 0 1 1 5.5 14.2C5.5 10.8 8.8 7.2 12 3z"/><path d="M9.2 14.2c.4 2 2 3.2 2.8 3.2"/></svg></i></div>
  <div class="as-in">
    <div>
      <img class="as-word" src="https://aquasafe.ir/wp-content/uploads/2026/11/aquasafe-wordmark-white-1.png" alt="aquasafe">
      <p>نمایندگی تصفیه آب خانگی در نیشابور. طراحی آرام، خدمات دقیق، و آبی که می‌شود به آن اعتماد کرد.</p>
    </div>
    <div>
      <h3>محصولات</h3>
      <ul class="as-links">
        <li><a href="https://aquasafe.ir/product-category/%d8%af%d8%b3%d9%86%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">دستگاه تصفیه آب</a></li>
        <li><a href="https://aquasafe.ir/product-category/%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%af%d8%b3%d8%aa%da%af%d8%a7%d9%87-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">فیلتر و یدکی</a></li>
        <li><a href="https://aquasafe.ir/product-category/%da%a9%d8%a7%d8%b1%d8%aa%d8%b1%db%8c%d8%ac-%d9%81%db%8c%d9%84%d8%aa%d8%b1-%d8%aa%d8%b5%d9%81%db%8c%d9%87-%d8%a2%d8%a8-%d8%ae%d8%a7%d9%86%da%af%db%8c/">کارتریج</a></li>
        <li><a href="https://aquasafe.ir/shop/">همه محصولات</a></li>
      </ul>
    </div>
    <div>
      <h3>پیوندها</h3>
      <ul class="as-links">
        <li><a href="https://aquasafe.ir/shop/">محصولات</a></li>
        <li><a href="https://aquasafe.ir/%d8%af%d8%b1%d8%a8%d8%a7%d8%b1%d9%87-%d9%85%d8%a7/">درباره ما</a></li>
        <li><a href="https://aquasafe.ir/%d9%88%d8%a8%d9%84%d8%a7%da%af/">وبلاگ</a></li>
        <li><a href="https://aquasafe.ir/%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d8%a7/">تماس با ما</a></li>
        <li><a href="https://aquasafe.ir/my-account/">حساب کاربری</a></li>
      </ul>
    </div>
    <div>
      <span class="as-k">تماس</span>
      <a class="as-ph" href="tel:09373211344">۰۹۳۷ ۳۲۱ ۱۳۴۴</a>
      <div class="as-addr">نیشابور، شهرک صنعتی عطار، بلوک ۱۳</div>
      <div class="as-soc">
        <a href="https://wa.me/989373211344" aria-label="واتساپ"><svg viewBox="0 0 24 24"><path d="M20 12a8 8 0 0 1-11.7 7.1L4 20l1-4.2A8 8 0 1 1 20 12zm-8 6.2A6.2 6.2 0 1 0 6.6 16l.2.4-.5 2.1 2.2-.5.4.2A6.2 6.2 0 0 0 12 18.2zm3.4-3.6c-.2-.1-1.2-.6-1.4-.7s-.3-.1-.5.1-.6.7-.7.8-.3.2-.5.1a5 5 0 0 1-1.5-.9 5.5 5.5 0 0 1-1-1.3c-.1-.2 0-.3.1-.5l.4-.5.1-.2c0-.1 0-.3-.1-.4l-.7-1.6c-.2-.4-.4-.4-.5-.4h-.4c-.2 0-.4.1-.6.4s-.7.7-.7 1.8.7 2.1.8 2.2a8.8 8.8 0 0 0 3.3 2.7c.5.2.8.2 1.1.1.3-.1 1.2-.5 1.3-1s.2-.8.1-.9-.2-.1-.4-.2z"/></svg></a>
        <a href="tel:09373211344" aria-label="تلفن"><svg viewBox="0 0 24 24"><path d="M7 3h3l1 5-2 1a12 12 0 0 0 6 6l1-2 5 1v3c0 1-1 2-2 2A15 15 0 0 1 5 5c0-1 1-2 2-2z"/></svg></a>
        <a href="https://aquasafe.ir/%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d8%a7/" aria-label="پیام"><svg viewBox="0 0 24 24"><path d="M4 6h16v12H4z"/><path d="m4 7 8 7 8-7" fill="none" stroke="currentColor" stroke-width="1.6"/></svg></a>
      </div>
    </div>
  </div>
  <div class="as-bar"><span>© ۱۴۰۴ <b>AquaSafe</b> · نیشابور</span><span>طراحی آرام برای آب سالم</span></div>
</footer>
HTML;
	echo <<<'JS'
<script id="as-inner-js">
(function(){
  var top=document.getElementById('as-top');
  var drawer=document.getElementById('as-drawer');
  if(top){document.body.insertBefore(top, document.body.firstChild)}
  if(drawer){document.body.insertBefore(drawer, top ? top.nextSibling : document.body.firstChild)}
  ['colophon','masthead','site-header','site-footer'].forEach(function(id){ var n=document.getElementById(id); if(n) n.remove(); });
  document.querySelectorAll('.ehf-footer, .ehf-header, .elementor-location-footer, .elementor-location-header, .hfe-before-footer-hook').forEach(function(el){ el.remove(); });
  setTimeout(function(){
    document.querySelectorAll('.ehf-footer, .ehf-header, #masthead, #colophon, .elementor-location-footer').forEach(function(el){ el.remove(); });
  }, 800);
  var burger=document.querySelector('.as-burger');
  if(burger){burger.addEventListener('click',function(){drawer.classList.toggle('open')})}
  if(drawer){drawer.addEventListener('click',function(){drawer.classList.remove('open')})}
  var onScroll=function(){if(!top)return; top.classList.toggle('is-stuck', window.scrollY>8)};
  onScroll(); window.addEventListener('scroll', onScroll, {passive:true});
  var path=location.pathname.replace(/\/+$/,'')||'/';
  document.querySelectorAll('#as-top .as-nav a').forEach(function(a){
    if(a.closest('.as-mega')) return;
    var href=(a.getAttribute('href')||'').replace(/\/+$/,'');
    if(href && (href.endsWith(path) || (path==='/' && href==='https://aquasafe.ir'))) a.classList.add('is-on');
  });
  var badge=document.querySelector('.uael-mc__btn-badge, .cart-count, .count');
  var mine=document.querySelector('#as-top .as-cart b');
  if(badge && mine){ mine.textContent = (badge.textContent||'0').trim() || '0'; }

  function asHead(kicker,title){
    if(document.querySelector('.as-page-head')) return;
    var el=document.createElement('div'); el.className='as-page-head';
    el.innerHTML='<p></p><h1></h1>';
    el.querySelector('p').textContent=kicker;
    el.querySelector('h1').textContent=title;
    var anchor=document.getElementById('as-top');
    if(anchor && anchor.parentNode){
      if(anchor.nextSibling) anchor.parentNode.insertBefore(el, anchor.nextSibling);
      else anchor.parentNode.appendChild(el);
    } else document.body.insertBefore(el, document.body.firstChild);
  }
  if(document.body.classList.contains('woocommerce-shop')) asHead('فروشگاه','محصولات آکواسیف');
  if(document.body.classList.contains('tax-product_cat')){
    var catTitle=document.querySelector('.woocommerce-products-header h1, h1.page-title');
    asHead('دسته‌بندی', (catTitle && catTitle.textContent.trim()) || document.title.split('|')[0].trim() || 'محصولات');
  }
  if(document.body.classList.contains('blog')) asHead('مجله','وبلاگ آکواسیف');
  (function(){
    var prose=document.querySelector('.as-cat-prose');
    var foot=document.getElementById('as-foot');
    if(prose && foot && foot.parentNode) foot.parentNode.insertBefore(prose, foot);
  })();

  document.querySelectorAll('.woolentor-filter-wrap').forEach(function(el){
    var col=el.closest('.elementor-column'); if(col) col.classList.add('as-hide');
  });
  (function(){
    if(document.querySelector('.as-cats')) return;
    if(!document.body.classList.contains('woocommerce-shop') && !document.body.classList.contains('tax-product_cat')) return;
    var row=document.createElement('nav'); row.className='as-cats'; row.setAttribute('aria-label','دسته‌ها');
    var seen={};
    document.querySelectorAll('.as-mega-in a').forEach(function(a){
      var sm=a.querySelector('small');
      var main='';
      a.childNodes.forEach(function(n){ if(n.nodeType===3) main+=n.textContent; });
      main=(main||'').replace(/\s+/g,' ').trim();
      var sub=sm?(sm.textContent||'').trim():'';
      var t=sub? (main+' · '+sub): (a.textContent||'').trim();
      var href=a.getAttribute('href')||'';
      if(!t || seen[t] || t.length>48) return; seen[t]=1;
      var chip=document.createElement('a'); chip.href=href; chip.textContent=t; row.appendChild(chip);
    });
    var head=document.querySelector('.as-page-head');
    if(head && head.parentNode && row.childNodes.length) head.parentNode.insertBefore(row, head.nextSibling);
  })();

  document.querySelectorAll('.as-cats a').forEach(function(a){
    var sm=a.querySelector('small');
    if(!sm) return;
    var main='';
    a.childNodes.forEach(function(n){ if(n.nodeType===3) main+=n.textContent; });
    main=main.replace(/\s+/g,' ').trim();
    var sub=(sm.textContent||'').trim();
    if(main && sub && a.textContent.indexOf('·')===-1){
      sm.textContent=' · '+sub;
    }
  });

  
  document.querySelectorAll('.wl-filterable-products-content a.add_to_cart_button').forEach(function(a){
    if(a.closest('.as-card-buy')) return;
    var wrap=document.createElement('div');
    wrap.className='as-card-buy';
    a.parentNode.insertBefore(wrap,a);
    if(a.classList.contains('product_type_simple')){
      var qty=document.createElement('div');
      qty.className='as-qty';
      qty.innerHTML='<button type="button" data-q="-1" aria-label="کم کردن">−</button><input type="number" min="1" value="1" inputmode="numeric" aria-label="تعداد"><button type="button" data-q="1" aria-label="زیاد کردن">+</button>';
      wrap.appendChild(qty);
      var input=qty.querySelector('input');
      function sync(){
        var n=Math.max(1, parseInt(input.value,10)||1);
        input.value=n;
        a.setAttribute('data-quantity', String(n));
        var href=a.getAttribute('href')||'';
        if(href.indexOf('quantity=')===-1){ a.setAttribute('href', href+(href.indexOf('?')>=0?'&':'?')+'quantity='+n); }
        else { a.setAttribute('href', href.replace(/quantity=\d+/, 'quantity='+n)); }
      }
      qty.addEventListener('click', function(e){
        var b=e.target.closest('button');
        if(!b) return;
        e.preventDefault();
        e.stopPropagation();
        input.value=Math.max(1,(parseInt(input.value,10)||1)+parseInt(b.getAttribute('data-q'),10));
        sync();
      });
      input.addEventListener('change', sync);
      input.addEventListener('click', function(e){ e.stopPropagation(); });
      sync();
      a.textContent='افزودن به سبد';
    }
    wrap.appendChild(a);
  });

  function asGrid(){
    var ul=document.querySelector('.wl-filterable-products-content ul.products');
    if(!ul || ul.closest('.elementor-element-43148cdf') || ul.closest('.related') || ul.closest('.upsells')) return;
    [].forEach.call(ul.querySelectorAll('li.product-category'), function(li){ li.parentNode.removeChild(li); });
    try{
      if(window.jQuery && jQuery(ul).data('isotope')) jQuery(ul).isotope('destroy');
    }catch(e){}
    var cols = window.innerWidth<=640 ? 1 : 3;
    ul.style.setProperty('display','grid','important');
    ul.style.setProperty('grid-template-columns','repeat('+cols+', minmax(0,1fr))','important');
    ul.style.setProperty('width','100%','important');
    ul.style.setProperty('height','auto','important');
    ul.style.setProperty('overflow','visible','important');
    ul.style.setProperty('gap','24px 18px','important');
    [].forEach.call(ul.children, function(li){
      li.style.setProperty('float','none','important');
      li.style.setProperty('width','100%','important');
      li.style.setProperty('max-width','none','important');
      li.style.setProperty('min-width','0','important');
      li.style.setProperty('flex','none','important');
      li.style.setProperty('clear','none','important');
      li.style.setProperty('position','static','important');
      li.style.setProperty('left','auto','important');
      li.style.setProperty('top','auto','important');
      li.style.setProperty('transform','none','important');
      li.style.setProperty('margin','0','important');
    });
  }
  asGrid();
  document.addEventListener('DOMContentLoaded', asGrid);
  window.addEventListener('load', asGrid);
  setTimeout(asGrid, 400);
  function asRelatedScroll(){
    var ul=document.querySelector('.elementor-element-43148cdf ul.products');
    if(!ul) return;
    try{if(window.jQuery&&jQuery(ul).data('isotope'))jQuery(ul).isotope('destroy')}catch(e){}
    ul.style.setProperty('display','flex','important');
    ul.style.setProperty('flex-wrap','nowrap','important');
    ul.style.removeProperty('width');
    [].forEach.call(ul.children,function(li){
      li.style.removeProperty('float'); li.style.removeProperty('position'); li.style.removeProperty('left'); li.style.removeProperty('top'); li.style.removeProperty('transform');
    });
    if(ul.parentNode.classList.contains('as-rel-wrap')) return;
    var wrap=document.createElement('div'); wrap.className='as-rel-wrap';
    ul.parentNode.insertBefore(wrap, ul);
    wrap.appendChild(ul);
    var nav=document.createElement('div'); nav.className='as-rel-nav';
    nav.innerHTML='<button type="button" class="as-rel-next" aria-label="بعدی">‹</button><button type="button" class="as-rel-prev" aria-label="قبلی">›</button>';
    wrap.appendChild(nav);
    var amt=function(){return Math.max(240, Math.round(ul.clientWidth*0.72));};
    nav.querySelector('.as-rel-next').addEventListener('click',function(){ul.scrollBy({left:-amt(),behavior:'smooth'})});
    nav.querySelector('.as-rel-prev').addEventListener('click',function(){ul.scrollBy({left:amt(),behavior:'smooth'})});
  }
  asRelatedScroll();
  document.addEventListener('DOMContentLoaded', asRelatedScroll);
  window.addEventListener('load', asRelatedScroll);
  setTimeout(asRelatedScroll, 400);
  setTimeout(asRelatedScroll, 1200);

  try {
  var crumb=document.querySelector('.woolentor-breadcrumb nav.woocommerce-breadcrumb, nav.woocommerce-breadcrumb');
  if(crumb && !document.querySelector('.as-crumbs')){
    var items=[];
    crumb.querySelectorAll('a').forEach(function(a){
      var t=(a.textContent||'').trim();
      if(t) items.push({href:a.getAttribute('href'), t:t});
    });
    var clone=crumb.cloneNode(true);
    clone.querySelectorAll('a, .breadcrumb-separator, i').forEach(function(n){n.remove()});
    var now=(clone.textContent||'').replace(/\s+/g,' ').trim();
    var bar=document.createElement('nav');
    bar.className='as-crumbs';
    bar.setAttribute('aria-label','breadcrumb');
    items.forEach(function(it,i){
      if(i){var s=document.createElement('i'); s.className='sep'; bar.appendChild(s)}
      var a=document.createElement('a'); a.href=it.href; a.textContent=it.t; bar.appendChild(a);
    });
    if(now){
      var s=document.createElement('i'); s.className='sep'; bar.appendChild(s);
      var span=document.createElement('span'); span.className='now'; span.textContent=now; bar.appendChild(span);
    }
    var dest=document.querySelector('.elementor-element-23eeb16') || document.querySelector('.woolentor-woo-template-builder') || document.querySelector('.site-main, #content');
    if(dest && dest.parentNode) dest.parentNode.insertBefore(bar, dest);
    else if(dest) dest.insertBefore(bar, dest.firstChild);
    crumb.closest('.elementor-element-74a45fcc, .woolentor-breadcrumb') && (crumb.closest('.elementor-element-74a45fcc, .woolentor-breadcrumb').classList.add('as-hide'));
  } else if(document.body.classList.contains('single-post') && !document.querySelector('.as-crumbs')){
    var t=(document.querySelector('h1.entry-title')||{}).textContent||'';
    var bar=document.createElement('nav'); bar.className='as-crumbs'; bar.setAttribute('aria-label','breadcrumb');
    bar.innerHTML='<a href="https://aquasafe.ir/">خانه</a><i class="sep"></i><a href="https://aquasafe.ir/%d9%88%d8%a8%d9%84%d8%a7%da%af/">وبلاگ</a>';
    if(t.trim()){
      var s=document.createElement('i'); s.className='sep'; bar.appendChild(s);
      var span=document.createElement('span'); span.className='now'; span.textContent=t.trim(); bar.appendChild(span);
    }
    var main=document.querySelector('#content, .site-main');
    if(main) main.insertBefore(bar, main.firstChild);
  }
  } catch(e) {}

  (function(){
    if(!document.body.classList.contains('single-product')) return;
    if(document.querySelector('.as-spec-grid')) return;
    function txt(n){return (n&&n.textContent||'').replace(/\s+/g,' ').trim()}
    function asHiraData(){
      var el=document.getElementById('as-hira-data');
      if(!el) return null;
      try{return JSON.parse(el.textContent)}catch(e){return null}
    }
    function rowsFromShort(short){
      var rows=[];
      if(!short) return rows;
      short.querySelectorAll('table tr').forEach(function(tr){
        var cells=tr.querySelectorAll('td,th');
        if(cells.length<2) return;
        var k=txt(cells[0]), v=txt(cells[1]);
        if(!k||!v||/مشخصه|جزئیات/.test(k)) return;
        rows.push({k:k,v:v});
      });
      if(rows.length) return rows;
      var raw=short.innerHTML.replace(/<p>/g,'').replace(/<\/p>/g,'<br>');
      raw.split(/<br\s*\/?\>/i).map(function(s){return s.replace(/&nbsp;/g,' ').replace(/<[^>]+>/g,'').trim()}).filter(Boolean).forEach(function(line){
        if(/مشخصه|جزئیات|جدول/.test(line)&&line.length<24) return;
        var m=line.match(/^([\u0600-\u06FF\s،.\-]+?)\s+([A-Za-z0-9۰-۹٠-٩۱۲۳۴۵۶۷۸۹].*)$/);
        if(m) rows.push({k:m[1].trim(),v:m[2].trim()});
      });
      return rows;
    }
    var short=document.querySelector('.woocommerce-product-details__short-description');
    var data=asHiraData();
    var rows=(data&&data.specs&&data.specs.length)?data.specs:rowsFromShort(short);
    if(!rows.length) return;
    var host=document.querySelector('.elementor-element-1ef1b460')||document.querySelector('.elementor-element-3ca5dac7 .elementor-widget-wrap');
    if(!host) return;
    var wrap=document.createElement('div'); wrap.className='as-specs';
    wrap.innerHTML='<div class="as-spec-head"><span class="t">مشخصات فنی</span><a href="#as-spec-table">مشاهده جدول مشخصات</a></div>';
    var grid=document.createElement('div'); grid.className='as-spec-grid';
    var uls=[document.createElement('ul'),document.createElement('ul')];
    uls.forEach(function(u){grid.appendChild(u)});
    var mid=Math.ceil(rows.length/2);
    rows.forEach(function(r,i){
      var li=document.createElement('li');
      li.innerHTML='<span class="k"></span><span class="v"></span>';
      li.querySelector('.k').textContent=r.k;
      li.querySelector('.v').textContent=r.v||'';
      uls[i<mid?0:1].appendChild(li);
    });
    wrap.appendChild(grid);
    if(host.parentNode) host.parentNode.insertBefore(wrap, host.nextSibling);
    else host.appendChild(wrap);
    if(!document.getElementById('as-spec-table')){
      var table=document.createElement('div'); table.id='as-spec-table';
      table.innerHTML='<h3>جدول مشخصات</h3>';
      var full=document.createElement('div'); full.className='as-specs';
      rows.forEach(function(r){
        var d=document.createElement('div'); d.className='row';
        d.innerHTML='<span class="k"></span><span class="dots"></span><span class="v"></span>';
        d.querySelector('.k').textContent=r.k; d.querySelector('.v').textContent=r.v||'';
        full.appendChild(d);
      });
      table.appendChild(full);
      var after=document.querySelector('.elementor-element-729d4d2c')||host.closest('.elementor-element-23eeb16');
      if(after&&after.parentNode) after.parentNode.insertBefore(table, after.nextSibling);
    }
  })();

  var gal=document.querySelector('.woocommerce-product-gallery');
  if(document.body.classList.contains('single-product') && gal && !document.querySelector('.as-buy')){
    var price=document.querySelector('.elementor-element-69a420b5 .price');
    var form=document.querySelector('form.cart');
    function clean(v){return (v||'').replace(/\s+/g,' ').trim();}
    var amount='';
    if(price){
      var node=price.querySelector('.woocommerce-Price-amount');
      if(node){
        var clone=node.cloneNode(true);
        clone.querySelectorAll('.woocommerce-Price-currencySymbol').forEach(function(n){n.remove();});
        amount=clean(clone.textContent).replace(/تومان/g,'').trim();
      }
    }
    var dateText='';
    var dateIso='';
    if(price){
      var stamp=price.querySelector('time.as-price-spotlight__date');
      if(stamp){
        dateIso=stamp.getAttribute('datetime')||'';
        var raw=clean(stamp.textContent);
        var parts=raw.split(/[:\uff1a]/);
        dateText=clean(parts.length>1?parts.slice(1).join(':'):'');
        if(dateText.indexOf('روزرسانی')!==-1) dateText='';
      }
    }
    var pill=document.createElement('div');
    pill.className='as-buy';
    var meta=document.createElement('div');
    var label=document.createElement('span');
    label.className='as-purchase__label';
    label.textContent='قیمت';
    var amountRow=document.createElement('p');
    amountRow.className='as-purchase__amount';
    var num=document.createElement('span');
    num.className='as-purchase__num';
    num.textContent=amount;
    var currency=document.createElement('span');
    currency.className='as-purchase__currency';
    currency.textContent='تومان';
    amountRow.appendChild(num);
    amountRow.appendChild(currency);
    meta.appendChild(label);
    meta.appendChild(amountRow);
    if(dateText){
      var updated=document.createElement('p');
      updated.className='as-purchase__updated';
      var updatedLabel=document.createElement('span');
      updatedLabel.className='as-purchase__updated-label';
      updatedLabel.textContent='آخرین به‌روزرسانی:';
      var timeEl=document.createElement('time');
      timeEl.textContent=dateText;
      if(dateIso) timeEl.setAttribute('datetime', dateIso);
      updated.appendChild(updatedLabel);
      updated.appendChild(timeEl);
      meta.appendChild(updated);
    }
    var actions=document.createElement('div');
    actions.className='as-purchase__actions';
    if(form) actions.appendChild(form);
    var gbtn=document.createElement('button');
    gbtn.type='button';
    gbtn.className='as-gallery-btn';
    gbtn.textContent='مشاهده گالری تصاویر';
    gbtn.addEventListener('click',function(){
      var t=gal.querySelector('.woocommerce-product-gallery__trigger');
      if(t) t.click();
    });
    actions.appendChild(gbtn);
    pill.appendChild(meta);
    pill.appendChild(actions);
    gal.parentNode.appendChild(pill);
    var shell=document.querySelector('.elementor-element-1843cd7e');
    if(shell && form && !shell.contains(form)) shell.style.display='none';
    var bar=document.querySelector('.elementor-element-63068de');
    if(bar) bar.classList.add('as-hide');
  }
  var btn=document.querySelector('.single_add_to_cart_button');
  if(btn && btn.textContent.trim()==='خرید') btn.textContent='اضافه به سبد خرید';
})();
</script>
JS;
}, 5 );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, array( 'term.php', 'edit-tags.php' ), true ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'product_cat' !== $screen->taxonomy ) {
		return;
	}
	wp_enqueue_editor();
	wp_enqueue_media();
} );

add_action( 'admin_footer', function () {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || empty( $screen->taxonomy ) || 'product_cat' !== $screen->taxonomy ) {
		return;
	}
	echo <<<'JS'
<script>
jQuery(function($){
  var id = document.getElementById('description') ? 'description' : (document.getElementById('tag-description') ? 'tag-description' : '');
  if(!id || !window.wp || !wp.editor || !wp.editor.initialize) return;
  $('#'+id).closest('.form-field').find('label, p').first().text('متن دسته (با هدینگ)');
  wp.editor.initialize(id, {
    tinymce: {
      wpautop: true,
      plugins: 'lists,link,paste,wordpress,wplink',
      toolbar1: 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignright,aligncenter,link,unlink,undo,redo',
      block_formats: 'پاراگراف=p;عنوان 2=h2;عنوان 3=h3;عنوان 4=h4'
    },
    quicktags: true,
    mediaButtons: true
  });
});
</script>
JS;
} );


add_action( 'wp_footer', function () {
	echo <<<'JS'
<script>
(function(){
  function hideCrisp(){
    try{
      window.$crisp=window.$crisp||[];
      window.$crisp.push(["do","chat:hide"]);
      window.$crisp.push(["on","session:loaded",function(){ window.$crisp.push(["do","chat:hide"]); }]);
    }catch(e){}
  }
  function openChat(e){
    if(e) e.preventDefault();
    try{
      window.$crisp=window.$crisp||[];
      window.$crisp.push(["do","chat:show"]);
      window.$crisp.push(["do","chat:open"]);
    }catch(err){
      location.href="https://aquasafe.ir/%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d8%a7/";
    }
  }
  hideCrisp();
  document.addEventListener("click", function(e){
    var b=e.target.closest(".as-open-chat");
    if(!b) return;
    openChat(e);
  });
  setTimeout(hideCrisp, 800);
  setTimeout(hideCrisp, 2500);
})();
</script>
JS;
}, 80 );


add_action('wp_footer', function () {
  if (is_front_page()) return;
  echo <<<'JS'
<script>
(function(){
  function fixGrid(){
    var cols = window.innerWidth<=640 ? 1 : 3;
    document.querySelectorAll('.wl-filterable-products-content ul.products').forEach(function(ul){
      ul.classList.remove('columns-1','columns-2','columns-4','columns-5','columns-6');
      ul.classList.add('columns-3');
      ul.style.setProperty('display','grid','important');
      ul.style.setProperty('grid-template-columns', cols===1 ? 'minmax(0,1fr)' : 'repeat(3, minmax(0, 1fr))','important');
      var items=[];
      [].forEach.call(ul.children, function(li){
        if(!li.classList.contains('type-product')){ li.style.setProperty('display','none','important'); return; }
        items.push(li);
        li.classList.remove('first','last');
        ['width','max-width','float','clear','position','left','right','top','margin'].forEach(function(p){
          li.style.setProperty(p, p==='position'?'static':(p==='float'||p==='clear'?'none':'auto'), 'important');
        });
        li.style.setProperty('grid-column','auto','important');
        li.style.setProperty('grid-row','auto','important');
        li.style.setProperty('margin','0','important');
      });
      var keep = Math.floor(items.length / cols) * cols;
      if(!keep) keep = items.length;
      items.forEach(function(li, i){
        li.style.setProperty('display', i < keep ? 'flex' : 'none', 'important');
      });
    });
  }
  fixGrid();
  document.addEventListener('DOMContentLoaded', fixGrid);
  window.addEventListener('load', fixGrid);
  setTimeout(fixGrid, 300);
  setTimeout(fixGrid, 1200);
})();
</script>
JS;
}, 99);
