<?php
/**
 * Weekly / monthly digest.
 *
 * @package NahalPaymentBugReporter
 * @var array<string, mixed> $period
 * @var array<string, mixed> $sum
 * @var string $kind
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$kind = isset( $kind ) ? $kind : 'week';
$base = admin_url( 'admin.php?page=npbr-digest' );
?>
<div class="wrap npbr-wrap">
	<div class="npbr-hero">
		<div>
			<h1>گزارش هفتگی و ماهانه خطاها</h1>
			<p>چه خطاهایی در این بازه ثبت شده، چند بار تکرار شده و کدام‌ها رفع شده‌اند.</p>
		</div>
	</div>

	<p class="npbr-tabs">
		<a class="button <?php echo 'week' === $kind ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $base . '&range=week' ); ?>">۷ روز گذشته</a>
		<a class="button <?php echo 'month' === $kind ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $base . '&range=month' ); ?>">این ماه</a>
		<a class="button <?php echo 'last_month' === $kind ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $base . '&range=last_month' ); ?>">ماه قبل</a>
	</p>

	<p class="npbr-hero"><strong><?php echo esc_html( $period['label'] ); ?></strong></p>

	<div class="npbr-stats">
		<div class="npbr-stat"><span><?php echo (int) $sum['total']; ?></span>ثبت‌شده</div>
		<div class="npbr-stat is-open"><span><?php echo (int) $sum['open']; ?></span>باز</div>
		<div class="npbr-stat is-done"><span><?php echo (int) $sum['resolved']; ?></span>رفع‌شده</div>
		<div class="npbr-stat"><span><?php echo (int) $sum['auto']; ?></span>خودکار</div>
	</div>

	<div class="npbr-card">
		<h2>خطاهای این بازه</h2>
		<?php if ( empty( $sum['issues'] ) ) : ?>
			<p>در این بازه گزارشی ثبت نشده است.</p>
		<?php else : ?>
			<table class="widefat striped npbr-table">
				<thead>
					<tr>
						<th>خطا</th>
						<th>تکرار</th>
						<th>باز</th>
						<th>رفع‌شده</th>
						<th>وضعیت</th>
						<th>آخرین رخداد</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $sum['issues'] as $issue ) : ?>
					<tr>
						<td><?php echo esc_html( isset( $issue['label'] ) ? $issue['label'] : '' ); ?></td>
						<td><?php echo (int) $issue['count']; ?></td>
						<td><?php echo (int) $issue['open']; ?></td>
						<td><?php echo (int) $issue['resolved']; ?></td>
						<td><?php echo ! empty( $issue['open'] ) ? '<span class="npbr-badge is-open">باز</span>' : '<span class="npbr-badge is-done">رفع‌شده</span>'; ?></td>
						<td><?php echo esc_html( isset( $issue['lastDate'] ) ? $issue['lastDate'] : '' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<p class="npbr-hero">خطاهای تکراری با هم گروه‌بندی می‌شوند. اگر حداقل یک مورد باز باشد وضعیت «باز» می‌ماند؛ وقتی همهٔ موارد آن خطا رفع‌شده باشند ستون وضعیت سبز می‌شود.</p>
		<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports' ) ); ?>">فهرست کامل گزارش‌ها</a></p>
	</div>
</div>
