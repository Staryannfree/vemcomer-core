<?php
/**
 * WooCommerce — Integração básica (espelho de pedido e sync de status)
 * @package VemComerCore
 */

namespace VC\Integration;

use VC_CPT_Pedido;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class WooCommerce {
    public function init(): void {
        // Sincroniza mudança de status do WooCommerce para VC
        add_action( 'woocommerce_order_status_changed', [ $this, 'sync_status' ], 10, 4 );
        // Garante espelho VC quando um pedido é criado no WC e ainda não há vínculo
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'maybe_mirror_order' ], 10, 3 );
    }

    /**
     * Mapeia status do Woo para status VC
     */
    private function map_wc_to_vc( string $wc_status ): string {
        return match ( $wc_status ) {
            'processing' => 'vc-paid',
            'completed'  => 'vc-completed',
            'cancelled'  => 'vc-cancelled',
            'on-hold'    => 'vc-pending',
            default      => 'vc-pending',
        };
    }

    public function sync_status( int $order_id, string $old_status, string $new_status, $order ): void {
        $vc_id = (int) get_post_meta( $order_id, '_vc_pedido_id', true );
        if ( ! $vc_id ) {
            // Sem vínculo? tenta criar um espelho
            $vc_id = $this->create_mirror_from_wc( $order_id );
        }
        if ( $vc_id ) {
            $this->set_vc_status( $vc_id, $this->map_wc_to_vc( $new_status ) );
        }
    }

    public function maybe_mirror_order( int $order_id, array $posted_data, $order ): void {
        if ( (int) get_post_meta( $order_id, '_vc_pedido_id', true ) ) { return; }
        $this->create_mirror_from_wc( $order_id );
    }

    private function create_mirror_from_wc( int $order_id ): int {
        if ( ! function_exists( 'wc_get_order' ) ) { return 0; }
        $order = wc_get_order( $order_id );
        if ( ! $order ) { return 0; }

        $items = [];
        foreach ( $order->get_items() as $item ) {
            $items[] = [
                'product_id' => (int) $item->get_product_id(),
                'name'       => (string) $item->get_name(),
                'qty'        => (int) $item->get_quantity(),
                'total'      => (string) $item->get_total(),
            ];
        }

        $vc_id = wp_insert_post([
            'post_type'   => VC_CPT_Pedido::SLUG,
            'post_title'  => 'WC #' . $order_id,
            'post_status' => 'vc-pending',
        ]);
        if ( is_wp_error( $vc_id ) ) { return 0; }

        update_post_meta( $vc_id, '_vc_itens', $items );
        update_post_meta( $vc_id, '_vc_total', (string) $order->get_total() );

        // vínculo cruzado
        update_post_meta( $order_id, '_vc_pedido_id', $vc_id );
        update_post_meta( $vc_id, '_vc_linked_wc_order', $order_id );

        /** Gatilho para Automator */
        do_action( 'vemcomer/order_status_changed', $vc_id, 'vc-pending', 'new' );

        return (int) $vc_id;
    }

    private function set_vc_status( int $vc_id, string $status ): void {
        global $wpdb;
        $old = get_post_status( $vc_id );
        if ( $old === $status ) { return; }
        $wpdb->update( $wpdb->posts, [ 'post_status' => $status ], [ 'ID' => $vc_id ] );
        clean_post_cache( $vc_id );
        do_action( 'vemcomer/order_status_changed', $vc_id, $status, $old );
        if ( 'vc-paid' === $status ) {
            do_action( 'vemcomer/order_paid', $vc_id );
        }
    }
}
