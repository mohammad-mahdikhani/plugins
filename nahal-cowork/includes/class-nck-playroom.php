<?php
defined( 'ABSPATH' ) || exit;

/**
 * قرارداد رزرو و استفاده موقت از سالن بازی نهال
 */
class NCK_Playroom {

	public static function title_label( $title ) {
		if ( 'ms' === $title ) {
			return 'خانم';
		}
		if ( 'mr' === $title ) {
			return 'آقای';
		}
		return 'آقای/خانم';
	}

	public static function default_org() {
		return 'مؤسسه نهال رشد آفرین پویا';
	}

	public static function rent_price() {
		return 1500000;
	}

	public static function coach_price() {
		return 350000;
	}

	public static function validate( array $in ) {
		$name = trim( isset( $in['name'] ) ? (string) $in['name'] : '' );
		if ( $name === '' || ( function_exists( 'mb_strlen' ) ? mb_strlen( $name ) : strlen( $name ) ) < 3 ) {
			return array( 'ok' => false, 'message' => 'نام برگزارکننده را کامل وارد کنید.' );
		}

		$nid = NCK_Hall::normalize_nid( isset( $in['national_id'] ) ? $in['national_id'] : '' );
		if ( ! $nid ) {
			return array( 'ok' => false, 'message' => 'کد ملی معتبر نیست. باید ۱۰ رقم و مطابق الگوریتم ملی باشد.' );
		}

		$phone = NCK_Phone::normalize( isset( $in['phone'] ) ? $in['phone'] : '' );
		if ( ! $phone ) {
			return array( 'ok' => false, 'message' => 'شماره موبایل معتبر نیست. مثال: ۰۹۱۲۳۴۵۶۷۸۹' );
		}
		
		$address = trim( isset( $in['address'] ) ? (string) $in['address'] : '' );
		if ( $address === '' ) {
			return array( 'ok' => false, 'message' => 'نشانی را وارد کنید.' );
		}

		$date = NCK_Jalali::parse( isset( $in['event_date'] ) ? $in['event_date'] : '' );
		if ( ! $date ) {
			return array( 'ok' => false, 'message' => 'تاریخ رزرو را به صورت ۱۴۰۴/۰۶/۲۰ وارد کنید.' );
		}

		$hours = NCK_Hall::check_hours(
			isset( $in['start_hour'] ) ? $in['start_hour'] : '',
			isset( $in['end_hour'] ) ? $in['end_hour'] : ''
		);
		if ( empty( $hours['ok'] ) ) {
			return $hours;
		}

		$honorific = isset( $in['honorific'] ) ? (string) $in['honorific'] : 'mr';
		$honorific = in_array( $honorific, array( 'mr', 'ms' ), true ) ? $honorific : 'mr';

		$start_s = $hours['start'];
		$end_s   = $hours['end'];
		$date_s  = NCK_Jalali::format( $date['y'], $date['m'], $date['d'] );

		// Check clashes using Hall logic but with 'playroom' as hall name
		$clash = NCK_Hall::clash( $date_s, $start_s, $end_s, 'playroom' );
		if ( empty( $clash['ok'] ) ) {
			$clash['message'] = str_replace( 'در «playroom» پر است', 'پر است', $clash['message'] );
			return $clash;
		}
		
		$want_coach = ! empty( $in['coaches'] );
		$rent = self::rent_price();
		$coach_amount = $want_coach ? self::coach_price() : 0;
		$amount = $rent + $coach_amount;

		$pay = class_exists( 'NCK_Pay' ) ? NCK_Pay::parse_front_payment( $in, $amount ) : array( 'ok' => true, 'payload' => array() );
		if ( empty( $pay['ok'] ) ) {
			return $pay;
		}
		$pay_p = isset( $pay['payload'] ) && is_array( $pay['payload'] ) ? $pay['payload'] : array();
		$pay_p['pay_amount'] = $amount;

		return array(
			'ok'      => true,
			'message' => '',
			'payload' => array_merge(
				array(
					'kind'              => 'playroom',
					'honorific'         => $honorific,
					'name'              => $name,
					'national_id'       => $nid,
					'phone'             => $phone,
					'address'           => $address,
					'hall_name'         => 'سالن بازی کودکان',
					'amount'            => $amount,
					'rent'              => $rent,
					'coach_amount'      => $coach_amount,
					'coaches'           => $want_coach ? 1 : 0,
					'event_date'        => $date_s,
					'start_hour'        => $start_s,
					'end_hour'          => $end_s,
				),
				$pay_p
			),
		);
	}

