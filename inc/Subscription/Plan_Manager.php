<?php
/**
 * Plan_Manager — Gerenciador de planos e limites de assinatura
 * @package VemComerCore
 */

namespace VC\Subscription;

use VC\Model\CPT_SubscriptionPlan;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Plan_Manager {
	/**
	 * Obtém o plano atual de um restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return array|null Dados do plano ou null se não tiver plano
	 */
	public static function get_restaurant_plan( int $restaurant_id ): ?array {
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant ) {
			return null;
		}

		$user_id = (int) $restaurant->post_author;
		if ( ! $user_id ) {
			return null;
		}

		$plan_id = (int) get_user_meta( $user_id, 'vc_restaurant_subscription_plan_id', true );
		if ( $plan_id <= 0 ) {
			return null;
		}

		$plan = get_post( $plan_id );
		if ( ! $plan || CPT_SubscriptionPlan::SLUG !== $plan->post_type ) {
			return null;
		}

		// Verificar se plano está ativo
		$active = (bool) get_post_meta( $plan_id, '_vc_plan_active', true );
		if ( ! $active ) {
			return null;
		}

		// Verificar se assinatura não expirou
		$expires_at = get_user_meta( $user_id, 'vc_restaurant_subscription_expires_at', true );
		if ( $expires_at && strtotime( $expires_at ) < time() ) {
			return null;
		}

		return [
			'id'                        => $plan_id,
			'name'                      => get_the_title( $plan ),
			'monthly_price'             => (float) get_post_meta( $plan_id, '_vc_plan_monthly_price', true ),
			'max_menu_items'            => (int) get_post_meta( $plan_id, '_vc_plan_max_menu_items', true ),
			'max_modifiers_per_item'    => (int) get_post_meta( $plan_id, '_vc_plan_max_modifiers_per_item', true ),
			'advanced_analytics'        => (bool) get_post_meta( $plan_id, '_vc_plan_advanced_analytics', true ),
			'priority_support'          => (bool) get_post_meta( $plan_id, '_vc_plan_priority_support', true ),
			'features'                  => json_decode( get_post_meta( $plan_id, '_vc_plan_features', true ), true ) ?: [],
			'status'                    => get_user_meta( $user_id, 'vc_restaurant_subscription_status', true ) ?: 'active',
			'expires_at'                => $expires_at,
		];
	}

	/**
	 * Obtém limite de itens no cardápio do plano.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return int Limite (0 = ilimitado)
	 */
	public static function get_max_menu_items( int $restaurant_id ): int {
		$plan = self::get_restaurant_plan( $restaurant_id );
		if ( ! $plan ) {
			return 0; // Sem plano = ilimitado (ou pode retornar um limite padrão)
		}
		return $plan['max_menu_items'];
	}

	/**
	 * Obtém limite de modificadores por item do plano.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return int Limite (0 = ilimitado)
	 */
	public static function get_max_modifiers_per_item( int $restaurant_id ): int {
		$plan = self::get_restaurant_plan( $restaurant_id );
		if ( ! $plan ) {
			return 0; // Sem plano = ilimitado
		}
		return $plan['max_modifiers_per_item'];
	}

	/**
	 * Verifica se o plano tem analytics avançado.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return bool
	 */
	public static function has_advanced_analytics( int $restaurant_id ): bool {
		$plan = self::get_restaurant_plan( $restaurant_id );
		if ( ! $plan ) {
			return false;
		}
		return $plan['advanced_analytics'];
	}

	/**
	 * Verifica se o plano tem suporte prioritário.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @return bool
	 */
	public static function has_priority_support( int $restaurant_id ): bool {
		$plan = self::get_restaurant_plan( $restaurant_id );
		if ( ! $plan ) {
			return false;
		}
		return $plan['priority_support'];
	}

	/**
	 * Atribui um plano a um restaurante.
	 *
	 * @param int $restaurant_id ID do restaurante
	 * @param int $plan_id ID do plano
	 * @param string $status Status da assinatura (active, cancelled, expired)
	 * @param string|null $expires_at Data de expiração (formato Y-m-d H:i:s)
	 * @return bool
	 */
	public static function assign_plan( int $restaurant_id, int $plan_id, string $status = 'active', ?string $expires_at = null ): bool {
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant ) {
			return false;
		}

		$user_id = (int) $restaurant->post_author;
		if ( ! $user_id ) {
			return false;
		}

		update_user_meta( $user_id, 'vc_restaurant_subscription_plan_id', $plan_id );
		update_user_meta( $user_id, 'vc_restaurant_subscription_status', $status );

		if ( $expires_at ) {
			update_user_meta( $user_id, 'vc_restaurant_subscription_expires_at', $expires_at );
		} else {
			// Se não especificado, expira em 1 mês
			$expires_at = date( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
			update_user_meta( $user_id, 'vc_restaurant_subscription_expires_at', $expires_at );
		}

		return true;
	}
}

