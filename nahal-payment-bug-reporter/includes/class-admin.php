<?php
/**
 * Admin inbox and settings.
 *
 * @package NahalPaymentBugReporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NPBR_Admin {

	/** @var self|null */
	private static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_filter( 'plugin_action_links_' . NPBR_BASENAME, array( $this, 'action_links' ) );
		add_action( 'admin_post_npbr_save_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_npbr_test_telegram', array( $this, 'test_telegram' ) );
		add_action( 'admin_post_npbr_delete_report', array( $this, 'delete_report' ) );
		add_action( 'admin_post_npbr_set_status', array( $this, 'set_status' ) );
		add_action( 'admin_notices', array( $this, 'dashboard_notice' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 80 );
	}

	public function menu() {
		$cap   = $this->cap();
		$open  = class_exists( 'NPBR_Stats', false ) ? NPBR_Stats::open_count() : 0;
		$title = 'خطای پرداخت';
		if ( $open ) {
			$title .= ' <span class="awaiting-mod">' . (int) $open . '</span>';
		}
		add_menu_page(
			'گزارش خطای پرداخت',
			$title,
			$cap,
			'npbr-reports',
			array( $this, 'render_reports' ),
			'dashicons-warning',
			57
		);
		add_submenu_page(
			'npbr-reports',
			'گزارش‌های دریافتی',
			'گزارش‌ها',
			$cap,
			'npbr-reports',
			array( $this, 'render_reports' )
		);
		add_submenu_page(
			'npbr-reports',
			'گزارش هفتگی و ماهانه',
			'هفتگی / ماهانه',
			$cap,
			'npbr-digest',
			array( $this, 'render_digest' )
		);
		add_submenu_page(
			'npbr-reports',
			'تنظیمات گزارشگر',
			'تنظیمات',
			$cap,
			'npbr-settings',
			array( $this, 'render_settings' )
		);
	}

	/**
	 * @param string $hook Hook.
	 */
	public function assets( $hook ) {
		$hook = (string) $hook;
		if ( false === strpos( $hook, 'npbr-' ) && 'index.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'npbr-admin', NPBR_URL . 'assets/admin/admin.css', array(), NPBR_VERSION );
	}

	/**
	 * @param array<int, string> $links Links.
	 * @return array<int, string>
	 */
	public function action_links( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=npbr-settings' ) ) . '">تنظیمات</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=npbr-reports' ) ) . '">گزارش‌ها</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=npbr-digest' ) ) . '">هفتگی / ماهانه</a>';
		return $links;
	}

	public function render_reports() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		if ( class_exists( 'NPBR_Stats', false ) ) {
			NPBR_Stats::clear_unread();
		}
		$single = isset( $_GET['report'] ) ? (int) $_GET['report'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $single ) {
			$post = get_post( $single );
			if ( ! $post || 'npbr_report' !== $post->post_type ) {
				echo '<div class="notice notice-error"><p>گزارش پیدا نشد.</p></div>';
				return;
			}
			include NPBR_DIR . 'templates/page-report.php';
			return;
		}
		$args = array(
			'post_type'      => 'npbr_report',
			'post_status'    => array( 'private', 'publish', 'draft' ),
			'posts_per_page' => 40,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$filter = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'open' === $filter ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_npbr_status',
					'value'   => 'resolved',
					'compare' => '!=',
				),
				array(
					'key'     => '_npbr_status',
					'compare' => 'NOT EXISTS',
				),
			);
		} elseif ( 'resolved' === $filter ) {
			$args['meta_query'] = array(
				array(
					'key'   => '_npbr_status',
					'value' => 'resolved',
				),
			);
		}
		$query = new WP_Query( $args );
		include NPBR_DIR . 'templates/page-reports.php';
	}

	public function render_settings() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$settings = NPBR_Plugin::settings();
		$tg_test  = get_transient( 'npbr_tg_test_' . get_current_user_id() );
		if ( is_array( $tg_test ) ) {
			delete_transient( 'npbr_tg_test_' . get_current_user_id() );
		} else {
			$tg_test = null;
		}
		include NPBR_DIR . 'templates/page-settings.php';
	}

	public function save_settings() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_save_settings' );
		NPBR_Plugin::save_settings(
			array(
				'manager_email'       => isset( $_POST['manager_email'] ) ? sanitize_email( wp_unslash( $_POST['manager_email'] ) ) : '',
				'button_label'        => isset( $_POST['button_label'] ) ? sanitize_text_field( wp_unslash( $_POST['button_label'] ) ) : 'گزارش خطا',
				'show_on_checkout'    => ! empty( $_POST['show_on_checkout'] ),
				'show_on_order_pay'   => ! empty( $_POST['show_on_order_pay'] ),
				'show_on_cart'        => ! empty( $_POST['show_on_cart'] ),
				'show_on_shop'        => ! empty( $_POST['show_on_shop'] ),
				'show_on_product'     => ! empty( $_POST['show_on_product'] ),
				'show_on_thankyou'    => ! empty( $_POST['show_on_thankyou'] ),
				'show_sitewide'       => ! empty( $_POST['show_sitewide'] ),
				'show_near_payment'   => false,
				'show_near_cart'      => false,
				'show_floating'       => ! empty( $_POST['show_floating'] ),
				'require_description' => ! empty( $_POST['require_description'] ),
				'telegram_enabled'     => ! empty( $_POST['telegram_enabled'] ),
				'telegram_bot_token'   => isset( $_POST['telegram_bot_token'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_bot_token'] ) ) : '',
				'telegram_chat_id'     => isset( $_POST['telegram_chat_id'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_chat_id'] ) ) : '',
				'telegram_api_base'    => isset( $_POST['telegram_api_base'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_api_base'] ) ) : '',
				'telegram_http_proxy'  => isset( $_POST['telegram_http_proxy'] ) ? sanitize_text_field( wp_unslash( $_POST['telegram_http_proxy'] ) ) : '',
				'digest_email'         => ! empty( $_POST['digest_email'] ),
				'require_login'        => ! empty( $_POST['require_login'] ),
				'require_verified'     => ! empty( $_POST['require_verified'] ),
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-settings&saved=1' ) );
		exit;
	}

	public function test_telegram() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_test_telegram' );
		$result = class_exists( 'NPBR_Telegram', false ) ? NPBR_Telegram::test() : array(
			'ok'      => false,
			'message' => 'کلاس تلگرام بارگذاری نشد.',
		);
		set_transient( 'npbr_tg_test_' . get_current_user_id(), $result, 120 );
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-settings' ) );
		exit;
	}

	public function delete_report() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_delete_report' );
		$id = isset( $_POST['report_id'] ) ? (int) $_POST['report_id'] : 0;
		if ( $id ) {
			$shot = (int) get_post_meta( $id, '_npbr_screenshot_id', true );
			if ( $shot ) {
				wp_delete_attachment( $shot, true );
			}
			wp_delete_post( $id, true );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-reports&deleted=1' ) );
		exit;
	}

	public function set_status() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'npbr_set_status' );
		$id     = isset( $_POST['report_id'] ) ? (int) $_POST['report_id'] : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'open';
		if ( $id && class_exists( 'NPBR_Stats', false ) ) {
			NPBR_Stats::set_status( $id, $status );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=npbr-reports&report=' . $id . '&status_saved=1' ) );
		exit;
	}

	public function render_digest() {
		if ( ! current_user_can( $this->cap() ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		$kind = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : 'week'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $kind, array( 'week', 'month', 'last_month' ), true ) ) {
			$kind = 'week';
		}
		$now    = function_exists( 'current_time' ) ? (int) current_time( 'timestamp' ) : time();
		$period = NPBR_Stats::period( $kind, $now );
		$sum    = NPBR_Stats::summarize( NPBR_Stats::rows_between( $period['start'], $period['end'] ) );
		include NPBR_DIR . 'templates/page-digest.php';
	}

	public function dashboard_notice() {
		if ( ! current_user_can( $this->cap() ) || ! class_exists( 'NPBR_Stats', false ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && ! empty( $screen->id ) && 'dashboard' !== $screen->id ) {
			return;
		}
		$unread = NPBR_Stats::unread();
		$open   = NPBR_Stats::open_count();
		if ( ! $unread && ! $open ) {
			return;
		}
		$url = admin_url( 'admin.php?page=npbr-reports&status=open' );
		$msg = '';
		if ( $unread ) {
			$msg = sprintf( '%d گزارش خطای خرید جدید رسیده.', $unread );
		} elseif ( $open ) {
			$msg = sprintf( '%d گزارش خطای خرید هنوز باز است.', $open );
		}
		echo '<div class="notice notice-warning"><p><strong>خطای خرید نهال:</strong> ' . esc_html( $msg ) . '</p><p><a href="' . esc_url( $url ) . '">مشاهده فهرست</a> — <a href="' . esc_url( admin_url( 'admin.php?page=npbr-digest' ) ) . '">گزارش هفتگی و ماهانه</a></p></div>';
	}

	public function dashboard_widget() {
		if ( ! current_user_can( $this->cap() ) ) {
			return;
		}
		wp_add_dashboard_widget(
			'npbr_dashboard',
			'خطاهای خرید و پرداخت نهال',
			array( $this, 'render_dashboard_widget' )
		);
	}

	public function render_dashboard_widget() {
		$open  = class_exists( 'NPBR_Stats', false ) ? NPBR_Stats::open_count() : 0;
		$week  = class_exists( 'NPBR_Stats', false ) ? NPBR_Stats::period( 'week' ) : array();
		$sum   = ( $week && class_exists( 'NPBR_Stats', false ) ) ? NPBR_Stats::summarize( NPBR_Stats::rows_between( $week['start'], $week['end'] ) ) : array( 'total' => 0, 'resolved' => 0, 'issues' => array() );
		echo '<p>گزارش‌های باز: <strong>' . (int) $open . '</strong> — ۷ روز گذشته: <strong>' . (int) $sum['total'] . '</strong> ثبت، <strong>' . (int) $sum['resolved'] . '</strong> رفع‌شده.</p>';
		if ( ! empty( $sum['issues'] ) ) {
			echo '<ul>';
			foreach ( array_slice( $sum['issues'], 0, 5 ) as $issue ) {
				$state = ! empty( $issue['open'] ) ? 'باز' : 'رفع‌شده';
				echo '<li>' . esc_html( ( isset( $issue['label'] ) ? $issue['label'] : '' ) . ' (' . (int) $issue['count'] . ' — ' . $state . ')' ) . '</li>';
			}
			echo '</ul>';
		}
		echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=npbr-reports&status=open' ) ) . '">فهرست خطاها</a> ';
		echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=npbr-digest' ) ) . '">گزارش هفتگی و ماهانه</a></p>';
	}

	/**
	 * @param WP_Admin_Bar $bar Bar.
	 */
	public function admin_bar( $bar ) {
		if ( ! current_user_can( $this->cap() ) || ! is_object( $bar ) || ! method_exists( $bar, 'add_node' ) ) {
			return;
		}
		$open = class_exists( 'NPBR_Stats', false ) ? NPBR_Stats::open_count() : 0;
		if ( ! $open ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'npbr-errors',
				'title' => 'خطای خرید (' . (int) $open . ')',
				'href'  => admin_url( 'admin.php?page=npbr-reports&status=open' ),
				'meta'  => array( 'title' => 'گزارش‌های باز خطای خرید' ),
			)
		);
	}

	/**
	 * @return string
	 */
	private function cap() {
		return current_user_can( 'manage_woocommerce' ) ? 'manage_woocommerce' : 'manage_options';
	}
}
