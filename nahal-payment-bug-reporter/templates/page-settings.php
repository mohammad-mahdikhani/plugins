<?php
/**
 * Settings screen.
 *
 * @package NahalPaymentBugReporter
 * @var array<string, mixed> $settings
 * @var array{ok:bool,message:string}|null $tg_test
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! isset( $tg_test ) ) {
	$tg_test = null;
}
?>
<div class="wrap npbr-wrap">
	<div class="npbr-hero">
		<div>
			<h1>تنظیمات گزارشگر خطای پرداخت</h1>
			<p>دکمه گزارش روی کل سایت (با تمرکز روی سبد، دکمه خرید، فروشگاه، محصول و درگاه) نشان داده می‌شود. گزارش شامل دستگاه، نام، فامیلی، موبایل لاگین و خریدهای قبلی است تا بتوانید تماس بگیرید.</p>
		</div>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
	<?php endif; ?>

	<?php if ( is_array( $tg_test ) ) : ?>
		<div class="notice <?php echo ! empty( $tg_test['ok'] ) ? 'notice-success' : 'notice-error'; ?> is-dismissible">
			<p><?php echo esc_html( isset( $tg_test['message'] ) ? $tg_test['message'] : '' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="npbr-card">
		<?php wp_nonce_field( 'npbr_save_settings' ); ?>
		<input type="hidden" name="action" value="npbr_save_settings">

		<label class="npbr-field">
			<span>ایمیل مدیر</span>
			<input type="email" name="manager_email" value="<?php echo esc_attr( $settings['manager_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
			<small>اگر خالی باشد همان ایمیل مدیریت وردپرس استفاده می‌شود.</small>
		</label>

		<label class="npbr-field">
			<span>متن دکمه</span>
			<input type="text" name="button_label" value="<?php echo esc_attr( $settings['button_label'] ); ?>">
		</label>

		<fieldset class="npbr-checks">
			<legend>تلگرام</legend>
			<label><input type="checkbox" name="telegram_enabled" value="1" <?php checked( $settings['telegram_enabled'] ); ?>> همزمان با ارسال برای مدیر، گزارش به تلگرام هم برود</label>
		</fieldset>
		<label class="npbr-field">
			<span>توکن ربات تلگرام</span>
			<input type="password" name="telegram_bot_token" value="" autocomplete="new-password" placeholder="<?php echo $settings['telegram_bot_token'] ? 'ذخیره شده — برای تغییر، توکن جدید بنویسید' : '123456:ABC-DEF...'; ?>">
			<small>از @BotFather بگیرید. اگر خالی بماند توکن قبلی عوض نمی‌شود.</small>
		</label>
		<label class="npbr-field">
			<span>Chat ID تلگرام</span>
			<input type="text" name="telegram_chat_id" value="<?php echo esc_attr( $settings['telegram_chat_id'] ); ?>" dir="ltr" placeholder="-100... یا @channel">
			<small>آیدی عددی گروه/شخص یا یوزرنیم کانال. ربات باید عضو کانال/گروه باشد.</small>
		</label>
		<label class="npbr-field">
			<span>آدرس پروکسی API تلگرام (برای هاست ایران)</span>
			<input type="url" name="telegram_api_base" value="<?php echo esc_attr( $settings['telegram_api_base'] ); ?>" dir="ltr" placeholder="https://npbr-tg.YOUR-SUBDOMAIN.workers.dev">
			<small>روی هاست ایران <code>api.telegram.org</code> فیلتر است. Worker خودتان را از فایل <code>telegram-proxy-worker.js</code> داخل افزونه روی Cloudflare بسازید و آدرس workers.dev را اینجا بگذارید. از پروکسی عمومی دیگران استفاده نکنید؛ توکن ربات دیده می‌شود.</small>
		</label>
		<label class="npbr-field">
			<span>پروکسی HTTP اختیاری</span>
			<input type="text" name="telegram_http_proxy" value="<?php echo esc_attr( $settings['telegram_http_proxy'] ); ?>" dir="ltr" placeholder="host:port یا http://user:pass@host:port">
			<small>اگر سرور HTTP CONNECT دارد، اینجا بگذارید. در غیر این صورت خالی بماند؛ مسیر اصلی همان Worker است.</small>
		</label>
		<div class="npbr-help">
			<strong>اگر تلگرام فیلتر است:</strong>
			<ol>
				<li>در Cloudflare: Workers &amp; Pages ← Create ← کد <code>telegram-proxy-worker.js</code> را جایگزین کنید و Deploy.</li>
				<li>آدرس <code>https://….workers.dev</code> را در فیلد بالا ذخیره کنید.</li>
				<li>بعد از ذخیره، دکمه «ارسال پیام تست به تلگرام» را بزنید.</li>
			</ol>
		</div>

		<fieldset class="npbr-checks">
			<legend>نمایش دکمه</legend>
			<label><input type="checkbox" name="show_sitewide" value="1" <?php checked( ! empty( $settings['show_sitewide'] ) ); ?>> کل سایت</label>
			<label><input type="checkbox" name="show_on_shop" value="1" <?php checked( ! empty( $settings['show_on_shop'] ) ); ?>> فروشگاه و دسته‌بندی محصولات</label>
			<label><input type="checkbox" name="show_on_product" value="1" <?php checked( ! empty( $settings['show_on_product'] ) ); ?>> صفحه محصول و دکمه خرید</label>
			<label><input type="checkbox" name="show_on_cart" value="1" <?php checked( $settings['show_on_cart'] ); ?>> سبد خرید و ویرایش سبد</label>
			<label><input type="checkbox" name="show_on_checkout" value="1" <?php checked( $settings['show_on_checkout'] ); ?>> صفحه تسویه / پرداخت تا درگاه</label>
			<label><input type="checkbox" name="show_on_order_pay" value="1" <?php checked( $settings['show_on_order_pay'] ); ?>> پرداخت سفارش (order-pay)</label>
			<label><input type="checkbox" name="show_on_thankyou" value="1" <?php checked( $settings['show_on_thankyou'] ); ?>> صفحه تشکر بعد از پرداخت</label>
			<label><input type="checkbox" name="show_near_cart" value="1" <?php checked( ! empty( $settings['show_near_cart'] ) ); ?>> دکمه کمکی کنار سبد، دکمه خرید و ادامه تسویه (نه داخل باکس درگاه)</label>
			<label><input type="checkbox" name="show_floating" value="1" <?php checked( $settings['show_floating'] ); ?>> دکمه شناور</label>
			<label><input type="checkbox" name="require_description" value="1" <?php checked( $settings['require_description'] ); ?>> نوشتن شرح خطا اجباری باشد</label>
			<label><input type="checkbox" name="digest_email" value="1" <?php checked( ! empty( $settings['digest_email'] ) ); ?>> گزارش هفتگی و ماهانه برای ایمیل مدیر ارسال شود</label>
		</fieldset>

		<p><button type="submit" class="button button-primary">ذخیره تنظیمات</button></p>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="npbr-card">
		<?php wp_nonce_field( 'npbr_test_telegram' ); ?>
		<input type="hidden" name="action" value="npbr_test_telegram">
		<p>اول تنظیمات را ذخیره کنید، بعد اتصال تلگرام را از همین سرور تست کنید. افزونه اول Worker شما را امتحان می‌کند و اگر نرسید همان <code>api.telegram.org</code> را.</p>
		<p><button type="submit" class="button">ارسال پیام تست به تلگرام</button></p>
	</form>
</div>
