<?php
/**
 * Reviews_Controller — REST endpoints para avaliações de restaurantes
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Restaurant;
use VC\Model\CPT_Review;
use VC\Utils\Rating_Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Reviews_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista avaliações de um restaurante (público, apenas aprovadas)
		register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/reviews', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_restaurant_reviews' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'id'       => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'per_page' => [
					'required'          => false,
					'default'           => 10,
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && $param > 0 && $param <= 50;
					},
					'sanitize_callback' => 'absint',
				],
				'page'     => [
					'required'          => false,
					'default'           => 1,
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// GET: Rating agregado de um restaurante (público)
		register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/rating', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_restaurant_rating' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// POST: Criar avaliação (requer autenticação)
		register_rest_route( 'vemcomer/v1', '/reviews', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_review' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'restaurant_id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
				'rating'        => [
					'required'          => true,
					'validate_callback' => function( $param ) {
						return is_numeric( $param ) && $param >= 1 && $param <= 5;
					},
					'sanitize_callback' => 'absint',
				],
				'comment'       => [
					'required'          => false,
					'sanitize_callback' => 'sanitize_textarea_field',
				],
				'order_id'      => [
					'required'          => false,
					'validate_callback' => function( $param ) {
						return is_numeric( $param );
					},
					'sanitize_callback' => 'absint',
				],
			],
		] );
	}

	/**
	 * Verifica se o usuário está autenticado.
	 */
	public function check_authenticated(): bool {
		return is_user_logged_in();
	}

	/**
	 * GET /wp-json/vemcomer/v1/restaurants/{id}/reviews
	 * Lista avaliações aprovadas de um restaurante
	 */
	public function get_restaurant_reviews( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'id' );
		$per_page      = (int) $request->get_param( 'per_page' );
		$page          = (int) $request->get_param( 'page' );

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error(
				'vc_restaurant_not_found',
				__( 'Restaurante não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		$query = new WP_Query( [
			'post_type'      => CPT_Review::SLUG,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => CPT_Review::STATUS_APPROVED,
			'meta_query'     => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
			],
			'orderby'        => 'date',
			'order'          => 'DESC',
		] );

		$reviews = [];
		foreach ( $query->posts as $review ) {
			$customer_id = (int) get_post_meta( $review->ID, '_vc_customer_id', true );
			$customer    = $customer_id > 0 ? get_userdata( $customer_id ) : null;

			$reviews[] = [
				'id'         => $review->ID,
				'rating'     => (int) get_post_meta( $review->ID, '_vc_rating', true ),
				'comment'    => $review->post_content,
				'customer'   => $customer ? [
					'id'    => $customer->ID,
					'name'  => $customer->display_name,
					'email' => $customer->user_email,
				] : null,
				'created_at' => get_post_time( 'c', false, $review ),
			];
		}

		$response = [
			'reviews'      => $reviews,
			'total'        => (int) $query->found_posts,
			'per_page'     => $per_page,
			'current_page' => $page,
			'total_pages'  => (int) $query->max_num_pages,
		];

		log_event( 'REST reviews fetched', [
			'restaurant_id' => $restaurant_id,
			'count'         => count( $reviews ),
		], 'debug' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * GET /wp-json/vemcomer/v1/restaurants/{id}/rating
	 * Retorna rating agregado de um restaurante
	 */
	public function get_restaurant_rating( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$restaurant_id = (int) $request->get_param( 'id' );

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error(
				'vc_restaurant_not_found',
				__( 'Restaurante não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Obter rating usando helper
		$rating = Rating_Helper::get_rating( $restaurant_id );

		$response = [
			'restaurant_id' => $restaurant_id,
			'average'       => $rating['avg'],
			'count'         => $rating['count'],
			'formatted'     => $rating['formatted'],
		];

		log_event( 'REST rating fetched', [
			'restaurant_id' => $restaurant_id,
			'average'       => $rating['avg'],
			'count'         => $rating['count'],
		], 'debug' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/reviews
	 * Cria uma nova avaliação
	 */
	public function create_review( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado para criar uma avaliação.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

		$restaurant_id = (int) $request->get_param( 'restaurant_id' );
		$rating        = (int) $request->get_param( 'rating' );
		$comment       = (string) $request->get_param( 'comment' );
		$order_id      = (int) $request->get_param( 'order_id' );

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error(
				'vc_restaurant_not_found',
				__( 'Restaurante não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Verificar se já existe avaliação do mesmo usuário para este restaurante
		$existing_reviews = get_posts( [
			'post_type'      => CPT_Review::SLUG,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'meta_query'     => [
				[
					'key'   => '_vc_restaurant_id',
					'value' => (string) $restaurant_id,
				],
				[
					'key'   => '_vc_customer_id',
					'value' => (string) $user_id,
				],
			],
		] );

		if ( ! empty( $existing_reviews ) ) {
			return new WP_Error(
				'vc_review_already_exists',
				__( 'Você já avaliou este restaurante.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		// Criar avaliação
		$review_id = wp_insert_post( [
			'post_type'    => CPT_Review::SLUG,
			'post_status'  => CPT_Review::STATUS_PENDING, // Inicia como pendente
			'post_title'   => sprintf( __( 'Avaliação de %s', 'vemcomer' ), $restaurant->post_title ),
			'post_content' => $comment,
			'post_author'  => $user_id,
		], true );

		if ( is_wp_error( $review_id ) ) {
			return new WP_Error(
				'vc_review_creation_failed',
				__( 'Erro ao criar avaliação.', 'vemcomer' ),
				[ 'status' => 500 ]
			);
		}

		// Salvar meta fields
		update_post_meta( $review_id, '_vc_restaurant_id', $restaurant_id );
		update_post_meta( $review_id, '_vc_customer_id', $user_id );
		update_post_meta( $review_id, '_vc_rating', $rating );
		if ( $order_id > 0 ) {
			update_post_meta( $review_id, '_vc_order_id', $order_id );
		}

		// Invalidar cache de rating (será recalculado quando aprovado)
		if ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
			Rating_Helper::invalidate_cache( $restaurant_id );
		}

		$customer = get_userdata( $user_id );

		$response = [
			'id'         => $review_id,
			'restaurant_id' => $restaurant_id,
			'rating'     => $rating,
			'comment'    => $comment,
			'customer'   => [
				'id'   => $user_id,
				'name' => $customer ? $customer->display_name : '',
			],
			'status'     => CPT_Review::STATUS_PENDING,
			'created_at' => get_post_time( 'c', false, $review_id ),
		];

		log_event( 'REST review created', [
			'review_id'     => $review_id,
			'restaurant_id' => $restaurant_id,
			'user_id'       => $user_id,
		], 'info' );

		return new WP_REST_Response( $response, 201 );
	}
}

