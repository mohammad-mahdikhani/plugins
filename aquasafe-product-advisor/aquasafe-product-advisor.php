<?php
/**
 * Plugin Name: AquaSafe Product Advisor
 * Description: دستیار انتخاب محصول آکواسیف — شورت‌کد [as_product_advisor]
 * Version: 1.0.0
 * Author: AquaSafe
 * Requires Plugins: woocommerce
 * Text Domain: aquasafe-product-advisor
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'AS_ADVISOR_FILE', __FILE__ );
define( 'AS_ADVISOR_DIR', __DIR__ );
require_once __DIR__ . '/includes/class-engine.php';
require_once __DIR__ . '/includes/class-catalog.php';
require_once __DIR__ . '/includes/class-questions.php';
require_once __DIR__ . '/includes/class-rest.php';
require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-plugin.php';
add_action( 'plugins_loaded', array( 'AS_Advisor_Plugin', 'init' ) );
