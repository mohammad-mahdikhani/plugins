<?php
/**
 * Vazirmatn across the shop, and Persian digits in visible numbers.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Digits {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 5 );
		add_action( 'wp_head', array( __CLASS__, 'css' ), 99 );
		add_action( 'wp_footer', array( __CLASS__, 'script' ), 99 );
		add_filter( 'formatted_woocommerce_price', array( __CLASS__, 'digits' ), 20 );
		add_filter( 'woocommerce_get_stock_html', array( __CLASS__, 'digits' ), 20 );
		add_filter( 'style_loader_src', array( __CLASS__, 'asset_src' ), 20, 2 );
		add_filter( 'script_loader_src', array( __CLASS__, 'asset_src' ), 20, 2 );
	}

	public static function digits( $value ): string {
		return strtr(
			(string) $value,
			array(
				'0' => "\u{06F0}",
				'1' => "\u{06F1}",
				'2' => "\u{06F2}",
				'3' => "\u{06F3}",
				'4' => "\u{06F4}",
				'5' => "\u{06F5}",
				'6' => "\u{06F6}",
				'7' => "\u{06F7}",
				'8' => "\u{06F8}",
				'9' => "\u{06F9}",
			)
		);
	}

	public static function enqueue(): void {
		wp_enqueue_style(
			'pm-vazirmatn',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);
	}

	public static function css(): void {
		echo '<style id="pm-vazir-digits">'
			. 'html body,html body :is(p,a,li,label,button,input,select,textarea,h1,h2,h3,h4,h5,h6,td,th,strong,small,figcaption),'
			. 'html body .elementor-heading-title,html body .elementor-button,html body .elementor-widget-text-editor,'
			. 'html body .price,html body .woocommerce-Price-amount,html body .woocommerce-Price-currencySymbol,html body .pm-card__price{'
			. 'font-family:Vazirmatn,Vazir,Tahoma,sans-serif!important}'
			. '</style>';
	}

	public static function script(): void {
		echo <<<'JS'
<script id="pm-persian-digits">
(function () {
  var map = "\u06F0\u06F1\u06F2\u06F3\u06F4\u06F5\u06F6\u06F7\u06F8\u06F9";
  function fa(value) {
    return String(value).replace(/\d/g, function (digit) { return map[digit]; });
  }
  function skip(node) {
    if (!node || node.nodeType !== 1) return false;
    var tag = node.tagName;
    return tag === "SCRIPT" || tag === "STYLE" || tag === "TEXTAREA" || tag === "INPUT" || tag === "NOSCRIPT" || tag === "SVG" || node.isContentEditable;
  }
  function walk(node) {
    if (!node) return;
    if (node.nodeType === 3) {
      if (/\d/.test(node.nodeValue || "")) node.nodeValue = fa(node.nodeValue);
      return;
    }
    if (skip(node)) return;
    var kids = node.childNodes;
    for (var i = 0; i < kids.length; i++) walk(kids[i]);
  }
  function start() {
    walk(document.body);
    if (!window.MutationObserver || !document.body) return;
    var observer = new MutationObserver(function (records) {
      records.forEach(function (record) {
        if (record.type === "characterData") {
          var parent = record.target.parentElement;
          if (parent && !skip(parent) && /\d/.test(record.target.nodeValue || "")) {
            record.target.nodeValue = fa(record.target.nodeValue);
          }
          return;
        }
        record.addedNodes.forEach(walk);
      });
    });
    observer.observe(document.body, { subtree: true, childList: true, characterData: true });
  }
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", start);
  else start();
})();
</script>
JS;
	}

	public static function asset_src( $src, $handle ) {
		unset( $handle );
		if ( ! is_string( $src ) || ! str_contains( $src, '/plugins/pakanmental-home/' ) ) {
			return $src;
		}
		return str_replace( '/plugins/pakanmental-home/', '/plugins/pakanmental-home1/', $src );
	}
}
