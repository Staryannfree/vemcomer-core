<?php
/**
 * [vc_menu_items]
 * Atributos:
 *  - restaurant_id (obrigatório se fora de single do restaurante)
 *  - per_page (opcional, default 100)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_menu_items', function( $atts = [] ) {
    vc_sc_mark_used();

    if ( ! post_type_exists( 'vc_menu_item' ) ) {
        return '<p class="vc-empty">' . esc_html__( 'Itens de cardápio indisponíveis.', 'vemcomer' ) . '</p>';
    }

    $a = shortcode_atts([
        'restaurant_id' => '',
        'per_page'      => '100',
    ], $atts, 'vc_menu_items' );

    $rid = $a['restaurant_id'] ? (int) $a['restaurant_id'] : ( get_post_type() === 'vc_restaurant' ? get_the_ID() : 0 );
    if ( ! $rid ) {
        return '<p class="vc-empty">' . esc_html__( 'Defina um restaurante.', 'vemcomer' ) . '</p>';
    }

    $q = new WP_Query([
        'post_type'      => 'vc_menu_item',
        'posts_per_page' => max(1, (int) $a['per_page']),
        'meta_query'     => [ [ 'key' => '_vc_restaurant_id', 'value' => (string) $rid ] ],
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);

    ob_start();
    ?>
    <div class="vc-sc vc-menu">
      <?php if ( $q->have_posts() ) : ?>
        <ul class="vc-list">
          <?php while ( $q->have_posts() ) : $q->the_post(); ?>
            <li class="vc-row">
              <span class="vc-row__title"><?php the_title(); ?></span>
              <span class="vc-row__dots"></span>
              <span class="vc-row__price"><?php echo esc_html( (string) get_post_meta( get_the_ID(), '_vc_price', true ) ); ?></span>
            </li>
          <?php endwhile; ?>
        </ul>
      <?php else : ?>
        <p class="vc-empty"><?php echo esc_html__( 'Nenhum item cadastrado.', 'vemcomer' ); ?></p>
      <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
});
