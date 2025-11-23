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
    if ( ! function_exists( 'vemcomer_is_plugin_active' ) || ! vemcomer_is_plugin_active() ) {
        return '';
    }

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
        'hide_empty' => true,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
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

    if ( empty( $real_categories ) ) {
        return '';
    }

    ob_start();
    ?>
    <section class="home-categories">
        <div class="container">
            <h2 class="section-title"><?php esc_html_e( 'Categorias populares', 'vemcomer' ); ?></h2>
            <div class="categories-carousel-wrapper">
                <button class="carousel-btn carousel-btn--prev" aria-label="<?php esc_attr_e( 'Anterior', 'vemcomer' ); ?>">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="categories-carousel" id="categories-carousel">
                    <div class="categories-carousel__track">
                        <?php foreach ( $real_categories as $cat ) : ?>
                            <a href="<?php echo esc_url( $cat['url'] ); ?>" class="category-card">
                                <div class="category-card__icon"><?php echo esc_html( $cat['icon'] ); ?></div>
                                <h3 class="category-card__name"><?php echo esc_html( $cat['name'] ); ?></h3>
                                <p class="category-card__count"><?php echo esc_html( sprintf( _n( '%d restaurante', '%d restaurantes', $cat['count'], 'vemcomer' ), $cat['count'] ) ); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button class="carousel-btn carousel-btn--next" aria-label="<?php esc_attr_e( 'Próximo', 'vemcomer' ); ?>">
                    <i class="fas fa-chevron-right"></i>
                </button>
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

