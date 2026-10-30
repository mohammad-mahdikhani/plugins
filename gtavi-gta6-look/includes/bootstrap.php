<?php

defined( 'ABSPATH' ) || exit;

final class GTAVI_GTA6_Look_Bootstrap {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 120 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_action( 'woocommerce_before_single_product', array( __CLASS__, 'product_landing_open' ), 5 );
		add_action( 'woocommerce_after_single_product', array( __CLASS__, 'product_landing_close' ), 50 );
		add_filter( 'pre_get_document_title', array( __CLASS__, 'filter_404_title' ), 20 );
	}

	public static function enqueue_assets(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'gtavi-gta6-look',
			GTAVI_GTA6_LOOK_URL . 'assets/css/gta6-look.css',
			array(),
			GTAVI_GTA6_LOOK_VERSION
		);

		wp_enqueue_script(
			'gtavi-gta6-look',
			GTAVI_GTA6_LOOK_URL . 'assets/js/gta6-look.js',
			array(),
			GTAVI_GTA6_LOOK_VERSION,
			true
		);
	}

	/**
	 * @param string[] $classes
	 * @return string[]
	 */
	public static function body_class( array $classes ): array {
		$classes[] = 'gtavi-gta6-look';

		if ( is_singular( 'post' ) || is_home() || is_category() || is_tag() || is_author() || is_date() ) {
			$classes[] = 'gtavi-gta6-blog';
		}

		if ( function_exists( 'is_product' ) && is_product() ) {
			$classes[] = 'gtavi-gta6-product-landing';
		}

		if ( is_404() ) {
			$classes[] = 'gtavi-gta6-404';
		}

		return $classes;
	}

	public static function template_include( string $template ): string {
		if ( ! is_404() ) {
			return $template;
		}

		$custom = GTAVI_GTA6_LOOK_DIR . 'templates/gtavi-404.php';
		if ( is_readable( $custom ) ) {
			return $custom;
		}

		return $template;
	}

	public static function product_landing_open(): void {
		echo '<div class="gtavi-product-landing-shell">';
		echo '<div class="gtavi-product-landing-glow" aria-hidden="true"></div>';
	}

	public static function product_landing_close(): void {
		echo '</div>';
	}

	public static function filter_404_title( string $title ): string {
		if ( is_404() ) {
			return 'صفحه پیدا نشد | ' . get_bloginfo( 'name' );
		}

		return $title;
	}
}
