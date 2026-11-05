<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nobat_man_settings() {
	$defaults = array(
		'price'            => 350000,
		'duration'         => 45,
		'thank_you'        => 'نوبت شما با کد {booking_code} برای {jalali_date} ساعت {start_time} ثبت شد.',
		'working_days'     => array( 0, 1, 2, 3, 4 ),
		'slot_start'       => '09:00',
		'slot_end'         => '18:00',
		'holidays'         => '',
		'pro_license'      => '',
	);
	$saved = get_option( 'nobat_man_settings', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return array_merge( $defaults, $saved );
}

function nobat_man_specialists() {
	$items = get_option( 'nobat_man_specialists', array() );
	return is_array( $items ) ? $items : array();
}

function nobat_man_bookings() {
	$items = get_option( 'nobat_man_bookings', array() );
	return is_array( $items ) ? $items : array();
}

function nobat_man_save_bookings( $items ) {
	update_option( 'nobat_man_bookings', array_values( $items ), false );
}

function nobat_man_format_price( $amount ) {
	return number_format_i18n( (int) $amount ) . ' تومان';
}
