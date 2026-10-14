<?php
/**
 * Detect file type and parse into sheets.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Parser {

	/**
	 * @param string $path     Absolute path.
	 * @param string $filename Original filename.
	 * @return HPL_Parsed_File
	 */
	public static function parse( $path, $filename = '' ) {
		if ( '' === $filename ) {
			$filename = basename( $path );
		}
		$type = self::detect_type( $path, $filename );
		if ( '' === $type ) {
			throw new InvalidArgumentException( 'فرمت فایل پشتیبانی نمی‌شود. فایل اکسل (xlsx/xls/csv) یا PDF آپلود کنید.' );
		}

		try {
			switch ( $type ) {
				case 'csv':
					$parsed = HPL_Csv_Parser::parse( $path );
					break;
				case 'xlsx':
					$parsed = HPL_Xlsx_Parser::parse( $path );
					break;
				case 'xls':
					$parsed = HPL_Xls_Parser::parse( $path );
					break;
				case 'pdf':
					$parsed = HPL_Pdf_Parser::parse( $path );
					break;
				default:
					throw new InvalidArgumentException( 'فرمت فایل پشتیبانی نمی‌شود.' );
			}
		} catch ( Exception $e ) {
			if ( 'xlsx' === $type || 'xls' === $type ) {
				try {
					$parsed = HPL_Csv_Parser::parse( $path );
					$type   = 'csv';
				} catch ( Exception $ignored ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					throw $e;
				}
			} else {
				throw $e;
			}
		}

		$parsed->filename = $filename;
		$parsed->type     = $type;

		if ( empty( $parsed->sheets ) ) {
			throw new RuntimeException( 'هیچ جدولی در فایل پیدا نشد.' );
		}

		return $parsed;
	}

	/**
	 * @param string $path     Path.
	 * @param string $filename Filename.
	 * @return string
	 */
	public static function detect_type( $path, $filename ) {
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		$map = array(
			'csv'  => 'csv',
			'txt'  => 'csv',
			'xlsx' => 'xlsx',
			'xls'  => 'xls',
			'pdf'  => 'pdf',
		);
		if ( isset( $map[ $ext ] ) ) {
			return $map[ $ext ];
		}

		if ( ! is_readable( $path ) ) {
			return '';
		}
		$fh   = fopen( $path, 'rb' );
		$head = $fh ? fread( $fh, 8 ) : '';
		if ( $fh ) {
			fclose( $fh );
		}
		if ( 0 === strpos( $head, '%PDF' ) ) {
			return 'pdf';
		}
		if ( "PK" === substr( $head, 0, 2 ) ) {
			return 'xlsx';
		}
		return '';
	}

	/**
	 * Promote the best header row from raw matrix of cells.
	 *
	 * @param array<int, array<int, string>> $matrix Raw rows.
	 * @param string                         $name   Sheet name.
	 * @return HPL_Sheet
	 */
	public static function sheet_from_matrix( array $matrix, $name = 'Sheet1' ) {
		$best_index  = 0;
		$best_score  = -1;
		$scan_limit  = min( 25, count( $matrix ) );

		for ( $i = 0; $i < $scan_limit; $i++ ) {
			$row   = isset( $matrix[ $i ] ) ? $matrix[ $i ] : array();
			$score = self::header_score( $row );
			if ( $score > $best_score ) {
				$best_score = $score;
				$best_index = $i;
			}
		}

		if ( $best_score < 1 ) {
			// No obvious header: synthesize Col A, Col B, ...
			$width   = 0;
			foreach ( $matrix as $row ) {
				$width = max( $width, count( $row ) );
			}
			$headers = array();
			for ( $c = 0; $c < $width; $c++ ) {
				$headers[] = 'ستون ' . ( $c + 1 );
			}
			return new HPL_Sheet( $name, $headers, self::normalize_width( $matrix, $width ), 0 );
		}

		$headers = self::stringify_row( $matrix[ $best_index ] );
		$width   = count( $headers );
		$data    = array();
		for ( $i = $best_index + 1; $i < count( $matrix ); $i++ ) {
			$row = self::stringify_row( $matrix[ $i ] );
			if ( self::row_is_blank( $row ) ) {
				continue;
			}
			$data[] = $row;
		}

		return new HPL_Sheet( $name, $headers, self::normalize_width( $data, $width ), $best_index + 1 );
	}

	/**
	 * @param array<int, mixed> $row Row.
	 * @return int
	 */
	public static function header_score( array $row ) {
		$score   = 0;
		$aliases = HPL_Column_Mapper::aliases();
		$flat    = array();
		foreach ( $aliases as $list ) {
			foreach ( $list as $alias ) {
				$flat[] = $alias;
			}
		}
		foreach ( $row as $cell ) {
			$key = HPL_Normalizer::header_key( $cell );
			if ( '' === $key ) {
				continue;
			}
			foreach ( $flat as $alias ) {
				if ( $key === $alias || false !== strpos( $key, $alias ) ) {
					$score += 3;
					break;
				}
			}
		}
		return $score;
	}

	/**
	 * @param array<int, mixed> $row Row.
	 * @return string[]
	 */
	public static function stringify_row( array $row ) {
		$out = array();
		foreach ( $row as $cell ) {
			$out[] = HPL_Normalizer::to_string( $cell );
		}
		return $out;
	}

	/**
	 * @param array<int, array<int, string>> $rows  Rows.
	 * @param int                            $width Width.
	 * @return array<int, array<int, string>>
	 */
	public static function normalize_width( array $rows, $width ) {
		$out = array();
		foreach ( $rows as $row ) {
			$padded = array();
			for ( $i = 0; $i < $width; $i++ ) {
				$padded[] = isset( $row[ $i ] ) ? HPL_Normalizer::to_string( $row[ $i ] ) : '';
			}
			$out[] = $padded;
		}
		return $out;
	}

	/**
	 * @param string[] $row Row.
	 * @return bool
	 */
	public static function row_is_blank( array $row ) {
		foreach ( $row as $cell ) {
			if ( '' !== trim( (string) $cell ) ) {
				return false;
			}
		}
		return true;
	}
}
