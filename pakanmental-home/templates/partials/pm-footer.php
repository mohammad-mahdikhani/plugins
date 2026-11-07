<?php
/**
 * PM footer + mobile dock (used when Elementor footer section is empty).
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
$logo    = PM_HOME_URL . 'assets/images/logo-pakanmental.webp';
$active  = is_singular( 'product' ) || is_product_taxonomy() ? 'shop' : ( is_front_page() ? 'home' : '' );
?>
<footer class="pm-footer">
	<div class="pm-footer__glow" aria-hidden="true"></div>
	<div class="pm-container pm-footer__grid">
		<div class="pm-footer__brand">
			<a class="pm-footer__logo" href="<?php echo esc_url( $home ); ?>">
				<picture class="pm-logo__picture">
					<source type="image/webp" srcset="<?php echo esc_url( $logo ); ?>" />
					<img class="pm-logo__img" src="<?php echo esc_url( $logo ); ?>" width="160" height="64" alt="" loading="lazy" decoding="async" />
				</picture>
			</a>
			<p class="pm-footer__tagline"><?php esc_html_e( 'فروشگاه بازی فکری، بردگیم، پازل و سرگرمی رومیزی.', 'pakanmental-home' ); ?></p>
			<p class="pm-footer__brand-name"><?php esc_html_e( 'فروشگاه پاکان منتال', 'pakanmental-home' ); ?></p>
		</div>
		<div class="pm-footer__col">
			<h2 class="pm-footer__title"><?php esc_html_e( 'فروشگاه', 'pakanmental-home' ); ?></h2>
			<ul class="pm-footer__links">
				<li><a href="<?php echo esc_url( $shop ); ?>"><?php esc_html_e( 'همه محصولات', 'pakanmental-home' ); ?></a></li>
				<li><a href="<?php echo esc_url( $cart ); ?>"><?php esc_html_e( 'سبد خرید', 'pakanmental-home' ); ?></a></li>
			</ul>
		</div>
		<div class="pm-footer__col">
			<h2 class="pm-footer__title"><?php esc_html_e( 'مجله', 'pakanmental-home' ); ?></h2>
			<ul class="pm-footer__links">
				<li><a href="<?php echo esc_url( $blog ); ?>"><?php esc_html_e( 'مقالات', 'pakanmental-home' ); ?></a></li>
				<li><a href="<?php echo esc_url( $home ); ?>"><?php esc_html_e( 'صفحه اصلی', 'pakanmental-home' ); ?></a></li>
			</ul>
		</div>
		<div class="pm-footer__col pm-footer__enamad">
			<h2 class="pm-footer__title"><?php esc_html_e( 'نماد اعتماد', 'pakanmental-home' ); ?></h2>
			<div class="pm-footer__enamad-slot" id="pm-enamad" data-pm-enamad></div>
		</div>
	</div>
	<p class="pm-footer__copy"><?php esc_html_e( '© فروشگاه پاکان منتال — سرگرمی رومیزی برای هر سلیقه', 'pakanmental-home' ); ?></p>
</footer>
<nav class="pm-dock pm-mobile-only" aria-label="<?php esc_attr_e( 'ناوبری پایین موبایل', 'pakanmental-home' ); ?>">
	<a class="pm-dock__item<?php echo 'home' === $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $home ); ?>">
		<span class="pm-dock__icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><path d="M4 10.5L12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5z"/></svg></span>
		<span class="pm-dock__label"><?php esc_html_e( 'خانه', 'pakanmental-home' ); ?></span>
	</a>
	<a class="pm-dock__item<?php echo 'shop' === $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( $shop ); ?>">
		<span class="pm-dock__icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><path d="M4 10h16l-1.2 9H5.2L4 10z"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
		<span class="pm-dock__label"><?php esc_html_e( 'فروشگاه', 'pakanmental-home' ); ?></span>
	</a>
	<a class="pm-dock__item" href="<?php echo esc_url( $cart ); ?>" data-pm-cart-open>
		<span class="pm-dock__icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.2"/><circle cx="18" cy="20" r="1.2"/></svg><span class="pm-cart-count pm-cart-count--dock" data-pm-cart-count hidden aria-live="polite"></span></span>
		<span class="pm-dock__label"><?php esc_html_e( 'سبد خرید', 'pakanmental-home' ); ?></span>
	</a>
	<a class="pm-dock__item" href="<?php echo esc_url( $account ); ?>">
		<span class="pm-dock__icon-wrap"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.85" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M6 20v-1a6 6 0 0 1 12 0v1"/></svg></span>
		<span class="pm-dock__label"><?php esc_html_e( 'حساب کاربری', 'pakanmental-home' ); ?></span>
	</a>
</nav>