	public static function clauses() {
		return array(
			array(
				'num'   => '۱',
				'title' => 'موضوع و زمان استفاده',
				'body'  => 'موضوع قرارداد، استفاده و رزرو موقت از سالن بازی کودکان در تاریخ {{date}} از ساعت {{start}} تا ساعت {{end}} است. برگزارکننده و عوامل چیدمان می‌توانند از ۳۰ دقیقه پیش از ساعت شروع، صرفاً برای چیدمان و نصب تزئینات وارد سالن شوند. این فرصت آماده‌سازی بدون هزینه اضافه است و جزو ۹۰ دقیقه زمان رزرو محسوب نمی‌شود؛ ورود مهمانان و شروع مراسم از ساعت آغاز رزرو مجاز است. جمع‌آوری وسایل، خروج کامل مهمانان و تحویل سالن باید تا ساعت پایان رزرو انجام شود و تأخیر در تحویل، مشمول بند ۱۱ است. استفاده از دیگر فضاها و امکانات مجموعه، صرفاً با توافق قبلی مجاز است.',
			),
			array(
				'num'   => '۲',
				'title' => 'ظرفیت و تعداد مهمانان',
				'body'  => 'حداکثر تعداد مهمانان حاضر به‌صورت همزمان، مجموعاً ۲۰ نفر شامل کودکان و بزرگسالان است. برگزارکننده موظف به کنترل تعداد مهمانان است و مجموعه می‌تواند از ورود افراد بیش از ظرفیت جلوگیری کند. پرداخت مبلغ اضافی به‌تنهایی مجوز افزایش ظرفیت نیست.',
			),
			array(
				'num'   => '۳',
				'title' => 'مبلغ قرارداد و رزرو',
				'body'  => 'هزینه استفاده از سالن به ازای ۹۰ دقیقه، یک میلیون و پانصد هزار تومان است. رزرو پس از امضای قرارداد و پرداخت قطعی می‌شود. خدمات اضافی صرفاً مطابق توافق مکتوب طرفین محاسبه خواهد شد. در صورت اعلام لغو رزرو از سوی برگزارکننده تا ۴۸ ساعت پیش از ساعت شروع مراسم، یعنی با فاصله زمانی حداقل ۴۸ ساعت تا شروع، ۱۳ درصد از مبلغ پرداختی بابت رزرو به‌عنوان هزینه لغو کسر و ۸۷ درصد باقیمانده به پرداخت‌کننده بازگردانده می‌شود. این شرط با رعایت حقوق الزامی مصرف‌کننده، از جمله حق انصراف قانونی در موارد شمول، اجرا می‌شود.',
			),
			array(
				'num'   => '۴',
				'title' => 'مراقبت از کودکان و مسئولیت طرفین',
				'body'  => 'نظارت مستمر بر کودکان، جلوگیری از رفتارهای خطرناک، رعایت محدودیت سنی وسایل و تحویل کودکان به والدین یا افراد مجاز، بر عهده برگزارکننده و همراهان مسئول آنان است.',
			),
			array(
				'num'   => '۵',
				'title' => 'همکاری مربیان نهال در طول برنامه',
				'body'  => 'در صورت درخواست برگزارکننده و تأیید قبلی مجموعه، هزینه حضور هر مربی برای هر ۹۰ دقیقه، ۳۵۰ هزار تومان است. برای هر گروه حداکثر ۸ کودک، یک مربی اختصاص می‌یابد؛ بنابراین برای ۱ تا ۸ کودک یک مربی و برای ۹ تا ۱۵ کودک دو مربی لازم است.' . "\n\n" . 'مایل هستم همکاران مجموعه نهال در طول برنامه، ما را همراهی کنند: {{coaches}}' . "\n\n" . 'وظیفه مربی، هدایت بازی و نظارت در محدوده فعالیت توافق‌شده است. حضور مربی جایگزین مسئولیت برگزارکننده در کنترل مهمانان و تحویل کودکان نیست. واگذاری کامل نگهداری کودکان به مجموعه، نیازمند توافق جداگانه است.',
			),
			array(
				'num'   => '۶',
				'title' => 'خسارت به تجهیزات و ساختمان',
				'body'  => 'برگزارکننده متعهد است خسارت واردشده توسط خود، مهمانان یا عوامل اجرایی مراسم به وسایل بازی، مبلمان، تجهیزات، دیوارها، کف، درها، شیشه‌ها و سایر اجزای ساختمان را جبران کند.',
			),
			array(
				'num'   => '۷',
				'title' => 'نظافت و شست‌وشوی فرش و موکت',
				'body'  => 'صرف خوراکی و نوشیدنی فقط در محل تعیین‌شده مجاز است. چنانچه ریختن خوراکی، نوشیدنی، مواد رنگی یا سایر آلودگی‌های ناشی از مراسم، با نظافت معمول برطرف نشود، هزینه شست‌وشوی کامل فرش آلوده یا بخش یکپارچه موکت آلوده، مطابق صورت‌حساب خدمات شست‌وشو، بر عهده برگزارکننده است.' . "\n" . 'در صورت آلودگی گسترده و ضرورت شست‌وشوی تمام سطح مفروش سالن، هزینه شست‌وشوی کل آن دریافت می‌شود. نظافت عادی ناشی از استفاده متعارف، مشمول این هزینه نیست.',
			),
			array(
				'num'   => '۸',
				'title' => 'ممنوعیت فشفشه و مواد آتش‌زا',
				'body'  => 'استفاده از هر نوع فشفشه، از جمله فشفشه کیک، مواد محترقه، دودزا و تجهیزات جرقهزا در سالن ممنوع است. استفاده از شمع معمولی کیک نیز فقط با هماهنگی قبلی مجموعه، در محل مشخص و تحت نظارت مستقیم بزرگسال مجاز خواهد بود.',
			),
			array(
				'num'   => '۹',
				'title' => 'ممنوعیت برف شادی و اسپری‌های مشابه',
				'body'  => 'استفاده از برف شادی، نخ شادی و اسپری‌های جشن در فضای داخلی مجموعه ممنوع است. پاشش این محصولات، ذرات معلق و بسته به ترکیب محصول، بخارات حلال و گازهای پیشران را وارد هوا می‌کند. در فضای بسته یا دارای تهویه ناکافی، مواجهه با این مواد می‌تواند موجب تحریک چشم، پوست و دستگاه تنفسی شود؛ همچنین برخی فرمولاسیون‌ها قابلیت اشتعال دارند. این ممنوعیت با هدف کاهش مواجهه کودکان و پیشگیری از آسیب اعمال می‌شود.',
			),
			array(
				'num'   => '۱۰',
				'title' => 'تزئینات و عوامل بیرونی',
				'body'  => 'نصب تزئینات با میخ، پیچ، چسب آسیب‌زا یا اتصال به تجهیزات ایمنی، مسدودکردن خروجی‌ها و جابه‌جایی وسایل سنگین بدون اجازه مجموعه ممنوع است.',
			),
			array(
				'num'   => '۱۱',
				'title' => 'هزینه حضور اضافه بر زمان قرارداد',
				'body'  => 'هر میزان حضور یا استفاده از سالن پس از ساعت پایان قرارداد، حتی کمتر از یک ساعت، مشمول هزینه جداگانه است. این مدت از ساعت پایان مقرر تا خروج کامل مهمانان و عوامل مراسم، جمع‌آوری وسایل و تحویل سالن محاسبه می‌شود. کسری از ساعت نیز به نسبت دقیقه، بر مبنای یک‌شصتم نرخ ساعتی برای هر دقیقه، محاسبه می‌گردد. برگزارکننده موظف است این مبلغ را هنگام تحویل سالن، جدا از هزینه اولیه رزرو، تسویه کند. پرداخت هزینه اضافه‌زمان به‌تنهایی مجوز تمدید نیست و ادامه حضور نیازمند موافقت مجموعه است. هزینه اضافه حضور مربیان، در صورت استفاده، مطابق بند ۵ جداگانه محاسبه می‌شود.' . "\n" . 'ادامه مراسم پس از پایان زمان، بدون موافقت مجموعه مجاز نیست.',
			),
			array(
				'num'   => '۱۲',
				'title' => 'توقف رفتار خطرناک و حل اختلاف',
				'body'  => 'مجموعه می‌تواند از رفتار خطرناک، استفاده از وسایل ممنوع یا ورود بیش از ظرفیت جلوگیری کند. در خطر فوری، فعالیت مربوط بلافاصله متوقف می‌شود؛ در سایر موارد، ابتدا به برگزارکننده تذکر داده خواهد شد. اگر خطر با اقدام محدودتر برطرف نشود، مجموعه می‌تواند مراسم را متوقف کند. تسویه مالی بر اساس خدمات ارائه‌شده و خسارت مستند انجام می‌شود.',
			),
		);
	}

