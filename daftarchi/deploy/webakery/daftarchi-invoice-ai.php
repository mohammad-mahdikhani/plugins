<?php
/**
 * Read a photographed invoice through the GapGPT API on Webakery.
 * Shop clients never receive the GapGPT key. One free invoice per shop
 * account per day is enforced in Daftarchi; extra reads spend Pro tokens.
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
        $model = sanitize_text_field((string) wp_unslash($_POST['daftarchi_ai_model'] ?? ''));
        if ($model === '') {
            $model = 'gpt-4o';
        }
        update_option('daftarchi_ai_model', $model, false);
        echo '<div class="updated"><p>کلید گپ‌جی‌پی‌تی ذخیره شد. در صفحه نشان داده نمی‌شود.</p></div>';
    }
    $saved = trim((string) get_option('daftarchi_ai_key')) !== '';
    $model = trim((string) get_option('daftarchi_ai_model'));
    if ($model === '') {
        $model = 'gpt-4o';
    }
    echo '<div class="wrap"><h1>هوش فاکتور</h1>';
    echo '<p>خواندن عکس فاکتور از API گپ‌جی‌پی‌تی است (<code>https://api.gapgpt.app/v1</code>). کلید فقط اینجاست. فروشگاه کلید نمی‌گیرد. هر حساب دفترچی روزی یک فاکتور رایگان دارد؛ بقیه از توکن Pro کم می‌شود.</p>';
    echo '<p>' . ($saved ? 'یک کلید گپ‌جی‌پی‌تی ذخیره شده است.' : 'هنوز کلید گپ‌جی‌پی‌تی نیست.') . '</p>';
    echo '<form method="post">';
    wp_nonce_field('daftarchi_invoice_ai');
    echo '<p><label>کلید API گپ‌جی‌پی‌تی<br><input type="password" name="daftarchi_ai_key" autocomplete="new-password" style="width:24em" placeholder="sk-..."></label></p>';
    echo '<p><label>مدل<br><input class="regular-text" name="daftarchi_ai_model" value="' . esc_attr($model) . '" placeholder="gpt-4o"></label></p>';
    echo '<p><button class="button button-primary">ذخیره اتصال</button></p></form></div>';
}

function daftarchi_invoice_ai_route() {
    register_rest_route('daftarchi/v1', '/invoice-read', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_invoice_ai_read',
    ));
}

function daftarchi_invoice_ai_json($text) {
    $text = trim((string) $text);
    if (preg_match('/```(?:json)?\s*(.*?)```/s', $text, $match)) {
        $text = trim($match[1]);
    }
    $parsed = json_decode($text, true);
    return is_array($parsed) ? $parsed : null;
}

function daftarchi_invoice_ai_chat($secret, $model, $prompt, $mime, $image) {
    $payload = wp_json_encode(array(
        'model' => $model,
        'temperature' => 0,
        'response_format' => array('type' => 'json_object'),
        'messages' => array(
            array(
                'role' => 'system',
                'content' => 'فقط JSON فاکتور فارسی برگردان. فی هر قلم را دقیق بخوان تا با قیمت خرید یا فروش کالای فروشگاه قابل تطبیق باشد.',
            ),
            array(
                'role' => 'user',
                'content' => array(
                    array('type' => 'text', 'text' => $prompt),
                    array(
                        'type' => 'image_url',
                        'image_url' => array(
                            'url' => 'data:' . $mime . ';base64,' . $image,
                            'detail' => 'high',
                        ),
                    ),
                ),
            ),
        ),
    ));
    $urls = array(
        'https://api.gapgpt.app/v1/chat/completions',
        'https://api.gapapi.com/v1/chat/completions',
    );
    $last = null;
    foreach ($urls as $url) {
        $response = wp_remote_post($url, array(
            'timeout' => 50,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $secret,
            ),
            'body' => $payload,
        ));
        if (is_wp_error($response)) {
            $last = $response;
            continue;
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = json_decode((string) wp_remote_retrieve_body($response), true);
        if ($code < 400 && is_array($raw)) {
            return $raw;
        }
        $last = $raw;
    }
    return $last;
}

function daftarchi_invoice_ai_read($request) {
    $key = (string) $request->get_param('key');
    if ($key === '' || !hash_equals('daftarchi-webakery-mail', $key)) {
        return new WP_Error('auth', 'دسترسی نیست', array('status' => 403));
    }
    $secret = trim((string) get_option('daftarchi_ai_key'));
    if ($secret === '') {
        return new WP_Error('ai', 'کلید گپ‌جی‌پی‌تی در پیشخوان دفترچی ذخیره نشده', array('status' => 503));
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
    $model = trim((string) get_option('daftarchi_ai_model'));
    if ($model === '') {
        $model = 'gpt-4o';
    }
    $raw = daftarchi_invoice_ai_chat($secret, $model, $prompt, $mime, $image);
    if (!is_array($raw)) {
        return new WP_Error('ai', 'اتصال به گپ‌جی‌پی‌تی برقرار نشد', array('status' => 502));
    }
    $text = (string) ($raw['choices'][0]['message']['content'] ?? '');
    $parsed = daftarchi_invoice_ai_json($text);
    if (!is_array($parsed)) {
        return new WP_Error('ai', 'پاسخ گپ‌جی‌پی‌تی فاکتور نبود', array('status' => 502));
    }
    $tokens = (int) ($raw['usage']['total_tokens'] ?? 0);
    if ($tokens <= 0) {
        $tokens = (int) ($raw['usage']['prompt_tokens'] ?? 0) + (int) ($raw['usage']['completion_tokens'] ?? 0);
    }
    if ($tokens <= 0) {
        $tokens = 1;
    }
    $parsed['tokens'] = $tokens;
    $parsed['model'] = $model;
    $parsed['provider'] = 'gapgpt';
    return new WP_REST_Response($parsed, 200);
}
