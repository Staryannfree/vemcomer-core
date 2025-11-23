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

// Carregar função de featured se existir
$home_improvements = get_template_directory() . '/inc/home-improvements.php';
if ( file_exists( $home_improvements ) ) {
    require_once $home_improvements;
}

// Tentar usar a função de featured restaurants
if ( function_exists( 'vemcomer_home_featured_restaurants' ) ) {
    echo vemcomer_home_featured_restaurants(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
} else {
    // Fallback: buscar restaurantes em destaque manualmente
    $featured_query = new WP_Query([
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
    
    if ( ! $featured_query->have_posts() ) {
        // Se não encontrar com rating, buscar os mais recentes
        $featured_query = new WP_Query([
            'post_type' => 'vc_restaurant',
            'posts_per_page' => 6,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
    }
    
    if ( $featured_query->have_posts() ) :
        ?>
        <section class="home-featured" style="padding: 40px 0; background: #fafafa;">
            <div class="container">
                <div class="section-header" style="text-align: center; margin-bottom: 30px;">
                    <h2 class="section-title" style="font-size: 24px; font-weight: bold; margin-bottom: 10px; color: #222;">
                        <?php esc_html_e( 'Restaurantes em destaque', 'vemcomer' ); ?>
                    </h2>
                    <p class="section-description" style="color: #666; font-size: 16px;">
                        <?php esc_html_e( 'Os melhores restaurantes avaliados pelos clientes', 'vemcomer' ); ?>
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
        <?php
        wp_reset_postdata();
    endif;
}

