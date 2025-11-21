<?php
/**
 * [vc_notifications]
 * Atributos:
 *  - per_page (opcional, default 10)
 *  - show_badge (opcional, default true) - Mostrar badge com contador
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_notifications', function( $atts = [] ) {
    vc_sc_mark_used();

    // Enfileirar assets
    wp_enqueue_style( 'vemcomer-front' );
    wp_enqueue_script( 'vemcomer-front' );
    wp_enqueue_style( 'vemcomer-notifications' );
    wp_enqueue_script( 'vemcomer-notifications' );

    $a = shortcode_atts([
        'per_page'   => '10',
        'show_badge' => 'true',
    ], $atts, 'vc_notifications' );

    $per_page = max( 1, min( 50, (int) $a['per_page'] ) );
    $show_badge = filter_var( $a['show_badge'], FILTER_VALIDATE_BOOLEAN );

    // Verificar se usuário está logado
    if ( ! is_user_logged_in() ) {
        return '<div class="vc-notifications-empty">
            <p>' . esc_html__( 'Faça login para ver suas notificações.', 'vemcomer' ) . '</p>
        </div>';
    }

    ob_start();
    ?>
    <div class="vc-notifications" data-per-page="<?php echo esc_attr( (string) $per_page ); ?>" data-show-badge="<?php echo esc_attr( $show_badge ? '1' : '0' ); ?>">
        <div class="vc-notifications__header">
            <h2 class="vc-notifications__title"><?php echo esc_html__( 'Notificações', 'vemcomer' ); ?></h2>
            <?php if ( $show_badge ) : ?>
                <span class="vc-notifications__badge" id="vc-notifications-badge" style="display: none;">0</span>
            <?php endif; ?>
            <button class="vc-btn vc-btn--small vc-notifications__mark-all" id="vc-notifications-mark-all" style="display: none;">
                <?php echo esc_html__( 'Marcar todas como lidas', 'vemcomer' ); ?>
            </button>
        </div>
        <div class="vc-notifications__list" id="vc-notifications-list">
            <p class="vc-notifications__loading"><?php echo esc_html__( 'Carregando notificações...', 'vemcomer' ); ?></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

