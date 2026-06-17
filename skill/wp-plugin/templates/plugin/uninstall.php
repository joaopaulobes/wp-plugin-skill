<?php
/**
 * Desinstalação. Só remove dados se o usuário tiver optado (setting delete_on_uninstall).
 *
 * @package {{PLUGIN_NAME}}
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$s = get_option( '{{prefix}}_settings', array() );
if ( empty( $s['delete_on_uninstall'] ) ) {
	return; // por padrão, preserva os dados.
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{{prefix}}_items" ); // phpcs:ignore
delete_option( '{{prefix}}_settings' );
delete_option( '{{prefix}}_db_version' );
delete_transient( '{{prefix}}_update_info' );
