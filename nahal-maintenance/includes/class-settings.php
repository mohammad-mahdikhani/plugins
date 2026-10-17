<?php
/**
 * Settings defaults and sanitization.
 *
 * @package NahalMaintenance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NM_Settings {

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'enabled'       => false,
			'brand'         => 'مجموعه نهال نیشابور',
			'kicker'        => 'در حال به‌روزرسانی',
			'title'         => 'کمی صبر کنید',
			'message'       => 'سایت برای چند ساعت در حال تعمیر و آماده‌سازی است. به‌زودی با تجربه‌ای تازه‌تر برمی‌گردیم.',
			'eta_text'      => 'به‌زودی باز می‌گردیم',
			'phone'         => '05143331449',
			'address'       => 'نیشابور، خیابان ۱۵ خرداد ۲۶',
			'logo_url'      => '',
			'bypass_admins' => true,
			'bypass_key'    => '',
			'retry_after'   => 3600,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get() {
		$stored = get_option( 'nm_settings', array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return self::sanitize( array_merge( self::defaults(), $stored ) );
	}

	/**
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			return self::defaults();
		}
		$defaults = self::defaults();
		$out      = $defaults;

		$out['enabled']       = ! empty( $input['enabled'] );
		$out['bypass_admins'] = ! empty( $input['bypass_admins'] );
		$out['brand']         = self::text( isset( $input['brand'] ) ? $input['brand'] : $defaults['brand'], 120 );
		$out['kicker']        = self::text( isset( $input['kicker'] ) ? $input['kicker'] : $defaults['kicker'], 80 );
		$out['title']         = self::text( isset( $input['title'] ) ? $input['title'] : $defaults['title'], 80 );
		$out['message']       = self::text( isset( $input['message'] ) ? $input['message'] : $defaults['message'], 500 );
		$out['eta_text']      = self::text( isset( $input['eta_text'] ) ? $input['eta_text'] : $defaults['eta_text'], 120 );
		$out['phone']         = self::text( isset( $input['phone'] ) ? $input['phone'] : $defaults['phone'], 40 );
		$out['address']       = self::text( isset( $input['address'] ) ? $input['address'] : $defaults['address'], 160 );
		$out['logo_url']      = self::url( isset( $input['logo_url'] ) ? $input['logo_url'] : '' );
		$out['bypass_key']    = self::key( isset( $input['bypass_key'] ) ? $input['bypass_key'] : '' );

		$retry = isset( $input['retry_after'] ) ? (int) $input['retry_after'] : $defaults['retry_after'];
		if ( $retry < 60 ) {
			$retry = 60;
		}
		if ( $retry > 86400 ) {
			$retry = 86400;
		}
		$out['retry_after'] = $retry;

		return $out;
	}

	/**
	 * @param mixed $value Value.
	 * @param int   $max   Max length.
	 * @return string
	 */
	public static function text( $value, $max = 200 ) {
		$text = is_string( $value ) ? trim( wp_strip_all_tags( $value ) ) : '';
		if ( function_exists( 'sanitize_text_field' ) ) {
			$text = sanitize_text_field( $text );
		}
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $max );
		}
		return substr( $text, 0, $max );
	}

	/**
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function url( $value ) {
		$url = is_string( $value ) ? trim( $value ) : '';
		if ( $url === '' ) {
			return '';
		}
		if ( function_exists( 'esc_url_raw' ) ) {
			$url = esc_url_raw( $url );
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			return '';
		}
		return $url;
	}

	/**
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function key( $value ) {
		$key = is_string( $value ) ? trim( $value ) : '';
		$key = preg_replace( '/[^A-Za-z0-9_-]/', '', $key );
		if ( ! is_string( $key ) ) {
			return '';
		}
		return substr( $key, 0, 64 );
	}

	/**
	 * Data passed to the public page renderer.
	 *
	 * @param array<string, mixed>|null $settings Settings.
	 * @return array<string, mixed>
	 */
	public static function page_data( $settings = null ) {
		$s = is_array( $settings ) ? self::sanitize( $settings ) : self::get();
		$css = defined( 'NM_URL' ) ? NM_URL . 'assets/frontend/maintenance.css' : 'assets/frontend/maintenance.css';
		$js  = defined( 'NM_URL' ) ? NM_URL . 'assets/frontend/maintenance.js' : 'assets/frontend/maintenance.js';
		$ver = defined( 'NM_VERSION' ) ? NM_VERSION : '1.0.0';

		return array(
			'brand'    => $s['brand'],
			'kicker'   => $s['kicker'],
			'title'    => $s['title'],
			'message'  => $s['message'],
			'eta_text' => $s['eta_text'],
			'phone'    => $s['phone'],
			'address'  => $s['address'],
			'logo_url' => $s['logo_url'],
			'css_href' => $css . '?ver=' . rawurlencode( $ver ),
			'js_src'   => $js . '?ver=' . rawurlencode( $ver ),
		);
	}
}
