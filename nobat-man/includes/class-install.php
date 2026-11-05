<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Nobat_Man_Install {
	public static function activate() {
		if ( get_option( Nobat_Man_Categories::OPTION, null ) === null ) {
			update_option( Nobat_Man_Categories::OPTION, Nobat_Man_Categories::defaults(), false );
		}
		if ( get_option( 'nobat_man_settings', null ) === null ) {
			update_option( 'nobat_man_settings', nobat_man_settings(), false );
		}
		if ( get_option( 'nobat_man_specialists', null ) === null ) {
			update_option(
				'nobat_man_specialists',
				array(
					array(
						'id'    => 'sp_1',
						'name'  => 'مشاور پیش‌فرض',
						'bio'   => 'روانشناسی مثبت',
					),
				),
				false
			);
		}
		add_option( 'nobat_man_version', NOBAT_MAN_VERSION );
		update_option( 'nobat_man_version', NOBAT_MAN_VERSION );
	}
}
