<?php
/**
 * Public AJAX: store report and email manager.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Ajax {

	/** @var self|null */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'wp_ajax_npbr_submit', array( $this, 'submit' ) );
		add_action( 'wp_ajax_nopriv_npbr_submit', array( $this, 'submit' ) );
		add_action( 'wp_ajax_npbr_download', array( $this, 'download' ) );
		add_action( 'wp_ajax_nopriv_npbr_download', array( $this, 'download' ) );
	}

	/**
	 * Force a real HTTP ZIP download so mobile browsers do not block blob saves.
	 */
	public function download() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'npbr_report' ) ) {
			wp_die( 'نشست منقضی شده. صفحه را تازه کنید و دوباره دانلود کنید.', 'خطا', array( 'response' => 403 ) );
		}

		$data = $this->payload_from_request();
		if ( is_wp_error( $data ) ) {
			wp_die( esc_html( $data->get_error_message() ), 'خطا', array( 'response' => 400 ) );
		}

		$tmp = wp_tempnam( 'npbr-report.zip' );
		if ( ! $tmp || ! NPBR_Zip::write( $data, $tmp ) ) {
			wp_die( 'ساخت فایل دانلود ممکن نشد.', 'خطا', array( 'response' => 500 ) );
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="nahal-payment-bug-report.zip"' );
		header( 'Content-Length: ' . (string) filesize( $tmp ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $tmp );
		@unlink( $tmp );
		exit;
	}

	/**
	 * @return array<string, mixed>|\WP_Error
	 */
	private function payload_from_request() {
		$raw = isset( $_POST['payload'] ) ? wp_unslash( $_POST['payload'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$decoded = is_string( $raw ) && $raw ? json_decode( $raw, true ) : array();
		if ( ! is_array( $decoded ) ) {
			return new WP_Error( 'npbr_bad', 'گزارش ناقص است.' );
		}
		if ( empty( $decoded['screenshot'] ) && ! empty( $_POST['screenshot'] ) ) {
			$decoded['screenshot'] = wp_unslash( $_POST['screenshot'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		$normalized = NPBR_Sanitizer::normalize_payload( $decoded );
		if ( ! $normalized['ok'] ) {
			return new WP_Error( 'npbr_bad', $normalized['error'] );
		}
		return $normalized['data'];
	}

	public function submit() {
		check_ajax_referer( 'npbr_report', 'nonce' );

		if ( $this->rate_limited() ) {
			wp_send_json_error( array( 'message' => 'چند گزارش پشت سر هم ارسال شده. کمی بعد دوباره تلاش کنید.' ), 429 );
		}

		$data = $this->payload_from_request();
		if ( is_wp_error( $data ) ) {
			wp_send_json_error( array( 'message' => $data->get_error_message() ), 400 );
		}

		$id = wp_insert_post(
			array(
				'post_type'    => 'npbr_report',
				'post_status'  => 'private',
				'post_title'   => NPBR_Report::title( $data ),
				'post_content' => $data['note'],
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			wp_send_json_error( array( 'message' => 'ذخیره گزارش ممکن نشد.' ), 500 );
		}

		$public = NPBR_Report::public_json( $data );
		update_post_meta( $id, '_npbr_payload', wp_json_encode( $public, JSON_UNESCAPED_UNICODE ) );
		update_post_meta( $id, '_npbr_agent_prompt', NPBR_Report::agent_prompt( $data ) );
		update_post_meta( $id, '_npbr_page_url', $data['pageUrl'] );
		update_post_meta( $id, '_npbr_summary', NPBR_Report::summary( $data ) );
		update_post_meta( $id, '_npbr_failed_count', count( $data['failed'] ) );
		update_post_meta( $id, '_npbr_ip', $this->ip() );

		if ( $data['screenshot'] ) {
			$attach = $this->store_screenshot( $id, $data['screenshot'] );
			if ( $attach ) {
				update_post_meta( $id, '_npbr_screenshot_id', $attach );
			}
		}

		$mailed = NPBR_Mailer::send( $data, $id );
		update_post_meta( $id, '_npbr_emailed', $mailed ? '1' : '0' );

		$telegram = false;
		if ( class_exists( 'NPBR_Telegram' ) && NPBR_Telegram::enabled() ) {
			$telegram = NPBR_Telegram::send( $data, $id );
			update_post_meta( $id, '_npbr_telegram', $telegram ? '1' : '0' );
			if ( ! $telegram ) {
				update_post_meta( $id, '_npbr_telegram_error', NPBR_Telegram::last_error() );
			}
		}

		$parts = array();
		if ( $mailed ) {
			$parts[] = 'برای مدیر ایمیل شد';
		}
		if ( $telegram ) {
			$parts[] = 'همزمان به تلگرام ارسال شد';
		}
		if ( ! $parts ) {
			$message = 'گزارش در پیشخوان ذخیره شد، اما ایمیل/تلگرام ارسال نشد. فایل را دانلود کنید و خودتان بفرستید.';
		} else {
			$message = 'گزارش ' . implode( ' و ', $parts ) . '. اگر بخواهید همان فایل را هم دانلود کنید.';
		}
		if ( class_exists( 'NPBR_Telegram' ) && NPBR_Telegram::enabled() && ! $telegram ) {
			$message .= ' تلگرام نرسید — روی هاست ایران api.telegram.org فیلتر است؛ آدرس Worker را در تنظیمات بگذارید.';
		}

		wp_send_json_success(
			array(
				'message'  => $message,
				'id'       => $id,
				'emailed'  => $mailed,
				'telegram' => $telegram,
			)
		);
	}

	/**
	 * @param int    $report_id Post ID.
	 * @param string $data_uri  Data URI.
	 * @return int
	 */
	private function store_screenshot( $report_id, $data_uri ) {
		if ( ! preg_match( '#^data:image/(png|jpeg|webp);base64,(.+)$#', $data_uri, $m ) ) {
			return 0;
		}
		$bin = base64_decode( $m[2], true );
		if ( false === $bin ) {
			return 0;
		}
		$ext      = 'jpeg' === $m[1] ? 'jpg' : $m[1];
		$filename = 'npbr-' . $report_id . '-' . gmdate( 'Ymd-His' ) . '.' . $ext;
		$upload   = wp_upload_bits( $filename, null, $bin );
		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return 0;
		}
		$filetype = wp_check_filetype( $filename, null );
		$attach   = wp_insert_attachment(
			array(
				'post_mime_type' => $filetype['type'] ? $filetype['type'] : ( 'image/' . $m[1] ),
				'post_title'     => 'اسکرین گزارش پرداخت ' . $report_id,
				'post_status'    => 'private',
				'post_parent'    => $report_id,
			),
			$upload['file'],
			$report_id,
			true
		);
		if ( is_wp_error( $attach ) ) {
			return 0;
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$meta = wp_generate_attachment_metadata( $attach, $upload['file'] );
		wp_update_attachment_metadata( $attach, $meta );
		return (int) $attach;
	}

	/**
	 * @return bool
	 */
	private function rate_limited() {
		$key   = 'npbr_rl_' . md5( $this->ip() );
		$count = (int) get_transient( $key );
		if ( $count >= 5 ) {
			return true;
		}
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return false;
	}

	/**
	 * @return string
	 */
	private function ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return $ip ? $ip : '0.0.0.0';
	}
}
