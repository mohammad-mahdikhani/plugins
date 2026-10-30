<?php
/**
 * Plugin Name: GTAVI GTA-6 Look
 * Description: هم‌راستاسازی ظاهر gtavistore با سبک gta-6.ir — بلاگ سبک، لندینگ محصول، و صفحه ۴۰۴.
 * Version: 1.0.0
 * Author: MHM
 * Text Domain: gtavi-gta6-look
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'GTAVI_GTA6_LOOK_VERSION', '1.0.0' );
define( 'GTAVI_GTA6_LOOK_FILE', __FILE__ );
define( 'GTAVI_GTA6_LOOK_DIR', plugin_dir_path( __FILE__ ) );
define( 'GTAVI_GTA6_LOOK_URL', plugin_dir_url( __FILE__ ) );

require_once GTAVI_GTA6_LOOK_DIR . 'includes/bootstrap.php';

GTAVI_GTA6_Look_Bootstrap::init();
