<?php
/**
 * Availability_Helper — Helper para verificação de disponibilidade de restaurantes
 * @package VemComerCore
 */

namespace VC\Utils;

use VC\Model\CPT_Restaurant;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Availability_Helper {
	/**
	 * Cache duration em segundos (1 minuto)
	 */
	private const CACHE_DURATION = 60;

	/**
	 * Verifica disponibilidade de um restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @param array $context Contexto adicional (lat, lng para verificar raio)
	 * @return array{available: bool, reason: string|null, details: array}
	 */
	public static function check_availability( int $restaurant_id, array $context = [] ): array {
		// Verificar cache
		$cache_key = 'vc_availability_' . $restaurant_id;
		if ( ! empty( $context['lat'] ) && ! empty( $context['lng'] ) ) {
			$cache_key .= '_' . round( $context['lat'], 4 ) . '_' . round( $context['lng'], 4 );
		}
		$cached = get_transient( $cache_key );
		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type || 'publish' !== $restaurant->post_status ) {
			$result = [
				'available' => false,
				'reason'    => 'restaurant_not_found',
				'details'   => [],
			];
			return $result;
		}

		$details = [];
		$reasons = [];

		// Verificar se está aberto
		$is_open = Schedule_Helper::is_open( $restaurant_id );
		if ( ! $is_open ) {
			$reasons[] = 'closed';
			$next_open = Schedule_Helper::get_next_open_time( $restaurant_id );
			$details['next_open'] = $next_open;
		}

		// Verificar se oferece delivery (se contexto de entrega)
		if ( isset( $context['delivery'] ) && $context['delivery'] ) {
			$has_delivery = (bool) get_post_meta( $restaurant_id, '_vc_has_delivery', true );
			if ( ! $has_delivery ) {
				$reasons[] = 'no_delivery';
			} else {
				// Verificar se está dentro do raio (se coordenadas fornecidas)
				if ( ! empty( $context['lat'] ) && ! empty( $context['lng'] ) ) {
					$restaurant_lat = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lat', true );
					$restaurant_lng = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lng', true );
					$radius = (float) get_post_meta( $restaurant_id, '_vc_delivery_radius', true );

					if ( $restaurant_lat && $restaurant_lng && $radius > 0 ) {
						$distance = vc_haversine_km( $restaurant_lat, $restaurant_lng, (float) $context['lat'], (float) $context['lng'] );
						if ( $distance > $radius ) {
							$reasons[] = 'out_of_range';
							$details['distance'] = round( $distance, 2 );
							$details['radius'] = $radius;
						}
					}
				}
			}
		}

		$available = empty( $reasons );
		$reason = ! empty( $reasons ) ? $reasons[0] : null;

		$result = [
			'available' => $available,
			'reason'    => $reason,
			'details'   => $details,
		];

		// Cache por 1 minuto
		set_transient( $cache_key, $result, self::CACHE_DURATION );

		return $result;
	}
}

