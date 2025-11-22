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

