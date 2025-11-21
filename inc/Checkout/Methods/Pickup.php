<?php
namespace VC\Checkout\Methods;

use VC\Checkout\FulfillmentMethod;
use VC\Utils\Schedule_Helper;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Método de fulfillment: Retirada (Pickup)
 * Frete: R$ 0,00
 * Não verifica raio de entrega
 * Verifica apenas se restaurante está aberto
 */
class Pickup implements FulfillmentMethod {
	public const SLUG = 'pickup';

	public function supports_order( array $order ): bool {
		$restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
		if ( ! $restaurant_id ) {
			return false;
		}

		// Pickup está sempre disponível se restaurante existe
		return true;
	}

	public function calculate_fee( array $order ): array {
		$restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );

		if ( ! $restaurant_id ) {
			return [
				'total'   => 0.0,
				'free'    => true,
				'label'   => __( 'Retirada', 'vemcomer' ),
				'details' => [],
			];
		}

		// Verificar se restaurante está aberto
		$is_open = Schedule_Helper::is_open( $restaurant_id );
		if ( ! $is_open ) {
			return [
				'total'   => 0.0,
				'free'    => true,
				'label'   => __( 'Retirada', 'vemcomer' ),
				'details' => [
					'available' => false,
					'reason'    => __( 'Restaurante está fechado', 'vemcomer' ),
				],
			];
		}

		return [
			'total'   => 0.0,
			'free'    => true,
			'label'   => __( 'Retirada', 'vemcomer' ),
			'details' => [
				'available' => true,
				'method'    => 'pickup',
			],
		];
	}
}

