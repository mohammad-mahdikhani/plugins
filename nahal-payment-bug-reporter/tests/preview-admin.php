<?php
/**
 * Render real admin templates without WordPress.
 * php -S 127.0.0.1:8765 -t nahal-payment-bug-reporter
 * then /tests/preview-admin.php?page=dashboard|reports|digest
 */
define( 'ABSPATH', sys_get_temp_dir() . '/npbr-preview/' );
define( 'NPBR_TEST', true );
define( 'NPBR_VERSION', '1.0.8' );

require dirname( __DIR__ ) . '/includes/class-stats.php';

function esc_html( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $v ) {
	return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' );
}
function admin_url( $path = '' ) {
	$page = 'dashboard';
	if ( false !== strpos( $path, 'npbr-digest' ) ) {
		$page = 'digest';
		if ( false !== strpos( $path, 'range=month' ) ) {
			$page = 'digest&range=month';
		} elseif ( false !== strpos( $path, 'range=last_month' ) ) {
			$page = 'digest&range=last_month';
		} elseif ( false !== strpos( $path, 'range=week' ) ) {
			$page = 'digest&range=week';
		}
	} elseif ( false !== strpos( $path, 'npbr-reports' ) ) {
		$page = 'reports';
		if ( false !== strpos( $path, 'status=open' ) ) {
			$page = 'reports&status=open';
		} elseif ( false !== strpos( $path, 'status=resolved' ) ) {
			$page = 'reports&status=resolved';
		}
	} elseif ( false !== strpos( $path, 'npbr-settings' ) ) {
		$page = 'settings';
	}
	return 'preview-admin.php?page=' . $page;
}

$key = NPBR_Stats::issue_key(
	array(
		'failed'  => array(
			array(
				'url'     => 'https://nahal.website/wp-content/plugins/woocommerce/assets/js/frontend/checkout.min.js?ver=8.0',
				'payment' => true,
			),
		),
		'pageUrl' => 'https://nahal.website/checkout/',
	)
);
$rows = array(
	array(
		'status'     => 'open',
		'auto'       => true,
		'date'       => '2026-10-16 10:00:00',
		'pageUrl'    => 'https://nahal.website/checkout/',
		'phone'      => '09120000000',
		'issueKey'   => $key,
		'issueLabel' => 'اسکریپت checkout لود نشد',
		'summary'    => 'اسکریپت checkout لود نشد',
	),
	array(
		'status'     => 'resolved',
		'auto'       => false,
		'date'       => '2026-10-15 10:00:00',
		'pageUrl'    => 'https://nahal.website/checkout/',
		'phone'      => '',
		'issueKey'   => $key,
		'issueLabel' => 'اسکریپت checkout لود نشد',
		'summary'    => 'اسکریپت checkout لود نشد',
	),
	array(
		'status'     => 'open',
		'auto'       => false,
		'date'       => '2026-10-14 10:00:00',
		'pageUrl'    => 'https://nahal.website/cart/',
		'phone'      => '09123334444',
		'issueKey'   => 'page:https://nahal.website/cart/|سبد خالی',
		'issueLabel' => 'سبد خرید به‌روز نشد',
		'summary'    => 'سبد خرید به‌روز نشد',
	),
);

$page   = isset( $_GET['page'] ) ? (string) $_GET['page'] : 'dashboard';
$kind   = isset( $_GET['range'] ) ? (string) $_GET['range'] : 'week';
$filter = isset( $_GET['status'] ) ? (string) $_GET['status'] : '';
if ( ! in_array( $kind, array( 'week', 'month', 'last_month' ), true ) ) {
	$kind = 'week';
}
$period = NPBR_Stats::period( $kind, strtotime( '2026-10-17 12:00:00' ) );
$sum    = NPBR_Stats::summarize( $rows );
$open   = (int) $sum['open'];

