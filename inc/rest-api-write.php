<?php
/**
 * REST API – Escrita para Restaurantes (POST/PATCH/DELETE).
 *
 * @package VemComer\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

require_once __DIR__ . '/rest-middleware-rate-limit.php';
require_once __DIR__ . '/audit/logger.php';

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
                                                return current_user_can( 'create_vc_restaurants' ) || current_user_can( 'publish_vc_restaurants' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Capabilidades personalizadas.
                                        },
                                        'args'                => vc_rest_write_args(),
                                ),
                        )
                );

                register_rest_route(
                        'vemcomer/v1',
                        '/restaurants/(?P<id>\\d+)',
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
                                array(
                                        'methods'             => WP_REST_Server::DELETABLE,
                                        'callback'            => 'vc_rest_delete_restaurant',
                                        'permission_callback' => function ( WP_REST_Request $req ) {
                                                $id = (int) $req['id'];
                                                return $id && current_user_can( 'delete_post', $id );
                                        },
                                        'args'                => array(
                                                'id' => array(
                                                        'type'     => 'integer',
                                                        'required' => true,
                                                ),
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
                        'required' => false,
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
        $out['cnpj']       = array_key_exists( 'cnpj', $data ) ? preg_replace( '/[^\\d\\.\\-\/]/', '', (string) $data['cnpj'] ) : null;
        $out['whatsapp']   = array_key_exists( 'whatsapp', $data ) ? sanitize_text_field( (string) $data['whatsapp'] ) : null;
        $out['site']       = array_key_exists( 'site', $data ) ? esc_url_raw( (string) $data['site'] ) : null;
        $out['open_hours'] = array_key_exists( 'open_hours', $data ) ? wp_kses_post( (string) $data['open_hours'] ) : null;
        $out['delivery']   = array_key_exists( 'delivery', $data ) ? ( ! empty( $data['delivery'] ) ? '1' : '0' ) : null;
        $out['address']    = array_key_exists( 'address', $data ) ? sanitize_text_field( (string) $data['address'] ) : null;
        $out['cuisine']    = array_key_exists( 'cuisine', $data ) ? sanitize_title( (string) $data['cuisine'] ) : null;
        $out['location']   = array_key_exists( 'location', $data ) ? sanitize_title( (string) $data['location'] ) : null;
        return $out;
}

/**
 * Aplica rate limiting e retorna resposta caso bloqueado.
 *
 * @param string $operation Operação atual.
 *
 * @return WP_REST_Response|null
 */
function vc_rest_rate_limit_or_block( string $operation ) {
        $key = 'restaurants_' . $operation;
        if ( ! vc_rate_limit_allow( $key, 60, 60 ) ) {
                return new WP_REST_Response( array( 'message' => __( 'Rate limit excedido.', 'vemcomer' ) ), 429 );
        }
        return null;
}

/**
 * Cria um restaurante via REST.
 *
 * @param WP_REST_Request $req Requisição recebida.
 *
 * @return WP_REST_Response
 */
function vc_rest_create_restaurant( WP_REST_Request $req ): WP_REST_Response {
        $rl = vc_rest_rate_limit_or_block( 'create' );
        if ( $rl ) {
                return $rl;
        }

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

        vc_audit_log(
                'create_restaurant',
                array(
                        'id'   => (int) $pid,
                        'data' => $data,
                )
        );

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
        $rl = vc_rest_rate_limit_or_block( 'update' );
        if ( $rl ) {
                return $rl;
        }

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

        vc_audit_log(
                'update_restaurant',
                array(
                        'id'   => (int) $id,
                        'data' => $data,
                )
        );

        return new WP_REST_Response(
                array(
                        'id'   => (int) $id,
                        'link' => get_permalink( $id ),
                ),
                200
        );
}

/**
 * Deleta um restaurante via REST.
 *
 * @param WP_REST_Request $req Requisição recebida.
 *
 * @return WP_REST_Response
 */
function vc_rest_delete_restaurant( WP_REST_Request $req ): WP_REST_Response {
        $rl = vc_rest_rate_limit_or_block( 'delete' );
        if ( $rl ) {
                return $rl;
        }

        $id   = (int) $req['id'];
        $post = get_post( $id );
        if ( ! $post || 'vc_restaurant' !== $post->post_type ) {
                return new WP_REST_Response( array( 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ), 404 );
        }

        if ( post_type_exists( 'vc_menu_item' ) ) {
                $items = get_posts(
                        array(
                                'post_type'        => 'vc_menu_item',
                                'numberposts'      => -1,
                                'fields'           => 'ids',
                                'meta_query'       => array(
                                        array(
                                                'key'   => '_vc_restaurant_id',
                                                'value' => (string) $id,
                                        ),
                                ),
                                'suppress_filters' => true,
                        )
                );

                foreach ( $items as $menu_id ) {
                        wp_delete_post( $menu_id, true );
                }
        }

        $deleted = wp_delete_post( $id, true );
        if ( ! $deleted ) {
                return new WP_REST_Response( array( 'message' => __( 'Falha ao deletar.', 'vemcomer' ) ), 500 );
        }

        vc_audit_log(
                'delete_restaurant',
                array(
                        'id' => (int) $id,
                )
        );

        return new WP_REST_Response( array( 'deleted' => true, 'id' => (int) $id ), 200 );
}

/**
 * Persiste termos e metadados compatíveis com o metabox do CPT.
 *
 * @param int   $pid  ID do post.
 * @param array $data Dados sanitizados.
 */
function vc_rest_apply_terms_and_meta( int $pid, array $data ): void {
        $meta_map = array(
                'cnpj'       => 'vc_restaurant_cnpj',
                'whatsapp'   => 'vc_restaurant_whatsapp',
                'site'       => 'vc_restaurant_site',
                'open_hours' => 'vc_restaurant_open_hours',
                'delivery'   => 'vc_restaurant_delivery',
                'address'    => 'vc_restaurant_address',
        );

        foreach ( $meta_map as $key => $meta_key ) {
                if ( array_key_exists( $key, $data ) && null !== $data[ $key ] ) {
                        update_post_meta( $pid, $meta_key, $data[ $key ] );
                }
        }

        if ( ! empty( $data['cuisine'] ?? '' ) && taxonomy_exists( 'vc_cuisine' ) ) {
                wp_set_object_terms( $pid, array( $data['cuisine'] ), 'vc_cuisine', false );
        }
        if ( ! empty( $data['location'] ?? '' ) && taxonomy_exists( 'vc_location' ) ) {
                wp_set_object_terms( $pid, array( $data['location'] ), 'vc_location', false );
        }
}
