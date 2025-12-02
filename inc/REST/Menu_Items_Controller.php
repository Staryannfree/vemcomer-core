<?php
/**
 * Menu_Items_Controller — REST endpoints para itens do cardápio
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Menu_Items_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        // GET: Lista itens do cardápio (público)
        register_rest_route( 'vemcomer/v1', '/menu-items', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_menu_items' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'featured'  => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return in_array( $param, [ 'true', 'false', '1', '0', '' ], true );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'restaurant_id' => [
                    'required'          => false,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'required'          => false,
                    'default'           => 10,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0 && $param <= 50;
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // POST: Criar novo item do cardápio (lojista)
        register_rest_route( 'vemcomer/v1', '/menu-items', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_menu_item' ],
            'permission_callback' => [ $this, 'can_manage_menu_items' ],
        ] );

        // PUT: Atualizar item do cardápio existente (lojista)
        register_rest_route( 'vemcomer/v1', '/menu-items/(?P<id>\d+)', [
            'methods'             => 'PUT',
            'callback'            => [ $this, 'update_menu_item' ],
            'permission_callback' => [ $this, 'can_edit_menu_item' ],
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
     * GET /wp-json/vemcomer/v1/menu-items
     * Lista itens do cardápio (pratos do dia se featured=true)
     */
    public function get_menu_items( WP_REST_Request $request ): WP_REST_Response {
        $featured      = $request->get_param( 'featured' );
        $restaurant_id = $request->get_param( 'restaurant_id' );
        $per_page      = (int) $request->get_param( 'per_page' ) ?: 10;

        $args = [
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => $per_page,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $meta_query = [];

        // Filtrar por featured (pratos do dia)
        if ( $featured === 'true' || $featured === '1' ) {
            $meta_query[] = [
                'key'   => '_vc_menu_item_featured',
                'value' => '1',
            ];
        }

        // Filtrar por restaurante
        if ( $restaurant_id > 0 ) {
            $meta_query[] = [
                'key'   => '_vc_restaurant_id',
                'value' => (string) $restaurant_id,
            ];
        }

        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 ) {
                $meta_query['relation'] = 'AND';
            }
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );

        $items = [];
        foreach ( $query->posts as $post ) {
            $restaurant_id = (int) get_post_meta( $post->ID, '_vc_restaurant_id', true );
            $restaurant    = $restaurant_id > 0 ? get_post( $restaurant_id ) : null;
            
            $image_id = get_post_thumbnail_id( $post->ID );
            $price    = get_post_meta( $post->ID, '_vc_price', true );
            
            // Determinar badge (featured, promoção, etc)
            $is_featured = (bool) get_post_meta( $post->ID, '_vc_menu_item_featured', true );
            $badge = '';
            if ( $is_featured ) {
                $badge = 'DESTAQUE';
            } elseif ( $price ) {
                // Verificar se tem desconto (pode ser implementado depois)
                $badge = '';
            }
            
            $items[] = [
                'id'            => $post->ID,
                'name'          => get_the_title( $post ),
                'description'  => wp_trim_words( $post->post_content, 15, '...' ),
                'restaurant'    => $restaurant ? get_the_title( $restaurant ) : '',
                'restaurant_id' => $restaurant_id,
                'restaurant_slug' => $restaurant ? $restaurant->post_name : null, // Adicionar slug para URLs
                'price'         => $price ? 'R$ ' . number_format( (float) $price, 2, ',', '.' ) : '',
                'image'         => $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : null,
                'badge'         => $badge,
                'is_featured'   => $is_featured,
            ];
        }

        return new WP_REST_Response( $items, 200 );
    }

    /**
     * Verifica se o usuário pode gerenciar itens do cardápio
     */
    public function can_manage_menu_items(): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        if ( ! $user instanceof \WP_User ) {
            return false;
        }

        // Admin sempre pode
        if ( current_user_can( 'edit_posts' ) ) {
            return true;
        }

        // Lojista pode gerenciar itens do seu restaurante
        return user_can( $user, 'edit_vc_menu_items' ) || in_array( 'lojista', $user->roles, true );
    }

    /**
     * POST /wp-json/vemcomer/v1/menu-items
     * Cria um novo item do cardápio
     */
    public function create_menu_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $body = $request->get_json_params();
        if ( ! $body ) {
            return new WP_Error(
                'vc_invalid_json',
                __( 'JSON inválido no body da requisição.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Validar título (obrigatório)
        $title = sanitize_text_field( $body['title'] ?? '' );
        if ( empty( $title ) ) {
            return new WP_Error(
                'vc_title_required',
                __( 'O título é obrigatório.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Obter restaurante do usuário logado
        $user_id = get_current_user_id();
        $restaurant_id = 0;

        // Tentar obter restaurante via meta do usuário
        $meta_restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );
        if ( $meta_restaurant_id > 0 ) {
            $restaurant_id = $meta_restaurant_id;
        } else {
            // Tentar obter restaurante via post_author
            $restaurant_query = new WP_Query( [
                'post_type'      => 'vc_restaurant',
                'author'         => $user_id,
                'posts_per_page' => 1,
                'post_status'    => [ 'publish', 'pending', 'draft' ],
            ] );
            if ( $restaurant_query->have_posts() ) {
                $restaurant_id = $restaurant_query->posts[0]->ID;
            }
            wp_reset_postdata();
        }

        if ( $restaurant_id <= 0 ) {
            return new WP_Error(
                'vc_restaurant_not_found',
                __( 'Restaurante não encontrado para este usuário.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Criar o post
        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post( $body['description'] ?? '' ),
            'post_excerpt' => wp_trim_words( wp_kses_post( $body['description'] ?? '' ), 20 ),
            'post_type'    => CPT_MenuItem::SLUG,
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ];

        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Salvar meta fields
        if ( isset( $body['price'] ) ) {
            $price = sanitize_text_field( (string) $body['price'] );
            update_post_meta( $post_id, '_vc_price', $price );
        }

        if ( isset( $body['prep_time'] ) ) {
            $prep_time = absint( $body['prep_time'] );
            update_post_meta( $post_id, '_vc_prep_time', $prep_time );
        }

        $is_available = isset( $body['is_available'] ) && (bool) $body['is_available'];
        update_post_meta( $post_id, '_vc_is_available', $is_available ? '1' : '0' );

        $is_featured = isset( $body['is_featured'] ) && (bool) $body['is_featured'];
        if ( $is_featured ) {
            update_post_meta( $post_id, '_vc_menu_item_featured', '1' );
        }

        // Vincular ao restaurante
        update_post_meta( $post_id, '_vc_restaurant_id', $restaurant_id );
        update_post_meta( $post_id, '_vc_menu_item_restaurant', $restaurant_id );

        // Categoria
        if ( isset( $body['category_id'] ) && is_numeric( $body['category_id'] ) ) {
            $category_id = absint( $body['category_id'] );
            if ( term_exists( $category_id, 'vc_menu_category' ) ) {
                wp_set_object_terms( $post_id, [ $category_id ], 'vc_menu_category', false );
            }
        }

        // Imagem (data:image ou URL)
        if ( isset( $body['image'] ) && ! empty( $body['image'] ) ) {
            $image_url = sanitize_text_field( $body['image'] );
            
            // Se for data:image, fazer upload
            if ( strpos( $image_url, 'data:image' ) === 0 ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $upload = wp_upload_bits( 'menu-item-' . $post_id . '.jpg', null, base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $image_url ) ) );
                if ( ! $upload['error'] ) {
                    $attachment = [
                        'post_mime_type' => 'image/jpeg',
                        'post_title'     => sanitize_file_name( $title ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    set_post_thumbnail( $post_id, $attach_id );
                }
            } elseif ( is_numeric( $image_url ) ) {
                // Se for ID de attachment
                set_post_thumbnail( $post_id, absint( $image_url ) );
            }
        }

        log_event( 'REST menu item created', [ 'post_id' => $post_id, 'restaurant_id' => $restaurant_id ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'id'      => $post_id,
            'message' => __( 'Item do cardápio criado com sucesso!', 'vemcomer' ),
        ], 201 );
    }

    /**
     * Verifica se o usuário pode editar um item específico do cardápio
     */
    public function can_edit_menu_item( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $item_id = (int) $request->get_param( 'id' );
        $item = get_post( $item_id );

        if ( ! $item || CPT_MenuItem::SLUG !== $item->post_type ) {
            return false;
        }

        // Verificar se o usuário é o dono do restaurante associado ao item
        $restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
        if ( $restaurant_id <= 0 ) {
            return false;
        }

        $user_id = get_current_user_id();
        $user_restaurant_id = (int) get_user_meta( $user_id, 'vc_restaurant_id', true );

        if ( $user_restaurant_id === $restaurant_id ) {
            return true;
        }

        // Verificar se o usuário é autor do restaurante
        $restaurant = get_post( $restaurant_id );
        if ( $restaurant && (int) $restaurant->post_author === $user_id ) {
            return true;
        }

        return current_user_can( 'edit_post', $item_id );
    }

    /**
     * PUT /wp-json/vemcomer/v1/menu-items/{id}
     * Atualiza um item do cardápio existente
     */
    public function update_menu_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $item_id = (int) $request->get_param( 'id' );
        $item = get_post( $item_id );

        if ( ! $item || CPT_MenuItem::SLUG !== $item->post_type ) {
            return new WP_Error(
                'vc_menu_item_not_found',
                __( 'Item do cardápio não encontrado.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        $body = $request->get_json_params();
        if ( ! $body ) {
            return new WP_Error(
                'vc_invalid_json',
                __( 'JSON inválido no body da requisição.', 'vemcomer' ),
                [ 'status' => 400 ]
            );
        }

        // Atualizar título se fornecido
        if ( isset( $body['title'] ) && ! empty( trim( $body['title'] ) ) ) {
            wp_update_post( [
                'ID'         => $item_id,
                'post_title' => sanitize_text_field( trim( $body['title'] ) ),
            ] );
        }

        // Atualizar descrição se fornecida
        if ( isset( $body['description'] ) ) {
            wp_update_post( [
                'ID'           => $item_id,
                'post_content' => wp_kses_post( $body['description'] ),
                'post_excerpt' => wp_trim_words( wp_kses_post( $body['description'] ), 20 ),
            ] );
        }

        // Atualizar meta fields
        if ( isset( $body['price'] ) ) {
            $price = sanitize_text_field( (string) $body['price'] );
            update_post_meta( $item_id, '_vc_price', $price );
        }

        if ( isset( $body['prep_time'] ) ) {
            $prep_time = absint( $body['prep_time'] );
            update_post_meta( $item_id, '_vc_prep_time', $prep_time );
        }

        if ( isset( $body['is_available'] ) ) {
            $is_available = (bool) $body['is_available'];
            update_post_meta( $item_id, '_vc_is_available', $is_available ? '1' : '0' );
        }

        if ( isset( $body['is_featured'] ) ) {
            if ( (bool) $body['is_featured'] ) {
                update_post_meta( $item_id, '_vc_menu_item_featured', '1' );
            } else {
                delete_post_meta( $item_id, '_vc_menu_item_featured' );
            }
        }

        // Atualizar categoria
        if ( isset( $body['category_id'] ) && is_numeric( $body['category_id'] ) ) {
            $category_id = absint( $body['category_id'] );
            if ( term_exists( $category_id, 'vc_menu_category' ) ) {
                wp_set_object_terms( $item_id, [ $category_id ], 'vc_menu_category', false );
            }
        }

        // Atualizar imagem se fornecida
        if ( isset( $body['image'] ) && ! empty( $body['image'] ) ) {
            $image_url = sanitize_text_field( $body['image'] );
            
            // Se for data:image, fazer upload
            if ( strpos( $image_url, 'data:image' ) === 0 ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';

                $title = get_the_title( $item_id );
                $upload = wp_upload_bits( 'menu-item-' . $item_id . '.jpg', null, base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $image_url ) ) );
                if ( ! $upload['error'] ) {
                    $attachment = [
                        'post_mime_type' => 'image/jpeg',
                        'post_title'     => sanitize_file_name( $title ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    ];
                    $attach_id = wp_insert_attachment( $attachment, $upload['file'], $item_id );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_data );
                    set_post_thumbnail( $item_id, $attach_id );
                }
            } elseif ( is_numeric( $image_url ) ) {
                // Se for ID de attachment
                set_post_thumbnail( $item_id, absint( $image_url ) );
            }
        }

        log_event( 'REST menu item updated', [ 'post_id' => $item_id ], 'info' );

        return new WP_REST_Response( [
            'success' => true,
            'id'      => $item_id,
        ], 200 );
    }
}

