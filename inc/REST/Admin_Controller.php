<?php
/**
 * Admin_Controller — REST endpoints para super admin
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Restaurant;
use VC\Subscription\Plan_Manager;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Admin_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista todos restaurantes
		register_rest_route( 'vemcomer/v1', '/admin/restaurants', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_restaurants' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// POST: Alterar plano de assinatura
		register_rest_route( 'vemcomer/v1', '/admin/restaurants/(?P<id>\d+)/subscription', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_subscription' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// GET: Dashboard geral
		register_rest_route( 'vemcomer/v1', '/admin/dashboard', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_dashboard' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );
	}

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_restaurants( WP_REST_Request $request ): WP_REST_Response {
		$query = new WP_Query( [
			'post_type'      => CPT_Restaurant::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'any',
		] );

		$restaurants = [];
		foreach ( $query->posts as $post ) {
			$owner_id = (int) $post->post_author;
			$plan = Plan_Manager::get_restaurant_plan( $post->ID );

			$restaurants[] = [
				'id'          => $post->ID,
				'title'       => get_the_title( $post ),
				'status'      => $post->post_status,
				'owner_id'    => $owner_id,
				'owner_email' => $owner_id > 0 ? get_userdata( $owner_id )->user_email : null,
				'plan'        => $plan,
				'created_at'  => $post->post_date,
			];
		}

		return new WP_REST_Response( $restaurants, 200 );
	}

	public function update_subscription( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'id' );
		$plan_id = (int) $request->get_param( 'plan_id' );
		$status = sanitize_text_field( $request->get_param( 'status' ) ?: 'active' );
		$expires_at = sanitize_text_field( $request->get_param( 'expires_at' ) ?: null );

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$success = Plan_Manager::assign_plan( $restaurant_id, $plan_id, $status, $expires_at );

		if ( ! $success ) {
			return new WP_Error( 'vc_update_failed', __( 'Erro ao atualizar plano.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		$plan = Plan_Manager::get_restaurant_plan( $restaurant_id );

		return new WP_REST_Response( [ 'success' => true, 'plan' => $plan ], 200 );
	}

	public function get_dashboard( WP_REST_Request $request ): WP_REST_Response {
		// Total de restaurantes
		$total_restaurants = wp_count_posts( CPT_Restaurant::SLUG );
		$total_restaurants = (int) ( $total_restaurants->publish ?? 0 );

		// Total de pedidos
		$total_orders = wp_count_posts( 'vc_pedido' );
		$total_orders = (int) ( $total_orders->{'vc-completed'} ?? 0 );

		// Restaurantes por plano
		$restaurants_by_plan = [];
		$query = new WP_Query( [
			'post_type'      => CPT_Restaurant::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		] );

		foreach ( $query->posts as $restaurant ) {
			$plan = Plan_Manager::get_restaurant_plan( $restaurant->ID );
			$plan_name = $plan ? $plan['name'] : 'Sem plano';
			$restaurants_by_plan[ $plan_name ] = ( $restaurants_by_plan[ $plan_name ] ?? 0 ) + 1;
		}

		return new WP_REST_Response( [
			'total_restaurants'     => $total_restaurants,
			'total_orders'          => $total_orders,
			'restaurants_by_plan'  => $restaurants_by_plan,
		], 200 );
	}
}

