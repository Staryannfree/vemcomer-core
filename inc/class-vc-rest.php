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
        $params        = $request->get_json_params();
        $items_payload = isset( $params['itens'] ) && is_array( $params['itens'] ) ? $params['itens'] : [];
        if ( empty( $items_payload ) ) {
            return new WP_Error( 'vc_empty_cart', __( 'Adicione itens antes de finalizar o pedido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $restaurant_from_payload = isset( $params['restaurant_id'] ) ? (int) $params['restaurant_id'] : 0;
        $clean_items             = [];
        $restaurant_id           = 0;
        $subtotal                = 0.0;

        foreach ( $items_payload as $item ) {
            $product_id = isset( $item['produto_id'] ) ? (int) $item['produto_id'] : 0;
            $qty        = isset( $item['qtd'] ) ? (int) $item['qtd'] : 0;
            if ( $product_id <= 0 || $qty <= 0 ) {
                return new WP_Error( 'vc_bad_item', __( 'Item inválido.', 'vemcomer' ), [ 'status' => 400 ] );
            }
            $product_restaurant = (int) get_post_meta( $product_id, '_vc_restaurant_id', true );
            if ( ! $product_restaurant ) {
                return new WP_Error( 'vc_item_without_restaurant', __( 'Item sem restaurante vinculado.', 'vemcomer' ), [ 'status' => 400 ] );
            }
            if ( ! $restaurant_id ) {
                $restaurant_id = $product_restaurant;
            }
            if ( $restaurant_id !== $product_restaurant ) {
                return new WP_Error( 'vc_multi_restaurant', __( 'O checkout aceita apenas itens de um único restaurante.', 'vemcomer' ), [ 'status' => 400 ] );
            }
            if ( $restaurant_from_payload && $restaurant_from_payload !== $product_restaurant ) {
                return new WP_Error( 'vc_restaurant_mismatch', __( 'O restaurante informado não corresponde aos itens.', 'vemcomer' ), [ 'status' => 400 ] );
            }
            $price_raw = get_post_meta( $product_id, '_vc_price', true );
            $price     = (float) str_replace( ',', '.', (string) $price_raw );
            $line      = $price * $qty;
            $subtotal += $line;

            $clean_items[] = [
                'produto_id' => $product_id,
                'qtd'        => $qty,
                'price'      => number_format( $price, 2, '.', '' ),
            ];
        }

        if ( $restaurant_id <= 0 ) {
            return new WP_Error( 'vc_missing_restaurant', __( 'Restaurante inválido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $order_context = [
            'restaurant_id' => $restaurant_id,
            'subtotal'      => $subtotal,
            'items'         => $clean_items,
        ];

        $fulfillment_payload = isset( $params['fulfillment'] ) && is_array( $params['fulfillment'] ) ? $params['fulfillment'] : [];
        $method_id           = isset( $fulfillment_payload['method'] ) ? sanitize_key( $fulfillment_payload['method'] ) : '';
        if ( ! $method_id ) {
            return new WP_Error( 'vc_missing_fulfillment', __( 'Selecione um método de entrega.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $quotes   = \VC\Checkout\FulfillmentRegistry::get_quotes( $order_context );
        $selected = null;
        foreach ( $quotes as $quote ) {
            if ( $quote['id'] === $method_id ) {
                $selected = $quote;
                break;
            }
        }
        if ( ! $selected ) {
            return new WP_Error( 'vc_invalid_fulfillment', __( 'Método de entrega inválido para este pedido.', 'vemcomer' ), [ 'status' => 400 ] );
        }

        $ship_total  = (float) ( $selected['amount'] ?? 0.0 );
        $ship_label  = (string) ( $selected['label'] ?? '' );
        $ship_eta    = $selected['eta'] ?? '';
        $total_value = $subtotal + $ship_total;

        $post_id = wp_insert_post([
            'post_type'   => VC_CPT_Pedido::SLUG,
            'post_title'  => 'Pedido ' . wp_date( 'Y-m-d H:i:s' ),
            'post_status' => 'publish',
        ]);
        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'vc_pedido_error', __( 'Não foi possível criar o pedido.', 'vemcomer' ), [ 'status' => 500 ] );
        }

        $subtotal_str = vc_sanitize_money( number_format( $subtotal, 2, ',', '' ) );
        $ship_str     = vc_sanitize_money( number_format( $ship_total, 2, ',', '' ) );
        $total_str    = vc_sanitize_money( number_format( $total_value, 2, ',', '' ) );

        update_post_meta( $post_id, '_vc_itens', $clean_items );
        update_post_meta( $post_id, '_vc_subtotal', $subtotal_str );
        update_post_meta( $post_id, '_vc_ship_total', $ship_str );
        update_post_meta( $post_id, '_vc_total', $total_str );
        update_post_meta( $post_id, '_vc_ship_method', $method_id );
        update_post_meta( $post_id, '_vc_ship_label', $ship_label );
        update_post_meta( $post_id, '_vc_ship_eta', $ship_eta );
        update_post_meta( $post_id, '_vc_restaurant_id', $restaurant_id );

        return rest_ensure_response([
            'id'             => $post_id,
            'restaurant_id'  => $restaurant_id,
            'subtotal'       => $subtotal_str,
            'ship_total'     => $ship_str,
            'total'          => $total_str,
            'fulfillment'    => [
                'method' => $method_id,
                'label'  => $ship_label,
                'eta'    => $ship_eta,
            ],
        ]);
    }
}
