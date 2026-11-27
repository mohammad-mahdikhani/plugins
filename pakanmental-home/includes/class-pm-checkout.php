<?php
/**
 * Checkout: shipping rates from the billing address, compact review controls.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Checkout {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_action( 'wp', array( __CLASS__, 'prime_customer_address' ), 20 );
		add_action( 'woocommerce_after_checkout_billing_form', array( __CLASS__, 'hidden_billing_country' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'fill_posted_country' ), 1 );
		add_filter( 'woocommerce_cart_shipping_packages', array( __CLASS__, 'packages' ) );
		add_filter( 'woocommerce_checkout_cart_item_quantity', array( __CLASS__, 'item_controls' ), 10, 3 );
		add_action( 'wc_ajax_pm_checkout_item', array( __CLASS__, 'ajax_item' ) );
		add_action( 'wc_ajax_nopriv_pm_checkout_item', array( __CLASS__, 'ajax_item' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'persian_fields' ), 99999 );
		add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );
		add_filter( 'woocommerce_package_rates', array( __CLASS__, 'neyshabur_pickup_only' ), 100, 2 );
		add_filter( 'woocommerce_cart_shipping_method_full_label', array( __CLASS__, 'shipping_label' ), 20, 2 );
		add_action( 'wp_footer', array( __CLASS__, 'shipping_groups' ), 30 );
		add_filter( 'gettext', array( __CLASS__, 'persian_strings' ), 20, 3 );
		add_action( 'template_redirect', array( __CLASS__, 'translate_optin' ) );
	}

	public static function prime_customer_address(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		self::copy_billing_onto_empty_shipping();
	}

	/**
	 * @param WC_Checkout $checkout Checkout.
	 */
	public static function hidden_billing_country( $checkout ): void {
		unset( $checkout );
		$fields = WC()->checkout() ? WC()->checkout()->get_checkout_fields( 'billing' ) : array();
		if ( isset( $fields['billing_country'] ) ) {
			return;
		}
		$country = WC()->customer ? WC()->customer->get_billing_country() : '';
		if ( ! $country ) {
			$country = 'IR';
		}
		echo '<input type="hidden" name="billing_country" id="billing_country" value="' . esc_attr( $country ) . '" />';
	}

	/**
	 * @param string $post_data Serialized checkout form.
	 */
	public static function fill_posted_country( $post_data ): void {
		if ( empty( $_POST['country'] ) ) {
			$_POST['country'] = 'IR';
		}
		$different = false;
		if ( is_string( $post_data ) && '' !== $post_data ) {
			$posted    = array();
			parse_str( $post_data, $posted );
			$different = ! empty( $posted['ship_to_different_address'] );
		}
		if ( $different ) {
			return;
		}
		$map = array(
			's_country'  => 'country',
			's_state'    => 'state',
			's_city'     => 'city',
			's_postcode' => 'postcode',
			's_address'  => 'address',
			's_address_2' => 'address_2',
		);
		foreach ( $map as $shipping_key => $billing_key ) {
			if ( empty( $_POST[ $shipping_key ] ) && ! empty( $_POST[ $billing_key ] ) ) {
				$_POST[ $shipping_key ] = wc_clean( wp_unslash( $_POST[ $billing_key ] ) );
			}
		}
	}

	/**
	 * @param array<int, array<string, mixed>> $packages Shipping packages.
	 * @return array<int, array<string, mixed>>
	 */
	public static function packages( array $packages ): array {
		if ( ! WC()->customer ) {
			return $packages;
		}
		$customer = WC()->customer;
		$country  = $customer->get_billing_country() ? $customer->get_billing_country() : 'IR';
		foreach ( $packages as &$package ) {
			if ( empty( $package['destination'] ) || ! is_array( $package['destination'] ) ) {
				continue;
			}
			if ( empty( $package['destination']['country'] ) ) {
				$package['destination']['country'] = $country;
			}
			if ( empty( $package['destination']['state'] ) && $customer->get_billing_state() ) {
				$package['destination']['state'] = $customer->get_billing_state();
			}
			if ( empty( $package['destination']['postcode'] ) && $customer->get_billing_postcode() ) {
				$package['destination']['postcode'] = $customer->get_billing_postcode();
			}
			if ( empty( $package['destination']['city'] ) && $customer->get_billing_city() ) {
				$package['destination']['city'] = $customer->get_billing_city();
			}
		}
		unset( $package );
		return $packages;
	}

	/**
	 * @param string              $html      Quantity HTML.
	 * @param array<string,mixed> $cart_item Cart item.
	 * @param string              $key       Cart key.
	 */
	public static function item_controls( string $html, array $cart_item, string $key ): string {
		unset( $html );
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$qty     = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 1;
		$min     = 1;
		$max     = 0;
		if ( $product instanceof WC_Product ) {
			$min = max( 1, (int) $product->get_min_purchase_quantity() );
			$max = (int) $product->get_max_purchase_quantity();
		}
		ob_start();
		?>
		<span class="pm-co-qty" data-key="<?php echo esc_attr( $key ); ?>">
			<button type="button" class="pm-co-qty__btn" data-pm-qty="-1" aria-label="کم کردن">−</button>
			<input class="pm-co-qty__input" type="number" inputmode="numeric" min="<?php echo esc_attr( (string) $min ); ?>" <?php echo $max > 0 ? 'max="' . esc_attr( (string) $max ) . '"' : ''; ?> value="<?php echo esc_attr( (string) $qty ); ?>" aria-label="تعداد" />
			<button type="button" class="pm-co-qty__btn" data-pm-qty="1" aria-label="افزودن">+</button>
			<button type="button" class="pm-co-qty__remove" data-pm-remove>حذف</button>
		</span>
		<?php
		return (string) ob_get_clean();
	}

	public static function ajax_item(): void {
		check_ajax_referer( 'pm-checkout-item', 'nonce' );
		if ( ! WC()->cart ) {
			wp_send_json_error();
		}
		$key = isset( $_POST['key'] ) ? wc_clean( wp_unslash( $_POST['key'] ) ) : '';
		$qty = isset( $_POST['qty'] ) ? (int) wp_unslash( $_POST['qty'] ) : 0;
		if ( '' === $key || ! WC()->cart->get_cart_item( $key ) ) {
			wp_send_json_error();
		}
		if ( $qty < 1 ) {
			WC()->cart->remove_cart_item( $key );
		} else {
			WC()->cart->set_quantity( $key, $qty, true );
		}
		WC()->cart->calculate_totals();
		wp_send_json_success(
			array(
				'count'    => WC()->cart->get_cart_contents_count(),
				'redirect' => WC()->cart->is_empty() ? wc_get_cart_url() : '',
			)
		);
	}

	/**
	 * @param array<string, array<string, array<string, mixed>>> $fields Checkout fields.
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public static function persian_fields( array $fields ): array {
		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = 'یادداشت سفارش';
			$fields['order']['order_comments']['placeholder'] = 'اگر توضیح خاصی برای ارسال دارید بنویسید.';
		}
		return $fields;
	}

	/**
	 * @param string $translated Translated text.
	 * @param string $text       Original text.
	 * @param string $domain     Text domain.
	 */
	public static function persian_strings( $translated, $text, $domain ): string {
		unset( $domain );
		if ( 'I would like to receive exclusive emails with discounts and product information' === $text ) {
			return 'می‌خواهم ایمیل تخفیف و محصولات جدید را دریافت کنم';
		}
		if ( 'Order Notes' === $text ) {
			return 'یادداشت سفارش';
		}
		if ( is_string( $text ) && str_starts_with( $text, 'Notes about your order' ) ) {
			return 'اگر توضیح خاصی برای ارسال دارید بنویسید.';
		}
		return is_string( $translated ) ? $translated : '';
	}

	public static function translate_optin(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url( 'order-received' ) ) {
			return;
		}
		ob_start(
			static function ( $html ) {
				return str_replace(
					'I would like to receive exclusive emails with discounts and product information',
					'می‌خواهم ایمیل تخفیف و محصولات جدید را دریافت کنم',
					(string) $html
				);
			}
		);
	}

	public static function enqueue(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		wp_enqueue_script(
			'pm-checkout',
			PM_HOME_URL . 'assets/js/pm-checkout.js',
			array( 'jquery' ),
			PM_HOME_VERSION,
			true
		);
		wp_localize_script(
			'pm-checkout',
			'pmCheckout',
			array(
				'nonce' => wp_create_nonce( 'pm-checkout-item' ),
			)
		);
	}

	/**
	 * Carrier name only. The group heading says whether it is freight-collect or the DigiExpress fee.
	 *
	 * @param string           $label  Full method label.
	 * @param WC_Shipping_Rate $method Rate.
	 */
	/**
	 * In-person pickup is only offered when the customer city is Neyshabur.
	 *
	 * @param array<string, WC_Shipping_Rate> $rates   Rates.
	 * @param array<string, mixed>            $package Package.
	 * @return array<string, WC_Shipping_Rate>
	 */
	public static function neyshabur_pickup_only( array $rates, array $package ): array {
		$city = isset( $package['destination']['city'] ) ? (string) $package['destination']['city'] : '';
		if ( self::is_neyshabur( $city ) ) {
			return $rates;
		}
		foreach ( $rates as $id => $rate ) {
			$name = wp_strip_all_tags( $rate->get_label() );
			if ( 'local_pickup' === $rate->get_method_id() || 'تحویل حضوری' === $name ) {
				unset( $rates[ $id ] );
			}
		}
		return $rates;
	}

	public static function is_neyshabur( string $city ): bool {
		$city = trim( wp_strip_all_tags( $city ) );
		$city = str_replace( array( 'ي', 'ك', 'ة', '‌', ' ' ), array( 'ی', 'ک', 'ه', '', '' ), $city );
		return 'نیشابور' === $city;
	}

	public static function shipping_label( $label, $method ): string {
		$name = wp_strip_all_tags( $method->get_label() );
		if ( 'تحویل حضوری' === $name ) {
			return 'تحویل حضوری (فقط نیشابور)';
		}
		if ( in_array( $name, array( 'تیپاکس', 'دکاپست', 'چاپار', 'دیجی اکسپرس' ), true ) ) {
			return esc_html( $name );
		}
		return $label;
	}

	public static function shipping_groups(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		?>
		<script id="pm-ship-groups">
		(function () {
			function group() {
				var list = document.querySelector("ul.woocommerce-shipping-methods");
				if (!list) return;
				list.querySelectorAll(".pm-ship-head").forEach(function (node) { node.remove(); });
				var freight = [];
				var paid = [];
				list.querySelectorAll("li").forEach(function (item) {
					if (item.classList.contains("pm-ship-head")) return;
					var text = item.textContent || "";
					if (text.indexOf("دیجی اکسپرس") !== -1) paid.push(item);
					else if (/تیپاکس|دکاپست|چاپار/.test(text)) freight.push(item);
				});
				function head(text, before) {
					var row = document.createElement("li");
					row.className = "pm-ship-head";
					row.textContent = text;
					before.parentNode.insertBefore(row, before);
				}
				if (freight[0]) head("پس کرایه", freight[0]);
				if (paid[0]) head("هزینه پست ۲۰۰٬۰۰۰ تومان", paid[0]);
			}
			if (window.jQuery) {
				window.jQuery(document.body).on("updated_checkout", group);
				window.jQuery(document.body).on("change", "#billing_city, #shipping_city", function () {
					window.jQuery(document.body).trigger("update_checkout");
				});
			}
			if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", group);
			else group();
		})();
		</script>
		<?php
	}

	private static function copy_billing_onto_empty_shipping(): void {
		$customer = WC()->customer;
		if ( ! $customer ) {
			return;
		}
		$country = $customer->get_billing_country() ? $customer->get_billing_country() : 'IR';
		$changed = false;
		if ( ! $customer->get_billing_country() ) {
			$customer->set_billing_country( $country );
			$changed = true;
		}
		if ( ! $customer->get_shipping_country() ) {
			$customer->set_shipping_country( $country );
			if ( ! $customer->get_shipping_state() && $customer->get_billing_state() ) {
				$customer->set_shipping_state( $customer->get_billing_state() );
			}
			if ( ! $customer->get_shipping_postcode() && $customer->get_billing_postcode() ) {
				$customer->set_shipping_postcode( $customer->get_billing_postcode() );
			}
			if ( ! $customer->get_shipping_city() && $customer->get_billing_city() ) {
				$customer->set_shipping_city( $customer->get_billing_city() );
			}
			$customer->set_calculated_shipping( true );
			$changed = true;
		}
		if ( $changed ) {
			$customer->save();
		}
	}
}
