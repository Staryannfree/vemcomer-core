<?php
/**
 * Coupon_Validator — Validador de cupons
 * @package VemComerCore
 */

namespace VC\Coupons;

use VC\Model\CPT_Coupon;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Coupon_Validator {
	/**
	 * Valida um cupom.
	 *
	 * @param string $code Código do cupom
	 * @param int    $restaurant_id ID do restaurante (opcional)
	 * @param float  $subtotal Subtotal do pedido
	 * @return array|WP_Error Array com dados do cupom ou WP_Error se inválido
	 */
	public static function validate( string $code, int $restaurant_id = 0, float $subtotal = 0.0 ): array|WP_Error {
		$code = strtoupper( trim( $code ) );
		if ( empty( $code ) ) {
			return new WP_Error( 'vc_coupon_empty', __( 'Código do cupom não pode estar vazio.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Buscar cupom pelo código
		$query = new \WP_Query( [
			'post_type'      => CPT_Coupon::SLUG,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => '_vc_coupon_code',
					'value' => $code,
				],
			],
		] );

		if ( empty( $query->posts ) ) {
			return new WP_Error( 'vc_coupon_not_found', __( 'Cupom não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$coupon = $query->posts[0];
		$coupon_id = $coupon->ID;

		// Verificar se não expirou
		$expires_at = get_post_meta( $coupon_id, '_vc_coupon_expires_at', true );
		if ( $expires_at && strtotime( $expires_at ) < time() ) {
			return new WP_Error( 'vc_coupon_expired', __( 'Cupom expirado.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Verificar uso máximo
		$max_uses = (int) get_post_meta( $coupon_id, '_vc_coupon_max_uses', true );
		$used_count = (int) get_post_meta( $coupon_id, '_vc_coupon_used_count', true );
		if ( $max_uses > 0 && $used_count >= $max_uses ) {
			return new WP_Error( 'vc_coupon_max_uses', __( 'Cupom atingiu o limite de uso.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Verificar se é válido para o restaurante
		$coupon_restaurant_id = (int) get_post_meta( $coupon_id, '_vc_coupon_restaurant_id', true );
		if ( $coupon_restaurant_id > 0 && $coupon_restaurant_id !== $restaurant_id ) {
			return new WP_Error( 'vc_coupon_invalid_restaurant', __( 'Cupom não é válido para este restaurante.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Calcular desconto
		$type = (string) get_post_meta( $coupon_id, '_vc_coupon_type', true );
		$value = (float) get_post_meta( $coupon_id, '_vc_coupon_value', true );

		$discount = 0.0;
		if ( 'percent' === $type ) {
			$discount = ( $subtotal * $value ) / 100;
		} elseif ( 'money' === $type ) {
			$discount = min( $value, $subtotal ); // Não pode descontar mais que o subtotal
		}

		return [
			'id'       => $coupon_id,
			'code'     => $code,
			'type'     => $type,
			'value'    => $value,
			'discount' => $discount,
		];
	}

	/**
	 * Registra uso do cupom.
	 *
	 * @param int $coupon_id ID do cupom
	 */
	public static function record_usage( int $coupon_id ): void {
		$used_count = (int) get_post_meta( $coupon_id, '_vc_coupon_used_count', true );
		update_post_meta( $coupon_id, '_vc_coupon_used_count', $used_count + 1 );
	}
}

