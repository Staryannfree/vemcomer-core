<?php
/**
 * Template Part: Mapa de Restaurantes
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vemcomer_is_plugin_active' ) || ! vemcomer_is_plugin_active() ) {
    return;
}
?>

<section class="home-map">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Veja restaurantes no mapa', 'vemcomer' ); ?></h2>
        <p class="section-description"><?php echo esc_html__( 'Encontre restaurantes próximos a você usando o mapa interativo.', 'vemcomer' ); ?></p>
        <?php echo do_shortcode( '[vc_restaurants_map]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</section>

