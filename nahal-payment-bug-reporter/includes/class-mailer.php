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

		$prefix  = ! empty( $data['auto'] ) ? 'هشدار خودکار پرداخت نهال — ' : 'گزارش خطای پرداخت نهال — ';
		$subject = $prefix . ( isset( $data['pageTitle'] ) ? $data['pageTitle'] : 'صفحه پرداخت' );
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
		$admin  = $report_id ? admin_url( 'admin.php?page=npbr-reports&report=' . (int) $report_id ) : admin_url( 'admin.php?page=npbr-reports' );
		$prompt = NPBR_Report::agent_prompt( $data );
		$vitals = NPBR_Report::vital_failures( $data );
		$auto   = ! empty( $data['auto'] );

		$html   = array();
		$html[] = '<div style="font-family:Tahoma,Vazirmatn,sans-serif;direction:rtl;text-align:right;color:#14221c;max-width:720px">';
		$html[] = $auto
			? '<h1 style="font-size:18px;margin:0 0 12px">هشدار خودکار: المان حیاتی صفحه پرداخت لود نشد</h1>'
			: '<h1 style="font-size:18px;margin:0 0 12px">گزارش خطای پرداخت نهال</h1>';
		$html[] = '<p style="line-height:1.8;color:#3d5349">صفحه: ' . esc_html( isset( $data['pageTitle'] ) ? $data['pageTitle'] : '' ) . '<br>';
		$html[] = '<span dir="ltr">' . esc_html( isset( $data['pageUrl'] ) ? $data['pageUrl'] : '' ) . '</span></p>';

		$contact = NPBR_Report::contact_lines( $data );
		$c       = isset( $data['customer'] ) && is_array( $data['customer'] ) ? $data['customer'] : array();
		$phone   = isset( $c['phone'] ) && $c['phone'] ? (string) $c['phone'] : ( isset( $data['phone'] ) ? (string) $data['phone'] : '' );
		$html[]  = '<div style="background:#eef6ff;border:1px solid #cfe0f5;border-radius:12px;padding:14px 16px;margin:0 0 16px">';
		$html[]  = '<p style="margin:0 0 8px;font-weight:700">تماس با کاربر</p>';
		$html[]  = '<pre style="white-space:pre-wrap;font-family:Tahoma,sans-serif;margin:0;line-height:1.8">' . esc_html( implode( "\n", $contact ) ) . '</pre>';
		if ( $phone ) {
			$html[] = '<p style="margin:10px 0 0">تماس: <a dir="ltr" href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></p>';
		}
		$html[] = '</div>';

		$html[] = '<div style="background:#f7faf6;border:1px solid #d7e4dc;border-radius:12px;padding:14px 16px;margin:0 0 16px">';
		$html[] = '<p style="margin:0 0 8px;font-weight:700">شرح</p>';
		$html[] = '<pre style="white-space:pre-wrap;font-family:Tahoma,sans-serif;margin:0;line-height:1.8">' . esc_html( isset( $data['note'] ) ? $data['note'] : '' ) . '</pre>';
		$html[] = '</div>';

		if ( $vitals ) {
			$html[] = '<p style="font-weight:700;margin:0 0 8px">فهرست المان‌های حیاتی</p>';
			$html[] = '<ol style="line-height:1.9;padding-right:22px">';
			foreach ( $vitals as $row ) {
				$item = esc_html( isset( $row['title'] ) ? $row['title'] : '' );
				if ( ! empty( $row['status'] ) ) {
					$item .= ' <span style="color:#9a3412">[' . esc_html( $row['status'] ) . ']</span>';
				}
				if ( ! empty( $row['url'] ) ) {
					$item .= '<br><span dir="ltr" style="font-size:12px;color:#4b6358">' . esc_html( $row['url'] ) . '</span>';
				}
				$html[] = '<li style="margin:0 0 8px">' . $item . '</li>';
			}
			$html[] = '</ol>';
		}

		$failed = isset( $data['failed'] ) && is_array( $data['failed'] ) ? $data['failed'] : array();
		if ( $failed ) {
			$html[] = '<p style="font-weight:700;margin:16px 0 8px">همه کدها و فایل‌های لودنشده</p><ul style="line-height:1.8">';
			foreach ( array_slice( $failed, 0, 25 ) as $row ) {
				$label  = ! empty( $row['payment'] ) ? '[پرداخت] ' : '';
				$html[] = '<li>' . esc_html( $label . ( isset( $row['kind'] ) ? $row['kind'] : '' ) . ' — ' . ( isset( $row['status'] ) ? $row['status'] : '' ) . ' — ' . ( isset( $row['url'] ) ? $row['url'] : '' ) ) . '</li>';
			}
			$html[] = '</ul>';
		}

		$html[] = '<p style="color:#3d5349">اسکرین‌شات همان صفحه در پیوست است (اگر گرفته شده باشد).</p>';
		$html[] = '<p style="font-weight:700;margin:18px 0 8px">پرامپت آماده برای ایجنت هوش مصنوعی</p>';
		$html[] = '<p style="color:#5b7268;font-size:13px">این متن را کپی کنید و همراه اسکرین برای Cursor یا ایجنت دیگر بفرستید.</p>';
		$html[] = '<pre style="white-space:pre-wrap;background:#14221c;color:#f4f7f3;border-radius:12px;padding:14px;font-size:12px;line-height:1.7;direction:rtl">' . esc_html( $prompt ) . '</pre>';
		$html[] = '<p><a href="' . esc_url( $admin ) . '">مشاهده در پیشخوان</a></p>';
		$html[] = '</div>';
		return implode( "\n", $html );
	}

	/**
	 * @param string               $subject Subject.
	 * @param array<string, mixed> $sum     Summary.
	 * @param array<string, mixed> $period  Period.
	 * @return bool
	 */
	public static function send_digest( $subject, array $sum, array $period ) {
		$to = NPBR_Plugin::manager_email();
		if ( ! $to || ! is_email( $to ) ) {
			return false;
		}
		$list = admin_url( 'admin.php?page=npbr-digest' );
		$html   = array();
		$html[] = '<div style="font-family:Tahoma,Vazirmatn,sans-serif;direction:rtl;text-align:right;color:#14221c;max-width:720px">';
		$html[] = '<h1 style="font-size:18px;margin:0 0 12px">' . esc_html( $subject ) . '</h1>';
		$html[] = '<p style="color:#3d5349">بازه: ' . esc_html( isset( $period['label'] ) ? $period['label'] : '' ) . '</p>';
		$html[] = '<p><strong>ثبت‌شده:</strong> ' . (int) $sum['total']
			. ' — <strong>باز:</strong> ' . (int) $sum['open']
			. ' — <strong>رفع‌شده:</strong> ' . (int) $sum['resolved'] . '</p>';
		$issues = isset( $sum['issues'] ) && is_array( $sum['issues'] ) ? $sum['issues'] : array();
		if ( $issues ) {
			$html[] = '<ol style="line-height:1.9;padding-right:22px">';
			foreach ( array_slice( $issues, 0, 25 ) as $issue ) {
				$state = ! empty( $issue['open'] ) ? 'باز' : 'رفع‌شده';
				$html[] = '<li>' . esc_html( ( isset( $issue['label'] ) ? $issue['label'] : '' ) . ' — ' . (int) $issue['count'] . ' بار — ' . $state ) . '</li>';
			}
			$html[] = '</ol>';
		} else {
			$html[] = '<p>در این بازه گزارشی نبود.</p>';
		}
		$html[] = '<p><a href="' . esc_url( $list ) . '">مشاهده گزارش دوره‌ای در پیشخوان</a></p>';
		$html[] = '</div>';
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		return (bool) wp_mail( $to, $subject, implode( "\n", $html ), $headers );
	}
}
