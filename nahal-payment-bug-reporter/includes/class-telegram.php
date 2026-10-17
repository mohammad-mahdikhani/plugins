<?php
/**
 * Send the payment bug report to Telegram at the same time as email.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NPBR_TEST' ) ) {
	exit;
}

class NPBR_Telegram {

	/**
	 * @return bool
	 */
	public static function enabled() {
		if ( ! function_exists( 'get_option' ) ) {
			return false;
		}
		$s = NPBR_Plugin::settings();
		return ! empty( $s['telegram_enabled'] ) && self::token() && self::chat_id();
	}

	/**
	 * @return string
	 */
	public static function token() {
		$s = NPBR_Plugin::settings();
		return isset( $s['telegram_bot_token'] ) ? (string) $s['telegram_bot_token'] : '';
	}

	/**
	 * @return string
	 */
	public static function chat_id() {
		$s = NPBR_Plugin::settings();
		return isset( $s['telegram_chat_id'] ) ? (string) $s['telegram_chat_id'] : '';
	}

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @return string
	 */
	public static function text( array $data ) {
		$lines   = array();
		$lines[] = 'گزارش خطای پرداخت نهال';
		$lines[] = '';
		$lines[] = 'شرح: ' . ( isset( $data['note'] ) ? $data['note'] : '' );
		if ( ! empty( $data['name'] ) || ! empty( $data['phone'] ) ) {
			$lines[] = 'نام: ' . ( isset( $data['name'] ) && $data['name'] ? $data['name'] : '—' ) . ' — موبایل: ' . ( isset( $data['phone'] ) && $data['phone'] ? $data['phone'] : '—' );
		}
		$lines[] = 'صفحه: ' . ( isset( $data['pageUrl'] ) ? $data['pageUrl'] : '' );
		$lines[] = 'زمان: ' . ( isset( $data['capturedAt'] ) ? $data['capturedAt'] : '' );
		$lines[] = '';
		$failed  = isset( $data['failed'] ) && is_array( $data['failed'] ) ? $data['failed'] : array();
		$lines[] = 'کدها و فایل‌هایی که لود نشدند (' . count( $failed ) . '):';
		if ( $failed ) {
			foreach ( array_slice( $failed, 0, 15 ) as $i => $row ) {
				$lines[] = ( $i + 1 ) . '. ' . ( isset( $row['status'] ) ? $row['status'] : '' ) . ' — ' . ( isset( $row['url'] ) ? $row['url'] : '' );
			}
		} else {
			$lines[] = 'موردی ثبت نشد.';
		}
		$errors = isset( $data['errors'] ) && is_array( $data['errors'] ) ? $data['errors'] : array();
		$lines[] = '';
		$lines[] = 'کنسول (' . count( $errors ) . '):';
		if ( $errors ) {
			foreach ( array_slice( $errors, 0, 12 ) as $row ) {
				$lines[] = '- ' . ( isset( $row['message'] ) ? $row['message'] : '' );
			}
		} else {
			$lines[] = 'خطای کنسولی ثبت نشد.';
		}
		$text = implode( "\n", $lines );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, 3500 );
		}
		return substr( $text, 0, 3500 );
	}

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @param int                  $report_id Report id.
	 * @return bool
	 */
	public static function send( array $data, $report_id = 0 ) {
		if ( ! self::enabled() ) {
			return false;
		}
		$token   = self::token();
		$chat    = self::chat_id();
		$text    = self::text( $data );
		$caption = $text;
		if ( function_exists( 'mb_substr' ) ) {
			$caption = mb_substr( $text, 0, 1024 );
		} else {
			$caption = substr( $text, 0, 1024 );
		}

		$sent = false;
		if ( ! empty( $data['screenshot'] ) && preg_match( '#^data:image/(png|jpeg|webp);base64,(.+)$#', (string) $data['screenshot'], $m ) ) {
			$bin = base64_decode( $m[2], true );
			if ( false !== $bin ) {
				$ext  = 'jpeg' === $m[1] ? 'jpg' : $m[1];
				$sent = self::api_multipart(
					$token,
					'sendPhoto',
					array(
						'chat_id'    => $chat,
						'caption'    => $caption,
					),
					'photo',
					'screenshot.' . $ext,
					$bin,
					'image/' . ( 'jpg' === $ext ? 'jpeg' : $ext )
				);
			}
		}

		if ( ! $sent ) {
			$sent = self::api(
				$token,
				'sendMessage',
				array(
					'chat_id' => $chat,
					'text'    => $text,
				)
			);
		} elseif ( strlen( $text ) > 1024 ) {
			self::api(
				$token,
				'sendMessage',
				array(
					'chat_id' => $chat,
					'text'    => $text,
				)
			);
		}

		return $sent;
	}

	/**
	 * @param string               $token Bot token.
	 * @param string               $method Method.
	 * @param array<string, mixed> $fields Fields.
	 * @return bool
	 */
	public static function api( $token, $method, array $fields ) {
		$url = 'https://api.telegram.org/bot' . $token . '/' . $method;
		if ( ! function_exists( 'wp_remote_post' ) ) {
			return false;
		}
		$res = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'body'    => $fields,
			)
		);
		if ( is_wp_error( $res ) ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$body = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return 200 === $code && ! empty( $body['ok'] );
	}

	/**
	 * @param string               $token Bot token.
	 * @param string               $method Method.
	 * @param array<string, string> $fields Fields.
	 * @param string               $file_field File field name.
	 * @param string               $filename Filename.
	 * @param string               $binary File bytes.
	 * @param string               $mime Mime.
	 * @return bool
	 */
	public static function api_multipart( $token, $method, array $fields, $file_field, $filename, $binary, $mime ) {
		$boundary = function_exists( 'wp_generate_password' ) ? wp_generate_password( 24, false ) : uniqid( 'npbr', true );
		$body     = '';
		foreach ( $fields as $name => $value ) {
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
			$body .= $value . "\r\n";
		}
		$body .= '--' . $boundary . "\r\n";
		$body .= 'Content-Disposition: form-data; name="' . $file_field . '"; filename="' . $filename . '"' . "\r\n";
		$body .= 'Content-Type: ' . $mime . "\r\n\r\n";
		$body .= $binary . "\r\n";
		$body .= '--' . $boundary . "--\r\n";

		$url = 'https://api.telegram.org/bot' . $token . '/' . $method;
		$res = wp_remote_post(
			$url,
			array(
				'timeout' => 25,
				'headers' => array(
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);
		if ( is_wp_error( $res ) ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return 200 === $code && ! empty( $json['ok'] );
	}
}
