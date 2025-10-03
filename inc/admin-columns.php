<?php
/**
 * Colunas de admin para o CPT Restaurantes
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_filter( 'manage_vc_restaurant_posts_columns', function( $columns ) {
    $new = [];
    // Mantém checkbox e título
    foreach ( $columns as $key => $label ) {
        if ( in_array( $key, [ 'cb', 'title' ], true ) ) {
            $new[ $key ] = $label;
        }
    }

    $new['vc_cuisine']   = __( 'Cozinha', 'vemcomer' );
    $new['vc_location']  = __( 'Bairro', 'vemcomer' );
    $new['delivery']     = __( 'Delivery', 'vemcomer' );
    $new['date']         = $columns['date'];

    return $new;
});

add_action( 'manage_vc_restaurant_posts_custom_column', function( $column, $post_id ) {
    if ( 'vc_cuisine' === $column ) {
        echo esc_html( join( ', ', wp_get_post_terms( $post_id, 'vc_cuisine', [ 'fields' => 'names' ] ) ) );
    }
    if ( 'vc_location' === $column ) {
        echo esc_html( join( ', ', wp_get_post_terms( $post_id, 'vc_location', [ 'fields' => 'names' ] ) ) );
    }
    if ( 'delivery' === $column ) {
        $val = get_post_meta( $post_id, VC_META_RESTAURANT_FIELDS['delivery'], true );
        echo $val === '1' ? '✓' : '—';
    }
}, 10, 2 );
