<?php
/**
 * Marketplace_Templates — Shortcodes para templates estáticos do marketplace
 * @package VemComerCore
 */

namespace VC\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Marketplace_Templates {
    private const MAP = [
        'busca-avancada' => [
            'title'     => 'Marketplace - Busca Avançada',
            'template'  => 'templates/marketplace/busca-avancada.php',
            'shortcode' => 'vc_marketplace_busca_avancada',
            'slug'      => 'busca-avancada',
        ],
        'cardapio-digital-standalone' => [
            'title'     => 'Marketplace - Cardápio Digital Standalone',
            'template'  => 'templates/marketplace/cardapio-digital-standalone.php',
            'shortcode' => 'vc_marketplace_cardapio_digital',
            'slug'      => 'cardapio-digital-standalone',
        ],
        'carrinho-side-cart' => [
            'title'     => 'Marketplace - Carrinho Side Cart',
            'template'  => 'templates/marketplace/carrinho-side-cart.php',
            'shortcode' => 'vc_marketplace_carrinho_side_cart',
            'slug'      => 'carrinho-side-cart',
        ],
        'central-marketing' => [
            'title'     => 'Marketplace - Central Marketing',
            'template'  => 'templates/marketplace/central-marketing.php',
            'shortcode' => 'vc_marketplace_central_marketing',
            'slug'      => 'central-marketing',
        ],
        'checkout-simplificado' => [
            'title'     => 'Marketplace - Checkout Simplificado',
            'template'  => 'templates/marketplace/checkout-simplificado.php',
            'shortcode' => 'vc_marketplace_checkout_simplificado',
            'slug'      => 'checkout-simplificado',
        ],
        'configuracao-loja' => [
            'title'     => 'Marketplace - Configuração da Loja',
            'template'  => 'templates/marketplace/configuracao-loja.php',
            'shortcode' => 'vc_marketplace_configuracao_loja',
            'slug'      => 'configuracao-loja',
        ],
        'criador-stories-restaurantes' => [
            'title'     => 'Marketplace - Criador de Stories',
            'template'  => 'templates/marketplace/criador-stories-restaurantes.php',
            'shortcode' => 'vc_marketplace_criador_stories',
            'slug'      => 'criador-stories-restaurantes',
        ],
        'detalhes-evento' => [
            'title'     => 'Marketplace - Detalhes de Evento',
            'template'  => 'templates/marketplace/detalhes-evento.php',
            'shortcode' => 'vc_marketplace_detalhes_evento',
            'slug'      => 'detalhes-evento',
        ],
        'feed-eventos' => [
            'title'     => 'Marketplace - Feed de Eventos',
            'template'  => 'templates/marketplace/feed-eventos.php',
            'shortcode' => 'vc_marketplace_feed_eventos',
            'slug'      => 'feed-eventos',
        ],
        'gestao-cardapio' => [
            'title'     => 'Marketplace - Gestão de Cardápio',
            'template'  => 'templates/marketplace/gestao-cardapio.php',
            'shortcode' => 'vc_marketplace_gestao_cardapio',
            'slug'      => 'gestao-cardapio',
        ],
        'gestor-eventos' => [
            'title'     => 'Marketplace - Gestor de Eventos',
            'template'  => 'templates/marketplace/gestor-eventos.php',
            'shortcode' => 'vc_marketplace_gestor_eventos',
            'slug'      => 'gestor-eventos',
        ],
        'meus-enderecos' => [
            'title'     => 'Marketplace - Meus Endereços',
            'template'  => 'templates/marketplace/meus-enderecos.php',
            'shortcode' => 'vc_marketplace_meus_enderecos',
            'slug'      => 'meus-enderecos',
        ],
        'meus-favoritos' => [
            'title'     => 'Marketplace - Meus Favoritos',
            'template'  => 'templates/marketplace/meus-favoritos.php',
            'shortcode'  => 'vc_marketplace_meus_favoritos',
            'slug'      => 'meus-favoritos',
        ],
        'minha-conta-cliente' => [
            'title'     => 'Marketplace - Minha Conta Cliente',
            'template'  => 'templates/marketplace/minha-conta-cliente.php',
            'shortcode' => 'vc_marketplace_minha_conta_cliente',
            'slug'      => 'minha-conta-cliente',
        ],
        'modal-detalhes-produto' => [
            'title'     => 'Marketplace - Modal Detalhes do Produto',
            'template'  => 'templates/marketplace/modal-detalhes-produto.php',
            'shortcode' => 'vc_marketplace_modal_detalhes_produto',
            'slug'      => 'modal-detalhes-produto',
        ],
        'modal-informacoes-restaurante' => [
            'title'     => 'Marketplace - Modal Informações do Restaurante',
            'template'  => 'templates/marketplace/modal-informacoes-restaurante.php',
            'shortcode' => 'vc_marketplace_modal_informacoes_restaurante',
            'slug'      => 'modal-informacoes-restaurante',
        ],
        'notificacoes' => [
            'title'     => 'Marketplace - Notificações',
            'template'  => 'templates/marketplace/notificacoes.php',
            'shortcode' => 'vc_marketplace_notificacoes',
            'slug'      => 'notificacoes',
        ],
        'offline-pwa' => [
            'title'     => 'Marketplace - Offline PWA',
            'template'  => 'templates/marketplace/offline-pwa.php',
            'shortcode' => 'vc_marketplace_offline_pwa',
            'slug'      => 'offline-pwa',
        ],
        'painel-lojista-plano-delivery-pro' => [
            'title'     => 'Marketplace - Painel Lojista Delivery Pro',
            'template'  => 'templates/marketplace/painel-lojista-plano-delivery-pro.php',
            'shortcode' => 'vc_marketplace_painel_delivery_pro',
            'slug'      => 'painel-lojista-plano-delivery-pro',
        ],
        'painel-lojista-plano-gratis' => [
            'title'     => 'Marketplace - Painel Lojista Plano Grátis',
            'template'  => 'templates/marketplace/painel-lojista-plano-gratis.php',
            'shortcode' => 'vc_marketplace_painel_gratis',
            'slug'      => 'painel-lojista-plano-gratis',
        ],
        'painel-lojista-plano-growth-master' => [
            'title'     => 'Marketplace - Painel Lojista Growth Master',
            'template'  => 'templates/marketplace/painel-lojista-plano-growth-master.php',
            'shortcode' => 'vc_marketplace_painel_growth_master',
            'slug'      => 'painel-lojista-plano-growth-master',
        ],
        'painel-pedidos' => [
            'title'     => 'Marketplace - Painel de Pedidos',
            'template'  => 'templates/marketplace/painel-pedidos.php',
            'shortcode' => 'vc_marketplace_painel_pedidos',
            'slug'      => 'painel-pedidos',
        ],
        'popup-planos-disponiveis' => [
            'title'     => 'Marketplace - Popup Planos Disponíveis',
            'template'  => 'templates/marketplace/popup-planos-disponiveis.php',
            'shortcode' => 'vc_marketplace_popup_planos',
            'slug'      => 'popup-planos-disponiveis',
        ],
        'secao-avaliacoes' => [
            'title'     => 'Marketplace - Seção de Avaliações',
            'template'  => 'templates/marketplace/secao-avaliacoes.php',
            'shortcode' => 'vc_marketplace_secao_avaliacoes',
            'slug'      => 'secao-avaliacoes',
        ],
        'todas-as-categorias' => [
            'title'     => 'Marketplace - Todas as Categorias',
            'template'  => 'templates/marketplace/todas-as-categorias.php',
            'shortcode' => 'vc_marketplace_todas_categorias',
            'slug'      => 'todas-as-categorias',
        ],
        'wizard-onboarding' => [
            'title'     => 'Marketplace - Wizard Onboarding',
            'template'  => 'templates/marketplace/wizard-onboarding.php',
            'shortcode' => 'vc_marketplace_wizard_onboarding',
            'slug'      => 'wizard-onboarding',
        ],
    ];

    public function init(): void {
        foreach ( self::MAP as $config ) {
            add_shortcode( $config['shortcode'], fn() => $this->render_template( $config['template'] ) );
        }
    }

    public static function get_templates(): array {
        return self::MAP;
    }

    private function render_template( string $relative_path ): string {
        $template_file = VEMCOMER_CORE_DIR . ltrim( $relative_path, '/' );

        if ( ! file_exists( $template_file ) ) {
            return '';
        }

        if ( ! defined( 'VC_MARKETPLACE_INLINE' ) ) {
            define( 'VC_MARKETPLACE_INLINE', true );
        }

        if ( str_contains( $relative_path, 'wizard-onboarding' ) && ! defined( 'VC_WIZARD_INLINE' ) ) {
            define( 'VC_WIZARD_INLINE', true );
        }

        ob_start();
        include $template_file;

        return ob_get_clean();
    }
}
