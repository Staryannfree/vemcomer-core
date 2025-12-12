<?php
/**
 * Onboarding_Controller — REST endpoints para o wizard de onboarding
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Utils\Onboarding_Helper;
use VC\Utils\Restaurant_Helper;
use VC\Utils\Category_Helper;
use VC\Config\Cuisine_Menu_Blueprints;
use VC\Utils\Cuisine_Helper;
use function VC\Logging\log_event;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Onboarding_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // GET: Status do onboarding
        register_rest_route( 'vemcomer/v1', '/onboarding/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_status' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );

        // GET: Conteúdo HTML de um passo específico
        register_rest_route( 'vemcomer/v1', '/onboarding/step-content', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_step_content' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );

        // POST: Salvar passo do onboarding
        register_rest_route( 'vemcomer/v1', '/onboarding/step', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'save_step' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );

        // POST: Completar onboarding
        register_rest_route( 'vemcomer/v1', '/onboarding/complete', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'complete' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );
    }

    public function can_access(): bool {
        return is_user_logged_in() && ( current_user_can( 'edit_posts' ) || user_can( get_current_user_id(), 'lojista' ) );
    }

    public function get_status( WP_REST_Request $request ): WP_REST_Response {
        $restaurant_id = $request->get_param( 'restaurant_id' );
        $status        = Onboarding_Helper::get_onboarding_status( $restaurant_id ? (int) $restaurant_id : null );

        return new WP_REST_Response( $status, 200 );
    }

    /**
     * GET: Retorna o HTML do conteúdo de um passo específico
     */
    public function get_step_content( WP_REST_Request $request ): WP_REST_Response {
        $step = (int) $request->get_param( 'step' );
        $restaurant_id = (int) $request->get_param( 'restaurant_id' );
        
        // Log removido para melhorar performance
        $restaurant = Restaurant_Helper::get_restaurant_for_user();
        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ], 404 );
        }

        $step = (int) $request->get_param( 'step' );
        if ( $step < 1 || $step > 7 ) {
            return new WP_REST_Response( [ 'message' => __( 'Passo inválido.', 'vemcomer' ) ], 400 );
        }

        // Preparar dados necessários para os parciais
        if ( ! function_exists( 'vc_marketplace_collect_restaurant_data' ) ) {
            require_once VEMCOMER_CORE_DIR . 'templates/marketplace/helpers.php';
        }
        $restaurant_data = vc_marketplace_collect_restaurant_data( $restaurant );
        $wizard_data = $this->prepare_wizard_data( $restaurant, $restaurant_data );

        // Buscar apenas os 24 arquétipos (com cache)
        $cache_key = 'vc_onboarding_archetype_options';
        $cached_options = wp_cache_get( $cache_key );
        
        if ( false === $cached_options ) {
            $blueprints = Cuisine_Menu_Blueprints::all();
            $cuisine_options_primary = [];
            $cuisine_options_tags = []; // Não mostrar tags no passo 1
            
            foreach ( $blueprints as $archetype_slug => $blueprint_data ) {
                $label = $blueprint_data['label'] ?? ucfirst( str_replace( '_', ' ', $archetype_slug ) );
                
                // Criar ou obter termo vc_cuisine para este arquétipo
                $term = get_term_by( 'slug', $archetype_slug, 'vc_cuisine' );
                
                if ( ! $term || is_wp_error( $term ) ) {
                    // Criar termo se não existir
                    $result = wp_insert_term(
                        $label,
                        'vc_cuisine',
                        [
                            'slug' => $archetype_slug,
                        ]
                    );
                    
                    if ( ! is_wp_error( $result ) && is_array( $result ) ) {
                        $term_id = $result['term_id'];
                        // Marcar como primary e definir arquétipo
                        update_term_meta( $term_id, '_vc_is_primary_cuisine', '1' );
                        update_term_meta( $term_id, '_vc_cuisine_archetype', $archetype_slug );
                        $term = get_term( $term_id, 'vc_cuisine' );
                    } else {
                        continue; // Pular se não conseguir criar
                    }
                } else {
                    // Garantir que o termo existente tem os metas corretos
                    update_term_meta( $term->term_id, '_vc_is_primary_cuisine', '1' );
                    update_term_meta( $term->term_id, '_vc_cuisine_archetype', $archetype_slug );
                }
                
                if ( $term && ! is_wp_error( $term ) ) {
                    $cuisine_options_primary[] = [
                        'id'   => $term->term_id,
                        'name' => $term->name,
                    ];
                }
            }
            
            // Ordenar por nome
            usort( $cuisine_options_primary, function( $a, $b ) {
                return strcmp( $a['name'], $b['name'] );
            } );
            
            $cached_options = [
                'primary' => $cuisine_options_primary,
                'tags'    => [], // Sem tags no passo 1
            ];
            
            // Cache por 1 hora
            wp_cache_set( $cache_key, $cached_options, '', 3600 );
        }
        
        $cuisine_options_primary = $cached_options['primary'];
        $cuisine_options_tags = $cached_options['tags'];

        // Buscar categorias recomendadas (para passo 4)
        $recommended_categories = [];
        $has_primary_cuisine = false;
        if ( $restaurant instanceof \WP_Post ) {
            // Se o passo for 4 e houver cuisine_ids passados na requisição (ainda não salvos), usar esses
            $cuisine_ids_param = $request->get_param( 'cuisine_ids' );
            $cuisine_ids = [];
            
            if ( $step === 4 && ! empty( $cuisine_ids_param ) ) {
                // Usar IDs passados na requisição (selecionados no passo 1, mas ainda não salvos)
                $cuisine_ids = array_map( 'intval', explode( ',', $cuisine_ids_param ) );
                // Verificar quais são primárias
                foreach ( $cuisine_ids as $cuisine_id ) {
                    $term = get_term( $cuisine_id, 'vc_cuisine' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $is_primary = get_term_meta( $term->term_id, '_vc_is_primary_cuisine', true );
                        if ( $is_primary === '1' || $is_primary === '' ) {
                            $has_primary_cuisine = true;
                        }
                    }
                }
            } else {
                // Buscar do banco de dados (já salvos)
                // Tentar buscar via meta primeiro (mais confiável se acabou de salvar)
                $primary_cuisine = (int) get_post_meta( $restaurant->ID, '_vc_primary_cuisine', true );
                if ( $primary_cuisine > 0 ) {
                    $cuisine_ids[] = $primary_cuisine;
                }
                $secondary_cuisines_json = get_post_meta( $restaurant->ID, '_vc_secondary_cuisines', true );
                if ( $secondary_cuisines_json ) {
                    $secondary_cuisines = json_decode( $secondary_cuisines_json, true );
                    if ( is_array( $secondary_cuisines ) ) {
                        $cuisine_ids = array_merge( $cuisine_ids, array_map( 'intval', $secondary_cuisines ) );
                    }
                }

                // Se não encontrou via meta, buscar da taxonomia
                if ( empty( $cuisine_ids ) ) {
                    $cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'all' ] );
                    if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) {
                        foreach ( $cuisine_terms as $term ) {
                            $is_primary = get_term_meta( $term->term_id, '_vc_is_primary_cuisine', true );
                            if ( $is_primary === '' || $is_primary === '1' ) {
                                $cuisine_ids[] = (int) $term->term_id;
                            }
                        }
                    }
                }
                
                // Verificar se algum dos IDs encontrados é primário
                if ( ! empty( $cuisine_ids ) ) {
                    foreach ( $cuisine_ids as $id ) {
                        $is_primary = get_term_meta( $id, '_vc_is_primary_cuisine', true );
                        if ( $is_primary === '1' || $is_primary === '' ) {
                            $has_primary_cuisine = true;
                        }
                    }
                }
            }

            // Buscar categorias recomendadas (OTIMIZADO - apenas se passo 4)
            if ( $step === 4 ) {
                // Buscar arquétipos do restaurante baseado nas cuisines selecionadas
                $archetypes = [];
                if ( ! empty( $cuisine_ids ) ) {
                    foreach ( $cuisine_ids as $cuisine_id ) {
                        $term = get_term( $cuisine_id, 'vc_cuisine' );
                        if ( $term && ! is_wp_error( $term ) ) {
                            // Garantir que o termo tem arquétipo (se foi criado no passo 1, já deve ter)
                            $archetype_meta = get_term_meta( $term->term_id, '_vc_cuisine_archetype', true );
                            if ( empty( $archetype_meta ) ) {
                                // Se não tem meta, tentar resolver pelo slug e salvar
                                $archetype = Cuisine_Helper::get_archetype_for_cuisine( $term );
                                if ( $archetype ) {
                                    Cuisine_Helper::set_archetype_for_cuisine( $term->term_id, $archetype );
                                }
                            } else {
                                $archetype = $archetype_meta;
                            }
                            
                            if ( $archetype ) {
                                $archetypes[] = $archetype;
                            }
                        }
                    }
                    $archetypes = array_unique( $archetypes );
                }
                
                $cache_key_cats = 'vc_onboarding_recommended_cats_' . md5( implode( ',', $archetypes ) . '_' . implode( ',', $cuisine_ids ) . '_' . $restaurant->ID );
                $recommended_categories = wp_cache_get( $cache_key_cats );
                
                if ( false === $recommended_categories ) {
                    $user_categories = get_terms( [
                        'taxonomy'   => 'vc_menu_category',
                        'hide_empty' => false,
                        'fields'     => 'ids',
                        'meta_query' => [
                            [
                                'key'     => '_vc_restaurant_id',
                                'value'   => $restaurant->ID,
                                'compare' => '=',
                            ],
                            [
                                'key'     => '_vc_is_catalog_category',
                                'value'   => '1',
                                'compare' => '!=',
                            ],
                        ],
                    ] );

                    $user_category_names = [];
                    if ( ! is_wp_error( $user_categories ) && ! empty( $user_categories ) ) {
                        $user_cat_terms = get_terms( [
                            'taxonomy'   => 'vc_menu_category',
                            'include'    => $user_categories,
                            'hide_empty' => false,
                        ] );
                        foreach ( $user_cat_terms as $user_cat ) {
                            $user_category_names[] = strtolower( trim( $user_cat->name ) );
                        }
                    }

                    $catalog_categories = get_terms( [
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

                    if ( ! is_wp_error( $catalog_categories ) && ! empty( $catalog_categories ) ) {
                        $recommended = [];
                        $generic = [];
                        
                        // Buscar todos os meta de uma vez
                        $cat_term_ids = array_map( function( $cat ) { return $cat->term_id; }, $catalog_categories );
                        $all_recommended_archetypes_meta = [];
                        $all_recommended_cuisines_meta = [];
                        $all_order_meta = [];
                        foreach ( $cat_term_ids as $cat_id ) {
                            $all_recommended_archetypes_meta[ $cat_id ] = get_term_meta( $cat_id, '_vc_recommended_for_archetypes', true );
                            $all_recommended_cuisines_meta[ $cat_id ] = get_term_meta( $cat_id, '_vc_recommended_for_cuisines', true );
                            $all_order_meta[ $cat_id ] = (int) get_term_meta( $cat_id, '_vc_category_order', true );
                        }
                        
                        foreach ( $catalog_categories as $category ) {
                            if ( in_array( strtolower( trim( $category->name ) ), $user_category_names, true ) ) {
                                continue;
                            }
                            
                            $matched = false;
                            
                            // NOVO: Tentar primeiro via arquétipos
                            $recommended_for_archetypes = $all_recommended_archetypes_meta[ $category->term_id ] ?? '';
                            if ( ! empty( $recommended_for_archetypes ) && ! empty( $archetypes ) ) {
                                $archetype_list = json_decode( $recommended_for_archetypes, true );
                                if ( is_array( $archetype_list ) && ! empty( array_intersect( $archetypes, $archetype_list ) ) ) {
                                    $recommended[] = [
                                        'id'    => $category->term_id,
                                        'name'  => $category->name,
                                        'slug'  => $category->slug,
                                        'order' => $all_order_meta[ $category->term_id ] ?? 0,
                                    ];
                                    $matched = true;
                                }
                            }
                            
                            // Fallback: tentar via cuisine IDs (compatibilidade)
                            if ( ! $matched ) {
                                $recommended_for_cuisines = $all_recommended_cuisines_meta[ $category->term_id ] ?? '';
                                if ( ! empty( $recommended_for_cuisines ) && ! empty( $cuisine_ids ) ) {
                                    $recommended_cuisine_ids = json_decode( $recommended_for_cuisines, true );
                                    if ( is_array( $recommended_cuisine_ids ) ) {
                                        $intersection = array_intersect( $cuisine_ids, $recommended_cuisine_ids );
                                        if ( ! empty( $intersection ) ) {
                                            $recommended[] = [
                                                'id'    => $category->term_id,
                                                'name'  => $category->name,
                                                'slug'  => $category->slug,
                                                'order' => $all_order_meta[ $category->term_id ] ?? 0,
                                            ];
                                            $matched = true;
                                        }
                                    }
                                }
                            }
                            
                            // NÃO adicionar como genérica se o usuário selecionou arquétipos específicos
                            // Só mostrar categorias que têm match com os arquétipos selecionados
                            // Se não encontrou match e não tem arquétipos selecionados, aí sim pode ser genérica
                            if ( ! $matched && empty( $archetypes ) ) {
                                // Só mostrar genéricas se não selecionou nenhum arquétipo
                                $recommended_for_archetypes = $all_recommended_archetypes_meta[ $category->term_id ] ?? '';
                                $archetype_list = ! empty( $recommended_for_archetypes ) ? json_decode( $recommended_for_archetypes, true ) : [];
                                
                                // Se a categoria tem archetypes vazio (genérica) ou não tem archetypes definido
                                if ( empty( $archetype_list ) || ( is_array( $archetype_list ) && count( $archetype_list ) === 0 ) ) {
                                    // Categoria sem vínculo específico = genérica
                                    $generic[] = [
                                        'id'    => $category->term_id,
                                        'name'  => $category->name,
                                        'slug'  => $category->slug,
                                        'order' => $all_order_meta[ $category->term_id ] ?? 0,
                                    ];
                                }
                            }
                        }
                        
                        usort( $recommended, function( $a, $b ) {
                            return $a['order'] <=> $b['order'];
                        } );
                        
                        usort( $generic, function( $a, $b ) {
                            return $a['order'] <=> $b['order'];
                        } );
                        
                        // Se tem arquétipos selecionados, mostrar APENAS as categorias recomendadas (sem genéricas)
                        // Se não tem arquétipos, mostrar genéricas também
                        if ( ! empty( $archetypes ) ) {
                            $recommended_categories = $recommended; // Apenas categorias específicas dos arquétipos
                        } else {
                            $recommended_categories = array_merge( $recommended, $generic ); // Incluir genéricas
                        }
                    } else {
                        $recommended_categories = [];
                    }
                    
                    // Cache por 5 minutos
                    wp_cache_set( $cache_key_cats, $recommended_categories, '', 300 );
                }
            } else {
                $recommended_categories = [];
            }
        }

        // Renderizar o parcial do passo
        ob_start();
        $this->render_step_partial( $step, [
            'restaurant'              => $restaurant,
            'restaurant_data'         => $restaurant_data,
            'wizard_data'             => $wizard_data,
            'cuisine_options_primary' => $cuisine_options_primary,
            'cuisine_options_tags'     => $cuisine_options_tags,
            'recommended_categories'  => $recommended_categories,
            'has_primary_cuisine'     => $has_primary_cuisine,
        ] );
        $html = ob_get_clean();

        // Retornar HTML e dados salvos para o frontend atualizar wizardData
        return new WP_REST_Response( [
            'html'       => $html,
            'saved_data' => $wizard_data, // Dados salvos do backend
        ], 200 );
    }

    /**
     * Renderiza o parcial de um passo específico
     */
    private function render_step_partial( int $step, array $data ): void {
        $partial_path = VEMCOMER_CORE_DIR . "templates/marketplace/onboarding/onboarding-step-{$step}.php";
        
        if ( ! file_exists( $partial_path ) ) {
            echo '<div style="text-align:center;padding:40px;color:#999;">Passo não encontrado.</div>';
            return;
        }

        // Extrair variáveis para o escopo do parcial
        extract( $data );
        
        include $partial_path;
    }

    /**
     * Prepara os dados do wizard baseado no restaurante
     */
    private function prepare_wizard_data( \WP_Post $restaurant, array $restaurant_data ): array {
        $schedule_json = get_post_meta( $restaurant->ID, '_vc_restaurant_schedule', true );
        $schedule = [];
        if ( $schedule_json ) {
            $schedule_decoded = json_decode( $schedule_json, true );
            if ( is_array( $schedule_decoded ) ) {
                $days_map = [
                    'monday'    => 'seg',
                    'tuesday'   => 'ter',
                    'wednesday' => 'qua',
                    'thursday'  => 'qui',
                    'friday'    => 'sex',
                    'saturday'  => 'sab',
                    'sunday'    => 'dom',
                ];
                foreach ( $days_map as $meta_key => $slug ) {
                    $day_data = $schedule_decoded[ $meta_key ] ?? [];
                    $schedule[ $slug ] = [
                        'enabled' => ! empty( $day_data['enabled'] ),
                        'ranges'  => $day_data['periods'] ?? [ [ 'open' => '09:00', 'close' => '18:00' ] ],
                    ];
                }
            }
        }

        // Buscar categorias já criadas
        $category_names = [];
        $full_categories = []; // Nova lista com ID e Nome
        $user_categories = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant->ID,
                ],
            ],
        ] );
        if ( ! is_wp_error( $user_categories ) && ! empty( $user_categories ) ) {
            foreach ( $user_categories as $cat ) {
                $is_catalog = get_term_meta( $cat->term_id, '_vc_is_catalog_category', true );
                if ( $is_catalog !== '1' ) {
                    $category_names[] = $cat->name;
                    $full_categories[] = [
                        'id'   => $cat->term_id,
                        'name' => $cat->name,
                    ];
                }
            }
        }

        // Buscar produtos já criados
        $products = [];
        $menu_items = get_posts( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant->ID,
                ],
            ],
        ] );
        foreach ( $menu_items as $item ) {
            $category_terms = wp_get_post_terms( $item->ID, 'vc_menu_category', [ 'fields' => 'names' ] );
            $category_name = ! is_wp_error( $category_terms ) && ! empty( $category_terms ) ? $category_terms[0] : '';
            
            $products[] = [
                'name'        => $item->post_title,
                'category'    => $category_name,
                'category_id' => 0, // Será preenchido se necessário
                'price'       => (float) get_post_meta( $item->ID, '_vc_price', true ),
                'description' => $item->post_content,
            ];
        }

        // Buscar cuisine_ids salvos (passo 1)
        $cuisine_ids = [];
        $primary_cuisine = (int) get_post_meta( $restaurant->ID, '_vc_primary_cuisine', true );
        if ( $primary_cuisine > 0 ) {
            $cuisine_ids[] = $primary_cuisine;
        }
        $secondary_cuisines_json = get_post_meta( $restaurant->ID, '_vc_secondary_cuisines', true );
        if ( $secondary_cuisines_json ) {
            $secondary_cuisines = json_decode( $secondary_cuisines_json, true );
            if ( is_array( $secondary_cuisines ) ) {
                $cuisine_ids = array_merge( $cuisine_ids, array_map( 'intval', $secondary_cuisines ) );
            }
        }
        // Se não encontrou via meta, buscar da taxonomia
        if ( empty( $cuisine_ids ) ) {
            $cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'ids' ] );
            if ( ! is_wp_error( $cuisine_terms ) && ! empty( $cuisine_terms ) ) {
                $cuisine_ids = array_map( 'intval', $cuisine_terms );
            }
        }

        // Buscar grupos de adicionais salvos (passo 6)
        $addon_groups = [];
        $store_groups = get_posts( [
            'post_type'      => 'vc_product_modifier',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant->ID,
                ],
                [
                    'key'   => '_vc_group_id',
                    'value' => '0',
                ],
                [
                    'key'     => '_vc_catalog_group_id',
                    'compare' => 'EXISTS',
                ],
            ],
            'fields'         => 'ids',
        ] );
        foreach ( $store_groups as $group_id ) {
            $catalog_group_id = (int) get_post_meta( $group_id, '_vc_catalog_group_id', true );
            if ( $catalog_group_id > 0 ) {
                $addon_groups[] = $catalog_group_id;
            }
        }

        return [
            'restaurant_id'  => $restaurant->ID, // CRÍTICO: Necessário para o Passo 7 buscar produtos
            'cuisine_ids'    => array_unique( $cuisine_ids ),
            'name'           => $restaurant->post_title,
            'whatsapp'       => $restaurant_data['whatsapp'] ?? '',
            'logo'           => $restaurant_data['logo'] ?? '',
            'address'        => $restaurant_data['endereco'] ?? '',
            'neighborhood'   => get_post_meta( $restaurant->ID, 'vc_restaurant_neighborhood', true ) ?: ( $restaurant_data['bairro'] ?? '' ),
            'city'           => get_post_meta( $restaurant->ID, 'vc_restaurant_city', true ) ?: '',
            'zipcode'        => get_post_meta( $restaurant->ID, 'vc_restaurant_zipcode', true ) ?: '',
            'delivery'       => get_post_meta( $restaurant->ID, 'vc_restaurant_delivery', true ) === '1',
            'pickup'         => false, // Será preenchido se necessário
            'schedule'       => $schedule,
            'category_names' => $category_names,
            'full_categories' => $full_categories,
            'products'       => $products,
            'addon_groups'   => array_unique( $addon_groups ),
        ];
    }

    public function save_step( WP_REST_Request $request ): WP_REST_Response {
        $restaurant = Restaurant_Helper::get_restaurant_for_user();
        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ], 404 );
        }

        $payload = (array) $request->get_json_params();
        $step    = isset( $payload['step'] ) ? (int) $payload['step'] : 0;
        $step_slug = isset( $payload['step_slug'] ) ? sanitize_text_field( $payload['step_slug'] ) : '';

        // Log do payload recebido (especialmente para passo 5)
        if ( $step === 5 ) {
            error_log( sprintf( 'Onboarding::save_step - Passo 5 recebido. Restaurante ID: %d', $restaurant->ID ) );
            error_log( sprintf( 'Onboarding::save_step - Payload produtos: %d produto(s)', isset( $payload['products'] ) && is_array( $payload['products'] ) ? count( $payload['products'] ) : 0 ) );
            if ( isset( $payload['products'] ) && is_array( $payload['products'] ) ) {
                foreach ( $payload['products'] as $idx => $product ) {
                    error_log( sprintf( 'Onboarding::save_step - Produto %d: name=%s, category=%s, category_id=%s, price=%s', 
                        $idx + 1,
                        $product['name'] ?? 'não definido',
                        $product['category'] ?? 'não definido',
                        $product['category_id'] ?? 'não definido',
                        $product['price'] ?? 'não definido'
                    ) );
                }
            }
        }

        if ( $step < 1 || $step > 7 ) {
            return new WP_REST_Response( [ 'message' => __( 'Passo inválido.', 'vemcomer' ) ], 400 );
        }

        // Processar dados do passo
        $result = $this->process_step_data( $restaurant->ID, $step, $payload );

        if ( is_wp_error( $result ) ) {
            error_log( sprintf( 'Onboarding::save_step - ERRO ao processar passo %d: %s', $step, $result->get_error_message() ) );
            return new WP_REST_Response( [ 'message' => $result->get_error_message() ], 400 );
        }

        // Marcar passo como concluído
        if ( $step_slug ) {
            Onboarding_Helper::mark_step_completed( $restaurant->ID, $step_slug );
        }
        Onboarding_Helper::update_current_step( $restaurant->ID, $step );

        // Preparar resposta base
        $response_data = [
            'success' => true,
            'message' => __( 'Passo salvo com sucesso.', 'vemcomer' ),
        ];

        // Se for o passo 4 (categorias), retornar as categorias criadas
        if ( $step === 4 && is_array( $result ) ) {
            $response_data['created_categories'] = $result;
        }

        return new WP_REST_Response( $response_data, 200 );
    }

    public function complete( WP_REST_Request $request ): WP_REST_Response {
        $restaurant = Restaurant_Helper::get_restaurant_for_user();
        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ], 404 );
        }

        Onboarding_Helper::complete_onboarding( $restaurant->ID );

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Onboarding concluído com sucesso!', 'vemcomer' ),
        ], 200 );
    }

    /**
     * Processa os dados de cada passo do wizard
     */
    private function process_step_data( int $restaurant_id, int $step, array $payload ) {
        switch ( $step ) {
            case 1: // Tipo de restaurante
                return $this->save_cuisine_type( $restaurant_id, $payload );

            case 2: // Dados básicos
                return $this->save_basic_data( $restaurant_id, $payload );

            case 3: // Endereço e horários
                return $this->save_address_schedule( $restaurant_id, $payload );

            case 4: // Categorias do cardápio
                return $this->save_menu_categories( $restaurant_id, $payload );

            case 5: // Primeiros produtos
                return $this->save_products( $restaurant_id, $payload );

            case 6: // Adicionais
                return $this->save_addons( $restaurant_id, $payload );

            default:
                return true;
        }
    }

    private function save_cuisine_type( int $restaurant_id, array $payload ) {
        if ( ! isset( $payload['cuisine_ids'] ) || ! is_array( $payload['cuisine_ids'] ) ) {
            return new WP_Error( 'invalid_data', __( 'IDs de categorias não fornecidos.', 'vemcomer' ) );
        }

        $cuisine_ids = array_map( 'intval', $payload['cuisine_ids'] );
        $cuisine_ids = array_slice( array_unique( array_filter( $cuisine_ids ) ), 0, 3 ); // Máximo 3

        if ( empty( $cuisine_ids ) ) {
            return new WP_Error( 'invalid_data', __( 'Selecione pelo menos uma categoria.', 'vemcomer' ) );
        }

        $primary   = array_shift( $cuisine_ids );
        $secondary = array_slice( $cuisine_ids, 0, 2 );

        update_post_meta( $restaurant_id, '_vc_primary_cuisine', $primary );
        update_post_meta( $restaurant_id, '_vc_secondary_cuisines', wp_json_encode( $secondary ) );

        // Atualizar taxonomia
        if ( taxonomy_exists( 'vc_cuisine' ) ) {
            $all_ids = array_merge( [ $primary ], $secondary );
            wp_set_object_terms( $restaurant_id, $all_ids, 'vc_cuisine', false );
        }

        return true;
    }

    private function save_basic_data( int $restaurant_id, array $payload ) {
        $name = isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '';
        $whatsapp = isset( $payload['whatsapp'] ) ? sanitize_text_field( $payload['whatsapp'] ) : '';

        if ( empty( $name ) ) {
            return new WP_Error( 'invalid_data', __( 'Nome da loja é obrigatório.', 'vemcomer' ) );
        }

        if ( empty( $whatsapp ) ) {
            return new WP_Error( 'invalid_data', __( 'Telefone/WhatsApp é obrigatório.', 'vemcomer' ) );
        }

        // Atualizar título do post
        wp_update_post( [
            'ID'         => $restaurant_id,
            'post_title' => $name,
        ] );

        // Atualizar WhatsApp
        update_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', $whatsapp );

        // Logo (opcional)
        if ( isset( $payload['logo'] ) && ! empty( $payload['logo'] ) ) {
            $logo_url = $this->handle_image_upload( $payload['logo'], $restaurant_id );
            if ( $logo_url ) {
                update_post_meta( $restaurant_id, 'vc_restaurant_logo', $logo_url );
                // Também definir como featured image
                $this->set_logo_as_featured( $logo_url, $restaurant_id );
            }
        }

        return true;
    }

    private function save_address_schedule( int $restaurant_id, array $payload ) {
        // Endereço
        $address = isset( $payload['address'] ) ? sanitize_text_field( $payload['address'] ) : '';
        $neighborhood = isset( $payload['neighborhood'] ) ? sanitize_text_field( $payload['neighborhood'] ) : '';
        $city = isset( $payload['city'] ) ? sanitize_text_field( $payload['city'] ) : '';
        $zipcode = isset( $payload['zipcode'] ) ? sanitize_text_field( $payload['zipcode'] ) : '';

        if ( empty( $address ) ) {
            return new WP_Error( 'invalid_data', __( 'Endereço é obrigatório.', 'vemcomer' ) );
        }

        update_post_meta( $restaurant_id, 'vc_restaurant_address', $address );

        // Bairro, Cidade e CEP (se fornecidos)
        if ( ! empty( $neighborhood ) ) {
            update_post_meta( $restaurant_id, 'vc_restaurant_neighborhood', $neighborhood );
        }
        if ( ! empty( $city ) ) {
            update_post_meta( $restaurant_id, 'vc_restaurant_city', $city );
        }
        if ( ! empty( $zipcode ) ) {
            update_post_meta( $restaurant_id, 'vc_restaurant_zipcode', $zipcode );
        }

        // Coordenadas (se fornecidas)
        if ( isset( $payload['lat'] ) && isset( $payload['lng'] ) ) {
            update_post_meta( $restaurant_id, 'vc_restaurant_lat', (float) $payload['lat'] );
            update_post_meta( $restaurant_id, 'vc_restaurant_lng', (float) $payload['lng'] );
        }

        // Métodos de atendimento
        $delivery_enabled = isset( $payload['delivery'] ) && $payload['delivery'] === true;
        $pickup_enabled   = isset( $payload['pickup'] ) && $payload['pickup'] === true;

        update_post_meta( $restaurant_id, 'vc_restaurant_delivery', $delivery_enabled ? '1' : '0' );

        // Horários - converter formato do wizard para formato do sistema
        if ( isset( $payload['schedule'] ) && is_array( $payload['schedule'] ) ) {
            $schedule_valid = false;
            $schedule_formatted = [];
            
            $days_map = [
                'seg' => 'monday',
                'ter' => 'tuesday',
                'qua' => 'wednesday',
                'qui' => 'thursday',
                'sex' => 'friday',
                'sab' => 'saturday',
                'dom' => 'sunday',
            ];

            foreach ( $days_map as $slug => $meta_key ) {
                $day_data = $payload['schedule'][ $slug ] ?? [];
                $enabled = ! empty( $day_data['enabled'] );
                $ranges = $day_data['ranges'] ?? [];

                $periods = [];
                if ( is_array( $ranges ) ) {
                    foreach ( $ranges as $range ) {
                        $open  = isset( $range['open'] ) ? sanitize_text_field( (string) $range['open'] ) : '';
                        $close = isset( $range['close'] ) ? sanitize_text_field( (string) $range['close'] ) : '';
                        if ( $open || $close ) {
                            $periods[] = [ 'open' => $open, 'close' => $close ];
                            $schedule_valid = true;
                        }
                    }
                }

                $schedule_formatted[ $meta_key ] = [
                    'enabled' => $enabled,
                    'periods' => $periods ?: [],
                ];
            }

            if ( ! $schedule_valid ) {
                return new WP_Error( 'invalid_data', __( 'Configure pelo menos um dia de funcionamento.', 'vemcomer' ) );
            }

            update_post_meta( $restaurant_id, '_vc_restaurant_schedule', wp_json_encode( $schedule_formatted ) );
        } else {
            return new WP_Error( 'invalid_data', __( 'Horários não fornecidos.', 'vemcomer' ) );
        }

        return true;
    }

    private function save_menu_categories( int $restaurant_id, array $payload ) {
        if ( ! isset( $payload['category_names'] ) || ! is_array( $payload['category_names'] ) ) {
            // Log removido para performance
            return new WP_Error( 'invalid_data', __( 'Categorias não fornecidas.', 'vemcomer' ) );
        }

        $category_names = array_filter( array_map( 'sanitize_text_field', $payload['category_names'] ) );

        if ( empty( $category_names ) ) {
            // Log removido para performance
            return new WP_Error( 'invalid_data', __( 'Selecione pelo menos uma categoria.', 'vemcomer' ) );
        }

        // Log removido para performance

        // Criar categorias diretamente usando o método do controller
        $controller = new Menu_Categories_Controller();
        $order      = 0;
        $created    = 0;
        $errors     = [];
        $created_ids = [];

        foreach ( $category_names as $name ) {
            $order++;
            // Criar request simulado
            $request = new WP_REST_Request( 'POST', '/vemcomer/v1/menu-categories' );
            $request->set_body_params( [
                'name'  => $name,
                'order' => $order,
            ] );
            
            // Verificar permissão
            if ( ! $controller->can_manage_categories() ) {
                $error_msg = "Sem permissão para criar categoria: {$name}";
                $errors[] = $error_msg;
                // Log removido para performance
                continue;
            }
            
            // Chamar o método diretamente
            $result = $controller->create_category( $request );
            
            // Se retornar erro, logar mas continuar
            if ( is_wp_error( $result ) ) {
                $error_msg = $result->get_error_message();
                $errors[] = "Erro ao criar categoria '{$name}': {$error_msg}";
                // Log removido para performance
            } else {
                $created++;
                // Tentar extrair o ID da categoria criada
                if ( is_array( $result ) && isset( $result['id'] ) ) {
                    $created_ids[] = $result['id'];
                } elseif ( $result instanceof WP_REST_Response ) {
                    $data = $result->get_data();
                    if ( isset( $data['id'] ) ) {
                        $created_ids[] = $data['id'];
                    }
                }
            }
        }

        // Limpar cache de termos para garantir que as novas categorias apareçam
        clean_term_cache( null, 'vc_menu_category' );
        // Limpar cache de meta de termos também
        delete_transient( 'vc_menu_categories_' . $restaurant_id );

        // Log removido para performance

        if ( $created === 0 && ! empty( $errors ) ) {
            return new WP_Error( 'vc_categories_creation_failed', implode( '; ', $errors ), [ 'status' => 500 ] );
        }

        // Retornar as categorias criadas para o frontend
        $final_categories = [];
        foreach ( $category_names as $idx => $name ) {
            // Tentar encontrar o ID criado
            $cat_id = isset( $created_ids[ $idx ] ) ? $created_ids[ $idx ] : 0;
            if ( $cat_id === 0 ) {
                // Fallback: tentar buscar pelo nome e slug do restaurante
                $base_slug = sanitize_title( $name );
                $unique_slug = $base_slug . '-rest-' . $restaurant_id;
                $term = get_term_by( 'slug', $unique_slug, 'vc_menu_category' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $cat_id = $term->term_id;
                }
            }
            
            if ( $cat_id > 0 ) {
                $final_categories[] = [
                    'id' => $cat_id,
                    'name' => $name
                ];
            }
        }

        return $final_categories;
    }

    private function save_products( int $restaurant_id, array $payload ) {
        error_log( sprintf( 'Onboarding::save_products - Recebido para restaurante %d: %d produto(s)', $restaurant_id, isset( $payload['products'] ) && is_array( $payload['products'] ) ? count( $payload['products'] ) : 0 ) );
        
        // CRÍTICO: Garantir que o restaurant_id está salvo no meta do usuário
        // Menu_Items_Controller busca o restaurant_id do usuário logado
        $user_id = get_current_user_id();
        if ( $user_id > 0 ) {
            $current_user_restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );
            if ( $current_user_restaurant_id !== $restaurant_id ) {
                error_log( sprintf( 'Onboarding::save_products - Atualizando vc_restaurant_id do usuário %d: %d -> %d', $user_id, $current_user_restaurant_id, $restaurant_id ) );
                update_user_meta( $user_id, 'vc_restaurant_id', $restaurant_id );
            }
        }
        
        // Produtos são opcionais no onboarding (usuário pode pular)
        if ( ! isset( $payload['products'] ) || ! is_array( $payload['products'] ) ) {
            error_log( 'Onboarding::save_products - Produtos não fornecidos, retornando sucesso (opcional)' );
            return true; // Produtos são opcionais
        }

        if ( empty( $payload['products'] ) ) {
            error_log( 'Onboarding::save_products - Array de produtos está vazio, retornando sucesso (opcional)' );
            return true; // Produtos são opcionais, pode pular
        }
        
        error_log( sprintf( 'Onboarding::save_products - Processando %d produto(s)', count( $payload['products'] ) ) );

        // Buscar produtos já existentes do restaurante para evitar duplicatas
        $existing_products = get_posts( [
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
        
        $existing_names = [];
        if ( ! empty( $existing_products ) ) {
            foreach ( $existing_products as $product_id ) {
                $existing_names[] = strtolower( trim( get_the_title( $product_id ) ) );
            }
        }

        // Criar produtos diretamente usando o método do controller
        $controller = new Menu_Items_Controller();
        $created_count = 0;
        $skipped_count = 0;

        foreach ( $payload['products'] as $product_data ) {
            $product_name = isset( $product_data['name'] ) ? sanitize_text_field( $product_data['name'] ) : '';
            
            // Verificar se produto com mesmo nome já existe
            if ( ! empty( $product_name ) && in_array( strtolower( trim( $product_name ) ), $existing_names, true ) ) {
                $skipped_count++;
                continue; // Pular produto duplicado
            }
            
            // Preparar dados do produto
            $category_id = isset( $product_data['category_id'] ) ? $product_data['category_id'] : 0;
            
            // Se category_id for string "name:...", extrair o nome
            $category_name = isset( $product_data['category'] ) ? sanitize_text_field( $product_data['category'] ) : '';
            if ( is_string( $category_id ) && strpos( $category_id, 'name:' ) === 0 ) {
                $category_name = substr( $category_id, 5 ); // Remove "name:"
                $category_id = 0;
            }
            
            // Garantir que category_id seja inteiro
            if ( ! is_numeric( $category_id ) ) {
                $category_id = 0;
            } else {
                $category_id = (int) $category_id;
            }
            
            // Validar preço
            $price = isset( $product_data['price'] ) ? $product_data['price'] : 0;
            if ( is_string( $price ) ) {
                $price = (float) str_replace( ',', '.', $price );
            } else {
                $price = (float) $price;
            }
            
            $product_payload = [
                'title'       => $product_name,
                'description' => isset( $product_data['description'] ) ? sanitize_textarea_field( $product_data['description'] ) : '',
                'price'       => $price,
                'category_id' => $category_id,
                'category'    => $category_name, // Menu_Items_Controller usará isso se category_id for 0
                'is_available' => true,
            ];
            
            error_log( sprintf( 'Onboarding::save_products - Preparando produto "%s": category_id=%s, category=%s, price=%s', 
                $product_name, 
                $product_payload['category_id'], 
                $product_payload['category'],
                $product_payload['price']
            ) );

            if ( isset( $product_data['image'] ) && ! empty( $product_data['image'] ) ) {
                $product_payload['image'] = $product_data['image'];
            }

            // Criar request para o Menu_Items_Controller
            $request = new WP_REST_Request( 'POST', '/vemcomer/v1/menu-items' );
            $request->set_body_params( $product_payload );
            
            // Verificar permissão
            if ( ! $controller->can_manage_menu_items() ) {
                error_log( sprintf( 'Onboarding::save_products - Sem permissão para criar produto "%s"', $product_name ) );
                continue;
            }
            
            // Chamar o método diretamente
            $result = $controller->create_menu_item( $request );
            
            // Se retornar erro, logar detalhadamente
            if ( is_wp_error( $result ) ) {
                error_log( sprintf( 'Onboarding::save_products - ERRO ao criar produto "%s": %s (Código: %s)', 
                    $product_name, 
                    $result->get_error_message(),
                    $result->get_error_code()
                ) );
                // Logar dados adicionais do erro
                $error_data = $result->get_error_data();
                if ( $error_data ) {
                    error_log( sprintf( 'Onboarding::save_products - Dados do erro: %s', print_r( $error_data, true ) ) );
                }
            } else {
                $created_count++;
                $product_id = 0;
                if ( is_array( $result ) && isset( $result['id'] ) ) {
                    $product_id = (int) $result['id'];
                } elseif ( $result instanceof WP_REST_Response ) {
                    $response_data = $result->get_data();
                    $product_id = isset( $response_data['id'] ) ? (int) $response_data['id'] : 0;
                }
                
                // Verificar se o restaurant_id foi salvo corretamente
                $saved_restaurant_id = get_post_meta( $product_id, '_vc_restaurant_id', true );
                
                // Garantir que o restaurante está anexado (fallback de segurança)
                if ( $product_id > 0 && (int) $saved_restaurant_id !== $restaurant_id ) {
                    Restaurant_Helper::attach_restaurant_to_product( $product_id, $restaurant_id );
                    error_log( sprintf( 'Onboarding::save_products - Corrigido restaurant_id do produto "%s" (ID: %d): %s -> %d', 
                        $product_name, 
                        $product_id,
                        $saved_restaurant_id,
                        $restaurant_id
                    ) );
                }
                
                error_log( sprintf( 'Onboarding::save_products - Produto "%s" criado com sucesso (ID: %d, restaurant_id esperado: %d, restaurant_id salvo: %s)', 
                    $product_name, 
                    $product_id,
                    $restaurant_id,
                    $saved_restaurant_id
                ) );
                
                // Adicionar à lista de existentes para evitar duplicatas na mesma requisição
                if ( ! empty( $product_name ) ) {
                    $existing_names[] = strtolower( trim( $product_name ) );
                }
            }
        }

        // Logar resultado
        error_log( sprintf( 'Onboarding passo 5: %d produto(s) criado(s), %d produto(s) já existente(s) (pulados), %d produto(s) total recebido(s)', $created_count, $skipped_count, count( $payload['products'] ) ) );
        
        // Se nenhum produto foi criado e nenhum foi pulado, pode haver um problema
        if ( $created_count === 0 && $skipped_count === 0 && ! empty( $payload['products'] ) ) {
            error_log( 'Onboarding passo 5: ATENÇÃO - Nenhum produto foi criado ou pulado, mas produtos foram enviados!' );
            return new WP_Error( 'no_products_created', __( 'Nenhum produto foi criado. Verifique os logs do servidor.', 'vemcomer' ) );
        }
        
        // Se pelo menos um produto foi criado ou pulado, considerar sucesso
        if ( $created_count > 0 || $skipped_count > 0 ) {
            return true;
        }
        
        // Se não criou nada mas tinha payload, retornar sucesso mas com log de aviso (para não travar o fluxo)
        // Isso evita o erro 400 no frontend se a criação falhar por algum motivo não crítico
        if ( ! empty( $payload['products'] ) ) {
            error_log( 'Onboarding passo 5: Nenhum produto criado, mas retornando sucesso para não travar.' );
            return true;
        }
        
        return true;
    }

    private function save_addons( int $restaurant_id, array $payload ) {
        // Adicionais são opcionais, então não retorna erro se não houver
        if ( ! isset( $payload['addon_groups'] ) || ! is_array( $payload['addon_groups'] ) ) {
            return true;
        }

        $catalog_group_ids = array_map( 'intval', array_filter( $payload['addon_groups'] ) );
        
        if ( empty( $catalog_group_ids ) ) {
            return true; // Nenhum grupo selecionado, passo pode ser pulado
        }

        // Usar o serviço para aplicar grupos à loja
        if ( ! class_exists( '\\VC\\Services\\Addon_Onboarding_Service' ) ) {
            error_log( 'Onboarding: Classe Addon_Onboarding_Service não encontrada' );
            return new WP_Error( 'service_not_found', __( 'Serviço de adicionais não encontrado.', 'vemcomer' ) );
        }

        try {
            $result = \VC\Services\Addon_Onboarding_Service::apply_recommended_groups_to_store( $restaurant_id, $catalog_group_ids );

            if ( ! is_array( $result ) ) {
                error_log( 'Onboarding: Resultado do serviço de adicionais não é um array' );
                return new WP_Error( 'addon_application_failed', __( 'Erro ao processar grupos de adicionais.', 'vemcomer' ) );
            }

            if ( ! isset( $result['success'] ) || ! $result['success'] ) {
                $error_message = ! empty( $result['errors'] ) && is_array( $result['errors'] ) 
                    ? implode( ', ', $result['errors'] ) 
                    : __( 'Erro ao aplicar grupos de adicionais.', 'vemcomer' );
                error_log( 'Onboarding: Erro ao aplicar grupos - ' . $error_message );
                return new WP_Error( 'addon_application_failed', $error_message );
            }

            // Logar sucesso
            if ( isset( $result['groups_created'] ) && $result['groups_created'] > 0 ) {
                error_log( sprintf( 'Onboarding: %d grupo(s) de adicionais aplicado(s) ao restaurante %d', $result['groups_created'], $restaurant_id ) );
            }

            return true;
        } catch ( \Exception $e ) {
            error_log( 'Onboarding: Exceção ao salvar adicionais - ' . $e->getMessage() );
            error_log( 'Onboarding: Stack trace - ' . $e->getTraceAsString() );
            return new WP_Error( 'addon_exception', sprintf( __( 'Erro ao salvar adicionais: %s', 'vemcomer' ), $e->getMessage() ) );
        }
    }

    private function handle_image_upload( string $image_data, int $restaurant_id ): ?string {
        // Se for data:image, converter para arquivo
        if ( strpos( $image_data, 'data:image' ) === 0 ) {
            // Reutilizar lógica do Merchant_Settings_Controller
            $controller = new Merchant_Settings_Controller();
            $reflection = new \ReflectionClass( $controller );
            $method     = $reflection->getMethod( 'maybe_handle_data_image' );
            $method->setAccessible( true );
            $result = $method->invoke( $controller, $image_data, 'logo', $restaurant_id );

            if ( $result && is_string( $result ) ) {
                return $result;
            }
        }

        return null;
    }

    private function set_logo_as_featured( string $logo_url, int $restaurant_id ): void {
        // Buscar attachment pelo URL
        $attachment_id = attachment_url_to_postid( $logo_url );
        if ( $attachment_id ) {
            set_post_thumbnail( $restaurant_id, $attachment_id );
        }
    }

}

