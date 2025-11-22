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

