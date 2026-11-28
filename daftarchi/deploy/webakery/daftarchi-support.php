<?php
/**
 * List Daftarchi error reports and chats in wp-admin.
 * Nothing is sent to Telegram.
 */

add_action('admin_menu', 'daftarchi_support_menu');
add_action('admin_notices', 'daftarchi_support_notice');
add_action('wp_dashboard_setup', 'daftarchi_support_dashboard');
add_action('rest_api_init', 'daftarchi_support_route');
add_action('load-daftarchi_page_daftarchi-support', 'daftarchi_support_mark_seen');

function daftarchi_support_rows() {
    $rows = get_option('daftarchi_support', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_support_unread_counts() {
    $chat = 0;
    $bug = 0;
    foreach (daftarchi_support_rows() as $row) {
        if (!is_array($row) || !empty($row['seen_at'])) {
            continue;
        }
        if (($row['kind'] ?? '') === 'bug') {
            $bug++;
        } else {
            $chat++;
        }
    }
    return array($chat, $bug);
}

function daftarchi_support_menu() {
    list($chat, $bug) = daftarchi_support_unread_counts();
    $waiting = $chat + $bug;
    $title = 'پیام‌ها';
    if ($waiting) {
        $title .= ' <span class="awaiting-mod">' . (int) $waiting . '</span>';
    }
    add_submenu_page(
        'daftarchi',
        'پیام دفترچی',
        $title,
        'manage_options',
        'daftarchi-support',
        'daftarchi_support_page'
    );
    if ($waiting) {
        global $menu;
        foreach ((array) $menu as $index => $item) {
            if (isset($item[2]) && $item[2] === 'daftarchi') {
                $menu[$index][0] = 'دفترچی <span class="awaiting-mod">' . (int) $waiting . '</span>';
                break;
            }
        }
    }
}

function daftarchi_support_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if ($screen && $screen->id === 'daftarchi_page_daftarchi-support') {
        return;
    }
    list($chat, $bug) = daftarchi_support_unread_counts();
    if (!$chat && !$bug) {
        return;
    }
    $parts = array();
    if ($bug) {
        $parts[] = (int) $bug . ' گزارش خطا';
    }
    if ($chat) {
        $parts[] = (int) $chat . ' چت';
    }
    $url = admin_url('admin.php?page=daftarchi-support');
    echo '<div class="notice notice-info"><p>دفترچی: ' . esc_html(implode(' و ', $parts)) . ' تازه رسیده است. <a href="' . esc_url($url) . '">دیدن در پیشخوان</a></p></div>';
}

function daftarchi_support_dashboard() {
    wp_add_dashboard_widget('daftarchi_support', 'پیام دفترچی', 'daftarchi_support_widget');
}

function daftarchi_support_widget() {
    list($chat, $bug) = daftarchi_support_unread_counts();
    if ($chat || $bug) {
        echo '<p><strong>' . (int) $bug . ' گزارش خطا و ' . (int) $chat . ' چت خوانده نشده.</strong></p>';
    } else {
        echo '<p>گزارش خطا و چت فروشگاه‌ها این‌جا می‌آید. به تلگرام نمی‌رود.</p>';
    }
    $rows = array_reverse(daftarchi_support_rows());
    echo '<ul>';
    $shown = 0;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $label = (($row['kind'] ?? '') === 'bug') ? 'گزارش خطا' : 'چت';
        $fresh = empty($row['seen_at']) ? ' · تازه' : '';
        echo '<li><strong>' . esc_html($label . $fresh) . '</strong> — ' . esc_html((string) ($row['shop'] ?? '')) . ' — ' . esc_html((string) ($row['phone'] ?? '')) . ' — ' . esc_html(mb_substr((string) ($row['body'] ?? ''), 0, 120)) . '</li>';
        $shown++;
        if ($shown >= 8) {
            break;
        }
    }
    if (!$shown) {
        echo '<li>هنوز پیامی نرسیده است.</li>';
    }
    echo '</ul>';
    echo '<p><a href="' . esc_url(admin_url('admin.php?page=daftarchi-support')) . '">همه پیام‌ها</a></p>';
}

function daftarchi_support_mark_seen() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = daftarchi_support_rows();
    $changed = false;
    $now = gmdate('c');
    foreach ($rows as $index => $row) {
        if (!is_array($row) || !empty($row['seen_at'])) {
            continue;
        }
        $rows[$index]['seen_at'] = $now;
        $changed = true;
    }
    if ($changed) {
        update_option('daftarchi_support', $rows, false);
    }
}

function daftarchi_support_route() {
    register_rest_route('daftarchi/v1', '/support', array(
        array(
            'methods' => 'POST',
            'permission_callback' => '__return_true',
            'callback' => 'daftarchi_support_rest_save',
        ),
        array(
            'methods' => 'GET',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_support_rest_list',
        ),
        array(
            'methods' => 'DELETE',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_support_rest_delete',
        ),
    ));
}

