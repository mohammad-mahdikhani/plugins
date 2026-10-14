<?php
/**
 * Best-effort PDF table extractor for supplier price lists.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Pdf_Parser {

	/**
	 * @param string $path Path.
	 * @return HPL_Parsed_File
	 */
	public static function parse( $path ) {
		$text = self::extract_text( $path );
		$text = trim( $text );
		if ( '' === $text ) {
			throw new RuntimeException( 'متن قابل استخراج از PDF پیدا نشد. لطفاً فایل اکسل همان لیست را آپلود کنید (PDFهای اسکن‌شده پشتیبانی نمی‌شوند).' );
		}

		$matrix = self::text_to_matrix( $text );
		if ( empty( $matrix ) ) {
			throw new RuntimeException( 'جدول قیمت در PDF تشخیص داده نشد. فایل اکسل را آپلود کنید.' );
		}

		$parsed          = new HPL_Parsed_File();
		$parsed->sheets  = array( HPL_Parser::sheet_from_matrix( $matrix, 'PDF' ) );
		$parsed->warning = 'استخراج از PDF تقریبی است. قبل از اعمال، ستون‌ها و پیش‌نمایش را بررسی کنید.';
		return $parsed;
	}

	/**
	 * @param string $path Path.
	 * @return string
	 */
	public static function extract_text( $path ) {
		$from_cli = self::pdftotext( $path );
		if ( '' !== $from_cli ) {
			return $from_cli;
		}
		$raw = file_get_contents( $path );
		if ( false === $raw ) {
			throw new RuntimeException( 'خواندن فایل PDF ممکن نشد.' );
		}
		return self::extract_from_binary( $raw );
	}

	/**
	 * @param string $path Path.
	 * @return string
	 */
	private static function pdftotext( $path ) {
		$bin = self::find_pdftotext();
		if ( ! $bin ) {
			return '';
		}
		$cmd = escapeshellcmd( $bin ) . ' -layout -enc UTF-8 ' . escapeshellarg( $path ) . ' - 2>/dev/null';
		$out = shell_exec( $cmd ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec
		return is_string( $out ) ? $out : '';
	}

	/**
	 * @return string|null
	 */
	private static function find_pdftotext() {
		foreach ( array( '/usr/bin/pdftotext', '/usr/local/bin/pdftotext' ) as $candidate ) {
			if ( is_executable( $candidate ) ) {
				return $candidate;
			}
		}
		return null;
	}

	/**
	 * Extract Tj / TJ strings from PDF content streams.
	 *
	 * @param string $raw PDF bytes.
	 * @return string
	 */
	public static function extract_from_binary( $raw ) {
		$chunks = array();
		if ( preg_match_all( '/stream\s*\r?\n(.*?)endstream/s', $raw, $matches ) ) {
			foreach ( $matches[1] as $stream ) {
				$decoded  = self::decode_stream( $stream );
				$chunks[] = self::strings_from_content( $decoded );
			}
		}
		$text = trim( implode( "\n", array_filter( $chunks ) ) );
		if ( '' !== $text ) {
			return $text;
		}

		// Last resort: literal parentheses strings in the whole file.
		return self::strings_from_content( $raw );
	}

	/**
	 * @param string $stream Stream bytes (may include filter newline).
	 * @return string
	 */
	public static function decode_stream( $stream ) {
		$trimmed = ltrim( $stream, "\r\n" );
		$try     = @gzuncompress( $trimmed ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== $try ) {
			return $try;
		}
		$try = @gzinflate( $trimmed ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( false !== $try ) {
			return $try;
		}
		if ( "\x78" === substr( $trimmed, 0, 1 ) ) {
			$try = @gzinflate( substr( $trimmed, 2 ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $try ) {
				return $try;
			}
		}
		return $trimmed;
	}

	/**
	 * @param string $content Content stream.
	 * @return string
	 */
	public static function strings_from_content( $content ) {
		$lines = array();

		if ( preg_match_all( '/BT(.*?)ET/s', $content, $blocks ) ) {
			foreach ( $blocks[1] as $block ) {
				$parts = self::pull_strings( $block );
				if ( ! empty( $parts ) ) {
					$lines[] = implode( "\n", $parts );
				}
			}
		}

		if ( empty( $lines ) ) {
			$parts = self::pull_strings( $content );
			if ( ! empty( $parts ) ) {
				$lines[] = implode( "\n", $parts );
			}
		}

		$text = implode( "\n", $lines );
		$text = str_replace( array( '\\n', '\\r', '\\t' ), array( "\n", "\n", "\t" ), $text );
		$text = preg_replace( '/\\\\([()\\\\])/', '$1', $text );
		return $text;
	}

	/**
	 * @param string $block Content.
	 * @return string[]
	 */
	private static function pull_strings( $block ) {
		$parts = array();
		if ( preg_match_all( '/\((?:\\\\.|[^\\\\)])*\)/', $block, $m ) ) {
			foreach ( $m[0] as $lit ) {
				$inner   = substr( $lit, 1, -1 );
				$inner   = str_replace( array( '\\n', '\\r', '\\t', '\\(', '\\)', '\\\\' ), array( "\n", "\r", "\t", '(', ')', '\\' ), $inner );
				$parts[] = $inner;
			}
		}
		if ( preg_match_all( '/<([0-9A-Fa-f \r\n]+)>/', $block, $hex ) ) {
			foreach ( $hex[1] as $h ) {
				$h        = preg_replace( '/\s+/', '', $h );
				$decoded  = @hex2bin( $h ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				if ( false !== $decoded && preg_match( '/[\\x20-\\x7E\xC0-\xFF]/', $decoded ) ) {
					$parts[] = $decoded;
				}
			}
		}
		return $parts;
	}

	/**
	 * Convert extracted text into a cell matrix.
	 *
	 * @param string $text Text.
	 * @return array<int, array<int, string>>
	 */
	public static function text_to_matrix( $text ) {
		$text  = str_replace( "\t", '  ', $text );
		$raw   = preg_split( "/\r\n|\n|\r/", $text );
		$lines = array();
		foreach ( $raw as $line ) {
			$line = trim( preg_replace( '/[ ]{2,}/', '  ', $line ) );
			if ( '' !== $line ) {
				$lines[] = $line;
			}
		}

		$matrix = array();
		foreach ( $lines as $line ) {
			if ( preg_match( '/\s{2,}/', $line ) ) {
				$cells = preg_split( '/\s{2,}/', $line );
			} else {
				$cells = preg_split( '/\s+/', $line );
			}
			$cells = array_values( array_filter( $cells, static function ( $c ) {
				return '' !== trim( $c );
			} ) );
			if ( count( $cells ) >= 2 ) {
				$matrix[] = $cells;
			}
		}

		return $matrix;
	}
}
