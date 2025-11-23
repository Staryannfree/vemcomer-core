<?php
/**
 * Home Improvements - Funcionalidades extras para a Home
 * @package VemComer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adiciona filtros rápidos (chips) acima da listagem
 */
function vemcomer_home_quick_filters() {
    if ( ! function_exists( 'vemcomer_is_plugin_active' ) || ! vemcomer_is_plugin_active() ) {
        return '';
    }

    $cuisines = get_terms([
        'taxonomy' => 'vc_cuisine',
        'hide_empty' => true,
        'number' => 8,
    ]);

    ob_start();
    ?>
    <div class="home-quick-filters" id="home-quick-filters">
        <div class="home-quick-filters__chips">
            <button class="filter-chip" data-filter="is_open_now">
                <span class="filter-chip__icon">🕐</span>
                <span class="filter-chip__label"><?php esc_html_e( 'Aberto agora', 'vemcomer' ); ?></span>
            </button>
            <button class="filter-chip" data-filter="has_delivery">
                <span class="filter-chip__icon">🚚</span>
                <span class="filter-chip__label"><?php esc_html_e( 'Delivery', 'vemcomer' ); ?></span>
            </button>
            <button class="filter-chip" data-filter="min_rating" data-value="4.5">
                <span class="filter-chip__icon">⭐</span>
                <span class="filter-chip__label"><?php esc_html_e( '4.5+ estrelas', 'vemcomer' ); ?></span>
            </button>
            <button class="filter-chip" data-filter="free_shipping">
                <span class="filter-chip__icon">💰</span>
                <span class="filter-chip__label"><?php esc_html_e( 'Frete grátis', 'vemcomer' ); ?></span>
            </button>
            <?php if ( ! is_wp_error( $cuisines ) && ! empty( $cuisines ) ) : ?>
                <?php foreach ( array_slice( $cuisines, 0, 4 ) as $cuisine ) : ?>
                    <button class="filter-chip" data-filter="cuisine" data-value="<?php echo esc_attr( $cuisine->slug ); ?>">
                        <span class="filter-chip__icon">🍽️</span>
                        <span class="filter-chip__label"><?php echo esc_html( $cuisine->name ); ?></span>
                    </button>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="home-quick-filters__actions">
            <button class="btn btn--ghost btn--small" id="clear-filters">
                <?php esc_html_e( 'Limpar filtros', 'vemcomer' ); ?>
            </button>
            <span class="home-quick-filters__count" id="filter-count" style="display: none;"></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Adiciona seção de categorias populares em formato carrossel
 */
function vemcomer_home_popular_categories() {
    // Não retornar vazio mesmo se plugin não estiver ativo - tentar buscar categorias de qualquer forma

    // Mapeamento de ícones para categorias
    $icon_map = [
        'pizza' => '🍕',
        'lanches' => '🍔',
        'hamburguer' => '🍔',
        'sushi' => '🍣',
        'brasileira' => '🇧🇷',
        'arabe' => '🥙',
        'doces' => '🍰',
        'sobremesas' => '🍰',
        'bebidas' => '🥤',
        'bares' => '🍺',
        'frutos-do-mar' => '🐟',
        'vegetariana' => '🥗',
        'churrasco' => '🥩',
        'cafe-da-manha' => '☕',
        'cafe' => '☕',
        'cafes' => '☕',
        'saudavel' => '🥗',
        'pet-friendly' => '🐾',
        'drinks' => '🍹',
        'italiana' => '🍝',
        'japonesa' => '🍱',
        'chinesa' => '🥟',
        'mexicana' => '🌮',
        'francesa' => '🥐',
        'indiana' => '🍛',
        'massas' => '🍝',
        'salgados' => '🥐',
        'acai' => '🥤',
        'sorvete' => '🍦',
    ];

    // Buscar TODAS as categorias reais do WordPress
    $terms = get_terms( [
        'taxonomy'   => 'vc_cuisine',
        'hide_empty' => false, // Mostrar mesmo sem restaurantes
        'orderby'    => 'count',
        'order'      => 'DESC',
    ] );

    // Se não encontrar categorias, usar categorias padrão
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        // Criar categorias padrão
        $default_categories = [
            (object) ['slug' => 'pizza', 'name' => 'Pizza', 'count' => 0],
            (object) ['slug' => 'brasileira', 'name' => 'Brasileira', 'count' => 0],
            (object) ['slug' => 'lanches', 'name' => 'Lanches', 'count' => 0],
            (object) ['slug' => 'sushi', 'name' => 'Sushi', 'count' => 0],
            (object) ['slug' => 'bares', 'name' => 'Bares', 'count' => 0],
            (object) ['slug' => 'doces', 'name' => 'Doces', 'count' => 0],
            (object) ['slug' => 'arabe', 'name' => 'Árabe', 'count' => 0],
            (object) ['slug' => 'italiana', 'name' => 'Italiana', 'count' => 0],
        ];
        $terms = $default_categories;
    }

    // Preparar categorias com ícones
    $real_categories = [];
    foreach ( $terms as $term ) {
        $slug = $term->slug;
        $icon = $icon_map[ $slug ] ?? '🍽️'; // Ícone padrão se não encontrar
        
        $real_categories[] = [
            'slug'  => $slug,
            'name'  => $term->name,
            'icon'  => $icon,
            'count' => $term->count,
            'url'   => add_query_arg( 'cuisine', $slug, home_url( '/restaurantes/' ) ),
        ];
    }

    // Se não encontrou nenhuma categoria, usar as padrão
    if ( empty( $real_categories ) ) {
        $default_cats = [
            ['slug' => 'pizza', 'name' => 'Pizza', 'icon' => '🍕', 'count' => 0],
            ['slug' => 'brasileira', 'name' => 'Brasileira', 'icon' => '🇧🇷', 'count' => 0],
            ['slug' => 'lanches', 'name' => 'Lanches', 'icon' => '🍔', 'count' => 0],
            ['slug' => 'sushi', 'name' => 'Sushi', 'icon' => '🍣', 'count' => 0],
            ['slug' => 'bares', 'name' => 'Bares', 'icon' => '🍺', 'count' => 0],
            ['slug' => 'doces', 'name' => 'Doces', 'icon' => '🍰', 'count' => 0],
            ['slug' => 'arabe', 'name' => 'Árabe', 'icon' => '🥙', 'count' => 0],
            ['slug' => 'italiana', 'name' => 'Italiana', 'icon' => '🍝', 'count' => 0],
        ];
        foreach ( $default_cats as $cat ) {
            $real_categories[] = [
                'slug'  => $cat['slug'],
                'name'  => $cat['name'],
                'icon'  => $cat['icon'],
                'count' => $cat['count'],
                'url'   => add_query_arg( 'cuisine', $cat['slug'], home_url( '/restaurantes/' ) ),
            ];
        }
    }

    ob_start();
    ?>
    <style>
    /* CSS BLINDADO - Força bruta para layout horizontal com CSS Grid */
    .vc-force-row-container {
        width: 100% !important;
        overflow: hidden !important;
        padding: 20px 0 !important;
    }
    
    .vc-force-row-track {
        display: grid !important;
        grid-auto-flow: column !important; /* A MÁGICA: Força colunas infinitas (horizontal) */
        grid-auto-columns: minmax(130px, 1fr) !important; /* Tamanho mínimo de cada card */
        gap: 15px !important;
        overflow-x: auto !important; /* Permite rolar para o lado */
        padding-bottom: 15px !important; /* Espaço para scrollbar */
        
        /* Suavização de scroll */
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin; /* Firefox */
    }
    
    /* Estilização da barra de rolagem */
    .vc-force-row-track::-webkit-scrollbar {
        height: 6px;
    }
    
    .vc-force-row-track::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 10px;
    }
    
    .vc-force-row-track::-webkit-scrollbar-track {
        background-color: #f1f1f1;
        border-radius: 10px;
    }
    
    /* O Card Individual */
    .vc-category-card {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        background: #fff !important;
        border: 1px solid #eee !important;
        border-radius: 12px !important;
        padding: 15px 10px !important;
        text-decoration: none !important;
        text-align: center !important;
        height: 100% !important;
        min-width: 130px !important; /* Força largura mínima */
        max-width: none !important;
        width: auto !important;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05) !important;
        transition: transform 0.2s ease !important;
        margin: 0 !important;
        float: none !important;
        clear: none !important;
    }
    
    .vc-category-card:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
        border-color: var(--color-primary, #2f9e44) !important;
    }
    
    .vc-cat-icon {
        font-size: 28px !important;
        margin-bottom: 10px !important;
        display: block !important;
        line-height: 1 !important;
    }
    
    .vc-cat-name {
        font-size: 14px !important;
        font-weight: 600 !important;
        color: #333 !important;
        margin: 0 0 5px 0 !important;
        line-height: 1.2 !important;
    }
    
    .vc-cat-count {
        font-size: 11px !important;
        color: #888 !important;
        margin: 0 !important;
    }
    
    /* Responsivo: ajustar tamanho mínimo em telas menores */
    @media (max-width: 768px) {
        .vc-force-row-track {
            grid-auto-columns: minmax(110px, 1fr) !important;
            gap: 12px !important;
        }
        
        .vc-category-card {
            min-width: 110px !important;
            padding: 12px 8px !important;
        }
        
        .vc-cat-icon {
            font-size: 24px !important;
        }
        
        .vc-cat-name {
            font-size: 13px !important;
        }
    }
    
    @media (max-width: 480px) {
        .vc-force-row-track {
            grid-auto-columns: minmax(100px, 1fr) !important;
            gap: 10px !important;
        }
        
        .vc-category-card {
            min-width: 100px !important;
            padding: 10px 6px !important;
        }
    }
    </style>
    
    <section class="home-categories" style="background: #fafafa;">
        <div class="container">
            <h2 class="section-title" style="margin-bottom: 20px; font-size: 20px; font-weight: bold;">
                <?php esc_html_e( 'Categorias populares', 'vemcomer' ); ?>
            </h2>
            
            <div class="vc-force-row-container">
                <!-- Wrapper que usa GRID FLOW COLUMN -->
                <div class="vc-force-row-track">
                    <?php foreach ( $real_categories as $cat ) : ?>
                        <a href="<?php echo esc_url( $cat['url'] ); ?>" class="vc-category-card">
                            <span class="vc-cat-icon"><?php echo esc_html( $cat['icon'] ); ?></span>
                            <h3 class="vc-cat-name"><?php echo esc_html( $cat['name'] ); ?></h3>
                            <span class="vc-cat-count">
                                <?php echo esc_html( sprintf( _n( '%d restaurante', '%d restaurantes', $cat['count'], 'vemcomer' ), $cat['count'] ) ); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

