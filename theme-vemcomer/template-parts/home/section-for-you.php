<?php
/**
 * Template Part: Seção "Para Você" (usuários logados)
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() || ! function_exists( 'vemcomer_is_plugin_active' ) || ! vemcomer_is_plugin_active() ) {
    return;
}
?>

<section class="home-for-you">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Para você', 'vemcomer' ); ?></h2>
        
        <div class="tabs">
            <button class="tab tab--active" data-tab="favorites">
                <?php echo esc_html__( 'Meus Favoritos', 'vemcomer' ); ?>
            </button>
            <button class="tab" data-tab="orders">
                <?php echo esc_html__( 'Meus Pedidos', 'vemcomer' ); ?>
            </button>
        </div>

        <div class="tab-content tab-content--active" id="tab-favorites">
            <?php echo do_shortcode( '[vc_favorites]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>

        <div class="tab-content" id="tab-orders">
            <?php echo do_shortcode( '[vc_orders_history per_page="5"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
</section>

