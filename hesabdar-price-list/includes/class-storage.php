<?php
/**
 * Temporary JSON storage for an import session.
 *
 * @package HesabdarPriceList
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'HPL_TEST' ) ) {
	exit;
}

class HPL_Storage {

	/**
	 * @return string
	 */
	public static function dir() {
		if ( function_exists( 'wp_upload_dir' ) ) {
			$upload = wp_upload_dir();
			$dir    = trailingslashit( $upload['basedir'] ) . 'hesabdar-price-list';
		} else {
			$dir = sys_get_temp_dir() . '/hesabdar-price-list';
		}
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
			self::protect( $dir );
		}
		return $dir;
	}

	/**
	 * @param string $dir Directory.
	 */
	private static function protect( $dir ) {
		$ht = $dir . '/.htaccess';
		if ( ! file_exists( $ht ) ) {
			file_put_contents( $ht, "Deny from all\n" );
		}
		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// silence\n" );
		}
	}

	/**
	 * @param array<string, mixed> $payload Payload.
	 * @return string token
	 */
	public static function put( array $payload ) {
		$token = self::token();
		$path  = self::path( $token );
		$json  = wp_json_encode( $payload );
		if ( false === $json ) {
			throw new RuntimeException( 'ذخیره پیش‌نمایش ممکن نشد.' );
		}
		if ( false === file_put_contents( $path, $json ) ) {
			throw new RuntimeException( 'نوشتن فایل موقت ممکن نشد.' );
		}
		return $token;
	}

	/**
	 * @param string $token Token.
	 * @return array<string, mixed>
	 */
	public static function get( $token ) {
		if ( ! self::valid_token( $token ) ) {
			throw new InvalidArgumentException( 'نشست نامعتبر است. دوباره آپلود کنید.' );
		}
		$path = self::path( $token );
		if ( ! is_readable( $path ) ) {
			throw new RuntimeException( 'نشست منقضی شده است. دوباره آپلود کنید.' );
		}
		$json = file_get_contents( $path );
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			throw new RuntimeException( 'داده نشست خراب است.' );
		}
		return $data;
	}

	/**
	 * @param string               $token   Token.
	 * @param array<string, mixed> $payload Payload.
	 */
	public static function update( $token, array $payload ) {
		if ( ! self::valid_token( $token ) ) {
			throw new InvalidArgumentException( 'نشست نامعتبر است.' );
		}
		file_put_contents( self::path( $token ), wp_json_encode( $payload ) );
	}

	/**
	 * @param string $token Token.
	 */
	public static function delete( $token ) {
		if ( ! self::valid_token( $token ) ) {
			return;
		}
		$path = self::path( $token );
		if ( file_exists( $path ) ) {
			unlink( $path );
		}
	}

	/**
	 * @param string $token Token.
	 * @return string
	 */
	public static function path( $token ) {
		return trailingslashit( self::dir() ) . $token . '.json';
	}

	/**
	 * @return string
	 */
	public static function token() {
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( 20, false, false );
		}
		return bin2hex( random_bytes( 10 ) );
	}

	/**
	 * @param string $token Token.
	 * @return bool
	 */
	public static function valid_token( $token ) {
		return is_string( $token ) && (bool) preg_match( '/^[A-Za-z0-9]{12,40}$/', $token );
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	/**
	 * @param string $dir Dir.
	 * @return bool
	 */
	function wp_mkdir_p( $dir ) {
		return is_dir( $dir ) || mkdir( $dir, 0755, true );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data, JSON_UNESCAPED_UNICODE );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	/**
	 * @param string $value Value.
	 * @return string
	 */
	function trailingslashit( $value ) {
		return rtrim( $value, '/\\' ) . '/';
	}
}
