<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/class-vc-restaurants-table.php';
use VC\Utils\Onboarding_Helper;

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

add_filter(
    'bulk_actions-edit-vc_restaurant',
    static function( array $actions ): array {
        if ( current_user_can( 'manage_options' ) ) {
            $actions['reset_onboarding'] = __( 'Resetar onboarding (primeira visita)', 'vemcomer' );
        }

        return $actions;
    }
);

add_filter(
    'handle_bulk_actions-edit-vc_restaurant',
    static function( string $redirect_to, string $doaction, array $post_ids ): string {
        if ( 'reset_onboarding' !== $doaction || ! current_user_can( 'manage_options' ) ) {
            return $redirect_to;
        }

        $updated = 0;

        foreach ( $post_ids as $post_id ) {
            if ( 'vc_restaurant' !== get_post_type( $post_id ) ) {
                continue;
            }

            Onboarding_Helper::reset_to_first_visit( (int) $post_id );
            $updated++;
        }

        if ( $updated > 0 ) {
            $redirect_to = add_query_arg( 'vc_reset_onboarding', $updated, $redirect_to );
        }

        return $redirect_to;
    },
    10,
    3
);

add_filter(
    'post_row_actions',
    static function( array $actions, \WP_Post $post ): array {
        if ( 'vc_restaurant' !== $post->post_type || ! current_user_can( 'manage_options' ) ) {
            return $actions;
        }

        $url = wp_nonce_url(
            add_query_arg(
                [
                    'post_type' => 'vc_restaurant',
                    'action'    => 'reset_onboarding',
                    'post[]'    => $post->ID,
                ],
                admin_url( 'edit.php' )
            ),
            'bulk-posts'
        );

        $actions['vc_reset_onboarding'] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( $url ),
            esc_html__( 'Resetar onboarding', 'vemcomer' )
        );

        return $actions;
    },
    10,
    2
);

add_action(
    'admin_notices',
    static function(): void {
        if ( ! isset( $_REQUEST['vc_reset_onboarding'] ) ) {
            return;
        }

        $count = (int) $_REQUEST['vc_reset_onboarding'];
        if ( $count < 1 ) {
            return;
        }

        $message = sprintf(
            _n( 'Onboarding resetado para 1 lojista.', 'Onboarding resetado para %s lojistas.', $count, 'vemcomer' ),
            $count
        );

        printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
    }
);

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
