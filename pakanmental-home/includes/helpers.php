<?php
/**
 * Template helpers.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Output a picture element (WebP + fallback) for theme assets.
 *
 * @param string $relative_path Path under plugin assets/, e.g. 'images/logo.webp'.
 * @param string $alt           Alt text.
 * @param array  $attrs         width, height, class, loading, fetchpriority.
 */
function pm_picture( string $relative_path, string $alt, array $attrs = [] ): void {
	$base = PM_HOME_PATH . 'assets/' . ltrim( $relative_path, '/' );
	$url  = PM_HOME_URL . 'assets/' . ltrim( $relative_path, '/' );

	if ( preg_match( '/\.webp$/i', $relative_path ) ) {
		$webp_path = $base;
		$webp_url  = $url;
		$fallback_path = preg_replace( '/\.webp$/i', '.png', $base );
		$fallback_url  = preg_replace( '/\.webp$/i', '.png', $url );
	} else {
		$webp_path     = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $base );
		$webp_url      = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $url );
		$fallback_path = $base;
		$fallback_url  = $url;
	}

	$has_webp = file_exists( $webp_path );

	$width  = isset( $attrs['width'] ) ? (int) $attrs['width'] : 0;
	$height = isset( $attrs['height'] ) ? (int) $attrs['height'] : 0;
	$class  = isset( $attrs['class'] ) ? esc_attr( $attrs['class'] ) : '';
	$load   = isset( $attrs['loading'] ) ? esc_attr( $attrs['loading'] ) : 'lazy';
	$fetch  = isset( $attrs['fetchpriority'] ) ? ' fetchpriority="' . esc_attr( $attrs['fetchpriority'] ) . '"' : '';

	if ( ! file_exists( $fallback_path ) && ! $has_webp ) {
		return;
	}

	if ( ! file_exists( $fallback_path ) ) {
		$fallback_url = $webp_url;
	}

	echo '<picture>';
	if ( $has_webp ) {
		echo '<source type="image/webp" srcset="' . esc_url( $webp_url ) . '">';
	}
	echo '<img src="' . esc_url( $fallback_url ) . '" alt="' . esc_attr( $alt ) . '"';
	if ( $width ) {
		echo ' width="' . $width . '"';
	}
	if ( $height ) {
		echo ' height="' . $height . '"';
	}
	if ( $class ) {
		echo ' class="' . $class . '"';
	}
	echo ' loading="' . $load . '" decoding="async"' . $fetch . '>';
	echo '</picture>';
}

/**
 * Placeholder product cards until WooCommerce has products.
 *
 * @return array<int, array{title:string,badge:bool}>
 */
function pm_placeholder_products( int $count, string $prefix, bool $badge = false ): array {
	$items = array();
	for ( $i = 1; $i <= $count; $i++ ) {
		$items[] = array(
			'title' => $prefix . ' ' . $i,
			'badge' => $badge,
		);
	}
	return $items;
}

/**
 * WooCommerce products for a row (empty array if WC inactive).
 *
 * @param array{slug?:string,limit?:int,orderby?:string} $args
 * @return WC_Product[]
 */
function pm_get_row_products( array $args ): array {
	if ( ! function_exists( 'wc_get_products' ) ) {
		return array();
	}

	$query = array(
		'limit'   => isset( $args['limit'] ) ? (int) $args['limit'] : 8,
		'status'  => 'publish',
		'orderby' => isset( $args['orderby'] ) ? $args['orderby'] : 'date',
		'order'   => 'DESC',
	);

	if ( ! empty( $args['slug'] ) ) {
		$term = get_term_by( 'slug', $args['slug'], 'product_cat' );
		if ( ! $term ) {
			$term = get_term_by( 'slug', sanitize_title( $args['slug'] ), 'product_cat' );
		}
		if ( $term ) {
			$query['tax_query'] = array(
				array(
					'taxonomy'         => 'product_cat',
					'field'            => 'term_id',
					'terms'            => (int) $term->term_id,
					'include_children' => true,
				),
			);
		}
	}

	return wc_get_products( $query );
}

/**
 * Product for shortcodes (current single product or explicit ID).
 */
function pm_get_product_for_shortcode( int $product_id = 0 ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return null;
	}
	if ( $product_id > 0 ) {
		$product = wc_get_product( $product_id );
		return $product ? $product : null;
	}
	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_queried_object_id() );
		return $product ? $product : null;
	}
	global $product;
	if ( isset( $product ) && is_object( $product ) && method_exists( $product, 'get_id' ) ) {
		return $product;
	}
	return null;
}

function pm_product_promises_html(): string {
	return '<ul class="pm-product-promises">'
		. '<li class="pm-product-promise"><span class="pm-product-promise__icon" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7h11v10H3zM14 10h4l3 3v4h-7z"/><circle cx="7" cy="18" r="1.6"/><circle cx="18" cy="18" r="1.6"/></svg>'
		. '</span><span class="pm-product-promise__copy"><small>' . esc_html__( 'ارسال سریع', 'pakanmental-home' ) . '</small><strong>' . esc_html__( 'ارسال کالا در نیشابور', 'pakanmental-home' ) . '</strong><span>' . esc_html__( 'در کمترین زمان', 'pakanmental-home' ) . '</span></span></li>'
		. '<li class="pm-product-promise"><span class="pm-product-promise__icon" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20 12 12 4H5v7l8 8z"/><circle cx="8.5" cy="8.5" r="1"/></svg>'
		. '</span><span class="pm-product-promise__copy"><small>' . esc_html__( 'خرید مطمئن', 'pakanmental-home' ) . '</small><strong>' . esc_html__( 'تضمین قیمت', 'pakanmental-home' ) . '</strong><span>' . esc_html__( 'بهترین نرخ فروشگاه', 'pakanmental-home' ) . '</span></span></li>'
		. '<li class="pm-product-promise"><span class="pm-product-promise__icon" aria-hidden="true">'
		. '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4.5 4.5 0 0 0-6.4 6.4L4 17l3 3 4.3-4.3a4.5 4.5 0 0 0 6.4-6.4L15 12z"/></svg>'
		. '</span><span class="pm-product-promise__copy"><small>' . esc_html__( 'پس از خرید', 'pakanmental-home' ) . '</small><strong>' . esc_html__( 'پشتیبانی کامل قطعات', 'pakanmental-home' ) . '</strong><span>' . esc_html__( 'همراه شما می‌مانیم', 'pakanmental-home' ) . '</span></span></li>'
		. '</ul>';
}

/**
 * Parent WooCommerce product categories for the homepage grid.
 */
function pm_home_parent_categories_html(): string {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '<div class="pm-cats"></div>';
	}

	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		)
	);
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '<div class="pm-cats"></div>';
	}

	$default_id = (int) get_option( 'default_product_cat' );
	$html       = '<div class="pm-cats" data-pm-home-cats>';
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		if ( $default_id && (int) $term->term_id === $default_id ) {
			continue;
		}
		if ( 'uncategorized' === $term->slug || false !== strpos( $term->slug, 'دسته-بندی' ) ) {
			continue;
		}
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			$link = $shop_url;
		}
		$html .= sprintf(
			'<a class="pm-cat" href="%s"><span class="pm-cat__label">%s</span></a>',
			esc_url( $link ),
			esc_html( $term->name )
		);
	}
	$html .= '</div>';
	return $html;
}
