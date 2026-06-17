<?php
/**
 * Auto-update via servidor próprio. Veja references/auto-update.md.
 * Troque {{UPDATE_HOST}} pelo seu servidor de updates.
 *
 * @package {{PLUGIN_NAME}}
 */

defined( 'ABSPATH' ) || exit;

class {{PREFIX}}_Updater {

	const JSON_URL  = 'https://{{UPDATE_HOST}}/updates/{{PLUGIN_SLUG}}.json';
	const CACHE_KEY = '{{prefix}}_update_info';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 0 );
	}

	private static function url() {
		return apply_filters( '{{prefix}}_update_json_url', self::JSON_URL );
	}

	private static function remote() {
		$c = get_transient( self::CACHE_KEY );
		if ( false !== $c ) {
			return is_array( $c ) ? $c : null;
		}
		$url = add_query_arg( 'nc', rawurlencode( {{PREFIX}}_VERSION ), self::url() );
		$res = wp_remote_get( $url, array( 'timeout' => 12, 'headers' => array( 'Accept' => 'application/json' ) ) );
		if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
			set_transient( self::CACHE_KEY, 'none', HOUR_IN_SECONDS );
			return null;
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) || empty( $data['version'] ) ) {
			set_transient( self::CACHE_KEY, 'none', HOUR_IN_SECONDS );
			return null;
		}
		set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
		return $data;
	}

	public static function check( $transient ) {
		if ( ! is_object( $transient ) || empty( $transient->checked ) ) {
			return $transient;
		}
		$info = self::remote();
		if ( ! $info ) {
			return $transient;
		}
		$base      = plugin_basename( {{PREFIX}}_FILE );
		$installed = isset( $transient->checked[ $base ] ) ? $transient->checked[ $base ] : {{PREFIX}}_VERSION;
		$obj       = array(
			'slug'         => {{PREFIX}}_SLUG,
			'plugin'       => $base,
			'new_version'  => $info['version'],
			'url'          => isset( $info['homepage'] ) ? $info['homepage'] : '',
			'package'      => isset( $info['download_url'] ) ? $info['download_url'] : '',
			'tested'       => isset( $info['tested'] ) ? $info['tested'] : '',
			'requires'     => isset( $info['requires'] ) ? $info['requires'] : '',
			'requires_php' => isset( $info['requires_php'] ) ? $info['requires_php'] : '',
		);
		if ( version_compare( $info['version'], $installed, '>' ) && ! empty( $obj['package'] ) ) {
			$transient->response[ $base ] = (object) $obj;
		} else {
			$transient->no_update[ $base ] = (object) $obj;
		}
		return $transient;
	}

	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || {{PREFIX}}_SLUG !== $args->slug ) {
			return $result;
		}
		$info = self::remote();
		if ( ! $info ) {
			return $result;
		}
		// Reescreve as imagens do JSON para os assets LOCAIS do plugin (sem hotlink/CDN/CSP).
		$remote_base = 'https://{{UPDATE_HOST}}/updates/img/';
		$local_base  = plugins_url( 'assets/img/', {{PREFIX}}_FILE );
		$loc         = function ( $h ) use ( $remote_base, $local_base ) {
			return is_string( $h ) ? str_replace( $remote_base, $local_base, $h ) : $h;
		};
		$sections = array_map( $loc, (array) ( isset( $info['sections'] ) ? $info['sections'] : array() ) );
		$banners  = array_map( $loc, (array) ( isset( $info['banners'] ) ? $info['banners'] : array() ) );

		return (object) array(
			'name'          => isset( $info['name'] ) ? $info['name'] : {{prefix}}_brand(),
			'slug'          => {{PREFIX}}_SLUG,
			'version'       => $info['version'],
			'author'        => isset( $info['author'] ) ? $info['author'] : '',
			'homepage'      => isset( $info['homepage'] ) ? $info['homepage'] : '',
			'requires'      => isset( $info['requires'] ) ? $info['requires'] : '',
			'tested'        => isset( $info['tested'] ) ? $info['tested'] : '',
			'requires_php'  => isset( $info['requires_php'] ) ? $info['requires_php'] : '',
			'last_updated'  => isset( $info['last_updated'] ) ? $info['last_updated'] : '',
			'download_link' => isset( $info['download_url'] ) ? $info['download_url'] : '',
			'sections'      => $sections,
			'banners'       => $banners,
		);
	}

	public static function clear_cache() {
		delete_transient( self::CACHE_KEY );
	}
}
