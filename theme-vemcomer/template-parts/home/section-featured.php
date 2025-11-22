<?php
/**
 * Template Part: Restaurantes em Destaque
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

if ( function_exists( 'vemcomer_home_featured_restaurants' ) ) {
    echo vemcomer_home_featured_restaurants(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

