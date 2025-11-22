<?php
/**
 * Template Part: Listagem de Restaurantes
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vemcomer_is_plugin_active' ) || ! vemcomer_is_plugin_active() ) {
    return;
}

$args = isset( $args ) ? $args : [];
$titulo = $args['titulo'] ?? __( 'Restaurantes', 'vemcomer' );
$quantidade = isset( $args['quantidade'] ) ? absint( $args['quantidade'] ) : 12;
$ordenar_por = $args['ordenar_por'] ?? 'date';
?>

<section class="home-restaurants" id="restaurants-list">
    <div class="container">
        <h2 class="section-title"><?php echo esc_html( $titulo ); ?></h2>
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

