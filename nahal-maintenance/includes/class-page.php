<?php
/**
 * Public 3D maintenance page renderer.
 *
 * @package NahalMaintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NM_Page {

	/**
	 * @param array<string, mixed> $data Page data.
	 */
	public static function render( $data ) {
		$data = self::normalize( $data );
		$template = dirname( __DIR__ ) . '/templates/maintenance.php';
		if ( ! is_readable( $template ) ) {
			echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>تعمیر</title></head><body>سایت در حال تعمیر است.</body></html>';
			return;
		}
		include $template;
	}

	/**
	 * @param array<string, mixed> $data Page data.
	 * @return string
	 */
	public static function html( $data ) {
		ob_start();
		self::render( $data );
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $data Page data.
	 * @return array<string, mixed>
	 */
	public static function normalize( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$base = array(
			'brand'    => 'مجموعه نهال نیشابور',
			'kicker'   => 'در حال به‌روزرسانی',
			'title'    => 'کمی صبر کنید',
			'message'  => 'سایت برای چند ساعت در حال تعمیر و آماده‌سازی است.',
			'eta_text' => 'به‌زودی باز می‌گردیم',
			'phone'    => '',
			'address'  => '',
			'logo_url' => '',
			'css_href' => 'assets/frontend/maintenance.css',
			'js_src'   => 'assets/frontend/maintenance.js',
		);
		$out = array_merge( $base, $data );
		foreach ( $out as $key => $value ) {
			$out[ $key ] = is_scalar( $value ) ? (string) $value : '';
		}
		return $out;
	}

	/**
	 * Markup for one CSS 3D cube.
	 *
	 * @param string $extra Extra class names.
	 * @return string
	 */
	public static function cube( $extra = '' ) {
		$class = 'nm-cube' . ( $extra !== '' ? ' ' . $extra : '' );
		return '<div class="' . $class . '" aria-hidden="true">'
			. '<span class="nm-face nm-face-front"></span>'
			. '<span class="nm-face nm-face-back"></span>'
			. '<span class="nm-face nm-face-right"></span>'
			. '<span class="nm-face nm-face-left"></span>'
			. '<span class="nm-face nm-face-top"></span>'
			. '<span class="nm-face nm-face-bottom"></span>'
			. '</div>';
	}
}
