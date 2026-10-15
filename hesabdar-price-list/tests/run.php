<?php
/**
 * Run parser/matcher/updater tests without WordPress.
 *
 * Usage: php tests/run.php
 */

require __DIR__ . '/bootstrap.php';

$failed = 0;
$passed = 0;
$tmp    = sys_get_temp_dir() . '/hpl-tests-' . getmypid();
@mkdir( $tmp, 0755, true );

function hpl_assert( $cond, $label ) {
	global $failed, $passed;
	if ( $cond ) {
		$passed++;
		echo "  OK  $label\n";
	} else {
		$failed++;
		echo "  FAIL  $label\n";
	}
}

function hpl_catalog() {
	return HPL_Catalog::from_products(
		array(
			array( 'id' => 11, 'sku' => 'SKU-100', 'name' => 'روغن مایع ۱.۸ لیتر', 'regular_price' => '150000', 'sale_price' => '140000', 'stock' => '10', 'barcode' => '6260001' ),
			array( 'id' => 12, 'sku' => 'SKU-101', 'name' => 'برنج ایرانی ۱۰ کیلو', 'regular_price' => '800000', 'sale_price' => '', 'stock' => '8', 'barcode' => '6260002' ),
			array( 'id' => 13, 'sku' => 'SKU-102', 'name' => 'چای سیاه ۵۰۰ گرم', 'regular_price' => '200000', 'sale_price' => '190000', 'stock' => '30', 'barcode' => '6260003' ),
			array( 'id' => 14, 'sku' => 'SKU-200', 'name' => 'قند شکسته', 'regular_price' => '50000', 'sale_price' => '', 'stock' => '5' ),
		)
	);
}

echo "== Normalizer ==\n";
hpl_assert( HPL_Normalizer::digits( '۱۸۵۰۰۰' ) === '185000', 'Persian digits' );
hpl_assert( HPL_Normalizer::price( '۱٬۲۵۰٬۰۰۰ تومان' ) === '1250000', 'Persian price with separators' );
hpl_assert( HPL_Normalizer::price( '1,250,000' ) === '1250000', 'US thousands' );
hpl_assert( HPL_Normalizer::price( '' ) === '', 'empty price' );
hpl_assert( HPL_Normalizer::sku( ' sku-100 ' ) === 'SKU-100', 'sku trim/case' );
hpl_assert( HPL_Normalizer::name( 'برنج  ایرانی' ) === HPL_Normalizer::name( 'برنج ایرانی' ), 'name collapse space' );
hpl_assert( HPL_Normalizer::name( 'چاي سياه' ) === HPL_Normalizer::name( 'چای سیاه' ), 'yeh/kaf unification' );

echo "== CSV ==\n";
$csv = $tmp . '/prices.csv';
file_put_contents(
	$csv,
	"کد کالا,نام کالا,قیمت,قیمت ویژه,موجودی\nSKU-100,روغن مایع,185000,169000,40\nSKU-101,برنج ایرانی ۱۰ کیلو,920000,,12\nUNKNOWN,کالای جدید,1000,,1\n"
);
$parsed = HPL_Parser::parse( $csv, 'prices.csv' );
hpl_assert( 'csv' === $parsed->type, 'csv type' );
hpl_assert( 1 === count( $parsed->sheets ), 'one sheet' );
$map = HPL_Column_Mapper::detect( $parsed->sheets[0]->headers );
hpl_assert( 0 === $map['sku'], 'csv sku column' );
hpl_assert( 2 === $map['regular_price'], 'csv price column' );
hpl_assert( 3 === $map['sale_price'], 'csv sale column is not regular price' );
$rows = HPL_Column_Mapper::apply( $parsed->sheets[0], $map );
hpl_assert( 3 === count( $rows ), 'three data rows' );

