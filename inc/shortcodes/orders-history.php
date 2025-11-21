<?php
/**
 * [vc_orders_history]
 * Atributos:
 *  - per_page (opcional, default 10)
 *  - status (opcional, filtrar por status)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_orders_history', function( $atts = [] ) {
    vc_sc_mark_used();

    // Enfileirar assets
    wp_enqueue_style( 'vemcomer-front' );
    wp_enqueue_script( 'vemcomer-front' );
    wp_enqueue_style( 'vemcomer-orders-history' );
    wp_enqueue_script( 'vemcomer-orders-history' );

    $a = shortcode_atts([
        'per_page' => '10',
        'status'   => '',
    ], $atts, 'vc_orders_history' );

    $per_page = max( 1, min( 50, (int) $a['per_page'] ) );
    $status = sanitize_text_field( $a['status'] );

    // Verificar se usuário está logado
    if ( ! is_user_logged_in() ) {
        return '<div class="vc-orders-empty">
            <p>' . esc_html__( 'Faça login para ver seu histórico de pedidos.', 'vemcomer' ) . '</p>
            <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '" class="vc-btn">' . esc_html__( 'Entrar', 'vemcomer' ) . '</a>
        </div>';
    }

    ob_start();
    ?>
    <div class="vc-orders-history" data-per-page="<?php echo esc_attr( (string) $per_page ); ?>" data-status="<?php echo esc_attr( $status ); ?>">
        <div class="vc-orders-history__header">
            <h2 class="vc-orders-history__title"><?php echo esc_html__( 'Meus Pedidos', 'vemcomer' ); ?></h2>
            <div class="vc-orders-history__filters">
                <select class="vc-orders-history__status-filter" id="vc-orders-status-filter">
                    <option value=""><?php echo esc_html__( 'Todos os status', 'vemcomer' ); ?></option>
                    <option value="vc-pending" <?php selected( $status, 'vc-pending' ); ?>><?php echo esc_html__( 'Pendente', 'vemcomer' ); ?></option>
                    <option value="vc-confirmed" <?php selected( $status, 'vc-confirmed' ); ?>><?php echo esc_html__( 'Confirmado', 'vemcomer' ); ?></option>
                    <option value="vc-preparing" <?php selected( $status, 'vc-preparing' ); ?>><?php echo esc_html__( 'Preparando', 'vemcomer' ); ?></option>
                    <option value="vc-ready" <?php selected( $status, 'vc-ready' ); ?>><?php echo esc_html__( 'Pronto', 'vemcomer' ); ?></option>
                    <option value="vc-delivering" <?php selected( $status, 'vc-delivering' ); ?>><?php echo esc_html__( 'Em entrega', 'vemcomer' ); ?></option>
                    <option value="vc-completed" <?php selected( $status, 'vc-completed' ); ?>><?php echo esc_html__( 'Concluído', 'vemcomer' ); ?></option>
                    <option value="vc-cancelled" <?php selected( $status, 'vc-cancelled' ); ?>><?php echo esc_html__( 'Cancelado', 'vemcomer' ); ?></option>
                </select>
            </div>
        </div>
        <div class="vc-orders-history__list" id="vc-orders-list">
            <p class="vc-orders-history__loading"><?php echo esc_html__( 'Carregando pedidos...', 'vemcomer' ); ?></p>
        </div>
        <div class="vc-orders-history__pagination" id="vc-orders-pagination"></div>
    </div>
    <?php
    return ob_get_clean();
});

