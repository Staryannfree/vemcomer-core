<?php
/**
 * Menu_Items_Status_Controller — Endpoints para pausar/ativar e deletar itens do cardápio
 * @package VemComerCore
 */

namespace VC\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Menu_Items_Status_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/menu-items/(?P<id>\d+)/toggle-availability', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_availability' ],
            'permission_callback' => [ $this, 'can_edit_item' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( 'vemcomer/v1', '/menu-items/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_item' ],
            'permission_callback' => [ $this, 'can_delete_item' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    public function can_edit_item( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        return current_user_can( 'edit_vc_menu_item', (int) $request->get_param( 'id' ) );
    }

    public function can_delete_item( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        return current_user_can( 'delete_post', (int) $request->get_param( 'id' ) );
    }

    public function toggle_availability( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'vc_menu_item' ) {
            return new WP_Error( 'vc_menu_item_not_found', __( 'Item não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'vc_invalid_nonce', __( 'Nonce inválido.', 'vemcomer' ), [ 'status' => 403 ] );
        }

        $current = (bool) get_post_meta( $id, '_vc_is_available', true );
        $new     = ! $current;

        if ( $new ) {
            update_post_meta( $id, '_vc_is_available', '1' );
        } else {
            delete_post_meta( $id, '_vc_is_available' );
        }

        return new WP_REST_Response( [
            'success'    => true,
            'available'  => $new,
            'message'    => $new ? __( 'Produto ativado.', 'vemcomer' ) : __( 'Produto pausado.', 'vemcomer' ),
        ], 200 );
    }

    public function delete_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'vc_menu_item' ) {
            return new WP_Error( 'vc_menu_item_not_found', __( 'Item não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new WP_Error( 'vc_invalid_nonce', __( 'Nonce inválido.', 'vemcomer' ), [ 'status' => 403 ] );
        }

        $trashed = wp_trash_post( $id );

        if ( ! $trashed ) {
            return new WP_Error( 'vc_delete_failed', __( 'Não foi possível deletar este item.', 'vemcomer' ), [ 'status' => 500 ] );
        }

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Item movido para a lixeira.', 'vemcomer' ),
        ], 200 );
    }
}

