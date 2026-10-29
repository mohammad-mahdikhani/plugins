<?php
/**
 * Admin settings: trust badges (Enamad, etc.).
 */

defined( 'ABSPATH' ) || exit;

final class PM_Settings {

	public const OPTION_TRUST_HTML = 'pm_trust_badges_html';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function register_menu(): void {
		add_menu_page(
			__( 'پاکن منتال', 'pakanmental-home' ),
			__( 'پاکن منتال', 'pakanmental-home' ),
			'manage_options',
			'pm-settings',
			array( __CLASS__, 'render_page' ),
			'dashicons-store',
			58
		);
	}

	public static function register_settings(): void {
		register_setting(
			'pm_settings_group',
			self::OPTION_TRUST_HTML,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( __CLASS__, 'sanitize_trust_html' ),
				'default'           => '',
			)
		);
	}

	/**
	 * Trust scripts (Enamad) need iframes/scripts — only admins may save; output is injected in footer slot.
	 *
	 * @param mixed $value Raw POST value.
	 */
	public static function sanitize_trust_html( $value ): string {
		if ( ! current_user_can( 'manage_options' ) ) {
			return (string) get_option( self::OPTION_TRUST_HTML, '' );
		}
		return is_string( $value ) ? wp_unslash( $value ) : '';
	}

	public static function get_trust_html(): string {
		return (string) get_option( self::OPTION_TRUST_HTML, '' );
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$trust_html = self::get_trust_html();
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'تنظیمات پاکن منتال', 'pakanmental-home' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'pm_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="pm_trust_badges_html"><?php esc_html_e( 'نمادهای اعتماد (اینماد و …)', 'pakanmental-home' ); ?></label>
						</th>
						<td>
							<p class="description">
								<?php esc_html_e( 'کد HTML، اسکریپت یا تصویر اینماد و سایر نمادها را اینجا بچسبانید. در فوتر سایت داخل بخش «نماد اعتماد» نمایش داده می‌شود.', 'pakanmental-home' ); ?>
							</p>
							<textarea
								name="<?php echo esc_attr( self::OPTION_TRUST_HTML ); ?>"
								id="pm_trust_badges_html"
								class="large-text code"
								rows="12"
								style="direction:ltr;text-align:left;font-family:monospace;"
							><?php echo esc_textarea( $trust_html ); ?></textarea>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'ذخیره', 'pakanmental-home' ) ); ?>
			</form>
		</div>
		<?php
	}
}
