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
		if ( $term ) {
			$query['category'] = array( $term->slug );
		}
	}

	return wc_get_products( $query );
}
