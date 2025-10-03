<?php
/**
 * Export — Exportação CSV de pedidos
 * @package VemComerCore
 */

namespace VC\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Export {
    public function init(): void {
        add_action( 'admin_post_vc_export_orders', [ $this, 'export_orders' ] );
    }

    public function export_orders(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( __( 'Sem permissão.', 'vemcomer' ) ); }
        check_admin_referer( 'vc_export_orders' );

        $from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : wp_date( 'Y-m-01' );
        $to   = isset( $_GET['to'] )   ? sanitize_text_field( wp_unslash( $_GET['to'] ) )   : wp_date( 'Y-m-d' );

        $args = [
            'post_type'      => 'vc_pedido',
            'post_status'    => 'any',
            'date_query'     => [ [ 'after' => $from . ' 00:00:00', 'before' => $to . ' 23:59:59', 'inclusive' => true ] ],
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];
        $q = new \WP_Query( $args );
        $ids = $q->posts ?: [];

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=vemcomer-orders-' . $from . '_to_' . $to . '.csv' );

        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, [ 'id', 'status', 'created_at', 'total', 'items_json' ] );
        foreach ( $ids as $id ) {
            $status = get_post_status( $id );
            $date   = get_post_field( 'post_date', $id );
            $total  = (string) get_post_meta( $id, '_vc_total', true );
            $items  = (array) get_post_meta( $id, '_vc_itens', true );
            fputcsv( $out, [ $id, $status, $date, $total, wp_json_encode( $items ) ] );
        }
        fclose( $out );
        exit;
    }
}
