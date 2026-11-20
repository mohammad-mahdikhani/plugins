<?php
defined( 'ABSPATH' ) || exit;

class NCK_Print {

	public static function hooks() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_print' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_order_metabox' ) );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'render_order_after_billing' ) );
		add_filter( 'woocommerce_order_actions', array( __CLASS__, 'order_actions' ) );
		add_action( 'woocommerce_order_action_nck_print_cowork', array( __CLASS__, 'handle_print_action' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'render_account_order' ) );
		add_filter( 'woocommerce_my_account_my_orders_actions', array( __CLASS__, 'account_order_actions' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'account_assets' ) );
	}

	public static function order_from_metabox( $post ) {
		if ( is_object( $post ) && is_a( $post, 'WC_Order' ) ) {
			return $post;
		}
		$id = 0;
		if ( is_object( $post ) && isset( $post->ID ) ) {
			$id = (int) $post->ID;
		} elseif ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$id = absint( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( $id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $id );
			return $order ? $order : null;
		}
		return null;
	}

	/**
	 * قرارداد امضاشدهٔ فضای کار برای یک سفارش ووکامرس.
	 *
	 * @param mixed $order
	 * @return array|null
	 */
	public static function cowork_contract_from_order( $order ) {
		if ( ! $order || ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return null;
		}
		$kind  = (string) $order->get_meta( '_nck_kind' );
		$token = (string) $order->get_meta( '_nck_print_token' );
		$cid   = (int) $order->get_meta( '_nck_contract_id' );
		$row   = null;
		if ( $token !== '' && class_exists( 'NCK_Contracts' ) ) {
			$row = NCK_Contracts::by_token( $token );
		}
		if ( ! $row && $cid && class_exists( 'NCK_Contracts' ) ) {
			$row = NCK_Contracts::get( $cid );
		}
		if ( ! $row || empty( $row['status'] ) || 'signed' !== $row['status'] ) {
			return null;
		}
		$ckind = class_exists( 'NCK_Contracts' ) ? NCK_Contracts::kind_of( $row ) : 'cowork';
		if ( 'cowork' !== $ckind && 'cowork' !== $kind ) {
			return null;
		}
		return $row;
	}

	public static function register_order_metabox() {
		$screens = array( 'shop_order', 'woocommerce_page_wc-orders' );
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}
		foreach ( array_unique( array_filter( $screens ) ) as $screen ) {
			add_meta_box(
				'nck-cowork-agreement',
				'توافق‌نامه فضای کار اشتراکی',
				array( __CLASS__, 'render_order_metabox' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	public static function render_order_metabox( $post ) {
		$order = self::order_from_metabox( $post );
		self::render_order_box( $order, true );
	}

	public static function render_order_after_billing( $order ) {
		self::render_order_box( $order, false );
	}

	public static function render_account_order( $order ) {
		$contract = self::cowork_contract_from_order( $order );
		if ( ! $contract ) {
			return;
		}
		include NCK_PATH . 'templates/account-order-agreement.php';
	}

	public static function account_order_actions( $actions, $order ) {
		if ( ! is_array( $actions ) ) {
			$actions = array();
		}
		$contract = self::cowork_contract_from_order( $order );
		if ( $contract && ! empty( $contract['print_token'] ) ) {
			$actions['nck_pdf'] = array(
				'url'  => NCK_Contracts::print_url( $contract['print_token'], true ),
				'name' => 'دانلود PDF توافق‌نامه',
			);
		}
		return $actions;
	}

	public static function account_assets() {
		if ( is_admin() ) {
			return;
		}
		$need = function_exists( 'is_account_page' ) && is_account_page();
		if ( ! $need && function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
			$need = true;
		}
		if ( ! $need ) {
			return;
		}
		if ( class_exists( 'NCK_Frontend' ) ) {
			if ( ! wp_style_is( 'nck-frontend', 'registered' ) ) {
				NCK_Frontend::register_assets();
			}
			wp_enqueue_style( 'nck-frontend' );
		}
	}

	public static function render_order_box( $order, $compact = false ) {
		if ( ! $order || ! method_exists( $order, 'get_meta' ) ) {
			return;
		}
		$kind     = (string) $order->get_meta( '_nck_kind' );
		$contract = self::cowork_contract_from_order( $order );
		if ( ! $contract && 'cowork' !== $kind ) {
			return;
		}
		include NCK_PATH . 'templates/admin-order-agreement.php';
	}

	public static function order_actions( $actions ) {
		if ( ! is_array( $actions ) ) {
			$actions = array();
		}
		$order = null;
		if ( isset( $GLOBALS['theorder'] ) && is_object( $GLOBALS['theorder'] ) ) {
			$order = $GLOBALS['theorder'];
		} elseif ( isset( $_GET['id'] ) && function_exists( 'wc_get_order' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = wc_get_order( absint( wp_unslash( $_GET['id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( self::cowork_contract_from_order( $order ) ) {
			$actions['nck_print_cowork'] = 'چاپ PDF توافق‌نامه فضای کار';
		}
		return $actions;
	}

	public static function handle_print_action( $order ) {
		$contract = self::cowork_contract_from_order( $order );
		if ( ! $contract || empty( $contract['print_token'] ) ) {
			return;
		}
		wp_safe_redirect( NCK_Contracts::print_url( $contract['print_token'], true ) );
		exit;
	}

	public static function admin_assets( $hook ) {
		$hook = (string) $hook;
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ok   = false !== strpos( $hook, 'shop_order' )
			|| false !== strpos( $hook, 'wc-orders' )
			|| 'wc-orders' === $page;
		if ( ! $ok ) {
			return;
		}
		wp_enqueue_style( 'nck-admin', NCK_URL . 'assets/css/admin.css', array(), NCK_VERSION );
	}

	public static function maybe_print() {
		if ( empty( $_GET['nck_print'] ) ) { // phpcs:ignore
			return;
		}
		$token    = sanitize_text_field( wp_unslash( $_GET['nck_print'] ) ); // phpcs:ignore
		$contract = NCK_Contracts::by_token( $token );
		if ( ! $contract || 'signed' !== $contract['status'] ) {
			wp_die( 'قرارداد پیدا نشد.', 'نهال', array( 'response' => 404 ) );
		}
		$kind = NCK_Contracts::kind_of( $contract );
		if ( 'playroom' === $kind ) {
			self::render_playroom( $contract );
		} elseif ( 'hall' === $kind ) {
			self::render_hall( $contract );
		} elseif ( 'learner' === $kind ) {
			self::render_learner( $contract );
		} elseif ( 'form' === $kind ) {
			self::render_form( $contract );
		} else {
			self::render( $contract );
		}
		exit;
	}

	public static function render( array $contract ) {
		$s    = NCK_Settings::all();
		$vars = NCK_Settings::contract_vars(
			array(
				'name'  => $contract['full_name'],
				'phone' => $contract['phone'],
				'title' => $contract['honorific'],
				'plan'  => $contract['plan'],
			)
		);
		if ( ! empty( $contract['signed_at'] ) ) {
			$g = substr( (string) $contract['signed_at'], 0, 10 );
			$dt = NCK_Jalali::from_g_date( $g );
			if ( $dt ) {
				$vars['date'] = NCK_Jalali::format_long( $dt['y'], $dt['m'], $dt['d'] );
			}
		}
		$intro    = NCK_Contract::fill_template( $s['contract_intro'], $vars );
		$preamble = NCK_Contract::fill_template( $s['contract_preamble'], $vars );
		$sections = array();
		foreach ( NCK_Settings::parse_sections() as $sec ) {
			$sections[] = array(
				'title' => NCK_Contract::fill_template( $sec['title'], $vars ),
				'body'  => NCK_Contract::fill_template( $sec['body'], $vars ),
			);
		}
		include NCK_PATH . 'templates/print-contract.php';
	}

	public static function render_hall( array $contract ) {
		$s       = NCK_Settings::all();
		$payload = NCK_Contracts::payload( $contract );
		$vars    = NCK_Hall::fill_vars( $payload, $s );
		$clauses = NCK_Hall::filled_clauses( $vars );
		$note    = NCK_Hall::projector_note( (int) $s['projector_price'], (int) $s['projector_minutes'] );
		include NCK_PATH . 'templates/print-hall.php';
	}

	public static function render_playroom( array $contract ) {
		$s       = NCK_Settings::all();
		$payload = NCK_Contracts::payload( $contract );
		$vars    = NCK_Playroom::fill_vars( $payload, $s );
		$clauses = NCK_Playroom::filled_clauses( $vars );
		include NCK_PATH . 'templates/print-playroom.php';
	}

	public static function render_learner( array $contract ) {
		$s = NCK_Settings::all();
		$p = NCK_Contracts::payload( $contract );
		include NCK_PATH . 'templates/print-learner.php';
	}

	public static function render_form( array $contract ) {
		$s = NCK_Settings::all();
		$p = NCK_Contracts::payload( $contract );
		include NCK_PATH . 'templates/print-form.php';
	}
}
