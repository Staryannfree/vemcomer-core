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

        // GET: Categorias recomendadas baseadas no tipo de restaurante
        register_rest_route( 'vemcomer/v1', '/menu-categories/recommended', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_recommended_categories' ],
            'permission_callback' => [ $this, 'can_manage_categories' ],
        ] );

        // PUT: Editar categoria existente
        register_rest_route( 'vemcomer/v1', '/menu-categories/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_category' ],
            'permission_callback' => [ $this, 'can_manage_categories' ],
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

        // DELETE: Deletar categoria
        register_rest_route( 'vemcomer/v1', '/menu-categories/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_category' ],
            'permission_callback' => [ $this, 'can_manage_categories' ],
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
     * Lista categorias do cardápio do restaurante atual
     */
    public function get_categories( WP_REST_Request $request ): WP_REST_Response {
        // Buscar restaurante do usuário atual
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( ! $restaurant_id ) {
            // Tentar buscar pelo post_author
            $restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $restaurants ) ) {
                $restaurant_id = $restaurants[0];
            }
        }

        // Se não tiver restaurante, retornar vazio
        if ( ! $restaurant_id ) {
            return new WP_REST_Response( [], 200 );
        }

        $categories = get_terms( [
            'taxonomy'   => CPT_MenuItem::TAX_CATEGORY,
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $categories ) ) {
            return new WP_REST_Response( [], 200 );
        }

        $items = [];
        foreach ( $categories as $term ) {
            // Verificar se é categoria do catálogo
            $is_catalog = get_term_meta( $term->term_id, '_vc_is_catalog_category', true );
            $term_restaurant_id = (int) get_term_meta( $term->term_id, '_vc_restaurant_id', true );
            
            // Incluir apenas categorias criadas pelo usuário (não do catálogo) E do restaurante atual
            if ( $is_catalog !== '1' && $term_restaurant_id === $restaurant_id ) {
                $order = (int) get_term_meta( $term->term_id, '_vc_category_order', true );
                $items[] = [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                    'order' => $order,
                ];
            }
        }

        // Ordenar por ordem, depois por nome
        usort( $items, function( $a, $b ) {
            if ( $a['order'] !== $b['order'] ) {
                return $a['order'] <=> $b['order'];
            }
            return strcasecmp( $a['name'], $b['name'] );
        } );

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

        // Buscar restaurante do usuário atual
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( ! $restaurant_id ) {
            // Tentar buscar pelo post_author
            $restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $restaurants ) ) {
                $restaurant_id = $restaurants[0];
            }
        }

        if ( ! $restaurant_id ) {
            return new WP_Error(
                'vc_no_restaurant',
                __( 'Nenhum restaurante encontrado para este usuário.', 'vemcomer' ),
                [ 'status' => 403 ]
            );
        }

        // Verificar se já existe (apenas ao criar, não ao editar)
        $term_id_param = $request->get_param( 'id' );
        if ( ! $term_id_param ) {
            // Só verifica duplicatas ao criar (não ao editar)
            // Buscar termos com o mesmo nome
            $existing_terms = get_terms( [
                'taxonomy'   => CPT_MenuItem::TAX_CATEGORY,
                'name'       => $name,
                'hide_empty' => false,
            ] );

            if ( ! is_wp_error( $existing_terms ) && ! empty( $existing_terms ) ) {
                // Verificar se algum dos termos encontrados pertence ao mesmo restaurante
                // (ignorar categorias do catálogo e de outros restaurantes)
                foreach ( $existing_terms as $term ) {
                    $is_catalog = get_term_meta( $term->term_id, '_vc_is_catalog_category', true );
                    $term_restaurant_id = (int) get_term_meta( $term->term_id, '_vc_restaurant_id', true );
                    
                    // Se não é do catálogo E pertence ao mesmo restaurante, então já existe
                    if ( $is_catalog !== '1' && $term_restaurant_id === $restaurant_id ) {
                        return new WP_Error(
                            'vc_category_exists',
                            __( 'Uma categoria com este nome já existe.', 'vemcomer' ),
                            [ 'status' => 400 ]
                        );
                    }
                }
            }
        }

        // Criar o termo com slug único por restaurante
        // Adicionar ID do restaurante ao slug para evitar conflitos entre restaurantes
        $base_slug = sanitize_title( $name );
        $unique_slug = $base_slug . '-rest-' . $restaurant_id;
        
        $result = wp_insert_term(
            $name,
            CPT_MenuItem::TAX_CATEGORY,
            [
                'slug' => $unique_slug,
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

        // Vincular categoria ao restaurante
        update_term_meta( $term_id, '_vc_restaurant_id', $restaurant_id );

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

    /**
     * GET /wp-json/vemcomer/v1/menu-categories/recommended
     * Retorna categorias de cardápio recomendadas baseadas no tipo de restaurante do usuário
     */
    public function get_recommended_categories( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'vc_not_logged_in',
                __( 'Você precisa estar logado para ver categorias recomendadas.', 'vemcomer' ),
                [ 'status' => 401 ]
            );
        }

        // Buscar restaurante do usuário atual
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( ! $restaurant_id ) {
            // Tentar buscar pelo post_author
            $restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $restaurants ) ) {
                $restaurant_id = $restaurants[0];
            }
        }

        if ( ! $restaurant_id ) {
            return new WP_Error(
                'vc_no_restaurant',
                __( 'Nenhum restaurante encontrado para este usuário.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Buscar categorias de restaurante (vc_cuisine) associadas ao restaurante
        $cuisine_terms = wp_get_post_terms( $restaurant_id, 'vc_cuisine', [ 'fields' => 'ids' ] );

        if ( is_wp_error( $cuisine_terms ) || empty( $cuisine_terms ) ) {
            // Se não tiver categorias, retornar categorias genéricas (sem vínculo específico)
            $cuisine_ids = [];
        } else {
            $cuisine_ids = array_map( 'intval', $cuisine_terms );
        }

        // Buscar categorias já criadas pelo restaurante (para filtrar das recomendações)
        $user_categories = get_terms( [
            'taxonomy'   => CPT_MenuItem::TAX_CATEGORY,
            'hide_empty' => false,
        ] );

        $user_category_names = [];
        if ( ! is_wp_error( $user_categories ) && ! empty( $user_categories ) ) {
            foreach ( $user_categories as $user_cat ) {
                $is_catalog = get_term_meta( $user_cat->term_id, '_vc_is_catalog_category', true );
                $cat_restaurant_id = (int) get_term_meta( $user_cat->term_id, '_vc_restaurant_id', true );
                
                // Se não é do catálogo E pertence ao restaurante atual, adicionar à lista
                if ( $is_catalog !== '1' && $cat_restaurant_id === $restaurant_id ) {
                    $user_category_names[] = strtolower( trim( $user_cat->name ) );
                }
            }
        }

        // Buscar todas as categorias de cardápio do catálogo
        $catalog_categories = get_terms( [
            'taxonomy'   => CPT_MenuItem::TAX_CATEGORY,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => '_vc_is_catalog_category',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ] );

        if ( is_wp_error( $catalog_categories ) || empty( $catalog_categories ) ) {
            return new WP_REST_Response( [
                'success' => true,
                'categories' => [],
                'message' => __( 'Nenhuma categoria recomendada encontrada.', 'vemcomer' ),
            ], 200 );
        }

        // Filtrar categorias recomendadas baseadas nas categorias do restaurante
        $recommended = [];
        $generic = []; // Categorias genéricas (sem vínculo específico)

        foreach ( $catalog_categories as $category ) {
            // Pular se o restaurante já criou uma categoria com o mesmo nome
            if ( in_array( strtolower( trim( $category->name ) ), $user_category_names, true ) ) {
                continue;
            }
            $recommended_for = get_term_meta( $category->term_id, '_vc_recommended_for_cuisines', true );
            
            if ( empty( $recommended_for ) ) {
                // Categoria genérica (sem vínculo específico)
                $generic[] = [
                    'id'    => $category->term_id,
                    'name'  => $category->name,
                    'slug'  => $category->slug,
                    'order' => (int) get_term_meta( $category->term_id, '_vc_category_order', true ),
                ];
            } else {
                $recommended_cuisine_ids = json_decode( $recommended_for, true );
                
                if ( is_array( $recommended_cuisine_ids ) ) {
                    // Verificar se alguma categoria do restaurante está na lista de recomendadas
                    $intersection = array_intersect( $cuisine_ids, $recommended_cuisine_ids );
                    
                    if ( ! empty( $intersection ) ) {
                        $recommended[] = [
                            'id'    => $category->term_id,
                            'name'  => $category->name,
                            'slug'  => $category->slug,
                            'order' => (int) get_term_meta( $category->term_id, '_vc_category_order', true ),
                        ];
                    }
                }
            }
        }

        // Ordenar por ordem
        usort( $recommended, function( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );

        usort( $generic, function( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );

        // Combinar: primeiro as recomendadas, depois as genéricas
        $all_categories = array_merge( $recommended, $generic );

        return new WP_REST_Response( [
            'success'    => true,
            'categories' => $all_categories,
            'count'      => count( $all_categories ),
        ], 200 );
    }

    /**
     * PUT /wp-json/vemcomer/v1/menu-categories/{id}
     * Atualiza uma categoria existente
     */
    public function update_category( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $term_id = (int) $request->get_param( 'id' );
        $body    = $request->get_json_params();

        if ( ! $body ) {
            return new WP_Error(
                'vc_invalid_json',
                __( 'JSON inválido no body da requisição.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Verificar se a categoria existe
        $term = get_term( $term_id, CPT_MenuItem::TAX_CATEGORY );
        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_Error(
                'vc_category_not_found',
                __( 'Categoria não encontrada.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Verificar se a categoria pertence ao restaurante do usuário
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( ! $restaurant_id ) {
            $restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $restaurants ) ) {
                $restaurant_id = $restaurants[0];
            }
        }

        if ( $restaurant_id ) {
            $term_restaurant_id = (int) get_term_meta( $term_id, '_vc_restaurant_id', true );
            $is_catalog = get_term_meta( $term_id, '_vc_is_catalog_category', true );
            
            // Se não for do catálogo e não pertencer ao restaurante, negar acesso
            if ( $is_catalog !== '1' && $term_restaurant_id !== $restaurant_id ) {
                return new WP_Error(
                    'vc_forbidden',
                    __( 'Você não tem permissão para editar esta categoria.', 'vemcomer' ),
                    [ 'status' => 403 ]
                );
            }
        }

        // Atualizar nome se fornecido
        if ( isset( $body['name'] ) && ! empty( $body['name'] ) ) {
            $name = sanitize_text_field( $body['name'] );
            
            // Verificar se outro termo com esse nome já existe no mesmo restaurante
            $existing_terms = get_terms( [
                'taxonomy'   => CPT_MenuItem::TAX_CATEGORY,
                'name'       => $name,
                'hide_empty' => false,
            ] );

            if ( ! is_wp_error( $existing_terms ) && ! empty( $existing_terms ) ) {
                foreach ( $existing_terms as $existing_term ) {
                    if ( $existing_term->term_id === $term_id ) {
                        continue; // Pular o próprio termo
                    }
                    
                    $is_catalog_existing = get_term_meta( $existing_term->term_id, '_vc_is_catalog_category', true );
                    $existing_restaurant_id = (int) get_term_meta( $existing_term->term_id, '_vc_restaurant_id', true );
                    
                    // Se não é do catálogo E pertence ao mesmo restaurante, então já existe
                    if ( $is_catalog_existing !== '1' && $existing_restaurant_id === $restaurant_id ) {
                        return new WP_Error(
                            'vc_category_exists',
                            __( 'Uma categoria com este nome já existe.', 'vemcomer' ),
                            [ 'status' => 400 ]
                        );
                    }
                }
            }

            // Manter o slug único por restaurante
            $base_slug = sanitize_title( $name );
            $unique_slug = $base_slug . '-rest-' . $restaurant_id;

            $result = wp_update_term( $term_id, CPT_MenuItem::TAX_CATEGORY, [
                'name' => $name,
                'slug' => $unique_slug,
            ] );

            if ( is_wp_error( $result ) ) {
                return new WP_Error(
                    'vc_category_update_failed',
                    $result->get_error_message(),
                    [ 'status' => 500 ]
                );
            }
        }

        // Atualizar ordem se fornecida
        if ( isset( $body['order'] ) && is_numeric( $body['order'] ) ) {
            update_term_meta( $term_id, '_vc_category_order', absint( $body['order'] ) );
        }

        // Atualizar imagem se fornecida
        if ( isset( $body['image'] ) ) {
            $image_url = sanitize_text_field( $body['image'] );
            
            if ( empty( $image_url ) ) {
                // Se for vazio, remover imagem
                delete_term_meta( $term_id, '_vc_category_image' );
            } elseif ( strpos( $image_url, 'data:image' ) === 0 ) {
                // Se for data:image, fazer upload
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
                // Se for ID de attachment
                update_term_meta( $term_id, '_vc_category_image', absint( $image_url ) );
            }
        }

        log_event( 'REST menu category updated', [ 'term_id' => $term_id ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'id'      => $term_id,
            'message' => __( 'Categoria atualizada com sucesso!', 'vemcomer' ),
        ], 200 );
    }

    /**
     * DELETE /wp-json/vemcomer/v1/menu-categories/{id}
     * Deleta uma categoria
     */
    public function delete_category( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $term_id = (int) $request->get_param( 'id' );

        // Verificar se a categoria existe
        $term = get_term( $term_id, CPT_MenuItem::TAX_CATEGORY );
        if ( ! $term || is_wp_error( $term ) ) {
            return new WP_Error(
                'vc_category_not_found',
                __( 'Categoria não encontrada.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Verificar se a categoria pertence ao restaurante do usuário
        $user_id = get_current_user_id();
        $restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( ! $restaurant_id ) {
            $restaurants = get_posts( [
                'post_type'      => 'vc_restaurant',
                'author'         => $user_id,
                'posts_per_page' => 1,
                'fields'         => 'ids',
            ] );

            if ( ! empty( $restaurants ) ) {
                $restaurant_id = $restaurants[0];
            }
        }

        if ( $restaurant_id ) {
            $term_restaurant_id = (int) get_term_meta( $term_id, '_vc_restaurant_id', true );
            $is_catalog = get_term_meta( $term_id, '_vc_is_catalog_category', true );
            
            // Se não for do catálogo e não pertencer ao restaurante, negar acesso
            if ( $is_catalog !== '1' && $term_restaurant_id !== $restaurant_id ) {
                return new WP_Error(
                    'vc_forbidden',
                    __( 'Você não tem permissão para deletar esta categoria.', 'vemcomer' ),
                    [ 'status' => 403 ]
                );
            }
        }

        // Verificar se há produtos nesta categoria
        $count = $term->count;
        if ( $count > 0 ) {
            return new WP_Error(
                'vc_category_has_items',
                sprintf(
                    __( 'Não é possível deletar esta categoria. Ela possui %d produto(s) associado(s). Remova os produtos primeiro ou mova-os para outra categoria.', 'vemcomer' ),
                    $count
                ),
                [ 'status' => 400 ]
            );
        }

        // Deletar a categoria
        $result = wp_delete_term( $term_id, CPT_MenuItem::TAX_CATEGORY );

        if ( is_wp_error( $result ) ) {
            return new WP_Error(
                'vc_category_deletion_failed',
                $result->get_error_message(),
                [ 'status' => 500 ]
            );
        }

        if ( ! $result ) {
            return new WP_Error(
                'vc_category_deletion_failed',
                __( 'Não foi possível deletar a categoria.', 'vemcomer' ),
                [ 'status' => 500 ]
            );
        }

        log_event( 'REST menu category deleted', [ 'term_id' => $term_id ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Categoria deletada com sucesso!', 'vemcomer' ),
        ], 200 );
    }
}

