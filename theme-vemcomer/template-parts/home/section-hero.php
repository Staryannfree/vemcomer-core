<?php
/**
 * Template Part: Seção Hero (Banner Principal)
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$args = isset( $args ) ? $args : [];
$titulo = $args['titulo'] ?? __( 'Peça dos melhores restaurantes da sua cidade', 'vemcomer' );
$subtitulo = $args['subtitulo'] ?? __( 'Entrega, retirada e cardápios atualizados em tempo real', 'vemcomer' );
?>

<section class="home-hero">
    <div class="container">
        <div class="home-hero__content">
            <h1 class="home-hero__title" id="hero-title"><?php echo esc_html( $titulo ); ?></h1>
            <p class="home-hero__subtitle" id="hero-subtitle"><?php echo esc_html( $subtitulo ); ?></p>
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

