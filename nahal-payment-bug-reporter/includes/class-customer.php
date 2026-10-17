<?php
/**
 * Logged-in / session customer details for support contact.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Customer {

	/**
	 * Safe subset for JS prefill (no order history).
	 *
	 * @return array<string, mixed>
	 */
	public static function preview() {
		$full = self::current();
		return array(
			'loggedIn'    => ! empty( $full['loggedIn'] ),
			'firstName'   => $full['firstName'],
			'lastName'    => $full['lastName'],
			'displayName' => $full['displayName'],
			'phone'       => $full['phone'],
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function current() {
		$out = array(
			'loggedIn'    => false,
			'userId'      => 0,
			'login'       => '',
			'email'       => '',
			'firstName'   => '',
			'lastName'    => '',
			'displayName' => '',
			'phone'       => '',
			'orders'      => array(),
		);

		if ( function_exists( 'WC' ) && WC() && isset( WC()->customer ) && WC()->customer ) {
			$cust = WC()->customer;
			if ( is_object( $cust ) && method_exists( $cust, 'get_billing_first_name' ) ) {
				$out['firstName'] = (string) $cust->get_billing_first_name();
				$out['lastName']  = (string) $cust->get_billing_last_name();
				$out['phone']     = (string) $cust->get_billing_phone();
				$out['email']     = (string) $cust->get_billing_email();
			}
		}

		if ( ! function_exists( 'is_user_logged_in' ) || ! is_user_logged_in() ) {
			return self::sanitize_out( $out );
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->ID ) {
			return self::sanitize_out( $out );
		}

		$uid                 = (int) $user->ID;
		$out['loggedIn']     = true;
		$out['userId']       = $uid;
		$out['login']        = (string) $user->user_login;
		$out['email']        = $out['email'] ? $out['email'] : (string) $user->user_email;
		$out['displayName']  = (string) $user->display_name;
		$first               = (string) get_user_meta( $uid, 'first_name', true );
		$last                = (string) get_user_meta( $uid, 'last_name', true );
		$out['firstName']    = $first ? $first : (string) get_user_meta( $uid, 'billing_first_name', true );
		$out['lastName']     = $last ? $last : (string) get_user_meta( $uid, 'billing_last_name', true );
		if ( ! $out['firstName'] ) {
			$out['firstName'] = (string) get_user_meta( $uid, 'billing_first_name', true );
		}
		if ( ! $out['lastName'] ) {
			$out['lastName'] = (string) get_user_meta( $uid, 'billing_last_name', true );
		}

		$phone = '';
		foreach ( array( 'digits_phone', 'digits_phone_no', 'billing_phone', 'shipping_phone', 'phone', 'mobile', 'account_phone' ) as $key ) {
			$val = (string) get_user_meta( $uid, $key, true );
			if ( $val ) {
				$phone = $val;
				break;
			}
		}
		if ( ! $phone ) {
			$phone = $out['phone'];
		}
		if ( ! $phone && preg_match( '/^0?9\d{9}$/', (string) $user->user_login ) ) {
			$phone = (string) $user->user_login;
		}
		$out['phone']  = $phone;
		$out['orders'] = self::recent_orders( $uid );

		return self::sanitize_out( $out );
	}

	/**
	 * Server identity wins for a logged-in shopper so the manager can call them.
	 *
	 * @param array<string, mixed> $data Normalized payload.
	 * @return array<string, mixed>
	 */
	public static function merge_into( array $data ) {
		$server = self::current();
		$client = isset( $data['customer'] ) && is_array( $data['customer'] ) ? $data['customer'] : array();
		if ( ! empty( $server['loggedIn'] ) || ! empty( $server['phone'] ) || ! empty( $server['firstName'] ) ) {
			$data['customer'] = $server;
		} else {
			$data['customer'] = $client;
		}

		$c = $data['customer'];
		$full = trim( ( isset( $c['firstName'] ) ? $c['firstName'] : '' ) . ' ' . ( isset( $c['lastName'] ) ? $c['lastName'] : '' ) );
		if ( empty( $data['name'] ) && $full ) {
			$data['name'] = $full;
		} elseif ( empty( $data['name'] ) && ! empty( $c['displayName'] ) ) {
			$data['name'] = $c['displayName'];
		}
		if ( empty( $data['phone'] ) && ! empty( $c['phone'] ) ) {
			$data['phone'] = $c['phone'];
		}
		if ( empty( $data['lastName'] ) && ! empty( $c['lastName'] ) ) {
			$data['lastName'] = $c['lastName'];
		}
		return $data;
	}

	/**
	 * @param int $user_id User id.
	 * @return array<int, array<string, string>>
	 */
	private static function recent_orders( $user_id ) {
		if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'limit'       => 8,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'return'      => 'objects',
			)
		);
		$out = array();
		if ( ! is_array( $orders ) ) {
			return $out;
		}
		foreach ( $orders as $order ) {
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_id' ) ) {
				continue;
			}
			$names = array();
			foreach ( $order->get_items() as $item ) {
				if ( count( $names ) >= 4 ) {
					break;
				}
				$names[] = $item->get_name() . ' × ' . $item->get_quantity();
			}
			$date = '';
			if ( method_exists( $order, 'get_date_created' ) && $order->get_date_created() ) {
				$date = $order->get_date_created()->date_i18n( 'Y-m-d H:i' );
			}
			$out[] = array(
				'id'     => (string) $order->get_id(),
				'number' => (string) $order->get_order_number(),
				'status' => (string) $order->get_status(),
				'total'  => wp_strip_all_tags( $order->get_formatted_order_total() ),
				'date'   => $date,
				'items'  => implode( '، ', $names ),
			);
		}
		return $out;
	}

	/**
	 * @param array<string, mixed> $out Raw.
	 * @return array<string, mixed>
	 */
	private static function sanitize_out( array $out ) {
		if ( class_exists( 'NPBR_Sanitizer', false ) ) {
			return NPBR_Sanitizer::customer( $out );
		}
		return $out;
	}
}
