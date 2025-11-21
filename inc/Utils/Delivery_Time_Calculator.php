<?php
/**
 * Delivery_Time_Calculator — Calculadora de tempo estimado de entrega
 * @package VemComerCore
 */

namespace VC\Utils;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_Restaurant;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Delivery_Time_Calculator {
	/**
	 * Calcula tempo estimado de entrega.
	 *
	 * @param int   $restaurant_id ID do restaurante
	 * @param array $items Array de IDs de itens do cardápio
	 * @param float|null $lat Latitude do destino (opcional)
	 * @param float|null $lng Longitude do destino (opcional)
	 * @return array{prep_time: int, delivery_time: int, total_time: int, estimated_at: string}
	 */
	public static function calculate( int $restaurant_id, array $items, ?float $lat = null, ?float $lng = null ): array {
		// Tempo de preparo baseado nos itens
		$prep_time = self::calculate_prep_time( $restaurant_id, $items );

		// Tempo de entrega baseado na distância
		$delivery_time = 0;
		if ( $lat && $lng ) {
			$delivery_time = self::calculate_delivery_time( $restaurant_id, $lat, $lng );
		}

		// Aplicar multiplicador de horário de pico
		$peak_multiplier = self::get_peak_multiplier();
		$prep_time = (int) round( $prep_time * $peak_multiplier );

		$total_time = $prep_time + $delivery_time;

		// Calcular horário estimado de entrega
		$estimated_at = date( 'Y-m-d H:i:s', time() + ( $total_time * 60 ) );

		return [
			'prep_time'     => $prep_time,
			'delivery_time' => $delivery_time,
			'total_time'    => $total_time,
			'estimated_at'  => $estimated_at,
			'peak_multiplier' => $peak_multiplier,
		];
	}

	/**
	 * Calcula tempo de preparo baseado nos itens.
	 */
	private static function calculate_prep_time( int $restaurant_id, array $items ): int {
		if ( empty( $items ) ) {
			return 0;
		}

		$max_prep_time = 0;

		foreach ( $items as $item_id ) {
			$item_id = (int) $item_id;
			if ( ! $item_id ) {
				continue;
			}

			$item = get_post( $item_id );
			if ( ! $item || CPT_MenuItem::SLUG !== $item->post_type ) {
				continue;
			}

			// Verificar se item pertence ao restaurante
			$item_restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
			if ( $item_restaurant_id !== $restaurant_id ) {
				continue;
			}

			$prep_time = (int) get_post_meta( $item_id, '_vc_prep_time', true );
			if ( $prep_time > $max_prep_time ) {
				$max_prep_time = $prep_time;
			}
		}

		// Se nenhum item tem tempo de preparo, usar padrão de 20 minutos
		if ( $max_prep_time === 0 ) {
			$max_prep_time = 20;
		}

		return $max_prep_time;
	}

	/**
	 * Calcula tempo de entrega baseado na distância.
	 */
	private static function calculate_delivery_time( int $restaurant_id, float $lat, float $lng ): int {
		$restaurant_lat = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lat', true );
		$restaurant_lng = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lng', true );

		if ( ! $restaurant_lat || ! $restaurant_lng ) {
			return 15; // Padrão: 15 minutos se não houver coordenadas
		}

		$distance = vc_haversine_km( $restaurant_lat, $restaurant_lng, $lat, $lng );

		// Estimativa: 5 minutos por km (média de 12 km/h)
		$delivery_time = (int) round( $distance * 5 );

		// Mínimo de 10 minutos, máximo de 60 minutos
		$delivery_time = max( 10, min( 60, $delivery_time ) );

		return $delivery_time;
	}

	/**
	 * Obtém multiplicador de horário de pico.
	 * Horários de pico: 12:00-14:00 e 19:00-21:00
	 */
	private static function get_peak_multiplier(): float {
		$current_hour = (int) date( 'H' );
		$current_minute = (int) date( 'i' );
		$current_time = $current_hour + ( $current_minute / 60 );

		// Almoço: 12:00-14:00
		$lunch_peak = $current_time >= 12.0 && $current_time < 14.0;

		// Jantar: 19:00-21:00
		$dinner_peak = $current_time >= 19.0 && $current_time < 21.0;

		if ( $lunch_peak || $dinner_peak ) {
			return 1.5; // 50% mais tempo no horário de pico
		}

		return 1.0;
	}
}