$updater = new HPL_Updater( hpl_catalog(), array( 'update_stock' => true ) );
$plan    = $updater->plan( $rows );
hpl_assert( 3 === $plan['stats']['total'], 'plan total' );
hpl_assert( 2 === $plan['stats']['matched'], 'two matched' );
hpl_assert( 1 === $plan['stats']['missing'], 'one missing' );
hpl_assert( 2 === $plan['stats']['changed'], 'two changed' );

$changed = array_values(
	array_filter(
		$plan['items'],
		static function ( $i ) {
			return 'changed' === $i['status'];
		}
	)
);
hpl_assert( '185000' === $changed[0]['new_regular'], 'SKU-100 new price' );
hpl_assert( '150000' === $changed[0]['old_regular'], 'SKU-100 old price' );
hpl_assert( '920000' === $changed[1]['new_regular'], 'SKU-101 new price' );

$store  = array();
$writer = static function ( $item ) use ( &$store ) {
	$store[ $item['product_id'] ] = $item;
	return true;
};
$result = $updater->apply_slice( $plan['items'], $writer, 0, 50 );
hpl_assert( true === $result['done'], 'apply done' );
hpl_assert( 2 === $result['updated'], 'two products written' );
hpl_assert( '185000' === $store['11']['new_regular'], 'memory store SKU-100' );

echo "== XLSX ==\n";
$xlsx_path = $tmp . '/prices.xlsx';
$xlsx      = \Shuchkin\SimpleXLSXGen::fromArray(
	array(
		array( 'کد کالا', 'نام کالا', 'قیمت', 'قیمت ویژه' ),
		array( 'SKU-102', 'چای سیاه ۵۰۰ گرم', '210000', '0' ),
		array( 'SKU-200', 'قند شکسته', '55000', '' ),
	)
);
$xlsx->saveAs( $xlsx_path );
$parsed = HPL_Parser::parse( $xlsx_path, 'prices.xlsx' );
hpl_assert( 'xlsx' === $parsed->type, 'xlsx type' );
$map  = HPL_Column_Mapper::detect( $parsed->sheets[0]->headers );
$rows = HPL_Column_Mapper::apply( $parsed->sheets[0], $map );
$plan = ( new HPL_Updater( hpl_catalog() ) )->plan( $rows );
hpl_assert( 2 === $plan['stats']['changed'], 'xlsx two changes' );
$tea = $plan['items'][0];
hpl_assert( '210000' === $tea['new_regular'], 'tea regular' );
hpl_assert( '' === $tea['new_sale'], 'zero sale clears special price' );

echo "== PDF ==\n";
$pdf_path = $tmp . '/prices.pdf';
file_put_contents( $pdf_path, hpl_make_pdf( array( 'SKU Name Price', 'SKU-100 Oil 185000', 'SKU-101 Rice 920000' ) ) );
$parsed = HPL_Parser::parse( $pdf_path, 'prices.pdf' );
hpl_assert( 'pdf' === $parsed->type, 'pdf type' );
hpl_assert( count( $parsed->sheets[0]->rows ) >= 2, 'pdf has data rows' );
$map = HPL_Column_Mapper::detect( $parsed->sheets[0]->headers );
if ( -1 === $map['sku'] ) {
	$map['sku']           = 0;
	$map['name']          = 1;
	$map['regular_price'] = 2;
}
$rows = HPL_Column_Mapper::apply( $parsed->sheets[0], $map );
$plan = ( new HPL_Updater( hpl_catalog() ) )->plan( $rows );
hpl_assert( $plan['stats']['changed'] >= 2, 'pdf matched and changed prices' );

