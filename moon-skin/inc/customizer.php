<?php
/**
 * سفارشی‌سازی قالب.
 *
 * @package Moon_Skin
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('customize_register', 'moon_skin_customize');

/**
 * چک‌باکس را صفر و یک می‌کند.
 *
 * @param mixed $value Raw value.
 * @return int
 */
function moon_skin_sanitize_check($value) {
	return empty($value) || '0' === (string) $value ? 0 : 1;
}

/**
 * عدد را در بازه خودش نگه می‌دارد.
 *
 * @param mixed                $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return int
 */
function moon_skin_sanitize_number($value, $setting) {
	$ranges = array(
		'moon_skin_width'         => array(960, 1600),
		'moon_skin_radius'        => array(0, 48),
		'moon_skin_button_radius' => array(0, 999),
		'moon_skin_home_count'    => array(1, 24),
		'moon_skin_menu_count'    => array(1, 12),
	);
	$id  = $setting->id;
	$min = isset($ranges[$id]) ? $ranges[$id][0] : 0;
	$max = isset($ranges[$id]) ? $ranges[$id][1] : 9999;
	$n   = (int) $value;
	if ($n < $min || $n > $max) {
		$defaults = moon_skin_defaults();
		return isset($defaults[$id]) ? (int) $defaults[$id] : $min;
	}
	return $n;
}

/**
 * فقط گزینه‌های فهرست را می‌پذیرد.
 *
 * @param mixed                $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return string
 */
function moon_skin_sanitize_choice($value, $setting) {
	$control = $setting->manager->get_control($setting->id);
	$choices = ($control && !empty($control->choices) && is_array($control->choices)) ? $control->choices : array();
	$value   = (string) $value;
	if (isset($choices[$value])) {
		return $value;
	}
	$defaults = moon_skin_defaults();
	return isset($defaults[$setting->id]) ? (string) $defaults[$setting->id] : '';
}

/**
 * یک فیلد سفارشی‌سازی.
 *
 * @param WP_Customize_Manager $wp      Manager.
 * @param string               $id      Setting id.
 * @param string               $section Section id.
 * @param string               $label   Label.
 * @param string               $type    Control type.
 * @param array<string,mixed>  $args    Extra args.
 */
function moon_skin_add_field($wp, $id, $section, $label, $type = 'text', $args = array()) {
	$defaults = moon_skin_defaults();
	$sanitize = 'sanitize_text_field';
	if ('textarea' === $type) {
		$sanitize = 'sanitize_textarea_field';
	} elseif ('color' === $type) {
		$sanitize = 'sanitize_hex_color';
	} elseif ('checkbox' === $type) {
		$sanitize = 'moon_skin_sanitize_check';
	} elseif ('url' === $type) {
		$sanitize = 'esc_url_raw';
	} elseif ('email' === $type) {
		$sanitize = 'sanitize_email';
	} elseif ('number' === $type) {
		$sanitize = 'moon_skin_sanitize_number';
	} elseif ('select' === $type) {
		$sanitize = 'moon_skin_sanitize_choice';
	}

	$wp->add_setting($id, array(
		'default'           => array_key_exists($id, $defaults) ? $defaults[$id] : '',
		'sanitize_callback' => $sanitize,
		'transport'         => 'refresh',
	));

	if ('color' === $type) {
		$wp->add_control(new WP_Customize_Color_Control($wp, $id, array(
			'label'       => $label,
			'section'     => $section,
			'description' => isset($args['description']) ? $args['description'] : '',
		)));
		return;
	}

	$control = array(
		'label'   => $label,
		'section' => $section,
		'type'    => ('url' === $type || 'email' === $type) ? 'text' : $type,
	);
	if (!empty($args['description'])) {
		$control['description'] = $args['description'];
	}
	if ('select' === $type && !empty($args['choices'])) {
		$control['choices'] = $args['choices'];
	}
	if ('number' === $type && !empty($args['input_attrs'])) {
		$control['input_attrs'] = $args['input_attrs'];
	}
	$wp->add_control($id, $control);
}

