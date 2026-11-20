<?php
defined( 'ABSPATH' ) || exit;
/** @var WC_Order|object $order */
/** @var array|null $contract */
/** @var bool $compact */
$compact = ! empty( $compact );
if ( empty( $contract ) ) {
	echo '<div class="nck-order-pdf">';
	echo '<p>سفارش فضای کار اشتراکی است. پس از امضای توافق‌نامه، فایل چاپ اینجا نمایش داده می‌شود.</p>';
	echo '</div>';
	return;
}
$url     = NCK_Contracts::print_url( $contract['print_token'] );
$pdf_url = NCK_Contracts::print_url( $contract['print_token'], true );
$plan    = NCK_Contracts::plan_label( $contract );
$sign     = isset( $contract['signature_png'] ) ? (string) $contract['signature_png'] : '';
$name     = isset( $contract['full_name'] ) ? (string) $contract['full_name'] : '';
$phone    = isset( $contract['phone'] ) ? (string) $contract['phone'] : '';
$signed   = isset( $contract['signed_at'] ) ? (string) $contract['signed_at'] : '';
?>
<div class="nck-order-pdf">
	<?php if ( ! $compact ) : ?>
		<h3>توافق‌نامه و امضای فضای کار اشتراکی</h3>
	<?php endif; ?>
	<p class="nck-order-pdf-who">
		<strong><?php echo esc_html( $name ); ?></strong>
		<?php if ( $phone !== '' ) : ?>
			<span dir="ltr"><?php echo esc_html( $phone ); ?></span>
		<?php endif; ?>
	</p>
	<?php if ( $plan !== '' ) : ?>
		<p><?php echo esc_html( $plan ); ?></p>
	<?php endif; ?>
	<?php if ( $signed !== '' ) : ?>
		<p>امضا: <?php echo esc_html( $signed ); ?></p>
	<?php endif; ?>
	<?php if ( $sign !== '' ) : ?>
		<p class="nck-order-pdf-sign"><img src="<?php echo esc_attr( $sign ); ?>" alt="امضا" /></p>
	<?php endif; ?>
	<p class="nck-order-pdf-actions">
		<a class="button button-primary" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener">چاپ / ذخیره PDF</a>
		<a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">مشاهده توافق‌نامه</a>
	</p>
</div>
