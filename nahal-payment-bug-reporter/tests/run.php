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
npbr_assert( NPBR_Sanitizer::is_payment_asset( 'https://nahal.website/wp-content/plugins/woocommerce/assets/js/frontend/cart-fragments.min.js' ), 'cart-fragments is journey asset' );
npbr_assert( NPBR_Sanitizer::is_payment_asset( 'https://nahal.website/?add-to-cart=12' ), 'add-to-cart is journey asset' );
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
		'lastName'   => 'محمدی',
		'phone'      => '09120000000',
		'device'     => array(
			'label'     => 'اندروید (موبایل)',
			'userAgent' => 'Mozilla/5.0',
			'viewport'  => '390x844',
			'screen'    => '390x844',
			'touch'     => true,
			'connection'=> '4g',
		),
		'customer'   => array(
			'loggedIn'  => true,
			'firstName' => 'علی',
			'lastName'  => 'محمدی',
			'phone'     => '09120000000',
			'login'     => '09120000000',
			'orders'    => array(
				array(
					'id'     => '101',
					'number' => '101',
					'status' => 'completed',
					'total'  => '۱٬۹۹۰٬۰۰۰ تومان',
					'date'   => '2026-10-01 12:00',
					'items'  => 'دوره لگو × 1',
				),
			),
		),
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
			'pageKind'        => 'checkout',
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
npbr_assert( false !== strpos( $html, '09120000000' ), 'html has contact phone' );
npbr_assert( false !== strpos( $html, 'محمدی' ), 'html has family name' );
npbr_assert( false !== strpos( $html, 'اندروید' ), 'html has device' );
npbr_assert( false !== strpos( $html, 'checkout.min.js' ), 'html has failed url' );
npbr_assert( false !== strpos( $html, '<img class="shot"' ), 'html embeds screenshot' );
npbr_assert( false === strpos( $html, '<script>' ), 'html has no script' );
npbr_assert( false !== strpos( NPBR_Report::summary( $ok['data'] ), '1 فایل لودنشده' ), 'summary counts failed' );
npbr_assert( false !== strpos( NPBR_Report::title( $ok['data'] ), 'پرداخت' ), 'title uses page' );

$json = NPBR_Report::public_json( $ok['data'] );
npbr_assert( empty( $json['screenshot'] ), 'public json drops screenshot bytes' );
npbr_assert( ! empty( $json['hasScreenshot'] ), 'public json flags screenshot' );

echo "== Agent prompt ==\n";
$prompt = NPBR_Report::agent_prompt( $ok['data'] );
npbr_assert( false !== strpos( $prompt, 'https://nahal.website/checkout/' ), 'prompt has page url' );
npbr_assert( false !== strpos( $prompt, 'فیلدهای کارت' ), 'prompt has user note' );
npbr_assert( false !== strpos( $prompt, 'checkout.min.js' ), 'prompt has failed url' );
npbr_assert( false !== strpos( $prompt, 'screenshot' ), 'prompt mentions screenshot' );
npbr_assert( false !== strpos( $prompt, 'ایجنت' ), 'prompt is for an agent' );
npbr_assert( false !== strpos( $prompt, 'nahal.website' ), 'prompt names the site' );
npbr_assert( false !== strpos( $prompt, '09120000000' ), 'prompt has phone' );
npbr_assert( false !== strpos( $prompt, 'تماس با کاربر' ), 'prompt has contact block' );
npbr_assert( false === strpos( $prompt, '<script>' ), 'prompt has no html script' );

