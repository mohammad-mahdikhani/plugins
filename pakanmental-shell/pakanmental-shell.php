<?php
/**
 * Plugin Name: Pakan Mental Product Shell
 * Description: قالب‌های المنتوری قابل‌ویرایش برای هدر، فوتر، تک‌محصول، آرشیو، فروشگاه و بلاگ.
 * Version: 1.2.0
 * Author: Pakan Mental
 * Text Domain: pakanmental-shell
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'PM_SHELL_VERSION', '1.2.0' );
define( 'PM_SHELL_PATH', plugin_dir_path( __FILE__ ) );
define( 'PM_SHELL_URL', plugin_dir_url( __FILE__ ) );
define( 'PM_SHELL_LOGO', 'https://www.pakanmental.com/wp-content/uploads/2026/10/logo-pakanmental.webp' );

$pm_shell_files = array(
	'includes/class-pm-shell-deploy.php',
	'includes/class-pm-shell-templates.php',
	'includes/class-pm-shell-shortcodes.php',
	'includes/class-pm-shell-chrome.php',
	'includes/class-pm-shell-admin.php',
);

foreach ( $pm_shell_files as $pm_shell_rel ) {
	$pm_shell_abs = PM_SHELL_PATH . $pm_shell_rel;
	if ( ! is_readable( $pm_shell_abs ) ) {
		add_action(
			'admin_notices',
			static function () use ( $pm_shell_rel ): void {
				if ( ! current_user_can( 'update_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p>'
					. esc_html__( 'افزونه قالب پاکان ناقص است. فایل را دوباره آپلود کنید:', 'pakanmental-shell' )
					. ' <code>' . esc_html( $pm_shell_rel ) . '</code></p></div>';
			}
		);
		return;
	}
	require_once $pm_shell_abs;
}

PM_Shell_Deploy::init();
PM_Shell_Shortcodes::init();
PM_Shell_Chrome::init();
PM_Shell_Admin::init();

register_activation_hook(
	__FILE__,
	static function (): void {
		delete_transient( 'pm_shell_templates_ready' );
		if ( class_exists( 'PM_Shell_Templates' ) ) {
			PM_Shell_Templates::ensure_library_templates();
		}
	}
);
