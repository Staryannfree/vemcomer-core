<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/class-vc-restaurants-table.php';

add_action( 'admin_menu', static function() {
    add_submenu_page(
        'vemcomer-root',
        __( 'Restaurantes', 'vemcomer' ),
        __( 'Restaurantes', 'vemcomer' ),
        'edit_vc_restaurants',
        'vemcomer-restaurants',
        'vc_render_restaurants_table_page'
    );

    add_submenu_page(
        'vemcomer-root',
        __( 'Aprovar restaurantes', 'vemcomer' ),
        __( 'Aprovar restaurantes', 'vemcomer' ),
        'publish_vc_restaurants',
        'vemcomer-restaurants-approval',
        'vc_render_restaurants_approval_page'
    );
}, 30 );

if ( ! function_exists( 'vc_render_restaurants_table_page' ) ) {
function vc_render_restaurants_table_page(): void {
    if ( ! current_user_can( 'edit_vc_restaurants' ) ) {
        wp_die( esc_html__( 'Você não possui permissão para acessar esta página.', 'vemcomer' ) );
    }

    $table = new VC_Restaurants_Table();
    $table->prepare_items();

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</h1>';
    echo '<hr class="wp-header-end" />';
    echo '<p>' . esc_html__( 'Use os filtros avançados e ações em massa para gerir os restaurantes cadastrados.', 'vemcomer' ) . '</p>';

    settings_errors( 'vc_restaurants' );

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="vemcomer-restaurants" />';

    $table->display();
    echo '</form>';
    echo '</div>';
}
}

if ( ! function_exists( 'vc_render_restaurants_approval_page' ) ) {
function vc_render_restaurants_approval_page(): void {
    if ( ! current_user_can( 'publish_vc_restaurants' ) ) {
        wp_die( esc_html__( 'Você não possui permissão para acessar esta página.', 'vemcomer' ) );
    }

    $table = new VC_Restaurants_Table(
        [
            'post_statuses' => [ 'pending' ],
            'approval_mode' => true,
        ]
    );
    $table->prepare_items();

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__( 'Aprovação de restaurantes', 'vemcomer' ) . '</h1>';
    echo '<hr class="wp-header-end" />';
    echo '<p>' . esc_html__( 'Aprove inscrições de restaurantes antes de ficarem públicas.', 'vemcomer' ) . '</p>';

    settings_errors( 'vc_restaurants' );

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="vemcomer-restaurants-approval" />';

    $table->display();
    echo '</form>';
    echo '</div>';
}
}
