<?php
/**
 * Page_Templates — Templates de páginas pré-configuradas para importação
 * Similar aos temas premium que têm páginas prontas para importar
 *
 * @package VemComerCore
 */

namespace VC\Admin;

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
        return [
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
.marketplace-complete { font-family: \'Segoe UI\', Arial, sans-serif; background: #f8f8f8; margin: 0; padding: 0;}
.marketplace-complete header { background: #222; color: #fff; padding: 20px 0;}
.marketplace-complete .container { width: 93%; max-width: 1350px; margin: auto;}
.marketplace-complete nav { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;}
.marketplace-complete .logo { font-size: 2.5rem; font-weight: bold;}
.marketplace-complete .menu { list-style: none; display: flex; gap: 28px; flex-wrap: wrap;}
.marketplace-complete .menu li { display: inline;}
.marketplace-complete .menu a { color: #fff; text-decoration: none; font-weight: 500; transition: color 0.3s;}
.marketplace-complete .menu a:hover { color: #F4972E;}
.marketplace-complete .account {display:flex; gap: 10px;}
.marketplace-complete .account-btn { background: #F4972E; color: #fff; padding: 10px 26px; border-radius: 26px; text-decoration: none; font-weight:500; border:none; transition:.2s; cursor: pointer; display: inline-block;}
.marketplace-complete .account-btn:hover { background: #c85d1b;}
.marketplace-complete .hero { background: linear-gradient(0deg, rgba(38,38,38,0.55) 0%, rgba(38,38,38,0.27) 100%), url(\'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=1400&q=80\') center/cover no-repeat; padding: 105px 0 125px 0; color: #fff; text-align: center;}
.marketplace-complete .hero h1 { font-size: 3.6rem; margin-bottom: 21px;}
.marketplace-complete .hero p { font-size: 1.7rem; margin-bottom: 38px;}
.marketplace-complete .search-bar {margin: auto;width: 54%;display: flex; background: #fff; border-radius: 54px; overflow: hidden; box-shadow: 0 4px 16px #0003;}
.marketplace-complete .search-bar input { flex: 1; border:none; padding: 21px 28px; font-size: 1.24rem;}
.marketplace-complete .search-bar button {background: #F4972E; color: #fff; border:none; padding: 0 36px; font-size:1.29rem; cursor:pointer;}
.marketplace-complete .search-bar button:hover {background:#c85d1b;}
.marketplace-complete .quick-filters { display: flex; gap: 20px; justify-content:center; margin:31px 0; flex-wrap: wrap;}
.marketplace-complete .quick-filters button {background:#fff; color:#F4972E; border: 1px solid #F4972E;border-radius:22px;padding:10px 21px;cursor:pointer; transition: all 0.3s;}
.marketplace-complete .quick-filters button:hover { background: #F4972E;color:#fff;}
.marketplace-complete .section-title { font-size: 2.2rem; color: #222; margin: 39px 0 28px 0; text-align:center; letter-spacing:.8px;}
.marketplace-complete section { margin: 38px 0;}
.marketplace-complete .main-grid { display: grid; grid-template-columns: 60% 36%; gap: 4%;}
.marketplace-complete .categories, .marketplace-complete .featured, .marketplace-complete .reviews, .marketplace-complete .faq, .marketplace-complete .partners, .marketplace-complete .blog, .marketplace-complete .app-promo, .marketplace-complete .newsletter, .marketplace-complete .destaques-do-dia, .marketplace-complete .delivery-stats, .marketplace-complete .user-actions, .marketplace-complete .admin-area, .marketplace-complete .map-area, .marketplace-complete .calendar, .marketplace-complete .benefits, .marketplace-complete .premium, .marketplace-complete .ranking, .marketplace-complete .plan, .marketplace-complete .responsive-feature { display: flex; flex-wrap: wrap; gap: 34px; justify-content: center;}
.marketplace-complete .category-card, .marketplace-complete .restaurant-card, .marketplace-complete .review-card, .marketplace-complete .faq-card, .marketplace-complete .partner-card, .marketplace-complete .blog-card, .marketplace-complete .destaque-card, .marketplace-complete .stat-card, .marketplace-complete .user-card, .marketplace-complete .admin-card, .marketplace-complete .map-card, .marketplace-complete .premium-card, .marketplace-complete .ranking-card, .marketplace-complete .plan-card {background:#fff; border-radius:13px; box-shadow:0 2px 8px #0001; padding:26px; width:235px;text-align:center;}
.marketplace-complete .category-card i { font-size:2.6rem; margin-bottom:12px; color:#F4972E;}
.marketplace-complete .restaurant-img, .marketplace-complete .partner-img, .marketplace-complete .blog-img, .marketplace-complete .destaque-img, .marketplace-complete .map-img, .marketplace-complete .premium-img, .marketplace-complete .ranking-img {width:100%; height:138px; object-fit:cover; border-radius:10px;}
.marketplace-complete .restaurant-card h3 {margin-top:11px;margin-bottom:4px;}
.marketplace-complete .restaurant-card p {font-size:1.03rem;color:#555;}
.marketplace-complete .restaurant-card .tags span, .marketplace-complete .destaque-card .tags span, .marketplace-complete .premium-card .tags span {background:#f6f6f6;color:#333;border-radius:20px; padding:4px 13px; font-size:.84rem; margin: 2px; display: inline-block;}
.marketplace-complete .restaurant-card .rating, .marketplace-complete .destaque-card .rating {color:#F4972E;font-weight:600;margin-top:10px;}
.marketplace-complete .review-card{width:335px;}
.marketplace-complete .avatar{width:56px;height:56px;border-radius:50%;object-fit:cover;}
.marketplace-complete .faq-card{background: #f8f8f8;width:450px; font-size:1.06rem;}
.marketplace-complete .main-grid aside{display:flex;flex-direction:column;gap:36px;}
.marketplace-complete .partners{gap:20px;}
.marketplace-complete .partner-card{width:190px;padding:20px;}
.marketplace-complete .premium{gap:18px;}
.marketplace-complete .premium-card{width:260px;}
.marketplace-complete .premium-card .tags{margin:4px 0;}
.marketplace-complete .premium-card .rating{color:#FFD700;}
.marketplace-complete .plan{gap:16px;}
.marketplace-complete .plan-card{background:#F4972E;color:#fff;width:235px;}
.marketplace-complete .plan-card h4{margin-bottom:7px;}
.marketplace-complete .ranking{gap:14px;}
.marketplace-complete .ranking-card{width:200px;}
.marketplace-complete .ranking-card .rating{color:#2e8540;}
.marketplace-complete .responsive-feature{background:#ffe6d1;width:97%;text-align:center;padding:24px;font-size:1.13rem;border-radius:11px;}
.marketplace-complete .benefits-list{display:flex;gap:39px;justify-content:center;}
.marketplace-complete .benefit-item{background:#F4972E;color:#fff;font-size:1.3rem;border-radius:65px;padding:34px 24px;width:192px;}
.marketplace-complete .app-promo {margin-top:16px;align-items:center; display: flex;}
.marketplace-complete .app-promo img {width:155px;height:155px;border-radius:19px;}
.marketplace-complete .newsletter {background:#222;color:#fff; border-radius:13px; width:96%; margin:auto; padding:41px 0;text-align:center;}
.marketplace-complete .newsletter input, .marketplace-complete .newsletter button {border:none; border-radius:23px;padding:14px 27px; margin:7px;}
.marketplace-complete .newsletter input {width:330px;}
.marketplace-complete .newsletter button {background:#F4972E;color:#fff;font-weight:700;cursor:pointer;}
.marketplace-complete .newsletter button:hover {background:#c85d1b;}
.marketplace-complete .calendar{background:#fff;border-radius:13px;width:99%;margin:auto;box-shadow:0 2px 8px #0001;padding:26px; text-align:center;}
.marketplace-complete .calendar-title{font-size:1.38rem;color:#222;margin-bottom:5px;}
.marketplace-complete .calendar-list{margin-top:21px; list-style: none; padding: 0;}
.marketplace-complete .calendar-list li{list-style:none;padding:11px 0;}
.marketplace-complete .footer-social a{margin:0 12px;color:#F4972E;text-decoration:none;font-size:1.5rem;}
.marketplace-complete footer{background:#222;color:#fff;text-align:center;padding:39px 0 23px 0;font-size:1.12rem;margin-top:53px;}
@media (max-width: 920px) { .marketplace-complete .container{width:98%;} .marketplace-complete .search-bar{width:100%;} }
@media (max-width: 730px) {.marketplace-complete .container{width:100%;}.marketplace-complete .main-grid{grid-template-columns:100%;}.marketplace-complete .categories, .marketplace-complete .featured, .marketplace-complete .reviews, .marketplace-complete .faq, .marketplace-complete .partners, .marketplace-complete .blog, .marketplace-complete .app-promo, .marketplace-complete .newsletter, .marketplace-complete .destaques-do-dia, .marketplace-complete .delivery-stats, .marketplace-complete .user-actions, .marketplace-complete .admin-area, .marketplace-complete .map-area, .marketplace-complete .calendar, .marketplace-complete .benefits-list, .marketplace-complete .premium, .marketplace-complete .plan, .marketplace-complete .ranking, .marketplace-complete .responsive-feature{flex-direction:column;gap:18px;}.marketplace-complete .faq-card, .marketplace-complete .blog-card, .marketplace-complete .calendar, .marketplace-complete .benefit-item{width:100%;}}
</style>
<div class="marketplace-complete">
<header>
    <div class="container">
        <nav>
            <div class="logo"><i class="fas fa-utensils"></i> ' . esc_html( get_bloginfo( 'name' ) ) . '</div>
            <ul class="menu">
                <li><a href="' . esc_url( $home_url ) . '">' . esc_html__( 'Início', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $restaurants_url ) . '">' . esc_html__( 'Restaurantes', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#categorias">' . esc_html__( 'Categorias', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#ranking">' . esc_html__( 'Ranking', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#blog">' . esc_html__( 'Blog', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#eventos">' . esc_html__( 'Eventos', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#parceiros">' . esc_html__( 'Parceiros', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#premium">' . esc_html__( 'Premium', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#planos">' . esc_html__( 'Planos', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#sobre">' . esc_html__( 'Sobre', 'vemcomer' ) . '</a></li>
                <li><a href="' . esc_url( $home_url ) . '#ajuda">' . esc_html__( 'Ajuda', 'vemcomer' ) . '</a></li>
            </ul>
            <div class="account">' . 
                ( is_user_logged_in() 
                    ? '<a href="' . esc_url( admin_url() ) . '" class="account-btn">' . esc_html__( 'Painel', 'vemcomer' ) . '</a>'
                    : '<a href="' . esc_url( $login_url ) . '" class="account-btn">' . esc_html__( 'Login', 'vemcomer' ) . '</a>'
                ) . '
                <a href="' . esc_url( $signup_url ) . '" class="account-btn">' . esc_html__( 'Registrar', 'vemcomer' ) . '</a>
            </div>
        </nav>
    </div>
</header>
<section class="hero">
    <div class="container">
        <h1>' . esc_html__( 'Descubra, compare e peça dos melhores restaurantes, bares e deliverys!', 'vemcomer' ) . '</h1>
        <p>' . esc_html__( 'Delivery, reservas, cupons, ranking, premium, eventos, mapa, estatísticas e muito mais em um só lugar.', 'vemcomer' ) . '</p>
        <form class="search-bar" method="get" action="' . esc_url( $restaurants_url ) . '">
            <input type="text" name="s" placeholder="' . esc_attr__( 'Busque restaurante, prato, bairro, ranking, evento, premium...', 'vemcomer' ) . '">
            <button type="submit"><i class="fas fa-search"></i></button>
        </form>
        <div class="quick-filters">
            <button data-filter="open_now"><i class="fas fa-clock"></i> ' . esc_html__( 'Aberto agora', 'vemcomer' ) . '</button>
            <button data-filter="top_rated"><i class="fas fa-star"></i> ' . esc_html__( 'Mais avaliados', 'vemcomer' ) . '</button>
            <button data-filter="free_delivery"><i class="fas fa-shipping-fast"></i> ' . esc_html__( 'Frete grátis', 'vemcomer' ) . '</button>
            <button data-filter="promo"><i class="fas fa-percent"></i> ' . esc_html__( 'Promoção', 'vemcomer' ) . '</button>
            <button data-filter="kids"><i class="fas fa-child"></i> ' . esc_html__( 'Kids', 'vemcomer' ) . '</button>
            <button data-filter="events"><i class="fas fa-birthday-cake"></i> ' . esc_html__( 'Eventos', 'vemcomer' ) . '</button>
            <button data-filter="healthy"><i class="fas fa-leaf"></i> ' . esc_html__( 'Saudável', 'vemcomer' ) . '</button>
            <button data-filter="pet_friendly"><i class="fas fa-paw"></i> ' . esc_html__( 'Pet Friendly', 'vemcomer' ) . '</button>
            <button data-filter="bars"><i class="fas fa-glass-cheers"></i> ' . esc_html__( 'Bares', 'vemcomer' ) . '</button>
            <button data-filter="premium"><i class="fas fa-crown"></i> ' . esc_html__( 'Premium', 'vemcomer' ) . '</button>
            <button data-filter="ranking"><i class="fas fa-chart-line"></i> ' . esc_html__( 'Ranking', 'vemcomer' ) . '</button>
        </div>
    </div>
</section>
<div class="container main-grid">
    <div>
        <section id="categorias">
            <div class="section-title">' . esc_html__( 'Categorias Populares', 'vemcomer' ) . '</div>
            <div class="categories">
                <div class="category-card"><i class="fas fa-pizza-slice"></i><br>' . esc_html__( 'Pizza', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-hamburger"></i><br>' . esc_html__( 'Lanches', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-ice-cream"></i><br>' . esc_html__( 'Sobremesas', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-glass-cheers"></i><br>' . esc_html__( 'Bares', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-fish"></i><br>' . esc_html__( 'Frutos do Mar', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-utensil-spoon"></i><br>' . esc_html__( 'Brasileira', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-seedling"></i><br>' . esc_html__( 'Vegetariana', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-drumstick-bite"></i><br>' . esc_html__( 'Churrasco', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-birthday-cake"></i><br>' . esc_html__( 'Café da manhã', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-egg"></i><br>' . esc_html__( 'Saudável', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-paw"></i><br>' . esc_html__( 'Pet Friendly', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-glass-martini"></i><br>' . esc_html__( 'Drinks', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-coffee"></i><br>' . esc_html__( 'Cafés', 'vemcomer' ) . '</div>
                <div class="category-card"><i class="fas fa-leaf"></i><br>' . esc_html__( 'Vegano', 'vemcomer' ) . '</div>
            </div>
        </section>
        <section>
            <div class="section-title">' . esc_html__( 'Destaques do Dia', 'vemcomer' ) . '</div>
            <div class="destaques-do-dia">
                ' . do_shortcode( '[vc_banners limit="2"]' ) . '
            </div>
        </section>
        <section>
            <div class="section-title">' . esc_html__( 'Restaurantes em Destaque', 'vemcomer' ) . '</div>
            <div class="featured">
                ' . do_shortcode( '[vemcomer_restaurants per_page="3"]' ) . '
            </div>
        </section>
        <section id="ranking">
            <div class="section-title">' . esc_html__( 'Ranking dos Melhores', 'vemcomer' ) . '</div>
            <div class="ranking">
                ' . do_shortcode( '[vemcomer_restaurants per_page="2" orderby="rating"]' ) . '
            </div>
        </section>
        <section id="premium">
            <div class="section-title">' . esc_html__( 'Premium', 'vemcomer' ) . '</div>
            <div class="premium">
                ' . do_shortcode( '[vemcomer_restaurants per_page="1" meta_key="_vc_featured" meta_value="1"]' ) . '
            </div>
        </section>
        <section>
            <div class="section-title">' . esc_html__( 'Mapa de Restaurantes e Entregas', 'vemcomer' ) . '</div>
            <div class="map-area">
                <div class="map-card">
                    ' . do_shortcode( '[vc_restaurants_map]' ) . '
                    <p><i class="fas fa-map-marker-alt"></i> ' . esc_html__( 'Veja restaurantes por região, delivery ou retirada', 'vemcomer' ) . '</p>
                </div>
            </div>
        </section>
        <section id="eventos">
            <div class="section-title">' . esc_html__( 'Eventos & Agenda Gastronômica', 'vemcomer' ) . '</div>
            <div class="calendar">
                <div class="calendar-title"><i class="fas fa-calendar-alt"></i> ' . esc_html__( 'Próximos eventos', 'vemcomer' ) . '</div>
                <ul class="calendar-list">
                    <li><b>' . esc_html__( 'Festival de Pizza', 'vemcomer' ) . '</b> - ' . date_i18n( 'd/m/Y', strtotime( '+7 days' ) ) . '</li>
                    <li><b>' . esc_html__( 'Noite de Hambúrguer', 'vemcomer' ) . '</b> - ' . date_i18n( 'd/m/Y', strtotime( '+14 days' ) ) . '</li>
                    <li><b>' . esc_html__( 'Feijoada Especial', 'vemcomer' ) . '</b> - ' . date_i18n( 'd/m/Y', strtotime( '+21 days' ) ) . '</li>
                    <li><b>' . esc_html__( 'Happy Hour Vegano', 'vemcomer' ) . '</b> - ' . date_i18n( 'd/m/Y', strtotime( '+28 days' ) ) . '</li>
                </ul>
            </div>
        </section>
        <section id="parceiros">
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
            </div>
        </section>
        <section id="blog">
            <div class="section-title">' . esc_html__( 'Blog & Dicas', 'vemcomer' ) . '</div>
            <div class="blog">
                <div class="blog-card">
                    <img src="https://images.unsplash.com/photo-1543353071-873f17a7a088?auto=format&fit=crop&w=330&q=80" class="blog-img" alt="Blog">
                    <div class="blog-title">' . esc_html__( 'Como escolher o restaurante ideal?', 'vemcomer' ) . '</div>
                    <div class="blog-author"><i class="fas fa-user"></i> ' . esc_html__( 'Equipe Marketplace', 'vemcomer' ) . '</div>
                    <p>' . esc_html__( 'Dicas para garantir bons momentos!', 'vemcomer' ) . '</p>
                    <a href="' . esc_url( $home_url ) . '" style="color:#F4972E;text-decoration:underline;">' . esc_html__( 'Leia mais', 'vemcomer' ) . '</a>
                </div>
            </div>
        </section>
        <section class="responsive-feature">
            <b>' . esc_html__( 'Marketplace totalmente responsivo:', 'vemcomer' ) . '</b> ' . esc_html__( 'funciona em smartphones, tablets, e computadores!', 'vemcomer' ) . '
        </section>
    </div>
    <aside>
        <section>
            <div class="section-title">' . esc_html__( 'Estatísticas de Entregas', 'vemcomer' ) . '</div>
            <div class="delivery-stats">
                <div class="stat-card"><i class="fas fa-truck"></i> <span>' . esc_html__( 'Entregas hoje', 'vemcomer' ) . '</span></div>
                <div class="stat-card"><i class="fas fa-user-plus"></i> <span>' . esc_html__( 'Novos clientes', 'vemcomer' ) . '</span></div>
                <div class="stat-card"><i class="fas fa-coins"></i> <span>' . esc_html__( 'Em pedidos', 'vemcomer' ) . '</span></div>
                <div class="stat-card"><i class="fas fa-utensils"></i> <span>' . esc_html__( 'Restaurantes ativos', 'vemcomer' ) . '</span></div>
            </div>
        </section>
        <section>
            <div class="section-title">' . esc_html__( 'Ações do Usuário', 'vemcomer' ) . '</div>
            <div class="user-actions">
                <div class="user-card">
                    <h4>' . esc_html__( 'Crie seu perfil', 'vemcomer' ) . '</h4>
                    <p>' . esc_html__( 'Salve favoritos, acompanhe pedidos, ganhe recompensas!', 'vemcomer' ) . '</p>
                    ' . ( is_user_logged_in() 
                        ? '<a href="' . esc_url( admin_url() ) . '" class="account-btn">' . esc_html__( 'Minha Conta', 'vemcomer' ) . '</a>'
                        : '<a href="' . esc_url( $login_url ) . '" class="account-btn">' . esc_html__( 'Login', 'vemcomer' ) . '</a>'
                    ) . '
                </div>
                <div class="user-card">
                    <h4>' . esc_html__( 'Seja um Restaurante', 'vemcomer' ) . '</h4>
                    <p>' . esc_html__( 'Cadastre seu estabelecimento agora.', 'vemcomer' ) . '</p>
                    <a href="' . esc_url( home_url( '/cadastre-seu-restaurante/' ) ) . '" class="account-btn">' . esc_html__( 'Sou Restaurante', 'vemcomer' ) . '</a>
                </div>
            </div>
        </section>
        <section>
            <div class="section-title">' . esc_html__( 'Área do Administrador', 'vemcomer' ) . '</div>
            <div class="admin-area">
                <div class="admin-card">
                    <h4>' . esc_html__( 'Gestão de Pratos', 'vemcomer' ) . '</h4>
                    <p>' . esc_html__( 'Adicione, edite e destaque pratos.', 'vemcomer' ) . '</p>
                </div>
                <div class="admin-card">
                    <h4>' . esc_html__( 'Controle de Pedidos', 'vemcomer' ) . '</h4>
                    <p>' . esc_html__( 'Monitore entregas, pagamentos, avaliações.', 'vemcomer' ) . '</p>
                </div>
                <div class="admin-card">
                    <h4>' . esc_html__( 'Relatórios & Exportação', 'vemcomer' ) . '</h4>
                    <p>' . esc_html__( 'Estatísticas, vendas e desempenho.', 'vemcomer' ) . '</p>
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
        <section id="planos">
            <div class="section-title">' . esc_html__( 'Planos para Restaurantes', 'vemcomer' ) . '</div>
            <div class="plan">
                <div class="plan-card">
                    <h4>Free</h4>
                    <p>' . esc_html__( 'Inclui cadastro, pedidos online, avaliações.', 'vemcomer' ) . '</p>
                </div>
                <div class="plan-card">
                    <h4>Premium</h4>
                    <p>' . esc_html__( 'Destaque na home, cupons exclusivos, relatórios completos.', 'vemcomer' ) . '</p>
                </div>
                <div class="plan-card">
                    <h4>Enterprise</h4>
                    <p>' . esc_html__( 'Personalização avançada e integração API.', 'vemcomer' ) . '</p>
                </div>
            </div>
        </section>
        <section id="ajuda">
            <div class="section-title">' . esc_html__( 'FAQ', 'vemcomer' ) . '</div>
            <div class="faq">
                <div class="faq-card">
                    <b>' . esc_html__( 'Como pedir?', 'vemcomer' ) . '</b><br>' . esc_html__( 'Escolha o restaurante e finalize seu pedido.', 'vemcomer' ) . '
                </div>
                <div class="faq-card">
                    <b>' . esc_html__( 'Métodos de pagamento?', 'vemcomer' ) . '</b><br>' . esc_html__( 'Cartão, Pix, carteiras digitais, cupons.', 'vemcomer' ) . '
                </div>
                <div class="faq-card">
                    <b>' . esc_html__( 'Reservas?', 'vemcomer' ) . '</b><br>' . esc_html__( 'Restaurantes que aceitam reserva são sinalizados.', 'vemcomer' ) . '
                </div>
                <div class="faq-card">
                    <b>' . esc_html__( 'Pet friendly?', 'vemcomer' ) . '</b><br>' . esc_html__( 'Utilize o filtro "Pet Friendly".', 'vemcomer' ) . '
                </div>
                <div class="faq-card">
                    <b>' . esc_html__( 'Retirada?', 'vemcomer' ) . '</b><br>' . esc_html__( 'Selecione agência "Retirar".', 'vemcomer' ) . '
                </div>
            </div>
        </section>
    </aside>
</div>
<section>
    <div class="container app-promo" style="gap:30px;">
        <img src="https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=155&q=80" alt="App">
        <div>
            <div class="section-title" style="text-align:left;">' . esc_html__( 'Baixe o App!', 'vemcomer' ) . '</div>
            <p>' . esc_html__( 'Pedidos rápidos e experiência personalizada! Notificações e cupons exclusivos.', 'vemcomer' ) . '</p>
            <a href="#" class="account-btn"><i class="fab fa-android"></i> Google Play</a>
            <a href="#" class="account-btn"><i class="fab fa-apple"></i> App Store</a>
        </div>
    </div>
</section>
<footer id="sobre">
    <div class="container">
        &copy; ' . date( 'Y' ) . ' ' . esc_html( get_bloginfo( 'name' ) ) . ' &mdash; ' . esc_html__( 'Transformando experiências gastronômicas!', 'vemcomer' ) . '<br>
        <div class="footer-social">
            <a href="#"><i class="fab fa-facebook-square"></i></a>
            <a href="#"><i class="fab fa-instagram"></i></a>
            <a href="#"><i class="fab fa-whatsapp"></i></a>
            <a href="#"><i class="fab fa-twitter"></i></a>
            <a href="#"><i class="fab fa-youtube"></i></a>
        </div>
        <span>
            <a href="' . esc_url( home_url( '/politica-de-privacidade/' ) ) . '" style="color:#F4972E; text-decoration:underline;">' . esc_html__( 'Política de Privacidade', 'vemcomer' ) . '</a> |
            <a href="' . esc_url( home_url( '/termos-de-uso/' ) ) . '" style="color:#F4972E; text-decoration:underline;">' . esc_html__( 'Termos de Uso', 'vemcomer' ) . '</a> |
            <a href="' . esc_url( home_url( '/contato/' ) ) . '" style="color:#F4972E; text-decoration:underline;">' . esc_html__( 'Contato', 'vemcomer' ) . '</a>
        </span>
    </div>
</footer>
</div>
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

