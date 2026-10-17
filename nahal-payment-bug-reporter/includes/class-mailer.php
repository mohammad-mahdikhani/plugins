<?php
/**
 * Email the manager a payment bug report.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Mailer {

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @param int                  $report_id Report post ID.
	 * @return bool
	 */
	public static function send( array $data, $report_id = 0 ) {
		$to = NPBR_Plugin::manager_email();
		if ( ! $to || ! is_email( $to ) ) {
			return false;
		}

		$subject = 'گزارش خطای پرداخت نهال — ' . ( isset( $data['pageTitle'] ) ? $data['pageTitle'] : 'صفحه پرداخت' );
		$body    = self::body( $data, $report_id );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$attachments = array();
		$tmps        = array();
		if ( ! empty( $data['screenshot'] ) && preg_match( '#^data:image/(png|jpeg|webp);base64,(.+)$#', (string) $data['screenshot'], $m ) ) {
			$bin = base64_decode( $m[2], true );
			if ( false !== $bin ) {
				$ext = 'jpeg' === $m[1] ? 'jpg' : $m[1];
				$tmp = wp_tempnam( 'npbr-shot.' . $ext );
				if ( $tmp ) {
					file_put_contents( $tmp, $bin );
					$attachments[] = $tmp;
					$tmps[]        = $tmp;
				}
			}
		}
		$prompt = NPBR_Report::agent_prompt( $data );
		if ( $prompt ) {
			$prompt_tmp = trailingslashit( get_temp_dir() ) . 'npbr-agent-prompt-' . uniqid( '', true ) . '.md';
			if ( file_put_contents( $prompt_tmp, $prompt ) ) {
				$attachments[] = $prompt_tmp;
				$tmps[]        = $prompt_tmp;
			}
		}

		$ok = wp_mail( $to, $subject, $body, $headers, $attachments );
		foreach ( $tmps as $tmp ) {
			if ( $tmp && file_exists( $tmp ) ) {
				@unlink( $tmp );
			}
		}
		return (bool) $ok;
	}

	/**
	 * @param array<string, mixed> $data Data.
	 * @param int                  $report_id ID.
	 * @return string
	 */
	public static function body( array $data, $report_id = 0 ) {
		$admin = $report_id ? admin_url( 'admin.php?page=npbr-reports&report=' . (int) $report_id ) : admin_url( 'admin.php?page=npbr-reports' );
		$lines = array();
		$lines[] = '<p>یک گزارش خطای پرداخت از سایت نهال ثبت شد.</p>';
		$lines[] = '<p><strong>شرح کاربر:</strong></p><p>' . nl2br( esc_html( isset( $data['note'] ) ? $data['note'] : '' ) ) . '</p>';
		$lines[] = '<p>نام: ' . esc_html( isset( $data['name'] ) && $data['name'] ? $data['name'] : '—' ) . '<br>';
		$lines[] = 'موبایل: ' . esc_html( isset( $data['phone'] ) && $data['phone'] ? $data['phone'] : '—' ) . '<br>';
		$lines[] = 'صفحه: ' . esc_html( isset( $data['pageUrl'] ) ? $data['pageUrl'] : '' ) . '<br>';
		$lines[] = 'خلاصه فنی: ' . esc_html( NPBR_Report::summary( $data ) ) . '</p>';

		$failed = isset( $data['failed'] ) && is_array( $data['failed'] ) ? $data['failed'] : array();
		if ( $failed ) {
			$lines[] = '<p><strong>کدها و فایل‌هایی که لود نشدند:</strong></p><ul>';
			foreach ( array_slice( $failed, 0, 25 ) as $row ) {
				$label   = ! empty( $row['payment'] ) ? '[پرداخت] ' : '';
				$lines[] = '<li>' . esc_html( $label . ( isset( $row['kind'] ) ? $row['kind'] : '' ) . ' — ' . ( isset( $row['status'] ) ? $row['status'] : '' ) . ' — ' . ( isset( $row['url'] ) ? $row['url'] : '' ) ) . '</li>';
			}
			$lines[] = '</ul>';
		}

		$lines[] = '<p>اسکرین‌شات صفحه و فایل <code>agent-prompt.md</code> (پرامپت آماده برای ایجنت هوش مصنوعی) در پیوست همین ایمیل هستند.</p>';
		$lines[] = '<p><a href="' . esc_url( $admin ) . '">مشاهده در پیشخوان</a></p>';
		return implode( "\n", $lines );
	}
}
