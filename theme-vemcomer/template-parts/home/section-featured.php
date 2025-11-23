<?php
/**
 * Template Part: Restaurantes em Destaque
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$args = isset( $args ) ? $args : [];
$titulo = $args['titulo'] ?? __( 'Restaurantes em Destaque', 'vemcomer' );
$restaurant_ids = $args['restaurant_ids'] ?? [];
$quantidade = isset( $args['quantidade'] ) ? absint( $args['quantidade'] ) : 6;

// Se restaurant_ids vier como string, converter para array
if ( is_string( $restaurant_ids ) && ! empty( $restaurant_ids ) ) {
    $restaurant_ids = array_map( 'absint', explode( ',', $restaurant_ids ) );
    $restaurant_ids = array_filter( $restaurant_ids );
}

// Se não houver restaurantes selecionados, buscar restaurantes com melhor rating como fallback
if ( empty( $restaurant_ids ) ) {
    // Verificar se o post type existe
    if ( ! post_type_exists( 'vc_restaurant' ) ) {
        return; // Se o post type não existir, não exibir
    }
    
    // Fallback: buscar restaurantes com melhor rating
    $fallback_query = new WP_Query([
        'post_type' => 'vc_restaurant',
        'posts_per_page' => $quantidade,
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
    
    if ( $fallback_query->have_posts() ) {
        $restaurant_ids = wp_list_pluck( $fallback_query->posts, 'ID' );
        wp_reset_postdata();
    } else {
        // Se não houver com rating, buscar os mais recentes
        $fallback_query = new WP_Query([
            'post_type' => 'vc_restaurant',
            'posts_per_page' => $quantidade,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        if ( $fallback_query->have_posts() ) {
            $restaurant_ids = wp_list_pluck( $fallback_query->posts, 'ID' );
            wp_reset_postdata();
        } else {
            // Se não houver restaurantes no banco, não exibir a seção
            return;
        }
    }
}

// Limitar quantidade
$restaurant_ids = array_slice( $restaurant_ids, 0, $quantidade );

// Buscar os restaurantes selecionados
$featured_query = new WP_Query([
    'post_type'      => 'vc_restaurant',
    'post__in'       => $restaurant_ids,
    'posts_per_page' => count( $restaurant_ids ),
    'post_status'    => 'publish',
    'orderby'        => 'post__in', // Manter ordem de seleção
]);

if ( ! $featured_query->have_posts() ) {
    return;
}
?>

<section class="home-featured" style="padding: 40px 0; background: #fafafa;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 30px;">
            <h2 class="section-title" style="font-size: 28px; font-weight: bold; margin-bottom: 10px; color: #222;">
                <?php echo esc_html( $titulo ); ?>
            </h2>
            <p class="section-description" style="color: #666; font-size: 16px;">
                <?php esc_html_e( 'Os melhores restaurantes selecionados especialmente para você', 'vemcomer' ); ?>
            </p>
        </div>
        <div class="featured-carousel" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php while ( $featured_query->have_posts() ) : $featured_query->the_post(); ?>
                <div class="featured-card">
                    <?php 
                    if ( shortcode_exists( 'vc_restaurant' ) ) {
                        echo do_shortcode( '[vc_restaurant id="' . get_the_ID() . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    } else {
                        // Fallback básico
                        ?>
                        <article style="background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: transform 0.3s;">
                            <?php if ( has_post_thumbnail() ) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail( 'medium', [ 'style' => 'width: 100%; height: 200px; object-fit: cover;' ] ); ?>
                                </a>
                            <?php endif; ?>
                            <div style="padding: 20px;">
                                <h3 style="margin: 0 0 10px; font-size: 18px;">
                                    <a href="<?php the_permalink(); ?>" style="text-decoration: none; color: #222;">
                                        <?php the_title(); ?>
                                    </a>
                                </h3>
                                <p style="margin: 0; color: #666; font-size: 14px;">
                                    <?php echo wp_trim_words( get_the_excerpt(), 15 ); ?>
                                </p>
                            </div>
                        </article>
                        <?php
                    }
                    ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<style>
.home-featured .featured-card:hover {
    transform: translateY(-5px) !important;
}

@media (max-width: 768px) {
    .home-featured .featured-carousel {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php
wp_reset_postdata();
?>

