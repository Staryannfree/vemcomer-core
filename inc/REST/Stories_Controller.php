<?php
/**
 * Stories_Controller — REST endpoints para stories
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_Story;
use VC\Model\CPT_Restaurant;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Stories_Controller {
	public function init(): void {
		add_action( 'rest_api_init', [ $this, 'routes' ] );
	}

	public function routes(): void {
		// GET: Lista stories agrupados por restaurante (público)
		register_rest_route( 'vemcomer/v1', '/stories', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_stories' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'restaurant_id' => [
					'required'          => false,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// GET: Stories de um restaurante específico
		register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/stories', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_restaurant_stories' ],
			'permission_callback' => '__return_true',
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// POST: Criar story (admin ou dono do restaurante)
		register_rest_route( 'vemcomer/v1', '/stories', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'create_story' ],
			'permission_callback' => [ $this, 'check_permission' ],
		] );

		// PATCH: Atualizar story
		register_rest_route( 'vemcomer/v1', '/stories/(?P<id>\d+)', [
			'methods'             => 'PATCH',
			'callback'            => [ $this, 'update_story' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// DELETE: Deletar story
		register_rest_route( 'vemcomer/v1', '/stories/(?P<id>\d+)', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_story' ],
			'permission_callback' => [ $this, 'check_permission' ],
			'args'                => [
				'id' => [
					'required'          => true,
					'validate_callback' => 'is_numeric',
					'sanitize_callback' => 'absint',
				],
			],
		] );

		// POST: Marcar story como visto
		register_rest_route( 'vemcomer/v1', '/stories/(?P<id>\d+)/view', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'mark_as_viewed' ],
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

	public function check_authenticated(): bool {
		return is_user_logged_in();
	}

	public function check_permission(): bool {
		// Admin pode tudo
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		// Dono de restaurante pode criar stories para seus restaurantes
		if ( current_user_can( 'edit_posts' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Lista stories agrupados por restaurante
	 * Formato: [{ id, restaurant: { name, avatar }, stories: [...], viewed, hasNew }]
	 */
	public function get_stories( WP_REST_Request $request ): WP_REST_Response {
		$restaurant_id_filter = $request->get_param( 'restaurant_id' );

		// Buscar todos os stories ativos
		$meta_query = [
			[
				'key'   => '_vc_story_active',
				'value' => '1',
			],
		];

		if ( $restaurant_id_filter > 0 ) {
			$meta_query[] = [
				'key'   => '_vc_story_restaurant_id',
				'value' => $restaurant_id_filter,
			];
		}

		$query = new WP_Query( [
			'post_type'      => CPT_Story::SLUG,
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => $meta_query,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_vc_story_order',
			'order'          => 'ASC',
		] );

		// Agrupar por restaurante
		$stories_by_restaurant = [];
		$user_id = get_current_user_id();

		foreach ( $query->posts as $post ) {
			$restaurant_id = (int) get_post_meta( $post->ID, '_vc_story_restaurant_id', true );
			if ( $restaurant_id <= 0 ) {
				continue;
			}

			if ( ! isset( $stories_by_restaurant[ $restaurant_id ] ) ) {
				$restaurant = get_post( $restaurant_id );
				if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
					continue;
				}

				// Buscar avatar do restaurante (logo)
				$avatar_id = get_post_thumbnail_id( $restaurant_id );
				$avatar = $avatar_id ? wp_get_attachment_image_url( $avatar_id, 'thumbnail' ) : null;

				$stories_by_restaurant[ $restaurant_id ] = [
					'restaurant' => [
						'id'     => $restaurant_id,
						'name'   => $restaurant->post_title,
						'avatar' => $avatar,
						'slug'   => $restaurant->post_name,
					],
					'stories'   => [],
				];
			}

			// Adicionar story ao grupo
			$image_id = get_post_thumbnail_id( $post->ID );
			$type = (string) get_post_meta( $post->ID, '_vc_story_type', true );
			if ( empty( $type ) ) {
				$type = 'image';
			}
			$duration = (int) get_post_meta( $post->ID, '_vc_story_duration', true );
			if ( $duration <= 0 ) {
				$duration = 5000;
			}
			$link_type = (string) get_post_meta( $post->ID, '_vc_story_link_type', true );
			if ( empty( $link_type ) ) {
				// Compatibilidade: verificar meta antigo
				$old_link = (string) get_post_meta( $post->ID, '_vc_story_link', true );
				if ( $old_link ) {
					$link_type = 'custom';
				} else {
					$link_type = 'none';
				}
			}

			$media_url = null;
			if ( $image_id ) {
				// Usar tamanho 'large' mas garantir formato retangular
				$media_url = wp_get_attachment_image_url( $image_id, 'large' );
			}

			// Calcular timestamp relativo
			$post_date = get_post_time( 'U', false, $post->ID );
			$timestamp = $this->get_relative_time( $post_date );

			$story_data = [
				'id'        => $post->ID,
				'type'      => $type,
				'url'       => $media_url,
				'timestamp' => $timestamp,
				'duration'  => $duration,
				'link_type' => $link_type,
			];

			// Adicionar link baseado no tipo
			if ( $link_type === 'profile' ) {
				$story_data['link'] = get_permalink( $restaurant_id );
				$story_data['link_text'] = __( 'Ver Perfil', 'vemcomer' );
			} elseif ( $link_type === 'menu' ) {
				$story_data['link'] = 'menu'; // Especial: será tratado no frontend
				$story_data['link_text'] = __( 'Ver Cardápio', 'vemcomer' );
				$story_data['restaurant_id'] = $restaurant_id; // Para buscar cardápio
			} elseif ( $link_type === 'custom' ) {
				// Compatibilidade com meta antigo
				$old_link = (string) get_post_meta( $post->ID, '_vc_story_link', true );
				$old_link_text = (string) get_post_meta( $post->ID, '_vc_story_link_text', true );
				if ( $old_link ) {
					$story_data['link'] = $old_link;
					$story_data['link_text'] = $old_link_text ?: __( 'Ver Mais', 'vemcomer' );
				}
			}

			$stories_by_restaurant[ $restaurant_id ]['stories'][] = $story_data;
		}

		// Converter para formato esperado pelo frontend
		$groups = [];
		$group_id = 1;

		foreach ( $stories_by_restaurant as $restaurant_id => $data ) {
			if ( empty( $data['stories'] ) ) {
				continue;
			}

			// Ordenar stories por ordem (meta _vc_story_order)
			usort( $data['stories'], function( $a, $b ) {
				// Buscar ordem de cada story
				$order_a = (int) get_post_meta( $a['id'], '_vc_story_order', true );
				$order_b = (int) get_post_meta( $b['id'], '_vc_story_order', true );
				return $order_a <=> $order_b;
			} );

			// Verificar se usuário já viu algum story deste restaurante
			$viewed = false;
			$has_new = false;
			if ( $user_id > 0 ) {
				$viewed_stories = get_user_meta( $user_id, 'vc_stories_viewed', true );
				if ( is_array( $viewed_stories ) ) {
					foreach ( $data['stories'] as $story ) {
						if ( in_array( $story['id'], $viewed_stories, true ) ) {
							$viewed = true;
						} else {
							$has_new = true;
						}
					}
				} else {
					$has_new = true;
				}
			} else {
				$has_new = true;
			}

			$groups[] = [
				'id'        => $group_id++,
				'restaurant' => $data['restaurant'],
				'stories'   => $data['stories'],
				'viewed'    => $viewed,
				'hasNew'    => $has_new,
			];
		}

		return new WP_REST_Response( $groups, 200 );
	}

	/**
	 * Stories de um restaurante específico
	 */
	public function get_restaurant_stories( WP_REST_Request $request ): WP_REST_Response {
		$restaurant_id = (int) $request->get_param( 'id' );

		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_REST_Response( [], 200 );
		}

		$request->set_param( 'restaurant_id', $restaurant_id );
		return $this->get_stories( $request );
	}

	/**
	 * Criar story
	 */
	public function create_story( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$title         = $request->get_param( 'title' );
		$restaurant_id = (int) $request->get_param( 'restaurant_id' );
		$type          = $request->get_param( 'type' ) ?: 'image';
		$duration      = (int) $request->get_param( 'duration' ) ?: 5000;
		$order         = (int) $request->get_param( 'order' ) ?: 0;
		$active        = $request->get_param( 'active' ) ?? true;
		$image_id      = (int) $request->get_param( 'image_id' );
		$link_type     = $request->get_param( 'link_type' ) ?: 'none';

		if ( empty( $title ) ) {
			return new WP_Error( 'vc_missing_title', __( 'Título é obrigatório.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		if ( $restaurant_id <= 0 ) {
			return new WP_Error( 'vc_missing_restaurant', __( 'Restaurante é obrigatório.', 'vemcomer' ), [ 'status' => 400 ] );
		}

		// Verificar se restaurante existe
		$restaurant = get_post( $restaurant_id );
		if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
			return new WP_Error( 'vc_invalid_restaurant', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Verificar permissão: dono do restaurante só pode criar para seus próprios restaurantes
		if ( ! current_user_can( 'manage_options' ) ) {
			$user_restaurant_id = (int) get_user_meta( get_current_user_id(), 'vc_restaurant_id', true );
			if ( $user_restaurant_id !== $restaurant_id ) {
				return new WP_Error( 'vc_forbidden', __( 'Você não tem permissão para criar stories para este restaurante.', 'vemcomer' ), [ 'status' => 403 ] );
			}
		}

		$post_id = wp_insert_post( [
			'post_type'   => CPT_Story::SLUG,
			'post_title'  => sanitize_text_field( $title ),
			'post_status' => 'publish',
		], true );

		if ( is_wp_error( $post_id ) ) {
			return new WP_Error( 'vc_story_creation_failed', __( 'Erro ao criar story.', 'vemcomer' ), [ 'status' => 500 ] );
		}

		if ( $image_id > 0 ) {
			set_post_thumbnail( $post_id, $image_id );
		}

		update_post_meta( $post_id, '_vc_story_restaurant_id', $restaurant_id );
		update_post_meta( $post_id, '_vc_story_type', sanitize_text_field( $type ) );
		update_post_meta( $post_id, '_vc_story_duration', $duration );
		update_post_meta( $post_id, '_vc_story_order', $order );
		update_post_meta( $post_id, '_vc_story_active', $active ? '1' : '0' );

		$allowed_link_types = [ 'none', 'profile', 'menu' ];
		if ( in_array( $link_type, $allowed_link_types, true ) ) {
			update_post_meta( $post_id, '_vc_story_link_type', $link_type );
		}

		return new WP_REST_Response( [ 'id' => $post_id ], 201 );
	}

	/**
	 * Atualizar story
	 */
	public function update_story( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || CPT_Story::SLUG !== $post->post_type ) {
			return new WP_Error( 'vc_story_not_found', __( 'Story não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Verificar permissão
		$restaurant_id = (int) get_post_meta( $id, '_vc_story_restaurant_id', true );
		if ( ! current_user_can( 'manage_options' ) ) {
			$user_restaurant_id = (int) get_user_meta( get_current_user_id(), 'vc_restaurant_id', true );
			if ( $user_restaurant_id !== $restaurant_id ) {
				return new WP_Error( 'vc_forbidden', __( 'Você não tem permissão para editar este story.', 'vemcomer' ), [ 'status' => 403 ] );
			}
		}

		$title     = $request->get_param( 'title' );
		$type      = $request->get_param( 'type' );
		$duration  = $request->get_param( 'duration' );
		$order     = $request->get_param( 'order' );
		$active    = $request->get_param( 'active' );
		$image_id  = $request->get_param( 'image_id' );
		$link      = $request->get_param( 'link' );
		$link_text = $request->get_param( 'link_text' );

		if ( null !== $title ) {
			wp_update_post( [ 'ID' => $id, 'post_title' => sanitize_text_field( $title ) ] );
		}

		if ( null !== $image_id ) {
			if ( $image_id > 0 ) {
				set_post_thumbnail( $id, $image_id );
			} else {
				delete_post_thumbnail( $id );
			}
		}

		if ( null !== $type ) {
			$allowed_types = [ 'image', 'video' ];
			if ( in_array( $type, $allowed_types, true ) ) {
				update_post_meta( $id, '_vc_story_type', $type );
			}
		}

		if ( null !== $duration ) {
			$duration = (int) $duration;
			if ( $duration >= 1000 && $duration <= 30000 ) {
				update_post_meta( $id, '_vc_story_duration', $duration );
			}
		}

		if ( null !== $order ) {
			update_post_meta( $id, '_vc_story_order', (int) $order );
		}

		if ( null !== $active ) {
			update_post_meta( $id, '_vc_story_active', $active ? '1' : '0' );
		}

		if ( null !== $link_type ) {
			$allowed_link_types = [ 'none', 'profile', 'menu' ];
			if ( in_array( $link_type, $allowed_link_types, true ) ) {
				update_post_meta( $id, '_vc_story_link_type', $link_type );
			}
		}

		return new WP_REST_Response( [ 'id' => $id ], 200 );
	}

	/**
	 * Deletar story
	 */
	public function delete_story( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = (int) $request->get_param( 'id' );
		$post = get_post( $id );

		if ( ! $post || CPT_Story::SLUG !== $post->post_type ) {
			return new WP_Error( 'vc_story_not_found', __( 'Story não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Verificar permissão
		$restaurant_id = (int) get_post_meta( $id, '_vc_story_restaurant_id', true );
		if ( ! current_user_can( 'manage_options' ) ) {
			$user_restaurant_id = (int) get_user_meta( get_current_user_id(), 'vc_restaurant_id', true );
			if ( $user_restaurant_id !== $restaurant_id ) {
				return new WP_Error( 'vc_forbidden', __( 'Você não tem permissão para deletar este story.', 'vemcomer' ), [ 'status' => 403 ] );
			}
		}

		wp_delete_post( $id, true );

		return new WP_REST_Response( [ 'deleted' => true ], 200 );
	}

	/**
	 * Marcar story como visto
	 */
	public function mark_as_viewed( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$story_id = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			return new WP_Error( 'vc_not_authenticated', __( 'Usuário não autenticado.', 'vemcomer' ), [ 'status' => 401 ] );
		}

		$post = get_post( $story_id );
		if ( ! $post || CPT_Story::SLUG !== $post->post_type ) {
			return new WP_Error( 'vc_story_not_found', __( 'Story não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
		}

		// Adicionar ao array de stories vistos
		$viewed_stories = get_user_meta( $user_id, 'vc_stories_viewed', true );
		if ( ! is_array( $viewed_stories ) ) {
			$viewed_stories = [];
		}

		if ( ! in_array( $story_id, $viewed_stories, true ) ) {
			$viewed_stories[] = $story_id;
			update_user_meta( $user_id, 'vc_stories_viewed', $viewed_stories );
		}

		return new WP_REST_Response( [ 'viewed' => true ], 200 );
	}

	/**
	 * Calcula tempo relativo (ex: "2h", "1d")
	 */
	private function get_relative_time( int $timestamp ): string {
		$diff = time() - $timestamp;

		if ( $diff < 60 ) {
			return __( 'agora', 'vemcomer' );
		} elseif ( $diff < 3600 ) {
			$minutes = floor( $diff / 60 );
			return sprintf( __( '%dm', 'vemcomer' ), $minutes );
		} elseif ( $diff < 86400 ) {
			$hours = floor( $diff / 3600 );
			return sprintf( __( '%dh', 'vemcomer' ), $hours );
		} elseif ( $diff < 604800 ) {
			$days = floor( $diff / 86400 );
			return sprintf( __( '%dd', 'vemcomer' ), $days );
		} else {
			$weeks = floor( $diff / 604800 );
			return sprintf( __( '%dsem', 'vemcomer' ), $weeks );
		}
	}
}

