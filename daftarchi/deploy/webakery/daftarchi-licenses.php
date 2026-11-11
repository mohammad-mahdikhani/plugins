<?php
/**
 * List Daftarchi license buyers in wp-admin.
 * The app sends the buyer, phone, plan and amount. Passwords are refused.
 */

add_action('admin_menu', 'daftarchi_licenses_menu');
add_action('rest_api_init', 'daftarchi_licenses_route');

function daftarchi_licenses_menu() {
    add_menu_page(
        'لایسنس دفترچی',
        'لایسنس دفترچی',
        'manage_options',
        'daftarchi-licenses',
        'daftarchi_licenses_page',
        'dashicons-tickets-alt',
        60
    );
}

function daftarchi_licenses_rows() {
    $rows = get_option('daftarchi_licenses', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_licenses_key_ok($request) {
    $key = (string) $request->get_param('key');
    return $key !== '' && hash_equals('daftarchi-webakery-mail', $key);
}

function daftarchi_licenses_phone($value) {
    $phone = preg_replace('/\D+/', '', (string) $value);
    if (preg_match('/^9\d{9}$/', $phone)) {
        $phone = '0' . $phone;
    }
    return $phone;
}

function daftarchi_licenses_same($row, $old) {
    $shop = (string) ($row['shop_phone'] ?? '');
    $local = (string) ($row['local_id'] ?? '');
    $old_shop = (string) ($old['shop_phone'] ?? '');
    $old_local = (string) ($old['local_id'] ?? '');
    if ($local !== '' && $local === $old_local && $shop === $old_shop) {
        return true;
    }
    $ref = (string) ($row['gateway_ref'] ?? '');
    return $ref !== '' && $ref === (string) ($old['gateway_ref'] ?? '') && (string) ($row['phone'] ?? '') === (string) ($old['phone'] ?? '');
}

function daftarchi_licenses_keep($row) {
    $rows = daftarchi_licenses_rows();
    $kept = false;
    foreach ($rows as $index => $old) {
        if (!is_array($old) || !daftarchi_licenses_same($row, $old)) {
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
    update_option('daftarchi_licenses', $rows, false);
    return $row;
}

function daftarchi_licenses_drop($row) {
    $rows = array();
    foreach (daftarchi_licenses_rows() as $old) {
        if (is_array($old) && daftarchi_licenses_same($row, $old)) {
            continue;
        }
        $rows[] = $old;
    }
    update_option('daftarchi_licenses', $rows, false);
    return count($rows);
}

function daftarchi_licenses_route() {
    register_rest_route('daftarchi/v1', '/license', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_licenses_rest_save',
    ));
    register_rest_route('daftarchi/v1', '/licenses', array(
        array(
            'methods' => 'GET',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_licenses_rest_list',
        ),
        array(
            'methods' => 'DELETE',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_licenses_rest_delete',
        ),
    ));
}

function daftarchi_licenses_one($request, $source) {
    if (!empty($source['password']) || !empty($source['password_hash'])) {
        return new WP_Error('password', 'رمز ذخیره نمی‌شود', array('status' => 400));
    }
    $status = sanitize_key((string) ($source['status'] ?? 'paid'));
    $row = array(
        'shop_phone' => daftarchi_licenses_phone($source['shop_phone'] ?? ''),
        'local_id' => absint($source['local_id'] ?? 0),
        'buyer_name' => mb_substr(sanitize_text_field((string) ($source['buyer_name'] ?? '')), 0, 80),
        'phone' => daftarchi_licenses_phone($source['phone'] ?? ''),
        'plan' => sanitize_key((string) ($source['plan'] ?? '')),
        'title' => mb_substr(sanitize_text_field((string) ($source['title'] ?? '')), 0, 80),
        'payable' => absint($source['payable'] ?? 0),
        'status' => $status,
        'starts_at' => mb_substr(sanitize_text_field((string) ($source['starts_at'] ?? '')), 0, 40),
        'ends_at' => mb_substr(sanitize_text_field((string) ($source['ends_at'] ?? '')), 0, 40),
        'gateway_ref' => mb_substr(sanitize_text_field((string) ($source['gateway_ref'] ?? '')), 0, 80),
        'payment_method' => sanitize_key((string) ($source['payment_method'] ?? '')),
        'received_at' => gmdate('c'),
    );
    if (!preg_match('/^0\d{10}$/', $row['phone']) || $row['buyer_name'] === '') {
        return new WP_Error('buyer', 'نام و موبایل خریدار لازم است', array('status' => 400));
    }
    if ($status !== 'paid') {
        daftarchi_licenses_drop($row);
        return $row;
    }
    return daftarchi_licenses_keep($row);
}

function daftarchi_licenses_rest_save($request) {
    if (!daftarchi_licenses_key_ok($request)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    $items = $request->get_param('items');
    if (!is_array($items)) {
        $items = array($request->get_params());
    }
    $ids = array();
    foreach ($items as $source) {
        if (!is_array($source)) {
            continue;
        }
        $saved = daftarchi_licenses_one($request, $source);
        if (is_wp_error($saved)) {
            return $saved;
        }
        if (($saved['status'] ?? '') === 'paid') {
            $ids[] = (int) $saved['local_id'];
        }
    }
    return new WP_REST_Response(array(
        'ok' => true,
        'service' => 'daftarchi-licenses',
        'saved' => count($ids),
        'ids' => $ids,
    ), 200);
}

function daftarchi_licenses_rest_list() {
    return new WP_REST_Response(array('licenses' => array_reverse(daftarchi_licenses_rows())), 200);
}

function daftarchi_licenses_rest_delete($request) {
    if (!current_user_can('manage_options')) {
        return new WP_Error('forbidden', 'اجازه نیست', array('status' => 403));
    }
    $row = array(
        'shop_phone' => daftarchi_licenses_phone($request->get_param('shop_phone')),
        'local_id' => absint($request->get_param('local_id')),
        'phone' => daftarchi_licenses_phone($request->get_param('phone')),
        'gateway_ref' => sanitize_text_field((string) $request->get_param('gateway_ref')),
    );
    $count = daftarchi_licenses_drop($row);
    return new WP_REST_Response(array('ok' => true, 'count' => $count), 200);
}

function daftarchi_licenses_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = array_reverse(daftarchi_licenses_rows());
    echo '<div class="wrap">';
    echo '<h1>لایسنس دفترچی</h1>';
    echo '<p>هر لایسنسی که در دفترچی خریداری و پرداخت شود، این‌جا فهرست می‌شود.</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>خریدار</th><th>موبایل</th><th>طرح</th><th>مبلغ</th><th>پرداخت</th><th>پیگیری</th><th>شروع</th><th>پایان</th><th>موبایل فروشگاه</th>';
    echo '</tr></thead><tbody>';
    if (!$rows) {
        echo '<tr><td colspan="9">هنوز لایسنسی خریداری نشده است.</td></tr>';
    }
    foreach ($rows as $row) {
        if (!is_array($row) || ($row['status'] ?? '') !== 'paid') {
            continue;
        }
        $pay = array(
            'cash' => 'نقد',
            'card' => 'کارت',
            'gateway' => 'درگاه',
        );
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['buyer_name'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['phone'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['title'] ?? $row['plan'] ?? '')) . '</td>';
        echo '<td>' . esc_html(number_format((int) ($row['payable'] ?? 0))) . '</td>';
        echo '<td>' . esc_html($pay[$row['payment_method'] ?? ''] ?? (string) ($row['payment_method'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['gateway_ref'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['starts_at'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['ends_at'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['shop_phone'] ?? '')) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}
