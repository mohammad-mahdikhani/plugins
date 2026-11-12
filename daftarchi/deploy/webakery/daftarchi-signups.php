<?php
/**
 * Store Daftarchi account signups and show them in wp-admin.
 * The app sends name, phone and Gmail. Passwords are refused.
 */

add_action('admin_menu', 'daftarchi_signups_menu');
add_action('rest_api_init', 'daftarchi_signups_route');

function daftarchi_signups_menu() {
    add_submenu_page(
        'daftarchi',
        'ثبت‌نام دفترچی',
        'ثبت‌نام',
        'manage_options',
        'daftarchi-signups',
        'daftarchi_signups_page'
    );
}

function daftarchi_signups_rows() {
    $rows = get_option('daftarchi_signups', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_signups_keep($row) {
    $rows = daftarchi_signups_rows();
    $email = strtolower((string) ($row['email'] ?? ''));
    $phone = (string) ($row['phone'] ?? '');
    $kept = false;
    foreach ($rows as $index => $old) {
        if (!is_array($old)) {
            continue;
        }
        $old_email = strtolower((string) ($old['email'] ?? ''));
        $old_phone = (string) ($old['phone'] ?? '');
        $same = ($email !== '' && $old_email === $email) || ($email === '' && $phone !== '' && $old_phone === $phone);
        if (!$same) {
            continue;
        }
        if (!empty($old['created_at'])) {
            $row['created_at'] = $old['created_at'];
        }
        if (!empty($old['received_at']) && empty($row['received_at'])) {
            $row['received_at'] = $old['received_at'];
        }
        $rows[$index] = $row;
        $kept = true;
        break;
    }
    if (!$kept) {
        $rows[] = $row;
    }
    if (count($rows) > 1000) {
        $rows = array_slice($rows, -1000);
    }
    update_option('daftarchi_signups', $rows, false);
    return $row;
}

function daftarchi_signups_route() {
    register_rest_route('daftarchi/v1', '/signup', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_signups_rest_save',
    ));
    register_rest_route('daftarchi/v1', '/signups', array(
        array(
            'methods' => 'GET',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_signups_rest_list',
        ),
        array(
            'methods' => 'DELETE',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_signups_rest_delete',
        ),
    ));
}

function daftarchi_signups_rest_save($request) {
    $key = (string) $request->get_param('key');
    if (!hash_equals('daftarchi-webakery-mail', $key)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    if ($request->get_param('password') || $request->get_param('password_hash')) {
        return new WP_Error('password', 'رمز ذخیره نمی‌شود', array('status' => 400));
    }
    $name = sanitize_text_field((string) $request->get_param('name'));
    $phone = preg_replace('/\D+/', '', (string) $request->get_param('phone'));
    if (preg_match('/^9\d{9}$/', $phone)) {
        $phone = '0' . $phone;
    }
    $email = sanitize_email((string) $request->get_param('email'));
    if (!preg_match('/^09\d{9}$/', $phone) && $email === '') {
        return new WP_Error('identity', 'موبایل یا جیمیل لازم است', array('status' => 400));
    }
    if ($email !== '' && !is_email($email)) {
        return new WP_Error('email', 'جیمیل معتبر نیست', array('status' => 400));
    }
    $row = array(
        'name' => mb_substr($name, 0, 80),
        'phone' => $phone,
        'email' => strtolower($email),
        'verified' => ((string) $request->get_param('verified') === '1' || $request->get_param('verified') === 1) ? 1 : 0,
        'shop' => mb_substr(sanitize_text_field((string) $request->get_param('shop')), 0, 80),
        'channel' => sanitize_key((string) $request->get_param('channel')),
        'created_at' => mb_substr(sanitize_text_field((string) $request->get_param('created_at')), 0, 40),
        'received_at' => gmdate('c'),
    );
    $saved = daftarchi_signups_keep($row);
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-signups',
        'email' => $saved['email'],
        'phone' => $saved['phone'],
    ), 200);
}

function daftarchi_signups_rest_list() {
    $rows = daftarchi_signups_rows();
    return new WP_REST_Response(array('signups' => array_reverse($rows)), 200);
}

function daftarchi_signups_rest_delete($request) {
    $email = strtolower(sanitize_email((string) $request->get_param('email')));
    $phone = preg_replace('/\D+/', '', (string) $request->get_param('phone'));
    $rows = array();
    foreach (daftarchi_signups_rows() as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($email !== '' && strtolower((string) ($row['email'] ?? '')) === $email) {
            continue;
        }
        if ($email === '' && $phone !== '' && (string) ($row['phone'] ?? '') === $phone) {
            continue;
        }
        $rows[] = $row;
    }
    update_option('daftarchi_signups', $rows, false);
    return new WP_REST_Response(array('ok' => true, 'count' => count($rows)), 200);
}

function daftarchi_signups_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = array_reverse(daftarchi_signups_rows());
    echo '<div class="wrap">';
    echo '<h1>ثبت‌نام دفترچی</h1>';
    echo '<p>هر کسی در دفترچی اکانت بسازد، نام و موبایل و جیمیلش این‌جا می‌آید. رمز عبور ذخیره نمی‌شود.</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>نام</th><th>موبایل</th><th>جیمیل</th><th>فروشگاه</th><th>تأیید جیمیل</th><th>زمان ثبت</th>';
    echo '</tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="6">هنوز کسی ثبت‌نام نکرده است.</td></tr>';
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $when = (string) ($row['received_at'] ?? $row['created_at'] ?? '');
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['name'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['phone'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['email'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['shop'] ?? '')) . '</td>';
        echo '<td>' . (!empty($row['verified']) ? 'بله' : 'نه') . '</td>';
        echo '<td>' . esc_html($when) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
