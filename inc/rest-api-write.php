<?php
/**
 * REST API – Escrita para Restaurantes (POST/PATCH).
 *
 * Rotas:
 *   POST   /wp-json/vemcomer/v1/restaurants
 *   PATCH  /wp-json/vemcomer/v1/restaurants/(?P<id>\d+)
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'vemcomer/v1',
			'/restaurants',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => 'vc_rest_create_restaurant',
					'permission_callback' => function () {
						return current_user_can( 'create_vc_restaurants' ) || current_user_can( 'publish_vc_restaurants' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom capabilities registered for VC restaurants.
					},
					'args'                => vc_rest_write_args(),
				),
			)
		);

		register_rest_route(
			'vemcomer/v1',
			'/restaurants/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PATCH',
					'callback'            => 'vc_rest_update_restaurant',
					'permission_callback' => function ( WP_REST_Request $req ) {
						$id = (int) $req['id'];
						return $id && current_user_can( 'edit_post', $id );
					},
					'args'                => array_merge(
						array(
							'id' => array(
								'type'     => 'integer',
								'required' => true,
							),
						),
						vc_rest_write_args()
					),
				),
			)
		);
	}
);

/**
 * Campos aceitos no corpo JSON.
 *
 * @return array
 */
function vc_rest_write_args(): array {
	return array(
		'title'      => array(
			'type'     => 'string',
			'required' => true,
		),
		'cnpj'       => array(
			'type'     => 'string',
			'required' => false,
		),
		'whatsapp'   => array(
			'type'     => 'string',
			'required' => false,
		),
		'site'       => array(
			'type'     => 'string',
			'required' => false,
		),
		'open_hours' => array(
			'type'     => 'string',
			'required' => false,
		),
		'delivery'   => array(
			'type'     => 'boolean',
			'required' => false,
		),
		'address'    => array(
			'type'     => 'string',
			'required' => false,
		),
		'cuisine'    => array(
			'type'     => 'string',
			'required' => false,
		),
		'location'   => array(
			'type'     => 'string',
			'required' => false,
		),
	);
}

/**
 * Sanitiza a carga útil das requisições REST.
 *
 * @param array $data Dados originais da requisição.
 *
 * @return array
 */
function vc_rest_sanitize_payload( array $data ): array {
	$out = array();
	if ( array_key_exists( 'title', $data ) ) {
		$out['post_title'] = sanitize_text_field( (string) $data['title'] );
	}
	if ( array_key_exists( 'cnpj', $data ) ) {
		$out['cnpj'] = preg_replace( '/[^\d\.\-\/]/', '', (string) $data['cnpj'] );
	}
	if ( array_key_exists( 'whatsapp', $data ) ) {
		$out['whatsapp'] = sanitize_text_field( (string) $data['whatsapp'] );
	}
	if ( array_key_exists( 'site', $data ) ) {
		$out['site'] = esc_url_raw( (string) $data['site'] );
	}
	if ( array_key_exists( 'open_hours', $data ) ) {
		$out['open_hours'] = wp_kses_post( (string) $data['open_hours'] );
	}
	if ( array_key_exists( 'delivery', $data ) ) {
		$out['delivery'] = ! empty( $data['delivery'] ) ? '1' : '0';
	}
	if ( array_key_exists( 'address', $data ) ) {
		$out['address'] = sanitize_text_field( (string) $data['address'] );
	}
	if ( array_key_exists( 'cuisine', $data ) ) {
		$out['cuisine'] = sanitize_title( (string) $data['cuisine'] );
	}
	if ( array_key_exists( 'location', $data ) ) {
		$out['location'] = sanitize_title( (string) $data['location'] );
	}
	return $out;
}

/**
 * Cria um restaurante via REST.
 *
 * @param WP_REST_Request $req Requisição recebida.
 *
 * @return WP_REST_Response
 */
function vc_rest_create_restaurant( WP_REST_Request $req ): WP_REST_Response {
	$data = vc_rest_sanitize_payload( (array) $req->get_json_params() );
	if ( empty( $data['post_title'] ?? '' ) ) {
				return new WP_REST_Response( array( 'message' => __( 'O campo "title" é obrigatório.', 'vemcomer' ) ), 400 );
	}

	$pid = wp_insert_post(
		array(
			'post_type'   => 'vc_restaurant',
			'post_status' => 'publish',
			'post_title'  => $data['post_title'],
		)
	);

	if ( is_wp_error( $pid ) ) {
		return new WP_REST_Response( array( 'message' => $pid->get_error_message() ), 500 );
	}

	vc_rest_apply_terms_and_meta( $pid, $data );

	return new WP_REST_Response(
		array(
			'id'   => (int) $pid,
			'link' => get_permalink( $pid ),
		),
		201
	);
}

/**
 * Atualiza um restaurante via REST.
 *
 * @param WP_REST_Request $req Requisição recebida.
 *
 * @return WP_REST_Response
 */
function vc_rest_update_restaurant( WP_REST_Request $req ): WP_REST_Response {
	$id   = (int) $req['id'];
	$post = get_post( $id );
	if ( ! $post || 'vc_restaurant' !== $post->post_type ) {
				return new WP_REST_Response( array( 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ), 404 );
	}

	$data = vc_rest_sanitize_payload( (array) $req->get_json_params() );

	if ( ! empty( $data['post_title'] ?? '' ) ) {
		wp_update_post(
			array(
				'ID'         => $id,
				'post_title' => $data['post_title'],
			)
		);
	}

	vc_rest_apply_terms_and_meta( $id, $data );

	return new WP_REST_Response(
		array(
			'id'   => (int) $id,
			'link' => get_permalink( $id ),
		),
		200
	);
}

/**
 * Persiste termos e metadados compatíveis com o metabox do CPT.
 *
 * @param int   $pid  ID do post.
 * @param array $data Dados sanitizados.
 */
function vc_rest_apply_terms_and_meta( int $pid, array $data ): void {
		// Metas compatíveis com o metabox existente.
		$meta_map = array(
			'cnpj'       => 'vc_restaurant_cnpj',
			'whatsapp'   => 'vc_restaurant_whatsapp',
			'site'       => 'vc_restaurant_site',
			'open_hours' => 'vc_restaurant_open_hours',
			'delivery'   => 'vc_restaurant_delivery',
			'address'    => 'vc_restaurant_address',
		);

		foreach ( $meta_map as $key => $meta_key ) {
			if ( array_key_exists( $key, $data ) ) {
				update_post_meta( $pid, $meta_key, $data[ $key ] );
			}
		}

		// Termos (se informados).
		if ( ! empty( $data['cuisine'] ?? '' ) && taxonomy_exists( 'vc_cuisine' ) ) {
			wp_set_object_terms( $pid, array( $data['cuisine'] ), 'vc_cuisine', false );
		}
		if ( ! empty( $data['location'] ?? '' ) && taxonomy_exists( 'vc_location' ) ) {
			wp_set_object_terms( $pid, array( $data['location'] ), 'vc_location', false );
		}
}
