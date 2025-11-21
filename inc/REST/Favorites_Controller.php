<?php
/**
 * Favorites_Controller — REST endpoints para favoritos de usuários
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_Restaurant;
use VC\Utils\Favorites_Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_Query;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Favorites_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// ========== RESTAURANTES ==========

		// GET: Lista restaurantes favoritos do usuário
		register_rest_route( 'vemcomer/v1', '/favorites/restaurants', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_favorite_restaurants' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
		] );

		// POST: Adicionar restaurante aos favoritos
		register_rest_route( 'vemcomer/v1', '/favorites/restaurants/(?P<id>\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'add_favorite_restaurant' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// DELETE: Remover restaurante dos favoritos
		register_rest_route( 'vemcomer/v1', '/favorites/restaurants/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'remove_favorite_restaurant' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// ========== ITENS DO CARDÁPIO ==========

		// GET: Lista itens do cardápio favoritos do usuário
		register_rest_route( 'vemcomer/v1', '/favorites/menu-items', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_favorite_menu_items' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
		] );

		// POST: Adicionar item do cardápio aos favoritos
		register_rest_route( 'vemcomer/v1', '/favorites/menu-items/(?P<id>\d+)', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'add_favorite_menu_item' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// DELETE: Remover item do cardápio dos favoritos
		register_rest_route( 'vemcomer/v1', '/favorites/menu-items/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'remove_favorite_menu_item' ],
			'permission_callback' => [ $this, 'check_authenticated' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
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
	 * GET /wp-json/vemcomer/v1/favorites/restaurants
	 * Lista restaurantes favoritos do usuário autenticado
	 */
	public function get_favorite_restaurants( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

		$favorite_ids = Favorites_Helper::get_favorite_restaurants( $user_id );

		if ( empty( $favorite_ids ) ) {
			return new WP_REST_Response( [
				'restaurants' => [],
				'total'       => 0,
			], 200 );
		}

		// Buscar dados dos restaurantes
		$query = new WP_Query( [
			'post_type'      => CPT_Restaurant::SLUG,
			'posts_per_page' => -1,
			'post__in'       => $favorite_ids,
			'post_status'    => 'publish',
			'orderby'        => 'post__in', // Manter ordem dos favoritos
		] );

		$restaurants = [];
		foreach ( $query->posts as $restaurant ) {
			$restaurants[] = [
				'id'          => $restaurant->ID,
				'title'       => get_the_title( $restaurant ),
				'address'     => (string) get_post_meta( $restaurant->ID, '_vc_address', true ),
				'phone'       => (string) get_post_meta( $restaurant->ID, '_vc_phone', true ),
				'has_delivery' => (bool) get_post_meta( $restaurant->ID, '_vc_has_delivery', true ),
				'is_open'     => (bool) get_post_meta( $restaurant->ID, '_vc_is_open', true ),
			];
		}

		$response = [
			'restaurants' => $restaurants,
			'total'       => count( $restaurants ),
		];

		log_event( 'REST favorite restaurants fetched', [
			'user_id' => $user_id,
			'count'   => count( $restaurants ),
		], 'debug' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/favorites/restaurants/{id}
	 * Adiciona restaurante aos favoritos do usuário autenticado
	 */
	public function add_favorite_restaurant( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

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

		// Adicionar aos favoritos
		$added = Favorites_Helper::add_favorite_restaurant( $user_id, $restaurant_id );

		if ( ! $added ) {
			return new WP_Error(
				'vc_already_favorite',
				__( 'Restaurante já está nos favoritos.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$response = [
			'restaurant_id' => $restaurant_id,
			'is_favorite'   => true,
			'message'       => __( 'Restaurante adicionado aos favoritos.', 'vemcomer' ),
		];

		log_event( 'REST favorite restaurant added', [
			'user_id'       => $user_id,
			'restaurant_id' => $restaurant_id,
		], 'info' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * DELETE /wp-json/vemcomer/v1/favorites/restaurants/{id}
	 * Remove restaurante dos favoritos do usuário autenticado
	 */
	public function remove_favorite_restaurant( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

		$restaurant_id = (int) $request->get_param( 'id' );

		// Remover dos favoritos
		$removed = Favorites_Helper::remove_favorite_restaurant( $user_id, $restaurant_id );

		if ( ! $removed ) {
			return new WP_Error(
				'vc_not_favorite',
				__( 'Restaurante não está nos favoritos.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$response = [
			'restaurant_id' => $restaurant_id,
			'is_favorite'   => false,
			'message'       => __( 'Restaurante removido dos favoritos.', 'vemcomer' ),
		];

		log_event( 'REST favorite restaurant removed', [
			'user_id'       => $user_id,
			'restaurant_id' => $restaurant_id,
		], 'info' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * GET /wp-json/vemcomer/v1/favorites/menu-items
	 * Lista itens do cardápio favoritos do usuário autenticado
	 */
	public function get_favorite_menu_items( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

		$favorite_ids = Favorites_Helper::get_favorite_menu_items( $user_id );

		if ( empty( $favorite_ids ) ) {
			return new WP_REST_Response( [
				'menu_items' => [],
				'total'      => 0,
			], 200 );
		}

		// Buscar dados dos itens do cardápio
		$query = new WP_Query( [
			'post_type'      => CPT_MenuItem::SLUG,
			'posts_per_page' => -1,
			'post__in'        => $favorite_ids,
			'post_status'    => 'publish',
			'orderby'         => 'post__in', // Manter ordem dos favoritos
		] );

		$menu_items = [];
		foreach ( $query->posts as $item ) {
			$restaurant_id = (int) get_post_meta( $item->ID, '_vc_restaurant_id', true );
			$menu_items[] = [
				'id'           => $item->ID,
				'title'        => get_the_title( $item ),
				'price'        => (string) get_post_meta( $item->ID, '_vc_price', true ),
				'prep_time'    => (int) get_post_meta( $item->ID, '_vc_prep_time', true ),
				'is_available' => (bool) get_post_meta( $item->ID, '_vc_is_available', true ),
				'restaurant_id' => $restaurant_id,
			];
		}

		$response = [
			'menu_items' => $menu_items,
			'total'      => count( $menu_items ),
		];

		log_event( 'REST favorite menu items fetched', [
			'user_id' => $user_id,
			'count'   => count( $menu_items ),
		], 'debug' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/favorites/menu-items/{id}
	 * Adiciona item do cardápio aos favoritos do usuário autenticado
	 */
	public function add_favorite_menu_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

		$menu_item_id = (int) $request->get_param( 'id' );

		// Verificar se item do cardápio existe
		$menu_item = get_post( $menu_item_id );
		if ( ! $menu_item || CPT_MenuItem::SLUG !== $menu_item->post_type ) {
			return new WP_Error(
				'vc_menu_item_not_found',
				__( 'Item do cardápio não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Adicionar aos favoritos
		$added = Favorites_Helper::add_favorite_menu_item( $user_id, $menu_item_id );

		if ( ! $added ) {
			return new WP_Error(
				'vc_already_favorite',
				__( 'Item do cardápio já está nos favoritos.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$response = [
			'menu_item_id' => $menu_item_id,
			'is_favorite'  => true,
			'message'      => __( 'Item do cardápio adicionado aos favoritos.', 'vemcomer' ),
		];

		log_event( 'REST favorite menu item added', [
			'user_id'      => $user_id,
			'menu_item_id' => $menu_item_id,
		], 'info' );

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * DELETE /wp-json/vemcomer/v1/favorites/menu-items/{id}
	 * Remove item do cardápio dos favoritos do usuário autenticado
	 */
	public function remove_favorite_menu_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return new WP_Error(
				'vc_unauthorized',
				__( 'Você precisa estar autenticado.', 'vemcomer' ),
				[ 'status' => 401 ]
			);
		}

		$menu_item_id = (int) $request->get_param( 'id' );

		// Remover dos favoritos
		$removed = Favorites_Helper::remove_favorite_menu_item( $user_id, $menu_item_id );

		if ( ! $removed ) {
			return new WP_Error(
				'vc_not_favorite',
				__( 'Item do cardápio não está nos favoritos.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$response = [
			'menu_item_id' => $menu_item_id,
			'is_favorite'  => false,
			'message'      => __( 'Item do cardápio removido dos favoritos.', 'vemcomer' ),
		];

		log_event( 'REST favorite menu item removed', [
			'user_id'      => $user_id,
			'menu_item_id' => $menu_item_id,
		], 'info' );

		return new WP_REST_Response( $response, 200 );
	}
}

