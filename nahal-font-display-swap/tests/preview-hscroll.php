<?php
require __DIR__ . '/../includes/class-overflow.php';
$css = NFDS_Overflow::css();
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html lang="fa-IR" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>بدون اسکرول افقی</title>
	<style>
		body { margin: 0; font-family: Tahoma, sans-serif; background: #f4f7f3; color: #14221c; }
		header { padding: 16px 20px; background: #fff; border-bottom: 1px solid #d7e4dc; }
		.wide { width: 100vw; margin-left: -40px; background: #2563eb; color: #fff; padding: 18px; }
		.stick-out { width: 140%; background: #f97316; color: #fff; padding: 12px 16px; }
		main { padding: 20px; }
		<?php echo $css; ?>
	</style>
</head>
<body class="rtl">
	<header>نهال — تست اسکرول افقی</header>
	<div class="elementor-section elementor-section-stretched wide">سکشن کشیده المنتور (100vw)</div>
	<main>
		<p>اگر اسکرول افقی بسته شده باشد نوار پایین صفحه فقط عمودی است.</p>
		<div class="stick-out">المان بیرون‌زده</div>
	</main>
</body>
</html>
