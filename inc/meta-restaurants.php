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
            <td><input type="text" id="vc_restaurant_whatsapp" name="vc_restaurant_whatsapp" class="regular-text" placeholder="55 11 99999-9999" value="<?php echo esc_attr( $values['whatsapp'] ); ?>" /></td>
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
    </table>
    <?php
}

add_action( 'save_post_vc_restaurant', function( $post_id ) {
    // Verificações de segurança
    if ( ! isset( $_POST['vc_restaurant_meta_nonce_field'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['vc_restaurant_meta_nonce_field'], 'vc_restaurant_meta_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Sanitização e salvamento
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['cnpj'], sanitize_text_field( $_POST['vc_restaurant_cnpj'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['whatsapp'], sanitize_text_field( $_POST['vc_restaurant_whatsapp'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['site'], esc_url_raw( $_POST['vc_restaurant_site'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['open_hours'], wp_kses_post( $_POST['vc_restaurant_open_hours'] ?? '' ) );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery'], isset( $_POST['vc_restaurant_delivery'] ) ? '1' : '0' );
    update_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['address'], sanitize_text_field( $_POST['vc_restaurant_address'] ?? '' ) );
});
