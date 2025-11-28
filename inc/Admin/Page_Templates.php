<?php
/**
 * Page_Templates — Templates de páginas pré-configuradas para importação
 * Similar aos temas premium que têm páginas prontas para importar
 *
 * @package VemComerCore
 */

namespace VC\Admin;

use VC\Frontend\Marketplace_Templates;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Page_Templates {
    
    /**
     * Retorna todos os templates de páginas disponíveis
     * 
     * @return array Array de templates com título, conteúdo, slug, etc.
     */
    public static function get_templates(): array {
        $templates = [
            'home' => [
                'title'       => __( 'Home - Marketplace Completo', 'vemcomer' ),
                'slug'        => 'inicio',
                'description' => __( 'Página inicial completa com hero, banners, restaurantes, mapa e mais.', 'vemcomer' ),
                'template'    => 'templates/page-home.php',
                'content'     => self::get_home_content(),
                'featured'    => true,
            ],
            'restaurants' => [
                'title'       => __( 'Lista de Restaurantes', 'vemcomer' ),
                'slug'        => 'restaurantes',
                'description' => __( 'Página com lista completa de restaurantes e filtros avançados.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_restaurants_content(),
                'featured'    => true,
            ],
            'about' => [
                'title'       => __( 'Sobre Nós', 'vemcomer' ),
                'slug'        => 'sobre-nos',
                'description' => __( 'Página institucional sobre o marketplace.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_about_content(),
                'featured'    => false,
            ],
            'contact' => [
                'title'       => __( 'Contato', 'vemcomer' ),
                'slug'        => 'contato',
                'description' => __( 'Página de contato com formulário e informações.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_contact_content(),
                'featured'    => false,
            ],
            'faq' => [
                'title'       => __( 'Perguntas Frequentes', 'vemcomer' ),
                'slug'        => 'perguntas-frequentes',
                'description' => __( 'Página com perguntas e respostas comuns.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_faq_content(),
                'featured'    => false,
            ],
            'terms' => [
                'title'       => __( 'Termos de Uso', 'vemcomer' ),
                'slug'        => 'termos-de-uso',
                'description' => __( 'Página com termos e condições de uso do marketplace.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_terms_content(),
                'featured'    => false,
            ],
            'privacy' => [
                'title'       => __( 'Política de Privacidade', 'vemcomer' ),
                'slug'        => 'politica-de-privacidade',
                'description' => __( 'Página com política de privacidade e proteção de dados.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_privacy_content(),
                'featured'    => false,
            ],
            'restaurant_signup' => [
                'title'       => __( 'Cadastro de Restaurante', 'vemcomer' ),
                'slug'        => 'cadastre-seu-restaurante',
                'description' => __( 'Página para restaurantes se cadastrarem no marketplace.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_restaurant_signup_content(),
                'featured'    => true,
            ],
            'customer_signup' => [
                'title'       => __( 'Cadastro de Cliente', 'vemcomer' ),
                'slug'        => 'cadastro',
                'description' => __( 'Página para clientes criarem suas contas.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_customer_signup_content(),
                'featured'    => false,
            ],
            'checkout' => [
                'title'       => __( 'Checkout', 'vemcomer' ),
                'slug'        => 'checkout',
                'description' => __( 'Página de finalização de pedidos.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_checkout_content(),
                'featured'    => false,
            ],
            'marketplace_complete' => [
                'title'       => __( 'Marketplace Completo - Design Elaborado', 'vemcomer' ),
                'slug'        => 'marketplace-completo',
                'description' => __( 'Página completa com design elaborado: hero, categorias, destaques, ranking, premium, mapa, eventos, parceiros, blog e sidebar completa.', 'vemcomer' ),
                'template'    => '',
                'content'     => self::get_marketplace_complete_content(),
                'featured'    => true,
            ],
        ];

        if ( class_exists( '\\VC\\Frontend\\Marketplace_Templates' ) ) {
            foreach ( Marketplace_Templates::get_templates() as $key => $config ) {
                $templates[ 'marketplace_' . $key ] = [
                    'title'       => __( $config['title'], 'vemcomer' ),
                    'slug'        => $config['slug'],
                    'description' => sprintf( __( 'Importa o layout %s diretamente do marketplace.', 'vemcomer' ), $config['title'] ),
                    'template'    => $config['template'],
                    'content'     => '<!-- wp:shortcode -->[' . $config['shortcode'] . ']<!-- /wp:shortcode -->',
                    'featured'    => false,
                ];
            }
        }

        return $templates;
    }
    
    /**
     * Conteúdo da página Home
     */
    private static function get_home_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vc_banners]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":2} -->
<h2 id="restaurants-list">' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[vemcomer_restaurants]
<!-- /wp:shortcode -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Veja restaurantes no mapa', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Encontre restaurantes próximos a você usando o mapa interativo.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vc_restaurants_map]
<!-- /wp:shortcode -->';
    }
    
    /**
     * Conteúdo da página de Restaurantes
     */
    private static function get_restaurants_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Descubra os melhores restaurantes da sua região. Use os filtros para encontrar exatamente o que procura.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vc_filters]
<!-- /wp:shortcode -->

<!-- wp:shortcode -->
[vc_restaurants]
<!-- /wp:shortcode -->';
    }
    
    /**
     * Conteúdo da página Sobre Nós
     */
    private static function get_about_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Sobre Nós', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Bem-vindo ao VemComer, o marketplace de comida mais completo da região!', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Nossa Missão', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Conectar pessoas aos melhores restaurantes, facilitando o acesso a comida de qualidade com praticidade e agilidade.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'O Que Oferecemos', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:list -->
<ul>
<li>' . esc_html__( 'Delivery rápido e confiável', 'vemcomer' ) . '</li>
<li>' . esc_html__( 'Cardápios atualizados em tempo real', 'vemcomer' ) . '</li>
<li>' . esc_html__( 'Avaliações e comentários de clientes', 'vemcomer' ) . '</li>
<li>' . esc_html__( 'Promoções e cupons exclusivos', 'vemcomer' ) . '</li>
<li>' . esc_html__( 'Múltiplas formas de pagamento', 'vemcomer' ) . '</li>
</ul>
<!-- /wp:list -->';
    }
    
    /**
     * Conteúdo da página de Contato
     */
    private static function get_contact_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Entre em Contato', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Estamos aqui para ajudar! Entre em contato conosco através dos canais abaixo.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Informações de Contato', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>' . esc_html__( 'Email:', 'vemcomer' ) . '</strong> contato@vemcomer.com.br</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>' . esc_html__( 'Telefone:', 'vemcomer' ) . '</strong> (00) 0000-0000</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>' . esc_html__( 'WhatsApp:', 'vemcomer' ) . '</strong> (00) 00000-0000</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Horário de Atendimento', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Segunda a Sexta: 9h às 18h', 'vemcomer' ) . '<br>' . esc_html__( 'Sábado: 9h às 13h', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->';
    }
    
    /**
     * Conteúdo da página FAQ
     */
    private static function get_faq_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Perguntas Frequentes', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Como fazer um pedido?', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Escolha o restaurante, adicione os itens ao carrinho e finalize o pedido. Você pode pagar com cartão, Pix ou dinheiro na entrega.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Quais são as formas de pagamento?', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Aceitamos cartão de crédito, débito, Pix e dinheiro na entrega.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Qual o tempo de entrega?', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'O tempo de entrega varia conforme o restaurante e a distância. Geralmente entre 30 a 60 minutos.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( 'Posso cancelar um pedido?', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Sim, você pode cancelar o pedido antes que o restaurante comece a prepará-lo. Entre em contato conosco.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->';
    }
    
    /**
     * Conteúdo da página Termos de Uso
     */
    private static function get_terms_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Termos de Uso', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>' . esc_html__( 'Última atualização:', 'vemcomer' ) . '</strong> ' . date_i18n( get_option( 'date_format' ) ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( '1. Aceitação dos Termos', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Ao acessar e usar o VemComer, você concorda em cumprir estes termos de uso.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( '2. Uso do Serviço', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'O VemComer é uma plataforma que conecta clientes a restaurantes. Não somos responsáveis pela qualidade dos alimentos ou pelo tempo de entrega dos restaurantes parceiros.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( '3. Contas de Usuário', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Você é responsável por manter a segurança de sua conta e senha. Não compartilhe suas credenciais com terceiros.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->';
    }
    
    /**
     * Conteúdo da página Política de Privacidade
     */
    private static function get_privacy_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Política de Privacidade', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><strong>' . esc_html__( 'Última atualização:', 'vemcomer' ) . '</strong> ' . date_i18n( get_option( 'date_format' ) ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( '1. Informações que Coletamos', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Coletamos informações que você nos fornece diretamente, como nome, email, endereço e telefone.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( '2. Como Usamos suas Informações', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Utilizamos suas informações para processar pedidos, melhorar nossos serviços e comunicar promoções.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2>' . esc_html__( '3. Proteção de Dados', 'vemcomer' ) . '</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Implementamos medidas de segurança para proteger suas informações pessoais contra acesso não autorizado.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->';
    }
    
    /**
     * Conteúdo da página Cadastro de Restaurante
     */
    private static function get_restaurant_signup_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Cadastre seu Restaurante', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Junte-se ao VemComer e aumente suas vendas! Cadastre seu restaurante e comece a receber pedidos hoje mesmo.', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vemcomer_restaurant_signup]
<!-- /wp:shortcode -->';
    }
    
    /**
     * Conteúdo da página Cadastro de Cliente
     */
    private static function get_customer_signup_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Criar Conta', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>' . esc_html__( 'Crie sua conta no VemComer e aproveite todas as vantagens: pedidos rápidos, favoritos, histórico e muito mais!', 'vemcomer' ) . '</p>
<!-- /wp:paragraph -->

<!-- wp:shortcode -->
[vemcomer_customer_signup]
<!-- /wp:shortcode -->';
    }
    
    /**
     * Conteúdo da página Checkout
     */
    private static function get_checkout_content(): string {
        return '<!-- wp:heading {"level":1} -->
<h1>' . esc_html__( 'Finalizar Pedido', 'vemcomer' ) . '</h1>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[vemcomer_checkout]
<!-- /wp:shortcode -->';
    }
    
    /**
     * Conteúdo da página Marketplace Completo (Design Elaborado)
     */
    private static function get_marketplace_complete_content(): string {
        $home_url = home_url( '/' );
        $restaurants_url = home_url( '/restaurantes/' );
        $login_url = wp_login_url();
        $signup_url = wp_registration_url();
        
        return '<!-- wp:html -->
<style>
.marketplace-complete { font-family: \'Segoe UI\', Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 0;}
.marketplace-complete header { background: #262626; color: #fff; padding: 20px 0;}
.marketplace-complete .container { width: 100%; max-width: none; margin: 0; padding: 0;}
.marketplace-complete nav { display: flex; justify-content: space-between; align-items: center; padding: 0 4vw;}
.marketplace-complete .logo { font-size: 2.4rem; font-weight: bold;}
.marketplace-complete .menu { list-style: none; display: flex; gap: 26px;}
.marketplace-complete .menu li { display: inline;}
.marketplace-complete .menu a { color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s;}
.marketplace-complete .menu a:hover { color: #2f9e44;}
.marketplace-complete .account { display: flex; gap: 10px;}
.marketplace-complete .account-btn { background: #2f9e44; color: #fff; padding: 10px 23px; border-radius: 25px; text-decoration: none; font-weight: 500; border: none; transition: .2s; cursor: pointer; display: inline-block;}
.marketplace-complete .account-btn:hover { background: #1e7e34;}
.marketplace-complete .hero { background: linear-gradient(135deg, #2f9e44 0%, #1e7e34 100%),url(\'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80\')center/cover no-repeat; background-blend-mode: overlay; padding: 98px 0 120px 0; color: #fff; text-align: center;}
.marketplace-complete .hero h1 { font-size: 3.3rem; margin-bottom: 19px;}
.marketplace-complete .hero p { font-size: 1.6rem; margin-bottom: 36px;}
.marketplace-complete .search-bar { margin: auto; width: 54%; display: flex; background: #fff; border-radius: 50px; overflow: hidden; box-shadow: 0 4px 14px #0003; position: relative;}
.marketplace-complete .search-bar input { flex: 1; border: none; padding: 19px 27px; font-size: 1.22rem;}
.marketplace-complete .search-bar button { background: #2f9e44; color: #fff; border: none; padding: 0 34px; font-size: 1.29rem; cursor: pointer;}
.marketplace-complete .search-bar button:hover { background: #1e7e34;}
.marketplace-complete .search-autocomplete { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border-radius: 8px; box-shadow: 0 4px 14px #0003; z-index: 1000; max-height: 300px; overflow-y: auto; margin-top: 5px; display: none;}
.marketplace-complete .search-autocomplete-item { padding: 12px 20px; cursor: pointer; border-bottom: 1px solid #e5e7eb;}
.marketplace-complete .search-autocomplete-item:hover { background: #f9fafb;}
.marketplace-complete .btn-geolocation { background: rgba(255,255,255,0.2); color: #fff; border: 2px solid rgba(255,255,255,0.3); border-radius: 25px; padding: 10px 20px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; margin: 10px 0;}
.marketplace-complete .btn-geolocation:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5);}
.marketplace-complete .btn-geolocation.is-loading { opacity: 0.7; cursor: wait;}
.marketplace-complete .quick-filters { display: flex; gap: 19px; justify-content: center; margin: 29px 0; flex-wrap: wrap;}
.marketplace-complete .quick-filters button { background: #fff; color: #2f9e44; border: 1px solid #2f9e44; border-radius: 22px; padding: 8px 19px; cursor: pointer; transition: all 0.3s;}
.marketplace-complete .quick-filters button:hover { background: #2f9e44; color: #fff;}
.marketplace-complete .filter-chip { background: #fff; color: #2f9e44; border: 1px solid #2f9e44; border-radius: 22px; padding: 8px 19px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px;}
.marketplace-complete .filter-chip:hover, .marketplace-complete .filter-chip.is-active { background: #2f9e44; color: #fff;}
.marketplace-complete .section-title { font-size: 2.1rem; color: #262626; margin: 36px 0 26px 0; text-align: center; letter-spacing: .6px;}
.marketplace-complete section { margin: 38px 0;}
.marketplace-complete .categories { display: flex; flex-wrap: nowrap; overflow-x: auto; gap: 32px; justify-content: flex-start; padding-bottom: 10px; padding-left: 4vw;}
.marketplace-complete .category-card { min-width: 200px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px #0001; padding: 24px; text-align: center; flex: 0 0 auto; margin-bottom: 0;}
.marketplace-complete .category-card i { font-size: 2.5rem; margin-bottom: 10px; color: #2f9e44;}
.marketplace-complete .featured, .marketplace-complete .reviews, .marketplace-complete .faq, .marketplace-complete .partners, .marketplace-complete .blog, .marketplace-complete .app-promo, .marketplace-complete .newsletter, .marketplace-complete .destaques-do-dia, .marketplace-complete .delivery-stats, .marketplace-complete .user-actions, .marketplace-complete .admin-area, .marketplace-complete .map-area, .marketplace-complete .calendar, .marketplace-complete .benefits { display: flex; flex-wrap: wrap; gap: 32px; justify-content: center; width: 100%;}
.marketplace-complete .restaurant-card, .marketplace-complete .review-card, .marketplace-complete .faq-card, .marketplace-complete .partner-card, .marketplace-complete .blog-card, .marketplace-complete .destaque-card, .marketplace-complete .stat-card, .marketplace-complete .user-card, .marketplace-complete .admin-card, .marketplace-complete .map-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px #0001; padding: 24px; width: 220px; text-align: center;}
.marketplace-complete .restaurant-img, .marketplace-complete .partner-img, .marketplace-complete .blog-img, .marketplace-complete .destaque-img, .marketplace-complete .map-img { width: 100%; height: 130px; object-fit: cover; border-radius: 8px;}
.marketplace-complete .restaurant-card h3 { margin-top: 10px; margin-bottom: 3px;}
.marketplace-complete .restaurant-card p { font-size: .98rem; color: #555;}
.marketplace-complete .restaurant-card .tags span, .marketplace-complete .destaque-card .tags span { background: #f6f6f6; color: #333; border-radius: 20px; padding: 3px 12px; font-size: .79rem; margin: 2px; display: inline-block;}
.marketplace-complete .restaurant-card .rating, .marketplace-complete .destaque-card .rating { color: #2f9e44; font-weight: 600; margin-top: 8px;}
.marketplace-complete .skeleton-loading { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 24px; margin: 20px 0;}
.marketplace-complete .skeleton-card { background: #fff; border-radius: 12px; padding: 24px; animation: pulse 1.5s ease-in-out infinite;}
.marketplace-complete .skeleton-image { width: 100%; height: 130px; background: #e5e7eb; border-radius: 8px; margin-bottom: 12px;}
.marketplace-complete .skeleton-title { width: 70%; height: 20px; background: #e5e7eb; border-radius: 4px; margin-bottom: 8px;}
.marketplace-complete .skeleton-line { width: 100%; height: 14px; background: #e5e7eb; border-radius: 4px; margin-bottom: 6px;}
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
.marketplace-complete .review-card { width: 310px;}
.marketplace-complete .review-header { display: flex; align-items: center; gap: 15px; justify-content: center; margin-bottom: 8px;}
.marketplace-complete .avatar { width: 53px; height: 53px; border-radius: 50%; object-fit: cover;}
.marketplace-complete .faq-card { background: #f8f8f8; width: 430px; font-size: 1.06rem;}
.marketplace-complete .partners { gap: 20px;}
.marketplace-complete .partner-card { width: 180px; padding: 15px;}
.marketplace-complete .blog-card { width: 330px; padding: 20px; text-align: left;}
.marketplace-complete .blog-title { font-size: 1.16rem; font-weight: 600; margin: 9px 0 7px;}
.marketplace-complete .blog-author { font-size: .89rem; color: #333; margin-bottom: 7px;}
.marketplace-complete .blog-card p { font-size: .98rem; margin-bottom: 7px;}
.marketplace-complete .delivery-stats { gap: 16px;}
.marketplace-complete .stat-card { width: 170px;}
.marketplace-complete .stat-card i { font-size: 2.2rem; color: #2f9e44;}
.marketplace-complete .stat-card span { display: block; font-size: 1.22rem; font-weight: 600;}
.marketplace-complete .user-actions { background: #fff; border-radius: 12px; box-shadow: 0 1px 6px #0001; width: 320px; padding: 22px; flex-direction: column;}
.marketplace-complete .user-card { width: 100%; background: #eef; border: none;}
.marketplace-complete .user-card h4 { margin-bottom: 1px;}
.marketplace-complete .user-card p { margin-bottom: 9px;}
.marketplace-complete .admin-area { background: #fff; border-radius: 12px; padding: 26px 10px; width: 95%; box-shadow: 0 1px 6px #0001;}
.marketplace-complete .admin-card { width: 100%; background: #ffe; color: #333;}
.marketplace-complete .admin-card h4 { margin-bottom: 4px;}
.marketplace-complete .admin-card p { margin-bottom: 11px;}
.marketplace-complete .map-area { background: #fff; border-radius: 12px; box-shadow: 0 1px 8px #0002; padding: 24px; width: 96%;}
.marketplace-complete .map-card { width: 100%; background: transparent; padding: 3px;}
.marketplace-complete .map-img { height: 180px;}
.marketplace-complete .benefits-list { margin-top: 13px; display: flex; gap: 38px; justify-content: center;}
.marketplace-complete .benefit-item { background: #2f9e44; color: #fff; font-size: 1.3rem; border-radius: 60px; padding: 32px 22px; width: 182px;}
.marketplace-complete .app-promo { margin-top: 12px; align-items: center; display: flex; gap: 29px; justify-content: center;}
.marketplace-complete .app-promo img { width: 148px; height: 148px; border-radius: 18px;}
.marketplace-complete .newsletter { background: #262626; color: #fff; border-radius: 12px; width: 96%; margin: auto; padding: 35px 0; text-align: center;}
.marketplace-complete .newsletter input, .marketplace-complete .newsletter button { border: none; border-radius: 22px; padding: 13px 26px; margin: 6px;}
.marketplace-complete .newsletter input { width: 310px;}
.marketplace-complete .newsletter button { background: #2f9e44; color: #fff; font-weight: 700; cursor: pointer;}
.marketplace-complete .newsletter button:hover { background: #1e7e34;}
.marketplace-complete .calendar { background: #fff; border-radius: 12px; width: 98%; margin: auto; box-shadow: 0 2px 8px #0001; padding: 24px; text-align: center;}
.marketplace-complete .calendar-title { font-size: 1.35rem; color: #262626; margin-bottom: 6px;}
.marketplace-complete .calendar-list { margin-top: 18px; list-style: none; padding: 0;}
.marketplace-complete .calendar-list li { list-style: none; padding: 10px 0;}
.marketplace-complete .footer-social a { margin: 0 12px; color: #2f9e44; text-decoration: none; font-size: 1.4rem;}
.marketplace-complete .home-quick-filters { margin: 30px 0; padding: 0 4vw;}
.marketplace-complete .home-quick-filters__chips { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 15px;}
.marketplace-complete .home-quick-filters__actions { display: flex; align-items: center; gap: 15px;}
.marketplace-complete .home-quick-filters__count { color: #2f9e44; font-size: 0.9rem;}
.marketplace-complete footer { background: #262626; color: #fff; text-align: center; padding: 36px 0 22px 0; font-size: 1.09rem; margin-top: 50px;}
@media (max-width: 900px) { .marketplace-complete .container { width: 100%; } .marketplace-complete .search-bar { width: 100%; } .marketplace-complete nav { padding: 0 2vw;} }
@media (max-width: 720px) { .marketplace-complete .categories { gap: 18px; padding-bottom: 10px;} .marketplace-complete .category-card { min-width: 150px; padding: 15px;} .marketplace-complete .featured, .marketplace-complete .reviews, .marketplace-complete .faq, .marketplace-complete .partners, .marketplace-complete .blog, .marketplace-complete .app-promo, .marketplace-complete .newsletter, .marketplace-complete .delivery-stats, .marketplace-complete .user-actions, .marketplace-complete .admin-area, .marketplace-complete .map-area, .marketplace-complete .calendar, .marketplace-complete .benefits-list { flex-direction: column; gap: 18px;} .marketplace-complete .faq-card, .marketplace-complete .blog-card, .marketplace-complete .calendar, .marketplace-complete .benefit-item { width: 100%;} .marketplace-complete nav { flex-direction: column; gap: 14px;} }
</style>
<div class="marketplace-complete">
<header>
    <div class="container">
        <nav>
            <div class="logo"><i class="fas fa-utensils"></i> ' . esc_html( get_bloginfo( 'name' ) ) . '</div>
            <ul class="menu">
                <li><a href="' . esc_url( $home_url ) . '">' . esc_html__( 'Início', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $restaurants_url ) . '">' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</a></li>
                <li><a href="#">' . esc_html__( 'Categorias', 'vemcomer' ) . '</a></li>
                <li><a href="#">' . esc_html__( 'Blog', 'vemcomer' ) . '</a></li>
                <li><a href="#">' . esc_html__( 'Eventos', 'vemcomer' ) . '</a></li>
                <li><a href="#">' . esc_html__( 'Parceiros', 'vemcomer' ) . '</a></li>
                <li><a href="#">' . esc_html__( 'Sobre', 'vemcomer' ) . '</a></li>
                <li><a href="#">' . esc_html__( 'Ajuda', 'vemcomer' ) . '</a></li>
            </ul>
            <div class="account">
                <a href="' . esc_url( $login_url ) . '" class="account-btn">' . esc_html__( 'Login', 'vemcomer' ) . '</a>
                <a href="' . esc_url( $signup_url ) . '" class="account-btn">' . esc_html__( 'Registrar', 'vemcomer' ) . '</a>
            </div>
        </nav>
    </div>
</header>
<section class="hero">
    <div class="container">
        <h1 id="hero-title">' . esc_html__( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ) . '</h1>
        <p id="hero-subtitle">' . esc_html__( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ) . '</p>
        <form class="search-bar" method="get" action="#restaurants-list" id="hero-search-form">
            <div style="position: relative; flex: 1;">
                <input type="text" name="s" id="hero-search-input" placeholder="' . esc_attr__( 'Buscar restaurantes, pratos...', 'vemcomer' ) . '" value="' . esc_attr( get_query_var( 's' ) ) . '" autocomplete="off">
                <div class="search-autocomplete" id="search-autocomplete" style="display: none;"></div>
            </div>
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div id="hero-location-actions" style="margin: 20px 0;">
            <button class="btn-geolocation" id="vc-use-location" type="button">
                <span class="btn-geolocation__icon">📍</span>
                <span class="btn-geolocation__text">' . esc_html__( 'Usar minha localização', 'vemcomer' ) . '</span>
            </button>
        </div>
        <a href="#restaurants-list" class="account-btn" style="margin-top: 10px;">' . esc_html__( 'Explorar restaurantes', 'vemcomer' ) . '</a>
    </div>
</section>
' . ( function_exists( 'vemcomer_home_popular_categories' ) ? vemcomer_home_popular_categories() : '<section>
    <div class="section-title">' . esc_html__( 'Categorias Populares', 'vemcomer' ) . '</div>
    <div class="categories">
        <div class="category-card"><i class="fas fa-pizza-slice"></i><br>' . esc_html__( 'Pizza', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-hamburger"></i><br>' . esc_html__( 'Lanches', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-ice-cream"></i><br>' . esc_html__( 'Sobremesas', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-glass-cheers"></i><br>' . esc_html__( 'Bares', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-fish"></i><br>' . esc_html__( 'Frutos do Mar', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-utensil-spoon"></i><br>' . esc_html__( 'Comida Brasileira', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-seedling"></i><br>' . esc_html__( 'Vegetariana', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-drumstick-bite"></i><br>' . esc_html__( 'Churrasco', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-birthday-cake"></i><br>' . esc_html__( 'Café da manhã', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-egg"></i><br>' . esc_html__( 'Saudável', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-paw"></i><br>' . esc_html__( 'Pet Friendly', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-glass-martini"></i><br>' . esc_html__( 'Drinks', 'vemcomer' ) . '</div>
        <div class="category-card"><i class="fas fa-coffee"></i><br>' . esc_html__( 'Cafés', 'vemcomer' ) . '</div>
    </div>
</section>' ) . '
<section>
    <div class="section-title">' . esc_html__( 'Promoções e destaques', 'vemcomer' ) . '</div>
    <div class="destaques-do-dia">
        ' . do_shortcode( '[vc_banners]' ) . '
    </div>
</section>
' . ( function_exists( 'vemcomer_home_featured_restaurants' ) ? vemcomer_home_featured_restaurants() : '<section>
    <div class="section-title">' . esc_html__( 'Restaurantes em Destaque', 'vemcomer' ) . '</div>
    <div class="featured">
        ' . do_shortcode( '[vemcomer_restaurants per_page="3"]' ) . '
    </div>
</section>' ) . '
<section id="restaurants-list">
    <div class="section-title">' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</div>
    ' . ( function_exists( 'vemcomer_home_quick_filters' ) ? vemcomer_home_quick_filters() : '' ) . '
    <div id="restaurants-container" style="padding: 0 4vw;">
        <div class="skeleton-loading" id="skeleton-loading">
            ' . str_repeat( '<div class="skeleton-card"><div class="skeleton-image"></div><div class="skeleton-title"></div><div class="skeleton-line"></div><div class="skeleton-line"></div></div>', 6 ) . '
        </div>
        <div id="restaurants-content" style="display: none;">
            ' . do_shortcode( '[vemcomer_restaurants]' ) . '
        </div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Mapa de Restaurantes e Entregas', 'vemcomer' ) . '</div>
    <div class="map-area">
        <div class="map-card">
            <img src="https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=800&q=80" class="map-img" alt="' . esc_attr__( 'Mapa', 'vemcomer' ) . '">
            <p><i class="fas fa-map-marker-alt"></i> ' . esc_html__( 'Veja restaurantes por região, delivery ou para retirada', 'vemcomer' ) . '</p>
            <button class="account-btn">' . esc_html__( 'Ver no Mapa', 'vemcomer' ) . '</button>
        </div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Eventos & Agenda Gastronômica', 'vemcomer' ) . '</div>
    <div class="calendar">
        <div class="calendar-title"><i class="fas fa-calendar-alt"></i> ' . esc_html__( 'Próximos eventos', 'vemcomer' ) . '</div>
        <ul class="calendar-list">
            <li><b>' . esc_html__( 'Festival de Pizza', 'vemcomer' ) . '</b> - 01/12/2025 - Don Marco</li>
            <li><b>' . esc_html__( 'Noite de Hambúrguer', 'vemcomer' ) . '</b> - 12/12/2025 - Burguer Mania</li>
            <li><b>' . esc_html__( 'Feijoada Especial', 'vemcomer' ) . '</b> - 18/12/2025 - Sabor Brasil</li>
            <li><b>' . esc_html__( 'Happy Hour Vegano', 'vemcomer' ) . '</b> - 22/12/2025 - Veggie Life</li>
        </ul>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Parceiros & Serviços', 'vemcomer' ) . '</div>
    <div class="partners">
        <div class="partner-card">
            <img src="https://images.unsplash.com/photo-1508919801845-fc2ae1bc0a9c?auto=format&fit=crop&w=400&q=80" class="partner-img" alt="Food Express">
            <b>Food Express</b><br>' . esc_html__( 'Logística de entregas rápidas', 'vemcomer' ) . '
        </div>
        <div class="partner-card">
            <img src="https://images.unsplash.com/photo-1421987392252-0c7c1e44392b?auto=format&fit=crop&w=400&q=80" class="partner-img" alt="PagSeguro">
            <b>PagSeguro</b><br>' . esc_html__( 'Soluções de pagamento', 'vemcomer' ) . '
        </div>
        <div class="partner-card">
            <img src="https://images.unsplash.com/photo-1508919801845-fc2ae1bc0a9c?auto=format&fit=crop&w=400&q=80" class="partner-img" alt="Cuponzando">
            <b>Cuponzando</b><br>' . esc_html__( 'Cupons e descontos', 'vemcomer' ) . '
        </div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Estatísticas de Entregas', 'vemcomer' ) . '</div>
    <div class="delivery-stats">
        <div class="stat-card"><i class="fas fa-truck"></i> <span>612 ' . esc_html__( 'entregas hoje', 'vemcomer' ) . '</span></div>
        <div class="stat-card"><i class="fas fa-user-plus"></i> <span>72 ' . esc_html__( 'novos clientes', 'vemcomer' ) . '</span></div>
        <div class="stat-card"><i class="fas fa-coins"></i> <span>R$ 13k ' . esc_html__( 'em pedidos', 'vemcomer' ) . '</span></div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Ações do Usuário', 'vemcomer' ) . '</div>
    <div class="user-actions">
        <div class="user-card">
            <h4>' . esc_html__( 'Crie seu perfil', 'vemcomer' ) . '</h4>
            <p>' . esc_html__( 'Salve favoritos, acompanhe pedidos e ganhe recompensas!', 'vemcomer' ) . '</p>
            <button class="account-btn">' . esc_html__( 'Minha Conta', 'vemcomer' ) . '</button>
        </div>
        <div class="user-card">
            <h4>' . esc_html__( 'Comande seu Restaurante', 'vemcomer' ) . '</h4>
            <p>' . esc_html__( 'Cadastre seu estabelecimento e participe do marketplace.', 'vemcomer' ) . '</p>
            <button class="account-btn">' . esc_html__( 'Sou Restaurante', 'vemcomer' ) . '</button>
        </div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Área do Administrador', 'vemcomer' ) . '</div>
    <div class="admin-area">
        <div class="admin-card">
            <h4>' . esc_html__( 'Gestão de Cardápios', 'vemcomer' ) . '</h4>
            <p>' . esc_html__( 'Adicione, edite e destaque pratos em tempo real.', 'vemcomer' ) . '</p>
        </div>
        <div class="admin-card">
            <h4>' . esc_html__( 'Controle de Pedidos', 'vemcomer' ) . '</h4>
            <p>' . esc_html__( 'Monitore entregas, pagamentos e avaliações dos clientes.', 'vemcomer' ) . '</p>
        </div>
        <div class="admin-card">
            <h4>' . esc_html__( 'Relatórios & Exportação', 'vemcomer' ) . '</h4>
            <p>' . esc_html__( 'Acesse estatísticas, exporte vendas e analise o desempenho.', 'vemcomer' ) . '</p>
        </div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Newsletter', 'vemcomer' ) . '</div>
    <div class="newsletter">
        <h3>' . esc_html__( 'Receba novidades e ofertas!', 'vemcomer' ) . '</h3>
        <form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">
            <input type="hidden" name="action" value="vemcomer_subscribe_newsletter">
            <input type="email" name="email" placeholder="' . esc_attr__( 'Seu e-mail...', 'vemcomer' ) . '" required>
            <button type="submit"><i class="fas fa-paper-plane"></i> ' . esc_html__( 'Receber', 'vemcomer' ) . '</button>
        </form>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'FAQ - Perguntas Frequentes', 'vemcomer' ) . '</div>
    <div class="faq">
        <div class="faq-card">
            <b>' . esc_html__( 'Como faço um pedido?', 'vemcomer' ) . '</b><br>
            ' . esc_html__( 'Busque o restaurante, monte o pedido e clique em "Fazer Pedido".', 'vemcomer' ) . '
        </div>
        <div class="faq-card">
            <b>' . esc_html__( 'Métodos de pagamento?', 'vemcomer' ) . '</b><br>
            ' . esc_html__( 'Cartões, pix, carteiras digitais e voucher de cupons.', 'vemcomer' ) . '
        </div>
        <div class="faq-card">
            <b>' . esc_html__( 'Agendamento e reservas?', 'vemcomer' ) . '</b><br>
            ' . esc_html__( 'Restaurantes que aceitam reservas aparecem sinalizados.', 'vemcomer' ) . '
        </div>
        <div class="faq-card">
            <b>' . esc_html__( 'Pet friendly?', 'vemcomer' ) . '</b><br>
            ' . esc_html__( 'Veja restaurantes que aceitam pets na busca avançada.', 'vemcomer' ) . '
        </div>
        <div class="faq-card">
            <b>' . esc_html__( 'Posso retirar no local?', 'vemcomer' ) . '</b><br>
            ' . esc_html__( 'Sim, basta selecionar opção "Retirar".', 'vemcomer' ) . '
        </div>
        <div class="faq-card">
            <b>' . esc_html__( 'Sou restaurante, como entro?', 'vemcomer' ) . '</b><br>
            ' . esc_html__( 'Clique em "Sou Restaurante" e preencha o formulário de cadastro.', 'vemcomer' ) . '
        </div>
    </div>
</section>
<section>
    <div class="section-title">' . esc_html__( 'Blog & Dicas', 'vemcomer' ) . '</div>
    <div class="blog">
        <div class="blog-card">
            <img src="https://images.unsplash.com/photo-1543353071-873f17a7a088?auto=format&fit=crop&w=330&q=80" class="blog-img" alt="Blog">
            <div class="blog-title">' . esc_html__( 'Como escolher o restaurante ideal?', 'vemcomer' ) . '</div>
            <div class="blog-author"><i class="fas fa-user"></i> ' . esc_html__( 'Equipe Marketplace', 'vemcomer' ) . '</div>
            <p>' . esc_html__( 'Dicas para garantir bons momentos com família, casal ou trabalho!', 'vemcomer' ) . '</p>
            <a href="#" style="color:#2f9e44;text-decoration:underline;">' . esc_html__( 'Leia mais', 'vemcomer' ) . '</a>
        </div>
        <div class="blog-card">
            <img src="https://images.unsplash.com/photo-1464306076886-debede5e0038?auto=format&fit=crop&w=330&q=80" class="blog-img" alt="Blog">
            <div class="blog-title">' . esc_html__( 'Restaurantes com desconto este mês', 'vemcomer' ) . '</div>
            <div class="blog-author"><i class="fas fa-user"></i> ' . esc_html__( 'Equipe Marketplace', 'vemcomer' ) . '</div>
            <p>' . esc_html__( 'Aproveite ofertas e promoções imperdíveis na sua cidade!', 'vemcomer' ) . '</p>
            <a href="#" style="color:#2f9e44;text-decoration:underline;">' . esc_html__( 'Leia mais', 'vemcomer' ) . '</a>
        </div>
    </div>
</section>
<section>
    <div class="app-promo">
        <img src="https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=148&q=80" alt="App">
        <div>
            <div class="section-title" style="text-align:left;">' . esc_html__( 'Baixe o App!', 'vemcomer' ) . '</div>
            <p>' . esc_html__( 'Notificações, promoções, pedidos rápidos e experiência personalizada.', 'vemcomer' ) . '</p>
            <a href="#" class="account-btn"><i class="fab fa-android"></i> Google Play</a>
            <a href="#" class="account-btn"><i class="fab fa-apple"></i> App Store</a>
        </div>
    </div>
</section>
<footer>
    <div class="container">
        &copy; ' . date( 'Y' ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . ' &mdash; ' . esc_html__( 'Seu universo gastronômico online!', 'vemcomer' ) . '<br>
        <div class="footer-social">
            <a href="#"><i class="fab fa-facebook-square"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
        </div>
        <span>
            <a href="#" style="color:#2f9e44; text-decoration:underline;">' . esc_html__( 'Política de Privacidade', 'vemcomer' ) . '</a> |
            <a href="#" style="color:#2f9e44; text-decoration:underline;">' . esc_html__( 'Termos de Uso', 'vemcomer' ) . '</a> |
            <a href="#" style="color:#2f9e44; text-decoration:underline;">' . esc_html__( 'Contato', 'vemcomer' ) . '</a>
        </span>
    </div>
</footer>
</div>
<script>
(function() {
    // Carregar scripts da home se disponíveis
    if (typeof window.vemcomerTheme !== \'undefined\') {
        // Inicializar funcionalidades quando DOM estiver pronto
        if (document.readyState === \'loading\') {
            document.addEventListener(\'DOMContentLoaded\', initMarketplaceFeatures);
        } else {
            initMarketplaceFeatures();
        }
    }
    
    function initMarketplaceFeatures() {
        // Inicializar geolocalização
        const locationBtn = document.getElementById(\'vc-use-location\');
        if (locationBtn && typeof window.VemComerReverseGeocode !== \'undefined\') {
            locationBtn.addEventListener(\'click\', function() {
                if (!navigator.geolocation) {
                    alert(\'Geolocalização não suportada pelo seu navegador.\');
                    return;
                }
                locationBtn.classList.add(\'is-loading\');
                locationBtn.disabled = true;
                
                window.VemComerReverseGeocode.getLocationAndFill({
                    fillCheckout: false,
                    onSuccess: function(address, coordinates) {
                        const heroTitle = document.getElementById(\'hero-title\');
                        const heroSubtitle = document.getElementById(\'hero-subtitle\');
                        if (heroTitle && address.city) {
                            heroTitle.textContent = \'Peça dos melhores restaurantes de \' + address.city;
                        }
                        if (window.updateHeroSubtitleWithRestaurantCount && address.city) {
                            window.updateHeroSubtitleWithRestaurantCount(address.city);
                        }
                        if (window.filterRestaurantsByCity && address.city) {
                            window.filterRestaurantsByCity(address.city);
                        }
                        locationBtn.classList.remove(\'is-loading\');
                        locationBtn.disabled = false;
                    },
                    onError: function(error) {
                        locationBtn.classList.remove(\'is-loading\');
                        locationBtn.disabled = false;
                        alert(\'Não foi possível obter sua localização.\');
                    }
                });
            });
        }
        
        // Inicializar busca com autocomplete
        const searchInput = document.getElementById(\'hero-search-input\');
        const autocomplete = document.getElementById(\'search-autocomplete\');
        if (searchInput && autocomplete && typeof window.initSearchAutocomplete === \'function\') {
            window.initSearchAutocomplete(searchInput, autocomplete);
        }
        
        // Inicializar skeleton loading
        const skeleton = document.getElementById(\'skeleton-loading\');
        const restaurantsContent = document.getElementById(\'restaurants-content\');
        if (skeleton && restaurantsContent) {
            setTimeout(function() {
                skeleton.style.display = \'none\';
                restaurantsContent.style.display = \'block\';
            }, 1000);
        }
        
        // Verificar se já tem localização salva
        const savedCity = localStorage.getItem(\'vc_user_city\');
        if (savedCity) {
            const heroTitle = document.getElementById(\'hero-title\');
            if (heroTitle) {
                heroTitle.textContent = \'Peça dos melhores restaurantes de \' + savedCity;
            }
        }
    }
})();
</script>
<!-- /wp:html -->';
    }
    
    /**
     * Cria ou atualiza uma página a partir de um template
     * 
     * @param string $template_key Chave do template
     * @param array $options Opções adicionais (parent, menu_order, etc.)
     * @return int|WP_Error ID da página criada ou erro
     */
    public static function create_page_from_template( string $template_key, array $options = [] ): int {
        $templates = self::get_templates();
        
        if ( ! isset( $templates[ $template_key ] ) ) {
            return new \WP_Error( 'invalid_template', __( 'Template inválido.', 'vemcomer' ) );
        }
        
        // Carregar Font Awesome se necessário
        if ( $template_key === 'marketplace_complete' && ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
            wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2' );
        }
        
        $template = $templates[ $template_key ];
        
        // Verificar se a página já existe pelo slug
        $existing = get_page_by_path( $template['slug'] );
        if ( $existing ) {
            // Atualizar página existente
            $post_data = [
                'ID'           => $existing->ID,
                'post_title'   => $template['title'],
                'post_content' => $template['content'],
                'post_status'  => $options['status'] ?? 'publish',
            ];
            
            if ( ! empty( $template['template'] ) ) {
                update_post_meta( $existing->ID, '_wp_page_template', $template['template'] );
            }
            
            $page_id = wp_update_post( $post_data, true );
        } else {
            // Criar nova página
            $post_data = [
                'post_title'   => $template['title'],
                'post_name'    => $template['slug'],
                'post_content' => $template['content'],
                'post_status'  => $options['status'] ?? 'publish',
                'post_type'    => 'page',
                'post_author'  => get_current_user_id(),
            ];
            
            if ( isset( $options['parent'] ) ) {
                $post_data['post_parent'] = (int) $options['parent'];
            }
            
            if ( isset( $options['menu_order'] ) ) {
                $post_data['menu_order'] = (int) $options['menu_order'];
            }
            
            $page_id = wp_insert_post( $post_data, true );
            
            if ( ! is_wp_error( $page_id ) && ! empty( $template['template'] ) ) {
                update_post_meta( $page_id, '_wp_page_template', $template['template'] );
            }
        }
        
        return $page_id;
    }
    
    /**
     * Importa múltiplas páginas de uma vez
     * 
     * @param array $template_keys Array de chaves de templates para importar
     * @return array Array com resultados (sucesso/erro) para cada template
     */
    public static function import_pages( array $template_keys ): array {
        $results = [];
        
        foreach ( $template_keys as $key ) {
            $result = self::create_page_from_template( $key );
            
            if ( is_wp_error( $result ) ) {
                $results[ $key ] = [
                    'success' => false,
                    'error'   => $result->get_error_message(),
                ];
            } else {
                $results[ $key ] = [
                    'success' => true,
                    'page_id' => $result,
                    'url'     => get_permalink( $result ),
                ];
            }
        }
        
        return $results;
    }
}