$nav = array(
	'dashboard' => $page === 'dashboard' ? 'is-on' : '',
	'reports'   => $page === 'reports' ? 'is-on' : '',
	'digest'    => $page === 'digest' ? 'is-on' : '',
);
?><!DOCTYPE html>
<html lang="fa-IR" dir="rtl">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>پیشخوان گزارش خطا — نهال</title>
	<link rel="stylesheet" href="../assets/admin/admin.css">
	<style>
		body { margin: 0; font-family: Tahoma, Vazirmatn, sans-serif; background: #f0f0f1; color: #1d2327; }
		.wp-bar { background: #1d2327; color: #fff; display: flex; justify-content: space-between; padding: 8px 16px; font-size: 13px; }
		.wp-bar a { color: #fff; text-decoration: none; }
		.layout { display: grid; grid-template-columns: 200px 1fr; min-height: 100vh; }
		.side { background: #1d2327; color: #fff; padding: 16px 0; }
		.side a { display: block; color: #fff; text-decoration: none; padding: 10px 16px; }
		.side a.is-on { background: #2271b1; }
		.awaiting-mod { background: #d63638; color: #fff; border-radius: 9px; padding: 0 6px; font-size: 11px; margin-right: 6px; }
		.content { padding: 8px 20px 60px; }
		.notice { background: #fff; border: 1px solid #c3c4c7; border-right: 4px solid #dba617; padding: 8px 12px; margin: 0 0 16px; }
		.notice a { color: #2271b1; }
		.postbox { background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; padding: 12px 16px; max-width: 560px; }
		.postbox h2 { margin: 0 0 10px; font-size: 14px; }
		.postbox ul { margin: 0 0 12px; padding-right: 18px; line-height: 1.8; }
		.button { display: inline-block; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; padding: 4px 10px; text-decoration: none; color: #2c3338; }
		.button-primary { background: #2271b1; border-color: #2271b1; color: #fff; }
		.widefat { width: 100%; border-collapse: collapse; background: #fff; }
		.widefat th, .widefat td { border: 1px solid #d7e4dc; padding: 8px 10px; text-align: right; }
		@media (max-width: 782px) { .layout { grid-template-columns: 1fr; } .side { display: none; } }
	</style>
</head>
<body>
	<div class="wp-bar">
		<div>نهال نیشابور</div>
		<a href="preview-admin.php?page=reports&amp;status=open">خطای خرید (<?php echo (int) $open; ?>)</a>
	</div>
	<div class="layout">
		<nav class="side">
			<a class="<?php echo esc_attr( $nav['dashboard'] ); ?>" href="preview-admin.php?page=dashboard">پیشخوان</a>
			<a class="<?php echo esc_attr( $nav['reports'] ); ?>" href="preview-admin.php?page=reports&amp;status=open">خطای پرداخت <span class="awaiting-mod"><?php echo (int) $open; ?></span></a>
			<a class="<?php echo esc_attr( $nav['digest'] ); ?>" href="preview-admin.php?page=digest">هفتگی / ماهانه</a>
		</nav>
		<main class="content">
<?php
if ( 'digest' === $page ) {
	include dirname( __DIR__ ) . '/templates/page-digest.php';
} elseif ( 'reports' === $page ) {
	$shown = array();
	foreach ( $rows as $row ) {
		if ( 'open' === $filter && 'resolved' === $row['status'] ) {
			continue;
		}
		if ( 'resolved' === $filter && 'resolved' !== $row['status'] ) {
			continue;
		}
		$shown[] = $row;
	}
	?>
			<div class="wrap npbr-wrap">
				<div class="npbr-hero">
					<div>
						<h1>گزارش‌های خطای پرداخت</h1>
						<p>فهرست خطاهای رسیده از مسیر خرید. موارد باز را رفع‌شده کنید تا در گزارش ماهانه دیده شوند.</p>
					</div>
				</div>
				<p class="npbr-tabs">
					<a class="button <?php echo '' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports' ) ); ?>">همه</a>
					<a class="button <?php echo 'open' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&status=open' ) ); ?>">باز</a>
					<a class="button <?php echo 'resolved' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&status=resolved' ) ); ?>">رفع‌شده</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-digest' ) ); ?>">گزارش هفتگی / ماهانه</a>
				</p>
				<div class="npbr-card">
					<table class="widefat striped npbr-table">
						<thead>
							<tr>
								<th>زمان</th>
								<th>صفحه</th>
								<th>موبایل</th>
								<th>خلاصه فنی</th>
								<th>وضعیت</th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $shown as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['date'] ); ?></td>
								<td dir="ltr"><?php echo esc_html( $row['pageUrl'] ); ?></td>
								<td dir="ltr"><?php echo $row['phone'] ? esc_html( $row['phone'] ) : '—'; ?></td>
								<td><?php echo esc_html( $row['summary'] ); ?></td>
								<td><?php echo 'resolved' === $row['status'] ? '<span class="npbr-badge is-done">رفع‌شده</span>' : '<span class="npbr-badge is-open">باز</span>'; ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
	<?php
} else {
	?>
			<h1>پیشخوان</h1>
			<div class="notice notice-warning">
				<p><strong>خطای خرید نهال:</strong> ۲ گزارش خطای خرید جدید رسیده.</p>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&status=open' ) ); ?>">مشاهده فهرست</a> — <a href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-digest' ) ); ?>">گزارش هفتگی و ماهانه</a></p>
			</div>
			<div class="postbox" id="npbr-dashboard">
				<h2>خطاهای خرید و پرداخت نهال</h2>
				<p>گزارش‌های باز: <strong><?php echo (int) $open; ?></strong> — ۷ روز گذشته: <strong><?php echo (int) $sum['total']; ?></strong> ثبت، <strong><?php echo (int) $sum['resolved']; ?></strong> رفع‌شده.</p>
				<ul>
				<?php foreach ( array_slice( $sum['issues'], 0, 5 ) as $issue ) : ?>
					<li><?php echo esc_html( $issue['label'] . ' (' . (int) $issue['count'] . ' — ' . ( ! empty( $issue['open'] ) ? 'باز' : 'رفع‌شده' ) . ')' ); ?></li>
				<?php endforeach; ?>
				</ul>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&status=open' ) ); ?>">فهرست خطاها</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-digest' ) ); ?>">گزارش هفتگی و ماهانه</a>
				</p>
			</div>
	<?php
}
?>
		</main>
	</div>
</body>
</html>
