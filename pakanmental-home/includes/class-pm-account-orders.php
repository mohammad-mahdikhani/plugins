<?php
/**
 * My Account order history: live list, edit address, delete/cancel.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Account_Orders {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_filter( 'update_post_metadata', array( __CLASS__, 'block_elementor_cache' ), 10, 3 );
		add_filter( 'add_post_metadata', array( __CLASS__, 'block_elementor_cache' ), 10, 3 );
		add_action( 'wp', array( __CLASS__, 'drop_stale_elementor_cache' ), 1 );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'order_actions' ), 20, 2 );
		add_action( 'template_redirect', array( __CLASS__, 'handle_requests' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'edit_form' ), 12 );
	}

	/**
	 * @param mixed  $check     Short-circuit value.
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key.
	 * @return mixed
	 */
	public static function block_elementor_cache( $check, $object_id, $meta_key ) {
		if ( '_elementor_element_cache' !== $meta_key ) {
			return $check;
		}
		if ( in_array( (int) $object_id, self::dynamic_page_ids(), true ) ) {
			return true;
		}
		return $check;
	}

	public static function drop_stale_elementor_cache(): void {
		if ( ! function_exists( 'is_account_page' ) || ! ( is_account_page() || is_cart() || is_checkout() ) ) {
			return;
		}
		$id = (int) get_queried_object_id();
		if ( $id && get_post_meta( $id, '_elementor_element_cache', true ) ) {
			delete_post_meta( $id, '_elementor_element_cache' );
		}
	}

	/**
	 * @param array<string, array{url:string, name:string}> $actions Row actions.
	 * @param WC_Order                                      $order   Order.
	 * @return array<string, array{url:string, name:string}>
	 */
	public static function order_actions( array $actions, $order ): array {
		if ( ! $order instanceof WC_Order || ! self::owns( $order ) ) {
			return $actions;
		}
		if ( self::can_edit( $order ) ) {
			$view = isset( $actions['view'] ) ? $actions['view'] : array(
				'url'  => $order->get_view_order_url(),
				'name' => 'ویرایش',
			);
			$view['name']       = 'ویرایش';
			$actions            = array( 'view' => $view ) + $actions;
		}
		unset( $actions['cancel'] );
		if ( self::can_delete( $order ) ) {
			$actions['pm_delete'] = array(
				'url'  => wp_nonce_url(
					add_query_arg( 'pm_delete_order', (string) $order->get_id(), wc_get_account_endpoint_url( 'orders' ) ),
					'pm_delete_order_' . $order->get_id()
				),
				'name' => 'حذف',
			);
		}
		return $actions;
	}

	public static function handle_requests(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		self::handle_delete();
		self::handle_edit();
	}

	/**
	 * @param WC_Order $order Order.
	 */
	public static function edit_form( $order ): void {
		if ( ! $order instanceof WC_Order || ! is_account_page() || ! self::can_edit( $order ) ) {
			return;
		}
		$states = WC()->countries->get_states( 'IR' );
		if ( ! is_array( $states ) ) {
			$states = array();
		}
		$state = $order->get_billing_state();
		?>
		<form class="pm-order-edit" method="post">
			<h3>ویرایش سفارش <?php echo esc_html( $order->get_order_number() ); ?></h3>
			<div class="pm-order-edit__grid">
				<label>نام
					<input type="text" name="pm_billing_first_name" value="<?php echo esc_attr( $order->get_billing_first_name() ); ?>" required />
				</label>
				<label>نام خانوادگی
					<input type="text" name="pm_billing_last_name" value="<?php echo esc_attr( $order->get_billing_last_name() ); ?>" required />
				</label>
				<label>استان
					<select name="pm_billing_state">
						<option value="">انتخاب استان</option>
						<?php foreach ( $states as $code => $label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $state, $code ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label>شهر
					<input type="text" name="pm_billing_city" value="<?php echo esc_attr( $order->get_billing_city() ); ?>" />
				</label>
				<label class="is-wide">آدرس
					<input type="text" name="pm_billing_address_1" value="<?php echo esc_attr( $order->get_billing_address_1() ); ?>" />
				</label>
				<label>کد پستی
					<input type="text" name="pm_billing_postcode" value="<?php echo esc_attr( $order->get_billing_postcode() ); ?>" />
				</label>
				<label>تلفن
					<input type="tel" name="pm_billing_phone" value="<?php echo esc_attr( $order->get_billing_phone() ); ?>" />
				</label>
				<label class="is-wide">یادداشت
					<textarea name="pm_customer_note" rows="2"><?php echo esc_textarea( $order->get_customer_note() ); ?></textarea>
				</label>
			</div>
			<?php wp_nonce_field( 'pm_edit_order_' . $order->get_id(), 'pm_edit_order_nonce' ); ?>
			<input type="hidden" name="pm_edit_order" value="<?php echo esc_attr( (string) $order->get_id() ); ?>" />
			<button type="submit" class="button">ذخیره ویرایش</button>
		</form>
		<?php
	}

	private static function handle_delete(): void {
		if ( empty( $_GET['pm_delete_order'] ) ) {
			return;
		}
		$order_id = absint( wp_unslash( $_GET['pm_delete_order'] ) );
		$nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		$redirect = wc_get_account_endpoint_url( 'orders' );
		if ( ! wp_verify_nonce( $nonce, 'pm_delete_order_' . $order_id ) ) {
			wc_add_notice( 'درخواست حذف معتبر نیست.', 'error' );
			wp_safe_redirect( $redirect );
			exit;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! self::can_delete( $order ) ) {
			wc_add_notice( 'این سفارش قابل حذف نیست.', 'error' );
			wp_safe_redirect( $redirect );
			exit;
		}
		if ( $order->has_status( 'cancelled' ) ) {
			$order->delete( false );
			wc_add_notice( 'سفارش از سابقه حذف شد.' );
		} else {
			$order->update_status( 'cancelled', 'لغو توسط مشتری از سابقه سفارش', true );
			wc_add_notice( 'سفارش لغو شد.' );
		}
		wp_safe_redirect( $redirect );
		exit;
	}

	private static function handle_edit(): void {
		if ( empty( $_POST['pm_edit_order'] ) ) {
			return;
		}
		$order_id = absint( wp_unslash( $_POST['pm_edit_order'] ) );
		$nonce    = isset( $_POST['pm_edit_order_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pm_edit_order_nonce'] ) ) : '';
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || ! wp_verify_nonce( $nonce, 'pm_edit_order_' . $order_id ) || ! self::can_edit( $order ) ) {
			wc_add_notice( 'ویرایش سفارش ممکن نیست.', 'error' );
			return;
		}
		$fields = array(
			'billing_first_name' => 'pm_billing_first_name',
			'billing_last_name'  => 'pm_billing_last_name',
			'billing_state'      => 'pm_billing_state',
			'billing_city'       => 'pm_billing_city',
			'billing_address_1'  => 'pm_billing_address_1',
			'billing_postcode'   => 'pm_billing_postcode',
			'billing_phone'      => 'pm_billing_phone',
		);
		foreach ( $fields as $prop => $key ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$order->{"set_$prop"}( $value );
		}
		if ( ! $order->get_billing_country() ) {
			$order->set_billing_country( 'IR' );
		}
		$note = isset( $_POST['pm_customer_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['pm_customer_note'] ) ) : '';
		$order->set_customer_note( $note );
		$order->save();
		wc_add_notice( 'سفارش ویرایش شد.' );
		wp_safe_redirect( $order->get_view_order_url() );
		exit;
	}

	private static function owns( WC_Order $order ): bool {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return false;
		}
		if ( (int) $order->get_customer_id() === $user_id ) {
			return true;
		}
		$user = get_userdata( $user_id );
		return $user instanceof WP_User && strtolower( $user->user_email ) === strtolower( $order->get_billing_email() );
	}

	private static function can_edit( WC_Order $order ): bool {
		if ( ! self::owns( $order ) || $order->has_status( 'refunded' ) ) {
			return false;
		}
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}
		return ! $order->has_status( array( 'completed' ) );
	}

	private static function can_delete( WC_Order $order ): bool {
		if ( ! self::owns( $order ) || $order->has_status( 'refunded' ) ) {
			return false;
		}
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}
		return $order->has_status( array( 'pending', 'failed', 'on-hold', 'cancelled' ) );
	}

	/**
	 * @return int[]
	 */
	private static function dynamic_page_ids(): array {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return array();
		}
		$ids = array(
			(int) wc_get_page_id( 'cart' ),
			(int) wc_get_page_id( 'checkout' ),
			(int) wc_get_page_id( 'myaccount' ),
		);
		return array_values( array_filter( $ids ) );
	}
}
