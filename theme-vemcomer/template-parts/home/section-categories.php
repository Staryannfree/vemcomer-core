<?php
/**
 * Template Part: Seção de Categorias Populares
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Carregar a função mesmo se o plugin não estiver ativo
$home_improvements = get_template_directory() . '/inc/home-improvements.php';
if ( file_exists( $home_improvements ) ) {
    require_once $home_improvements;
}

// Sempre tentar mostrar as categorias, mesmo sem plugin
if ( function_exists( 'vemcomer_home_popular_categories' ) ) {
    echo vemcomer_home_popular_categories(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
    // Fallback: mostrar categorias padrão se a função não existir
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
                        <a href="<?php echo esc_url( home_url( '/restaurantes/?cuisine=pizza' ) ); ?>" class="category-card">
                            <div class="category-card__icon">🍕</div>
                            <h3 class="category-card__name"><?php esc_html_e( 'Pizza', 'vemcomer' ); ?></h3>
                            <p class="category-card__count"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/restaurantes/?cuisine=brasileira' ) ); ?>" class="category-card">
                            <div class="category-card__icon">🇧🇷</div>
                            <h3 class="category-card__name"><?php esc_html_e( 'Brasileira', 'vemcomer' ); ?></h3>
                            <p class="category-card__count"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/restaurantes/?cuisine=lanches' ) ); ?>" class="category-card">
                            <div class="category-card__icon">🍔</div>
                            <h3 class="category-card__name"><?php esc_html_e( 'Lanches', 'vemcomer' ); ?></h3>
                            <p class="category-card__count"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/restaurantes/?cuisine=sushi' ) ); ?>" class="category-card">
                            <div class="category-card__icon">🍣</div>
                            <h3 class="category-card__name"><?php esc_html_e( 'Sushi', 'vemcomer' ); ?></h3>
                            <p class="category-card__count"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/restaurantes/?cuisine=bares' ) ); ?>" class="category-card">
                            <div class="category-card__icon">🍺</div>
                            <h3 class="category-card__name"><?php esc_html_e( 'Bares', 'vemcomer' ); ?></h3>
                            <p class="category-card__count"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></p>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/restaurantes/?cuisine=doces' ) ); ?>" class="category-card">
                            <div class="category-card__icon">🍰</div>
                            <h3 class="category-card__name"><?php esc_html_e( 'Doces', 'vemcomer' ); ?></h3>
                            <p class="category-card__count"><?php esc_html_e( 'Restaurantes', 'vemcomer' ); ?></p>
                        </a>
                    </div>
                </div>
                <button class="carousel-btn carousel-btn--next" aria-label="<?php esc_attr_e( 'Próximo', 'vemcomer' ); ?>">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </section>
    <?php
}

// Carregar Font Awesome se necessário (para os botões do carrossel)
if ( ! wp_style_is( 'font-awesome', 'enqueued' ) ) {
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css', [], '6.4.2' );
}

