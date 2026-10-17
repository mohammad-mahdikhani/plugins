<?php
/**
 * Tests that do not need WordPress.
 *
 * Usage: php tests/run.php
 */

require __DIR__ . '/bootstrap.php';

$failed = 0;
$passed = 0;

function npbr_assert( $cond, $label ) {
	global $failed, $passed;
	if ( $cond ) {
		$passed++;
		echo "  OK  $label\n";
	} else {
		$failed++;
		echo "  FAIL  $label\n";
	}
}

echo "== Sanitizer secrets ==\n";
npbr_assert( false !== strpos( NPBR_Sanitizer::redact_secrets( 'card 6037991122334455 extra' ), '[CARD]' ), 'redact card' );
npbr_assert( false === strpos( NPBR_Sanitizer::redact_secrets( 'cvv:123' ), '123' ), 'redact cvv' );
npbr_assert( false !== strpos( NPBR_Sanitizer::redact_secrets( 'password=secret99' ), '[REDACTED]' ), 'redact password' );
npbr_assert( NPBR_Sanitizer::phone( '0912-345-6789' ) === '09123456789', 'phone digits' );
npbr_assert( NPBR_Sanitizer::is_payment_asset( 'https://nahal.website/wp-content/plugins/digipay/checkout.js' ), 'digipay is payment' );
npbr_assert( ! NPBR_Sanitizer::is_payment_asset( 'https://nahal.website/wp-content/themes/studiare/style.css' ), 'theme css is not payment' );

echo "== Failed resources ==\n";
$failed_list = NPBR_Sanitizer::failed_resources(
	array(
		array(
			'url'     => 'https://nahal.website/wp-content/plugins/woocommerce-digipay/assets/js/fields.js',
			'kind'    => 'resource',
			'tag'     => 'script',
			'status'  => 'load-error',
			'message' => 'resource failed',
		),
		array(
			'url'    => 'javascript:alert(1)',
			'kind'   => 'resource',
			'status' => 'bad',
		),
		'ignore-me',
	)
);
npbr_assert( 1 === count( $failed_list ), 'drops bad url' );
npbr_assert( ! empty( $failed_list[0]['payment'] ), 'marks payment script' );
npbr_assert( $failed_list[0]['url'] === 'https://nahal.website/wp-content/plugins/woocommerce-digipay/assets/js/fields.js', 'keeps url' );

echo "== Payload ==\n";
$empty = NPBR_Sanitizer::normalize_payload( array( 'note' => '   ' ) );
npbr_assert( false === $empty['ok'], 'empty note rejected' );

$shot = 'data:image/png;base64,' . base64_encode( 'not-really-png-but-long-enough-bytes-ok!!' );
$ok   = NPBR_Sanitizer::normalize_payload(
	array(
		'note'       => "فیلدهای کارت دیجی‌پی لود نشد.\nصفحه سفید ماند.",
		'name'       => 'علی',
		'phone'      => '09120000000',
		'pageUrl'    => 'https://nahal.website/checkout/',
		'pageTitle'  => 'پرداخت',
		'userAgent'  => 'Mozilla/5.0',
		'viewport'   => '390x844',
		'online'     => true,
		'failed'     => array(
			array(
				'url'    => 'https://nahal.website/wp-content/plugins/woocommerce/assets/js/frontend/checkout.min.js',
				'kind'   => 'resource',
				'status' => 'load-error',
			),
		),
		'errors'     => array( array( 'message' => 'wc is not defined', 'type' => 'error' ) ),
		'payment'    => array(
			'foundPaymentBox' => true,
			'emptyBox'        => true,
			'noMethods'       => true,
			'checkoutType'    => 'course',
			'methods'         => array(),
			'fields'          => array(),
		),
		'screenshot' => $shot,
		'capturedAt' => '2026-10-17T10:00:00.000Z',
	)
);
npbr_assert( true === $ok['ok'], 'valid payload accepted' );
npbr_assert( false !== strpos( $ok['data']['note'], 'دیجی‌پی' ), 'keeps persian note' );
npbr_assert( 1 === count( $ok['data']['failed'] ), 'one failed resource' );
npbr_assert( ! empty( $ok['data']['failed'][0]['payment'] ), 'checkout.js marked payment' );
npbr_assert( 0 === strpos( $ok['data']['screenshot'], 'data:image/png;base64,' ), 'keeps screenshot' );

