<?php
/**
 * Generate and serve WebP variants for uploads and front-end images.
 */

defined( 'ABSPATH' ) || exit;

final class PM_WebP {

	public static function init(): void {
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'generate_webp_on_upload' ), 20, 2 );
		add_filter( 'wp_content_img_tag', array( __CLASS__, 'content_img_to_picture' ), 20, 3 );
		add_filter( 'the_content', array( __CLASS__, 'maybe_picture_in_content' ), 25 );
	}

	/**
	 * Create .webp sibling for each size in attachment metadata.
	 */
	public static function generate_webp_on_upload( array $metadata, int $attachment_id ): array {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return $metadata;
		}

		self::convert_file_to_webp( $file );

		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			$dir = trailingslashit( dirname( $file ) );
			foreach ( $metadata['sizes'] as $size ) {
				if ( empty( $size['file'] ) ) {
					continue;
				}
				$size_path = $dir . $size['file'];
				if ( file_exists( $size_path ) ) {
					self::convert_file_to_webp( $size_path );
				}
			}
		}

		return $metadata;
	}

	public static function convert_file_to_webp( string $path ): ?string {
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png', 'gif' ), true ) ) {
			return null;
		}

		$webp = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $path );
		if ( file_exists( $webp ) && filemtime( $webp ) >= filemtime( $path ) ) {
			return $webp;
		}

		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			return null;
		}

		$editor = wp_get_image_editor( $path );
		if ( is_wp_error( $editor ) ) {
			return null;
		}

		$editor->set_quality( 82 );
		$saved = $editor->save( $webp, 'image/webp' );
		if ( is_wp_error( $saved ) ) {
			return null;
		}

		return $webp;
	}

	public static function webp_url_for_src( string $src ): ?string {
		$webp = preg_replace( '/\.(jpe?g|png|gif)(\?.*)?$/i', '.webp', $src );
		if ( $webp === $src ) {
			return null;
		}

		$upload = wp_upload_dir();
		if ( empty( $upload['baseurl'] ) || strpos( $src, $upload['baseurl'] ) === false ) {
			return null;
		}

		$relative = str_replace( $upload['baseurl'], '', strtok( $src, '?' ) );
		$path     = $upload['basedir'] . $relative;
		$webp_path = preg_replace( '/\.(jpe?g|png|gif)$/i', '.webp', $path );

		if ( file_exists( $webp_path ) ) {
			return preg_replace( '/\.(jpe?g|png|gif)(\?.*)?$/i', '.webp$2', $src );
		}

		return null;
	}

	/**
	 * WP 6.3+ img tag filter.
	 */
	public static function content_img_to_picture( string $image, string $context, int $attachment_id ): string {
		if ( 'the_content' !== $context && 'post-thumbnail' !== $context ) {
			return $image;
		}
		return self::wrap_img_tag( $image );
	}

	public static function maybe_picture_in_content( string $content ): string {
		if ( false === strpos( $content, '<img' ) ) {
			return $content;
		}
		return preg_replace_callback(
			'/<img\b[^>]+>/i',
			static function ( array $m ) {
				return self::wrap_img_tag( $m[0] );
			},
			$content
		);
	}

	private static function wrap_img_tag( string $img_tag ): string {
		if ( false !== strpos( $img_tag, '<picture' ) ) {
			return $img_tag;
		}

		if ( ! preg_match( '/\bsrc=(["\'])([^"\']+)\1/i', $img_tag, $m ) ) {
			return $img_tag;
		}

		$webp = self::webp_url_for_src( $m[2] );
		if ( ! $webp ) {
			return $img_tag;
		}

		return '<picture><source type="image/webp" srcset="' . esc_url( $webp ) . '">' . $img_tag . '</picture>';
	}
}