/**
 * پنل سفارشی‌سازی.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function moon_skin_customize($wp_customize) {
	$wp_customize->add_panel('moon_skin_panel', array(
		'title'       => __('مون‌اسکین', 'moon-skin'),
		'description' => __('رنگ، متن، چیدمان و بخش‌های قالب از اینجا عوض می‌شود. لوگو و نام سایت در هویت سایت است. فهرست‌ها در بخش فهرست‌ها ساخته می‌شوند. روش ارسال در ووکامرس می‌ماند.', 'moon-skin'),
		'priority'    => 30,
	));

	$sections = array(
		'moon_skin_colors'  => __('رنگ‌ها', 'moon-skin'),
		'moon_skin_layout'  => __('چیدمان', 'moon-skin'),
		'moon_skin_header'  => __('سربرگ', 'moon-skin'),
		'moon_skin_home'    => __('خانه', 'moon-skin'),
		'moon_skin_shop'    => __('فروشگاه', 'moon-skin'),
		'moon_skin_blog'    => __('مجله', 'moon-skin'),
		'moon_skin_footer'  => __('پاصفحه', 'moon-skin'),
		'moon_skin_cart'    => __('سبد و تسویه', 'moon-skin'),
	);
	$priority = 10;
	foreach ($sections as $id => $title) {
		$wp_customize->add_section($id, array(
			'title'    => $title,
			'panel'    => 'moon_skin_panel',
			'priority' => $priority,
		));
		$priority += 10;
	}

	$colors = array(
		'moon_skin_accent'     => __('رنگ اصلی', 'moon-skin'),
		'moon_skin_on'         => __('متن روی دکمه', 'moon-skin'),
		'moon_skin_ink'        => __('متن', 'moon-skin'),
		'moon_skin_muted'      => __('متن فرعی', 'moon-skin'),
		'moon_skin_bg'         => __('پس‌زمینه', 'moon-skin'),
		'moon_skin_card'       => __('کارت', 'moon-skin'),
		'moon_skin_soft'       => __('سطح روشن', 'moon-skin'),
		'moon_skin_line'       => __('خط', 'moon-skin'),
		'moon_skin_bar'        => __('نوار بالا', 'moon-skin'),
		'moon_skin_footer_bg'  => __('پس‌زمینه پاصفحه', 'moon-skin'),
		'moon_skin_footer_ink' => __('متن پاصفحه', 'moon-skin'),
		'moon_skin_night_bg'   => __('پس‌زمینه حالت شب', 'moon-skin'),
		'moon_skin_night_card' => __('کارت حالت شب', 'moon-skin'),
		'moon_skin_night_ink'  => __('متن حالت شب', 'moon-skin'),
		'moon_skin_night_soft' => __('سطح حالت شب', 'moon-skin'),
	);
	foreach ($colors as $id => $label) {
		moon_skin_add_field($wp_customize, $id, 'moon_skin_colors', $label, 'color');
	}

	moon_skin_add_field($wp_customize, 'moon_skin_width', 'moon_skin_layout', __('پهنای صفحه', 'moon-skin'), 'number', array(
		'input_attrs' => array('min' => 960, 'max' => 1600, 'step' => 10),
		'description' => __('بین ۹۶۰ و ۱۶۰۰ پیکسل.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_radius', 'moon_skin_layout', __('گردی کارت‌ها', 'moon-skin'), 'number', array(
		'input_attrs' => array('min' => 0, 'max' => 48, 'step' => 1),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_button_radius', 'moon_skin_layout', __('گردی دکمه‌ها', 'moon-skin'), 'number', array(
		'input_attrs' => array('min' => 0, 'max' => 999, 'step' => 1),
		'description' => __('۹۹۹ دکمه را کاملا گرد می‌کند.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_shop_columns', 'moon_skin_layout', __('ستون محصولات', 'moon-skin'), 'select', array(
		'choices' => array('2' => '2', '3' => '3', '4' => '4'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_blog_columns', 'moon_skin_layout', __('ستون مجله', 'moon-skin'), 'select', array(
		'choices' => array('2' => '2', '3' => '3', '4' => '4'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_font', 'moon_skin_layout', __('فونت', 'moon-skin'), 'select', array(
		'choices' => array(
			'vazir'  => __('وزیرمتن', 'moon-skin'),
			'system' => __('فونت سیستم', 'moon-skin'),
		),
	));

	$header_text = array(
		'moon_skin_products_label'     => __('متن دکمه محصولات', 'moon-skin'),
		'moon_skin_search_placeholder' => __('متن جستجو', 'moon-skin'),
		'moon_skin_label_home'         => __('متن خانه', 'moon-skin'),
		'moon_skin_label_shop'         => __('متن فروشگاه', 'moon-skin'),
		'moon_skin_label_blog'         => __('متن مجله', 'moon-skin'),
		'moon_skin_label_account'      => __('متن حساب کاربری', 'moon-skin'),
		'moon_skin_label_cart'         => __('متن کوتاه سبد', 'moon-skin'),
	);
	foreach ($header_text as $id => $label) {
		moon_skin_add_field($wp_customize, $id, 'moon_skin_header', $label, 'text');
	}
	$header_checks = array(
		'moon_skin_show_search'  => __('نمایش جستجو', 'moon-skin'),
		'moon_skin_show_account' => __('نمایش حساب کاربری', 'moon-skin'),
		'moon_skin_show_cart'    => __('نمایش سبد', 'moon-skin'),
		'moon_skin_show_night'   => __('نمایش دکمه حالت شب', 'moon-skin'),
		'moon_skin_night_default'=> __('سایت با حالت شب باز شود', 'moon-skin'),
		'moon_skin_show_mega'    => __('نمایش منوی دسته‌ها', 'moon-skin'),
	);
	foreach ($header_checks as $id => $label) {
		moon_skin_add_field($wp_customize, $id, 'moon_skin_header', $label, 'checkbox');
	}
	moon_skin_add_field($wp_customize, 'moon_skin_menu_count', 'moon_skin_header', __('تعداد دسته در منو', 'moon-skin'), 'number', array(
		'input_attrs' => array('min' => 1, 'max' => 12, 'step' => 1),
	));

	moon_skin_add_field($wp_customize, 'moon_skin_show_hero', 'moon_skin_home', __('نمایش معرفی بالای خانه', 'moon-skin'), 'checkbox');
	moon_skin_add_field($wp_customize, 'moon_skin_kicker', 'moon_skin_home', __('متن کوچک', 'moon-skin'), 'text', array(
		'description' => __('خالی باشد نام سایت نوشته می‌شود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_hero_title', 'moon_skin_home', __('عنوان', 'moon-skin'), 'text', array(
		'description' => __('خالی باشد نام سایت نوشته می‌شود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_hero_text', 'moon_skin_home', __('توضیح', 'moon-skin'), 'textarea', array(
		'description' => __('خالی باشد معرفی کوتاه سایت نوشته می‌شود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_show_hero_button', 'moon_skin_home', __('نمایش دکمه', 'moon-skin'), 'checkbox');
	moon_skin_add_field($wp_customize, 'moon_skin_hero_button', 'moon_skin_home', __('متن دکمه', 'moon-skin'), 'text');
	moon_skin_add_field($wp_customize, 'moon_skin_hero_url', 'moon_skin_home', __('پیوند دکمه', 'moon-skin'), 'url', array(
		'description' => __('خالی باشد به فروشگاه می‌رود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_show_cats', 'moon_skin_home', __('نمایش دسته‌ها', 'moon-skin'), 'checkbox');
	moon_skin_add_field($wp_customize, 'moon_skin_show_products', 'moon_skin_home', __('نمایش محصولات', 'moon-skin'), 'checkbox');
	moon_skin_add_field($wp_customize, 'moon_skin_home_count', 'moon_skin_home', __('تعداد محصول خانه', 'moon-skin'), 'number', array(
		'input_attrs' => array('min' => 1, 'max' => 24, 'step' => 1),
	));

	moon_skin_add_field($wp_customize, 'moon_skin_show_shipping', 'moon_skin_shop', __('نمایش روش ارسال روی محصول', 'moon-skin'), 'checkbox', array(
		'description' => __('هزینه از تنظیمات حمل‌ونقل ووکامرس خوانده می‌شود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_shipping_title', 'moon_skin_shop', __('عنوان جعبه ارسال', 'moon-skin'), 'text');

	moon_skin_add_field($wp_customize, 'moon_skin_show_breadcrumb', 'moon_skin_blog', __('نمایش مسیر', 'moon-skin'), 'checkbox');
	moon_skin_add_field($wp_customize, 'moon_skin_show_post_image', 'moon_skin_blog', __('نمایش تصویر شاخص', 'moon-skin'), 'checkbox');
	moon_skin_add_field($wp_customize, 'moon_skin_show_date', 'moon_skin_blog', __('نمایش تاریخ', 'moon-skin'), 'checkbox');

	moon_skin_add_field($wp_customize, 'moon_skin_footer_about', 'moon_skin_footer', __('متن معرفی', 'moon-skin'), 'textarea', array(
		'description' => __('خالی باشد معرفی کوتاه سایت نوشته می‌شود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_phone', 'moon_skin_footer', __('تلفن', 'moon-skin'), 'text');
	moon_skin_add_field($wp_customize, 'moon_skin_email', 'moon_skin_footer', __('ایمیل', 'moon-skin'), 'email');
	moon_skin_add_field($wp_customize, 'moon_skin_address', 'moon_skin_footer', __('نشانی', 'moon-skin'), 'textarea');
	moon_skin_add_field($wp_customize, 'moon_skin_instagram', 'moon_skin_footer', __('اینستاگرام', 'moon-skin'), 'url');
	moon_skin_add_field($wp_customize, 'moon_skin_telegram', 'moon_skin_footer', __('تلگرام', 'moon-skin'), 'url');
	moon_skin_add_field($wp_customize, 'moon_skin_whatsapp', 'moon_skin_footer', __('واتساپ', 'moon-skin'), 'url');
	moon_skin_add_field($wp_customize, 'moon_skin_copyright', 'moon_skin_footer', __('متن پایین', 'moon-skin'), 'text', array(
		'description' => __('خالی باشد نام سایت و سال نوشته می‌شود.', 'moon-skin'),
	));
	moon_skin_add_field($wp_customize, 'moon_skin_show_dock', 'moon_skin_footer', __('نوار پایین موبایل', 'moon-skin'), 'checkbox');

	$cart_text = array(
		'moon_skin_cart_title' => __('عنوان سبد', 'moon-skin'),
		'moon_skin_cart_empty' => __('متن سبد خالی', 'moon-skin'),
		'moon_skin_pay_label'  => __('متن تسویه', 'moon-skin'),
		'moon_skin_step_1'     => __('مرحله اول', 'moon-skin'),
		'moon_skin_step_2'     => __('مرحله دوم', 'moon-skin'),
		'moon_skin_step_3'     => __('مرحله سوم', 'moon-skin'),
		'moon_skin_back'       => __('دکمه بازگشت', 'moon-skin'),
		'moon_skin_next'       => __('دکمه ادامه', 'moon-skin'),
		'moon_skin_ship_label' => __('عنوان روش ارسال', 'moon-skin'),
		'moon_skin_night_label'=> __('متن حالت شب', 'moon-skin'),
		'moon_skin_day_label'  => __('متن حالت روز', 'moon-skin'),
	);
	foreach ($cart_text as $id => $label) {
		moon_skin_add_field($wp_customize, $id, 'moon_skin_cart', $label, 'text');
	}
	moon_skin_add_field($wp_customize, 'moon_skin_checkout_steps', 'moon_skin_cart', __('تسویه سه مرحله‌ای', 'moon-skin'), 'checkbox');
}
