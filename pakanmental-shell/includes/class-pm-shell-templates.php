<?php
/**
 * Elementor document IDs for site-wide editable templates.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Shell_Templates {

	public const OPTION = 'pm_shell_template_ids';

	public const KEYS = array(
		'header'         => 'هدر',
		'footer'         => 'فوتر',
		'single_product' => 'تک محصول',
		'product_archive'=> 'آرشیو محصول',
		'shop'           => 'فروشگاه',
		'blog'           => 'بلاگ / مجله',
	);

	public static function defaults(): array {
		return array(
			'header'          => 124,
			'footer'          => 125,
			'single_product'  => 0,
			'product_archive' => 0,
			'shop'            => 15,
			'blog'            => 20,
		);
	}

	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), array_map( 'absint', $saved ) );
	}

	public static function id( string $key ): int {
		$all = self::all();
		return isset( $all[ $key ] ) ? (int) $all[ $key ] : 0;
	}

	public static function save( array $ids ): void {
		$out = self::defaults();
		foreach ( self::KEYS as $key => $_label ) {
			if ( isset( $ids[ $key ] ) ) {
				$out[ $key ] = absint( $ids[ $key ] );
			}
		}
		update_option( self::OPTION, $out, false );
	}

	public static function edit_url( int $post_id ): string {
		if ( $post_id < 1 ) {
			return '';
		}
		return admin_url( 'post.php?post=' . $post_id . '&action=elementor' );
	}

	/**
	 * Render an Elementor document by ID (library or page).
	 */
	public static function render( int $post_id ): void {
		if ( $post_id < 1 || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $post_id, true );
		if ( is_string( $content ) && '' !== $content ) {
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Ensure single-product + product-archive library templates exist and keep IDs in options.
	 */
	public static function ensure_library_templates(): void {
		if ( ! current_user_can( 'edit_posts' ) && ! wp_doing_cron() ) {
			// Still allow REST sync / activation hooks.
		}
		$ids = self::all();
		if ( $ids['single_product'] < 1 || 'publish' !== get_post_status( $ids['single_product'] ) ) {
			$ids['single_product'] = self::create_library_template(
				'PM Single Product',
				'<!-- PM: قالب ثابت تک‌محصول — در المنتور ویرایش کنید -->'
				. '<div class="pm-site" dir="rtl">[pm_single_product]</div>'
			);
		}
		if ( $ids['product_archive'] < 1 || 'publish' !== get_post_status( $ids['product_archive'] ) ) {
			$ids['product_archive'] = self::create_library_template(
				'PM Product Archive',
				'<!-- PM: قالب آرشیو/دسته محصول — در المنتور ویرایش کنید -->'
				. '<div class="pm-site" dir="rtl">[pm_product_archive]</div>'
			);
		}
		self::save( $ids );
	}

	private static function create_library_template( string $title, string $html ): int {
		$existing = get_page_by_title( $title, OBJECT, 'elementor_library' );
		if ( $existing instanceof WP_Post ) {
			self::seed_elementor_html( (int) $existing->ID, $html );
			return (int) $existing->ID;
		}

		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_status' => 'publish',
				'post_type'   => 'elementor_library',
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_template_type', 'page' );
		update_post_meta( $post_id, '_wp_page_template', 'elementor_canvas' );
		self::seed_elementor_html( (int) $post_id, $html );
		return (int) $post_id;
	}

	private static function seed_elementor_html( int $post_id, string $html ): void {
		$tree = array(
			array(
				'id'       => substr( md5( 'pm-shell-' . $post_id ), 0, 8 ),
				'elType'   => 'container',
				'isInner'  => false,
				'settings' => array(
					'content_width' => 'full',
					'padding'       => array(
						'unit'     => 'px',
						'top'      => '0',
						'right'    => '0',
						'bottom'   => '0',
						'left'     => '0',
						'isLinked' => true,
					),
				),
				'elements' => array(
					array(
						'id'         => substr( md5( 'pm-shell-html-' . $post_id ), 0, 8 ),
						'elType'     => 'widget',
						'widgetType' => 'html',
						'isInner'    => false,
						'settings'   => array( 'html' => $html ),
						'elements'   => array(),
					),
				),
			),
		);
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );
		delete_post_meta( $post_id, '_elementor_element_cache' );
	}
}
