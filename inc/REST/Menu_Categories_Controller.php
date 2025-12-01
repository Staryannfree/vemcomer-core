<?php
/**
 * Menu_Categories_Controller — REST endpoints para categorias do cardápio
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Menu_Categories_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        // POST: Criar nova categoria (lojista)
        register_rest_route( 'vemcomer/v1', '/menu-categories', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_category' ],
            'permission_callback' => [ $this, 'can_manage_categories' ],
        ] );

        // GET: Listar categorias (público)
        register_rest_route( 'vemcomer/v1', '/menu-categories', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_categories' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Verifica se o usuário pode gerenciar categorias
     */
    public function can_manage_categories(): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof \WP_User ) {
            return false;
        }

        // Admin sempre pode
        if ( current_user_can( 'manage_categories' ) ) {
            return true;
        }

        // Lojista pode criar categorias
        return user_can( $user, 'edit_posts' ) || in_array( 'lojista', $user->roles, true );
    }

    /**
     * GET /wp-json/vemcomer/v1/menu-categories
     * Lista categorias do cardápio
     */
    public function get_categories( WP_REST_Request $request ): WP_REST_Response {
        $categories = get_terms( [
            'taxonomy'   => CPT_MenuItem::TAX_CATEGORY,
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $categories ) ) {
            return new WP_REST_Response( [], 200 );
        }

        $items = [];
        foreach ( $categories as $term ) {
            $items[] = [
                'id'    => $term->term_id,
                'name'  => $term->name,
                'slug'  => $term->slug,
                'count' => $term->count,
            ];
        }

        return new WP_REST_Response( $items, 200 );
    }

    /**
     * POST /wp-json/vemcomer/v1/menu-categories
     * Cria uma nova categoria do cardápio
     */
    public function create_category( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $body = $request->get_json_params();
        if ( ! $body ) {
            return new WP_Error(
                'vc_invalid_json',
                __( 'JSON inválido no body da requisição.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Validar nome (obrigatório)
        $name = sanitize_text_field( $body['name'] ?? '' );
        if ( empty( $name ) ) {
            return new WP_Error(
                'vc_name_required',
                __( 'O nome da categoria é obrigatório.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Verificar se já existe
        if ( term_exists( $name, CPT_MenuItem::TAX_CATEGORY ) ) {
            return new WP_Error(
                'vc_category_exists',
                __( 'Uma categoria com este nome já existe.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Criar o termo
        $result = wp_insert_term(
            $name,
            CPT_MenuItem::TAX_CATEGORY,
            [
                'slug' => sanitize_title( $name ),
            ]
        );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                'vc_category_creation_failed',
                $result->get_error_message(),
                [ 'status' => 500 ]
            );
        }

        $term_id = is_array( $result ) ? $result['term_id'] : $result;

        // Salvar campos adicionais se fornecidos
        if ( isset( $body['order'] ) && is_numeric( $body['order'] ) ) {
            update_term_meta( $term_id, '_vc_category_order', absint( $body['order'] ) );
        }

        // Imagem (data:image ou ID)
        if ( isset( $body['image'] ) && ! empty( $body['image'] ) ) {
            $image_url = sanitize_text_field( $body['image'] );
            
            // Se for data:image, fazer upload
            if ( strpos( $image_url, 'data:image' ) === 0 ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $upload = wp_upload_bits( 'category-' . $term_id . '.jpg', null, base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $image_url ) ) );
                if ( ! $upload['error'] ) {
                    $attachment = [
                        'post_mime_type' => 'image/jpeg',
                        'post_title'     => sanitize_file_name( $name ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    update_term_meta( $term_id, '_vc_category_image', $attach_id );
                }
            } elseif ( is_numeric( $image_url ) ) {
                // Se for ID de attachment
                update_term_meta( $term_id, '_vc_category_image', absint( $image_url ) );
            }
        }

        log_event( 'REST menu category created', [ 'term_id' => $term_id, 'name' => $name ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'id'      => $term_id,
            'name'    => $name,
            'message' => __( 'Categoria criada com sucesso!', 'vemcomer' ),
        ], 201 );
    }
}

