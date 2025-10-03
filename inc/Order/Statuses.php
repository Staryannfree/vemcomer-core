<?php
/**
 * Statuses — Post Statuses e metabox de status para Pedidos
 * @package VemComerCore
 */

namespace VC\Order;

use VC_CPT_Pedido;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Statuses {
    public const STATUSES = [
        'vc-pending'    => 'Pendente',
        'vc-paid'       => 'Pago',
        'vc-preparing'  => 'Preparando',
        'vc-delivering' => 'Em entrega',
        'vc-completed'  => 'Concluído',
        'vc-cancelled'  => 'Cancelado',
    ];

    public function init(): void {
        add_action( 'init', [ $this, 'register_statuses' ] );
        add_action( 'add_meta_boxes', [ $this, 'metabox' ] );
        add_action( 'save_post_' . VC_CPT_Pedido::SLUG, [ $this, 'save' ] );
        add_filter( 'display_post_states', [ $this, 'list_states' ], 10, 2 );
    }

    public function register_statuses(): void {
        foreach ( self::STATUSES as $key => $label ) {
            register_post_status( $key, [
                'label'                     => $label,
                'public'                    => true,
                'internal'                  => false,
                'exclude_from_search'       => false,
                'show_in_admin_all_list'    => true,
                'show_in_admin_status_list' => true,
                'label_count'               => _n_noop( "$label <span class=\"count\">(%s)</span>", "$label <span class=\"count\">(%s)</span>", 'vemcomer' ),
            ] );
        }
    }

    public function metabox(): void {
        add_meta_box( 'vc_order_status', __( 'Status do Pedido', 'vemcomer' ), [ $this, 'render' ], VC_CPT_Pedido::SLUG, 'side', 'high' );
    }

    public function render( $post ): void {
        $current = get_post_status( $post );
        wp_nonce_field( 'vc_order_status_nonce', 'vc_order_status_nonce_field' );
        echo '<p><label for="vc_order_status_sel">' . esc_html__( 'Selecionar status', 'vemcomer' ) . '</label></p>';
        echo '<select id="vc_order_status_sel" name="vc_order_status_sel" class="widefat">';
        foreach ( self::STATUSES as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '" ' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    public function save( int $post_id ): void {
        if ( ! isset( $_POST['vc_order_status_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_order_status_nonce_field'], 'vc_order_status_nonce' ) ) { return; }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
        if ( isset( $_POST['vc_order_status_sel'] ) ) {
            $new = sanitize_text_field( wp_unslash( $_POST['vc_order_status_sel'] ) );
            if ( isset( self::STATUSES[ $new ] ) ) {
                global $wpdb;
                $old = get_post_status( $post_id );
                if ( $new !== $old ) {
                    $wpdb->update( $wpdb->posts, [ 'post_status' => $new ], [ 'ID' => $post_id ] );
                    clean_post_cache( $post_id );
                    do_action( 'vemcomer/order_status_changed', $post_id, $new, $old );
                    if ( 'vc-paid' === $new ) {
                        do_action( 'vemcomer/order_paid', $post_id );
                    }
                }
            }
        }
    }

    public function list_states( array $states, $post ): array {
        if ( VC_CPT_Pedido::SLUG !== $post->post_type ) { return $states; }
        $ps = get_post_status( $post );
        if ( isset( self::STATUSES[ $ps ] ) ) {
            $states[ $ps ] = self::STATUSES[ $ps ];
        }
        return $states;
    }
}
