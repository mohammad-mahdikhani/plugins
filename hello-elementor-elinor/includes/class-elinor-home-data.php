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
	 * First-level shop categories for the row under the slider.
	 *
	 * @return array<int, array{name: string, link: string, image: string, count: int}>
	 */
	public static function get_category_cards(int $limit = 8): array {
		$terms    = self::get_shop_parent_terms();
		$fallback = ELINOR_HELLO_URI . '/assets/img/category-fallback.svg';
		$cards    = [];

		foreach ($terms as $term) {
			if (count($cards) >= $limit) {
				break;
			}
			$thumb_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
			$image    = $thumb_id ? (string) wp_get_attachment_image_url($thumb_id, 'medium') : '';
			$link     = get_term_link($term);
			$cards[]  = [
				'name'  => $term->name,
				'link'  => is_wp_error($link) ? '#' : $link,
				'image' => $image ?: $fallback,
				'count' => (int) $term->count,
			];
		}

		return $cards;
	}

	/**
	 * Mega-menu columns: shop parent categories + children.
	 *
	 * @return array<int, array{name: string, link: string, children: array<int, array{name: string, link: string}>}>
	 */
	public static function get_mega_menu_columns(int $limit = 6): array {
		$parents = self::get_shop_parent_terms();
		$out     = [];

		foreach ($parents as $parent) {
			if (count($out) >= $limit) {
				break;
			}
			$plink = get_term_link($parent);
			$col   = [
				'name'     => $parent->name,
				'link'     => is_wp_error($plink) ? '#' : $plink,
				'children' => [],
			];

			$children = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'parent'     => $parent->term_id,
					'hide_empty' => true,
					'number'     => 8,
					'orderby'    => 'count',
					'order'      => 'DESC',
				]
			);

			if (!is_wp_error($children)) {
				foreach ($children as $child) {
					$clink             = get_term_link($child);
					$col['children'][] = [
						'name' => $child->name,
						'link' => is_wp_error($clink) ? '#' : $clink,
					];
				}
			}

			$out[] = $col;
		}

		return $out;
	}

	/** @return array<int, WP_Term> */
	private static function get_shop_parent_terms(): array {
		if (!function_exists('get_terms')) {
			return [];
		}

		$beauty = get_term_by('slug', 'beauty-personal-care', 'product_cat');
		$parent = ($beauty instanceof WP_Term) ? (int) $beauty->term_id : 0;

		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'parent'     => $parent,
				'hide_empty' => true,
				'number'     => 20,
				'orderby'    => 'count',
				'order'      => 'DESC',
			]
		);

		if (is_wp_error($terms) || empty($terms)) {
			$terms = get_terms(
				[
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'number'     => 12,
					'orderby'    => 'count',
					'order'      => 'DESC',
				]
			);
		}

		return is_array($terms) ? $terms : [];
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
