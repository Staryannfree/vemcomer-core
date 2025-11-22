<?php
/**
 * Helper functions para banners
 * 
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Obtém as classes CSS do banner baseado no tamanho
 * 
 * @param int|WP_Post $banner_id ID do banner ou objeto WP_Post
 * @return string Classes CSS do banner
 */
function vemcomer_get_banner_size_class( $banner_id ): string {
    if ( is_object( $banner_id ) ) {
        $banner_id = $banner_id->ID;
    }
    
    $size = (string) get_post_meta( (int) $banner_id, '_vc_banner_size', true );
    
    if ( empty( $size ) ) {
        $size = 'medium'; // Tamanho padrão
    }
    
    $allowed_sizes = [ 'small', 'medium', 'large', 'full' ];
    if ( ! in_array( $size, $allowed_sizes, true ) ) {
        $size = 'medium';
    }
    
    return 'vc-banner-item--' . esc_attr( $size );
}

/**
 * Obtém o tamanho do banner
 * 
 * @param int|WP_Post $banner_id ID do banner ou objeto WP_Post
 * @return string Tamanho do banner (small, medium, large, full)
 */
function vemcomer_get_banner_size( $banner_id ): string {
    if ( is_object( $banner_id ) ) {
        $banner_id = $banner_id->ID;
    }
    
    $size = (string) get_post_meta( (int) $banner_id, '_vc_banner_size', true );
    
    if ( empty( $size ) ) {
        return 'medium'; // Tamanho padrão
    }
    
    $allowed_sizes = [ 'small', 'medium', 'large', 'full' ];
    if ( ! in_array( $size, $allowed_sizes, true ) ) {
        return 'medium';
    }
    
    return $size;
}

/**
 * Obtém o label do tamanho do banner
 * 
 * @param int|WP_Post $banner_id ID do banner ou objeto WP_Post
 * @return string Label do tamanho
 */
function vemcomer_get_banner_size_label( $banner_id ): string {
    $size = vemcomer_get_banner_size( $banner_id );
    
    $labels = [
        'small'  => __( 'Pequeno', 'vemcomer' ),
        'medium' => __( 'Médio', 'vemcomer' ),
        'large'  => __( 'Grande', 'vemcomer' ),
        'full'   => __( 'Largura Total', 'vemcomer' ),
    ];
    
    return $labels[ $size ] ?? __( 'Médio', 'vemcomer' );
}

