<?php
/**
 * Plugin Name: کارت‌های هم‌اندازه نهال
 * Description: ابعاد کارت دوره‌ها و اخبار را یکسان می‌کند و دکمه افزودن به سبد خرید را مثل «مطالعه بیشتر» به کارت محصول اضافه می‌کند.
 * Version:     1.0.0
 * Plugin URI:  https://nahal.website
 * Author:      webakery.ir
 * Author URI:  https://webakery.ir
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: nahal-equal-cards
 * License:     GPLv2 or later
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'NEC_LOADED' ) ) {
	return;
}

define( 'NEC_LOADED', true );
define( 'NEC_VERSION', '1.0.0' );
define( 'NEC_FILE', __FILE__ );
define( 'NEC_PATH', plugin_dir_path( __FILE__ ) );
define( 'NEC_URL', plugin_dir_url( __FILE__ ) );

require_once NEC_PATH . 'includes/class-nec-cards.php';

add_action( 'plugins_loaded', array( 'NEC_Cards', 'instance' ), 20 );
