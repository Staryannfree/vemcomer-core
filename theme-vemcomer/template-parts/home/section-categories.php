<?php
/**
 * Template Part: Seção de Categorias Populares
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

$home_improvements = get_template_directory() . '/inc/home-improvements.php';
if ( file_exists( $home_improvements ) ) {
    require_once $home_improvements;
}

if ( function_exists( 'vemcomer_home_popular_categories' ) ) {
    echo vemcomer_home_popular_categories(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// Carregar Font Awesome se necessário (para os botões do carrossel)
if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2' );
}

