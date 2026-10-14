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
			<p>نحوه تطبیق ردیف‌های فایل با محصولات فروشگاه را مشخص کنید.</p>
		</div>
	</div>

	<?php if ( ! empty( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="hpl-alert is-ok">تنظیمات ذخیره شد.</div>
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

		<p>
			<button type="submit" class="hpl-btn hpl-btn-primary">ذخیره تنظیمات</button>
		</p>
	</form>
</div>
