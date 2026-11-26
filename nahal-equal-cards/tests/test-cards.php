<?php
/**
 * تست‌های کارت هم‌اندازه بدون وردپرس.
 * اجرا: php tests/test-cards.php
 */
define( 'ABSPATH', __DIR__ . '/../' );

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-nec-cards.php';

$pass = 0;
$fail = 0;
$check = function ( $label, $ok, $detail = '' ) use ( &$pass, &$fail ) {
	if ( $ok ) {
		$pass++;
		echo "  ok   — {$label}" . ( $detail ? " ({$detail})" : '' ) . "\n";
	} else {
		$fail++;
		echo "  FAIL — {$label}" . ( $detail ? " ({$detail})" : '' ) . "\n";
	}
};

$plugin = file_get_contents( dirname( __DIR__ ) . '/nahal-equal-cards.php' );
$css    = file_get_contents( dirname( __DIR__ ) . '/assets/css/cards.css' );
$js     = file_get_contents( dirname( __DIR__ ) . '/assets/js/cards.js' );
$php    = file_get_contents( dirname( __DIR__ ) . '/includes/class-nec-cards.php' );

echo "\n=== افزونه ===\n";
$check( 'هدر افزونه فارسی است', false !== strpos( $plugin, 'کارت‌های هم‌اندازه نهال' ) );
$check( 'کلاس کارت لود می‌شود', class_exists( 'NEC_Cards' ) );
$check( 'متن دکمه افزودن به سبد', 'افزودن به سبد خرید' === NEC_Cards::BUTTON_TEXT );

echo "\n=== شناسه محصول از کلاس ===\n";
$check(
	'post-6733 از کلاس کارت خوانده می‌شود',
	6733 === NEC_Cards::product_id_from_class( 'course-item product type-product post-6733 status-publish' )
);
$check( 'کلاس خالی صفر است', 0 === NEC_Cards::product_id_from_class( '' ) );
$check( 'بدون post صفر است', 0 === NEC_Cards::product_id_from_class( 'course-item product' ) );

echo "\n=== HTML دکمه ===\n";
$product = new class() {
	public function get_id() {
		return 6733;
	}
	public function get_type() {
		return 'simple';
	}
	public function get_permalink() {
		return 'https://nahal.website/product/al-6/';
	}
	public function add_to_cart_url() {
		return 'https://nahal.website/shop/?add-to-cart=6733';
	}
	public function is_purchasable() {
		return true;
	}
	public function is_in_stock() {
		return true;
	}
	public function get_sku() {
		return '';
	}
};
$html = NEC_Cards::button_markup( $product );
$check( 'دکمه کلاس nec-add-to-cart دارد', false !== strpos( $html, 'nec-add-to-cart' ) );
$check( 'دکمه ajax دارد', false !== strpos( $html, 'ajax_add_to_cart' ) );
$check( 'متن افزودن به سبد خرید', false !== strpos( $html, 'افزودن به سبد خرید' ) );
$check( 'شناسه محصول در data', false !== strpos( $html, 'data-product_id="6733"' ) );
$check( 'شیء خالی دکمه نمی‌سازد', '' === NEC_Cards::button_markup( null ) );

$variable = new class() {
	public function get_id() {
		return 10;
	}
	public function get_type() {
		return 'variable';
	}
	public function get_permalink() {
		return 'https://nahal.website/product/var/';
	}
	public function is_purchasable() {
		return true;
	}
	public function is_in_stock() {
		return true;
	}
};
$vhtml = NEC_Cards::button_markup( $variable );
$check( 'محصول متغیر ajax نیست', false === strpos( $vhtml, 'ajax_add_to_cart' ) );
$check( 'محصول متغیر مشاهده دوره است', false !== strpos( $vhtml, 'مشاهده دوره' ) );

echo "\n=== CSS ابعاد یکسان ===\n";
$check( 'نسبت تصویر دوره 370/270 است', false !== strpos( $css, 'aspect-ratio: 370 / 270' ) );
$check( 'تصویر cover می‌شود', false !== strpos( $css, 'object-fit: cover' ) );
$check( 'عنوان دوره دو خط clamp', false !== strpos( $css, '-webkit-line-clamp: 2' ) );
$check( 'کارت دوره فلکس ستونی است', false !== strpos( $css, '.course-item-inner' ) && false !== strpos( $css, 'flex-direction: column' ) );
$check( 'اوورلی inprolay3 صاف می‌شود', false !== strpos( $css, 'inprolay3' ) && false !== strpos( $css, 'transform: none' ) );
$check( 'کارت اخبار هم نسبت تصویر دارد', false !== strpos( $css, '.blog-loop-view-grid .post-thumbnail' ) );
$check( 'دکمه رنگ برند نهال', false !== strpos( $css, '--primary_color' ) );
$check( 'دکمه شناور تم پنهان می‌شود', false !== strpos( $css, 'a.studi_add_in_shop' ) );

echo "\n=== JS ===\n";
$check( 'JS کارت .course-item را پیدا می‌کند', false !== strpos( $js, '.course-item' ) );
$check( 'JS شناسه post- را می‌خواند', false !== strpos( $js, 'post-(\\d+)' ) || false !== strpos( $js, 'post-(\\d+)' ) );
$check( 'JS دکمه تکراری نمی‌سازد', false !== strpos( $js, '.nec-add-to-cart' ) );
$check( 'JS اسلایدر owl را پوشش می‌دهد', false !== strpos( $js, 'initialized.owl.carousel' ) );

echo "\n=== نتیجه: {$pass} موفق، {$fail} ناموفق ===\n";
exit( $fail ? 1 : 0 );
