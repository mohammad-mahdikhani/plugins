<?php
/**
 * Single report.
 *
 * @package NahalPaymentBugReporter
 * @var WP_Post $post
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id      = (int) $post->ID;
$payload = json_decode( (string) get_post_meta( $id, '_npbr_payload', true ), true );
if ( ! is_array( $payload ) ) {
	$payload = array();
}
$shot_id = (int) get_post_meta( $id, '_npbr_screenshot_id', true );
$shot    = $shot_id ? wp_get_attachment_url( $shot_id ) : '';
$failed  = isset( $payload['failed'] ) && is_array( $payload['failed'] ) ? $payload['failed'] : array();
$errors  = isset( $payload['errors'] ) && is_array( $payload['errors'] ) ? $payload['errors'] : array();
$payment = isset( $payload['payment'] ) && is_array( $payload['payment'] ) ? $payload['payment'] : array();
?>
<div class="wrap npbr-wrap">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=npbr-reports' ) ); ?>">← همه گزارش‌ها</a></p>
	<div class="npbr-hero">
		<div>
			<h1><?php echo esc_html( get_the_title( $post ) ); ?></h1>
			<p><?php echo esc_html( (string) get_post_meta( $id, '_npbr_summary', true ) ); ?></p>
		</div>
	</div>

	<div class="npbr-card">
		<h2>شرح کاربر</h2>
		<div class="npbr-note-box"><?php echo nl2br( esc_html( $post->post_content ) ); ?></div>
		<p>نام: <?php echo esc_html( isset( $payload['name'] ) && $payload['name'] ? $payload['name'] : '—' ); ?> —
			موبایل: <?php echo esc_html( isset( $payload['phone'] ) && $payload['phone'] ? $payload['phone'] : '—' ); ?></p>
		<p dir="ltr">URL: <?php echo esc_html( isset( $payload['pageUrl'] ) ? $payload['pageUrl'] : '' ); ?></p>
		<p>مرورگر: <?php echo esc_html( isset( $payload['userAgent'] ) ? $payload['userAgent'] : '' ); ?></p>
		<?php if ( '1' === (string) get_post_meta( $id, '_npbr_auto', true ) ) : ?>
			<p><strong>گزارش خودکار:</strong> المان حیاتی صفحه پرداخت لود نشده بود.</p>
		<?php endif; ?>
		<?php
		$tg_flag = get_post_meta( $id, '_npbr_telegram', true );
		$tg_err  = (string) get_post_meta( $id, '_npbr_telegram_error', true );
		if ( '1' === $tg_flag ) {
			echo '<p>تلگرام: ارسال شد.</p>';
		} elseif ( '0' === $tg_flag ) {
			echo '<p>تلگرام: نرسید';
			if ( $tg_err ) {
				echo ' — ' . esc_html( $tg_err );
			}
			echo '</p>';
		}
		?>
	</div>

	<div class="npbr-card">
		<h2>کدها و فایل‌هایی که لود نشدند</h2>
		<?php if ( ! $failed ) : ?>
			<p>موردی ثبت نشده.</p>
		<?php else : ?>
			<table class="widefat striped">
				<thead><tr><th>پرداخت؟</th><th>جنس</th><th>وضعیت</th><th>نشانی</th></tr></thead>
				<tbody>
				<?php foreach ( $failed as $row ) : ?>
					<tr>
						<td><?php echo ! empty( $row['payment'] ) ? 'بله' : 'خیر'; ?></td>
						<td><?php echo esc_html( isset( $row['kind'] ) ? $row['kind'] : '' ); ?></td>
						<td><?php echo esc_html( isset( $row['status'] ) ? $row['status'] : '' ); ?></td>
						<td dir="ltr"><?php echo esc_html( isset( $row['url'] ) ? $row['url'] : '' ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>

	<div class="npbr-card">
		<h2>خطاهای جاوااسکریپت</h2>
		<?php if ( ! $errors ) : ?>
			<p>موردی ثبت نشده.</p>
		<?php else : ?>
			<ul>
				<?php foreach ( $errors as $row ) : ?>
					<li><?php echo esc_html( ( isset( $row['type'] ) ? $row['type'] : '' ) . ': ' . ( isset( $row['message'] ) ? $row['message'] : '' ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="npbr-card">
		<h2>فیلدهای پرداخت</h2>
		<p>باکس پرداخت: <?php echo ! empty( $payment['foundPaymentBox'] ) ? 'پیدا شد' : 'پیدا نشد'; ?>
			<?php echo ! empty( $payment['emptyBox'] ) ? ' — خالی/رندر نشده' : ''; ?>
			<?php echo ! empty( $payment['noMethods'] ) ? ' — روش پرداختی لود نشد' : ''; ?></p>
		<?php if ( ! empty( $payment['methods'] ) ) : ?>
			<ul>
				<?php foreach ( $payment['methods'] as $method ) : ?>
					<li><?php echo esc_html( ( isset( $method['label'] ) ? $method['label'] : '' ) . ' (' . ( isset( $method['id'] ) ? $method['id'] : '' ) . ')' ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<div class="npbr-card">
		<h2>پرامپت آماده برای ایجنت هوش مصنوعی</h2>
		<p>این متن را کپی کنید و همراه اسکرین همان صفحه برای ایجنت بفرستید تا خطا را روی همان URL بررسی کند.</p>
		<?php
		$prompt = (string) get_post_meta( $id, '_npbr_agent_prompt', true );
		if ( ! $prompt ) {
			$payload['note'] = $post->post_content;
			$prompt          = NPBR_Report::agent_prompt( $payload, $shot );
		}
		?>
		<textarea id="npbr-agent-prompt" class="large-text code" rows="16" readonly><?php echo esc_textarea( $prompt ); ?></textarea>
		<p>
			<button type="button" class="button button-primary" id="npbr-copy-agent-prompt">کپی پرامپت</button>
			<button type="button" class="button" id="npbr-dl-agent-prompt">دانلود agent-prompt.md</button>
		</p>
	</div>

	<div class="npbr-card">
		<h2>اسکرین‌شات</h2>
		<?php if ( $shot ) : ?>
			<p><a class="button" href="<?php echo esc_url( $shot ); ?>" download>دانلود اسکرین</a></p>
			<p><img src="<?php echo esc_url( $shot ); ?>" alt="اسکرین‌شات" style="max-width:100%;border:1px solid #d7e4dc;border-radius:12px"></p>
		<?php else : ?>
			<p>اسکرینی ذخیره نشده.</p>
		<?php endif; ?>
	</div>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('این گزارش حذف شود؟');">
		<?php wp_nonce_field( 'npbr_delete_report' ); ?>
		<input type="hidden" name="action" value="npbr_delete_report">
		<input type="hidden" name="report_id" value="<?php echo esc_attr( (string) $id ); ?>">
		<button type="submit" class="button">حذف گزارش</button>
	</form>
</div>
<script>
(function () {
	var ta = document.getElementById('npbr-agent-prompt');
	var copyBtn = document.getElementById('npbr-copy-agent-prompt');
	var dlBtn = document.getElementById('npbr-dl-agent-prompt');
	if (!ta) {
		return;
	}
	function text() {
		return ta.value || '';
	}
	if (copyBtn) {
		copyBtn.addEventListener('click', function () {
			var t = text();
			var done = function () { copyBtn.textContent = 'کپی شد'; };
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(t).then(done).catch(function () {
					ta.select();
					document.execCommand('copy');
					done();
				});
			} else {
				ta.select();
				document.execCommand('copy');
				done();
			}
		});
	}
	if (dlBtn) {
		dlBtn.addEventListener('click', function () {
			var blob = new Blob([text()], { type: 'text/markdown;charset=utf-8' });
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = 'agent-prompt.md';
			document.body.appendChild(a);
			a.click();
			a.remove();
			setTimeout(function () { URL.revokeObjectURL(url); }, 2000);
		});
	}
})();
</script>
