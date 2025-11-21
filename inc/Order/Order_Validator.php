<?php
/**
 * Order_Validator — Validador de pedidos antes do WhatsApp
 * @package VemComerCore
 */

namespace VC\Order;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_ProductModifier;
use VC\Model\CPT_Restaurant;
use VC\Utils\Availability_Helper;
use VC\Utils\Schedule_Helper;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Order_Validator {
	/**
	 * Valida um pedido completo antes de gerar mensagem WhatsApp.
	 *
	 * @param array $order_data Dados do pedido
	 * @return array{valid: bool, errors: array}
	 */
	public static function validate( array $order_data ): array {
		$errors = [];

		$restaurant_id = (int) ( $order_data['restaurant_id'] ?? 0 );
		$items         = $order_data['items'] ?? [];
		$fulfillment   = $order_data['fulfillment'] ?? [];
		$customer_lat  = isset( $order_data['customer_lat'] ) ? (float) $order_data['customer_lat'] : null;
		$customer_lng  = isset( $order_data['customer_lng'] ) ? (float) $order_data['customer_lng'] : null;

		// Validar restaurante
		if ( ! $restaurant_id ) {
			$errors[] = __( 'Restaurante não especificado.', 'vemcomer' );
		} else {
			$restaurant = get_post( $restaurant_id );
			if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
				$errors[] = __( 'Restaurante não encontrado.', 'vemcomer' );
			} else {
				// Validar se está aberto
				if ( ! Schedule_Helper::is_open( $restaurant_id ) ) {
					$errors[] = __( 'Restaurante está fechado no momento.', 'vemcomer' );
				}

				// Validar disponibilidade geral
				$availability = Availability_Helper::check_availability( $restaurant_id, [
					'lat'      => $customer_lat,
					'lng'      => $customer_lng,
					'delivery' => ( $fulfillment['type'] ?? '' ) === 'delivery',
				] );

				if ( ! $availability['available'] ) {
					$reason = $availability['reason'] ?? 'unknown';
					$messages = [
						'closed'      => __( 'Restaurante está fechado.', 'vemcomer' ),
						'no_delivery' => __( 'Restaurante não oferece delivery.', 'vemcomer' ),
						'out_of_range' => __( 'Endereço fora da área de entrega.', 'vemcomer' ),
					];
					$errors[] = $messages[ $reason ] ?? __( 'Restaurante não disponível no momento.', 'vemcomer' );
				}
			}
		}

		// Validar itens
		if ( empty( $items ) || ! is_array( $items ) ) {
			$errors[] = __( 'Carrinho vazio.', 'vemcomer' );
		} else {
			foreach ( $items as $item ) {
				$item_id = (int) ( $item['id'] ?? 0 );
				if ( ! $item_id ) {
					$errors[] = __( 'Item do cardápio inválido.', 'vemcomer' );
					continue;
				}

				$menu_item = get_post( $item_id );
				if ( ! $menu_item || CPT_MenuItem::SLUG !== $menu_item->post_type ) {
					$errors[] = sprintf( __( 'Item #%d não encontrado.', 'vemcomer' ), $item_id );
					continue;
				}

				// Verificar se item está disponível
				$is_available = (bool) get_post_meta( $item_id, '_vc_is_available', true );
				if ( ! $is_available ) {
					$errors[] = sprintf( __( 'Item "%s" não está disponível.', 'vemcomer' ), get_the_title( $item_id ) );
				}

				// Verificar se item pertence ao restaurante
				$item_restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
				if ( $item_restaurant_id !== $restaurant_id ) {
					$errors[] = sprintf( __( 'Item "%s" não pertence a este restaurante.', 'vemcomer' ), get_the_title( $item_id ) );
				}

				// Validar modificadores obrigatórios
				$modifiers = $item['modifiers'] ?? [];
				$modifier_ids = array_map( 'absint', $modifiers );

				// Buscar modificadores obrigatórios do item
				$required_modifiers = get_posts( [
					'post_type'      => CPT_ProductModifier::SLUG,
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'meta_query'     => [
						[
							'key'     => '_vc_modifier_menu_items',
							'value'   => (string) $item_id,
							'compare' => 'LIKE',
						],
						[
							'key'   => '_vc_modifier_required',
							'value' => '1',
						],
					],
				] );

				foreach ( $required_modifiers as $required_mod ) {
					if ( ! in_array( $required_mod->ID, $modifier_ids, true ) ) {
						$errors[] = sprintf( __( 'Modificador obrigatório "%s" não foi selecionado para "%s".', 'vemcomer' ), get_the_title( $required_mod->ID ), get_the_title( $item_id ) );
					}
				}

				// Validar limites de modificadores (min/max)
				foreach ( $required_modifiers as $required_mod ) {
					$mod_group = get_post_meta( $required_mod->ID, '_vc_modifier_group', true );
					if ( $mod_group ) {
						// Contar modificadores do mesmo grupo selecionados
						$group_selected = 0;
						foreach ( $modifier_ids as $mod_id ) {
							$mod_group_check = get_post_meta( $mod_id, '_vc_modifier_group', true );
							if ( $mod_group_check === $mod_group ) {
								$group_selected++;
							}
						}

						$min_selections = (int) get_post_meta( $required_mod->ID, '_vc_modifier_min_selections', true );
						$max_selections = (int) get_post_meta( $required_mod->ID, '_vc_modifier_max_selections', true );

						if ( $min_selections > 0 && $group_selected < $min_selections ) {
							$errors[] = sprintf( __( 'Grupo "%s" requer no mínimo %d seleção(ões) para "%s".', 'vemcomer' ), $mod_group, $min_selections, get_the_title( $item_id ) );
						}
						if ( $max_selections > 0 && $group_selected > $max_selections ) {
							$errors[] = sprintf( __( 'Grupo "%s" permite no máximo %d seleção(ões) para "%s".', 'vemcomer' ), $mod_group, $max_selections, get_the_title( $item_id ) );
						}
					}
				}
			}
		}

		// Validar pedido mínimo
		if ( $restaurant_id > 0 ) {
			$min_order = (float) get_post_meta( $restaurant_id, '_vc_delivery_min_order', true );
			if ( $min_order > 0 ) {
				$subtotal = (float) ( $order_data['subtotal'] ?? 0 );
				if ( $subtotal < $min_order ) {
					$errors[] = sprintf( __( 'Pedido mínimo de R$ %s não atingido.', 'vemcomer' ), number_format( $min_order, 2, ',', '.' ) );
				}
			}
		}

		// Validar se está dentro do raio (se delivery)
		if ( ( $fulfillment['type'] ?? '' ) === 'delivery' ) {
			if ( ! $customer_lat || ! $customer_lng ) {
				$errors[] = __( 'Coordenadas do cliente necessárias para delivery.', 'vemcomer' );
			}
		}

		return [
			'valid'  => empty( $errors ),
			'errors' => $errors,
		];
	}
}

