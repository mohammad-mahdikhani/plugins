<?php
/**
 * Plugin Name: AquaSafe Product Price Freshness
 * Description: حالت برجسته قیمت به‌روز با تاریخ شمسی همان روز در صفحات محصول
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function as_fresh_jalali_today() {
	$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'Asia/Tehran' );
	try {
		$dt = new DateTime( 'now', $tz );
	} catch ( Exception $e ) {
		$dt = new DateTime( 'now', new DateTimeZone( 'Asia/Tehran' ) );
	}
	$j     = as_fresh_gregorian_to_jalali( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );
	$month = array( '', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	$label = as_fresh_fa_digits( $j[2] ) . ' ' . $month[ $j[1] ] . ' ' . as_fresh_fa_digits( $j[0] );
	return array(
		'iso'   => $dt->format( 'Y-m-d' ),
		'label' => $label,
	);
}

function as_fresh_fa_digits( $n ) {
	return strtr( (string) $n, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
}

function as_fresh_gregorian_to_jalali( $gy, $gm, $gd ) {
	$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
	$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
	$days  = 355666 + ( 365 * $gy ) + (int) ( ( $gy2 + 3 ) / 4 ) - (int) ( ( $gy2 + 99 ) / 100 ) + (int) ( ( $gy2 + 399 ) / 400 ) + $gd + $g_d_m[ $gm - 1 ];
	$jy    = -1595 + ( 33 * (int) ( $days / 12053 ) );
	$days %= 12053;
	$jy   += 4 * (int) ( $days / 1461 );
	$days %= 1461;
	if ( $days > 365 ) {
		$jy   += (int) ( ( $days - 1 ) / 365 );
		$days  = ( $days - 1 ) % 365;
	}
	if ( $days < 186 ) {
		$jm = 1 + (int) ( $days / 31 );
		$jd = 1 + ( $days % 31 );
	} else {
		$jm = 7 + (int) ( ( $days - 186 ) / 30 );
		$jd = 1 + ( ( $days - 186 ) % 30 );
	}
	return array( $jy, $jm, $jd );
}

function as_fresh_spotlight_html() {
	$d = as_fresh_jalali_today();
	return '<span class="as-price-spotlight"><span class="as-price-spotlight__badge">قیمت به‌روز</span><time class="as-price-spotlight__date" datetime="' . esc_attr( $d['iso'] ) . '">آخرین به‌روزرسانی: ' . esc_html( $d['label'] ) . '</time></span>';
}

add_filter(
	'woocommerce_get_price_html',
	function ( $html, $product ) {
		if ( is_admin() || ! $html ) {
			return $html;
		}
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $html;
		}
		if ( false !== strpos( $html, 'as-price-spotlight' ) ) {
			return $html;
		}
		return '<span class="as-price-wrap">' . $html . as_fresh_spotlight_html() . '</span>';
	},
	30,
	2
);

add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$html = as_fresh_spotlight_html();
		echo '<script id="as-price-fresh-js">
(function(){
  function place(){
    if (document.querySelector(".as-price-spotlight")) { return; }
    var html = ' . wp_json_encode( $html ) . ';
    var nodes = document.querySelectorAll(".elementor-widget-wl-single-product-price .price, .elementor-widget-woocommerce-product-price .price, body.single-product .summary p.price, body.single-product p.price");
    if (!nodes.length) { return; }
    nodes[0].insertAdjacentHTML("afterend", html);
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", place);
  else place();
})();
</script>';
	},
	40
);

add_action(
	'wp_head',
	function () {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		echo '<style id="as-price-fresh-css">
.single-product .as-price-wrap{display:flex;flex-direction:column;align-items:flex-start;gap:10px}
.single-product .as-price-spotlight{display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px 12px;background:linear-gradient(90deg,#eefbfc,#f7fcfd);border:1px solid #76cdd9;border-radius:999px;padding:6px 12px 6px 14px;box-shadow:0 8px 20px rgba(30,75,154,.08)}
.single-product .as-price-spotlight__badge{background:#1e4b9a;color:#fff;border-radius:999px;font-size:12px;font-weight:800;padding:4px 10px;line-height:1.6}
.single-product .as-price-spotlight__date{color:#1e4b9a;font-size:13px;font-weight:700}
.related .as-price-spotlight,.upsells .as-price-spotlight,.woocommerce-loop-product__price .as-price-spotlight{display:none!important}
</style>';
	},
	40
);
