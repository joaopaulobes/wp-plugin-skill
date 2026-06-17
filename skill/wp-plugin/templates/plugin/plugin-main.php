<?php
/**
 * Plugin Name:       {{PLUGIN_NAME}}
 * Plugin URI:        {{AUTHOR_URI}}/{{PLUGIN_SLUG}}
 * Description:       {{DESCRIPTION}}
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            {{AUTHOR}}
 * Author URI:        {{AUTHOR_URI}}
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       {{PLUGIN_SLUG}}
 * Domain Path:       /languages
 *
 * ATENÇÃO (skill /wp-plugin): renomeie este arquivo para {{PLUGIN_SLUG}}.php
 */

defined( 'ABSPATH' ) || exit;

/* Constantes ------------------------------------------------------------- */
define( '{{PREFIX}}_VERSION', '1.0.0' );
define( '{{PREFIX}}_DB_VERSION', '1.0.0' );
define( '{{PREFIX}}_FILE', __FILE__ );
define( '{{PREFIX}}_DIR', plugin_dir_path( __FILE__ ) );
define( '{{PREFIX}}_URL', plugin_dir_url( __FILE__ ) );
define( '{{PREFIX}}_SLUG', '{{PLUGIN_SLUG}}' );
define( '{{PREFIX}}_BRAND_DEFAULT', '{{PLUGIN_NAME}}' );

/* Helpers de marca / permissão (filtráveis → white-label) ---------------- */
function {{prefix}}_brand() {
	return apply_filters( '{{prefix}}_brand', {{PREFIX}}_BRAND_DEFAULT );
}
function {{prefix}}_capability() {
	return apply_filters( '{{prefix}}_capability', 'manage_options' );
}

/* Autoloader das classes {{PREFIX}}_* ------------------------------------ */
spl_autoload_register(
	function ( $class ) {
		if ( strpos( $class, '{{PREFIX}}_' ) !== 0 ) {
			return;
		}
		$file = {{PREFIX}}_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

/* Ativação / Desativação ------------------------------------------------- */
register_activation_hook(
	__FILE__,
	function () {
		{{PREFIX}}_Install::run();
		flush_rewrite_rules();
	}
);
register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

/* Bootstrap -------------------------------------------------------------- */
add_action(
	'plugins_loaded',
	function () {
		{{PREFIX}}_Install::maybe_upgrade();
		load_plugin_textdomain( '{{PLUGIN_SLUG}}', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		// Front-end sempre. Descomente conforme o escopo:
		// {{PREFIX}}_Front::init();

		if ( is_admin() ) {
			{{PREFIX}}_Admin::init();
			{{PREFIX}}_Updater::init();   // remova se não for usar auto-update
		}
	}
);
