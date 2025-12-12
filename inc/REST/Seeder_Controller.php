<?php
/**
 * Seeder_Controller — Endpoint REST para executar seeders
 * 
 * @package VemComerCore
 */

namespace VC\REST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Seeder_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( 'vemcomer/v1', '/seed/addon-catalog', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'seed_addon_catalog' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( 'vemcomer/v1', '/seed/connect-addons', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'connect_addons_to_categories' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( 'vemcomer/v1', '/seed/verify-connections', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'verify_connections' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( 'vemcomer/v1', '/seed/migrate-addon-groups', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'migrate_addon_groups_to_meta' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );

        register_rest_route( 'vemcomer/v1', '/seed/reseed-menu-categories', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'reseed_menu_categories' ],
            'permission_callback' => [ $this, 'check_permissions' ],
        ] );
    }

    public function check_permissions(): bool {
        // Apenas administradores
        return current_user_can( 'manage_options' );
    }

    public function seed_addon_catalog( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Classe Addon_Catalog_Seeder não encontrada.',
            ], 500 );
        }

        // Usar Reflection para acessar o método privado
        $reflection = new \ReflectionClass( '\\VC\\Utils\\Addon_Catalog_Seeder' );
        $method = $reflection->getMethod( 'get_groups_data' );
        $method->setAccessible( true );
        $groups_data = $method->invoke( null );

        // Obter grupos existentes
        $existing_groups = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );

        $existing_names = [];
        foreach ( $existing_groups as $group ) {
            $existing_names[] = $group->post_title;
        }

        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ( $groups_data as $group_data ) {
            // Verificar se já existe
            if ( in_array( $group_data['name'], $existing_names, true ) ) {
                $skipped++;
                continue;
            }
            
            // Criar grupo
            $group_id = wp_insert_post( [
                'post_type'    => 'vc_addon_group',
                'post_title'   => $group_data['name'],
                'post_content' => $group_data['description'] ?? '',
                'post_status'  => 'publish',
            ] );
            
            if ( is_wp_error( $group_id ) ) {
                $errors[] = "Erro ao criar grupo: {$group_data['name']} - " . $group_id->get_error_message();
                continue;
            }
            
            // Configurações
            update_post_meta( $group_id, '_vc_selection_type', $group_data['selection_type'] ?? 'multiple' );
            update_post_meta( $group_id, '_vc_min_select', $group_data['min_select'] ?? 0 );
            update_post_meta( $group_id, '_vc_max_select', $group_data['max_select'] ?? 0 );
            update_post_meta( $group_id, '_vc_is_required', $group_data['is_required'] ? '1' : '0' );
            update_post_meta( $group_id, '_vc_is_active', '1' );
            
            // Vincular categorias (usando a mesma abordagem do Menu_Category_Catalog_Seeder)
            if ( ! empty( $group_data['categories'] ) ) {
                $category_ids = [];
                foreach ( $group_data['categories'] as $category_name ) {
                    $term = get_term_by( 'name', $category_name, 'vc_cuisine' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $category_ids[] = $term->term_id;
                    }
                }
                if ( ! empty( $category_ids ) ) {
                    // Salvar IDs das categorias de restaurante como post meta (mesma abordagem do Menu_Category_Catalog_Seeder)
                    update_post_meta( $group_id, '_vc_recommended_for_cuisines', wp_json_encode( $category_ids ) );
                }
            }
            
            // Criar itens
            if ( ! empty( $group_data['items'] ) ) {
                foreach ( $group_data['items'] as $item_data ) {
                    $item_id = wp_insert_post( [
                        'post_type'    => 'vc_addon_item',
                        'post_title'   => $item_data['name'],
                        'post_content' => $item_data['description'] ?? '',
                        'post_status'  => 'publish',
                    ] );
                    
                    if ( ! is_wp_error( $item_id ) ) {
                        update_post_meta( $item_id, '_vc_group_id', $group_id );
                        update_post_meta( $item_id, '_vc_default_price', $item_data['price'] ?? '0.00' );
                        update_post_meta( $item_id, '_vc_allow_quantity', $item_data['allow_quantity'] ? '1' : '0' );
                        update_post_meta( $item_id, '_vc_max_quantity', $item_data['max_quantity'] ?? 1 );
                        update_post_meta( $item_id, '_vc_is_active', '1' );
                    }
                }
            }
            
            $created++;
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => 'Seeder executado com sucesso!',
            'created' => $created,
            'skipped' => $skipped,
            'errors'  => $errors,
        ], 200 );
    }

    /**
     * Reconecta todos os grupos de adicionais às categorias corretas
     */
    public function connect_addons_to_categories( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Classe Addon_Catalog_Seeder não encontrada.',
            ], 500 );
        }

        // Obter todos os dados dos grupos
        $reflection = new \ReflectionClass( '\\VC\\Utils\\Addon_Catalog_Seeder' );
        $method = $reflection->getMethod( 'get_groups_data' );
        $method->setAccessible( true );
        $groups_data = $method->invoke( null );

        // Obter todos os grupos existentes
        $existing_groups = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );

        $connected = 0;
        $reconnected = 0;
        $not_found = [];
        $errors = [];

        foreach ( $existing_groups as $group ) {
            // Encontrar dados do grupo
            $group_data = null;
            foreach ( $groups_data as $data ) {
                if ( $data['name'] === $group->post_title ) {
                    $group_data = $data;
                    break;
                }
            }

            if ( ! $group_data ) {
                $not_found[] = $group->post_title;
                continue;
            }

            // Obter categorias atuais do grupo (usando a mesma abordagem do Menu_Category_Catalog_Seeder)
            $current_cuisines_json = get_post_meta( $group->ID, '_vc_recommended_for_cuisines', true );
            $current_ids = [];
            if ( ! empty( $current_cuisines_json ) ) {
                $decoded = json_decode( $current_cuisines_json, true );
                if ( is_array( $decoded ) ) {
                    $current_ids = $decoded;
                }
            }

            // Buscar IDs das categorias corretas
            $correct_ids = [];
            if ( ! empty( $group_data['categories'] ) ) {
                foreach ( $group_data['categories'] as $category_name ) {
                    $term = get_term_by( 'name', $category_name, 'vc_cuisine' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $correct_ids[] = $term->term_id;
                    }
                }
            }

            // Comparar e reconectar se necessário
            sort( $current_ids );
            sort( $correct_ids );
            
            if ( $current_ids !== $correct_ids ) {
                if ( ! empty( $correct_ids ) ) {
                    // Usar a mesma abordagem do Menu_Category_Catalog_Seeder
                    $result = update_post_meta( $group->ID, '_vc_recommended_for_cuisines', wp_json_encode( $correct_ids ) );
                    if ( $result !== false ) {
                        $reconnected++;
                    } else {
                        $errors[] = "Erro ao reconectar {$group->post_title}";
                    }
                } else {
                    $errors[] = "Grupo {$group->post_title} não tem categorias definidas";
                }
            } else {
                $connected++;
            }
        }

        // Conectar grupos genéricos a categorias sem grupos específicos
        $generic_groups = [
            'Molhos Extras',
            'Bebida do Combo',
            'Tamanho da Bebida',
            'Tamanhos',
        ];

        // Obter todas as categorias de restaurantes
        $all_cuisines = get_terms( [
            'taxonomy'   => 'vc_cuisine',
            'hide_empty' => false,
        ] );

        $categories_with_groups = [];
        foreach ( $existing_groups as $group ) {
            // Usar a mesma abordagem do Menu_Category_Catalog_Seeder
            $cuisines_json = get_post_meta( $group->ID, '_vc_recommended_for_cuisines', true );
            if ( ! empty( $cuisines_json ) ) {
                $decoded = json_decode( $cuisines_json, true );
                if ( is_array( $decoded ) ) {
                    $categories_with_groups = array_merge( $categories_with_groups, $decoded );
                }
            }
        }
        $categories_with_groups = array_unique( $categories_with_groups );

        $categories_without_groups = [];
        foreach ( $all_cuisines as $cuisine ) {
            if ( ! in_array( $cuisine->term_id, $categories_with_groups, true ) ) {
                $categories_without_groups[] = $cuisine->term_id;
            }
        }

        $generic_connected = 0;
        if ( ! empty( $categories_without_groups ) ) {
            foreach ( $generic_groups as $generic_name ) {
                // Buscar grupo pelo título
                $generic_group = [];
                $all_groups = get_posts( [
                    'post_type'      => 'vc_addon_group',
                    'posts_per_page' => -1,
                    'post_status'    => 'any',
                ] );
                foreach ( $all_groups as $group ) {
                    if ( $group->post_title === $generic_name ) {
                        $generic_group = [ $group ];
                        break;
                    }
                }

                if ( ! empty( $generic_group ) ) {
                    // Usar a mesma abordagem do Menu_Category_Catalog_Seeder
                    $current_cuisines_json = get_post_meta( $generic_group[0]->ID, '_vc_recommended_for_cuisines', true );
                    $current_ids = [];
                    if ( ! empty( $current_cuisines_json ) ) {
                        $decoded = json_decode( $current_cuisines_json, true );
                        if ( is_array( $decoded ) ) {
                            $current_ids = $decoded;
                        }
                    }
                    $new_ids = array_unique( array_merge( $current_ids, $categories_without_groups ) );
                    
                    if ( $current_ids !== $new_ids ) {
                        $result = update_post_meta( $generic_group[0]->ID, '_vc_recommended_for_cuisines', wp_json_encode( $new_ids ) );
                        if ( $result !== false ) {
                            $generic_connected++;
                        }
                    }
                }
            }
        }

        return new \WP_REST_Response( [
            'success'                => true,
            'message'                => 'Conexões verificadas e atualizadas!',
            'already_connected'      => $connected,
            'reconnected'            => $reconnected,
            'generic_connected'      => $generic_connected,
            'categories_without_groups' => count( $categories_without_groups ),
            'groups_not_found'        => $not_found,
            'errors'                 => $errors,
        ], 200 );
    }

    /**
     * Verifica o status das conexões entre grupos e categorias
     */
    public function verify_connections( \WP_REST_Request $request ): \WP_REST_Response {
        // Obter todos os grupos
        $groups = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );

        // Obter todas as categorias
        $cuisines = get_terms( [
            'taxonomy'   => 'vc_cuisine',
            'hide_empty' => false,
        ] );

        $groups_with_categories = [];
        $groups_without_categories = [];
        $categories_with_groups = [];
        $categories_without_groups = [];

        foreach ( $groups as $group ) {
            // Usar a mesma abordagem do Menu_Category_Catalog_Seeder
            $cuisines_json = get_post_meta( $group->ID, '_vc_recommended_for_cuisines', true );
            if ( empty( $cuisines_json ) ) {
                $groups_without_categories[] = [
                    'id'   => $group->ID,
                    'name' => $group->post_title,
                ];
            } else {
                $cuisine_ids = json_decode( $cuisines_json, true );
                if ( ! is_array( $cuisine_ids ) ) {
                    $groups_without_categories[] = [
                        'id'   => $group->ID,
                        'name' => $group->post_title,
                    ];
                    continue;
                }

                $group_cuisines = [];
                foreach ( $cuisine_ids as $cuisine_id ) {
                    $term = get_term( $cuisine_id, 'vc_cuisine' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $group_cuisines[] = $term->name;
                        if ( ! isset( $categories_with_groups[ $cuisine_id ] ) ) {
                            $categories_with_groups[ $cuisine_id ] = [];
                        }
                        $categories_with_groups[ $cuisine_id ][] = $group->post_title;
                    }
                }
                $groups_with_categories[] = [
                    'id'         => $group->ID,
                    'name'       => $group->post_title,
                    'categories' => $group_cuisines,
                ];
            }
        }

        foreach ( $cuisines as $cuisine ) {
            if ( ! isset( $categories_with_groups[ $cuisine->term_id ] ) ) {
                $categories_without_groups[] = [
                    'id'   => $cuisine->term_id,
                    'name' => $cuisine->name,
                ];
            }
        }

        return new \WP_REST_Response( [
            'success'                    => true,
            'total_groups'               => count( $groups ),
            'groups_with_categories'     => count( $groups_with_categories ),
            'groups_without_categories'  => count( $groups_without_categories ),
            'total_cuisines'             => count( $cuisines ),
            'cuisines_with_groups'        => count( $categories_with_groups ),
            'cuisines_without_groups'    => count( $categories_without_groups ),
            'groups_without_categories_list' => $groups_without_categories,
            'cuisines_without_groups_list'   => $categories_without_groups,
        ], 200 );
    }

    /**
     * Migra grupos de adicionais de taxonomia para meta
     * Converte de wp_set_object_terms para update_post_meta com _vc_recommended_for_cuisines
     */
    public function migrate_addon_groups_to_meta( \WP_REST_Request $request ): \WP_REST_Response {
        // Buscar todos os grupos de adicionais
        $groups = get_posts( [
            'post_type'      => 'vc_addon_group',
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ] );

        if ( empty( $groups ) ) {
            return new \WP_REST_Response( [
                'success' => true,
                'message' => 'Nenhum grupo encontrado. Nada para migrar.',
                'migrated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ], 200 );
        }

        $migrated = 0;
        $skipped = 0;
        $errors = 0;
        $details = [];

        foreach ( $groups as $group ) {
            // Verificar se já tem meta (já migrado)
            $existing_meta = get_post_meta( $group->ID, '_vc_recommended_for_cuisines', true );
            if ( ! empty( $existing_meta ) ) {
                $decoded = json_decode( $existing_meta, true );
                if ( is_array( $decoded ) && ! empty( $decoded ) ) {
                    $skipped++;
                    $details[] = [
                        'group' => $group->post_title,
                        'status' => 'skipped',
                        'reason' => 'Já tem meta',
                    ];
                    continue;
                }
            }

            // Buscar categorias via taxonomia (abordagem antiga)
            $terms = wp_get_object_terms( $group->ID, 'vc_cuisine', [ 'fields' => 'ids' ] );
            
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                $skipped++;
                $details[] = [
                    'group' => $group->post_title,
                    'status' => 'skipped',
                    'reason' => 'Não tem categorias via taxonomia',
                ];
                continue;
            }

            // Migrar para meta
            $result = update_post_meta( $group->ID, '_vc_recommended_for_cuisines', wp_json_encode( $terms ) );
            
            if ( $result !== false ) {
                $migrated++;
                $details[] = [
                    'group' => $group->post_title,
                    'status' => 'migrated',
                    'categories_count' => count( $terms ),
                ];
            } else {
                $errors++;
                $details[] = [
                    'group' => $group->post_title,
                    'status' => 'error',
                    'reason' => 'Erro ao salvar meta',
                ];
            }
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => 'Migração concluída!',
            'migrated' => $migrated,
            'skipped' => $skipped,
            'errors' => $errors,
            'total' => count( $groups ),
            'details' => $details,
        ], 200 );
    }

    /**
     * POST /wp-json/vemcomer/v1/seed/reseed-menu-categories
     * Limpa e re-seed do catálogo de categorias de cardápio usando os novos blueprints
     */
    public function reseed_menu_categories( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! class_exists( '\\VC\\Utils\\Menu_Category_Catalog_Seeder' ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Classe Menu_Category_Catalog_Seeder não encontrada.',
            ], 500 );
        }

        if ( ! taxonomy_exists( 'vc_menu_category' ) || ! taxonomy_exists( 'vc_cuisine' ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => 'Taxonomias necessárias não existem.',
            ], 500 );
        }

        // Buscar categorias de catálogo existentes
        $existing_catalog = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => '_vc_is_catalog_category',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ] );

        $deleted_count = 0;
        $kept_count = 0;
        if ( ! is_wp_error( $existing_catalog ) && ! empty( $existing_catalog ) ) {
            foreach ( $existing_catalog as $term ) {
                delete_term_meta( $term->term_id, '_vc_is_catalog_category' );
                delete_term_meta( $term->term_id, '_vc_recommended_for_cuisines' );
                delete_term_meta( $term->term_id, '_vc_recommended_for_archetypes' );
                delete_term_meta( $term->term_id, '_vc_category_order' );
                
                if ( $term->count === 0 ) {
                    wp_delete_term( $term->term_id, 'vc_menu_category' );
                    $deleted_count++;
                } else {
                    $kept_count++;
                }
            }
        }

        // Limpar cache
        clean_term_cache( null, 'vc_menu_category' );
        delete_option( 'vemcomer_menu_categories_seeded' );
        wp_cache_flush();

        // Executar seed novamente
        \VC\Utils\Menu_Category_Catalog_Seeder::seed( true );

        // Verificar resultado
        $new_catalog = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => '_vc_is_catalog_category',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ] );

        $categories_info = [];
        if ( ! is_wp_error( $new_catalog ) && ! empty( $new_catalog ) ) {
            foreach ( array_slice( $new_catalog, 0, 20 ) as $cat ) {
                $archetypes = get_term_meta( $cat->term_id, '_vc_recommended_for_archetypes', true );
                $archetype_list = ! empty( $archetypes ) ? json_decode( $archetypes, true ) : [];
                $categories_info[] = [
                    'name' => $cat->name,
                    'archetypes' => $archetype_list,
                ];
            }
        }

        return new \WP_REST_Response( [
            'success' => true,
            'message' => 'Re-seed concluído!',
            'deleted' => $deleted_count,
            'kept' => $kept_count,
            'created' => ! is_wp_error( $new_catalog ) ? count( $new_catalog ) : 0,
            'categories_sample' => $categories_info,
        ], 200 );
    }
}
