<?php
/**
 * List Daftarchi SMS panel top-ups in wp-admin.
 * The shop pays the credit plus a 3 percent fee. Passwords are refused.
 */

add_action('admin_menu', 'daftarchi_sms_menu');
add_action('rest_api_init', 'daftarchi_sms_route');

function daftarchi_sms_menu() {
    add_menu_page(
        'پیامک دفترچی',
        'پیامک دفترچی',
        'manage_options',
        'daftarchi-sms',
        'daftarchi_sms_page',
        'dashicons-email-alt',
        61
    );
}

function daftarchi_sms_rows() {
    $rows = get_option('daftarchi_sms_credits', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_sms_key_ok($request) {
    $key = (string) $request->get_param('key');
    return $key !== '' && hash_equals('daftarchi-webakery-mail', $key);
}

function daftarchi_sms_phone($value) {
    $phone = preg_replace('/\D+/', '', (string) $value);
    if (preg_match('/^9\d{9}$/', $phone)) {
        $phone = '0' . $phone;
    }
    return $phone;
}

function daftarchi_sms_keep($row) {
    $rows = daftarchi_sms_rows();
    $kept = false;
    foreach ($rows as $index => $old) {
        if (!is_array($old)) {
            continue;
        }
        $same_shop = (string) ($old['shop_phone'] ?? '') === (string) $row['shop_phone'];
        $same_id = (string) ($old['local_id'] ?? '') === (string) $row['local_id'] && (string) $row['local_id'] !== '0';
        if (!$same_shop || !$same_id) {
            continue;
        }
        if (!empty($old['received_at'])) {
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
    update_option('daftarchi_sms_credits', $rows, false);
    return $row;
}

function daftarchi_sms_route() {
    register_rest_route('daftarchi/v1', '/sms-credit', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_sms_rest_save',
    ));
}

function daftarchi_sms_rest_save($request) {
    if (!daftarchi_sms_key_ok($request)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    $params = $request->get_params();
    if (!empty($params['password']) || !empty($params['password_hash'])) {
        return new WP_Error('password', 'رمز ذخیره نمی‌شود', array('status' => 400));
    }
    if (sanitize_key((string) ($params['status'] ?? '')) !== 'paid') {
        return new WP_REST_Response(array('ok' => true, 'service' => 'daftarchi-sms', 'saved' => 0), 200);
    }
    $row = array(
        'shop_phone' => daftarchi_sms_phone($params['shop_phone'] ?? ''),
        'shop' => mb_substr(sanitize_text_field((string) ($params['shop'] ?? '')), 0, 80),
        'local_id' => absint($params['local_id'] ?? 0),
        'credit' => absint($params['credit'] ?? 0),
        'fee' => absint($params['fee'] ?? 0),
        'payable' => absint($params['payable'] ?? 0),
        'status' => 'paid',
        'gateway_ref' => mb_substr(sanitize_text_field((string) ($params['gateway_ref'] ?? '')), 0, 80),
        'paid_at' => mb_substr(sanitize_text_field((string) ($params['paid_at'] ?? '')), 0, 40),
        'received_at' => gmdate('c'),
    );
    if ($row['credit'] < 100000 || $row['credit'] > 1000000) {
        return new WP_Error('amount', 'مبلغ شارژ خارج از بازه است', array('status' => 400));
    }
    daftarchi_sms_keep($row);
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-sms',
        'saved' => 1,
        'ids' => array($row['local_id']),
    ), 200);
}

function daftarchi_sms_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = array_reverse(daftarchi_sms_rows());
    $fee = 0;
    $credit = 0;
    foreach ($rows as $row) {
        if (!is_array($row) || ($row['status'] ?? '') !== 'paid') {
            continue;
        }
        $fee += (int) ($row['fee'] ?? 0);
        $credit += (int) ($row['credit'] ?? 0);
    }
    echo '<div class="wrap">';
    echo '<h1>پیامک دفترچی</h1>';
    echo '<p>فروشگاه پنل پیامک خودش را شارژ می‌کند. کارمزد هر تراکنش ۳ درصد است و جدا از مبلغ شارژ گرفته می‌شود.</p>';
    echo '<p>جمع شارژ: <strong>' . esc_html(number_format($credit)) . '</strong> تومان · جمع کارمزد شما: <strong>' . esc_html(number_format($fee)) . '</strong> تومان</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>فروشگاه</th><th>موبایل فروشگاه</th><th>شارژ</th><th>کارمزد ۳٪</th><th>پرداخت</th><th>پیگیری</th><th>زمان</th>';
    echo '</tr></thead><tbody>';
    $shown = 0;
    foreach ($rows as $row) {
        if (!is_array($row) || ($row['status'] ?? '') !== 'paid') {
            continue;
        }
        $shown++;
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['shop'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['shop_phone'] ?? '')) . '</td>';
        echo '<td>' . esc_html(number_format((int) ($row['credit'] ?? 0))) . '</td>';
        echo '<td>' . esc_html(number_format((int) ($row['fee'] ?? 0))) . '</td>';
        echo '<td>' . esc_html(number_format((int) ($row['payable'] ?? 0))) . '</td>';
        echo '<td>' . esc_html((string) ($row['gateway_ref'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['paid_at'] ?? '')) . '</td>';
        echo '</tr>';
    }
    if (!$shown) {
        echo '<tr><td colspan="7">هنوز شارژی پرداخت نشده است.</td></tr>';
    }
    echo '</tbody></table></div>';
}
