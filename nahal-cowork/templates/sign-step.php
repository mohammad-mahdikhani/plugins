<?php
defined( 'ABSPATH' ) || exit;
$nck_sign_title = isset( $nck_sign_title ) ? $nck_sign_title : 'امضا';
$nck_sign_party = isset( $nck_sign_party ) ? $nck_sign_party : 'امضا';
$nck_sign_date  = ! empty( $nck_sign_date );
$nck_sign_date_value = isset( $nck_sign_date_value ) ? (string) $nck_sign_date_value : '';
$nck_sign_agree = ! empty( $nck_sign_agree );
$nck_sign_agree_label = isset( $nck_sign_agree_label ) ? (string) $nck_sign_agree_label : 'اطلاعات واردشده صحیح است و ثبت این فرم را می‌پذیرم.';
$nck_sign_slogan = isset( $nck_sign_slogan ) ? (string) $nck_sign_slogan : '';
?>
<section class="nck-step" data-nck-step data-nck-step-label="امضا" data-nck-sign-step hidden>
	<?php if ( $nck_sign_agree ) : ?>
		<label class="nck-agree">
			<input type="checkbox" name="agree" value="1" required />
			<?php echo esc_html( $nck_sign_agree_label ); ?>
		</label>
	<?php endif; ?>
	<?php include NCK_PATH . 'templates/sign-pad.php'; ?>
	<?php if ( $nck_sign_date ) : ?>
		<div class="nck-field">
			<label for="nck-sign-date">تاریخ امضا</label>
			<input id="nck-sign-date" name="sign_date" type="text" dir="ltr" value="<?php echo esc_attr( $nck_sign_date_value ); ?>" placeholder="1404/06/20" />
		</div>
	<?php endif; ?>
	<?php if ( $nck_sign_slogan !== '' ) : ?>
		<p class="nck-slogan"><?php echo esc_html( $nck_sign_slogan ); ?></p>
	<?php endif; ?>
</section>
<?php
unset( $nck_sign_title, $nck_sign_party, $nck_sign_date, $nck_sign_date_value, $nck_sign_agree, $nck_sign_agree_label, $nck_sign_slogan );
