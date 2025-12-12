<?php
/**
 * Menu_Categories_Service — Serviço para gerenciar categorias de cardápio
 * @package VemComerCore
 */

namespace VC\Services;

use VC\Model\CPT_MenuItem;
use VC\Utils\Category_Helper;
use VC\Utils\Restaurant_Helper;
use WP_Error;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Serviço para gerenciar categorias de cardápio
 */
class Menu_Categories_Service {
    /**
     * Cria uma nova categoria de cardápio
     * 
     * @param array $data Dados da categoria (name, slug, order, image)
     * @param int   $restaurant_id ID do restaurante
     * @return int|WP_Error ID do termo criado ou WP_Error em caso de erro
     */
    public function create( array $data, int $restaurant_id ): int|\WP_Error {
        if ( $restaurant_id <= 0 ) {
            do_action( 'vemcomer/data_inconsistent', 'category_creation', [
                'restaurant_id' => $restaurant_id,
                'data' => $data,
            ] );
            return new \WP_Error( 'invalid_restaurant', __( 'Restaurante inválido.', 'vemcomer' ) );
        }

        // Verificar se o restaurante existe
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type ) {
            do_action( 'vemcomer/data_inconsistent', 'category_creation', [
                'restaurant_id' => $restaurant_id,
                'reason' => 'restaurant_not_found',
                'data' => $data,
            ] );
            return new \WP_Error( 'restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ) );
        }

        $name = sanitize_text_field( $data['name'] ?? '' );
        if ( empty( $name ) ) {
            return new \WP_Error( 'name_required', __( 'O nome da categoria é obrigatório.', 'vemcomer' ) );
        }

        $slug = isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '';

        // Criar categoria usando Category_Helper
        $term_id = Category_Helper::create_restaurant_category( $restaurant_id, $name, $slug );

        if ( is_wp_error( $term_id ) ) {
            return $term_id;
        }

        // Salvar campos adicionais
        if ( isset( $data['order'] ) && is_numeric( $data['order'] ) ) {
            update_term_meta( $term_id, '_vc_category_order', absint( $data['order'] ) );
        }

        // Imagem (data:image ou ID)
        if ( isset( $data['image'] ) && ! empty( $data['image'] ) ) {
            $image_url = sanitize_text_field( $data['image'] );
            
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

        return $term_id;
    }

    /**
     * Atualiza uma categoria existente
     * 
     * @param int   $term_id ID do termo
     * @param array $data Dados para atualizar
     * @param int   $restaurant_id ID do restaurante (para validação)
     * @return bool|WP_Error True em caso de sucesso ou WP_Error
     */
    public function update( int $term_id, array $data, int $restaurant_id ): bool|\WP_Error {
        // Validar que a categoria existe e pertence ao restaurante
        $term = get_term( $term_id, CPT_MenuItem::TAX_CATEGORY );
        if ( ! $term || is_wp_error( $term ) ) {
            return new \WP_Error( 'category_not_found', __( 'Categoria não encontrada.', 'vemcomer' ) );
        }

        if ( ! Category_Helper::belongs_to_restaurant( $term_id, $restaurant_id ) ) {
            return new \WP_Error( 'unauthorized', __( 'Você não tem permissão para editar esta categoria.', 'vemcomer' ) );
        }

        // Atualizar nome se fornecido
        if ( isset( $data['name'] ) && ! empty( trim( $data['name'] ) ) ) {
            wp_update_term( $term_id, CPT_MenuItem::TAX_CATEGORY, [
                'name' => sanitize_text_field( trim( $data['name'] ) ),
            ] );
        }

        // Atualizar ordem se fornecida
        if ( isset( $data['order'] ) && is_numeric( $data['order'] ) ) {
            update_term_meta( $term_id, '_vc_category_order', absint( $data['order'] ) );
        }

        // Atualizar imagem se fornecida
        if ( isset( $data['image'] ) ) {
            $image_url = sanitize_text_field( $data['image'] );
            
            if ( strpos( $image_url, 'data:image' ) === 0 ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $upload = wp_upload_bits( 'category-' . $term_id . '.jpg', null, base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $image_url ) ) );
                if ( ! $upload['error'] ) {
                    $attachment = [
                        'post_mime_type' => 'image/jpeg',
                        'post_title'     => sanitize_file_name( $term->name ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    update_term_meta( $term_id, '_vc_category_image', $attach_id );
                }
            } elseif ( is_numeric( $image_url ) ) {
                update_term_meta( $term_id, '_vc_category_image', absint( $image_url ) );
            } elseif ( empty( $image_url ) ) {
                delete_term_meta( $term_id, '_vc_category_image' );
            }
        }

        return true;
    }

    /**
     * Deleta uma categoria
     * 
     * @param int $term_id ID do termo
     * @param int $restaurant_id ID do restaurante (para validação)
     * @return bool|WP_Error True em caso de sucesso ou WP_Error
     */
    public function delete( int $term_id, int $restaurant_id ): bool|\WP_Error {
        // Validar que a categoria existe e pertence ao restaurante
        $term = get_term( $term_id, CPT_MenuItem::TAX_CATEGORY );
        if ( ! $term || is_wp_error( $term ) ) {
            return new \WP_Error( 'category_not_found', __( 'Categoria não encontrada.', 'vemcomer' ) );
        }

        if ( ! Category_Helper::belongs_to_restaurant( $term_id, $restaurant_id ) ) {
            return new \WP_Error( 'unauthorized', __( 'Você não tem permissão para deletar esta categoria.', 'vemcomer' ) );
        }

        // Deletar termo
        $result = wp_delete_term( $term_id, CPT_MenuItem::TAX_CATEGORY );

        return ! is_wp_error( $result ) && $result;
    }
}

