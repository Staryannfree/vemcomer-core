<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class VC_CPT_Pedido {
    const SLUG = 'vc_pedido';

    public function init(): void {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ $this, 'metaboxes' ] );
        add_action( 'save_post_' . self::SLUG, [ $this, 'save_meta' ] );
    }

    public function register_cpt(): void {
        $labels = [ 'name' => 'Pedidos', 'singular_name' => 'Pedido' ];
        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => [ 'title' ],
            'show_in_rest' => true,
        ];
        register_post_type( self::SLUG, $args );
    }

    public function metaboxes(): void {
        add_meta_box( 'vc_pedido_info', 'Informações do Pedido', [ $this, 'render_info' ], self::SLUG, 'normal' );
    }

    public function render_info( $post ): void {
        $itens = (array) get_post_meta( $post->ID, '_vc_itens', true );
        $total = (string) get_post_meta( $post->ID, '_vc_total', true );
        echo '<p><strong>Itens</strong></p>';
        echo '<textarea name="vc_itens" class="widefat" rows="6">' . esc_textarea( wp_json_encode( $itens ) ) . '</textarea>';
        echo '<p><strong>Total</strong></p>';
        echo '<input type="text" name="vc_total" class="widefat" value="' . esc_attr( $total ) . '" />';
        nonce_field( 'vc_pedido_nonce', 'vc_pedido_nonce_field' );
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_pedido_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_pedido_nonce_field'], 'vc_pedido_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

        $itens = isset( $_POST['vc_itens'] ) ? json_decode( wp_unslash( $_POST['vc_itens'] ), true ) : [];
        if ( ! is_array( $itens ) ) { $itens = []; }
        $total = isset( $_POST['vc_total'] ) ? vc_sanitize_money( $_POST['vc_total'] ) : '';
        update_post_meta( $post_id, '_vc_itens', $itens );
        update_post_meta( $post_id, '_vc_total', $total );
    }
}
