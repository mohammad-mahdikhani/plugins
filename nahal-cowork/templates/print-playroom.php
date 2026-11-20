<?php
defined( 'ABSPATH' ) || exit;
/** @var array $contract */
/** @var array $s */
/** @var array $vars */
/** @var array $clauses */
/** @var string $note */
$print_css = NCK_URL . 'assets/css/print.css?v=' . NCK_VERSION;
$front_css = NCK_URL . 'assets/css/frontend.css?v=' . NCK_VERSION;
$payload   = NCK_Contracts::payload( $contract );
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>رزرو سالن بازی — <?php echo esc_html( $contract['full_name'] ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( $front_css ); ?>" />
	<link rel="stylesheet" href="<?php echo esc_url( $print_css ); ?>" />
	<style>.nck-root{--nck-leaf:<?php echo esc_attr( $s['accent'] ); ?>;}</style>
</head>
<body class="nck-print-body">
	<?php
	$nck_save_title = 'سالن-بازی-' . ( isset( $contract['full_name'] ) ? $contract['full_name'] : 'نهال' );
	include NCK_PATH . 'templates/print-bar.php';
	?>
	<article class="nck-root nck-paper nck-print-paper" dir="rtl">
		<header class="nck-paper-head">
			<?php include NCK_PATH . 'templates/brand-mark.php'; ?>
			<div>
				<p class="nck-kicker">بسمه تعالی</p>
				<h1 class="nck-title">قرارداد رزرو و استفاده موقت از سالن بازی نهال</h1>
				<p class="nck-tag"><?php echo esc_html( NCK_Playroom::default_org() ); ?></p>
			</div>
		</header>

		<p class="nck-preamble">
			این قرارداد میان «<?php echo esc_html( $vars['org'] ); ?>» که از این پس «مجموعه» و
			<?php echo esc_html( $vars['title'] . ' ' . $vars['name'] ); ?>
			با کد ملی <?php echo esc_html( $vars['nid'] ); ?>،
			شماره تماس <?php echo esc_html( $vars['phone'] ); ?>
			و نشانی <?php echo esc_html( $vars['address'] ); ?>
			که از این پس «برگزارکننده» نامیده میشوند، منعقد میشود.
		</p>

		<?php foreach ( $clauses as $block ) : ?>
			<section class="nck-section">
				<h3><?php echo esc_html( $block['num'] . ' - ' . $block['title'] ); ?></h3>
				<?php if ( ! empty( $block['body'] ) ) : ?>
					<p><?php echo nl2br( esc_html( $block['body'] ) ); ?></p>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>

		<?php if ( ! empty( $payload['pay_amount'] ) || ! empty( $payload['payment'] ) ) : ?>
			<?php
			$pay_opts = NCK_Learner::payment_labels();
			$pay_key  = isset( $payload['payment'] ) ? $payload['payment'] : '';
			?>
			<section class="nck-section">
				<h3>پرداخت</h3>
				<p>روش: <?php echo esc_html( isset( $pay_opts[ $pay_key ] ) ? $pay_opts[ $pay_key ] : '—' ); ?></p>
				<p>مبلغ: <?php echo esc_html( NCK_Hall::format_money( ! empty( $payload['pay_amount'] ) ? (int) $payload['pay_amount'] : (int) $payload['amount'] ) ); ?></p>
				<?php if ( ! empty( $payload['pay_date'] ) ) : ?>
					<p>تاریخ: <?php echo esc_html( NCK_Jalali::fa_digits( $payload['pay_date'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $payload['pay_ref'] ) ) : ?>
					<p>پیگیری: <?php echo esc_html( $payload['pay_ref'] ); ?></p>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<footer class="nck-sign-row">
			<?php
			$plate_src  = ! empty( $contract['signature_png'] ) ? $contract['signature_png'] : '';
			$plate_name = trim( $vars['title'] . ' ' . $contract['full_name'] );
			$plate_role = 'امضای برگزارکننده';
			$plate_date = isset( $vars['date'] ) ? $vars['date'] : '';
			$plate_org  = false;
			include NCK_PATH . 'templates/sign-plate.php';
			$plate_src   = '';
			$plate_name  = $s['hall_signer'];
			$plate_role  = 'امضا و مهر مجموعه';
			$plate_date  = '';
			$plate_extra = NCK_Playroom::default_org();
			$plate_org   = true;
			include NCK_PATH . 'templates/sign-plate.php';
			?>
		</footer>
	</article>
</body>
</html>
