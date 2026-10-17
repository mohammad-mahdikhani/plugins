<?php
/**
 * Build a downloadable report ZIP.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NPBR_TEST' ) ) {
	exit;
}

class NPBR_Zip {

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @param string               $path Output zip path.
	 * @return bool
	 */
	public static function write( array $data, $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return false;
		}
		$zip = new ZipArchive();
		if ( true !== $zip->open( $path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return false;
		}

		$json = NPBR_Report::public_json( $data );
		$zip->addFromString(
			'report.json',
			json_encode( $json, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
		);
		$zip->addFromString( 'report.html', NPBR_Report::html( $data ) );

		if ( ! empty( $data['screenshot'] ) && preg_match( '#^data:image/(png|jpeg|webp);base64,(.+)$#', (string) $data['screenshot'], $m ) ) {
			$bin = base64_decode( $m[2], true );
			if ( false !== $bin ) {
				$ext = 'jpeg' === $m[1] ? 'jpg' : $m[1];
				$zip->addFromString( 'screenshot.' . $ext, $bin );
			}
		}

		$zip->close();
		return file_exists( $path ) && filesize( $path ) > 64;
	}
}
