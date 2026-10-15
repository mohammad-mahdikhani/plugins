<?php
/**
 * Turn Telegram channel text into a price-list sheet.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Telegram_Parser {

	/**
	 * @param string $text  Message text / caption.
	 * @param string $title Sheet title.
	 * @return HPL_Sheet
	 */
	public static function parse_text( $text, $title = 'تلگرام' ) {
		$text  = self::html_to_text( $text );
		$lines = preg_split( "/\r\n|\n|\r/u", $text );
		$rows  = array();
		$pending = '';

		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/\s+/u', ' ', (string) $line ) );
			if ( '' === $line ) {
				continue;
			}
			if ( self::is_heading( $line ) ) {
				continue;
			}

			$parsed = self::parse_line( $line );
			if ( $parsed ) {
				if ( '' === $parsed['name'] && '' !== $pending ) {
					$parsed['name'] = $pending;
				}
				if ( '' === $parsed['name'] && '' === $parsed['sku'] ) {
					$pending = $line;
					continue;
				}
				$rows[]  = array( $parsed['sku'], $parsed['name'], $parsed['price'], $parsed['stock'] );
				$pending = '';
			} else {
				$pending = $line;
			}
		}

		$headers = array( 'کد کالا', 'نام کالا', 'قیمت', 'موجودی' );
		return new HPL_Sheet( $title, $headers, $rows, 1 );
	}

	/**
	 * @param string $text     Text.
	 * @param string $filename Filename.
	 * @return HPL_Parsed_File
	 */
	public static function to_parsed_file( $text, $filename = 'telegram.txt' ) {
		$sheet = self::parse_text( $text, 'تلگرام' );
		if ( empty( $sheet->rows ) ) {
			throw new RuntimeException( 'در پیام تلگرام قیمتی پیدا نشد.' );
		}
		$parsed           = new HPL_Parsed_File();
		$parsed->filename = $filename;
		$parsed->type     = 'telegram';
		$parsed->sheets   = array( $sheet );
		return $parsed;
	}

	/**
	 * Count how many price rows a message likely contains.
	 *
	 * @param string $text Text.
	 * @return int
	 */
	public static function guess_rows( $text ) {
		try {
			return count( self::parse_text( $text )->rows );
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * @param string $html HTML or text.
	 * @return string
	 */
	public static function html_to_text( $html ) {
		$html = (string) $html;
		$html = preg_replace( '/<\s*br\s*\/?\s*>/i', "\n", $html );
		$html = preg_replace( '/<\/(p|div|li|h[1-6]|tr)>/i', "\n", $html );
		$html = preg_replace( '/<\/(td|th)>/i', "\t", $html );
		$text = html_entity_decode( strip_tags( $html ), ENT_QUOTES, 'UTF-8' );
		$text = str_replace( "\xC2\xA0", ' ', $text );
		return trim( $text );
	}

	/**
	 * @param string $line Line.
	 * @return bool
	 */
	public static function is_heading( $line ) {
		$n = HPL_Normalizer::name( $line );
		if ( '' === $n ) {
			return true;
		}
		if ( preg_match( '/^(لیست قیمت|لیست فروش|لیست روزانه|به روز رسانی|اپدیت|update|price list)/u', $n ) ) {
			return true;
		}
		if ( preg_match( '/^(تاریخ|فقط امروز|تماس|واتساپ|اینستاگرام|کانال)$/u', $n ) ) {
			return true;
		}
		if ( preg_match( '/^\d{4}\s+\d{1,2}\s+\d{1,2}$/', $n ) ) {
			return true;
		}
		if ( preg_match( '/^\d{1,2}\s+\S+\s+\d{4}$/u', $n ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $line Line.
	 * @return array{sku:string,name:string,price:string,stock:string}|null
	 */
	public static function parse_line( $line ) {
		$raw = trim( (string) $line );
		if ( '' === $raw ) {
			return null;
		}

		$work = str_replace( array( '٫', '٬' ), array( '.', ',' ), $raw );
		$work = preg_replace( '/[|؛;]+/u', "\t", $work );

		$labeled = self::labeled_fields( $work );
		$price   = $labeled['price'];
		$stock   = $labeled['stock'];
		$sku     = $labeled['sku'];
		$name    = $labeled['name'];
		$work    = $labeled['rest'];

		if ( '' === $price ) {
			$picked = self::pick_price( $work );
			if ( $picked ) {
				$price = $picked['value'];
				$work  = trim( substr( $work, 0, $picked['start'] ) . ' ' . substr( $work, $picked['end'] ) );
			}
		}

		if ( '' === $price ) {
			return null;
		}

		if ( '' === $sku ) {
			$sku_hit = self::pick_sku( $work );
			if ( $sku_hit ) {
				$sku  = $sku_hit['value'];
				$work = trim( substr( $work, 0, $sku_hit['start'] ) . ' ' . substr( $work, $sku_hit['end'] ) );
			}
		}

		if ( '' === $stock ) {
			$stock_hit = self::pick_stock( $work );
			if ( $stock_hit ) {
				$stock = $stock_hit['value'];
				$work  = trim( substr( $work, 0, $stock_hit['start'] ) . ' ' . substr( $work, $stock_hit['end'] ) );
			}
		}

		$work = preg_replace( '/\b(تومان|تومن|ریال| irr|irt|tmn)\b/iu', ' ', $work );
		$work = preg_replace( '/[:：=–—_,،]+/u', ' ', $work );
		$work = trim( preg_replace( '/\s+/u', ' ', $work ) );

		if ( '' === $name ) {
			$name = trim( preg_replace( '/\s+/u', ' ', $work ) );
			$name = preg_replace( '/^(کد|sku|نام|کالا)\s+/iu', '', $name );
		}

		return array(
			'sku'   => HPL_Normalizer::sku( $sku ),
			'name'  => HPL_Normalizer::to_string( $name ),
			'price' => $price,
			'stock' => $stock,
		);
	}

	/**
	 * @param string $line Line.
	 * @return array{sku:string,name:string,price:string,stock:string,rest:string}
	 */
	private static function labeled_fields( $line ) {
		$out = array(
			'sku'   => '',
			'name'  => '',
			'price' => '',
			'stock' => '',
			'rest'  => $line,
		);
		$patterns = array(
			'price' => '/(?:قیمت(?:\s*(?:فروش|اصلی|واحد))?|price)\s*[:：]?\s*([0-9۰-۹٠-٩][0-9۰-۹٠-٩,\.٬٫\' ]*)/iu',
			'stock' => '/(?:موجودی|تعداد|stock|qty)\s*[:：]?\s*([0-9۰-۹٠-٩]+)/iu',
			'sku'   => '/(?:کد(?:\s*کالا)?|sku)\s*[:：]?\s*([A-Za-z0-9۰-۹٠-٩][A-Za-z0-9۰-۹٠-٩\-_]{0,40})/iu',
			'name'  => '/(?:نام(?:\s*کالا)?)\s*[:：]?\s*(.+?)(?=\s+(?:کد|sku|قیمت|موجودی|تعداد|price|stock)|$)/iu',
		);
		foreach ( $patterns as $field => $regex ) {
			if ( preg_match( $regex, $out['rest'], $m ) ) {
				if ( 'price' === $field ) {
					$parsed = HPL_Normalizer::price( $m[1] );
					if ( false !== $parsed ) {
						$out['price'] = $parsed;
					}
				} elseif ( 'stock' === $field ) {
					$parsed         = HPL_Normalizer::stock( $m[1] );
					$out['stock'] = ( false === $parsed ) ? '' : $parsed;
				} elseif ( 'name' === $field ) {
					$out['name'] = HPL_Normalizer::to_string( $m[1] );
				} else {
					$out[ $field ] = HPL_Normalizer::sku( $m[1] );
				}
				$out['rest'] = trim( preg_replace( $regex, ' ', $out['rest'], 1 ) );
			}
		}
		return $out;
	}

	/**
	 * @param string $line Line.
	 * @return array{value:string,start:int,end:int}|null
	 */
	private static function pick_price( $line ) {
		if ( ! preg_match_all( '/[0-9۰-۹٠-٩][0-9۰-۹٠-٩,\.٬٫\' ]*[0-9۰-۹٠-٩]|[0-9۰-۹٠-٩]+/u', $line, $matches, PREG_OFFSET_CAPTURE ) ) {
			return null;
		}
		$best = null;
		$best_score = 0;
		foreach ( $matches[0] as $hit ) {
			$raw    = trim( $hit[0] );
			$parsed = HPL_Normalizer::price( $raw );
			if ( false === $parsed || '' === $parsed ) {
				continue;
			}
			$num   = (float) $parsed;
			$score = 0;
			if ( preg_match( '/[,\.٬٫]/u', $raw ) ) {
				$score += 60;
			}
			if ( $num >= 10000 ) {
				$score += 40;
			} elseif ( $num >= 1000 ) {
				$score += 15;
			} else {
				$score -= 30;
			}
			$after = substr( $line, $hit[1] + strlen( $hit[0] ), 12 );
			if ( preg_match( '/^\s*(تومان|تومن|ریال)/u', $after ) ) {
				$score += 40;
			}
			$before = substr( $line, max( 0, $hit[1] - 8 ), 8 );
			if ( preg_match( '/[:：=]$/u', trim( $before ) ) ) {
				$score += 10;
			}
			if ( $score > $best_score ) {
				$best_score = $score;
				$best       = array(
					'value' => $parsed,
					'start' => $hit[1],
					'end'   => $hit[1] + strlen( $hit[0] ),
				);
			}
		}
		if ( $best && $best_score >= 25 ) {
			return $best;
		}
		$only = trim( $line );
		$parsed = HPL_Normalizer::price( $only );
		if ( false !== $parsed && (float) $parsed >= 1000 ) {
			return array(
				'value' => $parsed,
				'start' => 0,
				'end'   => strlen( $line ),
			);
		}
		return null;
	}

	/**
	 * @param string $line Line.
	 * @return array{value:string,start:int,end:int}|null
	 */
	private static function pick_stock( $line ) {
		if ( ! preg_match( '/(?:موجودی|تعداد|stock|qty)\s*[:：]?\s*([0-9۰-۹٠-٩]+)/iu', $line, $m, PREG_OFFSET_CAPTURE ) ) {
			return null;
		}
		$parsed = HPL_Normalizer::stock( $m[1][0] );
		if ( false === $parsed ) {
			return null;
		}
		return array(
			'value' => $parsed,
			'start' => $m[1][1],
			'end'   => $m[1][1] + strlen( $m[1][0] ),
		);
	}

	/**
	 * @param string $line Line.
	 * @return array{value:string,start:int,end:int}|null
	 */
	private static function pick_sku( $line ) {
		if ( preg_match( '/\b([A-Za-z]{1,8}[-_]?[0-9۰-۹٠-٩]{2,20})\b/u', $line, $m, PREG_OFFSET_CAPTURE ) ) {
			return array(
				'value' => HPL_Normalizer::sku( $m[1][0] ),
				'start' => $m[1][1],
				'end'   => $m[1][1] + strlen( $m[1][0] ),
			);
		}
		return null;
	}
}
