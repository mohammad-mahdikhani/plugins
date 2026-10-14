<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$download_nonce = wp_create_nonce( 'hpl_download' );
$sample_xlsx    = admin_url( 'admin-post.php?action=hpl_sample_xlsx&_wpnonce=' . $download_nonce );
$sample_csv     = admin_url( 'admin-post.php?action=hpl_sample_csv&_wpnonce=' . $download_nonce );
$export_current = admin_url( 'admin-post.php?action=hpl_export_current&_wpnonce=' . $download_nonce );
?>
<div class="wrap hpl-wrap" dir="rtl">
	<div class="hpl-hero">
		<div>
			<h1>لیست قیمت حسابدار</h1>
			<p>فایل اکسل یا PDF لیست قیمت جدید را آپلود کنید تا قیمت همه محصولات مطابق لیست جدید به‌روز شود.</p>
		</div>
		<div class="hpl-hero-actions">
			<a class="hpl-btn hpl-btn-ghost" href="<?php echo esc_url( $sample_xlsx ); ?>">دانلود نمونه اکسل</a>
			<a class="hpl-btn hpl-btn-ghost" href="<?php echo esc_url( $sample_csv ); ?>">دانلود نمونه CSV</a>
			<a class="hpl-btn hpl-btn-primary" href="<?php echo esc_url( $export_current ); ?>">خروجی قیمت‌های فعلی فروشگاه</a>
		</div>
	</div>

	<ol class="hpl-steps">
		<li class="is-active" data-step="1">آپلود فایل</li>
		<li data-step="2">تطبیق ستون‌ها</li>
		<li data-step="3">پیش‌نمایش تغییرات</li>
		<li data-step="4">اعمال قیمت‌ها</li>
	</ol>

	<div class="hpl-card" id="hpl-step-1">
		<div class="hpl-drop" id="hpl-drop">
			<input type="file" id="hpl-file" accept=".xlsx,.xls,.csv,.pdf,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv">
			<div class="hpl-drop-icon">⬆</div>
			<strong>فایل اکسل یا PDF لیست قیمت را اینجا رها کنید</strong>
			<span>xlsx ، xls ، csv یا pdf — حداکثر ۲۰ مگابایت</span>
			<button type="button" class="hpl-btn hpl-btn-primary" id="hpl-browse">انتخاب فایل</button>
		</div>
		<p class="hpl-hint">محصولات با <b>کد کالا (SKU)</b>، شناسه، بارکد یا نام یکسان پیدا می‌شوند و فقط قیمت ردیف‌های موجود در فایل عوض می‌شود.</p>
	</div>

	<div class="hpl-card is-hidden" id="hpl-step-2">
		<div class="hpl-card-head">
			<h2>تطبیق ستون‌ها</h2>
			<p id="hpl-file-meta"></p>
		</div>
		<div class="hpl-alert" id="hpl-warning" hidden></div>
		<label class="hpl-field">
			<span>برگه اکسل</span>
			<select id="hpl-sheet"></select>
		</label>
		<div class="hpl-map" id="hpl-map"></div>
		<div class="hpl-options">
			<label><input type="checkbox" id="hpl-update-regular" <?php checked( ! empty( $settings['update_regular'] ) ); ?>> به‌روزرسانی قیمت اصلی</label>
			<label><input type="checkbox" id="hpl-update-sale" <?php checked( ! empty( $settings['update_sale'] ) ); ?>> به‌روزرسانی قیمت ویژه</label>
			<label><input type="checkbox" id="hpl-update-stock" <?php checked( ! empty( $settings['update_stock'] ) ); ?>> به‌روزرسانی موجودی</label>
		</div>
		<div class="hpl-table-wrap">
			<table class="hpl-table" id="hpl-sample"><thead></thead><tbody></tbody></table>
		</div>
		<div class="hpl-actions">
			<button type="button" class="hpl-btn" id="hpl-back-1">بازگشت</button>
			<button type="button" class="hpl-btn hpl-btn-primary" id="hpl-do-preview">پیش‌نمایش تغییرات قیمت</button>
		</div>
	</div>

	<div class="hpl-card is-hidden" id="hpl-step-3">
		<div class="hpl-card-head">
			<h2>پیش‌نمایش قیمت‌های جدید</h2>
			<p>اگر همه چیز درست است، قیمت‌ها را اعمال کنید. تا قبل از این مرحله هیچ محصولی تغییر نمی‌کند.</p>
		</div>
		<div class="hpl-stats" id="hpl-stats"></div>
		<div class="hpl-filters" id="hpl-filters">
			<button type="button" class="hpl-btn is-on" data-filter="all">همه</button>
			<button type="button" class="hpl-btn" data-filter="changed">قابل تغییر</button>
			<button type="button" class="hpl-btn" data-filter="missing">پیدا نشده</button>
			<button type="button" class="hpl-btn" data-filter="unchanged">بدون تغییر</button>
		</div>
		<p class="hpl-hint" id="hpl-match-note"></p>
		<div class="hpl-table-wrap hpl-table-tall">
			<table class="hpl-table" id="hpl-preview">
				<thead>
					<tr>
						<th>وضعیت</th>
						<th>کد فایل</th>
						<th>محصول فروشگاه</th>
						<th>نحوه پیدا شدن</th>
						<th>قیمت فعلی</th>
						<th>قیمت جدید</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
		<div class="hpl-actions">
			<button type="button" class="hpl-btn" id="hpl-back-2">بازگشت</button>
			<button type="button" class="hpl-btn hpl-btn-danger" id="hpl-do-apply">اعمال همه قیمت‌های جدید</button>
		</div>
	</div>

	<div class="hpl-card is-hidden" id="hpl-step-4">
		<div class="hpl-result" id="hpl-result">
			<div class="hpl-progress"><span id="hpl-bar"></span></div>
			<p id="hpl-progress-text">در حال اعمال…</p>
		</div>
	</div>

	<div class="hpl-toast" id="hpl-toast" hidden></div>
</div>
