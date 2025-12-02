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
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
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
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    public function can_edit_item( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $item_id = (int) $request->get_param( 'id' );
        $item = get_post( $item_id );

        if ( ! $item || $item->post_type !== 'vc_menu_item' ) {
            return false;
        }

        // Verificar se o usuário é admin
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Verificar se o usuário é dono do restaurante associado ao item
        $restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
        if ( $restaurant_id <= 0 ) {
            return false;
        }

        $user_id = get_current_user_id();
        $user_restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $user_restaurant_id === $restaurant_id ) {
            return true;
        }

        // Verificar se o usuário é autor do restaurante
        $restaurant = get_post( $restaurant_id );
        if ( $restaurant && (int) $restaurant->post_author === $user_id ) {
            return true;
        }

        // Fallback: verificar capability padrão
        return current_user_can( 'edit_post', $item_id );
    }

    public function can_delete_item( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $item_id = (int) $request->get_param( 'id' );
        $item = get_post( $item_id );

        if ( ! $item || $item->post_type !== 'vc_menu_item' ) {
            return false;
        }

        // Verificar se o usuário é admin
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Verificar se o usuário é dono do restaurante associado ao item
        $restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
        if ( $restaurant_id <= 0 ) {
            return false;
        }

        $user_id = get_current_user_id();
        $user_restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $user_restaurant_id === $restaurant_id ) {
            return true;
        }

        // Verificar se o usuário é autor do restaurante
        $restaurant = get_post( $restaurant_id );
        if ( $restaurant && (int) $restaurant->post_author === $user_id ) {
            return true;
        }

        // Fallback: verificar capability padrão
        return current_user_can( 'delete_post', $item_id );
    }

    public function toggle_availability( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'vc_menu_item' ) {
            return new WP_Error( 'vc_menu_item_not_found', __( 'Item não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        // A verificação de permissão já foi feita pela permission_callback
        // Não é necessário verificar o nonce manualmente aqui

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

        // A verificação de permissão já foi feita pela permission_callback
        // Não é necessário verificar o nonce manualmente aqui

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

