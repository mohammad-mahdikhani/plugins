<?php
/**
 * Read a photographed invoice, including handwriting, with Gemini.
 * The shop pays with Pro tokens. The model key stays on Webakery.
 */

add_action('admin_menu', 'daftarchi_invoice_ai_menu');
add_action('rest_api_init', 'daftarchi_invoice_ai_route');

function daftarchi_invoice_ai_menu() {
    add_submenu_page(
        'daftarchi',
        'هوش فاکتور',
        'هوش فاکتور',
        'manage_options',
        'daftarchi-invoice-ai',
        'daftarchi_invoice_ai_page'
    );
}

function daftarchi_invoice_ai_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftarchi_ai_key'])) {
        check_admin_referer('daftarchi_invoice_ai');
        $key = trim((string) wp_unslash($_POST['daftarchi_ai_key']));
        if ($key !== '') {
            update_option('daftarchi_ai_key', $key, false);
        }
        echo '<div class="updated"><p>کلید ذخیره شد. در صفحه نشان داده نمی‌شود.</p></div>';
    }
    $saved = trim((string) get_option('daftarchi_ai_key')) !== '';
    echo '<div class="wrap"><h1>هوش فاکتور</h1>';
    echo '<p>این کلید فقط برای خواندن عکس فاکتور، از جمله دست‌نویس، استفاده می‌شود. توکن مصرفی از اکانت Pro فروشگاه کم می‌شود، نه از VIP.</p>';
    echo '<p>' . ($saved ? 'یک کلید ذخیره شده است.' : 'هنوز کلیدی نیست.') . '</p>';
    echo '<form method="post">';
    wp_nonce_field('daftarchi_invoice_ai');
    echo '<input type="password" name="daftarchi_ai_key" autocomplete="new-password" style="width:24em" placeholder="کلید Gemini">';
    echo '<p><button class="button button-primary">ذخیره کلید</button></p></form></div>';
}

function daftarchi_invoice_ai_route() {
    register_rest_route('daftarchi/v1', '/invoice-read', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_invoice_ai_read',
    ));
}

function daftarchi_invoice_ai_read($request) {
    $key = (string) $request->get_param('key');
    if ($key === '' || !hash_equals('daftarchi-webakery-mail', $key)) {
        return new WP_Error('auth', 'دسترسی نیست', array('status' => 403));
    }
    $secret = trim((string) get_option('daftarchi_ai_key'));
    if ($secret === '') {
        return new WP_Error('ai', 'کلید هوش مصنوعی در پیشخوان دفترچی ذخیره نشده', array('status' => 503));
    }
    $image = (string) $request->get_param('image');
    $mime = (string) $request->get_param('mime');
    if (!in_array($mime, array('image/jpeg', 'image/png', 'image/webp', 'image/gif'), true)) {
        return new WP_Error('image', 'فرمت عکس فاکتور مجاز نیست', array('status' => 400));
    }
    if (strlen($image) < 20 || strlen($image) > 2500000) {
        return new WP_Error('image', 'حجم عکس فاکتور مناسب نیست', array('status' => 400));
    }
    $prompt = (string) $request->get_param('prompt');
    if ($prompt === '') {
        $prompt = 'فاکتور فارسی را بخوان، حتی اگر دست‌نویس باشد، و JSON برگردان.';
    }
    $body = array(
        'contents' => array(array('parts' => array(
            array('text' => $prompt),
            array('inline_data' => array('mime_type' => $mime, 'data' => $image)),
        ))),
        'generationConfig' => array(
            'responseMimeType' => 'application/json',
            'temperature' => 0,
        ),
    );
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . rawurlencode($secret);
    $response = wp_remote_post($url, array(
        'timeout' => 50,
        'headers' => array('Content-Type' => 'application/json'),
        'body' => wp_json_encode($body),
    ));
    if (is_wp_error($response)) {
        return new WP_Error('ai', 'اتصال به هوش مصنوعی برقرار نشد', array('status' => 502));
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    $raw = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($code >= 400 || !is_array($raw)) {
        return new WP_Error('ai', 'هوش مصنوعی فاکتور را نخواند', array('status' => 502));
    }
    $text = '';
    $parts = $raw['candidates'][0]['content']['parts'] ?? array();
    if (is_array($parts)) {
        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text'])) {
                $text .= (string) $part['text'];
            }
        }
    }
    $parsed = json_decode($text, true);
    if (!is_array($parsed)) {
        return new WP_Error('ai', 'پاسخ هوش مصنوعی فاکتور نبود', array('status' => 502));
    }
    $tokens = (int) ($raw['usageMetadata']['totalTokenCount'] ?? 0);
    if ($tokens <= 0) {
        return new WP_Error('ai', 'هوش مصنوعی تعداد توکن را برنگرداند', array('status' => 502));
    }
    $parsed['tokens'] = $tokens;
    return new WP_REST_Response($parsed, 200);
}
