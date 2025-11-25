<?php
/**
 * Quick_Toggle_Controller — REST endpoints para toggle rápido de featured
 * @package VemComerCore
 */

namespace VC\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Quick_Toggle_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    public function routes(): void {
        // POST: Toggle featured de menu item
        register_rest_route( 'vemcomer/v1', '/menu-items/(?P<id>\d+)/toggle-featured', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_menu_item_featured' ],
            'permission_callback' => [ $this, 'check_edit_permission' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // POST: Toggle featured de restaurante
        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/toggle-featured', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_restaurant_featured' ],
            'permission_callback' => [ $this, 'check_edit_permission' ],
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
     * Verifica permissão de edição
     */
    public function check_edit_permission( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $id = (int) $request->get_param( 'id' );
        $route = $request->get_route();

        if ( strpos( $route, '/menu-items/' ) !== false ) {
            return current_user_can( 'edit_vc_menu_item', $id );
        }

        if ( strpos( $route, '/restaurants/' ) !== false ) {
            return current_user_can( 'edit_vc_restaurant', $id );
        }

        return current_user_can( 'edit_posts' );
    }

    /**
     * POST /wp-json/vemcomer/v1/menu-items/{id}/toggle-featured
     * Alterna featured de um item do cardápio
     */
    public function toggle_menu_item_featured( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );
        
        if ( $id <= 0 ) {
            return new WP_Error( 'vc_invalid_id', __( 'ID inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'vc_menu_item' ) {
            return new WP_Error( 'vc_not_found', __( 'Item não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        // Verificar nonce
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'vc_toggle_featured_' . $id ) ) {
            return new WP_Error( 'vc_invalid_nonce', __( 'Nonce inválido.', 'vemcomer' ), [ 'status' => 403 ] );
        }

        $current = (bool) get_post_meta( $id, '_vc_menu_item_featured', true );
        $new_value = ! $current;

        if ( $new_value ) {
            update_post_meta( $id, '_vc_menu_item_featured', '1' );
        } else {
            delete_post_meta( $id, '_vc_menu_item_featured' );
        }

        log_event( 'Menu item featured toggled', [
            'item_id' => $id,
            'featured' => $new_value,
        ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'featured' => $new_value,
            'message' => $new_value ? __( 'Prato marcado como Prato do Dia', 'vemcomer' ) : __( 'Prato removido dos Pratos do Dia', 'vemcomer' ),
        ], 200 );
    }

    /**
     * POST /wp-json/vemcomer/v1/restaurants/{id}/toggle-featured
     * Alterna featured de um restaurante
     */
    public function toggle_restaurant_featured( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );
        
        if ( $id <= 0 ) {
            return new WP_Error( 'vc_invalid_id', __( 'ID inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $post = get_post( $id );
        if ( ! $post || $post->post_type !== 'vc_restaurant' ) {
            return new WP_Error( 'vc_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        // Verificar nonce
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! $nonce || ! wp_verify_nonce( $nonce, 'vc_toggle_restaurant_featured_' . $id ) ) {
            return new WP_Error( 'vc_invalid_nonce', __( 'Nonce inválido.', 'vemcomer' ), [ 'status' => 403 ] );
        }

        $current = (bool) get_post_meta( $id, '_vc_restaurant_featured', true );
        $new_value = ! $current;

        if ( $new_value ) {
            update_post_meta( $id, '_vc_restaurant_featured', '1' );
        } else {
            delete_post_meta( $id, '_vc_restaurant_featured' );
        }

        log_event( 'Restaurant featured toggled', [
            'restaurant_id' => $id,
            'featured' => $new_value,
        ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'featured' => $new_value,
            'message' => $new_value ? __( 'Restaurante marcado como Destaque', 'vemcomer' ) : __( 'Restaurante removido dos Destaques', 'vemcomer' ),
        ], 200 );
    }

    /**
     * Enfileira scripts e estilos no admin
     */
    public function enqueue_admin_scripts( string $hook ): void {
        global $post_type;
        
        // Apenas nas listas de menu items e restaurantes
        if ( ! in_array( $hook, [ 'edit.php' ], true ) ) {
            return;
        }

        if ( ! in_array( $post_type, [ 'vc_menu_item', 'vc_restaurant' ], true ) ) {
            return;
        }

        $script_url = plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/admin-quick-toggle.js';
        wp_enqueue_script( 'vc-quick-toggle', $script_url, [ 'jquery' ], defined( 'VEMCOMER_CORE_VERSION' ) ? VEMCOMER_CORE_VERSION : '1.0.0', true );
        wp_localize_script( 'vc-quick-toggle', 'vcQuickToggle', [
            'restUrl' => rest_url( 'vemcomer/v1/' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
        ] );

        wp_add_inline_style( 'wp-admin', $this->get_inline_css() );
    }

    /**
     * CSS inline para os toggles
     */
    private function get_inline_css(): string {
        return '
            .vc-quick-toggle {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
            }
            .vc-quick-toggle input[type="checkbox"] {
                margin: 0;
                cursor: pointer;
            }
            .vc-quick-toggle .vc-toggle-label {
                font-size: 18px;
                user-select: none;
            }
            .vc-quick-toggle input:checked + .vc-toggle-label {
                color: #f0ad4e;
            }
            .vc-quick-toggle input:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
        ';
    }
}

