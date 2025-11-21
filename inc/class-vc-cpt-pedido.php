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
        $customer_id = (int) get_post_meta( $post->ID, '_vc_customer_id', true );
        $customer_address = (string) get_post_meta( $post->ID, '_vc_customer_address', true );
        $customer_phone = (string) get_post_meta( $post->ID, '_vc_customer_phone', true );

        wp_nonce_field( 'vc_pedido_nonce', 'vc_pedido_nonce_field' );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="vc_customer_id"><?php echo esc_html__( 'Cliente', 'vemcomer' ); ?></label></th>
                <td>
                    <?php
                    $users = get_users( [
                        'number' => 100,
                        'orderby' => 'display_name',
                    ] );
                    ?>
                    <select id="vc_customer_id" name="vc_customer_id" class="regular-text">
                        <option value=""><?php echo esc_html__( '— Selecione —', 'vemcomer' ); ?></option>
                        <?php foreach ( $users as $user ) : ?>
                            <option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $customer_id, $user->ID ); ?>>
                                <?php echo esc_html( $user->display_name . ' (' . $user->user_email . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php echo esc_html__( 'Cliente que fez o pedido.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_customer_address"><?php echo esc_html__( 'Endereço de Entrega', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="text" id="vc_customer_address" name="vc_customer_address" class="regular-text" value="<?php echo esc_attr( $customer_address ); ?>" />
                    <p class="description"><?php echo esc_html__( 'Endereço completo para entrega.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_customer_phone"><?php echo esc_html__( 'Telefone do Cliente', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="text" id="vc_customer_phone" name="vc_customer_phone" class="regular-text" value="<?php echo esc_attr( $customer_phone ); ?>" />
                    <p class="description"><?php echo esc_html__( 'Telefone de contato do cliente.', 'vemcomer' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vc_itens"><?php echo esc_html__( 'Itens', 'vemcomer' ); ?></label></th>
                <td>
                    <textarea id="vc_itens" name="vc_itens" class="widefat" rows="6"><?php echo esc_textarea( wp_json_encode( $itens, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="vc_total"><?php echo esc_html__( 'Total', 'vemcomer' ); ?></label></th>
                <td>
                    <input type="text" id="vc_total" name="vc_total" class="regular-text" value="<?php echo esc_attr( $total ); ?>" />
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta( int $post_id ): void {
        if ( ! isset( $_POST['vc_pedido_nonce_field'] ) || ! wp_verify_nonce( $_POST['vc_pedido_nonce_field'], 'vc_pedido_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

        // Salvar itens e total (campos existentes)
        $itens = isset( $_POST['vc_itens'] ) ? json_decode( wp_unslash( $_POST['vc_itens'] ), true ) : [];
        if ( ! is_array( $itens ) ) { $itens = []; }
        $total = isset( $_POST['vc_total'] ) ? vc_sanitize_money( $_POST['vc_total'] ) : '';
        update_post_meta( $post_id, '_vc_itens', $itens );
        update_post_meta( $post_id, '_vc_total', $total );

        // Salvar novos campos de cliente
        $customer_id = isset( $_POST['vc_customer_id'] ) ? (int) $_POST['vc_customer_id'] : 0;
        if ( $customer_id > 0 ) {
            update_post_meta( $post_id, '_vc_customer_id', $customer_id );
        } else {
            delete_post_meta( $post_id, '_vc_customer_id' );
        }

        $customer_address = isset( $_POST['vc_customer_address'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_customer_address'] ) ) : '';
        if ( ! empty( $customer_address ) ) {
            update_post_meta( $post_id, '_vc_customer_address', $customer_address );
        } else {
            delete_post_meta( $post_id, '_vc_customer_address' );
        }

        $customer_phone = isset( $_POST['vc_customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['vc_customer_phone'] ) ) : '';
        if ( ! empty( $customer_phone ) ) {
            update_post_meta( $post_id, '_vc_customer_phone', $customer_phone );
        } else {
            delete_post_meta( $post_id, '_vc_customer_phone' );
        }
    }
}
