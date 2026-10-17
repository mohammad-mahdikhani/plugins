<?php
require __DIR__ . '/../includes/class-rewriter.php';
$css  = file_get_contents( __DIR__ . '/fixtures/sample.css' );
$html = file_get_contents( __DIR__ . '/fixtures/sample.html' );
$after_css  = NFDS_Rewriter::css( $css );
$after_css  = preg_replace_callback(
	'/@import\s+(?:url\(\s*)?(["\']?)([^"\')]+)(\1)\s*\)?/i',
	array( 'NFDS_Rewriter', 'rewrite_import' ),
	$after_css
);
$after_html = NFDS_Rewriter::html( $html );
header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>تبدیل font-display به swap</title>
	<style>
		body { font-family: Tahoma, sans-serif; margin: 24px; background: #f4f7f3; color: #14221c; }
		h1 { font-size: 22px; }
		.grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
		pre { background: #fff; border: 1px solid #d7e4dc; border-radius: 12px; padding: 12px; overflow: auto; font-size: 12px; direction: ltr; text-align: left; max-height: 360px; }
		.ok { color: #157347; font-weight: 700; }
	</style>
</head>
<body>
	<h1>اجبار font-display: swap</h1>
	<p class="ok">auto / block / optional → swap — گوگل‌فونت، المنتور، قالب</p>
	<div class="grid">
		<div>
			<h2>قبل</h2>
			<pre><?php echo htmlspecialchars( $css . "\n\n" . $html, ENT_QUOTES, 'UTF-8' ); ?></pre>
		</div>
		<div>
			<h2>بعد</h2>
			<pre><?php echo htmlspecialchars( $after_css . "\n\n" . $after_html, ENT_QUOTES, 'UTF-8' ); ?></pre>
		</div>
	</div>
</body>
</html>
