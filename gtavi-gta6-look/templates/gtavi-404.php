<?php
/**
 * Custom 404 — Vice City minimal (inspired by gta-6.ir).
 */

defined( 'ABSPATH' ) || exit;

status_header( 404 );
nocache_headers();

$home_url = home_url( '/' );
$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : $home_url;
$blog_url = get_post_type_archive_link( 'post' );
if ( ! $blog_url ) {
	$blog_url = $home_url;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'gtavi-gta6-look gtavi-gta6-404 gtavi-404-standalone' ); ?>>
<?php wp_body_open(); ?>
<main class="gtavi-404" role="main">
	<div class="gtavi-404__panel">
		<p class="gtavi-404__code" aria-hidden="true">404</p>
		<h1 class="gtavi-404__title">اینجا خیابان بن‌بسته است</h1>
		<p class="gtavi-404__desc">
			صفحه‌ای که دنبالش بودید وجود ندارد یا جابه‌جا شده. مثل Vice City، مسیر درست همیشه یک کلیک دورتر است.
		</p>
		<div class="gtavi-404__actions">
			<a class="gtavi-btn gtavi-btn--primary" href="<?php echo esc_url( $home_url ); ?>">بازگشت به خانه</a>
			<a class="gtavi-btn gtavi-btn--ghost" href="<?php echo esc_url( $shop_url ); ?>">مشاهده محصولات GTA VI</a>
			<a class="gtavi-btn gtavi-btn--ghost" href="<?php echo esc_url( $blog_url ); ?>">اخبار و بلاگ</a>
		</div>
		<p class="gtavi-404__watermark"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ?: 'GTAVI' ); ?></p>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
