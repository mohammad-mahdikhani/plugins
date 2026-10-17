<?php
/**
 * Reports inbox.
 *
 * @package NahalPaymentBugReporter
 * @var WP_Query $query
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		<?php $filter = isset( $filter ) ? (string) $filter : ''; ?>
		<a class="button <?php echo '' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports' ) ); ?>">همه</a>
		<a class="button <?php echo 'open' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&status=open' ) ); ?>">باز</a>
		<a class="button <?php echo 'resolved' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&status=resolved' ) ); ?>">رفع‌شده</a>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-digest' ) ); ?>">گزارش هفتگی / ماهانه</a>
	</p>

	<?php if ( isset( $_GET['deleted'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<div class="notice notice-success is-dismissible"><p>گزارش حذف شد.</p></div>
	<?php endif; ?>

	<div class="npbr-card">
		<?php if ( ! $query->have_posts() ) : ?>
			<p>هنوز گزارشی نرسیده است.</p>
		<?php else : ?>
			<table class="widefat striped npbr-table">
				<thead>
					<tr>
						<th>زمان</th>
						<th>صفحه</th>
						<th>موبایل</th>
						<th>خلاصه فنی</th>
						<th>وضعیت</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();
					$id      = get_the_ID();
					$url     = (string) get_post_meta( $id, '_npbr_page_url', true );
					$summary = (string) get_post_meta( $id, '_npbr_summary', true );
					$phone   = (string) get_post_meta( $id, '_npbr_phone', true );
					$st      = class_exists( 'NPBR_Stats', false ) ? NPBR_Stats::status( $id ) : 'open';
					?>
					<tr>
						<td><?php echo esc_html( get_the_date( 'Y-m-d H:i' ) ); ?></td>
						<td dir="ltr"><?php echo esc_html( $url ); ?></td>
						<td dir="ltr"><?php echo $phone ? '<a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a>' : '—'; ?></td>
						<td><?php echo esc_html( $summary ); ?></td>
						<td><?php echo 'resolved' === $st ? '<span class="npbr-badge is-done">رفع‌شده</span>' : '<span class="npbr-badge is-open">باز</span>'; ?></td>
						<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports&report=' . $id ) ); ?>">مشاهده</a></td>
					</tr>
				<?php endwhile; ?>
				</tbody>
			</table>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>
	</div>
</div>
