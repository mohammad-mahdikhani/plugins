<?php
/**
 * پیام آپدیت مشتری، بدون ورود به هاست.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * داده نسخه جاری.
 *
 * @return array<string,mixed>
 */
function moon_skin_release_get() {
	$data = get_option('moon_skin_release', array());
	if (!is_array($data)) {
		$data = array();
	}
	return array_merge(array(
		'on'       => 0,
		'version'  => '',
		'title'    => '',
		'message'  => '',
		'file_id'  => 0,
		'updated'  => 0,
	), $data);
}

/**
 * ذخیره نسخه و پاک کردن کش اگر افزونه کش باشد.
 *
 * @param array<string,mixed> $input   Fields.
 * @param int|null            $file_id Attachment id, or null to keep.
 * @return array<string,mixed>
 */
function moon_skin_release_store($input, $file_id = null) {
	$prev = moon_skin_release_get();
	$next = array(
		'on'       => empty($input['on']) ? 0 : 1,
		'version'  => sanitize_text_field(isset($input['version']) ? $input['version'] : ''),
		'title'    => sanitize_text_field(isset($input['title']) ? $input['title'] : ''),
		'message'  => sanitize_textarea_field(isset($input['message']) ? $input['message'] : ''),
		'file_id'  => null === $file_id ? (int) $prev['file_id'] : (int) $file_id,
		'updated'  => time(),
	);
	if (function_exists('mb_substr')) {
		$next['version'] = mb_substr($next['version'], 0, 40);
		$next['title']   = mb_substr($next['title'], 0, 120);
		$next['message'] = mb_substr($next['message'], 0, 600);
	}
	if (!$next['title'] && !$next['message'] && !$next['file_id']) {
		$next['on'] = 0;
	}
	if (!empty($prev['updated']) && ($prev['title'] || $prev['message'] || $prev['file_id'])) {
		$log = get_option('moon_skin_release_log', array());
		if (!is_array($log)) {
			$log = array();
		}
		array_unshift($log, $prev);
		update_option('moon_skin_release_log', array_slice($log, 0, 12), false);
	}
	update_option('moon_skin_release', $next, false);
	if (function_exists('rocket_clean_domain')) {
		rocket_clean_domain();
	}
	if (function_exists('rocket_clean_minify')) {
		rocket_clean_minify();
	}
	return $next;
}

/**
 * نوار بالای سایت.
 */
function moon_skin_release_banner() {
	if (is_admin()) {
		return;
	}
	if (function_exists('is_checkout') && is_checkout()) {
		return;
	}
	$data = moon_skin_release_get();
	if (empty($data['on'])) {
		return;
	}
	if (!$data['title'] && !$data['message'] && !(int) $data['file_id']) {
		return;
	}
	$file = (int) $data['file_id'] ? wp_get_attachment_url((int) $data['file_id']) : '';
	echo '<aside class="moon-release" data-id="' . esc_attr((string) $data['updated']) . '" role="status">';
	echo '<div class="moon-release-copy">';
	if ($data['version'] !== '') {
		echo '<em>' . esc_html(sprintf(/* translators: version label */ __('نسخه %s', 'moon-skin'), $data['version'])) . '</em>';
	}
	if ($data['title'] !== '') {
		echo '<b>' . esc_html($data['title']) . '</b>';
	}
	if ($data['message'] !== '') {
		echo '<span>' . nl2br(esc_html($data['message'])) . '</span>';
	}
	echo '</div>';
	if ($file) {
		echo '<a class="moon-release-file" href="' . esc_url($file) . '">' . esc_html__('مشاهده نسخه', 'moon-skin') . '</a>';
	}
	echo '<button type="button" class="moon-release-x" aria-label="' . esc_attr__('بستن', 'moon-skin') . '">×</button></aside>';
}

/**
 * صفحه پیشخوان.
 */
