<?php
/**
 * Shipping_Controller — Endpoint de cotação de frete
 * @route GET /wp-json/vemcomer/v1/shipping/quote?restaurant_id=ID&subtotal=99.90
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Frontend\Shipping;
use WP_Error;
use WP_REST_Request;
use function VC\Logging\log_event;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Shipping_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/shipping/quote', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'quote' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'restaurant_id' => [
                    'required'          => true,
                    'validate_callback' => 'is_numeric',
                    'sanitize_callback' => 'absint',
                ],
                'subtotal'      => [
                    'required' => true,
                ],
                'lat'           => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'floatval',
                ],
                'lng'           => [
                    'required'          => false,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param );
                    },
                    'sanitize_callback' => 'floatval',
                ],
                'address'       => [
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'neighborhood'  => [
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    public function quote( WP_REST_Request $req ) {
        $rid = (int) $req->get_param( 'restaurant_id' );
        $sub = (float) str_replace( ',', '.', (string) $req->get_param( 'subtotal' ) );
        if ( $rid <= 0 || $sub < 0 ) {
            log_event( 'REST shipping quote invalid params', [ 'restaurant_id' => $rid, 'subtotal' => $sub ], 'warning' );
            return new WP_Error( 'vc_bad_params', __( 'Parâmetros inválidos.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        // Verificar se restaurante existe e está publicado
        $restaurant = get_post( $rid );
        if ( ! $restaurant || 'vc_restaurant' !== $restaurant->post_type || 'publish' !== $restaurant->post_status ) {
            return new WP_Error( 'vc_restaurant_not_found', __( 'Restaurante não encontrado ou não disponível.', 'vemcomer' ), [ 'status' => 404 ] );
        }

        // Verificar se restaurante está aberto (usando meta _vc_is_open por enquanto)
        $is_open = (bool) get_post_meta( $rid, '_vc_is_open', true );
        if ( ! $is_open ) {
            return rest_ensure_response( [
                'restaurant_id' => $rid,
                'subtotal'      => $sub,
                'methods'       => [],
                'is_open'       => false,
                'message'       => __( 'Restaurante está fechado no momento.', 'vemcomer' ),
            ] );
        }

        // Obter coordenadas e endereço do cliente (se fornecidos)
        $customer_lat = $req->get_param( 'lat' );
        $customer_lng = $req->get_param( 'lng' );
        $customer_address = $req->get_param( 'address' );
        $customer_neighborhood = $req->get_param( 'neighborhood' );

        // Obter coordenadas do restaurante
        $restaurant_lat = (float) get_post_meta( $rid, 'vc_restaurant_lat', true );
        $restaurant_lng = (float) get_post_meta( $rid, 'vc_restaurant_lng', true );

        // Calcular distância se coordenadas disponíveis
        $distance = null;
        if ( $customer_lat && $customer_lng && $restaurant_lat && $restaurant_lng ) {
            if ( function_exists( 'vc_haversine_km' ) ) {
                $distance = vc_haversine_km( $restaurant_lat, $restaurant_lng, (float) $customer_lat, (float) $customer_lng );
            }
        }

        // Verificar raio máximo
        $radius = (float) str_replace( ',', '.', (string) get_post_meta( $rid, '_vc_delivery_radius', true ) );
        $within_radius = true;
        if ( $radius > 0 && $distance !== null ) {
            $within_radius = $distance <= $radius;
        }

        // Preparar dados do pedido para cálculo de frete
        $order = [
            'restaurant_id'      => $rid,
            'subtotal'           => $sub,
            'customer_lat'       => $customer_lat ? (float) $customer_lat : null,
            'customer_lng'       => $customer_lng ? (float) $customer_lng : null,
            'customer_address'   => $customer_address,
            'customer_neighborhood' => $customer_neighborhood,
        ];

        // Obter cotações de todos os métodos de fulfillment
        $quote = Shipping::quote( $rid, $sub, $order );

        // Adicionar informações adicionais à resposta
        $response = [
            'restaurant_id' => $rid,
            'subtotal'      => $sub,
            'methods'       => $quote['methods'] ?? [],
            'is_open'       => true,
            'distance'      => $distance !== null ? round( $distance, 2 ) : null,
            'within_radius' => $within_radius,
            'radius'        => $radius > 0 ? $radius : null,
        ];

        // Adicionar informações de cada método (especialmente DistanceBasedDelivery)
        foreach ( $response['methods'] as &$method ) {
            if ( isset( $method['details'] ) && is_array( $method['details'] ) ) {
                // Se o método retornou distância, usar ela
                if ( isset( $method['details']['distance'] ) ) {
                    $response['distance'] = $method['details']['distance'];
                }
                // Se o método retornou erro, incluir na resposta
                if ( isset( $method['details']['error'] ) && $method['details']['error'] ) {
                    $response['error'] = $method['details']['message'] ?? __( 'Erro ao calcular frete.', 'vemcomer' );
                }
            }
        }
        unset( $method );

        if ( empty( $response['methods'] ) ) {
            $response['message'] = __( 'Nenhum método de fulfillment disponível para este restaurante.', 'vemcomer' );
        }

        log_event( 'REST shipping quote', [
            'restaurant_id' => $rid,
            'subtotal'      => $sub,
            'distance'      => $distance,
            'within_radius' => $within_radius,
            'methods_count' => count( $response['methods'] ),
        ], 'debug' );

        return rest_ensure_response( $response );
    }
}
