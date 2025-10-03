<?php
/**
 * Reports — Relatórios administrativos do VemComer
 * @package VemComerCore
 */

namespace VC\Admin;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Reports {
    public function init(): void {
        add_action( 'admin_menu', [ $this, 'menu' ], 30 );
    }

    public function menu(): void {
        add_submenu_page( 'vemcomer-root', __( 'Relatórios', 'vemcomer' ), __( 'Relatórios', 'vemcomer' ), 'manage_options', 'vemcomer-reports', [ $this, 'render' ] );
    }

    private function range(): array {
        $from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : wp_date( 'Y-m-01' );
        $to   = isset( $_GET['to'] )   ? sanitize_text_field( wp_unslash( $_GET['to'] ) )   : wp_date( 'Y-m-d' );
        return [ $from, $to ];
    }

    private function query_orders( string $from, string $to, array $statuses = [] ): array {
        global $wpdb;
        $post_type = 'vc_pedido';
        $statuses_sql = '';
        if ( $statuses ) {
            $in = implode( ',', array_map( fn($s)=> $wpdb->prepare( '%s', $s ), $statuses ) );
            $statuses_sql = "AND p.post_status IN ($in)";
        }
        $sql = $wpdb->prepare(
            "SELECT p.ID, p.post_status, p.post_date, pm.meta_value AS total
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_vc_total'
             WHERE p.post_type = %s AND p.post_date BETWEEN %s AND %s $statuses_sql",
            $post_type,
            $from . ' 00:00:00',
            $to . ' 23:59:59'
        );
        $rows = $wpdb->get_results( $sql, ARRAY_A );
        return $rows ?: [];
    }

    private function sum( array $orders ): float {
        $t = 0.0; foreach ( $orders as $o ) { $t += (float) str_replace( ',', '.', (string) ( $o['total'] ?? 0 ) ); } return $t;
    }

    private function count_by_status( array $orders ): array {
        $out = [];
        foreach ( $orders as $o ) { $st = (string) $o['post_status']; $out[$st] = ($out[$st] ?? 0) + 1; }
        return $out;
    }

    private function counts(): array {
        $restaurants = wp_count_posts( 'vc_restaurant' );
        $menu_items  = wp_count_posts( 'vc_menu_item' );
        return [
            'restaurants' => array_sum( (array) $restaurants ),
            'menu_items'  => array_sum( (array) $menu_items ),
        ];
    }

    public function render(): void {
        [ $from, $to ] = $this->range();
        $orders = $this->query_orders( $from, $to, [] );
        $total  = $this->sum( $orders );
        $bySt   = $this->count_by_status( $orders );
        $cts    = $this->counts();

        echo '<div class="wrap"><h1>' . esc_html__( 'Relatórios', 'vemcomer' ) . '</h1>';
        echo '<form method="get" style="margin: 10px 0 20px 0">';
        echo '<input type="hidden" name="page" value="vemcomer-reports" />';
        echo '<label>De <input type="date" name="from" value="' . esc_attr( $from ) . '"></label> ';
        echo '<label>Até <input type="date" name="to" value="' . esc_attr( $to ) . '"></label> ';
        submit_button( __( 'Filtrar', 'vemcomer' ), 'secondary', '', false );
        echo ' <a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=vc_export_orders&from=' . $from . '&to=' . $to ), 'vc_export_orders' ) ) . '">' . esc_html__( 'Exportar CSV', 'vemcomer' ) . '</a>';
        echo '</form>';

        echo '<div class="vc-report-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">';
        echo '<div class="card" style="border:1px solid #e6e6e6;border-radius:10px;padding:12px"><strong>' . esc_html__( 'Pedidos (total)', 'vemcomer' ) . ':</strong> ' . esc_html( (string) count( $orders ) ) . '</div>';
        echo '<div class="card" style="border:1px solid #e6e6e6;border-radius:10px;padding:12px"><strong>' . esc_html__( 'Faturamento', 'vemcomer' ) . ':</strong> R$ ' . esc_html( number_format( $total, 2, ',', '.' ) ) . '</div>';
        echo '<div class="card" style="border:1px solid #e6e6e6;border-radius:10px;padding:12px"><strong>' . esc_html__( 'Restaurantes', 'vemcomer' ) . ':</strong> ' . esc_html( (string) $cts['restaurants'] ) . '</div>';
        echo '<div class="card" style="border:1px solid #e6e6e6;border-radius:10px;padding:12px"><strong>' . esc_html__( 'Itens do cardápio', 'vemcomer' ) . ':</strong> ' . esc_html( (string) $cts['menu_items'] ) . '</div>';
        echo '</div>';

        echo '<h2 style="margin-top:20px">' . esc_html__( 'Pedidos por status', 'vemcomer' ) . '</h2>';
        echo '<table class="widefat"><thead><tr><th>Status</th><th>Qtde</th></tr></thead><tbody>';
        $status_labels = [
            'vc-pending'    => __( 'Pendente', 'vemcomer' ),
            'vc-paid'       => __( 'Pago', 'vemcomer' ),
            'vc-preparing'  => __( 'Preparando', 'vemcomer' ),
            'vc-delivering' => __( 'Em entrega', 'vemcomer' ),
            'vc-completed'  => __( 'Concluído', 'vemcomer' ),
            'vc-cancelled'  => __( 'Cancelado', 'vemcomer' ),
        ];
        foreach ( $status_labels as $key => $label ) {
            $q = (int) ( $bySt[ $key ] ?? 0 );
            echo '<tr><td>' . esc_html( $label ) . '</td><td>' . esc_html( (string) $q ) . '</td></tr>';
        }
        echo '</tbody></table>';

        echo '<h2 style="margin-top:20px">' . esc_html__( 'Pedidos no período', 'vemcomer' ) . '</h2>';
        echo '<table class="widefat"><thead><tr><th>ID</th><th>Data</th><th>Status</th><th>Total</th></tr></thead><tbody>';
        foreach ( $orders as $o ) {
            echo '<tr>';
            echo '<td><a href="' . esc_url( get_edit_post_link( (int) $o['ID'] ) ) . '">#' . esc_html( (string) $o['ID'] ) . '</a></td>';
            echo '<td>' . esc_html( (string) $o['post_date'] ) . '</td>';
            echo '<td>' . esc_html( (string) $o['post_status'] ) . '</td>';
            $tot = number_format( (float) str_replace( ',', '.', (string) $o['total'] ), 2, ',', '.' );
            echo '<td>R$ ' . esc_html( $tot ) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';

        echo '</div>';
    }
}
