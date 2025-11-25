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
    <?php
    // Categorias padrão
    $categories = [
        [ 'name' => __( 'Pizza', 'vemcomer' ), 'icon' => '🍕', 'url' => home_url( '/restaurantes/?cuisine=pizza' ) ],
        [ 'name' => __( 'Brasileira', 'vemcomer' ), 'icon' => '🇧🇷', 'url' => home_url( '/restaurantes/?cuisine=brasileira' ) ],
        [ 'name' => __( 'Lanches', 'vemcomer' ), 'icon' => '🍔', 'url' => home_url( '/restaurantes/?cuisine=lanches' ) ],
        [ 'name' => __( 'Sushi', 'vemcomer' ), 'icon' => '🍣', 'url' => home_url( '/restaurantes/?cuisine=sushi' ) ],
        [ 'name' => __( 'Bares', 'vemcomer' ), 'icon' => '🍺', 'url' => home_url( '/restaurantes/?cuisine=bares' ) ],
        [ 'name' => __( 'Doces', 'vemcomer' ), 'icon' => '🍰', 'url' => home_url( '/restaurantes/?cuisine=doces' ) ],
    ];
    
    // Verificar categoria ativa
    $current_category = isset( $_GET['cuisine'] ) ? sanitize_text_field( $_GET['cuisine'] ) : '';
    ?>
    
    <!-- Categorias estilo pílulas (Mobile) -->
    <div class="categories-pills" id="categories-pills">
        <div class="categories-pills__container">
            <a href="<?php echo esc_url( home_url( '/restaurantes/' ) ); ?>" 
               class="categories-pills__item <?php echo empty( $current_category ) ? 'active' : ''; ?>"
               <?php echo empty( $current_category ) ? 'aria-current="page"' : ''; ?>>
                <span class="categories-pills__item-text"><?php esc_html_e( 'Todos', 'vemcomer' ); ?></span>
            </a>
            <?php foreach ( $categories as $cat ) : 
                $is_active = $current_category && strpos( $cat['url'], 'cuisine=' . $current_category ) !== false;
            ?>
                <a href="<?php echo esc_url( $cat['url'] ); ?>" 
                   class="categories-pills__item <?php echo $is_active ? 'active' : ''; ?>"
                   <?php echo $is_active ? 'aria-current="page"' : ''; ?>>
                    <span class="categories-pills__item-icon"><?php echo esc_html( $cat['icon'] ); ?></span>
                    <span class="categories-pills__item-text"><?php echo esc_html( $cat['name'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
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