echo "== Vital auto report ==\n";
npbr_assert( NPBR_Report::is_noise_asset( 'https://trustseal.enamad.ir/logo.aspx?id=1' ), 'enamad is noise' );
npbr_assert( NPBR_Report::is_vital_failure( $ok['data']['failed'][0] ), 'checkout.js is vital' );
npbr_assert( ! NPBR_Report::is_vital_failure( array( 'url' => 'https://trustseal.enamad.ir/logo.aspx?id=1', 'status' => '400' ) ), 'enamad 400 is not vital' );
$vitals = NPBR_Report::vital_failures( $ok['data'] );
npbr_assert( count( $vitals ) >= 1, 'vital list not empty' );
$listed = NPBR_Report::listed_note( $ok['data'] );
npbr_assert( false !== strpos( $listed, 'فهرست المان' ), 'listed heading' );
npbr_assert( false !== strpos( $listed, 'checkout.min.js' ), 'listed has failed url' );
npbr_assert( false === strpos( $listed, 'پرامپت آماده برای ایجنت' ), 'listed note does not nest prompt' );
npbr_assert( false !== strpos( $listed, '09120000000' ), 'listed has phone' );
npbr_assert( false !== strpos( $listed, 'محمدی' ), 'listed has family name' );
npbr_assert( false !== strpos( $listed, 'اندروید' ), 'listed has device' );
npbr_assert( false !== strpos( $listed, 'دوره لگو' ), 'listed has previous order' );

$shop_page = array(
	'payment' => array(
		'pageKind'        => 'product',
		'foundPaymentBox' => false,
		'emptyBox'        => true,
		'noMethods'       => true,
	),
	'failed'  => array(),
);
npbr_assert( 0 === count( NPBR_Report::vital_failures( $shop_page ) ), 'missing payment box is not vital on product page' );

$dev = NPBR_Sanitizer::device( array( 'label' => 'آیفون', 'userAgent' => 'Mozilla/5.0 iPhone', 'touch' => true ) );
npbr_assert( 'آیفون' === $dev['label'], 'device label kept' );
npbr_assert( ! empty( $dev['touch'] ), 'device touch kept' );
$cust = NPBR_Sanitizer::customer(
	array(
		'loggedIn'  => true,
		'firstName' => 'سارا',
		'lastName'  => 'رضایی',
		'phone'     => '0912-111-2233',
		'orders'    => array( array( 'id' => '9', 'number' => '9', 'status' => 'processing', 'total' => '10', 'items' => 'کتاب' ) ),
	)
);
npbr_assert( 'سارا' === $cust['firstName'], 'customer first name' );
npbr_assert( 'رضایی' === $cust['lastName'], 'customer last name' );
npbr_assert( '09121112233' === $cust['phone'], 'customer phone digits' );
npbr_assert( 1 === count( $cust['orders'] ), 'customer orders kept' );

$auto_ok = NPBR_Sanitizer::normalize_payload(
	array(
		'auto'    => true,
		'note'    => '',
		'pageUrl' => 'https://nahal.website/checkout/',
		'failed'  => array(
			array(
				'url'    => 'https://nahal.website/wp-content/plugins/woocommerce-digipay/assets/js/fields.js',
				'kind'   => 'resource',
				'status' => '404',
			),
		),
		'errors'  => array(),
		'payment' => array(
			'foundPaymentBox' => true,
			'emptyBox'        => false,
			'noMethods'       => false,
			'checkoutType'    => 'product',
			'methods'         => array( array( 'id' => 'zarinpal', 'label' => 'زرین پال' ) ),
			'fields'          => array(),
		),
	)
);
npbr_assert( true === $auto_ok['ok'], 'auto payload without user note accepted' );
npbr_assert( ! empty( $auto_ok['data']['auto'] ), 'auto flag kept' );
npbr_assert( false !== strpos( $auto_ok['data']['note'], 'فهرست المان' ), 'auto note is listed' );
npbr_assert( false !== strpos( $auto_ok['data']['note'], 'fields.js' ), 'auto note has vital url' );

$auto_noise = NPBR_Sanitizer::normalize_payload(
	array(
		'auto'    => true,
		'note'    => '',
		'pageUrl' => 'https://nahal.website/checkout/',
		'failed'  => array(
			array(
				'url'    => 'https://trustseal.enamad.ir/logo.aspx?id=1',
				'kind'   => 'resource',
				'status' => '400',
			),
		),
		'payment' => array(
			'foundPaymentBox' => true,
			'emptyBox'        => false,
			'noMethods'       => false,
			'methods'         => array( array( 'id' => 'zarinpal' ) ),
		),
	)
);
npbr_assert( false === $auto_noise['ok'], 'auto report ignored when only enamad failed' );

