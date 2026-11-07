add_action('admin_menu', 'daftarchi_release_menu');
add_action('admin_post_daftarchi_release_save', 'daftarchi_release_save');
add_action('rest_api_init', 'daftarchi_release_route');
add_action('template_redirect', 'daftarchi_release_legacy_json');

function daftarchi_release_channels() {
    return array(
        'customer' => 'فروشگاه عمومی',
        'rst' => 'رستوران',
    );
}

function daftarchi_release_dir() {
    $upload = wp_upload_dir();
    $dir = trailingslashit($upload['basedir']) . 'daftarchi-releases';
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    $guard = $dir . '/index.html';
    if (!is_file($guard)) {
        file_put_contents($guard, '');
    }
    return $dir;
}

function daftarchi_release_baseurl() {
    $upload = wp_upload_dir();
    return set_url_scheme(trailingslashit($upload['baseurl']) . 'daftarchi-releases', 'https');
}

function daftarchi_release_manifest_path($channel) {
    $channel = isset(daftarchi_release_channels()[$channel]) ? $channel : 'customer';
    return daftarchi_release_dir() . '/' . $channel . '.json';
}

function daftarchi_release_read($channel) {
    $path = daftarchi_release_manifest_path($channel);
    if (!is_file($path)) {
        return array();
    }
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : array();
}

function daftarchi_release_menu() {
    add_menu_page(
        'آپدیت دفترچی',
        'آپدیت دفترچی',
        'manage_options',
        'daftarchi-release',
        'daftarchi_release_page',
        'dashicons-update',
        58
    );
}

function daftarchi_release_store_file($field, $channel, $version, $suffix, $allowed) {
    if (empty($_FILES[$field]['name'])) {
        return '';
    }
    $file = $_FILES[$field];
    if (!empty($file['error'])) {
        return new WP_Error('upload', 'آپلود فایل کامل نشد');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return new WP_Error('type', 'پسوند فایل مجاز نیست');
    }
    $name = $channel . '-' . $version . '-' . $suffix . '.' . $ext;
    $dest = daftarchi_release_dir() . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return new WP_Error('move', 'فایل روی سایت ذخیره نشد');
    }
    return daftarchi_release_baseurl() . '/' . rawurlencode($name);
}

function daftarchi_release_publish($channel, $version, $notes) {
    $channels = daftarchi_release_channels();
    if (!isset($channels[$channel])) {
        return new WP_Error('channel', 'کانال نامعتبر است');
    }
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
        return new WP_Error('version', 'شماره نسخه نادرست است');
    }
    $notes = trim(preg_replace('/\s+/u', ' ', $notes));
    if ($notes === '') {
        return new WP_Error('notes', 'پیام آپدیت خالی است');
    }
    $notes = function_exists('mb_substr') ? mb_substr($notes, 0, 300) : substr($notes, 0, 300);
    $current = daftarchi_release_read($channel);
    $saved = array(
        'app' => daftarchi_release_store_file('app_file', $channel, $version, 'app', array('zip')),
        'windows' => daftarchi_release_store_file('windows_file', $channel, $version, 'windows', array('zip')),
        'mac' => daftarchi_release_store_file('mac_file', $channel, $version, 'mac', array('dmg', 'zip')),
        'linux' => daftarchi_release_store_file('linux_file', $channel, $version, 'linux', array('zip')),
    );
    foreach ($saved as $item) {
        if (is_wp_error($item)) {
            return $item;
        }
    }
    $app = $saved['app'] !== '' ? $saved['app'] : (isset($current['app_url']) ? $current['app_url'] : '');
    $windows = $saved['windows'] !== '' ? $saved['windows'] : (isset($current['full_url']) ? $current['full_url'] : '');
    $mac = $saved['mac'] !== '' ? $saved['mac'] : (isset($current['mac_url']) ? $current['mac_url'] : '');
    $linux = $saved['linux'] !== '' ? $saved['linux'] : (isset($current['linux_url']) ? $current['linux_url'] : '');
    $download = $windows !== '' ? $windows : $app;
    if ($download === '' && $mac !== '') {
        $download = $mac;
    }
    if ($download === '' && $linux !== '') {
        $download = $linux;
    }
    if ($download === '') {
        return new WP_Error('file', 'حداقل یک فایل نصب لازم است');
    }
    $manifest = array(
        'version' => $version,
        'channel' => $channel,
        'notes' => $notes,
        'url' => $download,
        'app_url' => $app,
        'full_url' => $windows,
        'mac_url' => $mac,
        'linux_url' => $linux,
    );
    file_put_contents(
        daftarchi_release_manifest_path($channel),
        wp_json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
    return $manifest;
}

