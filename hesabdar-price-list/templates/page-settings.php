<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$order = implode( ',', (array) $settings['match_order'] );
?>
<div class="wrap hpl-wrap" dir="rtl">
	<div class="hpl-hero">
		<div>
			<h1>تنظیمات لیست قیمت</h1>
			<p>تطبیق محصولات و اتصال کانال تلگرام برای خواندن لیست قیمت.</p>
		</div>
	</div>

	<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="hpl-alert is-ok">تنظیمات ذخیره شد<?php echo ! empty( $_GET['webhook'] ) ? ' و وب‌هوک تلگرام ثبت شد' : ''; ?>.</div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['webhook_error'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="hpl-alert">ثبت وب‌هوک ناموفق بود. توکن ربات و در دسترس بودن سایت را بررسی کنید.</div>
	<?php endif; ?>

	<form class="hpl-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="hpl_save_settings">
		<?php wp_nonce_field( 'hpl_save_settings' ); ?>

		<label class="hpl-field">
			<span>ترتیب تطبیق محصول</span>
			<input type="text" name="match_order" value="<?php echo esc_attr( $order ); ?>">
			<small>مقادیر مجاز با ویرگول: sku, product_id, barcode, name</small>
		</label>

		<label class="hpl-check"><input type="checkbox" name="update_regular" value="1" <?php checked( ! empty( $settings['update_regular'] ) ); ?>> قیمت اصلی را از فایل به‌روز کن</label>
		<label class="hpl-check"><input type="checkbox" name="update_sale" value="1" <?php checked( ! empty( $settings['update_sale'] ) ); ?>> قیمت ویژه را از فایل به‌روز کن</label>
		<label class="hpl-check"><input type="checkbox" name="update_stock" value="1" <?php checked( ! empty( $settings['update_stock'] ) ); ?>> موجودی را هم از فایل به‌روز کن</label>
		<label class="hpl-check"><input type="checkbox" name="skip_empty_price" value="1" <?php checked( ! empty( $settings['skip_empty_price'] ) ); ?>> اگر قیمت در فایل خالی بود، قیمت فعلی محصول را دست نزن</label>
		<label class="hpl-check"><input type="checkbox" name="zero_clears_sale" value="1" <?php checked( ! empty( $settings['zero_clears_sale'] ) ); ?>> قیمت ویژه صفر در فایل، قیمت ویژه فروشگاه را پاک کند</label>

		<h2 class="hpl-subhead">کانال تلگرام</h2>
		<p class="hpl-hint">ربات را از BotFather بسازید و ادمین کانال کنید. هر پست کانال یک کالا است؛ نام و قیمت کافی است.</p>
		<label class="hpl-field">
			<span>توکن ربات</span>
			<input type="password" name="telegram_bot_token" value="" placeholder="<?php echo ! empty( $settings['telegram_bot_token'] ) ? 'توکن ذخیره شده — برای تغییر، توکن جدید بنویسید' : '123456:ABC-DEF...'; ?>" autocomplete="off">
		</label>
		<label class="hpl-field">
			<span>کانال</span>
			<input type="text" name="telegram_channel" value="<?php echo esc_attr( isset( $settings['telegram_channel'] ) ? $settings['telegram_channel'] : '' ); ?>" placeholder="@pricechannel یا لینک t.me">
			<small>پست‌هایی مثل «نام کالا» و در انتها «قیمت: ۲۴،۵۰۰ تومان» خوانده می‌شوند.</small>
		</label>

		<p>
			<button type="submit" class="hpl-btn hpl-btn-primary">ذخیره تنظیمات</button>
		</p>
	</form>

	<?php if ( ! empty( $settings['telegram_bot_token'] ) ) : ?>
		<form class="hpl-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="hpl_telegram_webhook">
			<?php wp_nonce_field( 'hpl_telegram_webhook' ); ?>
			<h2 class="hpl-subhead">وب‌هوک تلگرام</h2>
			<p class="hpl-hint">با ثبت وب‌هوک، هر پست جدید کانال (نام و قیمت) همان لحظه به افزونه می‌رسد. آدرس سایت باید از اینترنت در دسترس باشد.</p>
			<p class="hpl-hint"><code><?php echo esc_html( HPL_Telegram::webhook_url() ); ?></code></p>
			<?php if ( ! empty( $settings['telegram_webhook'] ) ) : ?>
				<div class="hpl-alert is-ok">وب‌هوک ثبت شده است.</div>
			<?php endif; ?>
			<?php if ( ! empty( $settings['telegram_last_error'] ) ) : ?>
				<div class="hpl-alert"><?php echo esc_html( $settings['telegram_last_error'] ); ?></div>
			<?php endif; ?>
			<p>
				<button type="submit" class="hpl-btn hpl-btn-primary">ثبت وب‌هوک</button>
			</p>
		</form>
	<?php endif; ?>
</div>
