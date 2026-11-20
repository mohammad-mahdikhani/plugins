<?php
defined( 'ABSPATH' ) || exit;
/** @var array $form */
$steps     = NCK_Forms::display_steps( $form );
$need_sign = ! empty( $form['require_signature'] );
$data_steps = array();
$pay_steps  = array();
foreach ( $steps as $step ) {
	if ( NCK_Forms::step_has_payment( $step ) ) {
		$pay_steps[] = $step;
	} else {
		$data_steps[] = $step;
	}
}

$nck_render_step = static function ( array $step, $hidden, array $form ) {
	?>
	<section class="nck-step" data-nck-step data-nck-step-label="<?php echo esc_attr( $step['label'] ); ?>"<?php echo $hidden ? ' hidden' : ''; ?>>
		<?php
		$show_amount = ! empty( $form['payment']['amount'] ) ? (int) $form['payment']['amount'] : 0;
		if ( ! $show_amount && ! empty( $form['product_id'] ) && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $form['product_id'] );
			if ( $product ) {
				$show_amount = (int) $product->get_price();
			}
		}
		if ( ! empty( $form['payment']['enabled'] ) && $show_amount > 0 && NCK_Forms::step_has_payment( $step ) ) :
			?>
			<p class="nck-note">مبلغ این فرم: <?php echo esc_html( NCK_Hall::format_money( $show_amount ) ); ?></p>
		<?php endif; ?>
		<div class="nck-grid nck-grid-hall">
			<?php
			foreach ( isset( $step['fields'] ) ? $step['fields'] : array() as $field ) {
				$wide = in_array( $field['type'], array( 'textarea', 'note', 'checkbox', 'radio', 'payment_method' ), true );
				if ( $wide ) {
					echo '<div class="nck-field-wide">';
				}
				NCK_Forms::render_field( $field, $form );
				if ( $wide ) {
					echo '</div>';
				}
			}
			?>
		</div>
	</section>
	<?php
};
?>
<div class="nck-root" dir="rtl" data-nck="form">
	<form class="nck-form nck-wizard" data-nck-sign-form data-nck-wizard novalidate>
		<input type="hidden" name="form_id" value="<?php echo esc_attr( $form['id'] ); ?>" />
		<input type="hidden" name="form_slug" value="<?php echo esc_attr( $form['slug'] ); ?>" />
		<header class="nck-paper-head nck-wizard-brand">
			<?php include NCK_PATH . 'templates/brand-mark.php'; ?>
			<div>
				<?php if ( $form['kicker'] !== '' ) : ?>
					<p class="nck-kicker"><?php echo esc_html( $form['kicker'] ); ?></p>
				<?php endif; ?>
				<h2 class="nck-title"><?php echo esc_html( $form['title'] ); ?></h2>
				<?php if ( $form['tagline'] !== '' ) : ?>
					<p class="nck-tag"><?php echo esc_html( $form['tagline'] ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<?php include NCK_PATH . 'templates/wizard-head.php'; ?>

		<div class="nck-alert nck-alert-error" data-nck-error hidden></div>
		<div class="nck-alert nck-alert-ok" data-nck-ok hidden></div>

		<?php
		$step_i = 0;
		foreach ( $data_steps as $step ) {
			$nck_render_step( $step, 0 !== $step_i, $form );
			$step_i++;
		}
		?>

		<section class="nck-step" data-nck-step data-nck-step-label="تأیید و ثبت"<?php echo 0 === $step_i ? '' : ' hidden'; ?>>
			<label class="nck-agree">
				<input type="checkbox" name="agree" value="1" required />
				اطلاعات واردشده صحیح است و ثبت این فرم را می‌پذیرم.
			</label>
			<?php if ( $form['slogan'] !== '' ) : ?>
				<p class="nck-slogan"><?php echo esc_html( $form['slogan'] ); ?></p>
			<?php endif; ?>
		</section>

		<?php
		if ( $need_sign ) {
			$nck_sign_title = 'امضا';
			$nck_sign_party = 'امضا';
			include NCK_PATH . 'templates/sign-step.php';
		}
		foreach ( $pay_steps as $step ) {
			$nck_render_step( $step, true, $form );
		}
		?>

		<nav class="nck-wizard-nav">
			<button type="button" class="nck-btn-ghost" data-nck-prev>مرحله قبل</button>
			<button type="button" class="nck-btn" data-nck-next>مرحله بعد</button>
			<button type="submit" class="nck-btn" data-nck-submit hidden>ثبت فرم</button>
		</nav>
	</form>

	<div class="nck-done" data-nck-done hidden>
		<p class="nck-done-title">فرم ثبت شد.</p>
		<p data-nck-done-msg></p>
		<p>
			<a class="nck-btn" data-nck-print-link href="#" target="_blank" rel="noopener">مشاهده و چاپ فرم</a>
			<a class="nck-btn nck-btn-ghost" data-nck-pay-link href="#" hidden>پرداخت در سایت</a>
		</p>
	</div>
</div>
