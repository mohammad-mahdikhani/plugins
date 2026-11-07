<?php
/**
 * Admin: Elementor edit links for every site template.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Shell_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_ensure' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar' ), 100 );
	}

	public static function maybe_ensure(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( get_transient( 'pm_shell_templates_ready' ) ) {
			return;
		}
		PM_Shell_Templates::ensure_library_templates();
		set_transient( 'pm_shell_templates_ready', 1, DAY_IN_SECONDS );
	}

	public static function menu(): void {
		add_menu_page(
			__( 'قالب‌های المنتور پاکان', 'pakanmental-shell' ),
			__( 'قالب پاکان', 'pakanmental-shell' ),
			'edit_theme_options',
			'pm-shell-templates',
			array( __CLASS__, 'render' ),
			'dashicons-layout',
			58
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		if ( isset( $_POST['pm_shell_ensure'] ) && check_admin_referer( 'pm_shell_templates' ) ) {
			delete_transient( 'pm_shell_templates_ready' );
			PM_Shell_Templates::ensure_library_templates();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'قالب‌ها آماده شدند.', 'pakanmental-shell' ) . '</p></div>';
		}
		$ids = PM_Shell_Templates::all();
		?>
		<div class="wrap" dir="rtl">
			<h1><?php esc_html_e( 'قالب‌های المنتور سایت', 'pakanmental-shell' ); ?></h1>
			<p><?php esc_html_e( 'هر ردیف یک سند المنتور است. با «ویرایش با المنتور» همان قالبی که روی همهٔ صفحات آن نوع سوار است را عوض می‌کنید — نه برای هر محصول جدا.', 'pakanmental-shell' ); ?></p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'بخش', 'pakanmental-shell' ); ?></th>
						<th><?php esc_html_e( 'شناسه', 'pakanmental-shell' ); ?></th>
						<th><?php esc_html_e( 'عملیات', 'pakanmental-shell' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( PM_Shell_Templates::KEYS as $key => $label ) : ?>
					<?php
					$id  = (int) $ids[ $key ];
					$url = PM_Shell_Templates::edit_url( $id );
					?>
					<tr>
						<td><strong><?php echo esc_html( $label ); ?></strong></td>
						<td><?php echo $id ? (int) $id : '—'; ?></td>
						<td>
							<?php if ( $url ) : ?>
								<a class="button button-primary" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'ویرایش با المنتور', 'pakanmental-shell' ); ?></a>
							<?php else : ?>
								<span class="description"><?php esc_html_e( 'هنوز ساخته نشده', 'pakanmental-shell' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<form method="post" style="margin-top:1rem">
				<?php wp_nonce_field( 'pm_shell_templates' ); ?>
				<button type="submit" class="button" name="pm_shell_ensure" value="1"><?php esc_html_e( 'ساخت / تعمیر قالب‌های تک‌محصول و آرشیو', 'pakanmental-shell' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * @param WP_Admin_Bar $bar Admin bar.
	 */
	public static function admin_bar( $bar ): void {
		if ( ! current_user_can( 'edit_theme_options' ) || ! is_object( $bar ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'pm-shell-templates',
				'title' => 'قالب پاکان (المنتور)',
				'href'  => admin_url( 'admin.php?page=pm-shell-templates' ),
			)
		);

		$key = '';
		if ( is_singular( 'product' ) ) {
			$key = 'single_product';
		} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			$key = 'product_archive';
		} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
			$key = 'shop';
		} elseif ( is_home() || is_page( PM_Shell_Templates::id( 'blog' ) ) ) {
			$key = 'blog';
		}
		if ( ! $key ) {
			return;
		}
		$id  = PM_Shell_Templates::id( $key );
		$url = PM_Shell_Templates::edit_url( $id );
		if ( ! $url ) {
			return;
		}
		$bar->add_node(
			array(
				'id'     => 'pm-shell-edit-current',
				'parent' => 'pm-shell-templates',
				'title'  => 'ویرایش قالب این صفحه با المنتور',
				'href'   => $url,
				'meta'   => array( 'target' => '_blank' ),
			)
		);
	}
}
