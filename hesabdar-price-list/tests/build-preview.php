<?php
/**
 * Build a static RTL preview of the admin UI using a real parse + price plan.
 */

require __DIR__ . '/bootstrap.php';

$catalog = HPL_Catalog::from_products(
	array(
		array( 'id' => 11, 'sku' => 'SKU-100', 'name' => 'روغن مایع ۱.۸ لیتر', 'regular_price' => '150000', 'sale_price' => '140000', 'stock' => '10' ),
		array( 'id' => 12, 'sku' => 'SKU-101', 'name' => 'برنج ایرانی ۱۰ کیلو', 'regular_price' => '800000', 'sale_price' => '', 'stock' => '8' ),
		array( 'id' => 13, 'sku' => 'SKU-102', 'name' => 'چای سیاه ۵۰۰ گرم', 'regular_price' => '200000', 'sale_price' => '190000', 'stock' => '30' ),
	)
);

$csv    = __DIR__ . '/fixtures/sample-price-list.csv';
$parsed = HPL_Parser::parse( $csv, 'list-gheymat-1404.csv' );
$map    = HPL_Column_Mapper::detect( $parsed->sheets[0]->headers );
$rows   = HPL_Column_Mapper::apply( $parsed->sheets[0], $map );
$plan   = ( new HPL_Updater( $catalog, array( 'update_stock' => true ) ) )->plan( $rows );

function hpl_e( $s ) {
	return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' );
}
function hpl_fa( $n ) {
	$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
	$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
	return str_replace( $en, $fa, number_format( (int) $n ) );
}

ob_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>پیش‌نمایش لیست قیمت حسابدار</title>
<link rel="stylesheet" href="../assets/admin/admin.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap">
<style>
body { margin: 0; background: #eef3f0; }
.hpl-preview-page { padding: 28px; }
</style>
</head>
<body>
<div class="hpl-preview-page">
<div class="hpl-wrap">
	<div class="hpl-hero">
		<div>
			<h1>لیست قیمت حسابدار</h1>
			<p>فایل اکسل لیست قیمت جدید آپلود شد. قیمت محصولات مطابق لیست جدید تغییر می‌کند.</p>
		</div>
	</div>
	<ol class="hpl-steps">
		<li class="is-done">آپلود فایل</li>
		<li class="is-done">تطبیق ستون‌ها</li>
		<li class="is-active">پیش‌نمایش تغییرات</li>
		<li>اعمال قیمت‌ها</li>
	</ol>
	<div class="hpl-card">
		<div class="hpl-card-head">
			<h2>پیش‌نمایش قیمت‌های جدید — <?php echo hpl_e( $parsed->filename ); ?></h2>
			<p>تا قبل از کلیک روی اعمال، هیچ محصولی در فروشگاه عوض نمی‌شود.</p>
		</div>
		<div class="hpl-stats">
			<div class="hpl-stat"><b><?php echo hpl_fa( $plan['stats']['total'] ); ?></b><span>کل ردیف‌ها</span></div>
			<div class="hpl-stat"><b><?php echo hpl_fa( $plan['stats']['matched'] ); ?></b><span>تطبیق‌شده</span></div>
			<div class="hpl-stat"><b><?php echo hpl_fa( $plan['stats']['changed'] ); ?></b><span>قابل تغییر</span></div>
			<div class="hpl-stat"><b><?php echo hpl_fa( $plan['stats']['unchanged'] ); ?></b><span>بدون تغییر</span></div>
			<div class="hpl-stat"><b><?php echo hpl_fa( $plan['stats']['missing'] ); ?></b><span>پیدا نشد</span></div>
		</div>
		<div class="hpl-table-wrap">
			<table class="hpl-table">
				<thead>
					<tr>
						<th>وضعیت</th>
						<th>کد</th>
						<th>محصول فروشگاه</th>
						<th>قیمت فعلی</th>
						<th>قیمت جدید</th>
						<th>ویژه فعلی</th>
						<th>ویژه جدید</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $plan['items'] as $item ) : ?>
					<tr class="is-<?php echo hpl_e( $item['status'] ); ?>">
						<td><?php echo $item['status'] === 'changed' ? 'تغییر می‌کند' : ( $item['status'] === 'missing' ? 'در فروشگاه نیست' : 'بدون تغییر' ); ?></td>
						<td><?php echo hpl_e( $item['sku'] ); ?></td>
						<td><?php echo hpl_e( $item['product_name'] ?: $item['name'] ); ?></td>
						<td><?php echo hpl_fa( $item['old_regular'] ); ?></td>
						<td><b><?php echo hpl_fa( $item['new_regular'] ); ?></b></td>
						<td><?php echo $item['old_sale'] === '' ? '—' : hpl_fa( $item['old_sale'] ); ?></td>
						<td><?php echo $item['new_sale'] === '' ? '—' : hpl_fa( $item['new_sale'] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="hpl-actions">
			<button type="button" class="hpl-btn">بازگشت</button>
			<button type="button" class="hpl-btn hpl-btn-danger">اعمال همه قیمت‌های جدید</button>
		</div>
	</div>
</div>
</div>
</body>
</html>
<?php
$html = ob_get_clean();
$out  = __DIR__ . '/preview-price-update.html';
file_put_contents( $out, $html );
echo "Wrote $out\n";
