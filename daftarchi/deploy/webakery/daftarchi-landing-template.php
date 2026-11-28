<?php
/**
 * Full-width page template for Daftarchi landing (copy to child theme root if needed).
 * The snippet in daftarchi-landing.php can also load this file from deploy/webakery/.
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
echo do_shortcode('[daftarchi_landing]');
get_footer();