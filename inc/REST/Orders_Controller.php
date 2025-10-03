<?php
/**
 * Orders_Controller — Status e resumo de pedido
 * @route GET /wp-json/vemcomer/v1/orders/{id}
 * @package VemComerCore
 */

namespace VC\REST;

use WP_REST_Request;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orders_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/orders/(?P<id>\\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_order' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_order( WP_REST_Request $req ) {
        $id = (int) $req['id'];
        if ( $id <= 0 ) {
            return new WP_Error( 'vc_bad_id', __( 'ID inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }
        $post = get_post( $id );
        if ( ! $post || 'vc_pedido' !== $post->post_type ) {
            return new WP_Error( 'vc_not_found', __( 'Pedido não encontrado.', 'vemcomer' ), [ 'status' => 404 ] );
        }
        $status = get_post_status( $post );
        $map = [
            'vc-pending'    => __( 'Pendente', 'vemcomer' ),
            'vc-paid'       => __( 'Pago', 'vemcomer' ),
            'vc-preparing'  => __( 'Preparando', 'vemcomer' ),
            'vc-delivering' => __( 'Em entrega', 'vemcomer' ),
            'vc-completed'  => __( 'Concluído', 'vemcomer' ),
            'vc-cancelled'  => __( 'Cancelado', 'vemcomer' ),
        ];
        return rest_ensure_response([
            'id'           => $post->ID,
            'status'       => $status,
            'status_label' => $map[ $status ] ?? $status,
            'total'        => (string) get_post_meta( $post->ID, '_vc_total', true ),
            'itens'        => (array) get_post_meta( $post->ID, '_vc_itens', true ),
        ]);
    }
}
