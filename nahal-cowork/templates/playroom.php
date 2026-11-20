<?php
defined( 'ABSPATH' ) || exit;
$s     = NCK_Settings::all();
$blank = '…………………….';
?>
<div class="nck-root" dir="rtl" data-nck="playroom" data-ms-label="خانم">
	<form class="nck-form nck-wizard" data-nck-sign-playroom data-nck-wizard novalidate>
		<header class="nck-paper-head nck-wizard-brand">
			<?php include NCK_PATH . 'templates/brand-mark.php'; ?>
			<div>
				<p class="nck-kicker">بسمه تعالی</p>
				<h2 class="nck-title">قرارداد رزرو سالن بازی کودکان</h2>
				<p class="nck-tag"><?php echo esc_html( NCK_Playroom::default_org() ); ?></p>
			</div>
		</header>

		<?php include NCK_PATH . 'templates/wizard-head.php'; ?>

		<div class="nck-alert nck-alert-error" data-nck-error hidden></div>
		<div class="nck-alert nck-alert-ok" data-nck-ok hidden></div>

		<section class="nck-step" data-nck-step data-nck-step-label="برگزارکننده">
			<div class="nck-grid nck-grid-hall">
				<div class="nck-field">
					<label for="nck-pr-honorific">عنوان</label>
					<select id="nck-pr-honorific" name="honorific">
						<option value="mr">آقای</option>
						<option value="ms">خانم</option>
					</select>
				</div>
				<div class="nck-field nck-field-wide">
					<label for="nck-pr-name">نام برگزارکننده</label>
					<input id="nck-pr-name" name="name" type="text" required placeholder="مثال: علی رضایی" />
				</div>
				<div class="nck-field">
					<label for="nck-pr-nid">کد ملی</label>
					<input id="nck-pr-nid" name="national_id" type="text" dir="ltr" inputmode="numeric" required maxlength="10" placeholder="۱۰ رقم" />
				</div>
				<div class="nck-field">
					<label for="nck-pr-phone">شماره تماس</label>
					<input id="nck-pr-phone" name="phone" type="tel" dir="ltr" inputmode="numeric" required placeholder="09123456789" />
				</div>
				<div class="nck-field nck-field-wide">
					<label for="nck-pr-address">نشانی</label>
					<input id="nck-pr-address" name="address" type="text" required placeholder="نشانی دقیق..." />
				</div>
			</div>
		</section>

		<section class="nck-step" data-nck-step data-nck-step-label="زمان و خدمات" hidden>
			<div class="nck-grid nck-grid-hall">
				<?php include NCK_PATH . 'templates/hall-schedule.php'; ?>
				
				<div class="nck-field nck-field-wide">
					<label class="nck-agree nck-projector-row" style="margin:0">
						<span>
							<input type="checkbox" name="coaches" value="1" data-nck-price="<?php echo esc_attr( (string) NCK_Playroom::coach_price() ); ?>" />
							مایل هستم همکاران مجموعه نهال در طول برنامه، ما را همراهی کنند
						</span>
						<span class="nck-plan-price"><?php echo esc_html( NCK_Hall::format_money( NCK_Playroom::coach_price() ) ); ?></span>
					</label>
					<p class="nck-form-help">وظیفه مربی، هدایت بازی و نظارت در محدوده فعالیت توافق‌شده است.</p>
				</div>
				
				<div class="nck-field nck-field-wide">
					<span class="nck-label">مبلغ رزرو</span>
					<input id="nck-pr-amount" name="amount" type="hidden" value="" />
					<div class="nck-hall-quote" data-nck-hall-quote>
						<p>هزینه سالن بازی ۱,۵۰۰,۰۰۰ تومان است. در صورت انتخاب مربی مبلغ تغییر خواهد کرد.</p>
					</div>
				</div>
			</div>
		</section>

		<section class="nck-step" data-nck-step data-nck-step-label="مفاد قرارداد" hidden>
			<p class="nck-form-help">متن قرارداد با اطلاعات شما پر شده است. آن را بخوانید و فایل را دانلود کنید.</p>
			<p class="nck-review-actions">
				<button type="button" class="nck-btn" data-nck-download-review>دانلود قرارداد</button>
			</p>
			<article class="nck-paper nck-paper-nested" data-nck-review>
				<p class="nck-preamble">
					این قرارداد میان «<?php echo esc_html( NCK_Playroom::default_org() ); ?>» که از این پس «مجموعه» و 
					<span class="nck-blank" data-nck-live="title" data-blank="آقای/خانم">آقای/خانم</span>
					<span class="nck-blank" data-nck-live="name" data-blank="<?php echo esc_attr( $blank ); ?>"><?php echo esc_html( $blank ); ?></span>
					با کد ملی <span class="nck-blank" data-nck-live="national_id" data-blank="<?php echo esc_attr( $blank ); ?>"><?php echo esc_html( $blank ); ?></span>، 
					شماره تماس <span class="nck-blank" data-nck-live="phone" data-blank="<?php echo esc_attr( $blank ); ?>"><?php echo esc_html( $blank ); ?></span>
					و نشانی <span class="nck-blank" data-nck-live="address" data-blank="<?php echo esc_attr( $blank ); ?>"><?php echo esc_html( $blank ); ?></span>
					که از این پس «برگزارکننده» نامیده میشوند، منعقد میشود.
				</p>
				
				<?php foreach ( NCK_Playroom::clauses() as $clause ) : ?>
					<section class="nck-section">
						<h3><?php echo esc_html( $clause['num'] . ' - ' . $clause['title'] ); ?></h3>
						<p><?php echo nl2br( esc_html( $clause['body'] ) ); ?></p>
					</section>
				<?php endforeach; ?>

			</article>
			<label class="nck-agree">
				<input type="checkbox" name="agree" value="1" required />
				مفاد قرارداد را خواندم، فایل را در صورت نیاز دانلود کرده‌ام و شرایط رزرو سالن بازی را می‌پذیرم.
			</label>
		</section>

		<?php
		$nck_sign_title = 'امضای برگزارکننده';
		$nck_sign_party = 'امضای برگزارکننده';
		$nck_sign_help  = 'در این مرحله فقط امضای برگزارکننده ثبت می‌شود. مرحلهٔ بعد پرداخت است.';
		include NCK_PATH . 'templates/sign-step.php';

		$nck_pay_id       = 'nck-pr-pay';
		$nck_pay_amount   = '';
		$nck_pay_required = true;
		$nck_pay_from     = 'amount';
		include NCK_PATH . 'templates/pay-step.php';
		?>

		<nav class="nck-wizard-nav">
			<button type="button" class="nck-btn-ghost" data-nck-prev>مرحله قبل</button>
			<button type="button" class="nck-btn" data-nck-next>مرحله بعد</button>
			<button type="submit" class="nck-btn" data-nck-submit hidden>ثبت و پرداخت در سایت</button>
		</nav>
	</form>

	<div class="nck-done" data-nck-done hidden>
		<p class="nck-done-title">قرارداد رزرو سالن بازی ثبت شد.</p>
		<p data-nck-done-msg></p>
		<p>
			<a class="nck-btn" data-nck-print-link href="#" target="_blank" rel="noopener">دانلود و چاپ قرارداد</a>
			<a class="nck-btn nck-btn-ghost" data-nck-pay-link href="#" hidden>پرداخت در سایت</a>
		</p>
	</div>
</div>
