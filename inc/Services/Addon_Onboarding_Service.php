<?php
/**
 * Addon_Onboarding_Service — Serviço para aplicar grupos de adicionais durante o onboarding
 * 
 * @package VemComerCore
 */

namespace VC\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Addon_Onboarding_Service {
    
    /**
     * Aplica grupos de adicionais recomendados do catálogo à loja e associa aos produtos
     * 
     * @param int   $restaurant_id      ID do restaurante
     * @param array $catalog_group_ids  Array de IDs dos grupos do catálogo (vc_addon_group)
     * @return array {
     *     @type bool   $success  Se a operação foi bem-sucedida
     *     @type int    $groups_created  Número de grupos criados na loja
     *     @type array  $errors   Array de mensagens de erro
     * }
     */
    public static function apply_recommended_groups_to_store( int $restaurant_id, array $catalog_group_ids ): array {
        if ( empty( $catalog_group_ids ) ) {
            return [
                'success'       => true,
                'groups_created' => 0,
                'errors'        => [],
            ];
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return [
                'success'       => false,
                'groups_created' => 0,
                'errors'        => [ __( 'Usuário não encontrado.', 'vemcomer' ) ],
            ];
        }

        $created_groups = [];
        $errors = [];

        foreach ( $catalog_group_ids as $catalog_group_id ) {
            $catalog_group_id = (int) $catalog_group_id;
            
            try {
                // Copiar grupo do catálogo para a loja
                $store_group_id = self::copy_group_to_store( $catalog_group_id, $restaurant_id, $user_id );
                
                if ( is_wp_error( $store_group_id ) ) {
                    $errors[] = sprintf( __( 'Erro ao copiar grupo ID %d: %s', 'vemcomer' ), $catalog_group_id, $store_group_id->get_error_message() );
                    continue;
                }

                $created_groups[] = $store_group_id;
            } catch ( \Exception $e ) {
                $errors[] = sprintf( __( 'Erro ao processar grupo ID %d: %s', 'vemcomer' ), $catalog_group_id, $e->getMessage() );
                error_log( 'Addon_Onboarding_Service error: ' . $e->getMessage() );
            }
        }

        // Associar grupos aos produtos criados durante o onboarding
        if ( ! empty( $created_groups ) ) {
            try {
                $products = self::get_onboarding_products( $restaurant_id );
                
                if ( ! empty( $products ) ) {
                    foreach ( $created_groups as $store_group_id ) {
                        foreach ( $products as $product_id ) {
                            try {
                                self::link_group_to_product( $store_group_id, $product_id, $restaurant_id );
                            } catch ( \Exception $e ) {
                                error_log( sprintf( 'Erro ao vincular grupo %d ao produto %d: %s', $store_group_id, $product_id, $e->getMessage() ) );
                                // Continuar com os próximos produtos mesmo se houver erro
                            }
                        }
                    }
                }
            } catch ( \Exception $e ) {
                error_log( 'Erro ao buscar produtos para vincular grupos: ' . $e->getMessage() );
                // Não falhar o processo todo se houver erro ao vincular produtos
            }
        }

        return [
            'success'        => empty( $errors ) || ! empty( $created_groups ),
            'groups_created' => count( $created_groups ),
            'errors'         => $errors,
        ];
    }

