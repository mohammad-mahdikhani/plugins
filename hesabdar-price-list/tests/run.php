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

echo "== Name / barcode fallback ==\n";
$row  = array( 'sku' => '', 'product_id' => '', 'barcode' => '6260001', 'name' => '', 'regular_price' => '199000', 'sale_price' => '', 'stock' => '' );
$item = ( new HPL_Updater( hpl_catalog() ) )->plan_row( $row );
hpl_assert( 'changed' === $item['status'] && '11' === $item['product_id'], 'match by barcode' );
$row  = array( 'sku' => '', 'product_id' => '', 'barcode' => '', 'name' => 'قند شکسته', 'regular_price' => '61000', 'sale_price' => '', 'stock' => '' );
$item = ( new HPL_Updater( hpl_catalog() ) )->plan_row( $row );
hpl_assert( 'changed' === $item['status'] && '14' === $item['product_id'], 'match by name' );

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
