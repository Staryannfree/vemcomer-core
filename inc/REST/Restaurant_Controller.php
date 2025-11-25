<?php
/**
 * Restaurant_Controller — REST endpoints públicos para restaurantes e itens
 * Filtros suportados em GET /wp-json/vemcomer/v1/restaurants:
 *   - cuisine (slug da taxonomia vc_cuisine)
 *   - delivery (true|false) — meta _vc_has_delivery
 *   - is_open (true|false) — meta _vc_is_open
 *   - per_page (1..50)
 *   - search (texto)
 *   - orderby (title|date) e order (asc|desc)
 *
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Model\CPT_MenuItem;
use VC\Model\CPT_Restaurant;
use VC\Utils\Availability_Helper;
use VC\Utils\Delivery_Time_Calculator;
use VC\Utils\Schedule_Helper;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Restaurant_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/restaurants', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_restaurants' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_restaurant' ],
            'permission_callback' => '__return_true',
            'args' => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/menu-items', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_menu_items' ],
            'permission_callback' => '__return_true',
            'args' => [ 'id' => [ 'validate_callback' => 'is_numeric' ] ],
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/menu-categories', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_menu_categories' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/schedule', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_schedule' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/is-open', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_is_open' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id'        => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'timestamp' => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/availability', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_availability' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id'       => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'lat'      => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'floatval',
                ],
                'lng'      => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'floatval',
                ],
                'delivery' => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_bool( filter_var( $param, FILTER_VALIDATE_BOOLEAN ) );
                    },
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ],
            ],
        ] );

        register_rest_route( 'vemcomer/v1', '/restaurants/(?P<id>\d+)/estimated-delivery', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_estimated_delivery' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'id'     => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'lat'    => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'floatval',
                ],
                'lng'    => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'floatval',
                ],
                'items'  => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_array( $param );
                    },
                    'sanitize_callback' => function( $param ) {
                       	return array_map( 'absint', (array) $param );
                    },
                ],
            ],
        ] );
    }

    public function get_restaurants( WP_REST_Request $request ) {
        $per_page = (int) $request->get_param( 'per_page' );
        $per_page = $per_page > 0 ? min( $per_page, 50 ) : 10;

        $args = [
            'post_type'      => CPT_Restaurant::SLUG,
            'posts_per_page' => $per_page,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
        ];

        // Filtro por cidade (busca no endereço)
        $city = (string) $request->get_param( 'city' );
        if ( $city ) {
            if ( ! isset( $args['meta_query'] ) ) {
                $args['meta_query'] = [];
            }
            $args['meta_query'][] = [
                'key'     => '_vc_address',
                'value'   => sanitize_text_field( $city ),
                'compare' => 'LIKE',
            ];
        }

        // Busca livre (full-text)
        $search = (string) $request->get_param( 'search' );
        if ( $search ) {
            $args['s'] = $search;
            // Buscar também em itens do cardápio
            // Limitar a 200 resultados para performance
            $menu_items = get_posts( [
                'post_type'      => CPT_MenuItem::SLUG,
                'posts_per_page' => 200,
                'post_status'    => 'publish',
                's'              => $search,
                'fields'         => 'ids',
            ] );
            if ( ! empty( $menu_items ) ) {
                $restaurant_ids_from_items = [];
                foreach ( $menu_items as $item_id ) {
                    $rest_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
                    if ( $rest_id > 0 ) {
                        $restaurant_ids_from_items[] = $rest_id;
                    }
                }
                if ( ! empty( $restaurant_ids_from_items ) ) {
                    $restaurant_ids_from_items = array_unique( $restaurant_ids_from_items );
                    // Combinar busca: restaurantes que correspondem OU têm itens que correspondem
                    // Usar post__in ao invés de meta_query (correção do bug)
                    if ( ! empty( $args['post__in'] ) ) {
                        // Se já tiver um post__in, combina (OR) na mão
                        $args['post__in'] = array_unique( array_merge(
                            (array) $args['post__in'],
                            $restaurant_ids_from_items
                        ) );
                    } else {
                        $args['post__in'] = $restaurant_ids_from_items;
                    }
                }
            }
        }

        // Ordenação
        $orderby = (string) $request->get_param( 'orderby' );
        $order   = strtoupper( (string) $request->get_param( 'order' ) );
        if ( in_array( $orderby, [ 'title', 'date', 'rating' ], true ) ) {
            $args['orderby'] = $orderby;
            if ( 'rating' === $orderby ) {
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_vc_restaurant_rating_avg';
            }
        }
        if ( in_array( $order, [ 'ASC', 'DESC' ], true ) ) { $args['order'] = $order; }

        // Taxonomia: cozinha
        $cuisine = (string) $request->get_param( 'cuisine' );
        if ( $cuisine ) {
            if ( ! isset( $args['tax_query'] ) ) {
                $args['tax_query'] = [];
            }
            $args['tax_query'][] = [
                'taxonomy' => CPT_Restaurant::TAX_CUISINE,
                'field'    => 'slug',
                'terms'    => array_map( 'sanitize_title', array_map( 'trim', explode( ',', $cuisine ) ) ),
            ];
        }

        // Metas: delivery / is_open / min_rating / price_range
        $meta = [];
        
        // Unificar parâmetros delivery e has_delivery (priorizar has_delivery)
        $value_delivery = $request->get_param( 'has_delivery' );
        if ( null === $value_delivery ) {
            $value_delivery = $request->get_param( 'delivery' ); // Fallback para compatibilidade
        }
        if ( null !== $value_delivery ) {
            $meta[] = [
                'key'   => '_vc_has_delivery',
                'value' => filter_var( $value_delivery, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
            ];
        }
        
        $is_open = $request->get_param( 'is_open' );
        if ( null !== $is_open ) {
            $meta[] = [
                'key'   => '_vc_is_open',
                'value' => filter_var( $is_open, FILTER_VALIDATE_BOOLEAN ) ? '1' : '0',
            ];
        }
        
        $is_open_now = $request->get_param( 'is_open_now' );
        if ( null !== $is_open_now && filter_var( $is_open_now, FILTER_VALIDATE_BOOLEAN ) ) {
            // Filtrar por restaurantes abertos agora (usar Schedule_Helper)
            // Isso será filtrado após a query
        }
        
        $min_rating = $request->get_param( 'min_rating' );
        if ( null !== $min_rating && is_numeric( $min_rating ) ) {
            $meta[] = [
                'key'     => '_vc_restaurant_rating_avg',
                'value'   => (float) $min_rating,
                'compare' => '>=',
                'type'    => 'DECIMAL(10,2)',
            ];
        }
        
        $featured = $request->get_param( 'featured' );
        if ( null !== $featured && filter_var( $featured, FILTER_VALIDATE_BOOLEAN ) ) {
            $meta[] = [
                'key'     => '_vc_restaurant_featured',
                'value'   => '1',
                'compare' => '=',
            ];
        }
        
        // Composição segura de meta_query (evitar arrays aninhados)
        if ( ! empty( $meta ) ) {
            if ( ! isset( $args['meta_query'] ) ) {
                $args['meta_query'] = [];
            }
            // Garante que exista uma relation
            if ( ! isset( $args['meta_query']['relation'] ) ) {
                $args['meta_query']['relation'] = 'AND';
            }
            // Adiciona cada condição diretamente (flat, não aninhado)
            foreach ( $meta as $cond ) {
                $args['meta_query'][] = $cond;
            }
        }

        log_event( 'REST restaurants query', [ 'args' => $args ], 'debug' );

        $q = new WP_Query( $args );

        $items = [];
        foreach ( $q->posts as $p ) {
            // Filtrar por is_open_now se solicitado
            if ( isset( $is_open_now ) && filter_var( $is_open_now, FILTER_VALIDATE_BOOLEAN ) ) {
                if ( ! Schedule_Helper::is_open( $p->ID ) ) {
                    continue;
                }
            }

            $terms = wp_get_object_terms( $p->ID, CPT_Restaurant::TAX_CUISINE, [ 'fields' => 'slugs' ] );
            $rating = \VC\Utils\Rating_Helper::get_rating( $p->ID );

            $items[] = [
                'id'          => $p->ID,
                'title'       => get_the_title( $p ),
                'slug'        => $p->post_name, // Adicionar slug para URLs
                'address'     => (string) get_post_meta( $p->ID, '_vc_address', true ),
                'phone'       => (string) get_post_meta( $p->ID, '_vc_phone', true ),
                'has_delivery' => (bool) get_post_meta( $p->ID, '_vc_has_delivery', true ),
                'is_open'     => Schedule_Helper::is_open( $p->ID ),
                'is_featured' => (bool) get_post_meta( $p->ID, '_vc_restaurant_featured', true ),
                'cuisines'    => array_values( array_map( 'strval', (array) $terms ) ),
                'rating'      => [
                    'average' => $rating['avg'],
                    'count'   => $rating['count'],
                ],
            ];
        }

        return new WP_REST_Response( $items, 200 );
    }

    public function get_menu_items( WP_REST_Request $request ) {
        $rid = (int) $request->get_param( 'id' );
        if ( ! $rid ) {
            log_event( 'REST menu items missing id', [], 'warning' );
            return new WP_REST_Response( [], 200 );
        }

        $q = new WP_Query([
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => 100,
            'no_found_rows'  => true,
            'post_status'    => 'publish',
            'meta_query'     => [ [ 'key' => '_vc_restaurant_id', 'value' => (string) $rid ] ],
        ]);

        $items = [];
        foreach ( $q->posts as $p ) {
            $items[] = [
                'id'          => $p->ID,
                'title'       => get_the_title( $p ),
                'price'       => (string) get_post_meta( $p->ID, '_vc_price', true ),
                'prep_time'   => (int) get_post_meta( $p->ID, '_vc_prep_time', true ),
                'is_available'=> (bool) get_post_meta( $p->ID, '_vc_is_available', true ),
            ];
        }

        log_event( 'REST menu items query', [ 'restaurant_id' => $rid, 'count' => count( $items ) ], 'debug' );

        return new WP_REST_Response( $items, 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/restaurants/{id}/schedule
     * Retorna os horários estruturados do restaurante
     */
    public function get_schedule( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $restaurant_id = (int) $request->get_param( 'id' );

        // Verificar se restaurante existe
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
            return new WP_Error(
                'vc_restaurant_not_found',
                __( 'Restaurante não encontrado.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Obter horários estruturados
        $schedule = Schedule_Helper::get_schedule( $restaurant_id );

        // Obter feriados
        $holidays_json = get_post_meta( $restaurant_id, '_vc_restaurant_holidays', true );
        $holidays = $holidays_json ? json_decode( $holidays_json, true ) : [];
        $holidays = is_array( $holidays ) ? $holidays : [];

        // Obter horário legado (para compatibilidade)
        $legacy_hours = get_post_meta( $restaurant_id, 'vc_restaurant_open_hours', true );

        $response = [
            'restaurant_id' => $restaurant_id,
            'schedule'      => $schedule,
            'holidays'      => $holidays,
            'legacy_hours'  => $legacy_hours ?: null,
        ];

        log_event( 'REST schedule fetched', [ 'restaurant_id' => $restaurant_id ], 'debug' );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/restaurants/{id}/is-open
     * Retorna se o restaurante está aberto no momento (ou timestamp especificado)
     */
    public function get_is_open( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $restaurant_id = (int) $request->get_param( 'id' );
        $timestamp     = $request->get_param( 'timestamp' );
        $timestamp     = $timestamp ? (int) $timestamp : null;

        // Verificar se restaurante existe
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
            return new WP_Error(
                'vc_restaurant_not_found',
                __( 'Restaurante não encontrado.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Verificar se está aberto
        $is_open = Schedule_Helper::is_open( $restaurant_id, $timestamp );

        // Obter próximo horário de abertura (se fechado)
        $next_open = null;
        if ( ! $is_open ) {
            $next_open = Schedule_Helper::get_next_open_time( $restaurant_id, $timestamp );
        }

        $response = [
            'restaurant_id' => $restaurant_id,
            'is_open'       => $is_open,
            'timestamp'    => $timestamp ?: time(),
            'next_open'    => $next_open,
        ];

        log_event( 'REST is_open check', [
            'restaurant_id' => $restaurant_id,
            'is_open'       => $is_open,
            'timestamp'     => $timestamp,
        ], 'debug' );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/restaurants/{id}/availability
     * Retorna status de disponibilidade do restaurante
     */
    public function get_availability( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $restaurant_id = (int) $request->get_param( 'id' );
        $lat           = $request->get_param( 'lat' );
        $lng           = $request->get_param( 'lng' );
        $delivery      = $request->get_param( 'delivery' );

        // Verificar se restaurante existe
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
            return new WP_Error(
                'vc_restaurant_not_found',
                __( 'Restaurante não encontrado.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        $context = [];
        if ( null !== $lat && null !== $lng ) {
            $context['lat'] = (float) $lat;
            $context['lng'] = (float) $lng;
        }
        if ( null !== $delivery ) {
            $context['delivery'] = (bool) $delivery;
        }

        $availability = Availability_Helper::check_availability( $restaurant_id, $context );

        $response = [
            'restaurant_id' => $restaurant_id,
            'available'     => $availability['available'],
            'reason'        => $availability['reason'],
            'details'       => $availability['details'],
        ];

        log_event( 'REST availability checked', [
            'restaurant_id' => $restaurant_id,
            'available'     => $availability['available'],
            'reason'        => $availability['reason'],
        ], 'debug' );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/restaurants/{id}/menu-categories
     * Lista categorias do cardápio com itens, ordenadas
     */
    public function get_menu_categories( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $restaurant_id = (int) $request->get_param( 'id' );

        // Verificar se restaurante existe
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
            return new WP_Error(
                'vc_restaurant_not_found',
                __( 'Restaurante não encontrado.', 'vemcomer' ),
                [ 'status' => 404 ]
            );
        }

        // Buscar itens do cardápio
        $items_query = new WP_Query( [
            'post_type'      => CPT_MenuItem::SLUG,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => (string) $restaurant_id,
                ],
            ],
        ] );

        // Agrupar itens por categoria
        $categories_map = [];
        foreach ( $items_query->posts as $item ) {
            $terms = wp_get_object_terms( $item->ID, CPT_MenuItem::TAX_CATEGORY, [ 'fields' => 'ids' ] );
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                // Sem categoria
                if ( ! isset( $categories_map['_uncategorized'] ) ) {
                    $categories_map['_uncategorized'] = [
                        'id'          => 0,
                        'name'        => __( 'Sem categoria', 'vemcomer' ),
                        'slug'        => '_uncategorized',
                        'order'       => 9999,
                        'image'       => null,
                        'items'       => [],
                    ];
                }
                $categories_map['_uncategorized']['items'][] = $this->format_menu_item( $item );
            } else {
                foreach ( $terms as $term_id ) {
                    if ( ! isset( $categories_map[ $term_id ] ) ) {
                        $term = get_term( $term_id );
                       	if ( ! $term || is_wp_error( $term ) ) {
                            continue;
                        }

                        $image_id = (int) get_term_meta( $term_id, '_vc_category_image', true );
                        $categories_map[ $term_id ] = [
                            'id'          => $term_id,
                            'name'        => $term->name,
                            'slug'        => $term->slug,
                            'description' => $term->description,
                            'order'       => (int) get_term_meta( $term_id, '_vc_category_order', true ),
                            'image'       => $image_id > 0 ? wp_get_attachment_image_url( $image_id, 'medium' ) : null,
                            'items'       => [],
                        ];
                    }
                    $categories_map[ $term_id ]['items'][] = $this->format_menu_item( $item );
                }
            }
        }

        // Ordenar por ordem
        usort( $categories_map, function( $a, $b ) {
            return $a['order'] <=> $b['order'];
        } );

        $categories = array_values( $categories_map );

        return new WP_REST_Response( [
            'restaurant_id' => $restaurant_id,
            'categories'   => $categories,
        ], 200 );
    }

    /**
     * Formata item do cardápio para resposta.
     */
    private function format_menu_item( \WP_Post $item ): array {
        return [
            'id'           => $item->ID,
            'title'        => get_the_title( $item ),
            'price'        => (string) get_post_meta( $item->ID, '_vc_price', true ),
            'prep_time'    => (int) get_post_meta( $item->ID, '_vc_prep_time', true ),
            'is_available' => (bool) get_post_meta( $item->ID, '_vc_is_available', true ),
        ];
    }
}
