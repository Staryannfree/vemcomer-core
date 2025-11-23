<?php
/**
 * Template da página inicial - Sistema Modular
 * 
 * Este template lê as configurações do banco de dados e renderiza
 * apenas as seções ativadas pelo administrador.
 *
 * @package VemComer
 */

get_header();

// Carregar configurações da home
$options = get_option( 'vemcomer_home_options', [] );

// Se não houver configurações salvas, usar padrões
if ( empty( $options ) ) {
    $options = [
        'hero_section' => [
            'ativo'    => true,
            'titulo'   => __( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ),
            'subtitulo' => __( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ),
        ],
        'banners_section' => [
            'ativo'     => true,
            'quantidade' => 5,
        ],
        'daily_highlights_section' => [
            'ativo'     => true,
            'titulo'    => __( 'Destaques do Dia', 'vemcomer' ),
            'menu_items' => [],
            'quantidade' => 6,
        ],
        'categories_section' => [
            'ativo' => true,
        ],
        'featured_section' => [
            'ativo' => true,
        ],
        'restaurants_section' => [
            'ativo'      => true,
            'titulo'     => __( 'Restaurantes', 'vemcomer' ),
            'quantidade' => 12,
            'ordenar_por' => 'date',
        ],
        'map_section' => [
            'ativo' => true,
        ],
        'for_you_section' => [
            'ativo' => true,
        ],
        'cta_section' => [
            'ativo' => true,
        ],
    ];
}

// Seção 1: Hero
if ( ! empty( $options['hero_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'hero', [ 'args' => $options['hero_section'] ] );
}

// Seção 2: Categorias Populares
// Sempre mostrar, mesmo se não estiver nas configurações
if ( empty( $options['categories_section']['ativo'] ) ) {
    $options['categories_section'] = [ 'ativo' => true ];
}
get_template_part( 'template-parts/home/section', 'categories', [ 'args' => $options['categories_section'] ] );

// Seção 3: Banners (Promoções e Destaques) - Após categorias
if ( ! empty( $options['banners_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'banners', [ 'args' => $options['banners_section'] ] );
}

// Seção 4: Destaques do Dia
if ( ! empty( $options['daily_highlights_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'daily-highlights', [ 'args' => $options['daily_highlights_section'] ] );
}

// Seção 5: Restaurantes em Destaque
if ( ! empty( $options['featured_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'featured', [ 'args' => $options['featured_section'] ] );
}

// Seção 6: Listagem de Restaurantes
if ( ! empty( $options['restaurants_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'restaurants', [ 'args' => $options['restaurants_section'] ] );
}

// Seção 7: Mapa
if ( ! empty( $options['map_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'map', [ 'args' => $options['map_section'] ] );
}

// Seção 8: Para Você (só para logados)
if ( ! empty( $options['for_you_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'for-you', [ 'args' => $options['for_you_section'] ] );
}

// Seção 9: CTA para Donos
if ( ! empty( $options['cta_section']['ativo'] ) ) {
    get_template_part( 'template-parts/home/section', 'cta', [ 'args' => $options['cta_section'] ] );
}

get_footer();
