<?php
/**
 * Home_Template — Carrega assets e configurações para o template da Home
 * @package VemComerCore
 */

namespace VC\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Home_Template {
    public function init(): void {
        add_action( 'template_include', [ $this, 'maybe_enqueue_home_assets' ], 99 );
    }

    /**
     * Verifica se a página atual usa o template da Home e enfileira assets
     */
    public function maybe_enqueue_home_assets( string $template ): string {
        // Verificar se é o template da Home
        if ( is_page_template( 'templates/page-home.php' ) || $this->is_home_page() ) {
            wp_enqueue_style( 'vemcomer-home' );
            wp_enqueue_script( 'vemcomer-home' );
            
            // Garantir que os assets dos shortcodes também estejam carregados
            wp_enqueue_style( 'vemcomer-front' );
            wp_enqueue_script( 'vemcomer-front' );
            wp_enqueue_style( 'vemcomer-banners' );
            wp_enqueue_style( 'vemcomer-favorites' );
            wp_enqueue_script( 'vemcomer-favorites' );
            wp_enqueue_style( 'vemcomer-orders-history' );
            wp_enqueue_script( 'vemcomer-orders-history' );
        }

        return $template;
    }

    /**
     * Verifica se a página atual é a página inicial configurada
     */
    private function is_home_page(): bool {
        if ( ! is_front_page() ) {
            return false;
        }

        // Verificar se é uma página estática configurada como Home
        $front_page_id = get_option( 'page_on_front' );
        if ( $front_page_id ) {
            $template = get_page_template_slug( $front_page_id );
            if ( $template === 'templates/page-home.php' ) {
                return true;
            }
            
            // Verificar se a página tem o shortcode [vemcomer_restaurants] ou [vc_restaurants]
            $page = get_post( $front_page_id );
            if ( $page && (
                has_shortcode( $page->post_content, 'vemcomer_restaurants' ) ||
                has_shortcode( $page->post_content, 'vc_restaurants' ) ||
                has_shortcode( $page->post_content, 'vc_banners' )
            ) ) {
                return true;
            }
        }

        return false;
    }
}

