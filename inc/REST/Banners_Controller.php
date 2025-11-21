<?php
/**
 * Banners_Controller — REST endpoints para banners
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Banner;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Banners_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista banners ativos (público)
		register_rest_route( 'vemcomer/v1', '/banners', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_banners' ],
			'permission_callback' => '__return_true',
		] );

		// POST: Criar banner (admin)
		register_rest_route( 'vemcomer/v1', '/banners', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_banner' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
		] );

		// PATCH: Atualizar banner (admin)
		register_rest_route( 'vemcomer/v1', '/banners/(?P<id>\d+)', [
			'methods'             => 'PATCH',
			'callback'            => [ $this, 'update_banner' ],
			'permission_callback' => [ $this, 'check_admin_permission' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// DELETE: Deletar banner (admin)
		register_rest_route( 'vemcomer/v1', '/banners/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_banner' ],
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

	public function check_admin_permission(): bool {
		return current_user_can( 'manage_options' );
	}

	public function get_banners( WP_REST_Request $request ): WP_REST_Response {
		$query = new WP_Query( [
			'post_type'      => CPT_Banner::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'   => '_vc_banner_active',
					'value' => '1',
				],
			],
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_vc_banner_order',
			'order'          => 'ASC',
		] );

		$banners = [];
		foreach ( $query->posts as $post ) {
			$image_id = get_post_thumbnail_id( $post->ID );
			$banners[] = [
				'id'            => $post->ID,
				'title'         => get_the_title( $post ),
				'image'         => $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : null,
				'link'          => (string) get_post_meta( $post->ID, '_vc_banner_link', true ),
				'restaurant_id' => (int) get_post_meta( $post->ID, '_vc_banner_restaurant_id', true ) ?: null,
				'order'         => (int) get_post_meta( $post->ID, '_vc_banner_order', true ),
			];
		}

		return new WP_REST_Response( $banners, 200 );
	}

	public function create_banner( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$title         = $request->get_param( 'title' );
		$image_id      = $request->get_param( 'image_id' );
		$link          = $request->get_param( 'link' );
		$restaurant_id = $request->get_param( 'restaurant_id' );
		$order         = $request->get_param( 'order' );
		$active        = $request->get_param( 'active' );

		if ( empty( $title ) ) {
			return new WP_Error( 'vc_missing_title', __( 'Título é obrigatório.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		$post_id = wp_insert_post( [
			'post_type'   => CPT_Banner::SLUG,
			'post_title'  => sanitize_text_field( $title ),
			'post_status' => 'publish',
		], true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'vc_banner_creation_failed', __( 'Erro ao criar banner.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		if ( $image_id > 0 ) {
			set_post_thumbnail( $post_id, $image_id );
		}

		if ( $link ) {
			update_post_meta( $post_id, '_vc_banner_link', esc_url_raw( $link ) );
		}

		if ( $restaurant_id > 0 ) {
			update_post_meta( $post_id, '_vc_banner_restaurant_id', $restaurant_id );
		}

		update_post_meta( $post_id, '_vc_banner_order', (int) $order );
		update_post_meta( $post_id, '_vc_banner_active', $active ? '1' : '0' );

		return new WP_REST_Response( [ 'id' => $post_id ], 201 );
	}

	public function update_banner( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || CPT_Banner::SLUG !== $post->post_type ) {
			return new WP_Error( 'vc_banner_not_found', __( 'Banner não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		$title         = $request->get_param( 'title' );
		$image_id      = $request->get_param( 'image_id' );
		$link          = $request->get_param( 'link' );
		$restaurant_id = $request->get_param( 'restaurant_id' );
		$order         = $request->get_param( 'order' );
		$active        = $request->get_param( 'active' );

		if ( $title ) {
			wp_update_post( [ 'ID' => $id, 'post_title' => sanitize_text_field( $title ) ] );
		}

		if ( null !== $image_id ) {
			if ( $image_id > 0 ) {
				set_post_thumbnail( $id, $image_id );
			} else {
				delete_post_thumbnail( $id );
			}
		}

		if ( null !== $link ) {
			update_post_meta( $id, '_vc_banner_link', esc_url_raw( $link ) );
		}

		if ( null !== $restaurant_id ) {
			if ( $restaurant_id > 0 ) {
				update_post_meta( $id, '_vc_banner_restaurant_id', $restaurant_id );
			} else {
				delete_post_meta( $id, '_vc_banner_restaurant_id' );
			}
		}

		if ( null !== $order ) {
			update_post_meta( $id, '_vc_banner_order', (int) $order );
		}

		if ( null !== $active ) {
			update_post_meta( $id, '_vc_banner_active', $active ? '1' : '0' );
		}

		return new WP_REST_Response( [ 'id' => $id ], 200 );
	}

	public function delete_banner( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || CPT_Banner::SLUG !== $post->post_type ) {
			return new WP_Error( 'vc_banner_not_found', __( 'Banner não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		wp_delete_post( $id, true );

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}
}

