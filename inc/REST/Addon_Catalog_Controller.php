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

        // POST: Forçar atualização de itens dos grupos (temporário para debug)
        register_rest_route( 'vemcomer/v1', '/addon-catalog/update-items', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'force_update_items' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
        ] );

        // DELETE: Remover grupo de um produto
        register_rest_route( 'vemcomer/v1', '/addon-catalog/unlink-group-from-product', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'unlink_group_from_product' ],
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

        // GET: Buscar itens de um grupo da loja (para edição de preços)
        register_rest_route( 'vemcomer/v1', '/addon-catalog/store-groups/(?P<id>\d+)/items', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_store_group_items' ],
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

        // PUT: Atualizar preços dos itens de um grupo da loja
        register_rest_route( 'vemcomer/v1', '/addon-catalog/store-groups/(?P<id>\d+)/items/prices', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_store_group_items_prices' ],
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

        // POST: Setup inicial de adicionais (wizard de onboarding)
        register_rest_route( 'vemcomer/v1', '/addon-catalog/setup-onboarding', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'setup_addons_onboarding' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
        ] );

        // GET: Verificar se precisa de onboarding
        register_rest_route( 'vemcomer/v1', '/addon-catalog/needs-onboarding', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'check_needs_onboarding' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
        ] );

        // POST: Salvar grupo como modelo
        register_rest_route( 'vemcomer/v1', '/addon-catalog/store-groups/(?P<id>\d+)/save-as-template', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'save_group_as_template' ],
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

        // GET: Listar grupos templates do lojista
        register_rest_route( 'vemcomer/v1', '/addon-catalog/my-templates', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_my_templates' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
        ] );

        // POST: Dismiss onboarding
        register_rest_route( 'vemcomer/v1', '/addon-catalog/dismiss-onboarding', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'dismiss_onboarding' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
        ] );

        // POST: Aplicar grupo a múltiplos produtos
        register_rest_route( 'vemcomer/v1', '/addon-catalog/apply-group-to-products', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'apply_group_to_products' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
        ] );

        // POST: Copiar adicionais de um produto para outro
        register_rest_route( 'vemcomer/v1', '/addon-catalog/products/(?P<id>\d+)/copy-addons-from/(?P<source_id>\d+)', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'copy_addons_from_product' ],
            'permission_callback' => [ $this, 'can_manage_store' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'absint',
                ],
                'source_id' => [
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
                    'difficulty_level' => get_post_meta( $group->ID, '_vc_difficulty_level', true ) ?: 'basic',
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
                    'key'     => '_vc_is_active',
                    'value'   => '1',
                    'compare' => '=',
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

        // Verificar se o grupo pertence ao restaurante
        $group_restaurant_id = (int) get_post_meta( $group_id, '_vc_restaurant_id', true );
        if ( $group_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não pertence à sua loja.', 'vemcomer' ),
            ], 403 );
        }

        // REGRA: Um produto pode ter vários grupos diferentes, mas não pode ter o mesmo grupo duplicado
        // Verificar se o grupo já está vinculado a este produto
        $current_modifiers = get_post_meta( $product_id, '_vc_menu_item_modifiers', true );
        $current_modifiers = is_array( $current_modifiers ) ? $current_modifiers : [];

        // Buscar todos os modificadores do grupo (grupo + itens)
        $group_modifiers_all = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        $group_modifier_ids = [ $group_id ];
        foreach ( $group_modifiers_all as $gm ) {
            $group_modifier_ids[] = $gm->ID;
        }

        // Verificar se algum modificador do grupo já está vinculado
        $already_linked = false;
        foreach ( $group_modifier_ids as $mod_id ) {
            if ( in_array( $mod_id, $current_modifiers, true ) ) {
                $already_linked = true;
                break;
            }
        }

        if ( $already_linked ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Este grupo já está vinculado a este produto.', 'vemcomer' ),
            ], 400 );
        }

        // Buscar todos os modificadores do grupo (itens que pertencem ao grupo) para vincular
        $group_modifiers = $group_modifiers_all;

        // Criar array com todos os modificadores (grupo principal + itens)
        $all_modifiers = [];
        
        // Adicionar o grupo principal primeiro
        $all_modifiers[] = $group_post;
        
        // Adicionar todos os itens do grupo
        foreach ( $group_modifiers as $modifier ) {
            $all_modifiers[] = $modifier;
        }

        if ( empty( $all_modifiers ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado na sua loja.', 'vemcomer' ),
            ], 404 );
        }

        // Vincular modificadores ao produto (já verificamos que não está duplicado acima)
        $added_count = 0;
        $modifier_ids_to_add = [];
        
        foreach ( $all_modifiers as $modifier ) {
            $modifier_id = $modifier->ID;
            
            // Adicionar à lista (já verificamos duplicação acima)
            if ( ! in_array( $modifier_id, $current_modifiers, true ) ) {
                $modifier_ids_to_add[] = $modifier_id;
                $added_count++;
            }
        }

        // Atualizar meta do produto com todos os modificadores
        if ( ! empty( $modifier_ids_to_add ) ) {
            $current_modifiers = array_merge( $current_modifiers, $modifier_ids_to_add );
            update_post_meta( $product_id, '_vc_menu_item_modifiers', $current_modifiers );

            // Atualizar meta reversa em cada modificador
            foreach ( $modifier_ids_to_add as $modifier_id ) {
                $modifier_items = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
                $modifier_items = is_array( $modifier_items ) ? $modifier_items : [];
                if ( ! in_array( $product_id, $modifier_items, true ) ) {
                    $modifier_items[] = $product_id;
                    update_post_meta( $modifier_id, '_vc_modifier_menu_items', $modifier_items );
                }
            }
        }

        return new \WP_REST_Response( [
            'success'     => true,
            'message'     => __( 'Grupo vinculado ao produto com sucesso!', 'vemcomer' ),
            'added_count' => $added_count,
        ] );
    }

    /**
     * DELETE /wp-json/vemcomer/v1/addon-catalog/unlink-group-from-product
     * Remove um grupo de modificadores de um produto
     */
    public function unlink_group_from_product( \WP_REST_Request $request ): \WP_REST_Response {
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

        // Buscar todos os modificadores do grupo (grupo principal + itens)
        $group_modifiers = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        // Adicionar o grupo principal também
        $all_modifier_ids = [ $group_id ];
        foreach ( $group_modifiers as $mod ) {
            $all_modifier_ids[] = $mod->ID;
        }

        // Remover do produto
        $current_modifiers = get_post_meta( $product_id, '_vc_menu_item_modifiers', true );
        $current_modifiers = is_array( $current_modifiers ) ? $current_modifiers : [];
        $current_modifiers = array_diff( $current_modifiers, $all_modifier_ids );
        $current_modifiers = array_values( array_filter( $current_modifiers ) );
        update_post_meta( $product_id, '_vc_menu_item_modifiers', $current_modifiers );

        // Atualizar meta reversa
        foreach ( $all_modifier_ids as $modifier_id ) {
            $modifier_items = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
            $modifier_items = is_array( $modifier_items ) ? $modifier_items : [];
            $key = array_search( $product_id, $modifier_items, true );
            if ( $key !== false ) {
                unset( $modifier_items[ $key ] );
                $modifier_items = array_values( $modifier_items );
                update_post_meta( $modifier_id, '_vc_modifier_menu_items', $modifier_items );
            }
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => __( 'Grupo removido do produto com sucesso!', 'vemcomer' ),
        ] );
    }

    /**
     * GET /wp-json/vemcomer/v1/addon-catalog/store-groups/{id}/items
     * Retorna os itens de um grupo da loja (para edição de preços)
     */
    public function get_store_group_items( \WP_REST_Request $request ): \WP_REST_Response {
        $group_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
                'items'   => [],
            ], 404 );
        }

        // Verificar se o grupo pertence ao restaurante
        $group = get_post( $group_id );
        if ( ! $group || $group->post_type !== 'vc_product_modifier' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado.', 'vemcomer' ),
                'items'   => [],
            ], 404 );
        }

        $group_restaurant_id = (int) get_post_meta( $group_id, '_vc_restaurant_id', true );
        if ( $group_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não pertence à sua loja.', 'vemcomer' ),
                'items'   => [],
            ], 403 );
        }

        // Buscar itens do grupo (apenas os itens, não o grupo principal)
        $items = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
            'orderby'        => 'title',
            'order'          => 'ASC',
        ] );

        $items_data = [];
        foreach ( $items as $item ) {
            // Buscar preço do item (cada restaurante tem seu próprio preço)
            $price = (float) get_post_meta( $item->ID, '_vc_price', true );
            if ( $price <= 0 ) {
                // Se não tiver preço definido, usar o preço padrão do catálogo (se houver)
                $catalog_item_id = (int) get_post_meta( $item->ID, '_vc_catalog_item_id', true );
                if ( $catalog_item_id ) {
                    $price = (float) get_post_meta( $catalog_item_id, '_vc_default_price', true );
                }
            }

            $items_data[] = [
                'id'    => $item->ID,
                'name'  => $item->post_title,
                'price' => $price,
            ];
        }

        return new \WP_REST_Response( [
            'success' => true,
            'group'   => [
                'id'   => $group->ID,
                'name' => $group->post_title,
            ],
            'items'   => $items_data,
        ] );
    }

    /**
     * PUT /wp-json/vemcomer/v1/addon-catalog/store-groups/{id}/items/prices
     * Atualiza os preços dos itens de um grupo da loja (cada restaurante tem seus próprios preços)
     */
    public function update_store_group_items_prices( \WP_REST_Request $request ): \WP_REST_Response {
        $group_id = (int) $request->get_param( 'id' );
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Verificar se o grupo pertence ao restaurante
        $group = get_post( $group_id );
        if ( ! $group || $group->post_type !== 'vc_product_modifier' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        $group_restaurant_id = (int) get_post_meta( $group_id, '_vc_restaurant_id', true );
        if ( $group_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não pertence à sua loja.', 'vemcomer' ),
            ], 403 );
        }

        $body = $request->get_json_params();
        if ( ! isset( $body['items'] ) || ! is_array( $body['items'] ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Dados inválidos.', 'vemcomer' ),
            ], 400 );
        }

        $updated_count = 0;
        foreach ( $body['items'] as $item_data ) {
            $item_id = isset( $item_data['id'] ) ? (int) $item_data['id'] : 0;
            $price   = isset( $item_data['price'] ) ? max( 0.0, (float) $item_data['price'] ) : 0.00;

            if ( $item_id <= 0 ) {
                continue;
            }

            // Verificar se o item pertence ao grupo e ao restaurante
            $item = get_post( $item_id );
            if ( ! $item || $item->post_type !== 'vc_product_modifier' ) {
                continue;
            }

            $item_group_id = (int) get_post_meta( $item_id, '_vc_group_id', true );
            $item_restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );

            // IMPORTANTE: Cada restaurante tem seus próprios preços (itens copiados para a loja)
            if ( $item_group_id === $group_id && $item_restaurant_id === $restaurant_id ) {
                // Atualizar apenas o preço do item desta loja (não afeta outras lojas)
                update_post_meta( $item_id, '_vc_price', (string) $price );
                $updated_count++;
            }
        }

        return new \WP_REST_Response( [
            'success'       => true,
            'message'       => __( 'Preços atualizados com sucesso!', 'vemcomer' ),
            'updated_count' => $updated_count,
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/products/{id}/copy-addons-from/{source_id}
     * Copia todos os grupos de adicionais de um produto para outro
     */
    public function copy_addons_from_product( \WP_REST_Request $request ): \WP_REST_Response {
        $product_id = (int) $request->get_param( 'id' );
        $source_product_id = (int) $request->get_param( 'source_id' );
        
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Verificar se ambos os produtos existem e pertencem ao restaurante
        $product = get_post( $product_id );
        $source_product = get_post( $source_product_id );

        if ( ! $product || $product->post_type !== 'vc_menu_item' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Produto de destino não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        if ( ! $source_product || $source_product->post_type !== 'vc_menu_item' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Produto de origem não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
        $source_restaurant_id = (int) get_post_meta( $source_product_id, '_vc_restaurant_id', true );

        if ( $product_restaurant_id !== $restaurant_id || $source_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Você não tem permissão para modificar estes produtos.', 'vemcomer' ),
            ], 403 );
        }

        // Buscar grupos do produto de origem
        $source_modifiers = get_post_meta( $source_product_id, '_vc_menu_item_modifiers', true );
        $source_modifiers = is_array( $source_modifiers ) ? $source_modifiers : [];

        if ( empty( $source_modifiers ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'O produto de origem não possui adicionais para copiar.', 'vemcomer' ),
            ], 400 );
        }

        // Identificar grupos únicos (grupos principais, não itens individuais)
        // Um grupo principal tem _vc_group_id = 0 ou igual ao próprio ID
        // Um item tem _vc_group_id = ID do grupo principal
        $source_group_ids = [];
        
        foreach ( $source_modifiers as $modifier_id ) {
            $modifier = get_post( $modifier_id );
            if ( ! $modifier || $modifier->post_type !== 'vc_product_modifier' ) {
                continue;
            }

            $group_id = (int) get_post_meta( $modifier_id, '_vc_group_id', true );
            $modifier_restaurant_id = (int) get_post_meta( $modifier_id, '_vc_restaurant_id', true );

            // Verificar se pertence ao restaurante
            if ( $modifier_restaurant_id !== $restaurant_id ) {
                continue;
            }

            // Se group_id é 0 ou igual ao modifier_id, é o grupo principal
            if ( $group_id === 0 || $group_id === $modifier_id ) {
                // É um grupo principal
                if ( ! in_array( $modifier_id, $source_group_ids, true ) ) {
                    $source_group_ids[] = $modifier_id;
                }
            } elseif ( $group_id > 0 ) {
                // É um item, o grupo principal é o group_id
                if ( ! in_array( $group_id, $source_group_ids, true ) ) {
                    // Verificar se o grupo principal existe
                    $group_post = get_post( $group_id );
                    if ( $group_post && $group_post->post_type === 'vc_product_modifier' ) {
                        $group_restaurant_id = (int) get_post_meta( $group_id, '_vc_restaurant_id', true );
                        if ( $group_restaurant_id === $restaurant_id ) {
                            $source_group_ids[] = $group_id;
                        }
                    }
                }
            }
        }

        if ( empty( $source_group_ids ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Não foi possível identificar grupos de adicionais no produto de origem.', 'vemcomer' ),
            ], 400 );
        }

        // Buscar grupos atuais do produto de destino
        $current_modifiers = get_post_meta( $product_id, '_vc_menu_item_modifiers', true );
        $current_modifiers = is_array( $current_modifiers ) ? $current_modifiers : [];

        // Vincular cada grupo ao produto de destino (reutilizando os mesmos grupos)
        $added_count = 0;
        $groups_added = 0;
        
        foreach ( $source_group_ids as $group_id ) {
            // Buscar todos os modificadores do grupo (grupo principal + itens)
            $group_modifiers = get_posts( [
                'post_type'      => 'vc_product_modifier',
                'posts_per_page' => -1,
                'post_status'    => 'any',
                'meta_query'     => [
                    'relation' => 'AND',
                    [
                        'relation' => 'OR',
                        [
                            'key'   => '_vc_group_id',
                            'value' => $group_id,
                        ],
                        [
                            'key'   => '_vc_group_id',
                            'value' => '0',
                            'compare' => '=',
                        ],
                    ],
                    [
                        'key'   => '_vc_restaurant_id',
                        'value' => $restaurant_id,
                    ],
                ],
            ] );

            // Incluir o grupo principal e todos os itens
            $group_modifier_ids = [ $group_id ];
            foreach ( $group_modifiers as $gm ) {
                $gm_group_id = (int) get_post_meta( $gm->ID, '_vc_group_id', true );
                // Se é o grupo principal ou um item deste grupo
                if ( $gm->ID === $group_id || $gm_group_id === $group_id ) {
                    if ( ! in_array( $gm->ID, $group_modifier_ids, true ) ) {
                        $group_modifier_ids[] = $gm->ID;
                    }
                }
            }

            // Verificar se algum modificador do grupo já está vinculado
            $already_linked = false;
            foreach ( $group_modifier_ids as $mod_id ) {
                if ( in_array( $mod_id, $current_modifiers, true ) ) {
                    $already_linked = true;
                    break;
                }
            }

            if ( $already_linked ) {
                continue; // Pular grupos já vinculados
            }

            // Adicionar todos os modificadores do grupo
            foreach ( $group_modifier_ids as $mod_id ) {
                if ( ! in_array( $mod_id, $current_modifiers, true ) ) {
                    $current_modifiers[] = $mod_id;
                    $added_count++;

                    // Atualizar meta reversa
                    $modifier_items = get_post_meta( $mod_id, '_vc_modifier_menu_items', true );
                    $modifier_items = is_array( $modifier_items ) ? $modifier_items : [];
                    if ( ! in_array( $product_id, $modifier_items, true ) ) {
                        $modifier_items[] = $product_id;
                        update_post_meta( $mod_id, '_vc_modifier_menu_items', $modifier_items );
                    }
                }
            }
            
            $groups_added++;
        }

        // Atualizar meta do produto
        update_post_meta( $product_id, '_vc_menu_item_modifiers', $current_modifiers );

        return new \WP_REST_Response( [
            'success'     => true,
            'message'     => sprintf( __( '%d grupo(s) de adicionais copiado(s) com sucesso!', 'vemcomer' ), $groups_added ),
            'groups_added' => $groups_added,
            'items_added'  => $added_count,
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/apply-group-to-products
     * Aplica um grupo de adicionais a múltiplos produtos
     */
    public function apply_group_to_products( \WP_REST_Request $request ): \WP_REST_Response {
        $body = $request->get_json_params();
        $group_id = isset( $body['group_id'] ) ? (int) $body['group_id'] : 0;
        $product_ids = isset( $body['product_ids'] ) && is_array( $body['product_ids'] ) ? array_map( 'absint', $body['product_ids'] ) : [];

        if ( $group_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não especificado.', 'vemcomer' ),
            ], 400 );
        }

        if ( empty( $product_ids ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Nenhum produto selecionado.', 'vemcomer' ),
            ], 400 );
        }

        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Verificar se o grupo existe e pertence ao restaurante
        $group = get_post( $group_id );
        if ( ! $group || $group->post_type !== 'vc_product_modifier' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        $group_restaurant_id = (int) get_post_meta( $group_id, '_vc_restaurant_id', true );
        if ( $group_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não pertence à sua loja.', 'vemcomer' ),
            ], 403 );
        }

        // Verificar se é um grupo principal
        $group_parent_id = (int) get_post_meta( $group_id, '_vc_group_id', true );
        if ( $group_parent_id !== 0 && $group_parent_id !== $group_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Apenas grupos principais podem ser aplicados a produtos.', 'vemcomer' ),
            ], 400 );
        }

        // Buscar todos os modificadores do grupo (grupo principal + itens)
        $group_modifiers = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key'   => '_vc_group_id',
                        'value' => $group_id,
                    ],
                    [
                        'key'   => '_vc_group_id',
                        'value' => '0',
                        'compare' => '=',
                    ],
                ],
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        // Incluir o grupo principal e todos os itens
        $group_modifier_ids = [ $group_id ];
        foreach ( $group_modifiers as $gm ) {
            $gm_group_id = (int) get_post_meta( $gm->ID, '_vc_group_id', true );
            // Se é o grupo principal ou um item deste grupo
            if ( $gm->ID === $group_id || $gm_group_id === $group_id ) {
                if ( ! in_array( $gm->ID, $group_modifier_ids, true ) ) {
                    $group_modifier_ids[] = $gm->ID;
                }
            }
        }

        $applied_count = 0;
        $skipped_count = 0;

        // Aplicar grupo a cada produto selecionado
        foreach ( $product_ids as $product_id ) {
            // Verificar se o produto existe e pertence ao restaurante
            $product = get_post( $product_id );
            if ( ! $product || $product->post_type !== 'vc_menu_item' ) {
                $skipped_count++;
                continue;
            }

            $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
            if ( $product_restaurant_id !== $restaurant_id ) {
                $skipped_count++;
                continue;
            }

            // Buscar modificadores atuais do produto
            $current_modifiers = get_post_meta( $product_id, '_vc_menu_item_modifiers', true );
            $current_modifiers = is_array( $current_modifiers ) ? $current_modifiers : [];

            // Verificar se o grupo já está vinculado
            $already_linked = false;
            foreach ( $group_modifier_ids as $mod_id ) {
                if ( in_array( $mod_id, $current_modifiers, true ) ) {
                    $already_linked = true;
                    break;
                }
            }

            if ( $already_linked ) {
                $skipped_count++;
                continue;
            }

            // Adicionar todos os modificadores do grupo ao produto
            foreach ( $group_modifier_ids as $mod_id ) {
                if ( ! in_array( $mod_id, $current_modifiers, true ) ) {
                    $current_modifiers[] = $mod_id;

                    // Atualizar meta reversa
                    $modifier_items = get_post_meta( $mod_id, '_vc_modifier_menu_items', true );
                    $modifier_items = is_array( $modifier_items ) ? $modifier_items : [];
                    if ( ! in_array( $product_id, $modifier_items, true ) ) {
                        $modifier_items[] = $product_id;
                        update_post_meta( $mod_id, '_vc_modifier_menu_items', $modifier_items );
                    }
                }
            }

            // Atualizar meta do produto
            update_post_meta( $product_id, '_vc_menu_item_modifiers', $current_modifiers );
            $applied_count++;
        }

        $message = sprintf(
            __( 'Grupo aplicado a %d produto(s) com sucesso.', 'vemcomer' ),
            $applied_count
        );

        if ( $skipped_count > 0 ) {
            $message .= ' ' . sprintf(
                __( '%d produto(s) foram pulados (já possuem o grupo ou não pertencem à sua loja).', 'vemcomer' ),
                $skipped_count
            );
        }

        return new \WP_REST_Response( [
            'success'       => true,
            'message'       => $message,
            'applied_count' => $applied_count,
            'skipped_count' => $skipped_count,
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/update-items
     * Força a atualização dos itens dos grupos (temporário para debug)
     */
    public function force_update_items( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Classe Addon_Catalog_Seeder não encontrada.', 'vemcomer' ),
            ], 500 );
        }

        \VC\Utils\Addon_Catalog_Seeder::update_group_items();

        return new \WP_REST_Response( [
            'success' => true,
            'message' => __( 'Itens atualizados com sucesso.', 'vemcomer' ),
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/store-groups/{id}/save-as-template
     * Salva um grupo de adicionais da loja como template para reutilização
     */
    public function save_group_as_template( \WP_REST_Request $request ): \WP_REST_Response {
        $group_id = (int) $request->get_param( 'id' );
        
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        // Verificar se o grupo existe e pertence ao restaurante
        $group = get_post( $group_id );
        if ( ! $group || $group->post_type !== 'vc_product_modifier' ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        $group_restaurant_id = (int) get_post_meta( $group_id, '_vc_restaurant_id', true );
        if ( $group_restaurant_id !== $restaurant_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Grupo não pertence à sua loja.', 'vemcomer' ),
            ], 403 );
        }

        // Verificar se é um grupo principal (_vc_group_id = 0)
        $group_parent_id = (int) get_post_meta( $group_id, '_vc_group_id', true );
        if ( $group_parent_id !== 0 && $group_parent_id !== $group_id ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Apenas grupos principais podem ser salvos como template.', 'vemcomer' ),
            ], 400 );
        }

        // Criar um novo grupo template no catálogo global (vc_addon_group)
        $template_group_id = wp_insert_post( [
            'post_type'    => 'vc_addon_group',
            'post_title'   => $group->post_title . ' (Template)',
            'post_content' => $group->post_content,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ] );

        if ( is_wp_error( $template_group_id ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Erro ao criar template.', 'vemcomer' ),
            ], 500 );
        }

        // Copiar configurações do grupo
        update_post_meta( $template_group_id, '_vc_selection_type', get_post_meta( $group_id, '_vc_selection_type', true ) ?: 'multiple' );
        update_post_meta( $template_group_id, '_vc_min_select', get_post_meta( $group_id, '_vc_min_select', true ) );
        update_post_meta( $template_group_id, '_vc_max_select', get_post_meta( $group_id, '_vc_max_select', true ) );
        update_post_meta( $template_group_id, '_vc_is_required', get_post_meta( $group_id, '_vc_is_required', true ) );
        update_post_meta( $template_group_id, '_vc_difficulty_level', 'basic' );
        update_post_meta( $template_group_id, 'vc_addon_template_group', '1' ); // Marcar como template
        update_post_meta( $template_group_id, 'vc_addon_template_author', $user_id ); // Autor do template

        // Buscar e copiar itens do grupo
        $store_items = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_vc_group_id',
                    'value' => $group_id,
                ],
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        $copied_items = [];
        foreach ( $store_items as $store_item ) {
            // Criar item no catálogo (vc_addon_item)
            $template_item_id = wp_insert_post( [
                'post_type'    => 'vc_addon_item',
                'post_title'   => $store_item->post_title,
                'post_content' => $store_item->post_content,
                'post_status'  => 'publish',
                'post_author'  => $user_id,
            ] );

            if ( ! is_wp_error( $template_item_id ) ) {
                // Copiar configurações do item
                update_post_meta( $template_item_id, '_vc_group_id', $template_group_id );
                update_post_meta( $template_item_id, '_vc_default_price', get_post_meta( $store_item->ID, '_vc_price', true ) ?: '0.00' );
                update_post_meta( $template_item_id, '_vc_allow_quantity', get_post_meta( $store_item->ID, '_vc_allow_quantity', true ) );
                update_post_meta( $template_item_id, '_vc_max_quantity', get_post_meta( $store_item->ID, '_vc_max_quantity', true ) ?: '1' );
                update_post_meta( $template_item_id, '_vc_is_active', '1' );

                $copied_items[] = $template_item_id;
            }
        }

        return new \WP_REST_Response( [
            'success'      => true,
            'message'      => __( 'Grupo salvo como template com sucesso!', 'vemcomer' ),
            'template_id'  => $template_group_id,
            'items_count'  => count( $copied_items ),
        ] );
    }

    /**
     * GET /wp-json/vemcomer/v1/addon-catalog/my-templates
     * Lista grupos templates salvos pelo lojista
     */
    public function get_my_templates( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        
        // Buscar grupos templates criados pelo usuário
        $templates = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'author'         => $user_id,
            'meta_query'     => [
                [
                    'key'   => 'vc_addon_template_group',
                    'value' => '1',
                ],
            ],
        ] );

        $templates_data = [];
        foreach ( $templates as $template ) {
            $templates_data[] = [
                'id'          => $template->ID,
                'name'        => $template->post_title,
                'description' => $template->post_content,
            ];
        }

        return new \WP_REST_Response( [
            'success'   => true,
            'templates' => $templates_data,
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/setup-onboarding
     * Configura grupos iniciais de adicionais para o restaurante
     */
    public function setup_addons_onboarding( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Restaurante não encontrado.', 'vemcomer' ),
            ], 404 );
        }

        $body = $request->get_json_params();
        $group_ids = isset( $body['group_ids'] ) && is_array( $body['group_ids'] ) ? array_map( 'absint', $body['group_ids'] ) : [];

        if ( empty( $group_ids ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => __( 'Nenhum grupo selecionado.', 'vemcomer' ),
            ], 400 );
        }

        $copied_groups = [];
        foreach ( $group_ids as $catalog_group_id ) {
            // Criar uma requisição interna para copiar o grupo
            $copy_request = new \WP_REST_Request( 'POST', '/vemcomer/v1/addon-catalog/groups/' . $catalog_group_id . '/copy-to-store' );
            $copy_result = $this->copy_group_to_store( $copy_request );
            $copy_data = $copy_result->get_data();

            if ( $copy_data['success'] && isset( $copy_data['group_id'] ) ) {
                $copied_groups[] = $copy_data['group_id'];
            }
        }

        // Marcar onboarding como completo
        update_user_meta( $user_id, 'vc_addons_onboarding_completed', '1' );

        return new \WP_REST_Response( [
            'success'      => true,
            'message'      => sprintf( __( '%d grupo(s) configurado(s) com sucesso!', 'vemcomer' ), count( $copied_groups ) ),
            'groups_count' => count( $copied_groups ),
        ] );
    }

    /**
     * GET /wp-json/vemcomer/v1/addon-catalog/needs-onboarding
     * Verifica se o restaurante precisa de onboarding de adicionais
     */
    public function check_needs_onboarding( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $restaurant_id <= 0 ) {
            return new \WP_REST_Response( [
                'success' => false,
                'needs_onboarding' => false,
            ], 404 );
        }

        // Verificar se já tem grupos configurados
        $store_groups = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
                [
                    'key'   => '_vc_group_id',
                    'value' => '0',
                    'compare' => '=',
                ],
            ],
        ] );

        $has_groups = ! empty( $store_groups );
        $onboarding_completed = get_user_meta( $user_id, 'vc_addons_onboarding_completed', true ) === '1';
        $needs_onboarding = ! $has_groups && ! $onboarding_completed;

        return new \WP_REST_Response( [
            'success'           => true,
            'needs_onboarding'  => $needs_onboarding,
            'has_groups'        => $has_groups,
            'completed'         => $onboarding_completed,
        ] );
    }

    /**
     * POST /wp-json/vemcomer/v1/addon-catalog/dismiss-onboarding
     * Descarta o onboarding de adicionais
     */
    public function dismiss_onboarding( \WP_REST_Request $request ): \WP_REST_Response {
        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'vc_addons_onboarding_completed', '1' );

        return new \WP_REST_Response( [
            'success' => true,
            'message' => __( 'Onboarding descartado.', 'vemcomer' ),
        ] );
    }
}

