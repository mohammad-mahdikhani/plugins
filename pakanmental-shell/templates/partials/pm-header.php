<?php
/**
 * PM header + mini-cart (used when Elementor header section is empty).
 */
defined( 'ABSPATH' ) || exit;

$home    = home_url( '/' );
$shop    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$cart    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
$blog    = get_permalink( get_option( 'page_for_posts' ) );
if ( ! $blog ) {
	$blog = home_url( '/blog/' );
}
$account = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' );
$logo    = PM_SHELL_LOGO;
$is_shop = ( class_exists( 'WooCommerce' ) && ( is_shop() || is_product_taxonomy() || is_singular( 'product' ) ) );
?>
<header class="pm-header">
	<div class="pm-container pm-header__wrap">
		<div class="pm-header__bar">
			<button type="button" class="pm-burger pm-mobile-only" data-pm-drawer-open aria-controls="pm-drawer" aria-expanded="false" aria-label="<?php esc_attr_e( 'باز کردن منو', 'pakanmental-shell' ); ?>">
				<span class="pm-burger__lines" aria-hidden="true"><i></i><i></i><i></i></span>
			</button>
			<nav class="pm-header__nav pm-desktop-only" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pakanmental-shell' ); ?>">
				<div class="pm-seg" role="list">
					<a class="pm-seg__link<?php echo is_front_page() ? ' is-active' : ''; ?>" href="<?php echo esc_url( $home ); ?>"><?php esc_html_e( 'خانه', 'pakanmental-shell' ); ?></a>
					<div class="pm-mega pm-seg__mega" data-pm-mega data-shop-url="<?php echo esc_url( $shop ); ?>">
						<a class="pm-seg__link pm-mega__trigger<?php echo $is_shop ? ' is-active' : ''; ?>" href="<?php echo esc_url( $shop ); ?>" aria-haspopup="dialog" aria-expanded="false" id="pm-mega-trigger"><?php esc_html_e( 'فروشگاه', 'pakanmental-shell' ); ?></a>
						<div class="pm-mega__panel" hidden role="dialog" aria-labelledby="pm-mega-trigger">
							<div class="pm-mega__inner">
								<div class="pm-mega__head">
									<span class="pm-mega__head-title"><?php esc_html_e( 'دسته‌بندی فروشگاه', 'pakanmental-shell' ); ?></span>
									<button type="button" class="pm-mega__close" data-pm-mega-close aria-label="<?php esc_attr_e( 'بستن منو', 'pakanmental-shell' ); ?>">×</button>
								</div>
								<div class="pm-mega__custom" data-pm-elementor-slot="mega-promo"></div>
								<div class="pm-mega__grid" data-pm-mega-grid>
									<p class="pm-mega__loading"><?php esc_html_e( 'در حال بارگذاری دسته‌ها…', 'pakanmental-shell' ); ?></p>
								</div>
								<a class="pm-mega__all" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'مشاهده همه محصولات', 'pakanmental-shell' ); ?></a>
							</div>
						</div>
					</div>
					<a class="pm-seg__link" href="<?php echo esc_url( $blog ); ?>"><?php esc_html_e( 'مجله', 'pakanmental-shell' ); ?></a>
				</div>
			</nav>
			<a class="pm-header__logo" href="<?php echo esc_url( $home ); ?>" aria-label="<?php esc_attr_e( 'پاکن منتال', 'pakanmental-shell' ); ?>">
				<span class="pm-header__logo-glow" aria-hidden="true"></span>
				<picture class="pm-logo__picture">
					<source type="image/webp" srcset="<?php echo esc_url( $logo ); ?>" />
					<img class="pm-logo__img" src="<?php echo esc_url( $logo ); ?>" width="220" height="72" alt="<?php esc_attr_e( 'پاکن منتال', 'pakanmental-shell' ); ?>" decoding="async" fetchpriority="high" />
				</picture>
			</a>
			<a class="pm-header__icon-btn pm-cart pm-mobile-only pm-header__cart-mobile" href="<?php echo esc_url( $cart ); ?>" data-pm-cart-open aria-label="<?php esc_attr_e( 'سبد خرید', 'pakanmental-shell' ); ?>" aria-haspopup="dialog" aria-controls="pm-mini-cart">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6L5 3H2"/></svg>
			</a>
			<div class="pm-header__tools pm-desktop-only">
				<div class="pm-search pm-search--inline">
					<label class="pm-search__label" for="pm-search-input"><?php esc_html_e( 'جستجو در محصولات', 'pakanmental-shell' ); ?></label>
					<span class="pm-search__icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
					</span>
					<input id="pm-search-input" class="pm-search__input" type="search" name="q" placeholder="<?php esc_attr_e( 'جستجوی محصول…', 'pakanmental-shell' ); ?>" autocomplete="off" enterkeyhint="search" />
					<div class="pm-search__results" hidden></div>
				</div>
				<a class="pm-header__icon-btn pm-cart" href="<?php echo esc_url( $cart ); ?>" data-pm-cart-open aria-label="<?php esc_attr_e( 'سبد خرید', 'pakanmental-shell' ); ?>" aria-haspopup="dialog" aria-controls="pm-mini-cart">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/><path d="M6 6L5 3H2"/></svg>
				</a>
			</div>
		</div>
	</div>
	<div id="pm-drawer" class="pm-drawer" data-pm-drawer hidden>
		<div class="pm-drawer__backdrop" data-pm-drawer-close tabindex="-1" aria-hidden="true"></div>
		<aside class="pm-drawer__panel" role="dialog" aria-label="<?php esc_attr_e( 'منوی موبایل', 'pakanmental-shell' ); ?>">
			<div class="pm-drawer__head">
				<span class="pm-drawer__title"><?php esc_html_e( 'منوی پاکان منتال', 'pakanmental-shell' ); ?></span>
				<button type="button" class="pm-drawer__close" data-pm-drawer-close aria-label="<?php esc_attr_e( 'بستن', 'pakanmental-shell' ); ?>">×</button>
			</div>
			<div class="pm-drawer__search">
				<div class="pm-search pm-search--inline">
					<label class="pm-search__label" for="pm-search-input-mobile"><?php esc_html_e( 'جستجو', 'pakanmental-shell' ); ?></label>
					<span class="pm-search__icon" aria-hidden="true">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>
					</span>
					<input id="pm-search-input-mobile" class="pm-search__input" type="search" name="q" placeholder="<?php esc_attr_e( 'جستجوی محصول…', 'pakanmental-shell' ); ?>" autocomplete="off" />
					<div class="pm-search__results" hidden></div>
				</div>
			</div>
			<nav class="pm-drawer__links">
				<a class="pm-drawer__link<?php echo is_front_page() ? ' is-active' : ''; ?>" href="<?php echo esc_url( $home ); ?>"><?php esc_html_e( 'خانه', 'pakanmental-shell' ); ?></a>
				<a class="pm-drawer__link<?php echo $is_shop ? ' is-active' : ''; ?>" href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'فروشگاه', 'pakanmental-shell' ); ?></a>
				<a class="pm-drawer__link" href="<?php echo esc_url( $blog ); ?>"><?php esc_html_e( 'مجله', 'pakanmental-shell' ); ?></a>
				<a class="pm-drawer__link" href="<?php echo esc_url( $cart ); ?>" data-pm-cart-open><?php esc_html_e( 'سبد خرید', 'pakanmental-shell' ); ?></a>
				<a class="pm-drawer__link" href="<?php echo esc_url( $account ); ?>"><?php esc_html_e( 'حساب کاربری', 'pakanmental-shell' ); ?></a>
			</nav>
			<div class="pm-drawer__cats">
				<p class="pm-drawer__cats-title"><?php esc_html_e( 'دسته‌بندی‌ها', 'pakanmental-shell' ); ?></p>
				<div class="pm-mega__grid pm-drawer__mega-grid" data-pm-mega-grid>
					<p class="pm-mega__loading"><?php esc_html_e( 'در حال بارگذاری…', 'pakanmental-shell' ); ?></p>
				</div>
			</div>
		</aside>
	</div>
