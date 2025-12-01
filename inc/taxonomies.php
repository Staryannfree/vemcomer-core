<?php
/**
 * Taxonomias do CPT Restaurantes
 * - Tipo de Cozinha (hierárquica)
 * - Bairro/Localização (não hierárquica)
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function() {
    // Tipo de Cozinha
    register_taxonomy( 'vc_cuisine', [ 'vc_restaurant' ], [
        'labels' => [
            'name'          => __( 'Tipos de cozinha', 'vemcomer' ),
            'singular_name' => __( 'Tipo de cozinha', 'vemcomer' ),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite' => [ 'slug' => 'cozinha' ],
        'show_in_rest' => true,
    ]);

    // Bairro / Localização
    register_taxonomy( 'vc_location', [ 'vc_restaurant' ], [
        'labels' => [
            'name'          => __( 'Bairros', 'vemcomer' ),
            'singular_name' => __( 'Bairro', 'vemcomer' ),
        ],
        'hierarchical' => false,
        'show_admin_column' => true,
        'rewrite' => [ 'slug' => 'bairro' ],
        'show_in_rest' => true,
    ]);

    // Facilidades / Etiquetas
    register_taxonomy( 'vc_facility', [ 'vc_restaurant' ], [
        'labels' => [
            'name'          => __( 'Facilidades', 'vemcomer' ),
            'singular_name' => __( 'Facilidade', 'vemcomer' ),
        ],
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite' => [ 'slug' => 'facilidade' ],
        'show_in_rest' => true,
    ]);
});
