<?php
/**
 * Store Daftarchi logins, including the free trial, and show them in wp-admin.
 * The app sends name, phone, shop and license status. Passwords are refused.
 */

add_action('admin_menu', 'daftarchi_logins_menu');
add_action('rest_api_init', 'daftarchi_logins_route');

function daftarchi_logins_menu() {
    add_submenu_page(
        'daftarchi',
        'ورود دفترچی',
        'ورودها',
        'manage_options',
        'daftarchi-logins',
        'daftarchi_logins_page'
    );
}

function daftarchi_logins_rows() {
    $rows = get_option('daftarchi_logins', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_logins_same($row, $old) {
    $install = (string) ($row['install_id'] ?? '');
    $old_install = (string) ($old['install_id'] ?? '');
    if ($install !== '' && $install === $old_install) {
        return true;
    }
    if ($install !== '' && $old_install !== '') {
        return false;
    }
    $email = strtolower((string) ($row['email'] ?? ''));
    $phone = (string) ($row['phone'] ?? '');
    $old_email = strtolower((string) ($old['email'] ?? ''));
    $old_phone = (string) ($old['phone'] ?? '');
    return ($email !== '' && $old_email === $email) || ($email === '' && $phone !== '' && $old_phone === $phone);
}

function daftarchi_logins_keep($row) {
    $rows = daftarchi_logins_rows();
    $kept = false;
    foreach ($rows as $index => $old) {
        if (!is_array($old) || !daftarchi_logins_same($row, $old)) {
            continue;
        }
        $merged = $old;
        foreach ($row as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }
            $merged[$key] = $value;
        }
        $merged['received_at'] = $row['received_at'] ?? gmdate('c');
        if (!empty($old['first_seen'])) {
            $merged['first_seen'] = $old['first_seen'];
        } elseif (empty($merged['first_seen'])) {
            $merged['first_seen'] = $old['received_at'] ?? $merged['received_at'];
        }
        $merged['login_count'] = (int) ($old['login_count'] ?? 0) + 1;
        $rows[$index] = $merged;
        $row = $merged;
        $kept = true;
        break;
    }
    if (!$kept) {
        if (empty($row['first_seen'])) {
            $row['first_seen'] = $row['received_at'] ?? gmdate('c');
        }
        $row['login_count'] = 1;
        $rows[] = $row;
    }
    if (count($rows) > 1000) {
        $rows = array_slice($rows, -1000);
    }
    update_option('daftarchi_logins', $rows, false);
    return $row;
}

function daftarchi_logins_route() {
    register_rest_route('daftarchi/v1', '/login', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_logins_rest_save',
    ));
    register_rest_route('daftarchi/v1', '/logins', array(
        array(
            'methods' => 'GET',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_logins_rest_list',
        ),
        array(
            'methods' => 'DELETE',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_logins_rest_delete',
        ),
    ));
}

function daftarchi_logins_phone($value) {
    $phone = preg_replace('/\D+/', '', (string) $value);
    if (preg_match('/^9\d{9}$/', $phone)) {
        $phone = '0' . $phone;
    }
    return $phone;
}

