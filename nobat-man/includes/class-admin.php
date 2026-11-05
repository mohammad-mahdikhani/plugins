<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_nobat_man_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_nobat_man_save_hours', array( __CLASS__, 'save_hours' ) );
		add_action( 'admin_post_nobat_man_save_specialists', array( __CLASS__, 'save_specialists' ) );
		add_action( 'admin_post_nobat_man_save_category', array( __CLASS__, 'save_category' ) );
		add_action( 'admin_post_nobat_man_delete_category', array( __CLASS__, 'delete_category' ) );
	}

	public static function menu() {
		add_menu_page(
			'نوبت من',
			'نوبت من',
			'manage_options',
			'nobat-man',
			array( __CLASS__, 'page_dashboard' ),
			'dashicons-calendar-alt',
			56
		);
		add_submenu_page( 'nobat-man', 'داشبورد', 'داشبورد', 'manage_options', 'nobat-man', array( __CLASS__, 'page_dashboard' ) );
		add_submenu_page( 'nobat-man', 'تنظیمات', 'تنظیمات', 'manage_options', 'nobat-man-settings', array( __CLASS__, 'page_settings' ) );
		add_submenu_page( 'nobat-man', 'ساعات کاری', 'ساعات کاری', 'manage_options', 'nobat-man-hours', array( __CLASS__, 'page_hours' ) );
		add_submenu_page( 'nobat-man', 'متخصصین', 'متخصصین', 'manage_options', 'nobat-man-specialists', array( __CLASS__, 'page_specialists' ) );
		add_submenu_page( 'nobat-man', 'دسته‌بندی‌ها', 'دسته‌بندی‌ها', 'manage_options', 'nobat-man-categories', array( __CLASS__, 'page_categories' ) );
		add_submenu_page( 'nobat-man', 'لایسنس', 'لایسنس', 'manage_options', 'nobat-man-license', array( __CLASS__, 'page_license' ) );
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, 'nobat-man' ) === false ) {
			return;
		}
		wp_enqueue_style( 'nobat-man-admin', NOBAT_MAN_URL . 'assets/admin/admin.css', array(), NOBAT_MAN_VERSION );
		wp_enqueue_script( 'nobat-man-admin', NOBAT_MAN_URL . 'assets/admin/admin.js', array(), NOBAT_MAN_VERSION, true );
	}

	public static function wrap_start( $title ) {
		echo '<div class="wrap nm-wrap" dir="rtl"><h1>' . esc_html( $title ) . ' <span class="nm-ver">v' . esc_html( NOBAT_MAN_VERSION ) . '</span></h1>';
		echo '<p class="nm-brand">سازنده: <a href="https://webakery.ir" target="_blank" rel="noopener">webakery.ir</a></p>';
		if ( isset( $_GET['nm_saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div>';
		}
		if ( isset( $_GET['nm_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['nm_error'] ) ) ) . '</p></div>';
		}
	}

	public static function wrap_end() {
		echo '</div>';
	}

	public static function page_dashboard() {
		self::wrap_start( 'نوبت من' );
		$bookings = nobat_man_bookings();
		$cats     = Nobat_Man_Categories::all();
		echo '<div class="nm-cards">';
		echo '<div class="nm-card"><strong>' . count( $bookings ) . '</strong><span>رزرو ثبت‌شده</span></div>';
		echo '<div class="nm-card"><strong>' . count( $cats ) . '</strong><span>دسته‌بندی</span></div>';
		echo '<div class="nm-card"><strong>' . count( nobat_man_specialists() ) . '</strong><span>متخصص</span></div>';
		echo '</div>';
		echo '<p>شورت‌کد فرم رزرو: <code>[nobat_man]</code></p>';
		echo '<table class="widefat striped"><thead><tr><th>کد</th><th>نام</th><th>تاریخ</th><th>ساعت</th><th>دسته</th></tr></thead><tbody>';
		if ( ! $bookings ) {
			echo '<tr><td colspan="5">هنوز رزروی ثبت نشده است.</td></tr>';
		}
		foreach ( array_reverse( $bookings ) as $b ) {
			echo '<tr><td>' . esc_html( $b['code'] ) . '</td><td>' . esc_html( $b['name'] ) . '</td><td>' . esc_html( $b['date'] ) . '</td><td>' . esc_html( $b['start'] ) . '</td><td>' . esc_html( $b['category_title'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
		self::wrap_end();
	}

	public static function page_settings() {
		self::wrap_start( 'تنظیمات قیمت و مدت' );
		$s = nobat_man_settings();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="nm-form">';
		wp_nonce_field( 'nobat_man_save_settings' );
		echo '<input type="hidden" name="action" value="nobat_man_save_settings" />';
		echo '<p><label>قیمت جلسه (تومان)</label><input type="number" name="price" value="' . esc_attr( $s['price'] ) . '" /></p>';
		echo '<p><label>مدت جلسه (دقیقه، ۵ تا ۳۰۰)</label><input type="number" name="duration" min="5" max="300" value="' . esc_attr( $s['duration'] ) . '" /></p>';
		echo '<p><label>تعطیلات شمسی (هر خط یک تاریخ مثل 1404/01/01)</label><textarea name="holidays" rows="4">' . esc_textarea( $s['holidays'] ) . '</textarea></p>';
		echo '<p><label>متن تشکر</label><textarea name="thank_you" rows="4">' . esc_textarea( $s['thank_you'] ) . '</textarea></p>';
		echo '<p class="description">{booking_code} {jalali_date} {start_time} {end_time} {customer_name} {price} {site_url} {invoice_no}</p>';
		submit_button( 'ذخیره تنظیمات' );
		echo '</form>';
		self::wrap_end();
	}

	public static function page_hours() {
		self::wrap_start( 'ساعات کاری' );
		$s    = nobat_man_settings();
		$days = array( 'شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه' );
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="nm-form">';
		wp_nonce_field( 'nobat_man_save_hours' );
		echo '<input type="hidden" name="action" value="nobat_man_save_hours" />';
		echo '<p>روزهای فعال:</p><div class="nm-checks">';
		foreach ( $days as $i => $label ) {
			$checked = in_array( $i, (array) $s['working_days'], true ) ? ' checked' : '';
			echo '<label><input type="checkbox" name="working_days[]" value="' . esc_attr( $i ) . '"' . $checked . ' /> ' . esc_html( $label ) . '</label>';
		}
		echo '</div>';
		echo '<p><label>شروع</label><input type="time" name="slot_start" value="' . esc_attr( $s['slot_start'] ) . '" /></p>';
		echo '<p><label>پایان</label><input type="time" name="slot_end" value="' . esc_attr( $s['slot_end'] ) . '" /></p>';
		submit_button( 'ذخیره ساعات کاری' );
		echo '</form>';
		self::wrap_end();
	}

	public static function page_specialists() {
		self::wrap_start( 'متخصصین' );
		$items = nobat_man_specialists();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="nm-form">';
		wp_nonce_field( 'nobat_man_save_specialists' );
		echo '<input type="hidden" name="action" value="nobat_man_save_specialists" />';
		echo '<div id="nm-specialists">';
		if ( ! $items ) {
			$items = array( array( 'id' => '', 'name' => '', 'bio' => '' ) );
		}
		foreach ( $items as $i => $sp ) {
			echo '<div class="nm-repeat"><input type="hidden" name="sp_id[]" value="' . esc_attr( $sp['id'] ) . '" />';
			echo '<p><label>نام</label><input type="text" name="sp_name[]" value="' . esc_attr( $sp['name'] ) . '" /></p>';
			echo '<p><label>توضیح</label><input type="text" name="sp_bio[]" value="' . esc_attr( $sp['bio'] ) . '" /></p></div>';
		}
		echo '</div>';
		echo '<p><button type="button" class="button" id="nm-add-sp">افزودن متخصص</button></p>';
		submit_button( 'ذخیره متخصصین' );
		echo '</form>';
		self::wrap_end();
	}

	public static function page_categories() {
		self::wrap_start( 'دسته‌بندی مشکل' );
		$edit_id = isset( $_GET['edit'] ) ? sanitize_key( wp_unslash( $_GET['edit'] ) ) : '';
		$editing = $edit_id ? Nobat_Man_Categories::get( $edit_id ) : null;
		$items   = Nobat_Man_Categories::all();

		echo '<div class="nm-split">';
		echo '<div class="nm-panel">';
		echo '<h2>' . ( $editing ? 'ویرایش دسته‌بندی' : 'افزودن دسته‌بندی' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="nm-form">';
		wp_nonce_field( 'nobat_man_save_category' );
		echo '<input type="hidden" name="action" value="nobat_man_save_category" />';
		echo '<input type="hidden" name="id" value="' . esc_attr( $editing ? $editing['id'] : '' ) . '" />';
		echo '<p><label>عنوان دسته</label><input type="text" name="title" required value="' . esc_attr( $editing ? $editing['title'] : '' ) . '" /></p>';
		echo '<p><label>توضیح کوتاه</label><textarea name="description" rows="3">' . esc_textarea( $editing ? $editing['description'] : '' ) . '</textarea></p>';
		$qtext = '';
		if ( $editing && ! empty( $editing['questions'] ) ) {
			$labels = array();
			foreach ( $editing['questions'] as $q ) {
				$labels[] = $q['label'];
			}
			$qtext = implode( "\n", $labels );
		}
		echo '<p><label>سوالات این دسته (هر خط یک سوال)</label><textarea name="questions" rows="6" placeholder="مدت مشکل؟">' . esc_textarea( $qtext ) . '</textarea></p>';
		submit_button( $editing ? 'به‌روزرسانی دسته' : 'افزودن دسته' );
		if ( $editing ) {
			echo ' <a class="button" href="' . esc_url( admin_url( 'admin.php?page=nobat-man-categories' ) ) . '">انصراف</a>';
		}
		echo '</form></div>';

		echo '<div class="nm-panel"><h2>فهرست دسته‌ها</h2>';
		if ( ! $items ) {
			echo '<p>هنوز دسته‌ای نیست. از فرم سمت راست اضافه کنید.</p>';
		}
		echo '<table class="widefat striped"><thead><tr><th>عنوان</th><th>سوالات</th><th></th></tr></thead><tbody>';
		foreach ( $items as $row ) {
			$edit = admin_url( 'admin.php?page=nobat-man-categories&edit=' . rawurlencode( $row['id'] ) );
			$del  = wp_nonce_url( admin_url( 'admin-post.php?action=nobat_man_delete_category&id=' . rawurlencode( $row['id'] ) ), 'nobat_man_delete_category' );
			echo '<tr><td><strong>' . esc_html( $row['title'] ) . '</strong><br /><span class="description">' . esc_html( $row['description'] ) . '</span></td>';
			echo '<td>' . esc_html( count( $row['questions'] ) ) . '</td>';
			echo '<td><a class="button button-small" href="' . esc_url( $edit ) . '">ویرایش</a> ';
			echo '<a class="button button-small" href="' . esc_url( $del ) . '" onclick="return confirm(\'حذف شود؟\');">حذف</a></td></tr>';
		}
		echo '</tbody></table></div></div>';
		self::wrap_end();
	}

	public static function page_license() {
		self::wrap_start( 'لایسنس نسخه پرو' );
		$s = nobat_man_settings();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="nm-form">';
		wp_nonce_field( 'nobat_man_save_settings' );
		echo '<input type="hidden" name="action" value="nobat_man_save_settings" />';
		echo '<p>نسخه پرو ۵۹۹,۰۰۰ تومان — اتصال به webakery.ir</p>';
		echo '<p><label>کلید لایسنس</label><input type="text" name="pro_license" value="' . esc_attr( $s['pro_license'] ) . '" /></p>';
		submit_button( 'ذخیره لایسنس' );
		echo '</form>';
		self::wrap_end();
	}

	public static function save_settings() {
		self::guard( 'nobat_man_save_settings' );
		$s                 = nobat_man_settings();
		$s['price']        = isset( $_POST['price'] ) ? absint( $_POST['price'] ) : $s['price'];
		$s['duration']     = isset( $_POST['duration'] ) ? max( 5, min( 300, absint( $_POST['duration'] ) ) ) : $s['duration'];
		$s['holidays']     = isset( $_POST['holidays'] ) ? sanitize_textarea_field( wp_unslash( $_POST['holidays'] ) ) : $s['holidays'];
		$s['thank_you']    = isset( $_POST['thank_you'] ) ? wp_kses_post( wp_unslash( $_POST['thank_you'] ) ) : $s['thank_you'];
		if ( isset( $_POST['pro_license'] ) ) {
			$s['pro_license'] = sanitize_text_field( wp_unslash( $_POST['pro_license'] ) );
		}
		update_option( 'nobat_man_settings', $s );
		self::redirect( isset( $_POST['pro_license'] ) ? 'nobat-man-license' : 'nobat-man-settings' );
	}

	public static function save_hours() {
		self::guard( 'nobat_man_save_hours' );
		$s                 = nobat_man_settings();
		$days              = isset( $_POST['working_days'] ) ? array_map( 'absint', (array) $_POST['working_days'] ) : array();
		$s['working_days'] = $days;
		$s['slot_start']   = isset( $_POST['slot_start'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_start'] ) ) : $s['slot_start'];
		$s['slot_end']     = isset( $_POST['slot_end'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_end'] ) ) : $s['slot_end'];
		update_option( 'nobat_man_settings', $s );
		self::redirect( 'nobat-man-hours' );
	}

	public static function save_specialists() {
		self::guard( 'nobat_man_save_specialists' );
		$names = isset( $_POST['sp_name'] ) ? (array) $_POST['sp_name'] : array();
		$bios  = isset( $_POST['sp_bio'] ) ? (array) $_POST['sp_bio'] : array();
		$ids   = isset( $_POST['sp_id'] ) ? (array) $_POST['sp_id'] : array();
		$out   = array();
		foreach ( $names as $i => $name ) {
			$name = sanitize_text_field( wp_unslash( $name ) );
			if ( $name === '' ) {
				continue;
			}
			$id = isset( $ids[ $i ] ) ? sanitize_key( $ids[ $i ] ) : '';
			if ( $id === '' ) {
				$id = 'sp_' . wp_generate_password( 6, false, false );
			}
			$out[] = array(
				'id'   => $id,
				'name' => $name,
				'bio'  => isset( $bios[ $i ] ) ? sanitize_text_field( wp_unslash( $bios[ $i ] ) ) : '',
			);
		}
		update_option( 'nobat_man_specialists', $out, false );
		self::redirect( 'nobat-man-specialists' );
	}

	public static function save_category() {
		self::guard( 'nobat_man_save_category' );
		$result = Nobat_Man_Categories::upsert(
			array(
				'id'          => isset( $_POST['id'] ) ? sanitize_key( wp_unslash( $_POST['id'] ) ) : '',
				'title'       => isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '',
				'description' => isset( $_POST['description'] ) ? wp_unslash( $_POST['description'] ) : '',
				'questions'   => isset( $_POST['questions'] ) ? wp_unslash( $_POST['questions'] ) : '',
			)
		);
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'nobat-man-categories', 'nm_error' => rawurlencode( $result->get_error_message() ) ), admin_url( 'admin.php' ) ) );
			exit;
		}
		self::redirect( 'nobat-man-categories' );
	}

	public static function delete_category() {
		self::guard( 'nobat_man_delete_category', 'GET' );
		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';
		if ( $id ) {
			Nobat_Man_Categories::delete( $id );
		}
		self::redirect( 'nobat-man-categories' );
	}

	private static function guard( $action, $method = 'POST' ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'forbidden' );
		}
		check_admin_referer( $action );
	}

	private static function redirect( $page ) {
		wp_safe_redirect( add_query_arg( array( 'page' => $page, 'nm_saved' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
