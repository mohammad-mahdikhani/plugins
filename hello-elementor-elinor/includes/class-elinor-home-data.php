<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

final class Elinor_Home_Data {

	/** @return array<int, WC_Product> */
	public static function get_featured_products(int $limit = 8): array {
		if (!function_exists('wc_get_products')) {
			return [];
		}

		$products = wc_get_products(
			[
				'limit'   => $limit,
				'status'  => 'publish',
				'orderby' => 'date',
				'order'   => 'DESC',
			]
		);

		return is_array($products) ? $products : [];
	}

	/** @return array<int, WC_Product> */
	public static function get_on_sale_products(int $limit = 1): array {
		if (!function_exists('wc_get_products')) {
			return [];
		}

		$products = wc_get_products(
			[
				'limit'   => $limit,
				'status'  => 'publish',
				'on_sale' => true,
				'orderby' => 'date',
				'order'   => 'DESC',
			]
		);

		if (!empty($products)) {
			return $products;
		}

		return self::get_featured_products($limit);
	}

	/**
	 * @return array<int, array{name: string, link: string, image: string}>
	 */
	public static function get_category_cards(int $limit = 4): array {
		if (!function_exists('get_terms')) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 30,
				'orderby'    => 'count',
				'order'      => 'DESC',
			]
		);

		if (is_wp_error($terms) || empty($terms)) {
			return [];
		}

		$fallback = ELINOR_HELLO_URI . '/assets/img/category-fallback.svg';
		$cards    = [];

		foreach ($terms as $term) {
			if ((int) $term->parent === 0) {
				continue;
			}
			if (count($cards) >= $limit) {
				break;
			}
			$thumb_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
			$image    = $thumb_id ? (string) wp_get_attachment_image_url($thumb_id, 'medium_large') : '';
			$cards[]  = [
				'name'  => $term->name,
				'link'  => get_term_link($term),
				'image' => $image ?: $fallback,
			];
		}

		return $cards;
	}

	/** @return array<int, WP_Post> */
	public static function get_magazine_posts(int $limit = 4): array {
		$posts = get_posts(
			[
				'numberposts' => $limit,
				'post_status' => 'publish',
			]
		);

		return is_array($posts) ? $posts : [];
	}

	public static function format_price_html($product): string {
		if (is_object($product) && method_exists($product, 'get_price_html')) {
			return $product->get_price_html();
		}

		return '';
	}
}
