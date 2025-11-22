<?php
/**
 * Template Part: Seção de Banners Promocionais
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

$args = isset( $args ) ? $args : [];
$quantidade = isset( $args['quantidade'] ) ? absint( $args['quantidade'] ) : 5;
?>

<section class="home-banners">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Promoções e destaques', 'vemcomer' ); ?></h2>
        <?php echo do_shortcode( '[vc_banners]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</section>

