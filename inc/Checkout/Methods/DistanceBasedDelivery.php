<?php
namespace VC\Checkout\Methods;

use VC\Checkout\FulfillmentMethod;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Método de fulfillment baseado em distância e bairro.
 * Calcula frete usando: base_price + (distance * price_per_km)
 * Verifica se está dentro do raio e se bairro tem preço especial.
 */
class DistanceBasedDelivery implements FulfillmentMethod {
	public const SLUG = 'distance_based_delivery';

	public function supports_order( array $order ): bool {
		$restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
		if ( ! $restaurant_id ) {
			return false;
		}

		// Verificar se o restaurante tem configuração de frete por distância
		$has_radius = get_post_meta( $restaurant_id, '_vc_delivery_radius', true ) !== '';
		$has_price_per_km = get_post_meta( $restaurant_id, '_vc_delivery_price_per_km', true ) !== '';
		$has_base_price = get_post_meta( $restaurant_id, '_vc_delivery_base_price', true ) !== '';

		// Precisa ter pelo menos raio ou preço por km configurado
		return $has_radius || $has_price_per_km || $has_base_price;
	}

	public function calculate_fee( array $order ): array {
		$restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
		$subtotal      = (float) ( $order['subtotal'] ?? 0 );

		if ( ! $restaurant_id ) {
			return [
				'total'   => 0.0,
				'free'    => false,
				'label'   => __( 'Entrega por distância', 'vemcomer' ),
				'details' => [],
			];
		}

		// Obter coordenadas do restaurante
		$restaurant_lat = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lat', true );
		$restaurant_lng = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lng', true );

		// Obter coordenadas do cliente (se fornecidas)
		$customer_lat = isset( $order['customer_lat'] ) ? (float) $order['customer_lat'] : null;
		$customer_lng = isset( $order['customer_lng'] ) ? (float) $order['customer_lng'] : null;
		$customer_address = isset( $order['customer_address'] ) ? sanitize_text_field( $order['customer_address'] ) : '';
		$customer_neighborhood = isset( $order['customer_neighborhood'] ) ? sanitize_text_field( $order['customer_neighborhood'] ) : '';

		// Verificar pedido mínimo
		$min_order = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_delivery_min_order', true ) );
		if ( $min_order > 0 && $subtotal < $min_order ) {
			return [
				'total'   => 0.0,
				'free'    => false,
				'label'   => __( 'Entrega por distância', 'vemcomer' ),
				'details' => [
					'error'     => true,
					'message'   => sprintf( __( 'Pedido mínimo de R$ %s para entrega.', 'vemcomer' ), number_format( $min_order, 2, ',', '.' ) ),
					'min_order' => $min_order,
				],
			];
		}

		// Verificar se bairro tem preço especial (prioridade sobre cálculo por distância)
		$neighborhoods_json = get_post_meta( $restaurant_id, '_vc_delivery_neighborhoods', true );
		if ( $neighborhoods_json && $customer_neighborhood ) {
			$neighborhoods = json_decode( $neighborhoods_json, true );
			if ( is_array( $neighborhoods ) && isset( $neighborhoods[ $customer_neighborhood ] ) ) {
				$neighborhood_config = $neighborhoods[ $customer_neighborhood ];
				$neighborhood_price = isset( $neighborhood_config['price'] ) ? (float) $neighborhood_config['price'] : 0.0;
				$neighborhood_free_above = isset( $neighborhood_config['free_above'] ) ? (float) $neighborhood_config['free_above'] : 0.0;

				// Verificar frete grátis por bairro
				if ( $neighborhood_free_above > 0 && $subtotal >= $neighborhood_free_above ) {
					return [
						'total'   => 0.0,
						'free'    => true,
						'label'   => __( 'Entrega por distância', 'vemcomer' ),
						'details' => [
							'method'        => 'neighborhood',
							'neighborhood'  => $customer_neighborhood,
							'free_above'    => $neighborhood_free_above,
						],
					];
				}

				// Retornar preço do bairro
				return [
					'total'   => $neighborhood_price,
					'free'    => false,
					'label'   => __( 'Entrega por distância', 'vemcomer' ),
					'details' => [
						'method'       => 'neighborhood',
						'neighborhood' => $customer_neighborhood,
						'price'        => $neighborhood_price,
					],
				];
			}
		}

		// Calcular distância se coordenadas disponíveis
		$distance = null;
		if ( $restaurant_lat && $restaurant_lng && $customer_lat && $customer_lng ) {
			if ( function_exists( 'vc_haversine_km' ) ) {
				$distance = vc_haversine_km( $restaurant_lat, $restaurant_lng, $customer_lat, $customer_lng );
			}
		}

		// Verificar raio máximo
		$radius = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_delivery_radius', true ) );
		if ( $radius > 0 && $distance !== null && $distance > $radius ) {
			return [
				'total'   => 0.0,
				'free'    => false,
				'label'   => __( 'Entrega por distância', 'vemcomer' ),
				'details' => [
					'error'    => true,
					'message'  => sprintf( __( 'Endereço fora do raio de entrega (máximo %s km).', 'vemcomer' ), number_format( $radius, 1, ',', '.' ) ),
					'distance' => round( $distance, 2 ),
					'radius'   => $radius,
				],
			];
		}

		// Obter configurações de preço
		$base_price = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_delivery_base_price', true ) );
		$price_per_km = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_delivery_price_per_km', true ) );

		// Calcular frete: base_price + (distance * price_per_km)
		$shipping_price = $base_price;
		if ( $distance !== null && $price_per_km > 0 ) {
			$shipping_price += ( $distance * $price_per_km );
		}

		// Verificar frete grátis acima de X
		$free_above = (float) str_replace( ',', '.', (string) get_post_meta( $restaurant_id, '_vc_delivery_free_above', true ) );
		if ( $free_above > 0 && $subtotal >= $free_above ) {
			return [
				'total'   => 0.0,
				'free'    => true,
				'label'   => __( 'Entrega por distância', 'vemcomer' ),
				'details' => [
					'method'     => 'distance',
					'distance'   => $distance !== null ? round( $distance, 2 ) : null,
					'free_above' => $free_above,
				],
			];
		}

		return [
			'total'   => max( 0.0, $shipping_price ),
			'free'    => false,
			'label'   => __( 'Entrega por distância', 'vemcomer' ),
			'details' => [
				'method'       => 'distance',
				'distance'     => $distance !== null ? round( $distance, 2 ) : null,
				'base_price'   => $base_price,
				'price_per_km' => $price_per_km,
				'calculated'   => $shipping_price,
			],
		];
	}

