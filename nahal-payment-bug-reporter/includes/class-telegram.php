<?php
/**
 * Send the payment bug report to Telegram at the same time as email.
 *
 * From Iranian hosts api.telegram.org is often filtered. The plugin tries a
 * self-hosted HTTPS reverse proxy (Cloudflare Worker) first, then the official
 * API, and can send those requests through an optional HTTP proxy.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NPBR_TEST' ) ) {
	exit;
}

class NPBR_Telegram {

	const OFFICIAL_API = 'https://api.telegram.org';

	/** @var string */
	private static $last_error = '';

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
		if ( ! class_exists( 'NPBR_Plugin', false ) ) {
			return '';
		}
		$s = NPBR_Plugin::settings();
		return isset( $s['telegram_bot_token'] ) ? (string) $s['telegram_bot_token'] : '';
	}

	/**
	 * @return string
	 */
	public static function chat_id() {
		if ( ! class_exists( 'NPBR_Plugin', false ) ) {
			return '';
		}
		$s = NPBR_Plugin::settings();
		return isset( $s['telegram_chat_id'] ) ? (string) $s['telegram_chat_id'] : '';
	}

	/**
	 * @return string
	 */
	public static function last_error() {
		return self::$last_error;
	}

	/**
	 * Own reverse-proxy origin, e.g. https://npbr-tg.example.workers.dev
	 *
	 * @param mixed $custom Optional override for tests.
	 * @return string
	 */
	public static function custom_api_base( $custom = null ) {
		if ( null === $custom ) {
			$custom = '';
			if ( class_exists( 'NPBR_Plugin', false ) ) {
				$s      = NPBR_Plugin::settings();
				$custom = isset( $s['telegram_api_base'] ) ? $s['telegram_api_base'] : '';
			}
		}
		return self::sanitize_api_base( $custom );
	}

	/**
	 * Try the self-hosted proxy first, then the official Bot API.
	 *
	 * @param mixed $custom Optional override for tests.
	 * @return array<int, string>
	 */
	public static function api_bases( $custom = null ) {
		$bases  = array();
		$custom = self::custom_api_base( $custom );
		if ( $custom && 0 !== strcasecmp( $custom, self::OFFICIAL_API ) ) {
			$bases[] = $custom;
		}
		$bases[] = self::OFFICIAL_API;
		return array_values( array_unique( $bases ) );
	}

	/**
	 * @param string $base   Origin without /bot.
	 * @param string $token  Bot token.
	 * @param string $method Method name.
	 * @return string
	 */
	public static function build_url( $base, $token, $method ) {
		$base   = rtrim( (string) $base, '/' );
		$token  = (string) $token;
		$method = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $method );
		return $base . '/bot' . $token . '/' . $method;
	}

	/**
	 * Accept https://worker.example/ or a pasted .../botTOKEN URL (bot path stripped).
	 *
	 * @param mixed $url URL.
	 * @return string
	 */
	public static function sanitize_api_base( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			if ( ! preg_match( '/^[A-Za-z0-9.\-]+(:\d{2,5})?(\/.*)?$/', $url ) ) {
				return '';
			}
			$url = 'https://' . ltrim( $url, '/' );
		}
		$parts = parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! empty( $parts['user'] ) ) {
			return '';
		}
		$scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}
		$host = strtolower( (string) $parts['host'] );
		if ( ! preg_match( '/^[a-z0-9.\-]+$/', $host ) ) {
			return '';
		}
		$port = isset( $parts['port'] ) ? ':' . (int) $parts['port'] : '';
		$path = isset( $parts['path'] ) ? (string) $parts['path'] : '';
		$path = preg_replace( '#/bot[^/]*(/.*)?$#i', '', $path );
		$path = rtrim( (string) $path, '/' );
		if ( strlen( $path ) > 80 ) {
			return '';
		}
		return $scheme . '://' . $host . $port . $path;
	}

	/**
	 * Normalize host:port or http://user:pass@host:port for CURLOPT_PROXY.
	 *
	 * @param mixed $raw Raw proxy.
	 * @return string
	 */
	public static function sanitize_http_proxy( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}
		if ( preg_match( '/^([A-Za-z0-9.\-]+):(\d{2,5})$/', $raw, $m ) ) {
			$port = (int) $m[2];
			if ( $port < 1 || $port > 65535 ) {
				return '';
			}
			return $m[1] . ':' . $port;
		}
		if ( ! preg_match( '#^https?://#i', $raw ) ) {
			return '';
		}
		$parts = parse_url( $raw );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}
		$host = (string) $parts['host'];
		$port = isset( $parts['port'] ) ? (int) $parts['port'] : ( isset( $parts['scheme'] ) && 'https' === $parts['scheme'] ? 443 : 80 );
		if ( $port < 1 || $port > 65535 ) {
			return '';
		}
		$out = $host . ':' . $port;
		if ( ! empty( $parts['user'] ) ) {
			$user = rawurldecode( (string) $parts['user'] );
			$pass = isset( $parts['pass'] ) ? rawurldecode( (string) $parts['pass'] ) : '';
			$out  = $user . ':' . $pass . '@' . $out;
		}
		return $out;
	}

	/**
	 * @return string
	 */
	public static function http_proxy() {
		if ( ! class_exists( 'NPBR_Plugin', false ) ) {
			return '';
		}
		$s = NPBR_Plugin::settings();
		return isset( $s['telegram_http_proxy'] ) ? self::sanitize_http_proxy( $s['telegram_http_proxy'] ) : '';
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
						'chat_id' => $chat,
						'caption' => $caption,
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
	 * Send a short connectivity check. Token + chat are enough; the enable checkbox is not required.
	 *
	 * @return array{ok:bool,message:string}
	 */
	public static function test() {
		$token = self::token();
		$chat  = self::chat_id();
		if ( ! $token || ! $chat ) {
			return array(
				'ok'      => false,
				'message' => 'اول توکن ربات و Chat ID را ذخیره کنید، بعد تست بزنید.',
			);
		}
		$ok = self::api(
			$token,
			'sendMessage',
			array(
				'chat_id' => $chat,
				'text'    => 'تست گزارشگر خطای پرداخت نهال — اگر این پیام را می‌بینید، اتصال تلگرام برقرار است.',
			)
		);
		if ( $ok ) {
			return array(
				'ok'      => true,
				'message' => 'پیام تست به تلگرام رسید.',
			);
		}
		$err = self::last_error();
		if ( ! $err ) {
			$err = 'ارسال ناموفق. روی هاست ایران api.telegram.org فیلتر است؛ Worker خودتان را در «آدرس پروکسی API» بگذارید.';
		}
		return array(
			'ok'      => false,
			'message' => $err,
		);
	}

	/**
	 * @param string               $token Bot token.
	 * @param string               $method Method.
	 * @param array<string, mixed> $fields Fields.
	 * @return bool
	 */
	public static function api( $token, $method, array $fields ) {
		return self::request(
			$token,
			$method,
			array(
				'timeout' => 20,
				'body'    => $fields,
			)
		);
	}

	/**
	 * @param string                $token Bot token.
	 * @param string                $method Method.
	 * @param array<string, string> $fields Fields.
	 * @param string                $file_field File field name.
	 * @param string                $filename Filename.
	 * @param string                $binary File bytes.
	 * @param string                $mime Mime.
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

		return self::request(
			$token,
			$method,
			array(
				'timeout' => 25,
				'headers' => array(
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				),
				'body'    => $body,
			)
		);
	}

	/**
	 * @param string               $token Token.
	 * @param string               $method Method.
	 * @param array<string, mixed> $args Request args.
	 * @return bool
	 */
	private static function request( $token, $method, array $args ) {
		self::$last_error = '';
		$token            = (string) $token;
		$method           = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $method );
		if ( ! $token || ! $method ) {
			self::$last_error = 'توکن یا متد خالی است.';
			return false;
		}

		foreach ( self::api_bases() as $base ) {
			$url    = self::build_url( $base, $token, $method );
			$result = self::remote_post( $url, $args );
			if ( ! empty( $result['ok'] ) ) {
				return true;
			}
			if ( ! empty( $result['reject'] ) ) {
				return false;
			}
		}

		if ( '' === self::$last_error ) {
			self::$last_error = 'ارسال به تلگرام ممکن نشد. api.telegram.org فیلتر است؛ آدرس Worker خودتان را در تنظیمات بگذارید.';
		}
		return false;
	}

	/**
	 * @param string               $url URL.
	 * @param array<string, mixed> $args Args.
	 * @return array{ok:bool,reject:bool}
	 */
	private static function remote_post( $url, array $args ) {
		$proxy = self::http_proxy();
		if ( $proxy && function_exists( 'curl_init' ) ) {
			return self::curl_post( $url, $args, $proxy );
		}
		if ( function_exists( 'wp_remote_post' ) ) {
			return self::wp_post( $url, $args );
		}
		if ( function_exists( 'curl_init' ) ) {
			return self::curl_post( $url, $args, $proxy );
		}
		self::$last_error = 'امکان ارسال HTTP از این سرور وجود ندارد.';
		return array(
			'ok'     => false,
			'reject' => false,
		);
	}

	/**
	 * @param string               $url URL.
	 * @param array<string, mixed> $args Args.
	 * @return array{ok:bool,reject:bool}
	 */
	private static function wp_post( $url, array $args ) {
		$res = wp_remote_post( $url, $args );
		if ( is_wp_error( $res ) ) {
			self::$last_error = 'اتصال به ' . self::redact_url( $url ) . ' برقرار نشد (' . $res->get_error_message() . '). روی هاست ایران api.telegram.org معمولاً فیلتر است.';
			return array(
				'ok'     => false,
				'reject' => false,
			);
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		return self::interpret( $code, $json, $url );
	}

	/**
	 * @param string               $url URL.
	 * @param array<string, mixed> $args Args.
	 * @param string               $proxy CURLOPT_PROXY value.
	 * @return array{ok:bool,reject:bool}
	 */
	private static function curl_post( $url, array $args, $proxy ) {
		$ch = curl_init();
		if ( ! $ch ) {
			self::$last_error = 'curl_init ناموفق بود.';
			return array(
				'ok'     => false,
				'reject' => false,
			);
		}
		$headers = array();
		if ( ! empty( $args['headers'] ) && is_array( $args['headers'] ) ) {
			foreach ( $args['headers'] as $name => $value ) {
				$headers[] = $name . ': ' . $value;
			}
		}
		$opts = array(
			CURLOPT_URL            => $url,
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => isset( $args['body'] ) ? $args['body'] : '',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => isset( $args['timeout'] ) ? (int) $args['timeout'] : 20,
			CURLOPT_CONNECTTIMEOUT => 12,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_HTTPHEADER     => $headers,
		);
		if ( $proxy ) {
			$opts[ CURLOPT_PROXY ]     = $proxy;
			$opts[ CURLOPT_PROXYTYPE ] = CURLPROXY_HTTP;
		}
		curl_setopt_array( $ch, $opts );
		$body  = curl_exec( $ch );
		$errno = curl_errno( $ch );
		$code  = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$cerr  = curl_error( $ch );
		curl_close( $ch );

		if ( false === $body || $errno ) {
			self::$last_error = 'اتصال به ' . self::redact_url( $url ) . ' برقرار نشد (' . ( $cerr ? $cerr : 'curl ' . $errno ) . ').';
			return array(
				'ok'     => false,
				'reject' => false,
			);
		}
		$json = json_decode( (string) $body, true );
		return self::interpret( $code, $json, $url );
	}

	/**
	 * @param int                  $code HTTP code.
	 * @param mixed                $json Decoded body.
	 * @param string               $url URL.
	 * @return array{ok:bool,reject:bool}
	 */
	private static function interpret( $code, $json, $url ) {
		if ( is_array( $json ) && ! empty( $json['ok'] ) && $code >= 200 && $code < 300 ) {
			self::$last_error = '';
			return array(
				'ok'     => true,
				'reject' => false,
			);
		}
		if ( is_array( $json ) && array_key_exists( 'ok', $json ) && empty( $json['ok'] ) ) {
			$desc             = isset( $json['description'] ) ? (string) $json['description'] : 'rejected';
			self::$last_error = 'تلگرام رد کرد: ' . $desc;
			return array(
				'ok'     => false,
				'reject' => true,
			);
		}
		self::$last_error = 'پاسخ نامعتبر از ' . self::redact_url( $url ) . ' (HTTP ' . (int) $code . ').';
		return array(
			'ok'     => false,
			'reject' => false,
		);
	}

	/**
	 * @param string $url URL.
	 * @return string
	 */
	public static function redact_url( $url ) {
		return (string) preg_replace( '#/bot[^/]+/#', '/bot***/', (string) $url );
	}
}
