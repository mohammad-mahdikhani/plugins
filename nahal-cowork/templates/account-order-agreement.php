<?php
defined( 'ABSPATH' ) || exit;
/** @var array $contract */
$url     = NCK_Contracts::print_url( $contract['print_token'] );
$pdf_url = NCK_Contracts::print_url( $contract['print_token'], true );
$plan    = NCK_Contracts::plan_label( $contract );
$sign    = isset( $contract['signature_png'] ) ? (string) $contract['signature_png'] : '';
$name    = isset( $contract['full_name'] ) ? (string) $contract['full_name'] : '';
$signed  = isset( $contract['signed_at'] ) ? (string) $contract['signed_at'] : '';
?>
<section class="nck-root nck-account-pdf" dir="rtl">
	<h2>توافق‌نامه فضای کار اشتراکی</h2>
	<p>می‌توانید توافق‌نامه و امضای خود را مشاهده، چاپ یا به‌صورت PDF ذخیره کنید.</p>
	<?php if ( $name !== '' || $plan !== '' ) : ?>
		<p>
			<?php if ( $name !== '' ) : ?>
				<strong><?php echo esc_html( $name ); ?></strong>
			<?php endif; ?>
			<?php if ( $plan !== '' ) : ?>
				<?php echo $name !== '' ? ' — ' : ''; ?>
				<?php echo esc_html( $plan ); ?>
			<?php endif; ?>
			<?php if ( $signed !== '' ) : ?>
				<br />امضا: <?php echo esc_html( $signed ); ?>
			<?php endif; ?>
		</p>
	<?php endif; ?>
	<?php if ( $sign !== '' ) : ?>
		<p class="nck-account-pdf-sign"><img src="<?php echo esc_attr( $sign ); ?>" alt="امضا" /></p>
	<?php endif; ?>
	<p class="nck-account-pdf-actions">
		<a class="nck-btn" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank" rel="noopener">چاپ / ذخیره PDF</a>
		<a class="nck-btn nck-btn-ghost" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">مشاهده توافق‌نامه</a>
	</p>
</section>
