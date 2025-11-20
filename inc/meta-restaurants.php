<?php
/**
 * Metaboxes e salvamento seguro para Restaurantes
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Chaves de meta
const VC_META_RESTAURANT_FIELDS = [
    'cnpj'        => 'vc_restaurant_cnpj',
    'whatsapp'    => 'vc_restaurant_whatsapp',
    'site'        => 'vc_restaurant_site',
    'open_hours'  => 'vc_restaurant_open_hours',
    'delivery'    => 'vc_restaurant_delivery', // bool
    'address'     => 'vc_restaurant_address',
    'lat'         => 'vc_restaurant_lat',
    'lng'         => 'vc_restaurant_lng',
    'access_url'  => 'vc_restaurant_access_url', // token único para acesso ao painel
];

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'vc_restaurant_info',
        __( 'Informações do restaurante', 'vemcomer' ),
        'vc_render_restaurant_metabox',
        'vc_restaurant',
        'normal',
        'high'
    );
});

function vc_render_restaurant_metabox( $post ) {
    wp_nonce_field( 'vc_restaurant_meta_nonce', 'vc_restaurant_meta_nonce_field' );

    $values = [];
    foreach ( VC_META_RESTAURANT_FIELDS as $key => $meta_key ) {
        $values[ $key ] = get_post_meta( $post->ID, $meta_key, true );
    }
    ?>
    <table class="form-table">
        <tr>
            <th><label for="vc_restaurant_cnpj"><?php echo esc_html__( 'CNPJ', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_cnpj" name="vc_restaurant_cnpj" class="regular-text" value="<?php echo esc_attr( $values['cnpj'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_whatsapp"><?php echo esc_html__( 'WhatsApp', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_whatsapp" name="vc_restaurant_whatsapp" class="regular-text" placeholder="61981872528" value="<?php echo esc_attr( $values['whatsapp'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_site"><?php echo esc_html__( 'Site', 'vemcomer' ); ?></label></th>
            <td><input type="url" id="vc_restaurant_site" name="vc_restaurant_site" class="regular-text" value="<?php echo esc_attr( $values['site'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_open_hours"><?php echo esc_html__( 'Horário de funcionamento', 'vemcomer' ); ?></label></th>
            <td><textarea id="vc_restaurant_open_hours" name="vc_restaurant_open_hours" class="large-text" rows="3"><?php echo esc_textarea( $values['open_hours'] ); ?></textarea></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_delivery"><?php echo esc_html__( 'Entrega (delivery)', 'vemcomer' ); ?></label></th>
            <td><label><input type="checkbox" id="vc_restaurant_delivery" name="vc_restaurant_delivery" value="1" <?php checked( $values['delivery'], '1' ); ?> /> <?php echo esc_html__( 'Oferece delivery', 'vemcomer' ); ?></label></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_address"><?php echo esc_html__( 'Endereço', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_address" name="vc_restaurant_address" class="regular-text" value="<?php echo esc_attr( $values['address'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_lat"><?php echo esc_html__( 'Latitude', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_lat" name="vc_restaurant_lat" class="regular-text" value="<?php echo esc_attr( $values['lat'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_lng"><?php echo esc_html__( 'Longitude', 'vemcomer' ); ?></label></th>
            <td><input type="text" id="vc_restaurant_lng" name="vc_restaurant_lng" class="regular-text" value="<?php echo esc_attr( $values['lng'] ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="vc_restaurant_access_url"><?php echo esc_html__( 'Token de acesso (access_url)', 'vemcomer' ); ?></label></th>
            <td>
                <?php
                $access_url = $values['access_url'];
                if ( $access_url ) {
                    $validation_url = home_url( '/validar-acesso/?token=' . esc_attr( $access_url ) );
                    echo '<code style="display: block; margin-bottom: 5px;">' . esc_html( $access_url ) . '</code>';
                    echo '<a href="' . esc_url( $validation_url ) . '" target="_blank" class="button button-small">' . esc_html__( 'Ver página de validação', 'vemcomer' ) . '</a>';
                } else {
                    echo '<span class="description">' . esc_html__( 'Token será gerado automaticamente quando o restaurante for aprovado.', 'vemcomer' ) . '</span>';
                }
                ?>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post_vc_restaurant', function( $post_id ) {
    // Verificações de segurança
    if ( ! isset( $_POST['vc_restaurant_meta_nonce_field'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['vc_restaurant_meta_nonce_field'], 'vc_restaurant_meta_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $errors = new WP_Error();

    $cnpj_input  = sanitize_text_field( $_POST['vc_restaurant_cnpj'] ?? '' );
    $cnpj_digits = preg_replace( '/\D+/', '', $cnpj_input );

    if ( '' === $cnpj_digits ) {
        $errors->add( 'vc_restaurant_cnpj_empty', __( 'Informe o CNPJ do restaurante.', 'vemcomer' ) );
    } else {
        $use_remote   = (bool) apply_filters( 'vc_restaurant_validate_cnpj_remote', false, $post_id );
        $validation   = \VC\Utils\validate_cnpj( $cnpj_digits, $use_remote );

        if ( is_wp_error( $validation ) ) {
            foreach ( $validation->get_error_messages() as $message ) {
                $errors->add( $validation->get_error_code(), $message );
            }
        } else {
            update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['cnpj'], $validation['normalized'] );
        }
    }

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['whatsapp'], sanitize_text_field( $_POST['vc_restaurant_whatsapp'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['site'], esc_url_raw( $_POST['vc_restaurant_site'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['open_hours'], wp_kses_post( $_POST['vc_restaurant_open_hours'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery'], isset( $_POST['vc_restaurant_delivery'] ) ? '1' : '0' );
    $lat = sanitize_text_field( $_POST['vc_restaurant_lat'] ?? '' );
    $lng = sanitize_text_field( $_POST['vc_restaurant_lng'] ?? '' );

    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['address'], sanitize_text_field( $_POST['vc_restaurant_address'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['lat'], $lat );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['lng'], $lng );

    if ( $errors->has_errors() ) {
        vc_restaurant_store_errors( $errors );
    }
});

function vc_restaurant_store_errors( WP_Error $errors ): void {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return;
    }

    set_transient( 'vc_restaurant_meta_errors_' . $user_id, $errors->get_error_messages(), MINUTE_IN_SECONDS );
}

add_action( 'admin_notices', function() {
    $user_id = get_current_user_id();
    if ( ! $user_id ) {
        return;
    }

    $messages = get_transient( 'vc_restaurant_meta_errors_' . $user_id );
    if ( empty( $messages ) ) {
        return;
    }

    delete_transient( 'vc_restaurant_meta_errors_' . $user_id );

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || ( isset( $screen->post_type ) && 'vc_restaurant' !== $screen->post_type ) ) {
        return;
    }

    echo '<div class="notice notice-error">';
    foreach ( $messages as $message ) {
        echo '<p>' . esc_html( $message ) . '</p>';
    }
    echo '</div>';
});
