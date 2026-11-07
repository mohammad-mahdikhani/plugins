<?php
/**
 * Board-game product specs: age, players, time, type.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Game_Specs {

	public const META_AGE     = '_pm_game_age';
	public const META_PLAYERS = '_pm_game_players';
	public const META_TIME    = '_pm_game_time';
	public const META_TYPE    = '_pm_game_type';

	/**
	 * @return array<string, string>
	 */
	public static function type_choices(): array {
		return array(
			'strategic'    => __( 'استراتژیک', 'pakanmental-home' ),
			'competitive'  => __( 'رقابتی', 'pakanmental-home' ),
			'puzzle'       => __( 'معمایی', 'pakanmental-home' ),
			'mental'       => __( 'فکری', 'pakanmental-home' ),
		);
	}

	public static function init(): void {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
		add_action( 'admin_init', array( __CLASS__, 'ensure_attribute_terms' ), 30 );
		add_shortcode( 'pm_game_specs', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Default selectable values for each global attribute.
	 *
	 * @return array<string, array{name:string,slug:string,terms:array<int, string>}>
	 */
	public static function attribute_catalog(): array {
		return array(
			'pm-game-age'     => array(
				'name'  => __( 'سن', 'pakanmental-home' ),
				'slug'  => 'pm-game-age',
				'terms' => array( '۳+', '۴+', '۵+', '۶+', '۷+', '۸+', '۱۰+', '۱۲+', '۱۴+', '۱۶+', '۱۸+' ),
			),
			'pm-game-players' => array(
				'name'  => __( 'تعداد بازیکن', 'pakanmental-home' ),
				'slug'  => 'pm-game-players',
				'terms' => array( '۱', '۱–۲', '۲', '۲–۴', '۲–۵', '۲–۶', '۳–۵', '۳–۶', '۴–۸', '۱+' ),
			),
			'pm-game-time'    => array(
				'name'  => __( 'زمان ست', 'pakanmental-home' ),
				'slug'  => 'pm-game-time',
				'terms' => array( '۱۵ دقیقه', '۲۰ دقیقه', '۳۰ دقیقه', '۴۵ دقیقه', '۶۰ دقیقه', '۶۰–۹۰ دقیقه', '۹۰ دقیقه', '۱۲۰ دقیقه' ),
			),
			'pm-game-type'    => array(
				'name'  => __( 'نوع بازی', 'pakanmental-home' ),
				'slug'  => 'pm-game-type',
				'terms' => array( 'استراتژیک', 'رقابتی', 'معمایی', 'فکری', 'خانوادگی', 'کارتی', 'مهمانی' ),
			),
			'pm-game-size'    => array(
				'name'  => __( 'ابعاد جعبه', 'pakanmental-home' ),
				'slug'  => 'pm-game-size',
				'terms' => array(),
			),
		);
	}

	public static function ensure_attribute_terms(): void {
		if ( ! class_exists( 'WooCommerce' ) || ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}
		if ( get_transient( 'pm_game_attr_seeded_v2' ) ) {
			return;
		}
		foreach ( self::attribute_catalog() as $item ) {
			$attr_id = self::ensure_attribute( $item['name'], $item['slug'] );
			if ( $attr_id < 1 ) {
				continue;
			}
			$tax = wc_attribute_taxonomy_name( $item['slug'] );
			foreach ( $item['terms'] as $term_name ) {
				if ( ! term_exists( $term_name, $tax ) ) {
					wp_insert_term( $term_name, $tax );
				}
			}
		}
		set_transient( 'pm_game_attr_seeded_v2', 1, DAY_IN_SECONDS );
	}

	private static function ensure_attribute( string $name, string $slug ): int {
		$slug = wc_sanitize_taxonomy_name( $slug );
		foreach ( wc_get_attribute_taxonomies() as $tax ) {
			if ( $tax->attribute_name === $slug ) {
				return (int) $tax->attribute_id;
			}
		}
		$id = wc_create_attribute(
			array(
				'name'         => $name,
				'slug'         => $slug,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
			)
		);
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	public static function fields(): void {
		echo '<div class="options_group">';
		echo '<p class="form-field"><strong>' . esc_html__( 'ویژگی بازی', 'pakanmental-home' ) . '</strong></p>';

		woocommerce_wp_text_input(
			array(
				'id'          => self::META_AGE,
				'label'       => __( 'سن', 'pakanmental-home' ),
				'placeholder' => '۸+',
				'desc_tip'    => true,
				'description' => __( 'حداقل سن پیشنهادی', 'pakanmental-home' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => self::META_PLAYERS,
				'label'       => __( 'تعداد بازیکن', 'pakanmental-home' ),
				'placeholder' => '۲–۶',
				'desc_tip'    => true,
				'description' => __( 'تعداد بازیکن', 'pakanmental-home' ),
			)
		);
		woocommerce_wp_text_input(
			array(
				'id'          => self::META_TIME,
				'label'       => __( 'زمان هر ست', 'pakanmental-home' ),
				'placeholder' => '۴۵ دقیقه',
				'desc_tip'    => true,
				'description' => __( 'مدت تقریبی یک دست', 'pakanmental-home' ),
			)
		);
		woocommerce_wp_select(
			array(
				'id'      => self::META_TYPE,
				'label'   => __( 'نوع بازی', 'pakanmental-home' ),
				'options' => array( '' => __( 'انتخاب کنید', 'pakanmental-home' ) ) + self::type_choices(),
			)
		);
		echo '</div>';
	}

	/**
	 * @param WC_Product $product Product.
	 */
	public static function save( $product ): void {
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$keys = array( self::META_AGE, self::META_PLAYERS, self::META_TIME, self::META_TYPE );
		foreach ( $keys as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}
			$product->update_meta_data( $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
	}

	/**
	 * @param int $product_id Product ID.
	 * @return array<int, array{key:string,label:string,value:string}>
	 */
	public static function get_specs( int $product_id ): array {
		$age     = (string) get_post_meta( $product_id, self::META_AGE, true );
		$players = (string) get_post_meta( $product_id, self::META_PLAYERS, true );
		$time    = (string) get_post_meta( $product_id, self::META_TIME, true );
		$type    = (string) get_post_meta( $product_id, self::META_TYPE, true );
		$types   = self::type_choices();
		if ( isset( $types[ $type ] ) ) {
			$type = $types[ $type ];
		}

		$items = array(
			array( 'age', __( 'سن', 'pakanmental-home' ), $age ),
			array( 'players', __( 'بازیکن', 'pakanmental-home' ), $players ),
			array( 'time', __( 'زمان ست', 'pakanmental-home' ), $time ),
			array( 'type', __( 'نوع بازی', 'pakanmental-home' ), $type ),
		);

		$out = array();
		foreach ( $items as $item ) {
			if ( '' === $item[2] ) {
				continue;
			}
			$out[] = array(
				'key'   => $item[0],
				'label' => $item[1],
				'value' => $item[2],
			);
		}

		foreach ( self::woocommerce_attributes( $product_id ) as $attr ) {
			$dup = false;
			foreach ( $out as $existing ) {
				if ( $existing['label'] === $attr['label'] ) {
					$dup = true;
					break;
				}
			}
			if ( ! $dup ) {
				$out[] = $attr;
			}
		}
		return $out;
	}

	/**
	 * Visible WooCommerce product attributes (global taxonomies and custom attrs).
	 *
	 * @return array<int, array{key:string,label:string,value:string}>
	 */
	public static function woocommerce_attributes( int $product_id ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			return array();
		}

		$out = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( is_object( $attribute ) && method_exists( $attribute, 'get_visible' ) && ! $attribute->get_visible() ) {
				continue;
			}
			$taxonomy = is_object( $attribute ) ? $attribute->get_name() : '';
			$label    = wc_attribute_label( $taxonomy, $product );
			$value    = '';
			if ( is_object( $attribute ) && $attribute->is_taxonomy() ) {
				$terms = wc_get_product_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
				if ( is_array( $terms ) ) {
					$value = implode( '، ', array_map( 'strval', $terms ) );
				}
			} elseif ( is_object( $attribute ) ) {
				$opts = $attribute->get_options();
				if ( is_array( $opts ) ) {
					$value = implode( '، ', array_map( 'strval', $opts ) );
				}
			}
			$value = trim( $value );
			if ( '' === $value || '' === $label ) {
				continue;
			}
			$key   = sanitize_title( $taxonomy ? $taxonomy : $label );
			$key   = $key ? $key : 'attr';
			$out[] = array(
				'key'   => $key,
				'label' => $label,
				'value' => $value,
			);
		}
		return $out;
	}

	public static function render( int $product_id ): string {
		$specs = self::get_specs( $product_id );
		if ( ! $specs ) {
			return '';
		}
		$html = '<ul class="pm-game-specs" aria-label="' . esc_attr__( 'ویژگی بازی', 'pakanmental-home' ) . '">';
		foreach ( $specs as $spec ) {
			$html .= sprintf(
				'<li class="pm-game-spec pm-game-spec--%s"><span class="pm-game-spec__label">%s</span><span class="pm-game-spec__value">%s</span></li>',
				esc_attr( $spec['key'] ),
				esc_html( $spec['label'] ),
				esc_html( $spec['value'] )
			);
		}
		$html .= '</ul>';
		return $html;
	}

	/**
	 * @param array<string, string>|string $atts Attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'id' => '0' ), $atts, 'pm_game_specs' );
		$id   = (int) $atts['id'];
		if ( $id < 1 ) {
			$product = pm_get_product_for_shortcode( 0 );
			$id      = $product ? (int) $product->get_id() : 0;
		}
		return $id ? self::render( $id ) : '';
	}
}
