<?php
/**
 * Menu_Items_Service — Serviço para gerenciar itens do cardápio
 * @package VemComerCore
 */

namespace VC\Services;

use VC\Model\CPT_MenuItem;
use VC\Utils\Restaurant_Helper;
use WP_Error;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Serviço para gerenciar itens do cardápio
 */
class Menu_Items_Service {
    /**
     * Cria um novo item do cardápio
     * 
     * @param array $data Dados do produto (title, description, price, etc.)
     * @param int   $restaurant_id ID do restaurante
     * @return int|WP_Error ID do produto criado ou WP_Error em caso de erro
     */
    public function create( array $data, int $restaurant_id ): int|\WP_Error {
        if ( $restaurant_id <= 0 ) {
            do_action( 'vemcomer/data_inconsistent', 'product_creation', [
                'restaurant_id' => $restaurant_id,
                'data' => $data,
            ] );
            return new \WP_Error( 'invalid_restaurant', __( 'Restaurante inválido.', 'vemcomer' ) );
        }

        // Verificar se o restaurante existe
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type ) {
            do_action( 'vemcomer/data_inconsistent', 'product_creation', [
                'restaurant_id' => $restaurant_id,
                'reason' => 'restaurant_not_found',
                'data' => $data,
            ] );
            return new \WP_Error( 'restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ) );
        }

        // Validar título (obrigatório)
        $title = sanitize_text_field( $data['title'] ?? '' );
        if ( empty( $title ) ) {
            return new \WP_Error( 'title_required', __( 'O título é obrigatório.', 'vemcomer' ) );
        }

        // Criar post
        $post_data = [
            'post_title'   => $title,
            'post_content' => isset( $data['description'] ) ? wp_kses_post( $data['description'] ) : '',
            'post_excerpt' => isset( $data['description'] ) ? wp_trim_words( wp_kses_post( $data['description'] ), 20 ) : '',
            'post_status'  => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'publish',
            'post_type'    => CPT_MenuItem::SLUG,
        ];

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Anexar restaurante ao produto
        Restaurant_Helper::attach_restaurant_to_product( $post_id, $restaurant_id );

        // Salvar meta fields
        if ( isset( $data['price'] ) ) {
            update_post_meta( $post_id, '_vc_price', sanitize_text_field( (string) $data['price'] ) );
        }

        if ( isset( $data['prep_time'] ) ) {
            update_post_meta( $post_id, '_vc_prep_time', absint( $data['prep_time'] ) );
        }

        if ( isset( $data['is_available'] ) ) {
            update_post_meta( $post_id, '_vc_is_available', (bool) $data['is_available'] ? '1' : '0' );
        }

        if ( isset( $data['is_featured'] ) ) {
            update_post_meta( $post_id, '_vc_menu_item_featured', (bool) $data['is_featured'] ? '1' : '0' );
        }

        // Categoria
        if ( isset( $data['category_id'] ) && is_numeric( $data['category_id'] ) ) {
            $category_id = absint( $data['category_id'] );
            wp_set_object_terms( $post_id, [ $category_id ], 'vc_menu_category', false );
        }

        return $post_id;
    }

    /**
     * Atualiza um item do cardápio existente
     * 
     * @param int   $product_id ID do produto
     * @param array $data Dados para atualizar
     * @param int   $restaurant_id ID do restaurante (para validação)
     * @return bool|WP_Error True em caso de sucesso ou WP_Error
     */
    public function update( int $product_id, array $data, int $restaurant_id ): bool|\WP_Error {
        // Validar que o produto existe e pertence ao restaurante
        $product = get_post( $product_id );
        if ( ! $product || CPT_MenuItem::SLUG !== $product->post_type ) {
            return new \WP_Error( 'product_not_found', __( 'Produto não encontrado.', 'vemcomer' ) );
        }

        $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
        
        // Validar que o produto tem restaurante válido
        if ( $product_restaurant_id <= 0 ) {
            do_action( 'vemcomer/data_inconsistent', 'product_update', [
                'product_id' => $product_id,
                'restaurant_id' => $restaurant_id,
                'reason' => 'product_missing_restaurant_id',
            ] );
            return new \WP_Error( 'product_invalid', __( 'Produto sem restaurante associado.', 'vemcomer' ) );
        }
        
        if ( $product_restaurant_id !== $restaurant_id ) {
            return new \WP_Error( 'unauthorized', __( 'Você não tem permissão para editar este produto.', 'vemcomer' ) );
        }

        // Atualizar título se fornecido
        if ( isset( $data['title'] ) && ! empty( trim( $data['title'] ) ) ) {
            wp_update_post( [
                'ID'         => $product_id,
                'post_title' => sanitize_text_field( trim( $data['title'] ) ),
            ] );
        }

        // Atualizar descrição se fornecida
        if ( isset( $data['description'] ) ) {
            wp_update_post( [
                'ID'           => $product_id,
                'post_content' => wp_kses_post( $data['description'] ),
                'post_excerpt' => wp_trim_words( wp_kses_post( $data['description'] ), 20 ),
            ] );
        }

        // Atualizar meta fields
        if ( isset( $data['price'] ) ) {
            update_post_meta( $product_id, '_vc_price', sanitize_text_field( (string) $data['price'] ) );
        }

        if ( isset( $data['prep_time'] ) ) {
            update_post_meta( $product_id, '_vc_prep_time', absint( $data['prep_time'] ) );
        }

        if ( isset( $data['is_available'] ) ) {
            update_post_meta( $product_id, '_vc_is_available', (bool) $data['is_available'] ? '1' : '0' );
        }

        if ( isset( $data['is_featured'] ) ) {
            update_post_meta( $product_id, '_vc_menu_item_featured', (bool) $data['is_featured'] ? '1' : '0' );
        }

        // Categoria
        if ( isset( $data['category_id'] ) && is_numeric( $data['category_id'] ) ) {
            $category_id = absint( $data['category_id'] );
            wp_set_object_terms( $product_id, [ $category_id ], 'vc_menu_category', false );
        }

        return true;
    }

    /**
     * Deleta um item do cardápio
     * 
     * @param int $product_id ID do produto
     * @param int $restaurant_id ID do restaurante (para validação)
     * @return bool|WP_Error True em caso de sucesso ou WP_Error
     */
    public function delete( int $product_id, int $restaurant_id ): bool|\WP_Error {
        // Validar que o produto existe e pertence ao restaurante
        $product = get_post( $product_id );
        if ( ! $product || CPT_MenuItem::SLUG !== $product->post_type ) {
            return new \WP_Error( 'product_not_found', __( 'Produto não encontrado.', 'vemcomer' ) );
        }

        $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
        
        // Validar que o produto tem restaurante válido
        if ( $product_restaurant_id <= 0 ) {
            do_action( 'vemcomer/data_inconsistent', 'product_delete', [
                'product_id' => $product_id,
                'restaurant_id' => $restaurant_id,
                'reason' => 'product_missing_restaurant_id',
            ] );
            return new \WP_Error( 'product_invalid', __( 'Produto sem restaurante associado.', 'vemcomer' ) );
        }
        
        if ( $product_restaurant_id !== $restaurant_id ) {
            return new \WP_Error( 'unauthorized', __( 'Você não tem permissão para deletar este produto.', 'vemcomer' ) );
        }

        // Deletar produto (mover para lixeira)
        $result = wp_delete_post( $product_id, false );

        return $result instanceof WP_Post;
    }
}

