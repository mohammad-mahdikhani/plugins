<?php
/**
 * List Daftarchi SMS panel top-ups in wp-admin.
 * The shop pays the credit plus a 3 percent fee. Passwords are refused.
 */

add_action('admin_menu', 'daftarchi_sms_menu');
add_action('rest_api_init', 'daftarchi_sms_route');

function daftarchi_sms_menu() {
    add_submenu_page(
        'daftarchi',
        'پیامک دفترچی',
        'پیامک',
        'manage_options',
        'daftarchi-sms',
        'daftarchi_sms_page'
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
    register_rest_route('daftarchi/v1', '/sms-pattern', array(
        'methods' => 'POST',
        'permission_callback' => '__return_true',
        'callback' => 'daftarchi_sms_pattern_rest',
    ));
}

function daftarchi_sms_panel() {
    $panel = get_option('daftarchi_melipayamak', array());
    return is_array($panel) ? $panel : array();
}

function daftarchi_sms_catalog() {
    $rows = get_option('daftarchi_sms_catalog', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_sms_catalog_body($pattern_id) {
    $pattern_id = sanitize_key((string) $pattern_id);
    if ($pattern_id === '') {
        return '';
    }
    foreach (daftarchi_sms_catalog() as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (sanitize_key((string) ($row['id'] ?? '')) !== $pattern_id) {
            continue;
        }
        return preg_replace('/\D+/', '', (string) ($row['body_id'] ?? ''));
    }
    return '';
}

function daftarchi_sms_patterns() {
    $rows = get_option('daftarchi_sms_patterns', array());
    return is_array($rows) ? $rows : array();
}

function daftarchi_sms_pattern_key($shop_phone, $pattern) {
    return daftarchi_sms_phone($shop_phone) . '__' . sanitize_key((string) $pattern);
}

function daftarchi_sms_pattern_vars($text) {
    $index = 0;
    return preg_replace_callback('/\{[^{}]+\}/u', function () use (&$index) {
        $token = '{' . $index . '}';
        $index++;
        return $token;
    }, (string) $text);
}

function daftarchi_sms_keep_pattern($row) {
    $rows = daftarchi_sms_patterns();
    $key = daftarchi_sms_pattern_key($row['shop_phone'], $row['id']);
    $kept = false;
    foreach ($rows as $index => $old) {
        if (!is_array($old)) {
            continue;
        }
        if (daftarchi_sms_pattern_key($old['shop_phone'] ?? '', $old['id'] ?? '') !== $key) {
            continue;
        }
        $row['body_id'] = (string) ($old['body_id'] ?? '');
        $rows[$index] = $row;
        $kept = true;
        break;
    }
    if (!$kept) {
        $row['body_id'] = '';
        $rows[] = $row;
    }
    if (count($rows) > 500) {
        $rows = array_slice($rows, -500);
    }
    update_option('daftarchi_sms_patterns', $rows, false);
}

function daftarchi_sms_pattern_rest($request) {
    if (!daftarchi_sms_key_ok($request)) {
        return new WP_Error('forbidden', 'کلید نادرست است', array('status' => 403));
    }
    $params = $request->get_params();
    if (!empty($params['password']) || !empty($params['password_hash']) || !empty($params['token'])) {
        return new WP_Error('secret', 'رمز ذخیره نمی‌شود', array('status' => 400));
    }
    $action = sanitize_key((string) ($params['action'] ?? ''));
    if ($action === 'catalog') {
        $patterns = array();
        foreach (daftarchi_sms_catalog() as $row) {
            if (!is_array($row)) {
                continue;
            }
            $patterns[] = array(
                'id' => (string) ($row['id'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'text' => (string) ($row['text'] ?? ''),
                'pattern' => daftarchi_sms_pattern_vars((string) ($row['text'] ?? '')),
            );
        }
        return new WP_REST_Response(array(
            'ok' => true,
            'service' => 'daftarchi-sms-pattern',
            'patterns' => $patterns,
        ), 200);
    }
    if ($action === 'patterns') {
        $shop_phone = daftarchi_sms_phone($params['shop_phone'] ?? '');
        $shop = mb_substr(sanitize_text_field((string) ($params['shop'] ?? '')), 0, 80);
        $patterns = $params['patterns'] ?? array();
        if (!is_array($patterns)) {
            $patterns = array();
        }
        foreach ($patterns as $pattern) {
            if (!is_array($pattern)) {
                continue;
            }
            $text = trim(sanitize_textarea_field((string) ($pattern['text'] ?? '')));
            $id = sanitize_key((string) ($pattern['id'] ?? ''));
            if ($text === '' || $id === '') {
                continue;
            }
            daftarchi_sms_keep_pattern(array(
                'id' => $id,
                'title' => mb_substr(sanitize_text_field((string) ($pattern['title'] ?? $id)), 0, 80),
                'text' => mb_substr($text, 0, 400),
                'shop' => $shop,
                'shop_phone' => $shop_phone,
                'updated_at' => gmdate('c'),
            ));
        }
        return new WP_REST_Response(array('ok' => true, 'service' => 'daftarchi-sms-pattern'), 200);
    }
    if ($action !== 'send') {
        return new WP_Error('action', 'درخواست پیامک نامعتبر است', array('status' => 400));
    }
    $shop_phone = daftarchi_sms_phone($params['shop_phone'] ?? '');
    $pattern_id = sanitize_key((string) ($params['pattern'] ?? ''));
    $to = daftarchi_sms_phone($params['to'] ?? '');
    $body_id = daftarchi_sms_catalog_body($pattern_id);
    if ($body_id === '') {
        foreach (daftarchi_sms_patterns() as $row) {
        if (!is_array($row)) {
            continue;
        }
        if (daftarchi_sms_pattern_key($row['shop_phone'] ?? '', $row['id'] ?? '') === daftarchi_sms_pattern_key($shop_phone, $pattern_id)) {
            $body_id = preg_replace('/\D+/', '', (string) ($row['body_id'] ?? ''));
            break;
        }
        }
    }
    $panel = daftarchi_sms_panel();
    if ($body_id === '' || empty($panel['username']) || empty($panel['password'])) {
        return new WP_REST_Response(array(
            'ok' => true,
            'sent' => false,
            'detail' => 'الگو هنوز در ملی‌پیامک ثبت نشده',
        ), 200);
    }
    $args = array();
    foreach ((array) ($params['args'] ?? array()) as $arg) {
        $args[] = mb_substr(sanitize_text_field((string) $arg), 0, 80);
    }
    $response = wp_remote_post('https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber', array(
        'timeout' => 12,
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => wp_json_encode(array(
            'username' => (string) $panel['username'],
            'password' => (string) $panel['password'],
            'text' => implode(';', $args),
            'to' => $to,
            'bodyId' => (int) $body_id,
        )),
    ));
    if (is_wp_error($response)) {
        return new WP_REST_Response(array('ok' => true, 'sent' => false, 'detail' => 'ملی‌پیامک پاسخ نداد'), 200);
    }
    $decoded = json_decode(wp_remote_retrieve_body($response), true);
    $ret = is_array($decoded) ? (string) ($decoded['RetStatus'] ?? '') : '';
    if ((int) wp_remote_retrieve_response_code($response) < 400 && ($ret === '1' || $ret === 'Ok')) {
        return new WP_REST_Response(array('ok' => true, 'sent' => true, 'detail' => 'ملی‌پیامک'), 200);
    }
    $message = is_array($decoded) ? (string) ($decoded['StrRetStatus'] ?? 'ارسال الگو ناموفق بود') : 'ارسال الگو ناموفق بود';
    return new WP_REST_Response(array('ok' => true, 'sent' => false, 'detail' => mb_substr($message, 0, 180)), 200);
}

add_action('admin_post_daftarchi_sms_panel', 'daftarchi_sms_save_panel');
add_action('admin_post_daftarchi_sms_bodies', 'daftarchi_sms_save_bodies');
add_action('admin_post_daftarchi_sms_catalog', 'daftarchi_sms_save_catalog');

function daftarchi_sms_save_panel() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی ندارید');
    }
    check_admin_referer('daftarchi_sms_panel');
    $panel = daftarchi_sms_panel();
    $panel['username'] = sanitize_text_field((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($password !== '') {
        $panel['password'] = $password;
    }
    $panel['sender'] = preg_replace('/\D+/', '', (string) ($_POST['sender'] ?? ''));
    update_option('daftarchi_melipayamak', $panel, false);
    wp_safe_redirect(admin_url('admin.php?page=daftarchi-sms'));
    exit;
}

function daftarchi_sms_save_bodies() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی ندارید');
    }
    check_admin_referer('daftarchi_sms_bodies');
    $posted = $_POST['body_id'] ?? array();
    if (!is_array($posted)) {
        $posted = array();
    }
    $rows = daftarchi_sms_patterns();
    foreach ($rows as $index => $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = daftarchi_sms_pattern_key($row['shop_phone'] ?? '', $row['id'] ?? '');
        if (!array_key_exists($key, $posted)) {
            continue;
        }
        $rows[$index]['body_id'] = preg_replace('/\D+/', '', (string) $posted[$key]);
    }
    update_option('daftarchi_sms_patterns', $rows, false);
    wp_safe_redirect(admin_url('admin.php?page=daftarchi-sms'));
    exit;
}

function daftarchi_sms_save_catalog() {
    if (!current_user_can('manage_options')) {
        wp_die('دسترسی ندارید');
    }
    check_admin_referer('daftarchi_sms_catalog');
    $titles = $_POST['catalog_title'] ?? array();
    $texts = $_POST['catalog_text'] ?? array();
    $bodies = $_POST['catalog_body'] ?? array();
    $ids = $_POST['catalog_id'] ?? array();
    if (!is_array($titles) || !is_array($texts) || !is_array($bodies) || !is_array($ids)) {
        $titles = array();
        $texts = array();
        $bodies = array();
        $ids = array();
    }
    $saved = array();
    $used = array();
    $count = max(count($titles), count($texts), count($bodies), count($ids));
    for ($index = 0; $index < $count; $index++) {
        $title = mb_substr(sanitize_text_field((string) ($titles[$index] ?? '')), 0, 80);
        $text = mb_substr(trim(sanitize_textarea_field((string) ($texts[$index] ?? ''))), 0, 400);
        if ($title === '' || $text === '') {
            continue;
        }
        $id = sanitize_key((string) ($ids[$index] ?? ''));
        if ($id === '' || isset($used[$id])) {
            $id = 'c' . substr(md5($title . '|' . $text . '|' . $index), 0, 8);
        }
        $used[$id] = true;
        $saved[] = array(
            'id' => $id,
            'title' => $title,
            'text' => $text,
            'body_id' => preg_replace('/\D+/', '', (string) ($bodies[$index] ?? '')),
        );
    }
    update_option('daftarchi_sms_catalog', $saved, false);
    wp_safe_redirect(admin_url('admin.php?page=daftarchi-sms'));
    exit;
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
        'buyer_name' => mb_substr(sanitize_text_field((string) ($params['buyer_name'] ?? '')), 0, 80),
        'buyer_phone' => daftarchi_sms_phone($params['buyer_phone'] ?? ''),
        'invoice_code' => mb_substr(sanitize_text_field((string) ($params['invoice_code'] ?? '')), 0, 40),
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
    echo '<h2>اتصال ملی‌پیامک</h2>';
    echo '<p>نام کاربری و خط مشترک اینجاست. الگوهای آماده را همین‌جا بسازید تا در تنظیمات دفترچی فروشگاه دیده شوند و فروشگاه یکی را انتخاب کند. کد الگو را بعد از ساختن در ملی‌پیامک بنویسید.</p>';
    $panel = daftarchi_sms_panel();
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('daftarchi_sms_panel');
    echo '<input type="hidden" name="action" value="daftarchi_sms_panel">';
    echo '<table class="form-table"><tr><th>نام کاربری</th><td><input class="regular-text" name="username" value="' . esc_attr((string) ($panel['username'] ?? '')) . '"></td></tr>';
    echo '<tr><th>رمز</th><td><input class="regular-text" type="password" name="password" placeholder="' . (!empty($panel['password']) ? 'ذخیره شده؛ برای عوض کردن بنویسید' : '') . '" autocomplete="new-password"></td></tr>';
    echo '<tr><th>شماره خط</th><td><input class="regular-text" name="sender" value="' . esc_attr((string) ($panel['sender'] ?? '')) . '"></td></tr></table>';
    submit_button('ذخیره اتصال');
    echo '</form>';
    echo '<h2>الگوهای آماده</h2>';
    echo '<p>هر الگویی که اینجا ذخیره شود در پنل فروشگاه نمایش داده می‌شود تا برای خریدار یا مدیر یکی را انتخاب کند. متغیرها: {نام} {مبلغ} {فاکتور}. متن الگو برای ملی‌پیامک همان ستون کناری است.</p>';
    $catalog = daftarchi_sms_catalog();
    $blank = 0;
    while ($blank < 3) {
        $catalog[] = array('id' => '', 'title' => '', 'text' => '', 'body_id' => '');
        $blank++;
    }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('daftarchi_sms_catalog');
    echo '<input type="hidden" name="action" value="daftarchi_sms_catalog">';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>عنوان</th><th>متن برای فروشگاه</th><th>متن الگو برای ملی‌پیامک</th><th>کد الگو</th>';
    echo '</tr></thead><tbody>';
    foreach ($catalog as $row) {
        if (!is_array($row)) {
            continue;
        }
        $text = (string) ($row['text'] ?? '');
        echo '<tr>';
        echo '<td><input type="hidden" name="catalog_id[]" value="' . esc_attr((string) ($row['id'] ?? '')) . '">';
        echo '<input class="regular-text" name="catalog_title[]" value="' . esc_attr((string) ($row['title'] ?? '')) . '"></td>';
        echo '<td><textarea name="catalog_text[]" rows="3" class="large-text">' . esc_textarea($text) . '</textarea></td>';
        echo '<td>' . esc_html($text === '' ? '' : daftarchi_sms_pattern_vars($text)) . '</td>';
        echo '<td><input name="catalog_body[]" value="' . esc_attr((string) ($row['body_id'] ?? '')) . '"></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    submit_button('ذخیره الگوهای آماده');
    echo '</form>';
    echo '<h2>الگوهای فروشگاه</h2>';
    $patterns = array_reverse(daftarchi_sms_patterns());
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('daftarchi_sms_bodies');
    echo '<input type="hidden" name="action" value="daftarchi_sms_bodies">';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>فروشگاه</th><th>موبایل</th><th>عنوان</th><th>متن فروشگاه</th><th>متن الگو برای ملی‌پیامک</th><th>کد الگو</th>';
    echo '</tr></thead><tbody>';
    if (!$patterns) {
        echo '<tr><td colspan="6">هنوز الگویی از فروشگاه نرسیده است.</td></tr>';
    }
    foreach ($patterns as $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = daftarchi_sms_pattern_key($row['shop_phone'] ?? '', $row['id'] ?? '');
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['shop'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['shop_phone'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['title'] ?? '')) . '</td>';
        echo '<td>' . esc_html((string) ($row['text'] ?? '')) . '</td>';
        echo '<td>' . esc_html(daftarchi_sms_pattern_vars((string) ($row['text'] ?? ''))) . '</td>';
        echo '<td><input name="body_id[' . esc_attr($key) . ']" value="' . esc_attr((string) ($row['body_id'] ?? '')) . '"></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    submit_button('ذخیره کد الگوها');
    echo '</form>';
    echo '<h2>شارژ فروشگاه‌ها</h2>';
    echo '<p>فروشگاه پنل پیامک خودش را شارژ می‌کند. کارمزد هر تراکنش ۳ درصد است و جدا از مبلغ شارژ گرفته می‌شود.</p>';
    echo '<p>جمع شارژ: <strong>' . esc_html(number_format($credit)) . '</strong> تومان · جمع کارمزد شما: <strong>' . esc_html(number_format($fee)) . '</strong> تومان</p>';
    echo '<table class="widefat striped"><thead><tr>';
    echo '<th>فاکتور</th><th>مدیر فروشگاه</th><th>فروشگاه</th><th>موبایل فروشگاه</th><th>شارژ</th><th>کارمزد ۳٪</th><th>پرداخت</th><th>پیگیری</th><th>زمان</th>';
    echo '</tr></thead><tbody>';
    $shown = 0;
    foreach ($rows as $row) {
        if (!is_array($row) || ($row['status'] ?? '') !== 'paid') {
            continue;
        }
        $shown++;
        echo '<tr>';
        echo '<td>' . esc_html((string) ($row['invoice_code'] ?? '')) . '</td>';
        echo '<td>' . esc_html(trim((string) ($row['buyer_name'] ?? '') . ' ' . (string) ($row['buyer_phone'] ?? ''))) . '</td>';
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
        echo '<tr><td colspan="9">هنوز شارژی پرداخت نشده است.</td></tr>';
    }
    echo '</tbody></table></div>';
}
