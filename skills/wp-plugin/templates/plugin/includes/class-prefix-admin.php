<?php
/**
 * Camada de administração: menu, assets, página e handlers.
 *
 * @package {{PLUGIN_NAME}}
 */

defined( 'ABSPATH' ) || exit;

class {{PREFIX}}_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_{{prefix}}_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'wp_ajax_{{prefix}}_do', array( __CLASS__, 'ajax_do' ) );
	}

	public static function menu() {
		add_menu_page(
			{{prefix}}_brand(),
			{{prefix}}_brand(),
			{{prefix}}_capability(),
			{{PREFIX}}_SLUG,
			array( __CLASS__, 'page' ),
			'dashicons-admin-generic',
			58
		);
	}

	public static function assets( $hook ) {
		if ( strpos( (string) $hook, {{PREFIX}}_SLUG ) === false ) {
			return; // só nas telas do plugin
		}
		wp_enqueue_style( '{{prefix}}-admin', {{PREFIX}}_URL . 'assets/css/admin.css', array(), {{PREFIX}}_VERSION );
		wp_enqueue_script( '{{prefix}}-admin', {{PREFIX}}_URL . 'assets/js/admin.js', array(), {{PREFIX}}_VERSION, true );
		wp_localize_script(
			'{{prefix}}-admin',
			'{{PREFIX}}',
			array(
				'ajax'  => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( '{{prefix}}_ajax' ),
			)
		);
	}

	/** Página principal do plugin. */
	public static function page() {
		if ( ! current_user_can( {{prefix}}_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission.', '{{PLUGIN_SLUG}}' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( {{prefix}}_brand() ); ?></h1>

			<?php if ( isset( $_GET['msg'] ) && 'saved' === $_GET['msg'] ) : // phpcs:ignore ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Saved.', '{{PLUGIN_SLUG}}' ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="{{prefix}}_save">
				<?php wp_nonce_field( '{{prefix}}_save', '{{prefix}}_nonce' ); ?>
				<p>
					<label><?php esc_html_e( 'Title', '{{PLUGIN_SLUG}}' ); ?><br>
						<input type="text" name="title" class="regular-text" value="">
					</label>
				</p>
				<?php submit_button( __( 'Save', '{{PLUGIN_SLUG}}' ) ); ?>
			</form>
		</div>
		<?php
	}

	/** Handler de formulário (admin-post). */
	public static function handle_save() {
		if ( ! current_user_can( {{prefix}}_capability() ) ) {
			wp_die( esc_html__( 'You do not have permission.', '{{PLUGIN_SLUG}}' ) );
		}
		check_admin_referer( '{{prefix}}_save', '{{prefix}}_nonce' );

		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		// ... persistir $title (ex.: via {{PREFIX}}_Data) ...

		wp_safe_redirect( admin_url( 'admin.php?page=' . {{PREFIX}}_SLUG . '&msg=saved' ) );
		exit;
	}

	/** Handler de AJAX. */
	public static function ajax_do() {
		if ( ! current_user_can( {{prefix}}_capability() ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		check_ajax_referer( '{{prefix}}_ajax', 'nonce' );
		// ... trabalho ...
		wp_send_json_success( array( 'ok' => true ) );
	}
}