function moon_skin_release_page() {
	if (!current_user_can('edit_theme_options')) {
		return;
	}
	$data   = moon_skin_release_get();
	$log    = get_option('moon_skin_release_log', array());
	$notice = get_transient('moon_skin_release_notice_' . get_current_user_id());
	if ($notice) {
		delete_transient('moon_skin_release_notice_' . get_current_user_id());
	}
	$file = (int) $data['file_id'] ? wp_get_attachment_url((int) $data['file_id']) : '';
	echo '<div class="wrap" dir="rtl" style="max-width:760px">';
	echo '<h1>' . esc_html__('آپدیت مشتریان', 'moon-skin') . '</h1>';
	echo '<p>' . esc_html__('نسخه و پیام را همین‌جا بگذارید. مشتری آن را بالای سایت می‌بیند. فایل از همین صفحه آپلود می‌شود.', 'moon-skin') . '</p>';
	if (is_array($notice) && !empty($notice['text'])) {
		echo '<div class="notice ' . esc_attr(!empty($notice['ok']) ? 'notice-success' : 'notice-error') . '"><p>' . esc_html($notice['text']) . '</p></div>';
	}
	echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
	echo '<input type="hidden" name="action" value="moon_skin_release_save">';
	wp_nonce_field('moon_skin_release_save');
	echo '<table class="form-table"><tbody>';
	echo '<tr><th scope="row">' . esc_html__('نمایش', 'moon-skin') . '</th><td><label><input type="checkbox" name="moon_skin_release_on" value="1"' . (!empty($data['on']) ? ' checked' : '') . '> ' . esc_html__('پیام بالای سایت نشان داده شود', 'moon-skin') . '</label></td></tr>';
	echo '<tr><th scope="row"><label for="moon_skin_release_version">' . esc_html__('شماره نسخه', 'moon-skin') . '</label></th><td><input class="regular-text" id="moon_skin_release_version" name="moon_skin_release_version" value="' . esc_attr($data['version']) . '"></td></tr>';
	echo '<tr><th scope="row"><label for="moon_skin_release_title">' . esc_html__('عنوان', 'moon-skin') . '</label></th><td><input class="regular-text" id="moon_skin_release_title" name="moon_skin_release_title" value="' . esc_attr($data['title']) . '"></td></tr>';
	echo '<tr><th scope="row"><label for="moon_skin_release_message">' . esc_html__('پیام', 'moon-skin') . '</label></th><td><textarea class="large-text" rows="5" id="moon_skin_release_message" name="moon_skin_release_message">' . esc_textarea($data['message']) . '</textarea></td></tr>';
	echo '<tr><th scope="row">' . esc_html__('فایل', 'moon-skin') . '</th><td><input type="file" name="moon_skin_release_file" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.zip">';
	if ($file) {
		echo '<p><a href="' . esc_url($file) . '">' . esc_html(basename((string) $file)) . '</a></p>';
		echo '<label><input type="checkbox" name="moon_skin_release_clear" value="1"> ' . esc_html__('حذف فایل فعلی', 'moon-skin') . '</label>';
	}
	echo '</td></tr></tbody></table>';
	submit_button(__('ذخیره', 'moon-skin'));
	echo '</form>';
	if (is_array($log) && $log) {
		echo '<h2>' . esc_html__('نسخه‌های قبلی', 'moon-skin') . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__('زمان', 'moon-skin') . '</th><th>' . esc_html__('نسخه', 'moon-skin') . '</th><th>' . esc_html__('پیام', 'moon-skin') . '</th></tr></thead><tbody>';
		foreach ($log as $row) {
			if (!is_array($row)) {
				continue;
			}
			$when = !empty($row['updated']) ? wp_date('Y/m/d H:i', (int) $row['updated']) : '';
			$text = trim((isset($row['title']) ? $row['title'] : '') . ' ' . (isset($row['message']) ? $row['message'] : ''));
			echo '<tr><td>' . esc_html($when) . '</td><td>' . esc_html(isset($row['version']) ? $row['version'] : '') . '</td><td>' . esc_html($text) . '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}

/**
 * ذخیره فرم پیشخوان.
 */
function moon_skin_release_handle_save() {
	if (!current_user_can('edit_theme_options')) {
		wp_die(esc_html__('اجازه این کار را ندارید.', 'moon-skin'));
	}
	check_admin_referer('moon_skin_release_save');
	$file_id = null;
	$error   = '';
	if (!empty($_POST['moon_skin_release_clear'])) {
		$file_id = 0;
	}
	if (!empty($_FILES['moon_skin_release_file']['name'])) {
		if (!current_user_can('upload_files')) {
			$error = __('اجازه آپلود فایل را ندارید.', 'moon-skin');
		} else {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$id = media_handle_upload('moon_skin_release_file', 0);
			if (is_wp_error($id)) {
				$error = $id->get_error_message();
			} else {
				$file_id = (int) $id;
			}
		}
	}
	moon_skin_release_store(array(
		'on'      => empty($_POST['moon_skin_release_on']) ? 0 : 1,
		'version' => isset($_POST['moon_skin_release_version']) ? wp_unslash($_POST['moon_skin_release_version']) : '',
		'title'   => isset($_POST['moon_skin_release_title']) ? wp_unslash($_POST['moon_skin_release_title']) : '',
		'message' => isset($_POST['moon_skin_release_message']) ? wp_unslash($_POST['moon_skin_release_message']) : '',
	), $file_id);
	$text = $error
		? sprintf(/* translators: upload error */ __('متن ذخیره شد، ولی فایل آپلود نشد: %s', 'moon-skin'), $error)
		: __('ذخیره شد. اگر نمایش روشن باشد، مشتری بالای سایت این پیام را می‌بیند.', 'moon-skin');
	set_transient('moon_skin_release_notice_' . get_current_user_id(), array('ok' => $error === '', 'text' => $text), 60);
	wp_safe_redirect(admin_url('admin.php?page=moon-skin-release'));
	exit;
}

add_action('admin_menu', 'moon_skin_release_menu');

/**
 * منوی پیشخوان.
 */
function moon_skin_release_menu() {
	add_menu_page(
		__('آپدیت مشتریان', 'moon-skin'),
		__('آپدیت مشتریان', 'moon-skin'),
		'edit_theme_options',
		'moon-skin-release',
		'moon_skin_release_page',
		'dashicons-update',
		59
	);
}

add_action('admin_post_moon_skin_release_save', 'moon_skin_release_handle_save');