    /**
     * Copia um grupo do catálogo para a loja
     * 
     * @param int $catalog_group_id ID do grupo no catálogo (vc_addon_group)
     * @param int $restaurant_id     ID do restaurante
     * @param int $user_id           ID do usuário
     * @return int|\WP_Error ID do grupo criado na loja ou WP_Error em caso de erro
     */
    private static function copy_group_to_store( int $catalog_group_id, int $restaurant_id, int $user_id ) {
        // Buscar o grupo do catálogo
        $catalog_group = get_post( $catalog_group_id );
        if ( ! $catalog_group || $catalog_group->post_type !== 'vc_addon_group' ) {
            return new \WP_Error( 'group_not_found', __( 'Grupo do catálogo não encontrado.', 'vemcomer' ) );
        }

        // Criar grupo na loja (usando vc_product_modifier)
        $store_group_id = wp_insert_post( [
            'post_type'    => 'vc_product_modifier',
            'post_title'   => $catalog_group->post_title,
            'post_content' => $catalog_group->post_content,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ] );

        if ( is_wp_error( $store_group_id ) ) {
            return $store_group_id;
        }

        // Copiar configurações do grupo
        update_post_meta( $store_group_id, '_vc_restaurant_id', $restaurant_id );
        update_post_meta( $store_group_id, '_vc_group_id', '0' ); // Marcar como grupo principal
        update_post_meta( $store_group_id, '_vc_selection_type', get_post_meta( $catalog_group_id, '_vc_selection_type', true ) ?: 'multiple' );
        update_post_meta( $store_group_id, '_vc_min_select', get_post_meta( $catalog_group_id, '_vc_min_select', true ) );
        update_post_meta( $store_group_id, '_vc_max_select', get_post_meta( $catalog_group_id, '_vc_max_select', true ) );
        update_post_meta( $store_group_id, '_vc_is_required', get_post_meta( $catalog_group_id, '_vc_is_required', true ) );
        update_post_meta( $store_group_id, '_vc_catalog_group_id', $catalog_group_id ); // Referência ao grupo original

        // Buscar e copiar itens do grupo
        $catalog_items = get_posts( [
            'post_type'      => 'vc_addon_item',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_vc_group_id',
                    'value' => $catalog_group_id,
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => '_vc_is_active',
                        'value'   => '1',
                        'compare' => '=',
                    ],
                    [
                        'key'     => '_vc_is_active',
                        'compare' => 'NOT EXISTS',
                    ],
                ],
            ],
        ] );

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
            }
        }

        return $store_group_id;
    }

    /**
     * Retorna os produtos criados durante o onboarding
     * 
     * @param int $restaurant_id ID do restaurante
     * @return array Array de IDs dos produtos (vc_menu_item)
     */
    private static function get_onboarding_products( int $restaurant_id ): array {
        if ( $restaurant_id <= 0 ) {
            return [];
        }

        // Buscar todos os produtos do restaurante
        // Por enquanto, vamos usar todos os produtos da loja
        // No futuro, podemos adicionar uma meta para marcar produtos criados durante onboarding
        $products = get_posts( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
            'fields'         => 'ids',
        ] );

        // Garantir que retorna um array
        if ( ! is_array( $products ) ) {
            return [];
        }

        // Filtrar apenas IDs válidos
        return array_filter( array_map( 'intval', $products ), function( $id ) {
            return $id > 0;
        } );
    }

    /**
     * Vincula um grupo de adicionais a um produto
     * 
     * @param int $store_group_id ID do grupo na loja (vc_product_modifier)
     * @param int $product_id     ID do produto (vc_menu_item)
     * @param int $restaurant_id  ID do restaurante (para validação)
     * @return bool True se vinculado com sucesso
     */
    private static function link_group_to_product( int $store_group_id, int $product_id, int $restaurant_id ): bool {
        // Verificar se o produto pertence ao restaurante
        $product = get_post( $product_id );
        if ( ! $product || $product->post_type !== 'vc_menu_item' ) {
            return false;
        }

        $product_restaurant_id = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
        if ( $product_restaurant_id !== $restaurant_id ) {
            return false;
        }

        // Verificar se o grupo pertence ao restaurante
        $group_restaurant_id = (int) get_post_meta( $store_group_id, '_vc_restaurant_id', true );
        if ( $group_restaurant_id !== $restaurant_id ) {
            return false;
        }

        // Buscar todos os modificadores do grupo (grupo principal + itens)
        // Primeiro buscar itens que pertencem a este grupo
        $group_modifiers = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_vc_group_id',
                    'value' => $store_group_id,
                ],
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
            'fields'         => 'ids',
        ] );

        // Incluir o grupo principal e todos os itens
        $modifier_ids = [ $store_group_id ];
        if ( ! empty( $group_modifiers ) && is_array( $group_modifiers ) ) {
            foreach ( $group_modifiers as $modifier_id ) {
                $modifier_id = (int) $modifier_id;
                if ( $modifier_id > 0 && ! in_array( $modifier_id, $modifier_ids, true ) ) {
                    $modifier_ids[] = $modifier_id;
                }
            }
        }

        // Verificar se o grupo já está vinculado ao produto
        $current_modifiers = get_post_meta( $product_id, '_vc_menu_item_modifiers', true );
        $current_modifiers = is_array( $current_modifiers ) ? $current_modifiers : [];

        // Verificar se algum modificador do grupo já está vinculado
        $already_linked = false;
        foreach ( $modifier_ids as $modifier_id ) {
            if ( in_array( $modifier_id, $current_modifiers, true ) ) {
                $already_linked = true;
                break;
            }
        }

        if ( $already_linked ) {
            // Grupo já está vinculado, não fazer nada
            return true;
        }

        // Adicionar modificadores ao produto
        $updated_modifiers = array_unique( array_merge( $current_modifiers, $modifier_ids ) );
        update_post_meta( $product_id, '_vc_menu_item_modifiers', $updated_modifiers );

        // Atualizar meta reversa (modificadores -> produtos)
        foreach ( $modifier_ids as $modifier_id ) {
            $modifier_products = get_post_meta( $modifier_id, '_vc_modifier_menu_items', true );
            $modifier_products = is_array( $modifier_products ) ? $modifier_products : [];
            
            if ( ! in_array( $product_id, $modifier_products, true ) ) {
                $modifier_products[] = $product_id;
                update_post_meta( $modifier_id, '_vc_modifier_menu_items', $modifier_products );
            }
        }

        return true;
    }
}

