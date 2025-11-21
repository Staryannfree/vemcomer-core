<?php
/**
 * Subscription_Controller — REST endpoints para planos de assinatura
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_SubscriptionPlan;
use VC\Subscription\Plan_Manager;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Subscription_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista planos disponíveis (público)
		register_rest_route( 'vemcomer/v1', '/subscription/plans', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_plans' ],
			'permission_callback' => '__return_true',
		] );

		// GET: Plano atual do restaurante (requer autenticação)
		register_rest_route( 'vemcomer/v1', '/subscription/current', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_current_plan' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'restaurant_id' => [
					'required'          => false,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// POST: Upgrade de plano (admin)
		register_rest_route( 'vemcomer/v1', '/subscription/upgrade', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'upgrade_plan' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'restaurant_id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'plan_id'       => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'status'        => [
					'required'          => false,
					'default'           => 'active',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'expires_at'    => [
					'required'          => false,
					'validate_callback' => function( $param ) {
						return empty( $param ) || strtotime( $param ) !== false;
					},
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );
	}

	public function check_authenticated(): bool {
		return is_user_logged_in();
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * GET /wp-json/vemcomer/v1/subscription/plans
	 * Lista planos disponíveis
	 */
	public function get_plans( WP_REST_Request $request ): WP_REST_Response {
		$query = new WP_Query( [
			'post_type'      => CPT_SubscriptionPlan::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => '_vc_plan_active',
					'value' => '1',
				],
			],
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_vc_plan_monthly_price',
			'order'          => 'ASC',
		] );

		$plans = [];
		foreach ( $query->posts as $post ) {
			$plans[] = [
				'id'                        => $post->ID,
				'name'                      => get_the_title( $post ),
				'description'               => $post->post_content,
				'monthly_price'             => (float) get_post_meta( $post->ID, '_vc_plan_monthly_price', true ),
				'max_menu_items'            => (int) get_post_meta( $post->ID, '_vc_plan_max_menu_items', true ),
				'max_modifiers_per_item'    => (int) get_post_meta( $post->ID, '_vc_plan_max_modifiers_per_item', true ),
				'advanced_analytics'        => (bool) get_post_meta( $post->ID, '_vc_plan_advanced_analytics', true ),
				'priority_support'          => (bool) get_post_meta( $post->ID, '_vc_plan_priority_support', true ),
				'features'                  => json_decode( get_post_meta( $post->ID, '_vc_plan_features', true ), true ) ?: [],
			];
		}

		return new WP_REST_Response( $plans, 200 );
	}

	/**
	 * GET /wp-json/vemcomer/v1/subscription/current
	 * Retorna plano atual do restaurante
	 */
	public function get_current_plan( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error( 'vc_unauthorized', __( 'Você precisa estar autenticado.', 'vemcomer' ), [ 'status' => 401 ] );
		}

		$restaurant_id = $request->get_param( 'restaurant_id' );
		if ( ! $restaurant_id ) {
			// Tentar obter restaurante do usuário
			$restaurant = get_posts( [
				'post_type'      => CPT_Restaurant::SLUG,
				'posts_per_page' => 1,
				'author'         => $user_id,
				'post_status'    => 'any',
			] );

			if ( empty( $restaurant ) ) {
				return new WP_Error( 'vc_no_restaurant', __( 'Nenhum restaurante encontrado para este usuário.', 'vemcomer' ), [ 'status' => 404 ] );
			}

			$restaurant_id = $restaurant[0]->ID;
		}

		$plan = Plan_Manager::get_restaurant_plan( (int) $restaurant_id );

		if ( ! $plan ) {
			return new WP_REST_Response( [
				'has_plan' => false,
				'plan'     => null,
			], 200 );
		}

		return new WP_REST_Response( [
			'has_plan' => true,
			'plan'     => $plan,
		], 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/subscription/upgrade
	 * Atribui/atualiza plano de um restaurante (admin)
	 */
	public function upgrade_plan( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'restaurant_id' );
		$plan_id       = (int) $request->get_param( 'plan_id' );
		$status        = $request->get_param( 'status' );
		$expires_at    = $request->get_param( 'expires_at' );

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Verificar se plano existe e está ativo
		$plan = get_post( $plan_id );
		if ( ! $plan || CPT_SubscriptionPlan::SLUG !== $plan->post_type ) {
			return new WP_Error( 'vc_plan_not_found', __( 'Plano não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$plan_active = (bool) get_post_meta( $plan_id, '_vc_plan_active', true );
		if ( ! $plan_active ) {
			return new WP_Error( 'vc_plan_inactive', __( 'Plano não está ativo.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Atribuir plano
		$success = Plan_Manager::assign_plan( $restaurant_id, $plan_id, $status, $expires_at );

		if ( ! $success ) {
			return new WP_Error( 'vc_upgrade_failed', __( 'Erro ao atribuir plano.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		$plan_data = Plan_Manager::get_restaurant_plan( $restaurant_id );

		log_event( 'Subscription upgraded', [
			'restaurant_id' => $restaurant_id,
			'plan_id'       => $plan_id,
			'status'        => $status,
		], 'info' );

		return new WP_REST_Response( [
			'success' => true,
			'plan'    => $plan_data,
		], 200 );
	}
}

