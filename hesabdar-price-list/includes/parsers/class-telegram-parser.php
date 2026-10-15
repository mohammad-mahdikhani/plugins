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
	 * One Telegram post: either a product ad (title + قیمت) or a compact list.
	 *
	 * @param string $text  Message text / caption.
	 * @param string $title Sheet title.
	 * @return HPL_Sheet
	 */
	public static function parse_post( $text, $title = 'تلگرام' ) {
		$text = self::html_to_text( $text );
		if ( self::is_product_ad( $text ) ) {
			return self::sheet_from_ad( $text, $title );
		}
		$list = self::parse_text( $text, $title );
		if ( ! empty( $list->rows ) ) {
			return $list;
		}
		return self::sheet_from_ad( $text, $title );
	}

	/**
	 * Merge many channel posts into one sheet.
	 *
	 * @param string[] $texts    Post texts.
	 * @param string   $filename Filename.
	 * @return HPL_Parsed_File
	 */
	public static function from_posts( array $texts, $filename = 'telegram-channel.txt' ) {
		$rows = array();
		foreach ( $texts as $text ) {
			$sheet = self::parse_post( (string) $text );
			foreach ( $sheet->rows as $row ) {
				$rows[] = $row;
			}
		}
		if ( empty( $rows ) ) {
			throw new RuntimeException( 'در پست‌های کانال نام و قیمتی پیدا نشد.' );
		}
		$seen = array();
		$uniq = array();
		foreach ( $rows as $row ) {
			$key = HPL_Normalizer::name_compact( $row[1] ) . '|' . $row[2];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$uniq[]       = $row;
		}
		$sheet            = new HPL_Sheet( 'تلگرام', array( 'کد کالا', 'نام کالا', 'قیمت', 'موجودی' ), $uniq, 1 );
		$parsed           = new HPL_Parsed_File();
		$parsed->filename = $filename;
		$parsed->type     = 'telegram';
		$parsed->sheets   = array( $sheet );
		return $parsed;
	}

	/**
	 * Split pasted channel copy into separate posts (each has its own قیمت).
	 *
	 * @param string $text Pasted text.
	 * @return string[]
	 */
	public static function split_posts( $text ) {
		$text = trim( self::html_to_text( $text ) );
		if ( '' === $text ) {
			return array();
		}
		if ( ! preg_match_all( '/قیمت[^\n]*/u', $text, $matches, PREG_OFFSET_CAPTURE ) ) {
			return array( $text );
		}
		if ( count( $matches[0] ) < 2 ) {
			return array( $text );
		}
		$chunks = array();
		$start  = 0;
		foreach ( $matches[0] as $hit ) {
			$end   = $hit[1] + strlen( $hit[0] );
			$chunk = trim( substr( $text, $start, $end - $start ) );
			$start = $end;
			if ( '' !== $chunk ) {
				$chunks[] = $chunk;
			}
		}
		$tail = trim( substr( $text, $start ) );
		if ( '' !== $tail ) {
			$chunks[] = $tail;
		}
		return $chunks;
	}

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
			$line = self::strip_decor( $line );
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
		$sheet = self::parse_post( $text, 'تلگرام' );
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
			return count( self::parse_post( $text )->rows );
		} catch ( Exception $e ) {
			return 0;
		}
	}

	/**
	 * Channel ads are long posts with one title and one «قیمت».
	 *
	 * @param string $text Text.
	 * @return bool
	 */
	public static function is_product_ad( $text ) {
		$text = (string) $text;
		$len  = function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
		$checks = preg_match_all( '/✅|✔️/u', $text );
		if ( $len >= 180 || $checks >= 2 ) {
			return true;
		}
		if ( preg_match( '/^\s*[^\n]{0,8}قیمت\s*[:：]/mu', $text ) && $len >= 80 ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $text  Post text.
	 * @param string $title Sheet title.
	 * @return HPL_Sheet
	 */
	public static function sheet_from_ad( $text, $title = 'تلگرام' ) {
		$price = self::extract_labeled_price( $text );
		if ( '' === $price ) {
			$picked = self::pick_price( $text );
			$price  = $picked ? $picked['value'] : '';
		}
		$name = self::extract_ad_name( $text );
		$rows = array();
		if ( '' !== $name && '' !== $price ) {
			$rows[] = array( '', $name, $price, '' );
		}
		return new HPL_Sheet( $title, array( 'کد کالا', 'نام کالا', 'قیمت', 'موجودی' ), $rows, 1 );
	}

	/**
	 * @param string $text Text.
	 * @return string
	 */
	public static function extract_labeled_price( $text ) {
		if ( preg_match( '/قیمت(?:\s*(?:فروش|اصلی|واحد))?\s*[:：]?\s*([0-9۰-۹٠-٩][0-9۰-۹٠-٩,\.٬٫،\' ]*)/u', $text, $m ) ) {
			$parsed = HPL_Normalizer::price( $m[1] );
			if ( false !== $parsed && '' !== $parsed ) {
				return $parsed;
			}
		}
		return '';
	}

	/**
	 * First Persian title, plus English product line if present.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function extract_ad_name( $text ) {
		$lines = preg_split( "/\r\n|\n|\r/u", $text );
		$fa    = '';
		$en    = '';
		foreach ( $lines as $line ) {
			$line = self::strip_decor( $line );
			if ( '' === $line || self::is_heading( $line ) || self::is_ad_noise( $line ) ) {
				continue;
			}
			if ( self::extract_labeled_price( $line ) ) {
				continue;
			}
			if ( self::is_latin_title( $line ) ) {
				if ( '' === $en ) {
					$en = $line;
				}
				continue;
			}
			if ( '' === $fa ) {
				$fa = $line;
			}
			if ( $fa && $en ) {
				break;
			}
		}
		$name = trim( $fa . ( $en ? ' ' . $en : '' ) );
		return HPL_Normalizer::to_string( $name );
	}

	/**
	 * @param string $line Line.
	 * @return string
	 */
	public static function strip_decor( $line ) {
		$line = trim( (string) $line );
		$line = preg_replace( '/[\x{1F000}-\x{1FFFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}\x{20E3}]/u', '', $line );
		$line = preg_replace( '/^[\s✅✔✨⭐💎🔥🎀🎁❤♥🌷🌹🌸🌼🌿🍃🔹🔸▪️•·]+/u', '', $line );
		$line = trim( preg_replace( '/\s+/u', ' ', $line ) );
		return $line;
	}

	/**
	 * @param string $line Line.
	 * @return bool
	 */
	public static function is_ad_noise( $line ) {
		$n = HPL_Normalizer::name( $line );
		if ( '' === $n ) {
			return true;
		}
		if ( preg_match( '/^(قیمت|خرید|ارسال|سفارش|دایرکت|واتساپ|تماس|موجود|انگلیس|کد تخفیف)/u', $n ) ) {
			return true;
		}
		if ( preg_match( '/^(اگه |اگر |برای |دارای |قابل |مناسب |ترکیبی |امکان |بافت )/u', $n ) ) {
			return true;
		}
		$len = function_exists( 'mb_strlen' ) ? mb_strlen( $n, 'UTF-8' ) : strlen( $n );
		if ( $len > 90 ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $line Line.
	 * @return bool
	 */
	public static function is_latin_title( $line ) {
		$letters = preg_match_all( '/\p{L}/u', $line );
		$latin   = preg_match_all( '/[A-Za-z]/', $line );
		return $letters > 0 && $latin >= 8 && ( $latin / $letters ) >= 0.7;
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
			'price' => '/(?:قیمت(?:\s*(?:فروش|اصلی|واحد))?|price)\s*[:：]?\s*([0-9۰-۹٠-٩][0-9۰-۹٠-٩,\.٬٫،\' ]*)/iu',
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
		if ( ! preg_match_all( '/[0-9۰-۹٠-٩][0-9۰-۹٠-٩,\.٬٫،\' ]*[0-9۰-۹٠-٩]|[0-9۰-۹٠-٩]+/u', $line, $matches, PREG_OFFSET_CAPTURE ) ) {
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
