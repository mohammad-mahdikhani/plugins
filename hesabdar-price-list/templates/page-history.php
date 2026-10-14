<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap hpl-wrap" dir="rtl">
	<div class="hpl-hero">
		<div>
			<h1>تاریخچه لیست قیمت</h1>
			<p>هر بار که لیست جدیدی اعمال می‌شود، قیمت‌های قبلی ذخیره می‌شوند تا بتوانید برگردید.</p>
		</div>
		<a class="hpl-btn hpl-btn-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=hesabdar-price-list' ) ); ?>">آپلود لیست جدید</a>
	</div>

	<div class="hpl-card">
		<?php if ( empty( $imports ) ) : ?>
			<p class="hpl-empty">هنوز لیست قیمتی اعمال نشده است.</p>
		<?php else : ?>
			<table class="hpl-table">
				<thead>
					<tr>
						<th>#</th>
						<th>فایل</th>
						<th>نوع</th>
						<th>تاریخ</th>
						<th>آمار</th>
						<th>وضعیت</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $imports as $import ) : ?>
					<?php
					$stats = json_decode( $import['stats'], true );
					if ( ! is_array( $stats ) ) {
						$stats = array();
					}
					$applied  = ! empty( $import['applied_at'] );
					$rolled   = ! empty( $import['rolled_back_at'] );
					?>
					<tr>
						<td><?php echo (int) $import['id']; ?></td>
						<td><?php echo esc_html( $import['filename'] ); ?></td>
						<td><?php echo esc_html( strtoupper( $import['file_type'] ) ); ?></td>
						<td><?php echo esc_html( $import['created_at'] ); ?></td>
						<td>
							تغییر: <?php echo isset( $stats['changed'] ) ? (int) $stats['changed'] : 0; ?>
							—
							ناموجود: <?php echo isset( $stats['missing'] ) ? (int) $stats['missing'] : 0; ?>
						</td>
						<td>
							<?php if ( $rolled ) : ?>
								<span class="hpl-pill hpl-pill-muted">بازگشت داده شد</span>
							<?php elseif ( $applied ) : ?>
								<span class="hpl-pill hpl-pill-ok">اعمال شده</span>
							<?php else : ?>
								<span class="hpl-pill">ناتمام</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( $applied && ! $rolled ) : ?>
								<button type="button" class="hpl-btn hpl-btn-ghost hpl-rollback" data-id="<?php echo (int) $import['id']; ?>">بازگشت به قیمت قبلی</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
</div>
