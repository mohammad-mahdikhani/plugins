<?php
/**
 * Settings screen.
 *
 * @package NahalMaintenance
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap nm-wrap">
	<div class="nm-hero">
		<h1>صفحه تعمیر سه‌بعدی</h1>
		<p>وقتی این حالت روشن باشد، بازدیدکننده به‌جای سایت یک صحنه شیشه‌ای سه‌بعدی می‌بیند. مدیر همچنان می‌تواند وارد پیشخوان شود.</p>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
	<?php endif; ?>

	<?php if ( ! empty( $settings['enabled'] ) ) : ?>
		<div class="notice notice-warning"><p>حالت تعمیر الان <strong>روشن</strong> است. بازدیدکنندگان صفحه سه‌بعدی را می‌بینند.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nm-card">
		<?php wp_nonce_field( 'nm_save_settings' ); ?>
		<input type="hidden" name="action" value="nm_save_settings">

		<fieldset class="nm-checks">
			<legend>وضعیت</legend>
			<label><input type="checkbox" name="enabled" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> صفحه تعمیر برای بازدیدکنندگان روشن باشد</label>
			<label><input type="checkbox" name="bypass_admins" value="1" <?php checked( ! empty( $settings['bypass_admins'] ) ); ?>> مدیران سایت را عادی ببینند</label>
		</fieldset>

		<label class="nm-field">
			<span>نام مجموعه</span>
			<input type="text" name="brand" value="<?php echo esc_attr( $settings['brand'] ); ?>">
		</label>
		<label class="nm-field">
			<span>برچسب بالا</span>
			<input type="text" name="kicker" value="<?php echo esc_attr( $settings['kicker'] ); ?>">
		</label>
		<label class="nm-field">
			<span>عنوان</span>
			<input type="text" name="title" value="<?php echo esc_attr( $settings['title'] ); ?>">
		</label>
		<label class="nm-field">
			<span>متن صفحه</span>
			<textarea name="message" rows="4"><?php echo esc_textarea( $settings['message'] ); ?></textarea>
		</label>
		<label class="nm-field">
			<span>زمان برگشت</span>
			<input type="text" name="eta_text" value="<?php echo esc_attr( $settings['eta_text'] ); ?>">
		</label>
		<label class="nm-field">
			<span>تلفن</span>
			<input type="text" name="phone" value="<?php echo esc_attr( $settings['phone'] ); ?>" dir="ltr">
		</label>
		<label class="nm-field">
			<span>آدرس</span>
			<input type="text" name="address" value="<?php echo esc_attr( $settings['address'] ); ?>">
		</label>
		<label class="nm-field">
			<span>آدرس لوگو (اختیاری)</span>
			<input type="url" name="logo_url" value="<?php echo esc_attr( $settings['logo_url'] ); ?>" dir="ltr" placeholder="https://nahal.website/...png">
		</label>
		<label class="nm-field">
			<span>کلید عبور مخفی</span>
			<input type="text" name="bypass_key" value="<?php echo esc_attr( $settings['bypass_key'] ); ?>" dir="ltr" placeholder="مثلاً nahal1405">
			<small>اگر پر باشد، با افزودن <code>?nm_key=همان‌کلید</code> به آدرس سایت می‌توان صفحه عادی را دید.</small>
		</label>
		<label class="nm-field">
			<span>Retry-After (ثانیه)</span>
			<input type="number" name="retry_after" min="60" max="86400" value="<?php echo esc_attr( (string) $settings['retry_after'] ); ?>" dir="ltr">
			<small>به موتورهای جستجو می‌گوید کی دوباره سر بزنند. بین ۶۰ تا ۸۶۴۰۰.</small>
		</label>

		<p class="nm-actions">
			<button type="submit" class="button button-primary">ذخیره تنظیمات</button>
			<a class="button" href="<?php echo esc_url( home_url( '/?nm_preview=1' ) ); ?>" target="_blank" rel="noopener">پیش‌نمایش صفحه سه‌بعدی</a>
		</p>
	</form>

	<div class="nm-help">
		<strong>چطور استفاده شود</strong>
		<ol>
			<li>متن‌ها را تنظیم و ذخیره کنید.</li>
			<li>با دکمه پیش‌نمایش، صفحه را ببینید (سایت هنوز باز است).</li>
			<li>وقتی آماده تعمیر بودید، گزینه «صفحه تعمیر روشن باشد» را بزنید.</li>
			<li>بعد از اتمام کار، همان گزینه را خاموش کنید. غیرفعال‌کردن افزونه هم صفحه را خاموش می‌کند.</li>
		</ol>
	</div>
</div>