function daftarchi_release_save() {
    if (!current_user_can('manage_options')) {
        wp_die('اجازه این کار را ندارید');
    }
    check_admin_referer('daftarchi_release_save');
    $channel = isset($_POST['channel']) ? sanitize_key(wp_unslash($_POST['channel'])) : 'customer';
    $version = isset($_POST['version']) ? trim(sanitize_text_field(wp_unslash($_POST['version']))) : '';
    $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
    $result = daftarchi_release_publish($channel, $version, $notes);
    if (is_wp_error($result)) {
        $code = $result->get_error_code();
        $flag = in_array($code, array('version', 'notes'), true) ? $code : 'file';
        wp_safe_redirect(admin_url('admin.php?page=daftarchi-release&daftarchi_error=' . $flag));
        exit;
    }
    wp_safe_redirect(admin_url('admin.php?page=daftarchi-release&daftarchi_saved=1&channel=' . $channel));
    exit;
}

function daftarchi_release_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $channels = daftarchi_release_channels();
    $error = isset($_GET['daftarchi_error']) ? sanitize_key(wp_unslash($_GET['daftarchi_error'])) : '';
    $saved = isset($_GET['daftarchi_saved']);
    echo '<div class="wrap" style="max-width:760px">';
    echo '<h1>آپدیت دفترچی</h1>';
    echo '<p>نسخه و پیام را همین‌جا بگذارید. لازم نیست وارد هاست شوید. مشتری داخل برنامه همان پیام را می‌بیند.</p>';
    if ($saved) {
        echo '<div class="notice notice-success"><p>نسخه منتشر شد. برنامه مشتری با باز شدن دوباره، پیام را نشان می‌دهد.</p></div>';
    }
    if ($error === 'version') {
        echo '<div class="notice notice-error"><p>شماره نسخه را مثل ۱.۱.۱۴ با رقم انگلیسی بنویسید.</p></div>';
    }
    if ($error === 'notes') {
        echo '<div class="notice notice-error"><p>پیام آپدیت را بنویسید تا مشتری بداند چه چیزی عوض شده است.</p></div>';
    }
    if ($error === 'file') {
        echo '<div class="notice notice-error"><p>حداقل یک فایل نصب لازم است. زیپ داخل برنامه فقط zip است.</p></div>';
    }
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
    wp_nonce_field('daftarchi_release_save');
    echo '<input type="hidden" name="action" value="daftarchi_release_save">';
    echo '<table class="form-table"><tbody>';
    echo '<tr><th>کانال</th><td><select name="channel">';
    foreach ($channels as $id => $label) {
        echo '<option value="' . esc_attr($id) . '">' . esc_html($label) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th>شماره نسخه</th><td><input name="version" required pattern="\\d+\\.\\d+\\.\\d+" placeholder="1.1.14"></td></tr>';
    echo '<tr><th>پیام برای مشتری</th><td><textarea name="notes" rows="4" required style="width:100%" placeholder="چه چیزی در این نسخه عوض شده است"></textarea></td></tr>';
    echo '<tr><th>زیپ داخل برنامه</th><td><input type="file" name="app_file" accept=".zip"><p class="description">فایل کوچک پنل که app.py داخلش است. دکمه دریافت و نصب همین را می‌گیرد. اگر خالی بماند، فایل قبلی همین کانال می‌ماند.</p></td></tr>';
    echo '<tr><th>ویندوز</th><td><input type="file" name="windows_file" accept=".zip"></td></tr>';
    echo '<tr><th>مک</th><td><input type="file" name="mac_file" accept=".dmg,.zip"></td></tr>';
    echo '<tr><th>لینوکس</th><td><input type="file" name="linux_file" accept=".zip"></td></tr>';
    echo '</tbody></table>';
    submit_button('انتشار آپدیت');
    echo '</form>';
    echo '<h2>آخرین انتشار</h2>';
    foreach ($channels as $id => $label) {
        $manifest = daftarchi_release_read($id);
        echo '<h3>' . esc_html($label) . '</h3>';
        if (!$manifest) {
            echo '<p>هنوز نسخه‌ای این‌جا منتشر نشده است.</p>';
            continue;
        }
        echo '<p>نسخه <strong>' . esc_html($manifest['version']) . '</strong><br>' . esc_html($manifest['notes']) . '</p>';
    }
    echo '</div>';
}

