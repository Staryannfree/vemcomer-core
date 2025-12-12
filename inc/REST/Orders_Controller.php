<?php
/**
 * Orders_Controller — Status e resumo de pedido
 * @route GET /wp-json/vemcomer/v1/orders/{id}
 * @package VemComerCore
 */

namespace VC\REST;

use VC_CPT_Pedido;
use VC\Model\CPT_Restaurant;
use VC\Order\Order_Validator;
use VC\Utils\Schedule_Helper;
use VC\WhatsApp\Message_Formatter;
use WP_Error;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orders_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        // GET: Lista pedidos do usuário autenticado
        register_rest_route( 'vemcomer/v1', '/orders', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_orders' ],
            'permission_callback' => [ $this, 'check_authenticated' ],
            'args'                => [
                'status'      => [
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'data_inicio' => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || strtotime( $param ) !== false;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'data_fim'    => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return empty( $param ) || strtotime( $param ) !== false;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'restaurant_id' => [
                    'required'          => false,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'per_page'    => [
                    'required'          => false,
                    'default'           => 10,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0 && $param <= 50;
                    },
                    'sanitize_callback' => 'absint',
                ],
                'page'        => [
                    'required'          => false,
                    'default'           => 1,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // GET: Detalhes de um pedido específico
        register_rest_route( 'vemcomer/v1', '/orders/(?P<id>\\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_order' ],
            'permission_callback' => [ $this, 'check_order_access' ],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        // POST: Preparar mensagem WhatsApp
        register_rest_route( 'vemcomer/v1', '/orders/prepare-whatsapp', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'prepare_whatsapp' ],
            'permission_callback' => [ $this, 'check_authenticated' ],
        ] );

        // POST: Validar pedido
        register_rest_route( 'vemcomer/v1', '/orders/validate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'validate_order' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /**
     * Verifica se o usuário está autenticado.
     */
    public function check_authenticated(): bool {
        return is_user_logged_in();
    }

    /**
     * Verifica se o usuário pode acessar o pedido (deve ser o dono ou admin).
     */
    public function check_order_access( WP_REST_Request $request ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Admins podem ver todos os pedidos
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $order_id = (int) $request->get_param( 'id' );
        $user_id  = get_current_user_id();

        // Verificar se o pedido pertence ao usuário
        $customer_id = (int) get_post_meta( $order_id, '_vc_customer_id', true );
        return $customer_id === $user_id;
    }

    /**
     * GET /wp-json/vemcomer/v1/orders
     * Lista pedidos do usuário autenticado com filtros
     */
    public function get_orders( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return new WP_Error(
                'vc_unauthorized',
                __( 'Você precisa estar autenticado.', 'vemcomer' ),
                [ 'status' => 401 ]
            );
        }

        $status       = $request->get_param( 'status' );
        $data_inicio  = $request->get_param( 'data_inicio' );
        $data_fim     = $request->get_param( 'data_fim' );
        $restaurant_id = $request->get_param( 'restaurant_id' );
        $per_page     = (int) $request->get_param( 'per_page' );
        $page         = (int) $request->get_param( 'page' );

        // Construir query
        $args = [
            'post_type'      => VC_CPT_Pedido::SLUG,
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'post_status'    => 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [],
        ];

        // Filtrar por cliente (apenas pedidos do usuário, exceto se for admin)
        if ( ! current_user_can( 'manage_options' ) ) {
            $args['meta_query'][] = [
                'key'   => '_vc_customer_id',
                'value' => (string) $user_id,
            ];
        }

        // Filtrar por status
        if ( ! empty( $status ) ) {
            $args['post_status'] = sanitize_text_field( $status );
        }

        // Filtrar por restaurante (se houver meta _vc_restaurant_id)
        if ( ! empty( $restaurant_id ) ) {
            $args['meta_query'][] = [
                'key'   => '_vc_restaurant_id',
                'value' => (string) $restaurant_id,
            ];
        }

        // Filtrar por data
        if ( ! empty( $data_inicio ) || ! empty( $data_fim ) ) {
            $date_query = [];
            if ( ! empty( $data_inicio ) ) {
                $date_query['after'] = $data_inicio;
            }
            if ( ! empty( $data_fim ) ) {
                $date_query['before'] = $data_fim;
            }
            if ( ! empty( $date_query ) ) {
                $args['date_query'] = [ $date_query ];
            }
        }

        $query = new WP_Query( $args );

        $orders = [];
        foreach ( $query->posts as $post ) {
            $orders[] = $this->format_order_response( $post );
        }

        $response = [
            'orders'      => $orders,
            'total'       => (int) $query->found_posts,
            'per_page'    => $per_page,
            'current_page' => $page,
            'total_pages' => (int) $query->max_num_pages,
        ];

        log_event( 'REST orders list fetched', [
            'user_id' => $user_id,
            'count'   => count( $orders ),
        ], 'debug' );

        return new WP_REST_Response( $response, 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/orders/{id}
     * Detalhes de um pedido específico
     */
    public function get_order( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id = (int) $request->get_param( 'id' );
        if ( $id <= 0 ) {
            log_event( 'REST order lookup with invalid ID', [ 'id' => $id ], 'warning' );
            return new WP_Error( 'vc_bad_id', __( 'ID inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $post = get_post( $id );
        if ( ! $post || VC_CPT_Pedido::SLUG !== $post->post_type ) {
            log_event( 'REST order not found', [ 'id' => $id ], 'warning' );
            return new WP_Error( 'vc_not_found', __( 'Pedido não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        $response = $this->format_order_response( $post, true );

        log_event( 'REST order fetched', [ 'id' => $post->ID, 'status' => $response['status'] ], 'debug' );
        return new WP_REST_Response( $response, 200 );
    }

    /**
     * Formata resposta do pedido.
     *
     * @param \WP_Post $post Post do pedido
     * @param bool     $include_details Se deve incluir detalhes completos
     * @return array
     */
    private function format_order_response( \WP_Post $post, bool $include_details = false ): array {
        $status = get_post_status( $post );
        $status_map = [
            'vc-pending'    => __( 'Pendente', 'vemcomer' ),
            'vc-paid'       => __( 'Pago', 'vemcomer' ),
            'vc-preparing'  => __( 'Preparando', 'vemcomer' ),
            'vc-delivering' => __( 'Em entrega', 'vemcomer' ),
            'vc-completed'  => __( 'Concluído', 'vemcomer' ),
            'vc-cancelled'  => __( 'Cancelado', 'vemcomer' ),
        ];

        $customer_id = (int) get_post_meta( $post->ID, '_vc_customer_id', true );
        $customer    = $customer_id > 0 ? get_userdata( $customer_id ) : null;
        $restaurant_id = (int) get_post_meta( $post->ID, '_vc_restaurant_id', true );

        $response = [
            'id'           => $post->ID,
            'status'       => $status,
            'status_label' => $status_map[ $status ] ?? $status,
            'total'        => (string) get_post_meta( $post->ID, '_vc_total', true ),
            'itens'        => (array) get_post_meta( $post->ID, '_vc_itens', true ),
            'created_at'   => get_post_time( 'c', false, $post ),
        ];

        // Incluir detalhes completos se solicitado
        if ( $include_details ) {
            $response['customer'] = $customer ? [
                'id'      => $customer->ID,
                'name'    => $customer->display_name,
                'email'   => $customer->user_email,
            ] : null;
            $response['customer_address'] = (string) get_post_meta( $post->ID, '_vc_customer_address', true );
            $response['customer_phone']  = (string) get_post_meta( $post->ID, '_vc_customer_phone', true );
            if ( $restaurant_id > 0 ) {
                $restaurant = get_post( $restaurant_id );
                $response['restaurant'] = $restaurant ? [
                    'id'    => $restaurant->ID,
                    'title' => get_the_title( $restaurant ),
                ] : null;
            }
        }

        return $response;
    }

    /**
     * POST /wp-json/vemcomer/v1/orders/prepare-whatsapp
     * Prepara mensagem WhatsApp formatada para o pedido
     */
    public function prepare_whatsapp( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $restaurant_id = (int) $request->get_param( 'restaurant_id' );
        $items         = $request->get_param( 'items' );
        $customer_data = $request->get_param( 'customer' );
        $fulfillment   = $request->get_param( 'fulfillment' );

        if ( ! $restaurant_id || ! is_array( $items ) || empty( $items ) ) {
            return new WP_Error( 'vc_invalid_params', __( 'Parâmetros inválidos.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        // Verificar se restaurante existe e está aberto
        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant || CPT_Restaurant::SLUG !== $restaurant->post_type ) {
            return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        if ( ! Schedule_Helper::is_open( $restaurant_id ) ) {
            return new WP_Error( 'vc_restaurant_closed', __( 'Restaurante está fechado no momento.', 'vemcomer' ), [ 'status' => 403 ] );
        }

        // Verificar se o restaurante está ativo (status de ativação)
        $status = \VC\Services\Restaurant_Status_Service::get_status_for_restaurant( $restaurant_id );
        if ( ! $status['active'] ) {
            return new WP_Error(
                'restaurant_not_active',
                __( 'Este restaurante ainda não está pronto para receber pedidos.', 'vemcomer' ),
                [
                    'status' => 400,
                    'activation_progress' => $status['checks'],
                ]
            );
        }

        // Calcular totais
        $subtotal = 0.0;
        $clean_items = [];
        foreach ( $items as $item ) {
            $item_price = (float) ( $item['price'] ?? 0 );
            $quantity = (int) ( $item['quantity'] ?? 1 );
            $subtotal += $item_price * $quantity;

            $clean_items[] = [
                'name'      => sanitize_text_field( $item['name'] ?? '' ),
                'quantity'  => $quantity,
                'price'     => $item_price,
                'modifiers' => $item['modifiers'] ?? [],
            ];
        }

        $delivery_fee = (float) ( $fulfillment['fee'] ?? 0 );
        $total = $subtotal + $delivery_fee;

        // Preparar dados
        $order_data = [
            'items'           => $clean_items,
            'subtotal'        => $subtotal,
            'delivery_fee'    => $delivery_fee,
            'total'           => $total,
            'fulfillment_type' => $fulfillment['type'] ?? 'delivery',
        ];

        $customer_name = sanitize_text_field( $customer_data['name'] ?? '' );
        $customer_phone = sanitize_text_field( $customer_data['phone'] ?? '' );
        $customer_address = sanitize_text_field( $customer_data['address'] ?? '' );

        $restaurant_whatsapp = get_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', true );

        if ( empty( $restaurant_whatsapp ) ) {
            return new WP_Error( 'vc_no_whatsapp', __( 'Restaurante não possui WhatsApp configurado.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        // Formatar mensagem
        $message = Message_Formatter::format_order(
            $order_data,
            [
                'name'    => $customer_name,
                'phone'   => $customer_phone,
                'address' => $customer_address,
            ],
            [
                'id'   => $restaurant_id,
                'name' => get_the_title( $restaurant ),
            ]
        );

        // Gerar URL
        $whatsapp_url = Message_Formatter::generate_whatsapp_url( $restaurant_whatsapp, $message );

        return new WP_REST_Response( [
            'message'      => $message,
            'whatsapp_url' => $whatsapp_url,
        ], 200 );
    }

    /**
     * POST /wp-json/vemcomer/v1/orders/validate
     * Valida pedido antes de gerar mensagem WhatsApp
     */
    public function validate_order( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $order_data = $request->get_json_params();

        if ( ! is_array( $order_data ) ) {
            return new WP_Error( 'vc_invalid_params', __( 'Dados do pedido inválidos.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $result = Order_Validator::validate( $order_data );

        return new WP_REST_Response( $result, $result['valid'] ? 200 : 400 );
    }
}