</header>
<div id="pm-mini-cart" class="pm-mini-cart" data-pm-mini-cart hidden>
	<div class="pm-mini-cart__backdrop" data-pm-cart-close tabindex="-1" aria-hidden="true"></div>
	<aside class="pm-mini-cart__panel" role="dialog" aria-modal="true" aria-labelledby="pm-mini-cart-title">
		<header class="pm-mini-cart__head">
			<h2 id="pm-mini-cart-title" class="pm-mini-cart__title"><?php esc_html_e( 'سبد خرید', 'pakanmental-shell' ); ?></h2>
			<button type="button" class="pm-mini-cart__close" data-pm-cart-close aria-label="<?php esc_attr_e( 'بستن', 'pakanmental-shell' ); ?>">×</button>
		</header>
		<div class="pm-mini-cart__body" data-pm-mini-cart-body>
			<p class="pm-mini-cart__loading"><?php esc_html_e( 'در حال بارگذاری…', 'pakanmental-shell' ); ?></p>
		</div>
		<footer class="pm-mini-cart__foot">
			<p class="pm-mini-cart__subtotal"><span><?php esc_html_e( 'جمع', 'pakanmental-shell' ); ?></span> <strong data-pm-mini-cart-subtotal>۰</strong></p>
			<div class="pm-mini-cart__actions">
				<a class="pm-btn pm-btn--ghost" href="<?php echo esc_url( $cart ); ?>"><?php esc_html_e( 'مشاهده سبد', 'pakanmental-shell' ); ?></a>
				<a class="pm-btn pm-btn--primary" href="<?php echo esc_url( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>"><?php esc_html_e( 'تسویه حساب', 'pakanmental-shell' ); ?></a>
			</div>
		</footer>
	</aside>
</div>
