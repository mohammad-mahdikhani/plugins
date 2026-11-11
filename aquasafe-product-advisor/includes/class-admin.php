<?php
class AS_Advisor_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'product_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save_product' ) );
		add_action( 'admin_init', array( __CLASS__, 'save_settings' ) );
	}

	public static function menu() {
		add_submenu_page(
			'woocommerce',
			'دستیار انتخاب محصول',
			'دستیار انتخاب',
			'manage_woocommerce',
			'as-product-advisor',
			array( __CLASS__, 'page' )
		);
	}

	public static function page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$compat = AS_Advisor_Catalog::compat();
		$stats  = array(
			'start'          => (int) get_option( 'as_advisor_stat_start', 0 ),
			'complete'       => (int) get_option( 'as_advisor_stat_complete', 0 ),
			'no_result'      => (int) get_option( 'as_advisor_stat_no_result', 0 ),
			'compat_unknown' => (int) get_option( 'as_advisor_stat_compat_unknown', 0 ),
		);
		$incomplete = array();
		foreach ( AS_Advisor_Catalog::all() as $p ) {
			if ( ! empty( $p['incomplete'] ) ) {
				$incomplete[] = $p;
			}
		}
		$compat_json = wp_json_encode( $compat, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		echo '<div class="wrap"><h1>دستیار انتخاب محصول</h1>';
		echo '<p>شورت‌کد: <code>[as_product_advisor]</code></p>';
		echo '<h2>آمار</h2><p>شروع: ' . esc_html( (string) $stats['start'] ) . ' · تکمیل: ' . esc_html( (string) $stats['complete'] ) . ' · بدون نتیجه: ' . esc_html( (string) $stats['no_result'] ) . ' · سازگاری نامشخص: ' . esc_html( (string) $stats['compat_unknown'] ) . '</p>';
		echo '<h2>محصولات با داده ناقص</h2>';
		if ( ! $incomplete ) {
			echo '<p>موردی نیست.</p>';
		} else {
			echo '<ul>';
			foreach ( array_slice( $incomplete, 0, 40 ) as $p ) {
				echo '<li><a href="' . esc_url( get_edit_post_link( $p['id'] ) ) . '">' . esc_html( $p['name'] ) . '</a> (' . esc_html( $p['kind'] ) . ')</li>';
			}
			echo '</ul>';
		}
		echo '<form method="post">';
		wp_nonce_field( 'as_advisor_settings' );
		echo '<h2>سازگاری دستگاه و فیلتر/قطعه</h2>';
		echo '<p>هر خط: شناسه دستگاه، شناسه قطعه، وضعیت confirmed یا unconfirmed. مثال: <code>821,1376,confirmed</code></p>';
		$lines = '';
		foreach ( $compat as $row ) {
			$lines .= (int) $row['device_id'] . ',' . (int) $row['part_id'] . ',' . esc_attr( $row['status'] ) . "\n";
		}
		echo '<textarea name="as_advisor_compat_text" rows="10" class="large-text">' . esc_textarea( $lines ) . '</textarea>';
		echo '<h2>متن شروع و مسیرها</h2>';
		echo '<textarea name="as_advisor_questions_json" rows="8" class="large-text">' . esc_textarea( wp_json_encode( AS_Advisor_Questions::tree(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) . '</textarea>';
		submit_button( 'ذخیره' );
		echo '</form></div>';
	}

	public static function save_settings() {
		if ( empty( $_POST['_wpnonce'] ) || ! check_admin_referer( 'as_advisor_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( isset( $_POST['as_advisor_questions_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['as_advisor_questions_json'] ), true );
			if ( is_array( $decoded ) ) {
				update_option( 'as_advisor_questions', $decoded, false );
			}
		}
		if ( ! isset( $_POST['as_advisor_compat_text'] ) ) {
			return;
		}
		$rows = array();
		foreach ( preg_split( '/\r\n|\r|\n/', wp_unslash( $_POST['as_advisor_compat_text'] ) ) as $line ) {
			$line = trim( $line );
			if ( ! $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( ',', $line ) );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$rows[] = array(
				'device_id'    => (int) $parts[0],
				'part_id'      => (int) $parts[1],
				'status'       => ( isset( $parts[2] ) && 'confirmed' === $parts[2] ) ? 'confirmed' : 'unconfirmed',
				'device_model' => '',
			);
		}
		update_option( 'as_advisor_compat', $rows, false );
		add_settings_error( 'as_advisor', 'saved', 'ذخیره شد.', 'updated' );
	}

	public static function product_fields() {
		echo '<div class="options_group">';
		woocommerce_wp_select(
			array(
				'id'      => '_as_adv_kind',
				'label'   => 'نقش در دستیار انتخاب',
				'options' => array(
					''             => 'تشخیص خودکار',
					'water_device' => 'دستگاه تصفیه آب',
					'air_device'   => 'دستگاه تصفیه هوا',
					'water_filter' => 'فیلتر آب',
					'air_filter'   => 'فیلتر هوا',
					'cartridge'    => 'کارتریج',
					'tank'         => 'منبع',
					'pump'         => 'پمپ',
					'part'         => 'قطعه آب',
					'air_part'     => 'قطعه هوا',
					'other'        => 'سایر',
				),
			)
		);
		woocommerce_wp_text_input( array( 'id' => '_as_adv_model', 'label' => 'کد/مدل دستگاه' ) );
		woocommerce_wp_text_input( array( 'id' => '_as_adv_room_m2', 'label' => 'پوشش فضا (متر مربع)', 'type' => 'number' ) );
		woocommerce_wp_text_input( array( 'id' => '_as_adv_users_max', 'label' => 'حداکثر مصرف‌کننده', 'type' => 'number' ) );
		woocommerce_wp_select(
			array(
				'id'      => '_as_adv_place',
				'label'   => 'محل استفاده',
				'options' => array( '' => '—', 'home' => 'خانه', 'office' => 'محل کار', 'both' => 'هر دو' ),
			)
		);
		woocommerce_wp_select(
			array(
				'id'      => '_as_adv_need',
				'label'   => 'نیاز آب',
				'options' => array( '' => '—', 'general' => 'عمومی', 'taste' => 'طعم و بو', 'sediment' => 'ذرات', 'tds' => 'املاح' ),
			)
		);
		woocommerce_wp_select(
			array(
				'id'      => '_as_adv_install',
				'label'   => 'نصب',
				'options' => array( '' => '—', 'under_sink' => 'زیر سینک', 'compact' => 'کم‌جا', 'counter' => 'روی پیشخوان' ),
			)
		);
		woocommerce_wp_select(
			array(
				'id'      => '_as_adv_noise',
				'label'   => 'صدا',
				'options' => array( '' => '—', 'quiet' => 'کم‌صدا', 'normal' => 'معمول', 'loud' => 'بلند' ),
			)
		);
		woocommerce_wp_text_input( array( 'id' => '_as_adv_replace_months', 'label' => 'دوره تعویض فیلتر (ماه)', 'type' => 'number' ) );
		woocommerce_wp_text_input( array( 'id' => '_as_adv_filter_price', 'label' => 'قیمت ست فیلتر (همان واحد فروشگاه)', 'type' => 'number' ) );
		echo '</div>';
	}

	public static function save_product( $post_id ) {
		$keys = array( 'kind', 'model', 'room_m2', 'users_max', 'place', 'need', 'install', 'noise', 'replace_months', 'filter_price' );
		foreach ( $keys as $k ) {
			$field = '_as_adv_' . $k;
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta( $post_id, $field, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
			}
		}
	}
}
