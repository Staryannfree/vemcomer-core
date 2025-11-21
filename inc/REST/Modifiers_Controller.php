<?php
/**
 * Modifiers_Controller — REST endpoints para complementos/modificadores de produtos
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_ProductModifier;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Modifiers_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista modificadores de um item do cardápio (público)
		register_rest_route( 'vemcomer/v1', '/menu-items/(?P<id>\d+)/modifiers', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_menu_item_modifiers' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// POST: Criar modificador vinculado a um item do cardápio (admin)
		register_rest_route( 'vemcomer/v1', '/menu-items/(?P<id>\d+)/modifiers', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_modifier' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// PATCH: Atualizar modificador (admin)
		register_rest_route( 'vemcomer/v1', '/modifiers/(?P<id>\d+)', [
			'methods'             => 'PATCH',
			'callback'            => [ $this, 'update_modifier' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// DELETE: Deletar modificador (admin)
		register_rest_route( 'vemcomer/v1', '/modifiers/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_modifier' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
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
	 * Verifica se o usuário tem permissão para gerenciar modificadores
	 */
	public function check_admin_permission(): bool {
		return current_user_can( 'edit_vc_product_modifiers' );
	}

	/**
	 * GET /wp-json/vemcomer/v1/menu-items/{id}/modifiers
	 * Lista todos os modificadores vinculados a um item do cardápio
	 */
	public function get_menu_item_modifiers( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$menu_item_id = (int) $request->get_param( 'id' );

		// Verificar se o item do cardápio existe
		$menu_item = get_post( $menu_item_id );
		if ( ! $menu_item || CPT_MenuItem::SLUG !== $menu_item->post_type ) {
			log_event( 'REST modifiers: menu item not found', [ 'menu_item_id' => $menu_item_id ], 'warning' );
			return new WP_Error(
				'vc_menu_item_not_found',
				__( 'Item do cardápio não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Buscar modificadores vinculados via meta reversa
		$modifier_ids = get_post_meta( $menu_item_id, '_vc_menu_item_modifiers', true );
		$modifier_ids = is_array( $modifier_ids ) ? array_map( 'absint', $modifier_ids ) : [];

		if ( empty( $modifier_ids ) ) {
			return new WP_REST_Response( [], 200 );
		}

		// Buscar posts dos modificadores
		$modifiers = get_posts( [
			'post_type'      => CPT_ProductModifier::SLUG,
			'post__in'       => $modifier_ids,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$items = [];
		foreach ( $modifiers as $modifier ) {
			$type  = get_post_meta( $modifier->ID, '_vc_modifier_type', true );
			$price = get_post_meta( $modifier->ID, '_vc_modifier_price', true );
			$min   = (int) get_post_meta( $modifier->ID, '_vc_modifier_min', true );
			$max   = (int) get_post_meta( $modifier->ID, '_vc_modifier_max', true );

			$items[] = [
				'id'          => $modifier->ID,
				'title'       => get_the_title( $modifier ),
				'description' => wp_strip_all_tags( $modifier->post_content ),
				'type'        => $type ?: 'optional',
				'price'       => $price ? (float) $price : 0.0,
				'min'         => $min,
				'max'         => $max > 0 ? $max : null, // null = ilimitado
			];
		}

		log_event( 'REST modifiers fetched', [ 'menu_item_id' => $menu_item_id, 'count' => count( $items ) ], 'debug' );

		return new WP_REST_Response( $items, 200 );
	}

	/**
	 * POST /wp-json/vemcomer/v1/menu-items/{id}/modifiers
	 * Cria um novo modificador e vincula a um item do cardápio
	 */
	public function create_modifier( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$menu_item_id = (int) $request->get_param( 'id' );

		// Verificar se o item do cardápio existe
		$menu_item = get_post( $menu_item_id );
		if ( ! $menu_item || CPT_MenuItem::SLUG !== $menu_item->post_type ) {
			return new WP_Error(
				'vc_menu_item_not_found',
				__( 'Item do cardápio não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Validar dados do body
		$body = $request->get_json_params();
		if ( ! $body ) {
			return new WP_Error(
				'vc_invalid_json',
				__( 'JSON inválido no body da requisição.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$title = sanitize_text_field( $body['title'] ?? '' );
		if ( empty( $title ) ) {
			return new WP_Error(
				'vc_title_required',
				__( 'O título é obrigatório.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$type  = sanitize_text_field( $body['type'] ?? 'optional' );
		if ( ! in_array( $type, [ 'required', 'optional' ], true ) ) {
			$type = 'optional';
		}

		$price = isset( $body['price'] ) ? (float) $body['price'] : 0.0;
		$price = max( 0.0, $price );

		$min = isset( $body['min'] ) ? max( 0, (int) $body['min'] ) : 0;
		$max = isset( $body['max'] ) ? max( 0, (int) $body['max'] ) : 0;

		// Validar: mínimo <= máximo (se máximo > 0)
		if ( $max > 0 && $min > $max ) {
			return new WP_Error(
				'vc_invalid_min_max',
				__( 'O mínimo não pode ser maior que o máximo.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$description = isset( $body['description'] ) ? sanitize_textarea_field( $body['description'] ) : '';

		// Criar o post do modificador
		$modifier_id = wp_insert_post( [
			'post_type'    => CPT_ProductModifier::SLUG,
			'post_title'   => $title,
			'post_content' => $description,
			'post_status'  => 'publish',
		], true );

		if ( is_wp_error( $modifier_id ) ) {
			log_event( 'REST modifier creation failed', [ 'error' => $modifier_id->get_error_message() ], 'error' );
			return $modifier_id;
		}

		// Salvar meta fields
		update_post_meta( $modifier_id, '_vc_modifier_type', $type );
		update_post_meta( $modifier_id, '_vc_modifier_price', (string) $price );
		update_post_meta( $modifier_id, '_vc_modifier_min', $min );
		update_post_meta( $modifier_id, '_vc_modifier_max', $max );

		// Vincular ao item do cardápio
		$menu_item_modifiers = get_post_meta( $menu_item_id, '_vc_menu_item_modifiers', true );
		$menu_item_modifiers = is_array( $menu_item_modifiers ) ? $menu_item_modifiers : [];
		if ( ! in_array( $modifier_id, $menu_item_modifiers, true ) ) {
			$menu_item_modifiers[] = $modifier_id;
			update_post_meta( $menu_item_id, '_vc_menu_item_modifiers', $menu_item_modifiers );
		}

		// Atualizar meta reversa no modificador
		$modifier_menu_items = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
		$modifier_menu_items = is_array( $modifier_menu_items ) ? $modifier_menu_items : [];
		if ( ! in_array( $menu_item_id, $modifier_menu_items, true ) ) {
			$modifier_menu_items[] = $menu_item_id;
			update_post_meta( $modifier_id, '_vc_modifier_menu_items', $modifier_menu_items );
		}

		log_event( 'REST modifier created', [ 'modifier_id' => $modifier_id, 'menu_item_id' => $menu_item_id ], 'info' );

		// Retornar o modificador criado
		return new WP_REST_Response( [
			'id'          => $modifier_id,
			'title'       => $title,
			'description' => $description,
			'type'        => $type,
			'price'       => $price,
			'min'         => $min,
			'max'         => $max > 0 ? $max : null,
		], 201 );
	}

	/**
	 * PATCH /wp-json/vemcomer/v1/modifiers/{id}
	 * Atualiza um modificador existente
	 */
	public function update_modifier( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$modifier_id = (int) $request->get_param( 'id' );

		// Verificar se o modificador existe
		$modifier = get_post( $modifier_id );
		if ( ! $modifier || CPT_ProductModifier::SLUG !== $modifier->post_type ) {
			return new WP_Error(
				'vc_modifier_not_found',
				__( 'Modificador não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Validar dados do body
		$body = $request->get_json_params();
		if ( ! $body ) {
			return new WP_Error(
				'vc_invalid_json',
				__( 'JSON inválido no body da requisição.', 'vemcomer' ),
				[ 'status' => 400 ]
			);
		}

		$updated = [];

		// Atualizar título se fornecido
		if ( isset( $body['title'] ) ) {
			$title = sanitize_text_field( $body['title'] );
			if ( ! empty( $title ) ) {
				wp_update_post( [
					'ID'         => $modifier_id,
					'post_title' => $title,
				] );
				$updated['title'] = $title;
			}
		}

		// Atualizar descrição se fornecido
		if ( isset( $body['description'] ) ) {
			$description = sanitize_textarea_field( $body['description'] );
			wp_update_post( [
				'ID'           => $modifier_id,
				'post_content' => $description,
			] );
			$updated['description'] = $description;
		}

		// Atualizar tipo se fornecido
		if ( isset( $body['type'] ) ) {
			$type = sanitize_text_field( $body['type'] );
			if ( in_array( $type, [ 'required', 'optional' ], true ) ) {
				update_post_meta( $modifier_id, '_vc_modifier_type', $type );
				$updated['type'] = $type;
			}
		}

		// Atualizar preço se fornecido
		if ( isset( $body['price'] ) ) {
			$price = max( 0.0, (float) $body['price'] );
			update_post_meta( $modifier_id, '_vc_modifier_price', (string) $price );
			$updated['price'] = $price;
		}

		// Atualizar mínimo se fornecido
		if ( isset( $body['min'] ) ) {
			$min = max( 0, (int) $body['min'] );
			update_post_meta( $modifier_id, '_vc_modifier_min', $min );
			$updated['min'] = $min;
		}

		// Atualizar máximo se fornecido
		if ( isset( $body['max'] ) ) {
			$max = max( 0, (int) $body['max'] );
			update_post_meta( $modifier_id, '_vc_modifier_max', $max );
			$updated['max'] = $max > 0 ? $max : null;
		}

		// Validar: mínimo <= máximo (se máximo > 0)
		$min_saved = (int) get_post_meta( $modifier_id, '_vc_modifier_min', true );
		$max_saved = (int) get_post_meta( $modifier_id, '_vc_modifier_max', true );
		if ( $max_saved > 0 && $min_saved > $max_saved ) {
			// Ajustar mínimo para não exceder máximo
			update_post_meta( $modifier_id, '_vc_modifier_min', $max_saved );
			$updated['min'] = $max_saved;
		}

		log_event( 'REST modifier updated', [ 'modifier_id' => $modifier_id, 'updated' => array_keys( $updated ) ], 'info' );

		// Retornar dados atualizados
		return new WP_REST_Response( [
			'id'          => $modifier_id,
			'title'       => get_the_title( $modifier_id ),
			'description' => wp_strip_all_tags( get_post_field( 'post_content', $modifier_id ) ),
			'type'        => get_post_meta( $modifier_id, '_vc_modifier_type', true ) ?: 'optional',
			'price'       => (float) get_post_meta( $modifier_id, '_vc_modifier_price', true ),
			'min'         => (int) get_post_meta( $modifier_id, '_vc_modifier_min', true ),
			'max'         => ( (int) get_post_meta( $modifier_id, '_vc_modifier_max', true ) ) > 0
				? (int) get_post_meta( $modifier_id, '_vc_modifier_max', true )
				: null,
		], 200 );
	}

	/**
	 * DELETE /wp-json/vemcomer/v1/modifiers/{id}
	 * Deleta um modificador e remove vínculos
	 */
	public function delete_modifier( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$modifier_id = (int) $request->get_param( 'id' );

		// Verificar se o modificador existe
		$modifier = get_post( $modifier_id );
		if ( ! $modifier || CPT_ProductModifier::SLUG !== $modifier->post_type ) {
			return new WP_Error(
				'vc_modifier_not_found',
				__( 'Modificador não encontrado.', 'vemcomer' ),
				[ 'status' => 404 ]
			);
		}

		// Remover vínculos dos itens do cardápio
		$menu_item_ids = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
		$menu_item_ids = is_array( $menu_item_ids ) ? array_map( 'absint', $menu_item_ids ) : [];

		foreach ( $menu_item_ids as $menu_item_id ) {
			$modifiers = get_post_meta( $menu_item_id, '_vc_menu_item_modifiers', true );
			$modifiers = is_array( $modifiers ) ? $modifiers : [];
			$key       = array_search( $modifier_id, $modifiers, true );
			if ( $key !== false ) {
				unset( $modifiers[ $key ] );
				$modifiers = array_values( $modifiers ); // Reindexar
				update_post_meta( $menu_item_id, '_vc_menu_item_modifiers', $modifiers );
			}
		}

		// Deletar o post (mover para lixeira)
		$deleted = wp_delete_post( $modifier_id, false ); // false = não deletar permanentemente

		if ( ! $deleted ) {
			return new WP_Error(
				'vc_delete_failed',
				__( 'Falha ao deletar modificador.', 'vemcomer' ),
				[ 'status' => 500 ]
			);
		}

		log_event( 'REST modifier deleted', [ 'modifier_id' => $modifier_id ], 'info' );

		return new WP_REST_Response( [
			'message' => __( 'Modificador deletado com sucesso.', 'vemcomer' ),
			'id'      => $modifier_id,
		], 200 );
	}
}

