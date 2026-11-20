<?php
defined( 'ABSPATH' ) || exit;

$nck_sign_title = isset( $nck_sign_title ) ? $nck_sign_title : 'امضا';
$nck_sign_party = isset( $nck_sign_party ) ? $nck_sign_party : 'امضا';
$nck_sign_label = isset( $nck_sign_label ) ? $nck_sign_label : 'امضا';
$nck_sign_help  = isset( $nck_sign_help ) ? $nck_sign_help : 'در این مرحله فقط امضا ثبت می‌شود. مرحلهٔ بعد پرداخت است.';
$nck_sign_open  = ! empty( $nck_sign_open );
$nck_sign_with_date = ! empty( $nck_sign_with_date );
$nck_sign_date_value = isset( $nck_sign_date_value ) ? (string) $nck_sign_date_value : '';
?>
<section class="nck-step" data-nck-step data-nck-require-sign data-nck-step-label="<?php echo esc_attr( $nck_sign_label ); ?>"<?php echo $nck_sign_open ? '' : ' hidden'; ?>>
	<p class="nck-form-help"><?php echo esc_html( $nck_sign_help ); ?></p>
	<?php include NCK_PATH . 'templates/sign-pad.php'; ?>
	<?php if ( $nck_sign_with_date ) : ?>
		<div class="nck-field">
			<label for="nck-sign-date">تاریخ امضا</label>
			<input id="nck-sign-date" name="sign_date" type="text" dir="ltr" value="<?php echo esc_attr( $nck_sign_date_value ); ?>" placeholder="1404/06/20" />
		</div>
	<?php endif; ?>
</section>
<?php
$nck_sign_open      = false;
$nck_sign_with_date = false;