	public function get_eta( array $order ): ?string {
		$restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
		if ( ! $restaurant_id ) {
			return null;
		}

		// Obter ETA padrão do restaurante
		$eta = get_post_meta( $restaurant_id, '_vc_ship_eta', true );
		if ( $eta !== '' ) {
			return (string) $eta;
		}

		// Calcular ETA baseado em distância (se disponível)
		$restaurant_lat = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lat', true );
		$restaurant_lng = (float) get_post_meta( $restaurant_id, 'vc_restaurant_lng', true );
		$customer_lat = isset( $order['customer_lat'] ) ? (float) $order['customer_lat'] : null;
		$customer_lng = isset( $order['customer_lng'] ) ? (float) $order['customer_lng'] : null;

		if ( $restaurant_lat && $restaurant_lng && $customer_lat && $customer_lng ) {
			if ( function_exists( 'vc_haversine_km' ) ) {
				$distance = vc_haversine_km( $restaurant_lat, $restaurant_lng, $customer_lat, $customer_lng );
				// Estimativa: 5 minutos por km (média de entrega urbana)
				$minutes = (int) ceil( $distance * 5 );
				if ( $minutes > 0 ) {
					return sprintf( __( '%d min', 'vemcomer' ), $minutes );
				}
			}
		}

		return null;
	}
}