echo "== Weekly / monthly digest ==\n";
$key_a = NPBR_Stats::issue_key( $ok['data'] );
$key_b = NPBR_Stats::issue_key(
	array(
		'failed'  => array(
			array(
				'url'     => 'https://nahal.website/wp-content/plugins/woocommerce/assets/js/frontend/checkout.min.js?ver=2',
				'payment' => true,
			),
		),
		'pageUrl' => 'https://nahal.website/checkout/?foo=1',
	)
);
npbr_assert( $key_a === $key_b, 'same checkout.js groups together' );
$week = NPBR_Stats::period( 'week', strtotime( '2026-10-17 12:00:00' ) );
npbr_assert( $week['end'] - $week['start'] === 7 * 86400, 'week is 7 days' );
$month = NPBR_Stats::period( 'month', strtotime( '2026-10-17 12:00:00' ) );
npbr_assert( date( 'Y-m-d', $month['start'] ) === '2026-10-01', 'month starts on 1st' );
$prev = NPBR_Stats::period( 'last_month', strtotime( '2026-10-17 12:00:00' ) );
npbr_assert( date( 'Y-m', $prev['start'] ) === '2026-09', 'last month is September' );
$digest = NPBR_Stats::summarize(
	array(
		array(
			'status'     => 'open',
			'auto'       => true,
			'date'       => '2026-10-16 10:00:00',
			'pageUrl'    => 'https://nahal.website/checkout/',
			'issueKey'   => $key_a,
			'issueLabel' => 'اسکریپت checkout لود نشد',
		),
		array(
			'status'     => 'resolved',
			'auto'       => false,
			'date'       => '2026-10-15 10:00:00',
			'pageUrl'    => 'https://nahal.website/checkout/',
			'issueKey'   => $key_a,
			'issueLabel' => 'اسکریپت checkout لود نشد',
		),
		array(
			'status'     => 'open',
			'auto'       => false,
			'date'       => '2026-10-14 10:00:00',
			'pageUrl'    => 'https://nahal.website/cart/',
			'issueKey'   => 'page:https://nahal.website/cart/|سبد خالی',
			'issueLabel' => 'سبد خرید به‌روز نشد',
		),
	)
);
npbr_assert( 3 === $digest['total'], 'digest total' );
npbr_assert( 2 === $digest['open'], 'digest open' );
npbr_assert( 1 === $digest['resolved'], 'digest resolved' );
npbr_assert( 2 === count( $digest['issues'] ), 'two grouped issues' );
npbr_assert( 2 === (int) $digest['issues'][0]['count'], 'checkout issue counted twice' );
$text = NPBR_Stats::digest_text( $digest, 'گزارش هفتگی خطای خرید نهال' );
npbr_assert( false !== strpos( $text, 'رفع‌شده: 1' ), 'digest text has resolved' );
npbr_assert( false !== strpos( $text, 'سبد خرید' ), 'digest text has cart issue' );
npbr_assert( false !== strpos( $text, '2 بار — باز' ), 'grouped mixed issue stays open' );
$month_text = NPBR_Stats::digest_text( $digest, 'گزارش ماهانه خطای خرید نهال' );
npbr_assert( false !== strpos( $month_text, 'گزارش ماهانه' ), 'monthly digest title' );

echo "== Login / verified gate ==\n";
$req = array( 'require_login' => true, 'require_verified' => true );
$guest_gate = NPBR_Customer::can_report( array( 'loggedIn' => false, 'phone' => '' ), $req );
npbr_assert( false === $guest_gate['ok'] && 'guest' === $guest_gate['reason'], 'guest cannot report' );
$nophone = NPBR_Customer::can_report( array( 'loggedIn' => true, 'phone' => '' ), $req );
npbr_assert( false === $nophone['ok'] && 'unverified' === $nophone['reason'], 'logged in without phone blocked' );
$ok_user = NPBR_Customer::can_report( array( 'loggedIn' => true, 'phone' => '09120000000' ), $req );
npbr_assert( ! empty( $ok_user['ok'] ) && ! empty( $ok_user['verified'] ), 'logged in verified phone can report' );
npbr_assert( NPBR_Customer::has_verified_phone( '0912-000-0000' ), 'phone digits count as verified' );
npbr_assert( ! NPBR_Customer::has_verified_phone( '123' ), 'short phone is not verified' );

