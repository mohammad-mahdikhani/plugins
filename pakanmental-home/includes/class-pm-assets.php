<?php
/**
 * Enqueue styles/scripts only on the home template.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Assets {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_head', array( __CLASS__, 'font_preconnect' ), 1 );
	}

	public static function is_pm_home_page(): bool {
		return is_page() && 'templates/pm-home.php' === get_page_template_slug( get_queried_object_id() );
	}

	public static function font_preconnect(): void {
		if ( is_admin() ) {
			return;
		}
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}

	public static function enqueue(): void {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style(
			'pm-brand-overrides',
			PM_HOME_URL . 'assets/css/pm-brand-overrides.css',
			array(),
			PM_HOME_VERSION
		);

		if ( ! self::is_pm_home_page() ) {
			return;
		}

		wp_enqueue_style(
			'pm-fonts',
			'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap',
			array(),
			null
		);

		wp_enqueue_style(
			'pm-site',
			PM_HOME_URL . 'assets/css/pm.css',
			array( 'pm-fonts' ),
			PM_HOME_VERSION
		);

		wp_enqueue_script(
			'pm-home',
			PM_HOME_URL . 'assets/js/home.js',
			array(),
			PM_HOME_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}
}