function daftarchi_support_rest_save($request) {
    $key = (string) $request->get_param('key');
    if (!hash_equals('daftarchi-webakery-mail', $key)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    if ($request->get_param('password') || $request->get_param('password_hash') || $request->get_param('token')) {
        return new WP_Error('secret', 'رمز و توکن ذخیره نمی‌شود', array('status' => 400));
    }
    $kind = (string) $request->get_param('kind') === 'bug' ? 'bug' : 'chat';
    $body = sanitize_textarea_field((string) $request->get_param('body'));
    $body = trim(preg_replace("/\s+/u", ' ', $body));
    if ($body === '') {
        return new WP_Error('empty', 'متن پیام خالی است', array('status' => 400));
    }
    $rows = daftarchi_support_rows();
    $row = array(
        'id' => count($rows) + 1,
        'kind' => $kind,
        'body' => mb_substr($body, 0, 1000),
        'page' => mb_substr(sanitize_text_field((string) $request->get_param('page')), 0, 40),
        'error' => mb_substr(sanitize_text_field((string) $request->get_param('error')), 0, 300),
        'shop' => mb_substr(sanitize_text_field((string) $request->get_param('shop')), 0, 80),
        'user' => mb_substr(sanitize_text_field((string) $request->get_param('user')), 0, 80),
        'phone' => mb_substr(preg_replace('/\D+/', '', (string) $request->get_param('phone')), 0, 20),
        'image' => daftarchi_support_store_image((string) $request->get_param('image')),
        'version' => mb_substr(sanitize_text_field((string) $request->get_param('version')), 0, 20),
        'channel' => sanitize_key((string) $request->get_param('channel')),
        'received_at' => gmdate('c'),
        'seen_at' => '',
    );
    $rows[] = $row;
    if (count($rows) > 300) {
        $rows = array_slice($rows, -300);
    }
    update_option('daftarchi_support', $rows, false);
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-support',
        'id' => $row['id'],
        'kind' => $kind,
    ), 200);
}

function daftarchi_support_rest_list() {
    return new WP_REST_Response(array('messages' => array_reverse(daftarchi_support_rows())), 200);
}

function daftarchi_support_rest_delete($request) {
    $id = (int) $request->get_param('id');
    $rows = array();
    foreach (daftarchi_support_rows() as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($id && (int) ($row['id'] ?? 0) === $id) {
            continue;
        }
        $rows[] = $row;
    }
    update_option('daftarchi_support', $rows, false);
    return new WP_REST_Response(array('ok' => true, 'count' => count($rows)), 200);
}

function daftarchi_support_store_image($raw) {
    $raw = (string) $raw;
    $comma = strpos($raw, ',');
    if (strpos($raw, 'data:image/') !== 0 || $comma === false) {
        return '';
    }
    $bytes = base64_decode(substr($raw, $comma + 1), true);
    if (!$bytes || strlen($bytes) > 500000 || !function_exists('wp_upload_bits')) {
        return '';
    }
    $upload = wp_upload_bits('daftarchi-support-' . gmdate('Ymd-His') . '.jpg', null, $bytes);
    if (!empty($upload['error']) || empty($upload['url'])) {
        return '';
    }
    return (string) $upload['url'];
}

function daftarchi_support_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = array_reverse(daftarchi_support_rows());
    echo '<div class="wrap">';
    echo '<h1>پیام دفترچی</h1>';
    echo '<p>گزارش خطا و چت فروشگاه‌ها این‌جا فهرست می‌شود. به تلگرام فرستاده نمی‌شود. با باز کردن این صفحه، نوتیف تازه‌ها خوانده‌شده می‌شود.</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>نوع</th><th>فروشگاه</th><th>شماره اکانت</th><th>کاربر</th><th>متن</th><th>اسکرین</th><th>صفحه</th><th>خطا</th><th>نسخه</th><th>زمان</th>';
    echo '</tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="10">هنوز پیامی نرسیده است.</td></tr>';
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $label = (($row['kind'] ?? '') === 'bug') ? 'گزارش خطا' : 'چت';
        $image = (string) ($row['image'] ?? '');
        $shot = '';
        if ($image !== '' && strpos($image, 'http') === 0) {
            $shot = '<a href="' . esc_url($image) . '" target="_blank" rel="noopener"><img src="' . esc_url($image) . '" alt="" style="max-width:140px;height:auto"></a>';
        }
        echo '<tr>';
        echo '<td>' . esc_html($label) . '</td>';
        echo '<td>' . esc_html((string) ($row['shop'] ?? '')) . '</td>';
        echo '<td class="ltr">' . esc_html((string) ($row['phone'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['user'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['body'] ?? '')) . '</td>';
        echo '<td>' . $shot . '</td>';
        echo '<td>' . esc_html((string) ($row['page'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['error'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['version'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['received_at'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