echo "== Telegram text ==\n";
$tg = NPBR_Telegram::text( $ok['data'] );
npbr_assert( false !== strpos( $tg, 'فیلدهای کارت' ), 'telegram includes note' );
npbr_assert( false !== strpos( $tg, 'checkout.min.js' ), 'telegram includes failed url' );
npbr_assert( false !== strpos( $tg, 'کنسول' ), 'telegram has console section' );
npbr_assert( false !== strpos( $tg, '09120000000' ), 'telegram has phone' );
npbr_assert( false !== strpos( $tg, 'محمدی' ), 'telegram has family name' );
npbr_assert( false === NPBR_Telegram::enabled(), 'telegram disabled without settings' );

echo "== Telegram filter bypass ==\n";
npbr_assert( NPBR_Telegram::build_url( 'https://npbr.example.workers.dev', '123:ABC', 'sendMessage' ) === 'https://npbr.example.workers.dev/bot123:ABC/sendMessage', 'worker base url' );
npbr_assert( NPBR_Telegram::build_url( 'https://api.telegram.org/', '1:x', 'sendPhoto' ) === 'https://api.telegram.org/bot1:x/sendPhoto', 'official url strips slash' );
npbr_assert( NPBR_Telegram::sanitize_api_base( 'https://foo.workers.dev/' ) === 'https://foo.workers.dev', 'strips trailing slash' );
npbr_assert( NPBR_Telegram::sanitize_api_base( 'https://foo.workers.dev/bot123:tok' ) === 'https://foo.workers.dev', 'strips pasted bot path' );
npbr_assert( NPBR_Telegram::sanitize_api_base( 'foo.workers.dev' ) === 'https://foo.workers.dev', 'adds https' );
npbr_assert( NPBR_Telegram::sanitize_api_base( 'javascript:alert(1)' ) === '', 'rejects javascript' );
npbr_assert( NPBR_Telegram::sanitize_api_base( 'https://user:pass@evil.example/' ) === '', 'rejects userinfo' );
npbr_assert( NPBR_Telegram::redact_url( 'https://api.telegram.org/bot123:SECRET/sendMessage' ) === 'https://api.telegram.org/bot***/sendMessage', 'redacts token in url' );
npbr_assert( NPBR_Telegram::sanitize_http_proxy( '127.0.0.1:8080' ) === '127.0.0.1:8080', 'host:port proxy' );
npbr_assert( NPBR_Telegram::sanitize_http_proxy( 'http://u:p@10.0.0.8:3128' ) === 'u:p@10.0.0.8:3128', 'user:pass proxy' );
npbr_assert( NPBR_Telegram::sanitize_http_proxy( 'not a proxy' ) === '', 'rejects junk proxy' );
$bases = NPBR_Telegram::api_bases( 'https://foo.workers.dev/' );
npbr_assert( isset( $bases[0] ) && 'https://foo.workers.dev' === $bases[0], 'custom base first' );
npbr_assert( in_array( 'https://api.telegram.org', $bases, true ), 'official fallback remains' );
npbr_assert( 2 === count( $bases ), 'no duplicate bases' );

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
npbr_assert( false !== $z->locateName( 'agent-prompt.md' ), 'has agent-prompt.md' );
npbr_assert( false !== $z->locateName( 'screenshot.png' ) || false !== $z->locateName( 'screenshot.jpg' ), 'has screenshot' );
$prompt_in_zip = $z->getFromName( 'agent-prompt.md' );
npbr_assert( false !== strpos( (string) $prompt_in_zip, 'فیلدهای کارت' ), 'zip prompt has note' );
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