echo "== Flexible matching ==\n";
$flex = new HPL_Updater( hpl_catalog() );
$item = $flex->plan_row( array( 'sku' => 'SKU100', 'product_id' => '', 'barcode' => '', 'name' => '', 'regular_price' => '185000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '11' === $item['product_id'], 'SKU100 matches SKU-100' );
$item = $flex->plan_row( array( 'sku' => '100', 'product_id' => '', 'barcode' => '', 'name' => '', 'regular_price' => '185000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '11' === $item['product_id'], '100 matches SKU-100 prefix' );
$item = $flex->plan_row( array( 'sku' => '6260001', 'product_id' => '', 'barcode' => '', 'name' => '', 'regular_price' => '185000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '11' === $item['product_id'], 'barcode in sku column' );
$item = $flex->plan_row( array( 'sku' => '11', 'product_id' => '', 'barcode' => '', 'name' => '', 'regular_price' => '185000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '11' === $item['product_id'], 'product id in sku column' );
$item = $flex->plan_row( array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'روغن مایع', 'regular_price' => '185000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '11' === $item['product_id'], 'partial unique name' );
$item = $flex->plan_row( array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'چای سیاه', 'regular_price' => '210000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '13' === $item['product_id'], 'name without unit' );
$zeros = HPL_Catalog::from_products(
	array(
		array( 'id' => 21, 'sku' => '00100', 'name' => 'کالای صفر دار', 'regular_price' => '10', 'sale_price' => '', 'stock' => '1' ),
	)
);
$item = ( new HPL_Updater( $zeros ) )->plan_row( array( 'sku' => '100', 'product_id' => '', 'barcode' => '', 'name' => '', 'regular_price' => '20', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '21' === $item['product_id'], 'leading zeros stripped' );

$aqua = HPL_Catalog::from_products(
	array(
		array( 'id' => 31, 'sku' => 'AQ-401', 'name' => 'تصفیه آب آکواجوی مدل 401', 'regular_price' => '10000000', 'sale_price' => '', 'stock' => '2' ),
		array( 'id' => 32, 'sku' => 'AQ-402', 'name' => 'تصفیه آب آکواجوی مدل 402', 'regular_price' => '11000000', 'sale_price' => '', 'stock' => '2' ),
	)
);
$aqua_up = new HPL_Updater( $aqua );
$item    = $aqua_up->plan_row( array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'دستگاه تصفيه آب آکواجوی مدل 401', 'regular_price' => '13000000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '31' === $item['product_id'], 'aquajoy 401 arabic yeh / دستگاه / مدل' );
$item = $aqua_up->plan_row( array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'دستگاه تصفيه آب آکواجوی مدل 402', 'regular_price' => '14000000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '32' === $item['product_id'], 'aquajoy 402 not confused with 401' );
$long = HPL_Catalog::from_products(
	array(
		array( 'id' => 33, 'sku' => 'AQ-401H', 'name' => 'دستگاه تصفیه آب خانگی آکواجوی Aquajoy 401', 'regular_price' => '12000000', 'sale_price' => '', 'stock' => '1' ),
	)
);
$item = ( new HPL_Updater( $long ) )->plan_row( array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'دستگاه تصفيه آب آکواجوی مدل 401', 'regular_price' => '13000000', 'sale_price' => '', 'stock' => '' ) );
hpl_assert( '33' === $item['product_id'], 'aquajoy 401 matches longer shop title' );

echo "== Screening engine ==\n";
$screen_cat = HPL_Catalog::from_products(
	array(
		array( 'id' => 11, 'sku' => 'SKU-100', 'name' => 'روغن مایع ۱.۸ لیتر', 'regular_price' => '150000', 'sale_price' => '', 'stock' => '10' ),
		array( 'id' => 31, 'sku' => 'AQ-401', 'name' => 'تصفیه آب آکواجوی مدل 401', 'regular_price' => '10000000', 'sale_price' => '', 'stock' => '2' ),
		array( 'id' => 32, 'sku' => 'AQ-402', 'name' => 'تصفیه آب آکواجوی مدل 402', 'regular_price' => '11000000', 'sale_price' => '', 'stock' => '2' ),
	)
);
$screen = HPL_Screener::run(
	array(
		array( 'sku' => 'SKU-100', 'product_id' => '', 'barcode' => '', 'name' => 'روغن مایع', 'regular_price' => '185000', 'sale_price' => '', 'stock' => '40' ),
		array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'دستگاه تصفيه آب آکواجوی مدل 401', 'regular_price' => '13000000', 'sale_price' => '', 'stock' => '3' ),
		array( 'sku' => 'UNKNOWN-X', 'product_id' => '', 'barcode' => '', 'name' => 'کالای کاملا ناموجود در فروشگاه تست', 'regular_price' => '1000', 'sale_price' => '', 'stock' => '1' ),
	),
	$screen_cat,
	new HPL_Updater( $screen_cat )
);
hpl_assert( 2 === $screen['stats']['on_site'], 'screen finds two products on site' );
hpl_assert( 1 === $screen['stats']['not_on_site'] || 1 === $screen['stats']['review'], 'unknown row screened out of on-site' );
$create = HPL_Screener::prepare_create( array( 'sku' => 'BRAND-NEW', 'name' => 'کالای جدید تست', 'regular_price' => '5000', 'stock' => '8' ), 9 );
hpl_assert( 'create' === $create['status'] && 'کالای جدید تست' === $create['name'], 'prepare create from file row' );

echo "== Name / barcode fallback ==\n";
$row  = array( 'sku' => '', 'product_id' => '', 'barcode' => '6260001', 'name' => '', 'regular_price' => '199000', 'sale_price' => '', 'stock' => '' );
$item = ( new HPL_Updater( hpl_catalog() ) )->plan_row( $row );
hpl_assert( 'changed' === $item['status'] && '11' === $item['product_id'], 'match by barcode' );
$row  = array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'قند شکسته', 'regular_price' => '61000', 'sale_price' => '', 'stock' => '' );
$item = ( new HPL_Updater( hpl_catalog() ) )->plan_row( $row );
hpl_assert( 'changed' === $item['status'] && '14' === $item['product_id'], 'match by name' );

echo "== Telegram text prices ==\n";
$tg_text = "لیست قیمت ۱۵ مهر ۱۴۰۳\nدستگاه تصفيه آب آکواجوی مدل 401 : ۱۳٬۵۰۰٬۰۰۰ تومان\nSKU-100 روغن مایع 185,000\nآکواجوی 402\n14,000,000\nکد: AQ-500 نام: فیلتر قیمت: 2500000 موجودی: 7\n";
$tg_sheet = HPL_Telegram_Parser::parse_text( $tg_text );
hpl_assert( count( $tg_sheet->rows ) >= 4, 'telegram text yields price rows' );
$names = array();
foreach ( $tg_sheet->rows as $r ) {
	$names[] = $r[1];
}
$joined = implode( ' ', $names );
hpl_assert( false !== strpos( $joined, 'آکواجوی' ) || false !== strpos( $joined, 'آکواجوی' ), 'telegram keeps aquajoy name' );
$prices = array();
foreach ( $tg_sheet->rows as $r ) {
	$prices[] = $r[2];
}
hpl_assert( in_array( '13500000', $prices, true ), 'telegram persian price with separators' );
hpl_assert( in_array( '185000', $prices, true ), 'telegram sku line price' );
hpl_assert( in_array( '14000000', $prices, true ), 'telegram two-line name then price' );
$sku100 = false;
foreach ( $tg_sheet->rows as $r ) {
	if ( 'SKU-100' === $r[0] && '185000' === $r[2] ) {
		$sku100 = true;
	}
}
hpl_assert( $sku100, 'telegram keeps SKU-100' );
$labeled_ok = false;
foreach ( $tg_sheet->rows as $r ) {
	if ( 'AQ-500' === $r[0] && '2500000' === $r[2] && '7' === $r[3] && false !== strpos( $r[1], 'فیلتر' ) ) {
		$labeled_ok = true;
	}
}
hpl_assert( $labeled_ok, 'telegram labeled sku/price/stock' );
hpl_assert( HPL_Telegram_Parser::is_heading( 'لیست قیمت ۱۴۰۳/۰۷/۲۴' ), 'telegram heading skipped' );

HPL_Telegram::$store = array();
$item = HPL_Telegram::ingest_update(
	array(
		'update_id'    => 9,
		'channel_post' => array(
			'message_id' => 44,
			'date'       => 1700000000,
			'chat'       => array( 'id' => -1001, 'username' => 'pricelist', 'type' => 'channel' ),
			'text'       => "آکواجوی 401 : 13000000\nSKU-100 روغن 200000",
		),
	)
);
hpl_assert( $item && 'text' === $item['kind'] && $item['rows_guess'] >= 2, 'telegram ingest channel text' );
hpl_assert( 1 === count( HPL_Telegram::inbox() ), 'telegram inbox stores post' );

$html = '<div class="tgme_widget_message" data-post="pricelist/12"><div class="tgme_widget_message_text js-message_text">تصفيه آب آکواجوی مدل 401 : 13,500,000</div><div class="tgme_widget_message_footer">';
$public = HPL_Telegram::parse_public_html( $html, 'pricelist' );
hpl_assert( 1 === count( $public ) && $public[0]['rows_guess'] >= 1, 'telegram public html text' );
hpl_assert( 'pricelist' === HPL_Telegram::normalize_channel( 'https://t.me/s/pricelist' ), 'telegram channel link normalized' );

$mapped = HPL_Column_Mapper::detect( $tg_sheet->headers );
hpl_assert( 0 === $mapped['sku'] && 1 === $mapped['name'] && 2 === $mapped['regular_price'], 'telegram sheet maps columns' );
$rows = HPL_Column_Mapper::apply( $tg_sheet, $mapped );
$aqua_up = new HPL_Updater( $aqua );
$hit = $aqua_up->plan_row( $rows[0] );
hpl_assert( '31' === $hit['product_id'], 'telegram aquajoy row matches shop product' );

echo "== Sample export ==\n";
$bin = HPL_Export::sample_xlsx();
hpl_assert( strlen( $bin ) > 100 && 'PK' === substr( $bin, 0, 2 ), 'sample xlsx is zip' );
file_put_contents( $tmp . '/sample.xlsx', $bin );
$parsed = HPL_Parser::parse( $tmp . '/sample.xlsx', 'sample.xlsx' );
hpl_assert( count( $parsed->sheets[0]->rows ) >= 3, 'sample has products' );

echo "\n$passed passed, $failed failed\n";
exit( $failed > 0 ? 1 : 0 );

/**
 * Minimal text PDF for parser tests.
 *
 * @param string[] $lines Lines.
 * @return string
 */
function hpl_make_pdf( array $lines ) {
	$ops = "BT\n/F1 12 Tf\n50 750 Td\n";
	foreach ( $lines as $i => $line ) {
		$line = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $line );
		if ( $i > 0 ) {
			$ops .= "0 -18 Td\n";
		}
		$ops .= "($line) Tj\n";
	}
	$ops .= "ET\n";

	$objs   = array();
	$objs[] = '<< /Type /Catalog /Pages 2 0 R >>';
	$objs[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
	$objs[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
	$objs[] = '<< /Length ' . strlen( $ops ) . " >>\nstream\n" . $ops . 'endstream';
	$objs[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

	$pdf  = "%PDF-1.4\n";
	$xref = array( 0 );
	foreach ( $objs as $i => $body ) {
		$xref[] = strlen( $pdf );
		$pdf   .= ( $i + 1 ) . " 0 obj\n" . $body . "\nendobj\n";
	}
	$start = strlen( $pdf );
	$pdf  .= 'xref
0 ' . ( count( $objs ) + 1 ) . '
0000000000 65535 f 
';
	for ( $i = 1; $i <= count( $objs ); $i++ ) {
		$pdf .= sprintf( "%010d 00000 n \n", $xref[ $i ] );
	}
	$pdf .= "trailer\n<< /Size " . ( count( $objs ) + 1 ) . " /Root 1 0 R >>\nstartxref\n{$start}\n%%EOF\n";
	return $pdf;
}
