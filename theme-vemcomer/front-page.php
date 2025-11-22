<?php
/**
 * Template da página inicial
 *
 * @package VemComer
 */

get_header();
?>

<?php
// Seção 1: Hero
?>
<section class="home-hero">
    <div class="container">
        <div class="home-hero__content">
            <h1 class="home-hero__title" id="hero-title"><?php echo esc_html__( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' ); ?></h1>
            <p class="home-hero__subtitle" id="hero-subtitle"><?php echo esc_html__( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' ); ?></p>
            <div class="home-hero__search">
                <form method="get" action="#restaurants-list" class="home-hero__search-form" id="hero-search-form">
                    <div class="search-wrapper">
                        <input 
                            type="text" 
                            name="s" 
                            id="hero-search-input"
                            placeholder="<?php echo esc_attr__( 'Buscar restaurantes, pratos...', 'vemcomer' ); ?>" 
                            class="home-hero__search-input"
                            value="<?php echo esc_attr( get_query_var( 's' ) ); ?>"
                            autocomplete="off"
                        />
                        <div class="search-autocomplete" id="search-autocomplete" style="display: none;"></div>
                    </div>
                    <button type="submit" class="home-hero__search-btn">
                        <?php echo esc_html__( 'Buscar', 'vemcomer' ); ?>
                    </button>
                </form>
            </div>
            <div class="home-hero__quick-actions" id="hero-location-actions">
                <button class="btn-geolocation" id="vc-use-location" type="button">
                    <span class="btn-geolocation__icon">📍</span>
                    <span class="btn-geolocation__text"><?php esc_html_e( 'Usar minha localização', 'vemcomer' ); ?></span>
                </button>
            </div>
            <a href="#restaurants-list" class="btn btn--primary btn--large home-hero__cta">
                <?php echo esc_html__( 'Explorar restaurantes', 'vemcomer' ); ?>
            </a>
        </div>
    </div>
</section>

<?php
// Seção 2: Banners
if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
?>
<section class="home-banners">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Promoções e destaques', 'vemcomer' ); ?></h2>
        <?php echo do_shortcode( '[vc_banners]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</section>
<?php endif; ?>

<?php
// Seção 2.5: Categorias Populares
if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) {
    $home_improvements = get_template_directory() . '/inc/home-improvements.php';
    if ( file_exists( $home_improvements ) ) {
        require_once $home_improvements;
    }
    if ( function_exists( 'vemcomer_home_popular_categories' ) ) {
        echo vemcomer_home_popular_categories(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
?>

<?php
// Seção 2.6: Restaurantes em Destaque
if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) {
    if ( function_exists( 'vemcomer_home_featured_restaurants' ) ) {
        echo vemcomer_home_featured_restaurants(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}
?>

<?php
// Seção 3: Listagem de Restaurantes
if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
?>
<section class="home-restaurants" id="restaurants-list">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Restaurantes', 'vemcomer' ); ?></h2>
        <?php
        // Filtros rápidos
        $home_improvements = get_template_directory() . '/inc/home-improvements.php';
        if ( file_exists( $home_improvements ) && ! function_exists( 'vemcomer_home_quick_filters' ) ) {
            require_once $home_improvements;
        }
        if ( function_exists( 'vemcomer_home_quick_filters' ) ) {
            echo vemcomer_home_quick_filters(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>
        <div id="restaurants-container" class="restaurants-container">
            <div class="skeleton-loading" id="skeleton-loading">
                <?php for ( $i = 0; $i < 6; $i++ ) : ?>
                    <div class="skeleton-card">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-title"></div>
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line"></div>
                    </div>
                <?php endfor; ?>
            </div>
            <div id="restaurants-content" style="display: none;">
                <?php echo do_shortcode( '[vemcomer_restaurants]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Seção 4: Mapa
if ( function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
?>
<section class="home-map">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Veja restaurantes no mapa', 'vemcomer' ); ?></h2>
        <p class="section-description"><?php echo esc_html__( 'Encontre restaurantes próximos a você usando o mapa interativo.', 'vemcomer' ); ?></p>
        <?php echo do_shortcode( '[vc_restaurants_map]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
</section>
<?php endif; ?>

<?php
// Seção 5: Para você (só para logados)
if ( is_user_logged_in() && function_exists( 'vemcomer_is_plugin_active' ) && vemcomer_is_plugin_active() ) :
?>
<section class="home-for-you">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html__( 'Para você', 'vemcomer' ); ?></h2>
        
        <div class="tabs">
            <button class="tab tab--active" data-tab="favorites">
                <?php echo esc_html__( 'Meus Favoritos', 'vemcomer' ); ?>
            </button>
            <button class="tab" data-tab="orders">
                <?php echo esc_html__( 'Meus Pedidos', 'vemcomer' ); ?>
            </button>
        </div>

        <div class="tab-content tab-content--active" id="tab-favorites">
            <?php echo do_shortcode( '[vc_favorites]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>

        <div class="tab-content" id="tab-orders">
            <?php echo do_shortcode( '[vc_orders_history per_page="5"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Seção 6: CTA para donos
?>
<section class="home-cta">
    <div class="container">
        <div class="home-cta__content">
            <h2 class="home-cta__title"><?php echo esc_html__( 'Tem um restaurante? Venda pelo VemComer', 'vemcomer' ); ?></h2>
            <p class="home-cta__text">
                <?php echo esc_html__( 'Cadastre seu restaurante e comece a receber pedidos hoje mesmo. Sem comissões por venda, apenas uma mensalidade fixa.', 'vemcomer' ); ?>
            </p>
            <?php
            // Buscar página de cadastro
            $signup_page = get_posts([
                'post_type' => 'page',
                's' => 'cadastro restaurante',
                'posts_per_page' => 1,
            ]);
            $signup_url = ! empty( $signup_page ) 
                ? get_permalink( $signup_page[0]->ID ) 
                : home_url( '/cadastre-seu-restaurante/' );
            ?>
            <a href="<?php echo esc_url( $signup_url ); ?>" class="btn btn--primary btn--large" id="btn-cadastro-home">
                <?php echo esc_html__( 'Cadastrar meu restaurante', 'vemcomer' ); ?>
            </a>
        </div>
    </div>
</section>

<?php
get_footer();

