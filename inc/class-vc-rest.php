<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class VC_REST {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'routes' ] );
    }

    public function routes(): void {
        register_rest_route( 'vemcomer/v1', '/produtos', [
            'methods'  => 'GET',
            'callback' => [ $this, 'get_produtos' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'vemcomer/v1', '/pedidos', [
            'methods'  => 'POST',
            'callback' => [ $this, 'create_pedido' ],
            'permission_callback' => function () { return current_user_can( 'edit_posts' ); },
        ] );
    }

    public function get_produtos( WP_REST_Request $request ) {
        $q = new WP_Query([
            'post_type' => VC_CPT_Produto::SLUG,
            'posts_per_page' => 50,
            'no_found_rows' => true,
        ]);
        $items = [];
        foreach ( $q->posts as $p ) {
            $items[] = [
                'id' => $p->ID,
                'title' => get_the_title( $p ),
                'price' => get_post_meta( $p->ID, '_vc_preco', true ),
            ];
        }
        return rest_ensure_response( $items );
    }

    public function create_pedido( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        $itens = isset( $params['itens'] ) && is_array( $params['itens'] ) ? $params['itens'] : [];
        $total = isset( $params['total'] ) ? vc_sanitize_money( $params['total'] ) : '0';

        $post_id = wp_insert_post([
            'post_type' => VC_CPT_Pedido::SLUG,
            'post_title' => 'Pedido ' . wp_date( 'Y-m-d H:i:s' ),
            'post_status' => 'publish',
        ]);
        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'vc_pedido_error', 'Não foi possível criar o pedido.', [ 'status' => 500 ] );
        }
        update_post_meta( $post_id, '_vc_itens', $itens );
        update_post_meta( $post_id, '_vc_total', $total );

        return rest_ensure_response([
            'id' => $post_id,
            'total' => $total,
        ]);
    }
}