	public static function fill_vars( array $payload, array $s = array() ) {
		$blank = NCK_Contract::blank( 12 );
		$org   = self::default_org();
		$date  = isset( $payload['event_date'] ) ? NCK_Jalali::parse( $payload['event_date'] ) : null;
		$date_s = $date ? NCK_Jalali::fa_digits( NCK_Jalali::format( $date['y'], $date['m'], $date['d'] ) ) : '…. / …. / ….';

		return array(
			'org'     => $org,
			'title'   => self::title_label( isset( $payload['honorific'] ) ? $payload['honorific'] : '' ),
			'name'    => ! empty( $payload['name'] ) ? $payload['name'] : $blank,
			'nid'     => ! empty( $payload['national_id'] ) ? NCK_Jalali::fa_digits( $payload['national_id'] ) : $blank,
			'phone'   => ! empty( $payload['phone'] ) ? NCK_Jalali::fa_digits( $payload['phone'] ) : $blank,
			'address' => ! empty( $payload['address'] ) ? $payload['address'] : $blank,
			'amount'  => ! empty( $payload['amount'] ) ? NCK_Hall::format_money( $payload['amount'] ) : $blank,
			'date'    => $date_s,
			'start'   => ! empty( $payload['start_hour'] ) ? NCK_Jalali::fa_digits( $payload['start_hour'] ) : '….',
			'end'     => ! empty( $payload['end_hour'] ) ? NCK_Jalali::fa_digits( $payload['end_hour'] ) : '….',
			'coaches' => ! empty( $payload['coaches'] ) ? '☑ بله    ☐ خیر' : '☐ بله    ☑ خیر',
		);
	}

	public static function filled_clauses( array $vars ) {
		$out = array();
		foreach ( self::clauses() as $block ) {
			$row = array(
				'num'   => $block['num'],
				'title' => $block['title'],
			);
			if ( isset( $block['body'] ) ) {
				$row['body'] = NCK_Contract::fill_template( $block['body'], $vars );
			}
			$out[] = $row;
		}
		return $out;
	}
}
