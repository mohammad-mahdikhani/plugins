<?php
/**
 * Settings screen.
 *
 * @package NahalPaymentBugReporter
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap npbr-wrap">
	<div class="npbr-hero">
		<div>
			<h1>تنظیمات گزارشگر خطای پرداخت</h1>
			<p>دکمه گزارش روی صفحات پرداخت nahal.website نشان داده می‌شود. کاربر شرح خطا را می‌نویسد، کدهای لودنشده و اسکرین صفحه را دانلود یا برای مدیر می‌فرستد.</p>
		</div>
	</div>

	<?php if ( isset( $_GET['saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p>تنظیمات ذخیره شد.</p></div>
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
			<legend>نمایش دکمه</legend>
			<label><input type="checkbox" name="show_on_checkout" value="1" <?php checked( $settings['show_on_checkout'] ); ?>> صفحه تسویه / پرداخت</label>
			<label><input type="checkbox" name="show_on_order_pay" value="1" <?php checked( $settings['show_on_order_pay'] ); ?>> پرداخت سفارش (order-pay)</label>
			<label><input type="checkbox" name="show_on_thankyou" value="1" <?php checked( $settings['show_on_thankyou'] ); ?>> صفحه تشکر بعد از پرداخت</label>
			<label><input type="checkbox" name="show_on_cart" value="1" <?php checked( $settings['show_on_cart'] ); ?>> سبد خرید</label>
			<label><input type="checkbox" name="show_floating" value="1" <?php checked( $settings['show_floating'] ); ?>> دکمه شناور</label>
			<label><input type="checkbox" name="show_near_payment" value="1" <?php checked( $settings['show_near_payment'] ); ?>> دکمه کنار فیلدهای پرداخت</label>
			<label><input type="checkbox" name="require_description" value="1" <?php checked( $settings['require_description'] ); ?>> نوشتن شرح خطا اجباری باشد</label>
		</fieldset>

		<p><button type="submit" class="button button-primary">ذخیره تنظیمات</button></p>
	</form>
</div>
