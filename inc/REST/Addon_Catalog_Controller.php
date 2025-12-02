<?php
/**
 * REST Controller para Catálogo de Adicionais
 * Permite que lojistas busquem grupos recomendados baseados nas categorias do restaurante
 * e copiem grupos do catálogo para sua loja
 * 
 * @package VemComerCore
 */

namespace VC\REST;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Addon_Catalog_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        // GET: Buscar grupos recomendados para o restaurante do lojista
        register_rest_route( 'vemcomer/v1', '/addon-catalog/recommended-groups', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_recommended_groups' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );

        // GET: Buscar itens de um grupo do catálogo
        register_rest_route( 'vemcomer/v1', '/addon-catalog/groups/(?P<id>\d+)/items', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_group_items' ],
            'permission_callback' => [ $this, 'can_access' ],
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

        // POST: Copiar grupo do catálogo para a loja do lojista
        register_rest_route( 'vemcomer/v1', '/addon-catalog/groups/(?P<id>\d+)/copy-to-store', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'copy_group_to_store' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
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

        // POST: Vincular grupo copiado a um produto
        register_rest_route( 'vemcomer/v1', '/addon-catalog/link-group-to-product', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'link_group_to_product' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
            'args'                => [
                'product_id' => [
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'absint',
                ],
                'group_id' => [
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }

    public function can_access(): bool {
        return is_user_logged_in();
    }

    public function can_manage_store( \WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Verificar se o usuário é lojista e tem um restaurante associado
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id > 0 ) {
            $restaurant = get_post( $restaurant_id );
            if ( $restaurant && $restaurant->post_type === 'vc_restaurant' ) {
                // Verificar se é o autor ou tem permissão
                if ( (int) $restaurant->post_author === $user_id || current_user_can( 'edit_vc_restaurant', $restaurant_id ) ) {
                    return true;
                }
            }
        }

        return current_user_can( 'manage_options' );
    }

    /**
     * GET /wp-json/vemcomer/v1/addon-catalog/recommended-groups
     * Retorna grupos de adicionais recomendados baseados nas categorias do restaurante do lojista
     */
    public function get_recommended_groups( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
                'groups'  => [],
            ], 404 );
        }

        // Buscar categorias do restaurante
        $restaurant_categories = wp_get_object_terms( $restaurant_id, 'vc_cuisine', [ 'fields' => 'ids' ] );

        if ( is_wp_error( $restaurant_categories ) || empty( $restaurant_categories ) ) {
            return new \WP_REST_Response( [
                'success' => true,
                'message' => __( 'Nenhuma categoria encontrada para este restaurante.', 'vemcomer' ),
                'groups'  => [],
            ] );
        }

        // Buscar grupos do catálogo que estão vinculados a essas categorias
        $groups_query = new \WP_Query( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_vc_is_active',
                    'value' => '1',
                ],
            ],
            'tax_query'      => [
                [
                    'taxonomy' => 'vc_cuisine',
                    'field'    => 'term_id',
                    'terms'    => $restaurant_categories,
                ],
            ],
        ] );

        $groups = [];
        if ( $groups_query->have_posts() ) {
            foreach ( $groups_query->posts as $group ) {
                $groups[] = [
                    'id'             => $group->ID,
                    'name'           => $group->post_title,
                    'description'    => $group->post_content,
                    'selection_type' => get_post_meta( $group->ID, '_vc_selection_type', true ) ?: 'multiple',
                    'min_select'      => (int) get_post_meta( $group->ID, '_vc_min_select', true ),
                    'max_select'     => (int) get_post_meta( $group->ID, '_vc_max_select', true ),
                    'is_required'    => get_post_meta( $group->ID, '_vc_is_required', true ) === '1',
                ];
            }
        }

        return new \WP_REST_Response( [
            'success' => true,
            'groups'  => $groups,
        ] );
    }

    /**
     * GET /wp-json/vemcomer/v1/addon-catalog/groups/{id}/items
     * Retorna os itens de um grupo do catálogo
     */
    public function get_group_items( \WP_REST_Request $request ): \WP_REST_Response {
        $group_id = (int) $request->get_param( 'id' );

        $group = get_post( $group_id );
        if ( ! $group || $group->post_type !== 'vc_addon_group' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado.', 'vemcomer' ),
                'items'   => [],
            ], 404 );
        }

        // Buscar itens do grupo
        $items_query = new \WP_Query( [
            'post_type'      => 'vc_addon_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
                [
                    'key'   => '_vc_is_active',
                    'value' => '1',
                ],
            ],
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $items = [];
        if ( $items_query->have_posts() ) {
            foreach ( $items_query->posts as $item ) {
                $items[] = [
                    'id'             => $item->ID,
                    'name'           => $item->post_title,
                    'description'   => $item->post_content,
                    'default_price'  => (float) get_post_meta( $item->ID, '_vc_default_price', true ),
                    'allow_quantity' => get_post_meta( $item->ID, '_vc_allow_quantity', true ) === '1',
                    'max_quantity'   => (int) get_post_meta( $item->ID, '_vc_max_quantity', true ) ?: 1,
                ];
            }
        }

        return new \WP_REST_Response( [
            'success' => true,
            'group'   => [
                'id'             => $group->ID,
                'name'           => $group->post_title,
                'description'   => $group->post_content,
                'selection_type' => get_post_meta( $group->ID, '_vc_selection_type', true ) ?: 'multiple',
                'min_select'     => (int) get_post_meta( $group->ID, '_vc_min_select', true ),
                'max_select'     => (int) get_post_meta( $group->ID, '_vc_max_select', true ),
                'is_required'   => get_post_meta( $group->ID, '_vc_is_required', true ) === '1',
            ],
            'items'   => $items,
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/groups/{id}/copy-to-store
     * Copia um grupo do catálogo para a loja do lojista
     */
    public function copy_group_to_store( \WP_REST_Request $request ): \WP_REST_Response {
        $group_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Buscar o grupo do catálogo
        $catalog_group = get_post( $group_id );
        if ( ! $catalog_group || $catalog_group->post_type !== 'vc_addon_group' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo do catálogo não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Criar grupo na loja (usando vc_product_modifier como base)
        // Por enquanto, vamos criar como um grupo de modificadores vinculado ao restaurante
        $store_group_id = wp_insert_post( [
            'post_type'    => 'vc_product_modifier',
            'post_title'   => $catalog_group->post_title,
            'post_content' => $catalog_group->post_content,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ] );

        if ( is_wp_error( $store_group_id ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Erro ao criar grupo na loja.', 'vemcomer' ),
            ], 500 );
        }

        // Copiar configurações do grupo
        update_post_meta( $store_group_id, '_vc_restaurant_id', $restaurant_id );
        update_post_meta( $store_group_id, '_vc_selection_type', get_post_meta( $group_id, '_vc_selection_type', true ) ?: 'multiple' );
        update_post_meta( $store_group_id, '_vc_min_select', get_post_meta( $group_id, '_vc_min_select', true ) );
        update_post_meta( $store_group_id, '_vc_max_select', get_post_meta( $group_id, '_vc_max_select', true ) );
        update_post_meta( $store_group_id, '_vc_is_required', get_post_meta( $group_id, '_vc_is_required', true ) );
        update_post_meta( $store_group_id, '_vc_catalog_group_id', $group_id ); // Referência ao grupo original

        // Buscar e copiar itens do grupo
        $catalog_items = get_posts( [
            'post_type'      => 'vc_addon_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
                [
                    'key'   => '_vc_is_active',
                    'value' => '1',
                ],
            ],
        ] );

        $copied_items = [];
        foreach ( $catalog_items as $catalog_item ) {
            // Criar item na loja
            $store_item_id = wp_insert_post( [
                'post_type'    => 'vc_product_modifier',
                'post_title'   => $catalog_item->post_title,
                'post_content' => $catalog_item->post_content,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
            ] );

            if ( ! is_wp_error( $store_item_id ) ) {
                // Copiar configurações do item
                update_post_meta( $store_item_id, '_vc_restaurant_id', $restaurant_id );
                update_post_meta( $store_item_id, '_vc_group_id', $store_group_id );
                update_post_meta( $store_item_id, '_vc_price', get_post_meta( $catalog_item->ID, '_vc_default_price', true ) ?: '0.00' );
                update_post_meta( $store_item_id, '_vc_allow_quantity', get_post_meta( $catalog_item->ID, '_vc_allow_quantity', true ) );
                update_post_meta( $store_item_id, '_vc_max_quantity', get_post_meta( $catalog_item->ID, '_vc_max_quantity', true ) ?: '1' );
                update_post_meta( $store_item_id, '_vc_catalog_item_id', $catalog_item->ID ); // Referência ao item original

                $copied_items[] = $store_item_id;
            }
        }

        return new \WP_REST_Response( [
            'success'      => true,
            'message'      => __( 'Grupo copiado com sucesso para sua loja.', 'vemcomer' ),
            'group_id'     => $store_group_id,
            'items_count'  => count( $copied_items ),
            'modifier_ids' => array_merge( [ $store_group_id ], $copied_items ), // IDs dos modificadores criados
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/link-group-to-product
     * Vincula um grupo de modificadores (já copiado) a um produto
     */
    public function link_group_to_product( \WP_REST_Request $request ): \WP_REST_Response {
        $product_id = (int) $request->get_param( 'product_id' );
        $group_id = (int) $request->get_param( 'group_id' );

        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Verificar se o produto existe e pertence ao restaurante
        $product = get_post( $product_id );
        if ( ! $product || $product->post_type !== 'vc_menu_item' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Produto não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
        if ( $product_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Você não tem permissão para modificar este produto.', 'vemcomer' ),
            ], 403 );
        }

        // Buscar o grupo principal
        $group_post = get_post( $group_id );
        if ( ! $group_post || $group_post->post_type !== 'vc_product_modifier' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado na sua loja.', 'vemcomer' ),
            ], 404 );
        }

        // Buscar todos os modificadores do grupo (itens que pertencem ao grupo)
        $group_modifiers = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
            ],
        ] );

        // Adicionar o grupo principal também (se não estiver na lista)
        $group_modifiers_ids = array_map( function( $m ) { return $m->ID; }, $group_modifiers );
        if ( ! in_array( $group_id, $group_modifiers_ids, true ) ) {
            $group_modifiers[] = $group_post;
        }

        if ( empty( $group_modifiers ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado na sua loja.', 'vemcomer' ),
            ], 404 );
        }

        // Vincular modificadores ao produto
        $current_modifiers = get_post_meta( $product_id, '_vc_menu_item_modifiers', true );
        $current_modifiers = is_array( $current_modifiers ) ? $current_modifiers : [];

        $added_count = 0;
        foreach ( $group_modifiers as $modifier ) {
            $modifier_id = $modifier->ID;
            if ( ! in_array( $modifier_id, $current_modifiers, true ) ) {
                $current_modifiers[] = $modifier_id;
                $added_count++;

                // Atualizar meta reversa
                $modifier_items = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
                $modifier_items = is_array( $modifier_items ) ? $modifier_items : [];
                if ( ! in_array( $product_id, $modifier_items, true ) ) {
                    $modifier_items[] = $product_id;
                    update_post_meta( $modifier_id, '_vc_modifier_menu_items', $modifier_items );
                }
            }
        }

        update_post_meta( $product_id, '_vc_menu_item_modifiers', $current_modifiers );

        return new \WP_REST_Response( [
            'success'     => true,
            'message'     => __( 'Grupo vinculado ao produto com sucesso!', 'vemcomer' ),
            'added_count' => $added_count,
        ] );
    }
}

