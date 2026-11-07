<?php
/**
 * Plugin Name: AquaSafe Hirabsun Sync
 * Description: دریافت مشخصات فنی محصول از hirabsun.com و نمایش شبیه صفحه محصول هیراب‌سان.
 * Version: 1.0.0
 * Author: AquaSafe
 * Requires Plugins: woocommerce
 * Text Domain: aquasafe-hirabsun-sync
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AquaSafe_Hirabsun_Sync {

	const META_URL        = '_as_hira_url';
	const META_SPECS      = '_as_hira_specs';
	const META_HIGHLIGHTS = '_as_hira_highlights';
	const META_SYNCED     = '_as_hira_synced_at';

	public static function init() {
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'admin_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'wp_ajax_as_hira_import', array( __CLASS__, 'ajax_import' ) );
		add_action( 'wp_head', array( __CLASS__, 'frontend_json' ), 25 );
	}

	public static function admin_fields() {
		global $post;
		echo '<div class="options_group as-hira-sync-panel">';
		woocommerce_wp_text_input(
			array(
				'id'          => self::META_URL,
				'label'       => 'لینک محصول در هیراب‌سان',
				'placeholder' => 'https://hirabsun.com/product/aj-3500/',
				'desc_tip'    => true,
				'description' => 'آدرس صفحه همان مدل در سایت هیراب‌سان. با دکمه زیر مشخصات فنی و ویژگی‌ها وارد می‌شوند.',
				'value'       => get_post_meta( $post->ID, self::META_URL, true ),
			)
		);
		$synced = get_post_meta( $post->ID, self::META_SYNCED, true );
		echo '<p class="form-field as-hira-sync-actions">';
		echo '<label>&nbsp;</label>';
		echo '<button type="button" class="button button-primary" id="as-hira-import-btn" data-product-id="' . esc_attr( (string) $post->ID ) . '">دریافت مشخصات از هیراب‌سان</button> ';
		echo '<span id="as-hira-import-status" style="margin-right:8px;color:#50575e;">';
		if ( $synced ) {
			echo 'آخرین بروزرسانی: ' . esc_html( $synced );
		}
		echo '</span></p></div>';
	}

	public static function save_fields( $post_id ) {
		if ( isset( $_POST[ self::META_URL ] ) ) {
			update_post_meta( $post_id, self::META_URL, esc_url_raw( wp_unslash( $_POST[ self::META_URL ] ) ) );
		}
	}

	public static function admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}
		$admin_js = <<<'JS'
(function ($) {
	$('#as-hira-import-btn').on('click', function () {
		var $btn = $(this);
		var $status = $('#as-hira-import-status');
		var url = $('#_as_hira_url').val();
		if (!url) {
			$status.text('ابتدا لینک هیراب‌سان را وارد کنید.');
			return;
		}
		$btn.prop('disabled', true);
		$status.text('در حال دریافت…');
		$.post(asHiraSync.ajax, {
			action: 'as_hira_import',
			nonce: asHiraSync.nonce,
			product_id: $btn.data('product-id'),
			url: url,
		})
			.done(function (res) {
				if (res.success) {
					$status.text(res.data.message + ' — ' + res.data.synced);
				} else {
					$status.text(res.data && res.data.message ? res.data.message : 'خطا در دریافت.');
				}
			})
			.fail(function () {
				$status.text('خطای ارتباط با سرور.');
			})
			.always(function () {
				$btn.prop('disabled', false);
			});
	});
})(jQuery);
JS;
		wp_enqueue_script( 'jquery' );
		wp_add_inline_script(
			'jquery',
			'var asHiraSync=' . wp_json_encode(
				array(
					'ajax'  => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'as_hira_import' ),
				)
			) . ';' . $admin_js,
			'after'
		);
	}

	public static function ajax_import() {
		check_ajax_referer( 'as_hira_import', 'nonce' );
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'message' => 'دسترسی کافی نیست.' ), 403 );
		}
		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$url        = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => 'شناسه محصول نامعتبر است.' ) );
		}
		if ( ! $url ) {
			$url = get_post_meta( $product_id, self::META_URL, true );
		}
		if ( ! $url || false === strpos( $url, 'hirabsun.com' ) ) {
			wp_send_json_error( array( 'message' => 'آدرس معتبر هیراب‌سان وارد کنید.' ) );
		}
		$result = self::fetch_and_parse( $url );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}
		update_post_meta( $product_id, self::META_URL, $url );
		update_post_meta( $product_id, self::META_SPECS, $result['specs'] );
		update_post_meta( $product_id, self::META_HIGHLIGHTS, $result['highlights'] );
		$now = wp_date( 'Y/m/d H:i' );
		update_post_meta( $product_id, self::META_SYNCED, $now );
		wp_send_json_success(
			array(
				'message'    => sprintf( 'تعداد %d مشخصه و %d ویژگی برجسته ذخیره شد.', count( $result['specs'] ), count( $result['highlights'] ) ),
				'specs'      => count( $result['specs'] ),
				'highlights' => count( $result['highlights'] ),
				'synced'     => $now,
			)
		);
	}

	/**
	 * @return array{specs:array<int,array{k:string,v:string}>,highlights:array<int,array{k:string,v:string}>}|WP_Error
	 */
	public static function fetch_and_parse( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 25,
				'user-agent' => 'AquaSafe-Hirabsun-Sync/1.0',
			)
		);
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		if ( 200 !== $code || '' === $body ) {
			return new WP_Error( 'as_hira_http', 'صفحه هیراب‌سان در دسترس نیست.' );
		}
		return self::parse_html( $body );
	}

	/**
	 * @return array{specs:array<int,array{k:string,v:string}>,highlights:array<int,array{k:string,v:string}>}
	 */
	public static function parse_html( $html ) {
		$specs      = array();
		$highlights = array();
		$seen       = array();

		if ( preg_match_all(
			'/<li\s+class="flex\s+items-center\s+justify-between"[^>]*>\s*<span[^>]*>(.*?)<\/span>\s*<span[^>]*>(.*?)<\/span>/su',
			$html,
			$matches,
			PREG_SET_ORDER
		) ) {
			foreach ( $matches as $m ) {
				$k = trim( wp_strip_all_tags( html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
				$v = trim( wp_strip_all_tags( html_entity_decode( $m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
				if ( '' === $k || '' === $v ) {
					continue;
				}
				if ( preg_match( '/^\d+$/u', $k ) && mb_strlen( $v ) < 4 ) {
					continue;
				}
				if ( in_array( $k, array( '3', '10', '72', 'ضمانت اصلالت' ), true ) ) {
					continue;
				}
				$key = $k . '|' . $v;
				if ( isset( $seen[ $key ] ) ) {
					continue;
				}
				$seen[ $key ] = 1;
				$specs[]      = array( 'k' => $k, 'v' => $v );
				if ( count( $specs ) >= 24 ) {
					break;
				}
			}
		}

		if ( preg_match_all(
			'/<span\s+class="text-color_itm-color1[^"]*mb-2[^"]*"[^>]*>(.*?)<\/span>\s*<span\s+class="block\s+text-color_itm-color2[^"]*"[^>]*>(.*?)<\/span>/su',
			$html,
			$hm,
			PREG_SET_ORDER
		) ) {
			foreach ( $hm as $row ) {
				$k = trim( wp_strip_all_tags( html_entity_decode( $row[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
				$v = trim( wp_strip_all_tags( html_entity_decode( $row[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
				if ( $k && $v ) {
					$highlights[] = array( 'k' => $k, 'v' => $v );
				}
			}
		}

		return array(
			'specs'      => $specs,
			'highlights' => array_slice( $highlights, 0, 6 ),
		);
	}

	public static function frontend_json() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$product_id = get_queried_object_id();
		$specs      = get_post_meta( $product_id, self::META_SPECS, true );
		if ( ! is_array( $specs ) || ! $specs ) {
			return;
		}
		$highlights = get_post_meta( $product_id, self::META_HIGHLIGHTS, true );
		if ( ! is_array( $highlights ) ) {
			$highlights = array();
		}
		$data = array(
			'specs'      => $specs,
			'highlights' => $highlights,
			'source'     => get_post_meta( $product_id, self::META_URL, true ),
		);
		echo '<script type="application/json" id="as-hira-data">' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}

AquaSafe_Hirabsun_Sync::init();
