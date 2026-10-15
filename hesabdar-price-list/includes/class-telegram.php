<?php
/**
 * Read price lists from a Telegram channel (bot + public preview).
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Telegram {

	const INBOX_OPTION  = 'hpl_telegram_inbox';
	const OFFSET_OPTION = 'hpl_telegram_offset';
	const INBOX_LIMIT   = 40;

	/**
	 * Callable($method, $url, $params) for tests.
	 *
	 * @var callable|null
	 */
	public static $http = null;

	/** @var array<string, mixed> In-memory options when WordPress is absent. */
	public static $store = array();

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'telegram_bot_token'  => '',
			'telegram_channel'    => '',
			'telegram_secret'     => '',
			'telegram_webhook'    => false,
			'telegram_last_error' => '',
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function settings() {
		if ( class_exists( 'HPL_Plugin' ) ) {
			return HPL_Plugin::settings();
		}
		return array_merge( self::defaults(), isset( self::$store['hpl_settings'] ) && is_array( self::$store['hpl_settings'] ) ? self::$store['hpl_settings'] : array() );
	}

	/**
	 * @return string
	 */
	public static function token() {
		return trim( (string) self::settings()['telegram_bot_token'] );
	}

	/**
	 * Normalize @name or t.me link or numeric chat id.
	 *
	 * @param string $raw Raw channel.
	 * @return string
	 */
	public static function normalize_channel( $raw ) {
		$raw = trim( (string) $raw );
		$raw = preg_replace( '/^https?:\/\/(t\.me|telegram\.me)\//i', '', $raw );
		$raw = preg_replace( '/^s\//i', '', $raw );
		$raw = ltrim( $raw, '@' );
		$raw = preg_replace( '/[\/?#].*$/', '', $raw );
		return trim( $raw );
	}

	/**
	 * @return string
	 */
	public static function channel() {
		return self::normalize_channel( self::settings()['telegram_channel'] );
	}

	/**
	 * @return bool
	 */
	public static function configured() {
		return '' !== self::token() || '' !== self::channel();
	}

	/**
	 * Call Bot API.
	 *
	 * @param string               $method Method.
	 * @param array<string, mixed> $params Params.
	 * @return array<string, mixed>
	 */
	public static function api( $method, array $params = array() ) {
		$token = self::token();
		if ( '' === $token ) {
			throw new RuntimeException( 'توکن ربات تلگرام تنظیم نشده است.' );
		}
		$url = 'https://api.telegram.org/bot' . $token . '/' . ltrim( $method, '/' );
		$body = self::http_post( $url, $params );
		$data = json_decode( $body, true );
		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'پاسخ تلگرام نامعتبر است.' );
		}
		if ( empty( $data['ok'] ) ) {
			$desc = isset( $data['description'] ) ? $data['description'] : 'خطای تلگرام';
			throw new RuntimeException( $desc );
		}
		return isset( $data['result'] ) ? $data['result'] : array();
	}

	/**
	 * Pull new bot updates and public channel posts into the inbox.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function fetch() {
		$errors = array();
		if ( '' !== self::token() && empty( self::settings()['telegram_webhook'] ) ) {
			try {
				self::poll_updates();
			} catch ( Exception $e ) {
				$errors[] = $e->getMessage();
			}
		}
		$channel = self::channel();
		if ( $channel && ! preg_match( '/^-?\d+$/', $channel ) ) {
			try {
				self::ingest_public_channel( $channel );
			} catch ( Exception $e ) {
				$errors[] = $e->getMessage();
			}
		}
		if ( $errors ) {
			self::store_error( implode( ' | ', $errors ) );
		} else {
			self::store_error( '' );
		}
		$inbox = self::inbox();
		if ( empty( $inbox ) ) {
			throw new RuntimeException( self::empty_help( $errors ) );
		}
		return $inbox;
	}

	/**
	 * Hourly poll; ignore empty inbox.
	 */
	public static function cron_fetch() {
		try {
			self::fetch();
		} catch ( Exception $e ) {
			self::store_error( $e->getMessage() );
		}
	}

	/**
	 * @param string[] $errors Errors.
	 * @return string
	 */
	private static function empty_help( array $errors ) {
		$msg = 'هنوز لیست قیمتی از تلگرام نرسیده است.';
		if ( $errors ) {
			$msg .= ' ' . implode( ' ', $errors );
		}
		$msg .= ' ربات را ادمین کانال کنید و یک فایل اکسل/PDF یا متن قیمت در کانال بفرستید. می‌توانید همان فایل را هم برای ربات فوروارد کنید.';
		return $msg;
	}

	/**
	 * Poll getUpdates and ingest channel_post / forwarded files.
	 */
	public static function poll_updates() {
		$offset = (int) self::opt_get( self::OFFSET_OPTION, 0 );
		$params = array(
			'timeout'         => 0,
			'allowed_updates' => wp_json_encode( array( 'channel_post', 'message' ) ),
		);
		if ( $offset > 0 ) {
			$params['offset'] = $offset;
		}
		$updates = self::api( 'getUpdates', $params );
		if ( ! is_array( $updates ) ) {
			return;
		}
		$max = $offset;
		foreach ( $updates as $update ) {
			if ( ! empty( $update['update_id'] ) ) {
				$max = max( $max, (int) $update['update_id'] + 1 );
			}
			self::ingest_update( $update );
		}
		if ( $max > $offset ) {
			self::opt_set( self::OFFSET_OPTION, $max );
		}
	}

	/**
	 * @param array<string, mixed> $update Update.
	 * @return array<string, mixed>|null
	 */
	public static function ingest_update( array $update ) {
		$post = null;
		if ( ! empty( $update['channel_post'] ) && is_array( $update['channel_post'] ) ) {
			$post = $update['channel_post'];
		} elseif ( ! empty( $update['message'] ) && is_array( $update['message'] ) ) {
			$post = $update['message'];
		}
		if ( ! $post ) {
			return null;
		}
		if ( ! self::chat_allowed( isset( $post['chat'] ) ? $post['chat'] : array() ) ) {
			return null;
		}
		$item = self::item_from_post( $post );
		if ( ! $item ) {
			return null;
		}
		self::push_inbox( $item );
		return $item;
	}

	/**
	 * @param array<string, mixed> $chat Chat.
	 * @return bool
	 */
	public static function chat_allowed( array $chat ) {
		$want = self::channel();
		if ( '' === $want ) {
			return true;
		}
		$id       = isset( $chat['id'] ) ? (string) $chat['id'] : '';
		$username = isset( $chat['username'] ) ? strtolower( (string) $chat['username'] ) : '';
		$want_l   = strtolower( $want );
		if ( $username && $username === $want_l ) {
			return true;
		}
		if ( $id && ( $id === $want || $id === '-' . ltrim( $want, '-' ) ) ) {
			return true;
		}
		if ( ctype_digit( ltrim( $want, '-' ) ) && $id && abs( (int) $id ) === abs( (int) $want ) ) {
			return true;
		}
		$type = isset( $chat['type'] ) ? $chat['type'] : '';
		// Private forwards to the bot are always allowed so the merchant can send the file to the bot.
		if ( 'private' === $type ) {
			return true;
		}
		return false;
	}

	/**
	 * @param array<string, mixed> $post Message or channel_post.
	 * @return array<string, mixed>|null
	 */
	public static function item_from_post( array $post ) {
		$chat_id = isset( $post['chat']['id'] ) ? $post['chat']['id'] : 0;
		$mid     = isset( $post['message_id'] ) ? (int) $post['message_id'] : 0;
		$date    = isset( $post['date'] ) ? (int) $post['date'] : time();
		$text    = '';
		if ( ! empty( $post['text'] ) ) {
			$text = (string) $post['text'];
		} elseif ( ! empty( $post['caption'] ) ) {
			$text = (string) $post['caption'];
		}
		$doc = null;
		if ( ! empty( $post['document'] ) && is_array( $post['document'] ) ) {
			$doc = $post['document'];
		} elseif ( ! empty( $post['video'] ) && is_array( $post['video'] ) ) {
			$doc = $post['video'];
		}
		$filename = '';
		$file_id  = '';
		$mime     = '';
		$kind     = 'text';
		if ( $doc && ! empty( $doc['file_id'] ) ) {
			$filename = isset( $doc['file_name'] ) ? (string) $doc['file_name'] : 'telegram-file';
			$file_id  = (string) $doc['file_id'];
			$mime     = isset( $doc['mime_type'] ) ? (string) $doc['mime_type'] : '';
			$kind     = 'document';
			$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
			if ( $ext && ! in_array( $ext, array( 'xlsx', 'xls', 'csv', 'txt', 'pdf' ), true ) ) {
				if ( '' === $text ) {
					return null;
				}
				$kind     = 'text';
				$file_id  = '';
				$filename = '';
			}
		}
		$rows_guess = $text ? HPL_Telegram_Parser::guess_rows( $text ) : 0;
		if ( 'text' === $kind && $rows_guess < 1 ) {
			return null;
		}
		$preview = $filename ? $filename : self::preview_text( $text );
		return array(
			'id'         => $chat_id . ':' . $mid,
			'chat_id'    => $chat_id,
			'message_id' => $mid,
			'date'       => $date,
			'kind'       => $kind,
			'filename'   => $filename,
			'mime'       => $mime,
			'file_id'    => $file_id,
			'text'       => $text,
			'preview'    => $preview,
			'rows_guess' => $rows_guess,
			'source'     => 'bot',
		);
	}

	/**
	 * Read recent public posts from t.me/s/{channel}.
	 *
	 * @param string $channel Username.
	 */
	public static function ingest_public_channel( $channel ) {
		$channel = self::normalize_channel( $channel );
		if ( '' === $channel || preg_match( '/^-?\d+$/', $channel ) ) {
			return;
		}
		$html = self::http_get( 'https://t.me/s/' . rawurlencode( $channel ) );
		foreach ( self::parse_public_html( $html, $channel ) as $item ) {
			self::push_inbox( $item );
		}
	}

	/**
	 * @param string $html    HTML.
	 * @param string $channel Channel.
	 * @return array<int, array<string, mixed>>
	 */
	public static function parse_public_html( $html, $channel ) {
		$out = array();
		if ( ! is_string( $html ) || '' === $html ) {
			return $out;
		}
		if ( ! preg_match_all( '/<div class="tgme_widget_message[^"]*"([^>]*)>(.*?)<div class="tgme_widget_message_footer/s', $html, $blocks, PREG_SET_ORDER ) ) {
			if ( ! preg_match_all( '/class="tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>/s', $html, $texts ) ) {
				return $out;
			}
			foreach ( $texts[1] as $i => $chunk ) {
				$text = HPL_Telegram_Parser::html_to_text( $chunk );
				$rows = HPL_Telegram_Parser::guess_rows( $text );
				if ( $rows < 1 ) {
					continue;
				}
				$out[] = self::public_item( $channel, 'p' . $i, $text, $rows );
			}
			return $out;
		}
		foreach ( $blocks as $block ) {
			$attrs = $block[1];
			$body  = $block[2];
			$mid   = 0;
			if ( preg_match( '/data-post="[^"]+\/(\d+)"/', $attrs . $body, $m ) ) {
				$mid = (int) $m[1];
			}
			$text = '';
			if ( preg_match( '/class="tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>/s', $body, $tm ) ) {
				$text = HPL_Telegram_Parser::html_to_text( $tm[1] );
			} elseif ( preg_match( '/class="tgme_widget_message_caption[^"]*"[^>]*>(.*?)<\/div>/s', $body, $tm ) ) {
				$text = HPL_Telegram_Parser::html_to_text( $tm[1] );
			}
			$filename = '';
			if ( preg_match( '/tgme_widget_message_document_title[^>]*>(.*?)<\/div>/s', $body, $dm ) ) {
				$filename = trim( html_entity_decode( strip_tags( $dm[1] ), ENT_QUOTES, 'UTF-8' ) );
			}
			$rows = $text ? HPL_Telegram_Parser::guess_rows( $text ) : 0;
			$ext  = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
			$is_file = $filename && in_array( $ext, array( 'xlsx', 'xls', 'csv', 'txt', 'pdf' ), true );
			if ( ! $is_file && $rows < 1 ) {
				continue;
			}
			$item            = self::public_item( $channel, $mid ? (string) $mid : md5( $text . $filename ), $text, $rows );
			$item['filename'] = $filename;
			$item['kind']    = $is_file ? 'public_file' : 'text';
			$item['preview'] = $filename ? $filename : $item['preview'];
			$out[]           = $item;
		}
		return $out;
	}

	/**
	 * @param string $channel Channel.
	 * @param string $id      Id.
	 * @param string $text    Text.
	 * @param int    $rows    Rows.
	 * @return array<string, mixed>
	 */
	private static function public_item( $channel, $id, $text, $rows ) {
		return array(
			'id'         => 'public:' . $channel . ':' . $id,
			'chat_id'    => $channel,
			'message_id' => (int) $id,
			'date'       => time(),
			'kind'       => 'text',
			'filename'   => '',
			'mime'       => '',
			'file_id'    => '',
			'text'       => $text,
			'preview'    => self::preview_text( $text ),
			'rows_guess' => $rows,
			'source'     => 'public',
		);
	}

	/**
	 * Download/parse a stored inbox item into a parsed file.
	 *
	 * @param string $id Inbox id.
	 * @return HPL_Parsed_File
	 */
	public static function open( $id ) {
		$item = self::find( $id );
		if ( ! $item ) {
			throw new RuntimeException( 'این پیام تلگرام پیدا نشد. دوباره از کانال بخوانید.' );
		}
		if ( ! empty( $item['file_id'] ) ) {
			$path = self::download_file( $item['file_id'], isset( $item['filename'] ) ? $item['filename'] : 'telegram.bin' );
			try {
				$filename = isset( $item['filename'] ) ? $item['filename'] : 'telegram.xlsx';
				return HPL_Parser::parse( $path, $filename );
			} finally {
				if ( is_file( $path ) ) {
					unlink( $path );
				}
			}
		}
		if ( 'public_file' === $item['kind'] ) {
			throw new RuntimeException( 'دانلود فایل کانال عمومی بدون ربات ممکن نیست. ربات را ادمین کانال کنید یا فایل را برای ربات فوروارد کنید.' );
		}
		$text = isset( $item['text'] ) ? $item['text'] : '';
		$name = ! empty( $item['filename'] ) ? $item['filename'] : 'telegram-' . $item['id'] . '.txt';
		return HPL_Telegram_Parser::to_parsed_file( $text, $name );
	}

	/**
	 * @param string $file_id  File id.
	 * @param string $filename Filename.
	 * @return string Local temp path.
	 */
	public static function download_file( $file_id, $filename ) {
		$info = self::api( 'getFile', array( 'file_id' => $file_id ) );
		if ( empty( $info['file_path'] ) ) {
			throw new RuntimeException( 'مسیر فایل تلگرام پیدا نشد.' );
		}
		$token = self::token();
		$url   = 'https://api.telegram.org/file/bot' . $token . '/' . $info['file_path'];
		$bin   = self::http_get( $url );
		if ( '' === $bin ) {
			throw new RuntimeException( 'دانلود فایل تلگرام خالی بود.' );
		}
		if ( strlen( $bin ) > 20 * 1024 * 1024 ) {
			throw new RuntimeException( 'حجم فایل تلگرام بیشتر از ۲۰ مگابایت است.' );
		}
		$dir = class_exists( 'HPL_Storage' ) ? HPL_Storage::dir() : sys_get_temp_dir();
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		$ext = in_array( $ext, array( 'xlsx', 'xls', 'csv', 'txt', 'pdf' ), true ) ? $ext : 'bin';
		$rand = function_exists( 'wp_generate_password' ) ? wp_generate_password( 12, false, false ) : bin2hex( random_bytes( 6 ) );
		$path = trailingslashit( $dir ) . 'tg-' . $rand . '.' . $ext;
		if ( false === file_put_contents( $path, $bin ) ) {
			throw new RuntimeException( 'ذخیره فایل تلگرام ممکن نشد.' );
		}
		return $path;
	}

	/**
	 * Register webhook so new channel posts arrive automatically.
	 *
	 * @return string Webhook URL.
	 */
	public static function register_webhook() {
		$url    = self::webhook_url();
		$secret = self::ensure_secret();
		self::api(
			'setWebhook',
			array(
				'url'             => $url,
				'secret_token'    => $secret,
				'allowed_updates' => wp_json_encode( array( 'channel_post', 'message' ) ),
			)
		);
		if ( class_exists( 'HPL_Plugin' ) ) {
			HPL_Plugin::save_settings( array( 'telegram_webhook' => true, 'telegram_secret' => $secret ) );
		}
		if ( function_exists( 'wp_next_scheduled' ) ) {
			$ts = wp_next_scheduled( 'hpl_telegram_poll' );
			if ( $ts ) {
				wp_unschedule_event( $ts, 'hpl_telegram_poll' );
			}
		}
		return $url;
	}

	/**
	 * @return string
	 */
	public static function webhook_url() {
		$secret = self::ensure_secret();
		if ( function_exists( 'rest_url' ) ) {
			return add_query_arg( 'secret', $secret, rest_url( 'hesabdar-price-list/v1/telegram' ) );
		}
		return '';
	}

	/**
	 * @return string
	 */
	public static function ensure_secret() {
		$settings = self::settings();
		$secret   = isset( $settings['telegram_secret'] ) ? (string) $settings['telegram_secret'] : '';
		if ( '' === $secret ) {
			$secret = function_exists( 'wp_generate_password' ) ? wp_generate_password( 32, false, false ) : bin2hex( random_bytes( 16 ) );
			if ( class_exists( 'HPL_Plugin' ) ) {
				HPL_Plugin::save_settings( array( 'telegram_secret' => $secret ) );
			}
		}
		return $secret;
	}

	/**
	 * @param string $provided Secret from request.
	 * @return bool
	 */
	public static function valid_secret( $provided ) {
		$want = (string) self::settings()['telegram_secret'];
		if ( '' === $want || '' === (string) $provided ) {
			return false;
		}
		return hash_equals( $want, (string) $provided );
	}

	/**
	 * @param string $header_secret Header secret.
	 * @param string $query_secret  Query secret.
	 * @param string $raw_body      Raw JSON body.
	 * @return bool
	 */
	public static function handle_webhook( $header_secret, $query_secret, $raw_body ) {
		$secret = '' !== (string) $header_secret ? $header_secret : $query_secret;
		if ( ! self::valid_secret( $secret ) ) {
			return false;
		}
		$update = json_decode( (string) $raw_body, true );
		if ( is_array( $update ) ) {
			self::ingest_update( $update );
		}
		return true;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function inbox() {
		$items = self::opt_get( self::INBOX_OPTION, array() );
		if ( ! is_array( $items ) ) {
			return array();
		}
		usort(
			$items,
			static function ( $a, $b ) {
				return (int) $b['date'] - (int) $a['date'];
			}
		);
		return array_values( $items );
	}

	/**
	 * Public list for the admin UI (no file ids / full text dump).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function inbox_preview() {
		$out = array();
		foreach ( self::inbox() as $item ) {
			$out[] = array(
				'id'         => $item['id'],
				'date'       => isset( $item['date'] ) ? (int) $item['date'] : 0,
				'kind'       => $item['kind'],
				'filename'   => isset( $item['filename'] ) ? $item['filename'] : '',
				'preview'    => isset( $item['preview'] ) ? $item['preview'] : '',
				'rows_guess' => isset( $item['rows_guess'] ) ? (int) $item['rows_guess'] : 0,
				'source'     => isset( $item['source'] ) ? $item['source'] : '',
			);
		}
		return $out;
	}

	/**
	 * @param string $id Id.
	 * @return array<string, mixed>|null
	 */
	public static function find( $id ) {
		foreach ( self::inbox() as $item ) {
			if ( isset( $item['id'] ) && (string) $item['id'] === (string) $id ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Latest usable price list in the inbox.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function latest() {
		foreach ( self::inbox() as $item ) {
			if ( ! empty( $item['file_id'] ) || (int) $item['rows_guess'] > 0 ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $item Item.
	 */
	public static function push_inbox( array $item ) {
		$items = self::opt_get( self::INBOX_OPTION, array() );
		if ( ! is_array( $items ) ) {
			$items = array();
		}
		$keep = array();
		foreach ( $items as $old ) {
			if ( isset( $old['id'] ) && $old['id'] === $item['id'] ) {
				continue;
			}
			$keep[] = $old;
		}
		array_unshift( $keep, $item );
		$keep = array_slice( $keep, 0, self::INBOX_LIMIT );
		self::opt_set( self::INBOX_OPTION, $keep );
	}

	/**
	 * @param string $message Message.
	 */
	public static function store_error( $message ) {
		if ( class_exists( 'HPL_Plugin' ) ) {
			HPL_Plugin::save_settings( array( 'telegram_last_error' => (string) $message ) );
		}
	}

	/**
	 * @param string $text Text.
	 * @return string
	 */
	public static function preview_text( $text ) {
		$text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, 140, 'UTF-8' );
		}
		return substr( $text, 0, 140 );
	}

	/**
	 * @param string               $url    URL.
	 * @param array<string, mixed> $params Params.
	 * @return string
	 */
	/**
	 * @param string $key     Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	private static function opt_get( $key, $default ) {
		if ( function_exists( 'get_option' ) ) {
			return get_option( $key, $default );
		}
		return array_key_exists( $key, self::$store ) ? self::$store[ $key ] : $default;
	}

	/**
	 * @param string $key   Option key.
	 * @param mixed  $value Value.
	 */
	private static function opt_set( $key, $value ) {
		if ( function_exists( 'update_option' ) ) {
			update_option( $key, $value, false );
			return;
		}
		self::$store[ $key ] = $value;
	}

	private static function http_post( $url, array $params ) {
		if ( self::$http ) {
			return (string) call_user_func( self::$http, 'POST', $url, $params );
		}
		if ( function_exists( 'wp_remote_post' ) ) {
			$res = wp_remote_post(
				$url,
				array(
					'timeout' => 30,
					'body'    => $params,
				)
			);
			if ( is_wp_error( $res ) ) {
				throw new RuntimeException( $res->get_error_message() );
			}
			return (string) wp_remote_retrieve_body( $res );
		}
		return self::curl( $url, $params );
	}

	/**
	 * @param string $url URL.
	 * @return string
	 */
	private static function http_get( $url ) {
		if ( self::$http ) {
			return (string) call_user_func( self::$http, 'GET', $url, array() );
		}
		if ( function_exists( 'wp_remote_get' ) ) {
			$res = wp_remote_get(
				$url,
				array(
					'timeout' => 30,
					'headers' => array( 'User-Agent' => 'HesabdarPriceList/1.2' ),
				)
			);
			if ( is_wp_error( $res ) ) {
				throw new RuntimeException( $res->get_error_message() );
			}
			$code = (int) wp_remote_retrieve_response_code( $res );
			if ( $code >= 400 ) {
				throw new RuntimeException( 'خواندن کانال تلگرام ناموفق بود (HTTP ' . $code . ').' );
			}
			return (string) wp_remote_retrieve_body( $res );
		}
		return self::curl( $url, null );
	}

	/**
	 * @param string                    $url  URL.
	 * @param array<string, mixed>|null $post Post fields or null for GET.
	 * @return string
	 */
	private static function curl( $url, $post ) {
		if ( function_exists( 'curl_init' ) ) {
			$ch = curl_init( $url );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
			curl_setopt( $ch, CURLOPT_USERAGENT, 'HesabdarPriceList/1.2' );
			if ( null !== $post ) {
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
			}
			$body = curl_exec( $ch );
			$err  = curl_error( $ch );
			curl_close( $ch );
			if ( false === $body ) {
				throw new RuntimeException( $err ? $err : 'ارتباط با تلگرام برقرار نشد.' );
			}
			return (string) $body;
		}
		$ctx = stream_context_create(
			array(
				'http' => array(
					'method'  => null === $post ? 'GET' : 'POST',
					'header'  => "User-Agent: HesabdarPriceList/1.2\r\n",
					'content' => null === $post ? '' : http_build_query( $post ),
					'timeout' => 30,
				),
			)
		);
		$body = file_get_contents( $url, false, $ctx );
		if ( false === $body ) {
			throw new RuntimeException( 'ارتباط با تلگرام برقرار نشد.' );
		}
		return (string) $body;
	}
}
