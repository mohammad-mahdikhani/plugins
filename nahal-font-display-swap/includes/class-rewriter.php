<?php
/**
 * Convert any font-display strategy to swap.
 *
 * @package NahalFontDisplaySwap
 */

if ( ! defined( 'NFDS_VERSION' ) && ! defined( 'ABSPATH' ) ) {
	// CLI / tests may load this file without WordPress.
}

class NFDS_Rewriter {

	const DISPLAY = 'swap';

	/**
	 * Hosts that serve stylesheet CSS for webfonts (not the font files).
	 *
	 * @var string[]
	 */
	private static $css_hosts = array(
		'fonts.googleapis.com',
		'fonts.bunny.net',
		'api.fontshare.com',
		'fonts.gstatic.com',
	);

	/**
	 * @param string $css CSS text.
	 * @return string
	 */
	public static function css( $css ) {
		if ( ! is_string( $css ) || '' === $css || false === stripos( $css, '@font-face' ) ) {
			return $css;
		}
		return (string) preg_replace_callback(
			'/@font-face\s*\{[^{}]*\}/is',
			array( __CLASS__, 'rewrite_font_face' ),
			$css
		);
	}

	/**
	 * @param array<int, string> $match Regex match.
	 * @return string
	 */
	public static function rewrite_font_face( $match ) {
		$block = $match[0];
		if ( preg_match( '/font-display\s*:\s*([^;}\n]+)/i', $block, $cur ) ) {
			$val = strtolower( trim( $cur[1] ) );
			$val = preg_replace( '/\s*!important/i', '', $val );
			$val = trim( $val, " \t;" );
			if ( 'swap' === $val ) {
				return $block;
			}
			return (string) preg_replace(
				'/font-display\s*:\s*[^;}\n]+/i',
				'font-display:swap',
				$block
			);
		}
		return (string) preg_replace( '/\{/', '{font-display:swap;', $block, 1 );
	}

	/**
	 * Force display=swap on a webfont CSS URL.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function font_css_url( $url ) {
		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}
		$decoded = str_replace( '&amp;', '&', $url );
		if ( ! self::is_font_css_url( $decoded ) ) {
			return $url;
		}
		$decoded = preg_replace( '/([?&])display=[^&#]*/i', '$1display=swap', $decoded );
		if ( null === $decoded ) {
			$decoded = $url;
		}
		if ( false === stripos( $decoded, 'display=' ) ) {
			$decoded .= ( false === strpos( $decoded, '?' ) ? '?' : '&' ) . 'display=swap';
		}
		if ( false !== strpos( $url, '&amp;' ) ) {
			$decoded = str_replace( '&', '&amp;', $decoded );
		}
		return $decoded;
	}

	/**
	 * @param string $url URL.
	 * @return bool
	 */
	public static function is_font_css_url( $url ) {
		if ( ! is_string( $url ) || false === stripos( $url, 'http' ) ) {
			return false;
		}
		$host_ok = false;
		foreach ( self::$css_hosts as $host ) {
			if ( false !== stripos( $url, $host ) ) {
				$host_ok = true;
				break;
			}
		}
		if ( ! $host_ok ) {
			return false;
		}
		if ( false !== stripos( $url, 'fonts.gstatic.com' ) && false === stripos( $url, '/css' ) ) {
			return false;
		}
		return (bool) preg_match( '#/(css2?|css)#i', $url );
	}

	/**
	 * @param string $html HTML document or fragment.
	 * @return string
	 */
	public static function html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}
		$html = preg_replace_callback(
			'/\b(?:href|src)\s*=\s*(["\'])([^"\']*)\1/i',
			array( __CLASS__, 'rewrite_html_attr' ),
			$html
		);
		$html = preg_replace_callback(
			'/@import\s+(?:url\(\s*)?(["\']?)([^"\')]+)(\1)\s*\)?/i',
			array( __CLASS__, 'rewrite_import' ),
			$html
		);
		$html = preg_replace_callback(
			'/(<style\b[^>]*>)(.*?)(<\/style>)/is',
			array( __CLASS__, 'rewrite_style_tag' ),
			$html
		);
		return is_string( $html ) ? $html : '';
	}

	/**
	 * @param array<int, string> $m Match.
	 * @return string
	 */
	public static function rewrite_html_attr( $m ) {
		return str_replace( $m[2], self::font_css_url( $m[2] ), $m[0] );
	}

	/**
	 * @param array<int, string> $m Match.
	 * @return string
	 */
	public static function rewrite_import( $m ) {
		return str_replace( $m[2], self::font_css_url( $m[2] ), $m[0] );
	}

	/**
	 * @param array<int, string> $m Match.
	 * @return string
	 */
	public static function rewrite_style_tag( $m ) {
		return $m[1] . self::css( $m[2] ) . $m[3];
	}

	/**
	 * @param string $css CSS.
	 * @return int Number of @font-face blocks.
	 */
	public static function font_face_count( $css ) {
		if ( ! is_string( $css ) || '' === $css ) {
			return 0;
		}
		preg_match_all( '/@font-face\s*\{/i', $css, $m );
		return isset( $m[0] ) ? count( $m[0] ) : 0;
	}
}
