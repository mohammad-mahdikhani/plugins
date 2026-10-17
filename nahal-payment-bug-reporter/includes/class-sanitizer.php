<?php
/**
 * Sanitize and redact payment bug reports.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NPBR_TEST' ) ) {
	exit;
}

class NPBR_Sanitizer {

	const MAX_FAILED   = 80;
	const MAX_ERRORS   = 40;
	const MAX_FIELDS   = 40;
	const MAX_NOTE     = 4000;
	const MAX_NAME     = 80;
	const MAX_PHONE    = 32;
	const MAX_URL      = 500;
	const MAX_SHOT     = 1900000;

	/**
	 * @param mixed $value Raw text.
	 * @param int   $max   Max length.
	 * @return string
	 */
	public static function text( $value, $max = 500 ) {
		$text = self::as_string( $value );
		$text = self::redact_secrets( $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		$text = trim( (string) $text );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $max );
		}
		return substr( $text, 0, $max );
	}

	/**
	 * @param mixed $value Raw multiline note.
	 * @return string
	 */
	public static function note( $value ) {
		$text = self::as_string( $value );
		$text = self::redact_secrets( $text );
		$text = preg_replace( "/[ \t]+/u", ' ', $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );
		$text = trim( (string) $text );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, self::MAX_NOTE );
		}
		return substr( $text, 0, self::MAX_NOTE );
	}

	/**
	 * @param mixed $value Phone-like string.
	 * @return string
	 */
	public static function phone( $value ) {
		$digits = preg_replace( '/[^\d+]/', '', self::as_string( $value ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( (string) $digits, 0, self::MAX_PHONE );
		}
		return substr( (string) $digits, 0, self::MAX_PHONE );
	}

	/**
	 * Mask card numbers, CVV-like tokens, and passwords in free text.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function redact_secrets( $text ) {
		$text = (string) $text;
		$text = preg_replace( '/\b(?:\d[ -]?){13,19}\b/', '[CARD]', $text );
		$text = preg_replace( '/\b(cvv|cvc|cvv2)\s*[:=]?\s*\d{3,4}\b/iu', '$1=[REDACTED]', $text );
		$text = preg_replace( '/\b(password|passwd|رمز(?:\s*عبور)?)\s*[:=]\s*\S+/iu', '$1=[REDACTED]', $text );
		return $text;
	}

	/**
	 * @param mixed $url URL.
	 * @return string
	 */
	public static function url( $url ) {
		$url = self::text( $url, self::MAX_URL );
		if ( '' === $url ) {
			return '';
		}
		if ( preg_match( '#^(https?:|//|/)#i', $url ) ) {
			return $url;
		}
		return '';
	}

	/**
	 * @param mixed $list Failed resource rows.
	 * @return array<int, array<string, mixed>>
	 */
	public static function failed_resources( $list ) {
		if ( ! is_array( $list ) ) {
			return array();
		}
		$out = array();
		foreach ( $list as $row ) {
			if ( count( $out ) >= self::MAX_FAILED ) {
				break;
			}
			if ( ! is_array( $row ) ) {
				continue;
			}
			$url  = self::url( isset( $row['url'] ) ? $row['url'] : '' );
			$kind = self::text( isset( $row['kind'] ) ? $row['kind'] : 'resource', 32 );
			if ( '' === $url ) {
				continue;
			}
			$item = array(
				'url'         => $url,
				'kind'        => $kind ? $kind : 'resource',
				'tag'         => self::text( isset( $row['tag'] ) ? $row['tag'] : '', 24 ),
				'id'          => self::text( isset( $row['id'] ) ? $row['id'] : '', 80 ),
				'status'      => self::text( isset( $row['status'] ) ? $row['status'] : '', 40 ),
				'message'     => self::text( isset( $row['message'] ) ? $row['message'] : '', 240 ),
				'payment'     => self::is_payment_asset( $url . ' ' . $kind . ' ' . ( isset( $row['id'] ) ? $row['id'] : '' ) ),
				'time'        => self::text( isset( $row['time'] ) ? $row['time'] : '', 40 ),
			);
			$out[] = $item;
		}
		return $out;
	}

	/**
	 * @param mixed $list JS error rows.
	 * @return array<int, array<string, string>>
	 */
	public static function js_errors( $list ) {
		if ( ! is_array( $list ) ) {
			return array();
		}
		$out = array();
		foreach ( $list as $row ) {
			if ( count( $out ) >= self::MAX_ERRORS ) {
				break;
			}
			if ( is_string( $row ) ) {
				$row = array( 'message' => $row );
			}
			if ( ! is_array( $row ) ) {
				continue;
			}
			$message = self::text( isset( $row['message'] ) ? $row['message'] : '', 400 );
			if ( '' === $message ) {
				continue;
			}
			$out[] = array(
				'message' => $message,
				'source'  => self::url( isset( $row['source'] ) ? $row['source'] : '' ),
				'line'    => self::text( isset( $row['line'] ) ? $row['line'] : '', 12 ),
				'type'    => self::text( isset( $row['type'] ) ? $row['type'] : 'error', 32 ),
			);
		}
		return $out;
	}

	/**
	 * @param mixed $data Payment snapshot.
	 * @return array<string, mixed>
	 */
	public static function payment_snapshot( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$fields = array();
		$raw    = isset( $data['fields'] ) && is_array( $data['fields'] ) ? $data['fields'] : array();
		foreach ( $raw as $field ) {
			if ( count( $fields ) >= self::MAX_FIELDS ) {
				break;
			}
			if ( ! is_array( $field ) ) {
				continue;
			}
			$fields[] = array(
				'name'    => self::text( isset( $field['name'] ) ? $field['name'] : '', 80 ),
				'id'      => self::text( isset( $field['id'] ) ? $field['id'] : '', 80 ),
				'type'    => self::text( isset( $field['type'] ) ? $field['type'] : '', 24 ),
				'tag'     => self::text( isset( $field['tag'] ) ? $field['tag'] : '', 24 ),
				'visible' => ! empty( $field['visible'] ),
				'empty'   => ! empty( $field['empty'] ),
				'broken'  => ! empty( $field['broken'] ),
			);
		}
		$methods = array();
		$raw_m   = isset( $data['methods'] ) && is_array( $data['methods'] ) ? $data['methods'] : array();
		foreach ( $raw_m as $method ) {
			if ( count( $methods ) >= 20 ) {
				break;
			}
			if ( is_string( $method ) ) {
				$method = array( 'id' => $method );
			}
			if ( ! is_array( $method ) ) {
				continue;
			}
			$methods[] = array(
				'id'       => self::text( isset( $method['id'] ) ? $method['id'] : '', 80 ),
				'label'    => self::text( isset( $method['label'] ) ? $method['label'] : '', 120 ),
				'selected' => ! empty( $method['selected'] ),
			);
		}
		return array(
			'foundPaymentBox' => ! empty( $data['foundPaymentBox'] ),
			'emptyBox'        => ! empty( $data['emptyBox'] ),
			'noMethods'       => ! empty( $data['noMethods'] ),
			'checkoutType'    => self::text( isset( $data['checkoutType'] ) ? $data['checkoutType'] : '', 40 ),
			'bodyClasses'     => self::text( isset( $data['bodyClasses'] ) ? $data['bodyClasses'] : '', 400 ),
			'methods'         => $methods,
			'fields'          => $fields,
		);
	}

	/**
	 * @param mixed $data URI or raw base64.
	 * @return string Empty or data URI.
	 */
	public static function screenshot( $data ) {
		$raw = self::as_string( $data );
		if ( '' === $raw ) {
			return '';
		}
		if ( preg_match( '#^data:image/(png|jpeg|jpg|webp);base64,([A-Za-z0-9+/=\s]+)$#i', $raw, $m ) ) {
			$bin = base64_decode( preg_replace( '/\s+/', '', $m[2] ), true );
			if ( false === $bin || strlen( $bin ) < 32 || strlen( $bin ) > self::MAX_SHOT ) {
				return '';
			}
			$ext = strtolower( $m[1] );
			if ( 'jpg' === $ext ) {
				$ext = 'jpeg';
			}
			return 'data:image/' . $ext . ';base64,' . base64_encode( $bin );
		}
		return '';
	}

	/**
	 * @param string $haystack URL or id.
	 * @return bool
	 */
	public static function is_payment_asset( $haystack ) {
		$haystack = strtolower( (string) $haystack );
		$needles  = array(
			'payment',
			'checkout',
			'gateway',
			'digipay',
			'zarinpal',
			'zibal',
			'idpay',
			'nextpay',
			'payping',
			'saman',
			'mellat',
			'parsian',
			'sadad',
			'woocommerce/assets/js/frontend/checkout',
			'wc-checkout',
			'order-pay',
		);
		foreach ( $needles as $needle ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $payload Incoming report.
	 * @return array{ok:bool,error:string,data:array<string,mixed>}
	 */
	public static function normalize_payload( array $payload ) {
		$note = self::note( isset( $payload['note'] ) ? $payload['note'] : '' );
		if ( '' === $note ) {
			return array(
				'ok'    => false,
				'error' => 'شرح خطا را بنویسید تا مدیر بتواند همین مورد را پیگیری کند.',
				'data'  => array(),
			);
		}

		$page_url = self::url( isset( $payload['pageUrl'] ) ? $payload['pageUrl'] : '' );
		$failed   = self::failed_resources( isset( $payload['failed'] ) ? $payload['failed'] : array() );
		$errors   = self::js_errors( isset( $payload['errors'] ) ? $payload['errors'] : array() );
		$payment  = self::payment_snapshot( isset( $payload['payment'] ) ? $payload['payment'] : array() );
		$shot     = self::screenshot( isset( $payload['screenshot'] ) ? $payload['screenshot'] : '' );

		$data = array(
			'note'        => $note,
			'name'        => self::text( isset( $payload['name'] ) ? $payload['name'] : '', self::MAX_NAME ),
			'phone'       => self::phone( isset( $payload['phone'] ) ? $payload['phone'] : '' ),
			'pageUrl'     => $page_url,
			'pageTitle'   => self::text( isset( $payload['pageTitle'] ) ? $payload['pageTitle'] : '', 180 ),
			'userAgent'   => self::text( isset( $payload['userAgent'] ) ? $payload['userAgent'] : '', 240 ),
			'viewport'    => self::text( isset( $payload['viewport'] ) ? $payload['viewport'] : '', 40 ),
			'online'      => ! empty( $payload['online'] ),
			'failed'      => $failed,
			'errors'      => $errors,
			'payment'     => $payment,
			'screenshot'  => $shot,
			'capturedAt'  => self::text( isset( $payload['capturedAt'] ) ? $payload['capturedAt'] : '', 40 ),
		);

		return array(
			'ok'    => true,
			'error' => '',
			'data'  => $data,
		);
	}

	/**
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function as_string( $value ) {
		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}
		if ( is_string( $value ) ) {
			return $value;
		}
		return '';
	}
}