function daftarchi_logins_rest_save($request) {
    $key = (string) $request->get_param('key');
    if (!hash_equals('daftarchi-webakery-mail', $key)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    if ($request->get_param('password') || $request->get_param('password_hash')) {
        return new WP_Error('password', 'رمز ذخیره نمی‌شود', array('status' => 400));
    }
    $phone = daftarchi_logins_phone($request->get_param('phone'));
    $email = sanitize_email((string) $request->get_param('email'));
    $install = mb_substr(sanitize_text_field((string) $request->get_param('install_id')), 0, 80);
    if (!preg_match('/^09\d{9}$/', $phone) && $email === '' && $install === '') {
        return new WP_Error('identity', 'موبایل، جیمیل یا شناسه نصب لازم است', array('status' => 400));
    }
    if ($email !== '' && !is_email($email)) {
        return new WP_Error('email', 'جیمیل معتبر نیست', array('status' => 400));
    }
    $row = array(
        'name' => mb_substr(sanitize_text_field((string) $request->get_param('name')), 0, 80),
        'phone' => $phone,
        'email' => strtolower($email),
        'shop' => mb_substr(sanitize_text_field((string) $request->get_param('shop')), 0, 80),
        'shop_mode' => sanitize_key((string) $request->get_param('shop_mode')),
        'site_id' => sanitize_key((string) $request->get_param('site_id')),
        'install_id' => $install,
        'channel' => sanitize_key((string) $request->get_param('channel')),
        'version' => mb_substr(sanitize_text_field((string) $request->get_param('version')), 0, 20),
        'access' => sanitize_key((string) $request->get_param('access')),
        'access_title' => mb_substr(sanitize_text_field((string) $request->get_param('access_title')), 0, 80),
        'active' => ((string) $request->get_param('active') === '1' || $request->get_param('active') === 1) ? 1 : 0,
        'vip' => ((string) $request->get_param('vip') === '1' || $request->get_param('vip') === 1) ? 1 : 0,
        'days_left' => absint($request->get_param('days_left')),
        'ends_at' => mb_substr(sanitize_text_field((string) $request->get_param('ends_at')), 0, 40),
        'products' => absint($request->get_param('products')),
        'sales' => absint($request->get_param('sales')),
        'customers' => absint($request->get_param('customers')),
        'last_login' => mb_substr(sanitize_text_field((string) $request->get_param('last_login')), 0, 40),
        'created_at' => mb_substr(sanitize_text_field((string) $request->get_param('created_at')), 0, 40),
        'received_at' => gmdate('c'),
    );
    if ($row['last_login'] === '') {
        $row['last_login'] = $row['received_at'];
    }
    $saved = daftarchi_logins_keep($row);
    if (function_exists('daftarchi_signups_keep')) {
        daftarchi_signups_keep($saved);
    }
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-logins',
        'email' => $saved['email'],
        'phone' => $saved['phone'],
        'install_id' => $saved['install_id'],
    ), 200);
}

function daftarchi_logins_rest_list() {
    $rows = daftarchi_logins_rows();
    usort($rows, function ($left, $right) {
        return strcmp((string) ($right['last_login'] ?? ''), (string) ($left['last_login'] ?? ''));
    });
    return new WP_REST_Response(array('logins' => $rows), 200);
}

function daftarchi_logins_rest_delete($request) {
    $probe = array(
        'email' => strtolower(sanitize_email((string) $request->get_param('email'))),
        'phone' => daftarchi_logins_phone($request->get_param('phone')),
        'install_id' => (string) $request->get_param('install_id'),
    );
    $rows = array();
    foreach (daftarchi_logins_rows() as $row) {
        if (is_array($row) && daftarchi_logins_same($probe, $row)) {
            continue;
        }
        $rows[] = $row;
    }
    update_option('daftarchi_logins', $rows, false);
    return new WP_REST_Response(array('ok' => true, 'count' => count($rows)), 200);
}

function daftarchi_logins_status($row) {
    $modes = array('trial' => 'رایگان', 'paid' => 'پولی', 'expired' => 'منقضی', 'studio' => 'استودیو');
    $mode = (string) ($row['access'] ?? '');
    $status = $modes[$mode] ?? (string) ($row['access_title'] ?? '');
    if (!empty($row['vip'])) {
        $status .= ' VIP';
    }
    if ($mode === 'trial') {
        $status .= '، ' . absint($row['days_left'] ?? 0) . ' روز';
    }
    return $status;
}

function daftarchi_logins_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = daftarchi_logins_rows();
    usort($rows, function ($left, $right) {
        return strcmp((string) ($right['last_login'] ?? $right['received_at'] ?? ''), (string) ($left['last_login'] ?? $left['received_at'] ?? ''));
    });
    echo '<div class="wrap">';
    echo '<h1>ورود دفترچی</h1>';
    echo '<p>هر ورود به برنامه، حتی دوره رایگان، این‌جا می‌آید. رمز عبور ذخیره نمی‌شود.</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>نام</th><th>موبایل</th><th>جیمیل</th><th>فروشگاه</th><th>وضعیت</th><th>کالا / فروش</th><th>نسخه</th><th>دفعات</th><th>آخرین ورود</th>';
    echo '</tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="9">هنوز کسی وارد نشده است.</td></tr>';
    }
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['name'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['phone'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['email'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['shop'] ?? '')) . '</td>';
        echo '<td>' . esc_html(daftarchi_logins_status($row)) . '</td>';
        echo '<td>' . esc_html(absint($row['products'] ?? 0) . ' / ' . absint($row['sales'] ?? 0)) . '</td>';
        echo '<td>' . esc_html((string) ($row['version'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) absint($row['login_count'] ?? 0)) . '</td>';
        echo '<td>' . esc_html((string) ($row['last_login'] ?? $row['received_at'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
