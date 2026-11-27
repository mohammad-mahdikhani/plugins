<?php
/**
 * Cloud identity for Daftarchi: same phone/email + password on every PC.
 * Local SQLite still holds shop data; this only mirrors accounts.
 */

add_action('admin_menu', 'daftarchi_auth_menu');
add_action('rest_api_init', 'daftarchi_auth_routes');

function daftarchi_auth_menu() {
    add_submenu_page(
        'daftarchi',
        'حساب‌های ابری دفترچی',
        'حساب ابری',
        'manage_options',
        'daftarchi-auth',
        'daftarchi_auth_page'
    );
}

function daftarchi_auth_rows() {
    $rows = get_option('daftarchi_cloud_accounts', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_auth_key($phone, $email) {
    $phone = preg_replace('/\D+/', '', (string) $phone);
    if (preg_match('/^9\d{9}$/', $phone)) {
        $phone = '0' . $phone;
    }
    $email = strtolower(sanitize_email((string) $email));
    if ($phone !== '' && preg_match('/^09\d{9}$/', $phone)) {
        return 'p:' . $phone;
    }
    if ($email !== '' && is_email($email)) {
        return 'e:' . $email;
    }
    return '';
}

function daftarchi_auth_save_rows($rows) {
    if (count($rows) > 5000) {
        $rows = array_slice($rows, -5000, null, true);
    }
    update_option('daftarchi_cloud_accounts', $rows, false);
}

function daftarchi_auth_routes() {
    register_rest_route('daftarchi/v1', '/auth/register', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_auth_register',
    ));
    register_rest_route('daftarchi/v1', '/auth/login', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_auth_login',
    ));
}

function daftarchi_auth_check_key($request) {
    $key = (string) $request->get_param('key');
    if (!hash_equals('daftarchi-webakery-mail', $key)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    return true;
}

function daftarchi_auth_register(WP_REST_Request $request) {
    $ok = daftarchi_auth_check_key($request);
    if (is_wp_error($ok)) {
        return $ok;
    }
    $password = (string) $request->get_param('password');
    if (strlen($password) < 6) {
        return new WP_Error('password', 'رمز کوتاه است', array('status' => 400));
    }
    $phone = preg_replace('/\D+/', '', (string) $request->get_param('phone'));
    if (preg_match('/^9\d{9}$/', $phone)) {
        $phone = '0' . $phone;
    }
    $email = strtolower(sanitize_email((string) $request->get_param('email')));
    $key = daftarchi_auth_key($phone, $email);
    if ($key === '') {
        return new WP_Error('identity', 'موبایل یا جیمیل لازم است', array('status' => 400));
    }
    $rows = daftarchi_auth_rows();
    $role = sanitize_key((string) $request->get_param('role'));
    if (!in_array($role, array('admin', 'cashier', 'stock'), true)) {
        $role = 'admin';
    }
    $created = gmdate('c');
    if (isset($rows[$key])) {
        $rows[$key]['name'] = mb_substr(sanitize_text_field((string) $request->get_param('name')), 0, 80) ?: ($rows[$key]['name'] ?? '');
        $rows[$key]['password_hash'] = wp_hash_password($password);
        $rows[$key]['role'] = $role;
        $rows[$key]['updated_at'] = gmdate('c');
    } else {
        $rows[$key] = array(
            'account_key' => $key,
            'name' => mb_substr(sanitize_text_field((string) $request->get_param('name')), 0, 80),
            'phone' => $phone,
            'email' => $email,
            'role' => $role,
            'password_hash' => wp_hash_password($password),
            'created_at' => $created,
            'updated_at' => $created,
        );
    }
    daftarchi_auth_save_rows($rows);
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-auth',
        'account' => daftarchi_auth_public($rows[$key]),
    ), 200);
}

function daftarchi_auth_login(WP_REST_Request $request) {
    $ok = daftarchi_auth_check_key($request);
    if (is_wp_error($ok)) {
        return $ok;
    }
    $password = (string) $request->get_param('password');
    if ($password === '') {
        return new WP_Error('password', 'رمز لازم است', array('status' => 400));
    }
    $ident = (string) $request->get_param('ident');
    if ($ident === '') {
        $ident = (string) $request->get_param('phone');
    }
    if ($ident === '') {
        $ident = (string) $request->get_param('email');
    }
    $phone = '';
    $email = '';
    if (strpos($ident, '@') !== false) {
        $email = strtolower(sanitize_email($ident));
    } else {
        $phone = preg_replace('/\D+/', '', $ident);
        if (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '0' . $phone;
        }
    }
    $key = daftarchi_auth_key($phone, $email);
    if ($key === '') {
        return new WP_Error('identity', 'موبایل یا جیمیل لازم است', array('status' => 400));
    }
    $rows = daftarchi_auth_rows();
    if (!isset($rows[$key])) {
        return new WP_Error('missing', 'حساب پیدا نشد', array('status' => 404));
    }
    $row = $rows[$key];
    if (!wp_check_password($password, (string) ($row['password_hash'] ?? ''))) {
        return new WP_Error('auth', 'رمز نادرست است', array('status' => 401));
    }
    $row['updated_at'] = gmdate('c');
    $row['last_login'] = gmdate('c');
    $rows[$key] = $row;
    daftarchi_auth_save_rows($rows);
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-auth',
        'account' => daftarchi_auth_public($row),
    ), 200);
}

function daftarchi_auth_public($row) {
    return array(
        'account_key' => (string) ($row['account_key'] ?? ''),
        'name' => (string) ($row['name'] ?? ''),
        'phone' => (string) ($row['phone'] ?? ''),
        'email' => (string) ($row['email'] ?? ''),
        'role' => (string) ($row['role'] ?? 'admin'),
    );
}

function daftarchi_auth_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = daftarchi_auth_rows();
    echo '<div class="wrap"><h1>حساب‌های ابری دفترچی</h1>';
    echo '<p>ورود یکسان روی چند نصب؛ داده فروش هنوز روی هر PC جدا است.</p>';
    echo '<table class="widefat striped"><thead><tr><th>موبایل</th><th>جیمیل</th><th>نام</th><th>نقش</th><th>آخرین ورود</th></tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr><td>' . esc_html($row['phone'] ?? '') . '</td><td>' . esc_html($row['email'] ?? '') . '</td><td>' . esc_html($row['name'] ?? '') . '</td><td>' . esc_html($row['role'] ?? '') . '</td><td>' . esc_html($row['last_login'] ?? '') . '</td></tr>';
    }
    if (!$rows) {
        echo '<tr><td colspan="5">هنوز حسابی نیست.</td></tr>';
    }
    echo '</tbody></table></div>';
}