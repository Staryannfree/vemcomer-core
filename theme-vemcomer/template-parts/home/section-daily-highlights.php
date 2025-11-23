<?php
/**
 * Template Part: Seção Destaques do Dia
 * 
 * @package VemComer
 * @var array $args Configurações da seção vindas do admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$args = isset( $args ) ? $args : [];
$titulo = $args['titulo'] ?? __( 'Destaques do Dia', 'vemcomer' );
$menu_items_ids = $args['menu_items'] ?? [];
$quantidade = isset( $args['quantidade'] ) ? absint( $args['quantidade'] ) : 6;

// Se menu_items vier como string, converter para array
if ( is_string( $menu_items_ids ) && ! empty( $menu_items_ids ) ) {
    $menu_items_ids = array_map( 'absint', explode( ',', $menu_items_ids ) );
    $menu_items_ids = array_filter( $menu_items_ids );
}

// Se não houver itens selecionados, buscar produtos recentes como fallback
// (igual às categorias - sempre tentar mostrar algo)
if ( empty( $menu_items_ids ) ) {
    // Verificar se o post type existe
    if ( ! post_type_exists( 'vc_menu_item' ) ) {
        return; // Se o post type não existir, não exibir
    }
    
    // Fallback: buscar produtos recentes de qualquer restaurante
    $fallback_query = new WP_Query([
        'post_type'      => 'vc_menu_item',
        'posts_per_page' => $quantidade,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'   => '_vc_is_available',
                'value' => '1',
            ],
        ],
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    
    if ( $fallback_query->have_posts() ) {
        $menu_items_ids = wp_list_pluck( $fallback_query->posts, 'ID' );
        wp_reset_postdata();
    } else {
        // Se não houver produtos disponíveis, buscar sem filtro de disponibilidade
        $fallback_query = new WP_Query([
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => $quantidade,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        
        if ( $fallback_query->have_posts() ) {
            $menu_items_ids = wp_list_pluck( $fallback_query->posts, 'ID' );
            wp_reset_postdata();
        } else {
            // Se não houver produtos no banco, não exibir a seção
            return;
        }
    }
}

// Limitar quantidade
$menu_items_ids = array_slice( $menu_items_ids, 0, $quantidade );

// Buscar os menu items
$highlights_query = new WP_Query([
    'post_type'      => 'vc_menu_item',
    'post__in'       => $menu_items_ids,
    'posts_per_page' => count( $menu_items_ids ),
    'post_status'    => 'publish',
    'orderby'        => 'post__in', // Manter ordem de seleção
]);

if ( ! $highlights_query->have_posts() ) {
    return;
}
?>

<section class="home-daily-highlights" style="padding: 50px 0; background: #fafafa;">
    <div class="container">
        <div class="section-header" style="text-align: center; margin-bottom: 40px;">
            <h2 class="section-title" style="font-size: 28px; font-weight: bold; margin-bottom: 10px; color: #222;">
                <?php echo esc_html( $titulo ); ?>
            </h2>
            <p class="section-description" style="color: #666; font-size: 16px;">
                <?php esc_html_e( 'Produtos especiais selecionados especialmente para você', 'vemcomer' ); ?>
            </p>
        </div>

        <div class="highlights-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px;">
            <?php while ( $highlights_query->have_posts() ) : $highlights_query->the_post(); ?>
                <?php
                $item_id = get_the_ID();
                $restaurant_id = (int) get_post_meta( $item_id, '_vc_restaurant_id', true );
                $restaurant = $restaurant_id > 0 ? get_post( $restaurant_id ) : null;
                $price = get_post_meta( $item_id, '_vc_price', true );
                $prep_time = get_post_meta( $item_id, '_vc_prep_time', true );
                $is_available = (bool) get_post_meta( $item_id, '_vc_is_available', true );
                $image_id = get_post_thumbnail_id( $item_id );
                $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
                $restaurant_url = $restaurant ? get_permalink( $restaurant_id ) : '#';
                ?>
                <div class="highlight-card" style="background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s;">
                    <?php if ( $image_url ) : ?>
                        <div class="highlight-card__image" style="width: 100%; height: 200px; overflow: hidden; position: relative;">
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" loading="lazy" />
                            <?php if ( ! $is_available ) : ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(220, 50, 50, 0.9); color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                    <?php esc_html_e( 'Indisponível', 'vemcomer' ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="highlight-card__content" style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                            <h3 class="highlight-card__title" style="margin: 0; font-size: 20px; font-weight: 700; color: #222; flex: 1;">
                                <?php the_title(); ?>
                            </h3>
                            <?php if ( $price ) : ?>
                                <div class="highlight-card__price" style="font-size: 24px; font-weight: bold; color: #2f9e44; margin-left: 15px;">
                                    R$ <?php echo esc_html( $price ); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ( $restaurant ) : ?>
                            <div class="highlight-card__restaurant" style="margin-bottom: 12px;">
                                <a href="<?php echo esc_url( $restaurant_url ); ?>" style="color: #666; text-decoration: none; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 5px;">
                                    <span>🍽️</span>
                                    <span><?php echo esc_html( $restaurant->post_title ); ?></span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ( has_excerpt() ) : ?>
                            <p class="highlight-card__description" style="margin: 0 0 15px; color: #666; font-size: 0.95rem; line-height: 1.5;">
                                <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                            </p>
                        <?php endif; ?>

                        <div class="highlight-card__meta" style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; font-size: 0.9rem; color: #888;">
                            <?php if ( $prep_time ) : ?>
                                <span style="display: inline-flex; align-items: center; gap: 5px;">
                                    <span>⏱️</span>
                                    <span><?php echo esc_html( $prep_time ); ?> min</span>
                                </span>
                            <?php endif; ?>
                            <?php if ( $is_available ) : ?>
                                <span style="display: inline-flex; align-items: center; gap: 5px; color: #2f9e44;">
                                    <span>✓</span>
                                    <span><?php esc_html_e( 'Disponível', 'vemcomer' ); ?></span>
                                </span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo esc_url( $restaurant_url ); ?>" class="highlight-card__button" style="display: block; text-align: center; background: #2f9e44; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: background 0.3s;">
                            <?php esc_html_e( 'Ver Cardápio', 'vemcomer' ); ?>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<style>
.home-daily-highlights .highlight-card:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
}

.home-daily-highlights .highlight-card__button:hover {
    background: #1e7e34 !important;
}

@media (max-width: 768px) {
    .home-daily-highlights .highlights-grid {
        grid-template-columns: 1fr !important;
        gap: 20px !important;
    }
    
    .home-daily-highlights .highlight-card__image {
        height: 180px !important;
    }
}
</style>

<?php
wp_reset_postdata();
?>

