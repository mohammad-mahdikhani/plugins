<?php
/**
 * Build readable HTML / JSON reports.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'NPBR_TEST' ) ) {
	exit;
}

class NPBR_Report {

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @return string
	 */
	public static function html( array $data ) {
		$failed  = isset( $data['failed'] ) && is_array( $data['failed'] ) ? $data['failed'] : array();
		$errors  = isset( $data['errors'] ) && is_array( $data['errors'] ) ? $data['errors'] : array();
		$payment = isset( $data['payment'] ) && is_array( $data['payment'] ) ? $data['payment'] : array();
		$shot    = isset( $data['screenshot'] ) ? (string) $data['screenshot'] : '';

		$failed_rows = '';
		if ( $failed ) {
			foreach ( $failed as $row ) {
				$badge = ! empty( $row['payment'] ) ? 'پرداخت' : 'عمومی';
				$failed_rows .= '<tr><td>' . self::e( $badge ) . '</td><td>' . self::e( isset( $row['kind'] ) ? $row['kind'] : '' ) . '</td><td>' . self::e( isset( $row['status'] ) ? $row['status'] : '' ) . '</td><td dir="ltr">' . self::e( isset( $row['url'] ) ? $row['url'] : '' ) . '</td></tr>';
			}
		} else {
			$failed_rows = '<tr><td colspan="4">مورد لودنشده‌ای ثبت نشد.</td></tr>';
		}

		$error_rows = '';
		if ( $errors ) {
			foreach ( $errors as $row ) {
				$error_rows .= '<tr><td>' . self::e( isset( $row['type'] ) ? $row['type'] : '' ) . '</td><td>' . self::e( isset( $row['message'] ) ? $row['message'] : '' ) . '</td><td dir="ltr">' . self::e( isset( $row['source'] ) ? $row['source'] : '' ) . '</td></tr>';
			}
		} else {
			$error_rows = '<tr><td colspan="3">خطای جاوااسکریپت ثبت نشد.</td></tr>';
		}

		$methods = '';
		if ( ! empty( $payment['methods'] ) && is_array( $payment['methods'] ) ) {
			foreach ( $payment['methods'] as $method ) {
				$sel      = ! empty( $method['selected'] ) ? 'بله' : 'خیر';
				$methods .= '<li>' . self::e( ( isset( $method['label'] ) ? $method['label'] : '' ) . ' (' . ( isset( $method['id'] ) ? $method['id'] : '' ) . ') — انتخاب‌شده: ' . $sel ) . '</li>';
			}
		} else {
			$methods = '<li>هیچ درگاه پرداختی در صفحه دیده نشد.</li>';
		}

		$fields = '';
		if ( ! empty( $payment['fields'] ) && is_array( $payment['fields'] ) ) {
			foreach ( $payment['fields'] as $field ) {
				$state   = array();
				$state[] = ! empty( $field['visible'] ) ? 'نمایان' : 'مخفی';
				$state[] = ! empty( $field['empty'] ) ? 'خالی' : 'پر';
				if ( ! empty( $field['broken'] ) ) {
					$state[] = 'آسیب‌دیده';
				}
				$fields .= '<li>' . self::e( ( isset( $field['name'] ) ? $field['name'] : $field['id'] ) . ' [' . ( isset( $field['type'] ) ? $field['type'] : '' ) . '] — ' . implode( '، ', $state ) ) . '</li>';
			}
		} else {
			$fields = '<li>فیلد پرداختی پیدا نشد.</li>';
		}

		$shot_html = $shot
			? '<img class="shot" alt="اسکرین‌شات صفحه پرداخت" src="' . self::e( $shot ) . '" />'
			: '<p>اسکرین‌شات در دسترس نیست.</p>';

		$payment_flags = array();
		$payment_flags[] = ! empty( $payment['foundPaymentBox'] ) ? 'باکس پرداخت پیدا شد' : 'باکس پرداخت پیدا نشد';
		if ( ! empty( $payment['emptyBox'] ) ) {
			$payment_flags[] = 'باکس پرداخت خالی یا رندر نشده';
		}
		if ( ! empty( $payment['noMethods'] ) ) {
			$payment_flags[] = 'روش پرداختی لود نشد';
		}

		return '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><title>گزارش خطای پرداخت</title>'
			. '<style>body{font-family:Tahoma,Vazirmatn,sans-serif;background:#f4f7f3;color:#1b2a22;margin:0;padding:24px} .wrap{max-width:880px;margin:auto;background:#fff;border:1px solid #d7e4dc;border-radius:16px;padding:24px} h1{margin:0 0 8px} .meta{color:#4b6358;line-height:1.8} table{width:100%;border-collapse:collapse;margin:12px 0 20px} th,td{border:1px solid #e2ece6;padding:8px;text-align:right;font-size:13px} th{background:#edf3ef} .shot{max-width:100%;border:1px solid #d7e4dc;border-radius:12px} .note{white-space:pre-wrap;background:#f7faf6;border-radius:12px;padding:12px}</style>'
			. '</head><body><div class="wrap"><h1>گزارش خطای پرداخت نهال</h1>'
			. '<p class="meta">صفحه: ' . self::e( isset( $data['pageTitle'] ) ? $data['pageTitle'] : '' ) . '<br>نشانی: ' . self::e( isset( $data['pageUrl'] ) ? $data['pageUrl'] : '' ) . '<br>زمان: ' . self::e( isset( $data['capturedAt'] ) ? $data['capturedAt'] : '' ) . '<br>مرورگر: ' . self::e( isset( $data['userAgent'] ) ? $data['userAgent'] : '' ) . '<br>ابعاد: ' . self::e( isset( $data['viewport'] ) ? $data['viewport'] : '' ) . '</p>'
			. '<h2>شرح کاربر</h2><div class="note">' . self::e( isset( $data['note'] ) ? $data['note'] : '' ) . '</div>'
			. '<p class="meta">نام: ' . self::e( isset( $data['name'] ) ? $data['name'] : '—' ) . ' — موبایل: ' . self::e( isset( $data['phone'] ) ? $data['phone'] : '—' ) . '</p>'
			. '<h2>وضعیت فیلدهای پرداخت</h2><p>' . self::e( implode( '؛ ', $payment_flags ) ) . '</p><p>نوع صفحه: ' . self::e( isset( $payment['checkoutType'] ) ? $payment['checkoutType'] : '' ) . '</p>'
			. '<h3>درگاه‌ها</h3><ul>' . $methods . '</ul>'
			. '<h3>فیلدها</h3><ul>' . $fields . '</ul>'
			. '<h2>کدها و فایل‌هایی که لود نشدند</h2><table><thead><tr><th>نوع</th><th>جنس</th><th>وضعیت</th><th>نشانی</th></tr></thead><tbody>' . $failed_rows . '</tbody></table>'
			. '<h2>خطاهای جاوااسکریپت</h2><table><thead><tr><th>نوع</th><th>پیام</th><th>منبع</th></tr></thead><tbody>' . $error_rows . '</tbody></table>'
			. '<h2>اسکرین‌شات صفحه</h2>' . $shot_html
			. '</div></body></html>';
	}

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @return string
	 */
	public static function summary( array $data ) {
		$failed = isset( $data['failed'] ) && is_array( $data['failed'] ) ? $data['failed'] : array();
		$pay    = 0;
		foreach ( $failed as $row ) {
			if ( ! empty( $row['payment'] ) ) {
				$pay++;
			}
		}
		$errors = isset( $data['errors'] ) && is_array( $data['errors'] ) ? count( $data['errors'] ) : 0;
		return sprintf(
			'%d فایل لودنشده (%d مربوط به پرداخت) — %d خطای اسکریپت',
			count( $failed ),
			$pay,
			$errors
		);
	}

	/**
	 * @param array<string, mixed> $data Normalized payload.
	 * @return string
	 */
	public static function title( array $data ) {
		$page = isset( $data['pageTitle'] ) && $data['pageTitle'] ? $data['pageTitle'] : 'صفحه پرداخت';
		$when = isset( $data['capturedAt'] ) && $data['capturedAt'] ? $data['capturedAt'] : date( 'Y-m-d H:i' );
		return 'گزارش خطا — ' . $page . ' — ' . $when;
	}

	/**
	 * Prompt ready to paste into an AI coding agent (Cursor, ChatGPT, …)
	 * together with the screenshot of the broken payment page.
	 *
	 * @param array<string, mixed> $data Normalized payload.
	 * @param string               $screenshot_url Optional public/admin URL of the shot.
	 * @return string
	 */
	public static function agent_prompt( array $data, $screenshot_url = '' ) {
		$payment = isset( $data['payment'] ) && is_array( $data['payment'] ) ? $data['payment'] : array();
		$failed  = isset( $data['failed'] ) && is_array( $data['failed'] ) ? $data['failed'] : array();
		$errors  = isset( $data['errors'] ) && is_array( $data['errors'] ) ? $data['errors'] : array();
		$note    = isset( $data['note'] ) ? trim( (string) $data['note'] ) : '';
		$page    = isset( $data['pageUrl'] ) ? (string) $data['pageUrl'] : '';
		$title   = isset( $data['pageTitle'] ) ? (string) $data['pageTitle'] : '';
		$when    = isset( $data['capturedAt'] ) ? (string) $data['capturedAt'] : '';
		$ua      = isset( $data['userAgent'] ) ? (string) $data['userAgent'] : '';
		$view    = isset( $data['viewport'] ) ? (string) $data['viewport'] : '';
		$online  = array_key_exists( 'online', $data ) ? ( ! empty( $data['online'] ) ? 'آنلاین' : 'آفلاین' ) : '';
		$type    = isset( $payment['checkoutType'] ) ? (string) $payment['checkoutType'] : '';
		$classes = isset( $payment['bodyClasses'] ) ? (string) $payment['bodyClasses'] : '';

		$shot = 'اسکرین‌شات JPEG/PNG از همان صفحه همراه این گزارش است (فایل screenshot.jpg در ZIP یا پیوست ایمیل). تصویر را باز کن و ظاهر فیلدهای پرداخت، پیام خطا و بخش‌های خالی را با شرح کاربر مقایسه کن.';
		if ( $screenshot_url ) {
			$shot .= "\nنشانی اسکرین: " . $screenshot_url;
		} elseif ( empty( $data['screenshot'] ) ) {
			$shot = 'اسکرین‌شات در این گزارش ذخیره نشده؛ فقط شرح کاربر و لاگ کنسول را داری.';
		}

		$failed_lines = array();
		if ( $failed ) {
			foreach ( array_slice( $failed, 0, 25 ) as $i => $row ) {
				$failed_lines[] = ( $i + 1 ) . '. [' . ( ! empty( $row['payment'] ) ? 'پرداخت' : 'عمومی' ) . '] '
					. ( isset( $row['status'] ) ? $row['status'] : '' ) . ' — '
					. ( isset( $row['kind'] ) ? $row['kind'] : '' ) . ' — '
					. ( isset( $row['url'] ) ? $row['url'] : '' );
			}
		} else {
			$failed_lines[] = 'مورد لودنشده‌ای ثبت نشد.';
		}

		$error_lines = array();
		if ( $errors ) {
			foreach ( array_slice( $errors, 0, 15 ) as $row ) {
				$error_lines[] = '- ' . ( isset( $row['type'] ) ? $row['type'] : 'error' ) . ': '
					. ( isset( $row['message'] ) ? $row['message'] : '' )
					. ( ! empty( $row['source'] ) ? ' @ ' . $row['source'] : '' );
			}
		} else {
			$error_lines[] = 'خطای کنسولی ثبت نشد.';
		}

		$method_lines = array();
		if ( ! empty( $payment['methods'] ) && is_array( $payment['methods'] ) ) {
			foreach ( $payment['methods'] as $method ) {
				$method_lines[] = '- ' . ( isset( $method['label'] ) && $method['label'] ? $method['label'] : ( isset( $method['id'] ) ? $method['id'] : '' ) )
					. ( ! empty( $method['selected'] ) ? ' (انتخاب‌شده)' : '' );
			}
		} else {
			$method_lines[] = '- هیچ درگاه پرداختی در DOM دیده نشد.';
		}

		$field_lines = array();
		if ( ! empty( $payment['fields'] ) && is_array( $payment['fields'] ) ) {
			foreach ( array_slice( $payment['fields'], 0, 20 ) as $field ) {
				$state   = array();
				$state[] = ! empty( $field['visible'] ) ? 'نمایان' : 'مخفی';
				$state[] = ! empty( $field['empty'] ) ? 'خالی' : 'پر';
				if ( ! empty( $field['broken'] ) ) {
					$state[] = 'آسیب‌دیده';
				}
				$field_lines[] = '- ' . ( isset( $field['name'] ) && $field['name'] ? $field['name'] : ( isset( $field['id'] ) ? $field['id'] : '' ) )
					. ' [' . ( isset( $field['type'] ) ? $field['type'] : '' ) . '] — ' . implode( '، ', $state );
			}
		} else {
			$field_lines[] = '- فیلد پرداختی پیدا نشد.';
		}

		$pay_flags = array();
		$pay_flags[] = ! empty( $payment['foundPaymentBox'] ) ? 'باکس #payment پیدا شد' : 'باکس #payment پیدا نشد';
		if ( ! empty( $payment['emptyBox'] ) ) {
			$pay_flags[] = 'باکس خالی یا رندر نشده';
		}
		if ( ! empty( $payment['noMethods'] ) ) {
			$pay_flags[] = 'روش پرداختی لود نشد';
		}

		$lines   = array();
		$lines[] = 'تو یک ایجنت برنامه‌نویس هستی. کاربر در صفحه پرداخت سایت nahal.website به مشکل خورده.';
		$lines[] = 'این گزارش را همراه اسکرین‌شات همان صفحه بررسی کن، علت را پیدا کن و اصلاح مشخص بده.';
		$lines[] = '';
		$lines[] = '## نقش و زمینه';
		$lines[] = '- سایت: nahal.website — وردپرس + ووکامرس + درگاه دیجی‌پی';
		$lines[] = '- صفحات سفارشی تسویه: checkout-dore (دوره) / checkout-eyvand (ایونت) / checkout-ordoo (اردو) / checkout-mahsool (محصول)';
		$lines[] = '- تمرکز: فیلدهای کارت/پرداخت، اسکریپت و استایل لودنشده، خطاهای کنسول، ظاهر صفحه در اسکرین';
		$lines[] = '- کارت، CVV و رمز در گزارش ماسک شده‌اند؛ دنبال نشت اطلاعات پرداخت نباش';
		$lines[] = '';
		$lines[] = '## صفحه‌ای که در آن به مشکل خورده';
		$lines[] = '- عنوان: ' . ( $title ? $title : '—' );
		$lines[] = '- نشانی: ' . ( $page ? $page : '—' );
		$lines[] = '- زمان ثبت: ' . ( $when ? $when : '—' );
		$lines[] = '- مرورگر: ' . ( $ua ? $ua : '—' );
		$lines[] = '- ابعاد صفحه: ' . ( $view ? $view : '—' );
		if ( $online ) {
			$lines[] = '- وضعیت شبکه: ' . $online;
		}
		$lines[] = '- نوع تسویه: ' . ( $type ? $type : '—' );
		if ( $classes ) {
			$lines[] = '- کلاس‌های body: ' . $classes;
		}
		$lines[] = '';
		$lines[] = '## شرح خطایی که کاربر نوشته';
		$lines[] = $note ? $note : '(کاربر شرحی ننوشت)';
		$lines[] = '';
		$lines[] = '## اسکرین‌شات همان صفحه';
		$lines[] = $shot;
		$lines[] = '';
		$lines[] = '## کدها و فایل‌هایی که لود نشدند';
		$lines   = array_merge( $lines, $failed_lines );
		$lines[] = '';
		$lines[] = '## خطاهای کنسول و جاوااسکریپت';
		$lines   = array_merge( $lines, $error_lines );
		$lines[] = '';
		$lines[] = '## وضعیت باکس پرداخت';
		$lines[] = implode( '؛ ', $pay_flags );
		$lines[] = 'درگاه‌ها:';
		$lines   = array_merge( $lines, $method_lines );
		$lines[] = 'فیلدها:';
		$lines   = array_merge( $lines, $field_lines );
		$lines[] = '';
		$lines[] = '## کارهایی که باید انجام بدهی';
		$lines[] = '1. اسکرین را با شرح کاربر و خطاهای کنسول تطبیق بده؛ بگو روی صفحه چه چیزی دیده می‌شود و چه چیزی غایب است.';
		$lines[] = '2. مشخص کن کدام اسکریپت، استایل یا درخواست HTTP باعث خرابی فیلد/دکمه پرداخت شده (مسیر فایل وردپرس را بنویس).';
		$lines[] = '3. علت محتمل را بگو: ۴۰۴، ۴۰۰، CORS، مسدود بودن دامنه، ترتیب لود، تداخل افزونه/قالب، یا رندر نشدن درگاه.';
		$lines[] = '4. یک اصلاح عملی پیشنهاد بده (کدام فایل، کدام هوک/اسکریپت، چه تغییری).';
		$lines[] = '5. اگر اطلاعات کم است، دقیق بگو در پیشخوان یا روی همان URL چه چیزی را باید چک کرد.';

		$text = implode( "\n", $lines );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, 16000 );
		}
		return substr( $text, 0, 16000 );
	}

	/**
	 * @param array<string, mixed> $data Data.
	 * @return array<string, mixed>
	 */
	public static function public_json( array $data ) {
		$copy = $data;
		unset( $copy['screenshot'] );
		$copy['hasScreenshot'] = ! empty( $data['screenshot'] );
		$copy['summary']       = self::summary( $data );
		return $copy;
	}

	/**
	 * @param string $value Value.
	 * @return string
	 */
	private static function e( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
	}
}
