<?php
/**
 * Coupons_Controller — REST endpoints para cupons
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Coupons\Coupon_Validator;
use VC\Model\CPT_Coupon;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Coupons_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// POST: Validar cupom
		register_rest_route( 'vemcomer/v1', '/coupons/validate', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'validate_coupon' ],
			'permission_callback' => '__return_true',
		] );

		// GET: Lista cupons (admin)
		register_rest_route( 'vemcomer/v1', '/coupons', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_coupons' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// POST: Criar cupom (admin)
		register_rest_route( 'vemcomer/v1', '/coupons', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_coupon' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function validate_coupon( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$code = sanitize_text_field( $request->get_param( 'code' ) );
		$restaurant_id = (int) $request->get_param( 'restaurant_id' );
		$subtotal = (float) $request->get_param( 'subtotal' );

		$result = Coupon_Validator::validate( $code, $restaurant_id, $subtotal );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}

	public function get_coupons( WP_REST_Request $request ): WP_REST_Response {
		$query = new WP_Query( [
			'post_type'      => CPT_Coupon::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );

		$coupons = [];
		foreach ( $query->posts as $post ) {
			$coupons[] = [
				'id'          => $post->ID,
				'code'        => (string) get_post_meta( $post->ID, '_vc_coupon_code', true ),
				'type'        => (string) get_post_meta( $post->ID, '_vc_coupon_type', true ),
				'value'       => (float) get_post_meta( $post->ID, '_vc_coupon_value', true ),
				'expires_at'  => (string) get_post_meta( $post->ID, '_vc_coupon_expires_at', true ),
				'max_uses'    => (int) get_post_meta( $post->ID, '_vc_coupon_max_uses', true ),
				'used_count'  => (int) get_post_meta( $post->ID, '_vc_coupon_used_count', true ),
				'restaurant_id' => (int) get_post_meta( $post->ID, '_vc_coupon_restaurant_id', true ) ?: null,
			];
		}

		return new WP_REST_Response( $coupons, 200 );
	}

	public function create_coupon( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$code = sanitize_text_field( $request->get_param( 'code' ) );
		$type = sanitize_key( $request->get_param( 'type' ) );
		$value = (float) $request->get_param( 'value' );
		$expires_at = sanitize_text_field( $request->get_param( 'expires_at' ) );
		$max_uses = (int) $request->get_param( 'max_uses' );
		$restaurant_id = (int) $request->get_param( 'restaurant_id' );

		if ( empty( $code ) ) {
			return new WP_Error( 'vc_missing_code', __( 'Código do cupom é obrigatório.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		$post_id = wp_insert_post( [
			'post_type'   => CPT_Coupon::SLUG,
			'post_title'  => $code,
			'post_status' => 'publish',
		], true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'vc_coupon_creation_failed', __( 'Erro ao criar cupom.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		update_post_meta( $post_id, '_vc_coupon_code', strtoupper( $code ) );
		update_post_meta( $post_id, '_vc_coupon_type', $type ?: 'percent' );
		update_post_meta( $post_id, '_vc_coupon_value', $value );
		if ( $expires_at ) {
			update_post_meta( $post_id, '_vc_coupon_expires_at', date( 'Y-m-d H:i:s', strtotime( $expires_at ) ) );
		}
		update_post_meta( $post_id, '_vc_coupon_max_uses', $max_uses );
		update_post_meta( $post_id, '_vc_coupon_used_count', 0 );
		if ( $restaurant_id > 0 ) {
			update_post_meta( $post_id, '_vc_coupon_restaurant_id', $restaurant_id );
		}

		return new WP_REST_Response( [ 'id' => $post_id ], 201 );
	}
}