$bad_shot = NPBR_Sanitizer::screenshot( 'data:text/html;base64,PGh0bWw+' );
npbr_assert( '' === $bad_shot, 'rejects non-image data uri' );

echo "== Report ==\n";
$html = NPBR_Report::html( $ok['data'] );
npbr_assert( false !== strpos( $html, 'فیلدهای کارت' ), 'html has note' );
npbr_assert( false !== strpos( $html, 'checkout.min.js' ), 'html has failed url' );
npbr_assert( false !== strpos( $html, '<img class="shot"' ), 'html embeds screenshot' );
npbr_assert( false === strpos( $html, '<script>' ), 'html has no script' );
npbr_assert( false !== strpos( NPBR_Report::summary( $ok['data'] ), '1 فایل لودنشده' ), 'summary counts failed' );
npbr_assert( false !== strpos( NPBR_Report::title( $ok['data'] ), 'پرداخت' ), 'title uses page' );

$json = NPBR_Report::public_json( $ok['data'] );
npbr_assert( empty( $json['screenshot'] ), 'public json drops screenshot bytes' );
npbr_assert( ! empty( $json['hasScreenshot'] ), 'public json flags screenshot' );

echo "== Telegram text ==\n";
$tg = NPBR_Telegram::text( $ok['data'] );
npbr_assert( false !== strpos( $tg, 'فیلدهای کارت' ), 'telegram includes note' );
npbr_assert( false !== strpos( $tg, 'checkout.min.js' ), 'telegram includes failed url' );
npbr_assert( false !== strpos( $tg, 'کنسول' ), 'telegram has console section' );
npbr_assert( false === NPBR_Telegram::enabled(), 'telegram disabled without settings' );

$http400 = NPBR_Sanitizer::failed_resources(
	array(
		array(
			'url'     => 'https://trustseal.enamad.ir/logo.aspx?id=592152&Code=abc',
			'kind'    => 'resource',
			'status'  => '400',
			'message' => 'Failed to load resource: the server responded with a status of 400',
			'tag'     => 'img',
		),
	)
);
npbr_assert( 1 === count( $http400 ), 'keeps enamad 400 url' );
npbr_assert( '400' === $http400[0]['status'], 'keeps http 400 status' );

echo "== Zip ==\n";
$zip_path = sys_get_temp_dir() . '/npbr-test-' . getmypid() . '.zip';
npbr_assert( NPBR_Zip::write( $ok['data'], $zip_path ), 'writes zip' );
$z = new ZipArchive();
npbr_assert( true === $z->open( $zip_path ), 'opens zip' );
npbr_assert( false !== $z->locateName( 'report.json' ), 'has report.json' );
npbr_assert( false !== $z->locateName( 'report.html' ), 'has report.html' );
npbr_assert( false !== $z->locateName( 'screenshot.png' ) || false !== $z->locateName( 'screenshot.jpg' ), 'has screenshot' );
$z->close();
@unlink( $zip_path );

$escaped = NPBR_Report::html(
	array(
		'note'      => '<img src=x onerror=alert(1)>',
		'pageTitle' => '<b>x</b>',
		'failed'    => array(),
		'errors'    => array(),
		'payment'   => array(),
	)
);
npbr_assert( false === strpos( $escaped, '<img src=x' ), 'escapes html in note' );

echo "\n$passed passed, $failed failed\n";
exit( $failed ? 1 : 0 );
