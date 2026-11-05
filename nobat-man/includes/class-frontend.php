<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_Frontend {
	public static function init() {
		add_shortcode( 'nobat_man', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		wp_register_style( 'nobat-man', NOBAT_MAN_URL . 'assets/frontend/app.css', array(), NOBAT_MAN_VERSION );
		wp_register_script( 'nobat-man', NOBAT_MAN_URL . 'assets/frontend/app.js', array(), NOBAT_MAN_VERSION, true );
	}

	public static function shortcode() {
		wp_enqueue_style( 'nobat-man' );
		wp_enqueue_script( 'nobat-man' );
		$s     = nobat_man_settings();
		$now   = current_time( 'timestamp' );
		list( $jy, $jm ) = Nobat_Man_Jalali::gregorian_to_jalali( (int) gmdate( 'Y', $now ), (int) gmdate( 'n', $now ), (int) gmdate( 'j', $now ) );
		$config = array(
			'ajax'        => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'nobat_man' ),
			'year'        => $jy,
			'month'       => $jm,
			'grid'        => Nobat_Man_Jalali::month_matrix( $jy, $jm ),
			'monthName'   => Nobat_Man_Jalali::month_name( $jm ),
			'categories'  => Nobat_Man_Categories::all(),
			'specialists' => nobat_man_specialists(),
			'price'       => (int) $s['price'],
			'duration'    => (int) $s['duration'],
			'workingDays' => array_map( 'intval', (array) $s['working_days'] ),
			'slotStart'   => $s['slot_start'],
			'slotEnd'     => $s['slot_end'],
			'holidays'    => array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $s['holidays'] ) ) ),
		);
		wp_localize_script( 'nobat-man', 'NobatMan', $config );

		ob_start();
		?>
		<div class="nm-app" dir="rtl">
			<section class="nm-hero">
				<h2>رزرو نوبت مشاوره</h2>
				<p>تقویم شمسی، انتخاب دسته مشکل و پرداخت امن</p>
			</section>
			<form class="nm-form-front" id="nm-booking">
				<fieldset>
					<legend>۱) دسته‌بندی مشکل</legend>
					<div class="nm-cats" id="nm-cats"></div>
					<div id="nm-cat-questions"></div>
				</fieldset>
				<fieldset>
					<legend>۲) اطلاعات شما</legend>
					<input type="text" name="name" placeholder="نام و نام خانوادگی" required />
					<input type="tel" name="phone" placeholder="تلفن" required />
					<input type="email" name="email" placeholder="ایمیل" />
					<input type="text" name="city" placeholder="شهر" />
					<select name="gender">
						<option value="">جنسیت</option>
						<option value="female">زن</option>
						<option value="male">مرد</option>
					</select>
				</fieldset>
				<fieldset>
					<legend>۳) متخصص و زمان</legend>
					<select name="specialist" id="nm-specialist"></select>
					<div class="nm-cal-nav">
						<button type="button" id="nm-prev">ماه قبل</button>
						<strong id="nm-month-label"></strong>
						<button type="button" id="nm-next">ماه بعد</button>
					</div>
					<div class="nm-cal" id="nm-cal"></div>
					<div class="nm-slots" id="nm-slots"></div>
					<input type="hidden" name="date" id="nm-date" />
					<input type="hidden" name="start" id="nm-start" />
					<input type="hidden" name="category" id="nm-category" />
				</fieldset>
				<fieldset>
					<legend>۴) پیوست</legend>
					<input type="file" name="photo" accept="image/*" />
					<input type="file" name="voice" accept="audio/*" />
				</fieldset>
				<p class="nm-price">مبلغ جلسه: <strong id="nm-price-label"></strong></p>
				<button type="submit" class="nm-submit">ثبت نوبت</button>
				<div id="nm-result" class="nm-result" hidden></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
