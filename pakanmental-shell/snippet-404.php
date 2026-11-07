<?php

add_filter('pre_get_document_title', static function ($title) {
    if (function_exists('is_404') && is_404()) {
        return 'صفحه‌ای که دنبالش بودید اینجا نیست – پاکان منتال';
    }
    return $title;
}, 20);
add_filter('document_title_parts', static function ($parts) {
    if (function_exists('is_404') && is_404()) {
        $parts['title'] = 'صفحه‌ای که دنبالش بودید اینجا نیست';
    }
    return $parts;
}, 20);

add_action('template_redirect', static function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (function_exists('wp_is_json_request') && wp_is_json_request()) {
        return;
    }
    if (!is_404()) {
        return;
    }
    $page = get_page_by_path('404-pm');
    if (!$page instanceof WP_Post || 'publish' !== $page->post_status) {
        return;
    }
    $page_id = (int) $page->ID;
    status_header(404);
    nocache_headers();
    $html = '';
    if (class_exists('\Elementor\Plugin')) {
        $html = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display($page_id, true);
    }
    if (!is_string($html) || '' === trim($html)) {
        $html = apply_filters('the_content', $page->post_content);
    }
    ?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, follow">
<?php wp_head(); ?>
<style>html body.error404 .site-header,html body.error404 header#site-header,html body.error404 .site-footer,html body.error404 .page-header{display:none!important}</style>
</head>
<body <?php body_class(array('error404','pm-error-404')); ?>>
<?php
    wp_body_open();
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    wp_footer();
?>
</body>
</html>
<?php
    exit;
}, 0);