function daftarchi_release_route() {
    register_rest_route('daftarchi/v1', '/version', array(
        array(
            'methods' => 'GET',
            'permission_callback' => '__return_true',
            'callback' => 'daftarchi_release_rest',
        ),
        array(
            'methods' => 'POST',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_release_rest_publish',
        ),
        array(
            'methods' => 'DELETE',
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
            'callback' => 'daftarchi_release_rest_delete',
        ),
    ));
}

function daftarchi_release_rest_publish($request) {
    $channel = sanitize_key((string) $request->get_param('channel'));
    $version = trim(sanitize_text_field((string) $request->get_param('version')));
    $notes = sanitize_textarea_field((string) $request->get_param('notes'));
    $result = daftarchi_release_publish($channel, $version, $notes);
    if (is_wp_error($result)) {
        $result->add_data(array('status' => 400));
        return $result;
    }
    return new WP_REST_Response($result, 200);
}

function daftarchi_release_rest_delete($request) {
    $channel = sanitize_key((string) $request->get_param('channel'));
    if (!isset(daftarchi_release_channels()[$channel])) {
        return new WP_Error('channel', 'کانال نامعتبر است', array('status' => 400));
    }
    $manifest = daftarchi_release_read($channel);
    $dir = realpath(daftarchi_release_dir());
    foreach (array('url', 'app_url', 'full_url', 'mac_url', 'linux_url') as $key) {
        $url = isset($manifest[$key]) ? (string) $manifest[$key] : '';
        $name = $url ? urldecode(basename(parse_url($url, PHP_URL_PATH))) : '';
        if ($name === '' || $dir === false) {
            continue;
        }
        $file = $dir . DIRECTORY_SEPARATOR . $name;
        $real = realpath($file);
        if ($real && strpos($real, $dir . DIRECTORY_SEPARATOR) === 0 && is_file($real)) {
            unlink($real);
        }
    }
    $path = daftarchi_release_manifest_path($channel);
    if (is_file($path)) {
        unlink($path);
    }
    return new WP_REST_Response(array('ok' => true), 200);
}

function daftarchi_release_rest($request) {
    $channel = sanitize_key((string) $request->get_param('channel'));
    if (!isset(daftarchi_release_channels()[$channel])) {
        $channel = 'customer';
    }
    $manifest = daftarchi_release_read($channel);
    if (!$manifest || empty($manifest['version'])) {
        return new WP_REST_Response(array('version' => ''), 404);
    }
    return new WP_REST_Response($manifest, 200);
}

function daftarchi_release_legacy_json() {
    $path = isset($_SERVER['REQUEST_URI']) ? wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
    if ($path !== '/daftarchi/version.json') {
        return;
    }
    $manifest = daftarchi_release_read('customer');
    status_header($manifest ? 200 : 404);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo $manifest ? wp_json_encode($manifest, JSON_UNESCAPED_UNICODE) : '{"version":""}';
    exit;
}
