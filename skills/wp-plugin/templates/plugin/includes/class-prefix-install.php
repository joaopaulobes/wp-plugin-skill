<?php
/**
 * Instalação + migração de schema. Cria tabelas e options padrão.
 *
 * @package {{PLUGIN_NAME}}
 */

defined( 'ABSPATH' ) || exit;

class {{PREFIX}}_Install {

	/** Nome de tabela com prefixo do WP. */
	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . '{{prefix}}_' . $name;
	}

	/** Cria/atualiza tabelas e options. Chamado na ativação e na migração. */
	public static function run() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();

		$items = self::table( 'items' );
		dbDelta(
			"CREATE TABLE {$items} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				title VARCHAR(190) NOT NULL DEFAULT '',
				created_at DATETIME NULL DEFAULT NULL,
				updated_at DATETIME NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY title (title)
			) {$charset};"
		);

		add_option(
			'{{prefix}}_settings',
			array(
				'delete_on_uninstall' => 0,
			)
		);
		update_option( '{{prefix}}_db_version', {{PREFIX}}_DB_VERSION );
	}

	/** Roda a migração se a versão do schema mudou. */
	public static function maybe_upgrade() {
		if ( get_option( '{{prefix}}_db_version' ) !== {{PREFIX}}_DB_VERSION ) {
			self::run();
		}
	}

	/** Lê uma configuração com fallback. */
	public static function setting( $key, $default = '' ) {
		$s = get_option( '{{prefix}}_settings', array() );
		return isset( $s[ $key ] ) ? $s[ $key ] : $default;
	}
}
