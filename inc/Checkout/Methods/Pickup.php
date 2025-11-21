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

	/**
	 * Retorna ETA (tempo estimado) para retirada.
	 * Para pickup, o ETA é baseado apenas no tempo de preparo dos itens.
	 *
	 * @param array $order Dados do pedido.
	 * @return string|null ETA em formato "X min" ou null.
	 */
	public function get_eta( array $order ): ?string {
		$restaurant_id = (int) ( $order['restaurant_id'] ?? 0 );
		if ( ! $restaurant_id ) {
			return null;
		}

		// Para retirada, o ETA é baseado apenas no tempo de preparo
		// Se houver itens no pedido, calcular tempo máximo de preparo
		$items = $order['items'] ?? [];
		if ( ! empty( $items ) && is_array( $items ) ) {
			$max_prep_time = 0;
			foreach ( $items as $item ) {
				$item_id = is_array( $item ) ? ( $item['id'] ?? 0 ) : (int) $item;
				if ( $item_id > 0 ) {
					$prep_time = (int) get_post_meta( $item_id, '_vc_prep_time', true );
					if ( $prep_time > $max_prep_time ) {
						$max_prep_time = $prep_time;
					}
				}
			}

			if ( $max_prep_time > 0 ) {
				return sprintf( __( '%d min', 'vemcomer' ), $max_prep_time );
			}
		}

		// Se não houver itens ou tempo de preparo, retornar padrão de 20 minutos
		return __( '20 min', 'vemcomer' );
	}
}

