<?php
/**
 * [vc_favorites]
 * Atributos:
 *  - type="restaurants|menu-items" (default: restaurants)
 *  - per_page (opcional, default 12)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_favorites', function( $atts = [] ) {
    vc_sc_mark_used();

    // Enfileirar assets
    wp_enqueue_style( 'vemcomer-front' );
    wp_enqueue_script( 'vemcomer-front' );
    wp_enqueue_style( 'vemcomer-favorites' );
    wp_enqueue_script( 'vemcomer-favorites' );

    $a = shortcode_atts([
        'type'     => 'restaurants',
        'per_page' => '12',
    ], $atts, 'vc_favorites' );

    $type = in_array( $a['type'], [ 'restaurants', 'menu-items' ], true ) ? $a['type'] : 'restaurants';
    $per_page = max( 1, min( 50, (int) $a['per_page'] ) );

    // Verificar se usuário está logado
    if ( ! is_user_logged_in() ) {
        return '<div class="vc-favorites-empty">
            <p>' . esc_html__( 'Faça login para ver seus favoritos.', 'vemcomer' ) . '</p>
            <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="vc-btn">' . esc_html__( 'Entrar', 'vemcomer' ) . '</a>
        </div>';
    }

    ob_start();
    ?>
    <div class="vc-favorites" data-type="<?php echo esc_attr( $type ); ?>" data-per-page="<?php echo esc_attr( (string) $per_page ); ?>">
        <h2 class="vc-favorites__title"><?php echo esc_html( $type === 'restaurants' ? __( 'Meus Restaurantes Favoritos', 'vemcomer' ) : __( 'Meus Itens Favoritos', 'vemcomer' ) ); ?></h2>
        <div class="vc-favorites-list" id="vc-favorites-list">
            <p class="vc-favorites-loading"><?php echo esc_html__( 'Carregando favoritos...', 'vemcomer' ); ?></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

