<?php
/**
 * Convert CSS files on disk (theme, Elementor cache, uploads).
 *
 * @package NahalFontDisplaySwap
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NFDS_VERSION' ) ) {
	require_once __DIR__ . '/class-rewriter.php';
}

class NFDS_Files {

	/**
	 * @param string $path File or directory.
	 * @param bool   $backup Write a .nfds-bak once.
	 * @return array{files:int,faces:int,changed:int,errors:string[]}
	 */
	public static function convert_path( $path, $backup = true ) {
		$out = array(
			'files'   => 0,
			'faces'   => 0,
			'changed' => 0,
			'errors'  => array(),
		);
		$path = self::normalize_path( $path );
		if ( '' === $path || ! file_exists( $path ) ) {
			$out['errors'][] = 'path missing';
			return $out;
		}
		$files = is_dir( $path ) ? self::list_css( $path ) : array( $path );
		foreach ( $files as $file ) {
			$result = self::convert_file( $file, $backup );
			$out['files']++;
			$out['faces'] += $result['faces'];
			if ( $result['changed'] ) {
				$out['changed']++;
			}
			if ( $result['error'] ) {
				$out['errors'][] = $file . ': ' . $result['error'];
			}
		}
		return $out;
	}

	/**
	 * @param string $file File path.
	 * @param bool   $backup Backup.
	 * @return array{faces:int,changed:bool,error:string}
	 */
	public static function convert_file( $file, $backup = true ) {
		$empty = array(
			'faces'   => 0,
			'changed' => false,
			'error'   => '',
		);
		$file = self::normalize_path( $file );
		if ( '' === $file || ! is_file( $file ) || ! is_readable( $file ) ) {
			$empty['error'] = 'unreadable';
			return $empty;
		}
		if ( ! preg_match( '/\.(css|scss)$/i', $file ) ) {
			return $empty;
		}
		$css = file_get_contents( $file );
		if ( ! is_string( $css ) ) {
			$empty['error'] = 'read failed';
			return $empty;
		}
		$faces = NFDS_Rewriter::font_face_count( $css );
		$empty['faces'] = $faces;
		if ( $faces < 1 && false === stripos( $css, 'fonts.googleapis.com' ) && false === stripos( $css, 'fonts.bunny.net' ) ) {
			return $empty;
		}
		$next = NFDS_Rewriter::css( $css );
		$next = preg_replace_callback(
			'/@import\s+(?:url\(\s*)?(["\']?)([^"\')]+)(\1)\s*\)?/i',
			array( 'NFDS_Rewriter', 'rewrite_import' ),
			$next
		);
		if ( ! is_string( $next ) || $next === $css ) {
			return $empty;
		}
		if ( $backup ) {
			$bak = $file . '.nfds-bak';
			if ( ! file_exists( $bak ) ) {
				@copy( $file, $bak );
			}
		}
		$ok = @file_put_contents( $file, $next );
		if ( false === $ok ) {
			$empty['error'] = 'write failed';
			return $empty;
		}
		$empty['changed'] = true;
		$empty['faces']   = NFDS_Rewriter::font_face_count( $next );
		return $empty;
	}

	/**
	 * @param string $dir Directory.
	 * @return string[]
	 */
	public static function list_css( $dir ) {
		$dir   = self::normalize_path( $dir );
		$found = array();
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return $found;
		}
		$skip = array( 'node_modules', '.git', 'vendor', 'bower_components' );
		$it   = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			$path = $file->getPathname();
			$ok   = true;
			foreach ( $skip as $part ) {
				if ( false !== strpos( $path, DIRECTORY_SEPARATOR . $part . DIRECTORY_SEPARATOR ) ) {
					$ok = false;
					break;
				}
			}
			if ( ! $ok ) {
				continue;
			}
			if ( preg_match( '/\.(css|scss)$/i', $path ) ) {
				$found[] = $path;
			}
		}
		return $found;
	}

	/**
	 * @param string $path Path.
	 * @return string
	 */
	public static function normalize_path( $path ) {
		$path = str_replace( array( "\0", '\\' ), array( '', '/' ), (string) $path );
		$path = rtrim( $path, '/' );
		if ( function_exists( 'realpath' ) ) {
			$real = realpath( $path );
			if ( $real ) {
				return $real;
			}
		}
		return $path;
	}
}
