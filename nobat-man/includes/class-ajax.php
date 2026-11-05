<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_Ajax {
	public static function init() {
		add_action( 'wp_ajax_nobat_man_month', array( __CLASS__, 'month' ) );
		add_action( 'wp_ajax_nopriv_nobat_man_month', array( __CLASS__, 'month' ) );
		add_action( 'wp_ajax_nobat_man_book', array( __CLASS__, 'book' ) );
		add_action( 'wp_ajax_nopriv_nobat_man_book', array( __CLASS__, 'book' ) );
	}

	public static function month() {
		check_ajax_referer( 'nobat_man', 'nonce' );
		$jy = isset( $_POST['year'] ) ? (int) $_POST['year'] : 1404;
		$jm = isset( $_POST['month'] ) ? (int) $_POST['month'] : 1;
		if ( $jm < 1 ) {
			$jm = 12;
			$jy--;
		}
		if ( $jm > 12 ) {
			$jm = 1;
			$jy++;
		}
		wp_send_json_success(
			array(
				'year'      => $jy,
				'month'     => $jm,
				'grid'      => Nobat_Man_Jalali::month_matrix( $jy, $jm ),
				'monthName' => Nobat_Man_Jalali::month_name( $jm ),
			)
		);
	}

	public static function book() {
		check_ajax_referer( 'nobat_man', 'nonce' );
		$s    = nobat_man_settings();
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$date = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';
		$start = isset( $_POST['start'] ) ? sanitize_text_field( wp_unslash( $_POST['start'] ) ) : '';
		$cat_id = isset( $_POST['category'] ) ? sanitize_key( wp_unslash( $_POST['category'] ) ) : '';
		$cat = Nobat_Man_Categories::get( $cat_id );
		if ( $name === '' || $phone === '' || $date === '' || $start === '' || ! $cat ) {
			wp_send_json_error( array( 'message' => 'لطفاً نام، تلفن، دسته، روز و ساعت را کامل کنید.' ) );
		}
		$code = 'NM' . strtoupper( wp_generate_password( 6, false, false ) );
		$answers = array();
		if ( isset( $_POST['answers'] ) && is_array( $_POST['answers'] ) ) {
			foreach ( $_POST['answers'] as $qid => $val ) {
				$answers[ sanitize_key( $qid ) ] = sanitize_textarea_field( wp_unslash( $val ) );
			}
		}
		$end_ts = strtotime( $start ) + ( (int) $s['duration'] * 60 );
		$end    = $end_ts ? gmdate( 'H:i', $end_ts ) : $start;
		$booking = array(
			'code'            => $code,
			'name'            => $name,
			'phone'           => $phone,
			'email'           => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'city'            => isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '',
			'gender'          => isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : '',
			'specialist'      => isset( $_POST['specialist'] ) ? sanitize_text_field( wp_unslash( $_POST['specialist'] ) ) : '',
			'date'            => $date,
			'start'           => $start,
			'end'             => $end,
			'category_id'     => $cat['id'],
			'category_title'  => $cat['title'],
			'answers'         => $answers,
			'price'           => (int) $s['price'],
			'created'         => time(),
		);
		$all   = nobat_man_bookings();
		$all[] = $booking;
		nobat_man_save_bookings( $all );

		$msg = strtr(
			$s['thank_you'],
			array(
				'{booking_code}'  => $code,
				'{jalali_date}'   => $date,
				'{start_time}'    => $start,
				'{end_time}'      => $end,
				'{customer_name}' => $name,
				'{price}'         => nobat_man_format_price( $s['price'] ),
				'{site_url}'      => home_url( '/' ),
				'{invoice_no}'    => $code,
			)
		);

		$pay_url = Nobat_Man_WooCommerce::maybe_checkout( $booking );
		wp_send_json_success(
			array(
				'message' => wp_kses_post( $msg ),
				'pay_url' => $pay_url,
				'code'    => $code,
			)
		);
	}
}
