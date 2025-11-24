<?php
/**
 * Home_Template — Carrega assets e configurações para o template da Home
 * @package VemComerCore
 */

namespace VC\Frontend;

use WP_Post;
use WP_Theme;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Home_Template {
    public function init(): void {
        add_filter( 'theme_page_templates', [ $this, 'register_page_template' ], 10, 4 );
        add_filter( 'template_include', [ $this, 'maybe_use_home_template' ], 99 );
        add_action( 'template_include', [ $this, 'maybe_enqueue_home_assets' ], 99 );
    }

    /**
     * Registra o template da Home na lista de templates disponíveis
     */
    public function register_page_template( array $post_templates, WP_Theme $theme, WP_Post $post, string $post_type ): array {
        $template_path = 'templates/page-home.php';
        $template_file = VEMCOMER_CORE_DIR . $template_path;
        
        if ( file_exists( $template_file ) ) {
            $post_templates[ $template_path ] = __( 'Home Pedevem', 'vemcomer' );
        }
        
        return $post_templates;
    }

    /**
     * Carrega o template da Home quando selecionado
     */
    public function maybe_use_home_template( string $template ): string {
        // Verificar se é a página "inicio" de várias formas
        $is_inicio_page = false;
        $page_id = null;
        
        if ( is_page() ) {
            $page_template = get_page_template_slug();
            
            // Se já tem o template selecionado, usar
            if ( $page_template === 'templates/page-home.php' ) {
                $template_file = VEMCOMER_CORE_DIR . $page_template;
                if ( file_exists( $template_file ) ) {
                    return $template_file;
                }
            }
            
            // Verificar se é a página "inicio"
            $page = get_queried_object();
            if ( $page && isset( $page->post_name ) && $page->post_name === 'inicio' ) {
                $is_inicio_page = true;
                $page_id = $page->ID;
            } elseif ( is_page( 'inicio' ) ) {
                $is_inicio_page = true;
                $page_id = get_queried_object_id();
            } elseif ( is_page() ) {
                // Verificar pelo slug
                $current_page = get_queried_object();
                if ( $current_page && isset( $current_page->post_name ) && $current_page->post_name === 'inicio' ) {
                    $is_inicio_page = true;
                    $page_id = $current_page->ID;
                }
            }
        }
        
        // Se é a página inicio, aplicar o template
        if ( $is_inicio_page ) {
            $template_file = VEMCOMER_CORE_DIR . 'templates/page-home.php';
            if ( file_exists( $template_file ) ) {
                // Atualizar o meta da página para usar o template correto (apenas uma vez)
                if ( $page_id ) {
                    $current_template = get_post_meta( $page_id, '_wp_page_template', true );
                    if ( $current_template !== 'templates/page-home.php' ) {
                        update_post_meta( $page_id, '_wp_page_template', 'templates/page-home.php' );
                    }
                }
                return $template_file;
            }
        }
        
        return $template;
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