/**
 * Adiciona seção de restaurantes em destaque
 */
function vemcomer_home_featured_restaurants() {
    if ( ! function_exists( 'vemcomer_is_plugin_active' ) || ! vemcomer_is_plugin_active() ) {
        return '';
    }

    // Buscar restaurantes com melhor rating e mais reviews
    $featured = new WP_Query([
        'post_type' => 'vc_restaurant',
        'posts_per_page' => 6,
        'post_status' => 'publish',
        'meta_query' => [
            [
                'key' => '_vc_restaurant_rating_avg',
                'value' => '4.0',
                'compare' => '>=',
                'type' => 'NUMERIC',
            ],
        ],
        'orderby' => 'meta_value_num',
        'meta_key' => '_vc_restaurant_rating_avg',
        'order' => 'DESC',
    ]);

    if ( ! $featured->have_posts() ) {
        return '';
    }

    ob_start();
    ?>
    <section class="home-featured">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><?php esc_html_e( 'Restaurantes em destaque', 'vemcomer' ); ?></h2>
                <p class="section-description"><?php esc_html_e( 'Os melhores restaurantes avaliados pelos clientes', 'vemcomer' ); ?></p>
            </div>
            <div class="featured-carousel" id="featured-carousel">
                <?php while ( $featured->have_posts() ) : $featured->the_post(); ?>
                    <div class="featured-card">
                        <?php echo do_shortcode( '[vc_restaurant id="' . get_the_ID() . '"]' ); ?>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
}

