<?php
/**
 * Geocoding_Helper — Helper para geocodificação de endereços
 * @package VemComerCore
 */

namespace VC\Utils;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Geocoding_Helper {
	/**
	 * Cache duration em segundos (7 dias)
	 */
	private const CACHE_DURATION = 604800;

	/**
	 * Geocodifica um endereço (endereço -> lat/lng).
	 *
	 * @param string $address Endereço completo
	 * @return array|null Array com 'lat' e 'lng' ou null se falhar
	 */
	public static function geocode( string $address ): ?array {
		if ( empty( $address ) ) {
			return null;
		}

		// Verificar cache
		$cache_key = 'vc_geo_' . md5( $address );
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Tentar usar Google Maps API se disponível
		$api_key = get_option( 'vemcomer_google_maps_api_key' );
		if ( $api_key ) {
			$coords = self::geocode_google( $address, $api_key );
			if ( $coords ) {
				set_transient( $cache_key, $coords, self::CACHE_DURATION );
				return $coords;
			}
		}

		// Fallback: usar Nominatim (OpenStreetMap) - gratuito mas com rate limit
		$coords = self::geocode_nominatim( $address );
		if ( $coords ) {
			set_transient( $cache_key, $coords, self::CACHE_DURATION );
			return $coords;
		}

		return null;
	}

	/**
	 * Geocodifica usando Google Maps API.
	 */
	private static function geocode_google( string $address, string $api_key ): ?array {
		$url = add_query_arg( [
			'address' => urlencode( $address ),
			'key'     => $api_key,
		], 'https://maps.googleapis.com/maps/api/geocode/json' );

		$response = wp_remote_get( $url, [
			'timeout' => 5,
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! isset( $data['status'] ) || 'OK' !== $data['status'] ) {
			return null;
		}

		if ( empty( $data['results'] ) ) {
			return null;
		}

		$location = $data['results'][0]['geometry']['location'] ?? null;
		if ( ! $location ) {
			return null;
		}

		return [
			'lat' => (float) $location['lat'],
			'lng' => (float) $location['lng'],
		];
	}

	/**
	 * Geocodifica usando Nominatim (OpenStreetMap).
	 * Nota: Tem rate limit, use com moderação.
	 */
	private static function geocode_nominatim( string $address ): ?array {
		$url = add_query_arg( [
			'q'      => urlencode( $address ),
			'format' => 'json',
			'limit'  => 1,
		], 'https://nominatim.openstreetmap.org/search' );

		$response = wp_remote_get( $url, [
			'timeout' => 5,
			'headers' => [
				'User-Agent' => 'VemComer/1.0',
			],
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data ) ) {
			return null;
		}

		$result = $data[0] ?? null;
		if ( ! $result || ! isset( $result['lat'] ) || ! isset( $result['lon'] ) ) {
			return null;
		}

		return [
			'lat' => (float) $result['lat'],
			'lng' => (float) $result['lon'],
		];
	}
}

