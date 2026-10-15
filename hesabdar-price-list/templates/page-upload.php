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
			<p>لیست قیمت را از اکسل، PDF یا پست‌های کانال تلگرام بخوانید. برای تلگرام نام کالا و قیمت کافی است.</p>
		</div>
		<div class="hpl-hero-actions">
			<a class="hpl-btn hpl-btn-ghost" href="<?php echo esc_url( $sample_xlsx ); ?>">دانلود نمونه اکسل</a>
			<a class="hpl-btn hpl-btn-ghost" href="<?php echo esc_url( $sample_csv ); ?>">دانلود نمونه CSV</a>
			<a class="hpl-btn hpl-btn-primary" href="<?php echo esc_url( $export_current ); ?>">خروجی قیمت‌های فعلی فروشگاه</a>
		</div>
	</div>

	<ol class="hpl-steps">
		<li class="is-active" data-step="1">منبع لیست قیمت</li>
		<li data-step="2">تطبیق ستون‌ها</li>
		<li data-step="3">غربالگری هوشمند</li>
		<li data-step="4">اعمال قیمت و موجودی</li>
	</ol>

	<div class="hpl-card" id="hpl-step-1">
		<div class="hpl-drop" id="hpl-drop">
			<input type="file" id="hpl-file" accept=".xlsx,.xls,.csv,.pdf,application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv">
			<div class="hpl-drop-icon">⬆</div>
			<strong>فایل اکسل یا PDF لیست قیمت را اینجا رها کنید</strong>
			<span>xlsx ، xls ، csv یا pdf — حداکثر ۲۰ مگابایت</span>
			<button type="button" class="hpl-btn hpl-btn-primary" id="hpl-browse">انتخاب فایل</button>
		</div>
		<div class="hpl-or">یا</div>
		<div class="hpl-telegram" id="hpl-telegram">
			<div class="hpl-telegram-head">
				<strong>خواندن از کانال تلگرام</strong>
				<span>هر پست یک کالا است؛ نام و قیمت از متن پست خوانده می‌شود.</span>
			</div>
			<div class="hpl-actions">
				<button type="button" class="hpl-btn hpl-btn-primary" id="hpl-telegram-latest">خواندن همه پست‌های کانال</button>
				<button type="button" class="hpl-btn" id="hpl-telegram-refresh">نمایش پست‌ها</button>
			</div>
			<p class="hpl-hint" id="hpl-telegram-help"></p>
			<div class="hpl-table-wrap" id="hpl-telegram-list-wrap" hidden>
				<table class="hpl-table">
					<thead>
						<tr><th>پست</th><th>نوع</th><th>قیمت‌ها</th><th></th></tr>
					</thead>
					<tbody id="hpl-telegram-list"></tbody>
				</table>
			</div>
		</div>
		<p class="hpl-hint">موتور غربالگری تمام ردیف‌ها را با تمام محصولات سایت می‌سنجد: موجودها را برای قیمت و موجودی جدا می‌کند، غایب‌ها را غربال می‌کند و می‌توانید همان‌جا در سایت ست‌شان کنید.</p>
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
			<button type="button" class="hpl-btn hpl-btn-primary" id="hpl-do-preview">شروع غربالگری هوشمند</button>
		</div>
	</div>

	<div class="hpl-card is-hidden" id="hpl-step-3">
		<div class="hpl-card-head">
			<h2>غربالگری هوشمند فایل و سایت</h2>
			<p>موتور، همهٔ ستون‌های فایل و همهٔ محصولات فروشگاه را بررسی کرده. غایب‌ها را می‌توانید به کالا وصل کنید یا در سایت بسازید.</p>
		</div>
		<div class="hpl-stats" id="hpl-stats"></div>
		<div class="hpl-filters" id="hpl-filters">
			<button type="button" class="hpl-btn is-on" data-panel="on_site">در سایت</button>
			<button type="button" class="hpl-btn" data-panel="review">نیاز به تأیید</button>
			<button type="button" class="hpl-btn" data-panel="not_on_site">در سایت نیست</button>
			<button type="button" class="hpl-btn" data-panel="not_in_file">در فایل نیست</button>
		</div>
		<p class="hpl-hint" id="hpl-match-note"></p>
		<div class="hpl-screen-panels">
			<div class="hpl-table-wrap hpl-table-tall" id="hpl-panel-on_site">
				<table class="hpl-table"><thead><tr><th>کد فایل</th><th>محصول سایت</th><th>قیمت فعلی</th><th>قیمت جدید</th><th>موجودی فعلی</th><th>موجودی جدید</th></tr></thead><tbody id="hpl-body-on_site"></tbody></table>
			</div>
			<div class="hpl-table-wrap hpl-table-tall is-hidden" id="hpl-panel-review">
				<table class="hpl-table"><thead><tr><th>ردیف فایل</th><th>پیشنهاد موتور</th><th>اطمینان</th><th></th></tr></thead><tbody id="hpl-body-review"></tbody></table>
			</div>
			<div class="hpl-table-wrap hpl-table-tall is-hidden" id="hpl-panel-not_on_site">
				<table class="hpl-table"><thead><tr><th>کد</th><th>نام در فایل</th><th>قیمت</th><th>موجودی</th><th>ست کردن</th></tr></thead><tbody id="hpl-body-not_on_site"></tbody></table>
			</div>
			<div class="hpl-table-wrap hpl-table-tall is-hidden" id="hpl-panel-not_in_file">
				<table class="hpl-table"><thead><tr><th>شناسه</th><th>محصول سایت</th><th>SKU</th><th>قیمت</th><th>موجودی</th></tr></thead><tbody id="hpl-body-not_in_file"></tbody></table>
			</div>
		</div>
		<div class="hpl-actions">
			<button type="button" class="hpl-btn" id="hpl-back-2">بازگشت</button>
			<button type="button" class="hpl-btn hpl-btn-ghost" id="hpl-accept-all">پذیرش همه پیشنهادها</button>
			<button type="button" class="hpl-btn hpl-btn-ghost" id="hpl-create-all">ست کردن همه غایب‌ها در سایت</button>
			<label class="hpl-check"><input type="checkbox" id="hpl-publish-new"> غایب‌های جدید همان‌جا منتشر شوند</label>
			<button type="button" class="hpl-btn hpl-btn-danger" id="hpl-do-apply">اعمال قیمت و موجودی</button>
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
