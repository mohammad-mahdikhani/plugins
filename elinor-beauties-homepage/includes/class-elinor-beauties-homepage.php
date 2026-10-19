<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

final class Elinor_Beauties_Homepage {

	public static function init(): void {
		add_action('init', [self::class, 'register_settings']);
		add_action('admin_menu', [self::class, 'register_admin_menu']);
		add_filter('template_include', [self::class, 'maybe_load_homepage_template'], 99);
		add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
	}

	public static function register_settings(): void {
		register_setting(
			'elinor_bh_settings',
			'elinor_bh_enabled',
			[
				'type'              => 'boolean',
				'default'           => true,
				'sanitize_callback' => static function ($value) {
					return (bool) $value;
				},
			]
		);
	}

	public static function is_enabled(): bool {
		return (bool) get_option('elinor_bh_enabled', true);
	}

	public static function register_admin_menu(): void {
		add_options_page(
			__('صفحه اصلی الینور', 'elinor-beauties-homepage'),
			__('صفحه اصلی Beauties', 'elinor-beauties-homepage'),
			'manage_options',
			'elinor-beauties-homepage',
			[self::class, 'render_settings_page']
		);
	}

	public static function render_settings_page(): void {
		if (!current_user_can('manage_options')) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e('صفحه اصلی Beauties — الینور استور', 'elinor-beauties-homepage'); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('elinor_bh_settings');
				$enabled = self::is_enabled();
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e('فعال‌سازی', 'elinor-beauties-homepage'); ?></th>
						<td>
							<label>
								<input type="checkbox" name="elinor_bh_enabled" value="1" <?php checked($enabled); ?> />
								<?php esc_html_e('نمایش قالب سفارشی در صفحه اصلی سایت', 'elinor-beauties-homepage'); ?>
							</label>
							<p class="description">
								<?php esc_html_e('پس از نصب پلاگین، این گزینه را فعال نگه دارید. برای بازگشت به قالب قبلی Woodmart، تیک را بردارید.', 'elinor-beauties-homepage'); ?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function maybe_load_homepage_template(string $template): string {
		if (!self::is_enabled() || !is_front_page()) {
			return $template;
		}

		$custom = ELINOR_BH_PATH . 'templates/homepage.php';
		return file_exists($custom) ? $custom : $template;
	}

	public static function enqueue_assets(): void {
		if (!self::is_enabled() || !is_front_page()) {
			return;
		}

		wp_enqueue_style(
			'elinor-bh-fonts',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;1,600&display=swap',
			[],
			null
		);

		wp_enqueue_style(
			'elinor-bh-homepage',
			ELINOR_BH_URL . 'assets/css/homepage.css',
			['elinor-bh-fonts'],
			ELINOR_BH_VERSION
		);

		wp_enqueue_script(
			'elinor-bh-homepage',
			ELINOR_BH_URL . 'assets/js/homepage.js',
			[],
			ELINOR_BH_VERSION,
			true
		);
	}

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
				'limit'    => $limit,
				'status'   => 'publish',
				'on_sale'  => true,
				'orderby'  => 'date',
				'order'    => 'DESC',
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

		$cards = [];
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
				'image' => $image ?: ELINOR_BH_URL . 'assets/img/category-fallback.svg',
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
