<?php
defined( 'ABSPATH' ) || exit;
/** @var array $form */
$steps = NCK_Forms::display_steps( $form );
$need_sign = ! empty( $form['require_signature'] );
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
		$pay_index = null;
		foreach ( $steps as $i => $step ) {
			if ( isset( $step['label'] ) && 'پرداخت' === $step['label'] && null === $pay_index ) {
				$pay_index = $i;
			}
		}
		$sign_before   = $need_sign ? ( null === $pay_index ? count( $steps ) : $pay_index ) : null;
		$agree_on      = null;
		foreach ( $steps as $i => $step ) {
			if ( isset( $step['label'] ) && 'پرداخت' === $step['label'] ) {
				continue;
			}
			if ( null !== $pay_index && $i > $pay_index ) {
				continue;
			}
			$agree_on = $i;
		}
		$opened_step   = false;
		$rendered_sign = false;
		?>
		<?php if ( null === $agree_on ) : ?>
			<section class="nck-step" data-nck-step data-nck-step-label="تأیید"<?php echo $opened_step ? ' hidden' : ''; ?>>
				<label class="nck-agree">
					<input type="checkbox" name="agree" value="1" required />
					اطلاعات واردشده صحیح است و ثبت این فرم را می‌پذیرم.
				</label>
			</section>
			<?php $opened_step = true; ?>
		<?php endif; ?>
		<?php foreach ( $steps as $i => $step ) : ?>
			<?php
			if ( $need_sign && $sign_before === $i ) {
				$nck_sign_title = 'امضا';
				$nck_sign_party = 'امضا';
				$nck_sign_help  = null === $pay_index
					? 'در این مرحله فقط امضا ثبت می‌شود.'
					: 'در این مرحله فقط امضا ثبت می‌شود. مرحلهٔ بعد پرداخت است.';
				$nck_sign_open  = ! $opened_step;
				include NCK_PATH . 'templates/sign-step.php';
				$opened_step   = true;
				$rendered_sign = true;
			}
			?>
			<section class="nck-step" data-nck-step data-nck-step-label="<?php echo esc_attr( $step['label'] ); ?>"<?php echo $opened_step ? ' hidden' : ''; ?>>
				<?php
				$show_amount = ! empty( $form['payment']['amount'] ) ? (int) $form['payment']['amount'] : 0;
				if ( ! $show_amount && ! empty( $form['product_id'] ) && function_exists( 'wc_get_product' ) ) {
					$product = wc_get_product( $form['product_id'] );
					if ( $product ) {
						$show_amount = (int) $product->get_price();
					}
				}
				?>
				<?php if ( ! empty( $form['payment']['enabled'] ) && $show_amount > 0 && 'پرداخت' === $step['label'] ) : ?>
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
				<?php if ( $agree_on === $i ) : ?>
					<label class="nck-agree">
						<input type="checkbox" name="agree" value="1" required />
						اطلاعات واردشده صحیح است و ثبت این فرم را می‌پذیرم.
					</label>
				<?php endif; ?>
			</section>
			<?php $opened_step = true; ?>
		<?php endforeach; ?>

		<?php if ( $need_sign && ! $rendered_sign ) : ?>
			<?php
			$nck_sign_title = 'امضا';
			$nck_sign_party = 'امضا';
			$nck_sign_help  = null === $pay_index
				? 'در این مرحله فقط امضا ثبت می‌شود.'
				: 'در این مرحله فقط امضا ثبت می‌شود. مرحلهٔ بعد پرداخت است.';
			$nck_sign_open  = ! $opened_step;
			include NCK_PATH . 'templates/sign-step.php';
			?>
		<?php endif; ?>

		<?php if ( $form['slogan'] !== '' ) : ?>
			<p class="nck-slogan"><?php echo esc_html( $form['slogan'] ); ?></p>
		<?php endif; ?>

		<nav class="nck-wizard-nav">
			<button type="button" class="nck-btn-ghost" data-nck-prev>مرحله قبل</button>
			<button type="button" class="nck-btn" data-nck-next>مرحله بعد</button>
			<button type="submit" class="nck-btn" data-nck-submit hidden><?php echo ! empty( $form['payment']['enabled'] ) ? 'ثبت و پرداخت' : 'ثبت فرم'; ?></button>
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
