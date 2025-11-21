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

    // Enfileirar assets do modal de produto
    wp_enqueue_style( 'vemcomer-product-modal' );
    wp_enqueue_script( 'vemcomer-product-modal' );

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
        <ul class="vc-menu-list">
          <?php while ( $q->have_posts() ) : $q->the_post(); ?>
            <?php
                $mid        = get_the_ID();
                $title      = get_the_title();
                $raw_price  = trim( (string) get_post_meta( $mid, '_vc_price', true ) );
                $price      = $raw_price !== '' ? ( stripos( $raw_price, 'R$' ) === false ? 'R$ ' . $raw_price : $raw_price ) : __( 'Consulte', 'vemcomer' );
                $desc       = trim( wp_strip_all_tags( get_post_field( 'post_content', $mid ) ) );
                $prep_time  = (string) get_post_meta( $mid, '_vc_prep_time', true );
                $is_avail   = (string) get_post_meta( $mid, '_vc_is_available', true );
                $categories = get_the_terms( $mid, 'vc_menu_category' );
                $cat_names  = ! is_wp_error( $categories ) && $categories ? wp_list_pluck( $categories, 'name' ) : [];
                $letter     = strtoupper( (string) ( function_exists( 'mb_substr' ) ? mb_substr( $title, 0, 1, 'UTF-8' ) : substr( $title, 0, 1 ) ) );
            ?>
            <li class="vc-menu-item<?php echo $is_avail ? '' : ' is-unavailable'; ?>">
              <div class="vc-menu-item__media">
                <?php if ( has_post_thumbnail() ) : ?>
                    <?php the_post_thumbnail( 'medium', [ 'class' => 'vc-menu-item__thumb', 'loading' => 'lazy' ] ); ?>
                <?php else : ?>
                    <div class="vc-menu-item__thumb is-placeholder" aria-hidden="true"><span><?php echo esc_html( $letter ); ?></span></div>
                <?php endif; ?>
              </div>
              <div class="vc-menu-item__info">
                <div class="vc-menu-item__labels">
                  <?php if ( $cat_names ) : ?>
                    <span class="vc-badge vc-badge--pill"><?php echo esc_html( implode( ' · ', $cat_names ) ); ?></span>
                  <?php endif; ?>
                  <?php if ( ! $is_avail ) : ?>
                    <span class="vc-badge vc-badge--alert"><?php echo esc_html__( 'Indisponível', 'vemcomer' ); ?></span>
                  <?php endif; ?>
                </div>
                <h3 class="vc-menu-item__title"><?php echo esc_html( $title ); ?></h3>
                <?php if ( $desc ) : ?>
                  <p class="vc-menu-item__desc"><?php echo esc_html( $desc ); ?></p>
                <?php endif; ?>
                <div class="vc-menu-item__meta">
                  <?php if ( $prep_time ) : ?>
                    <span><?php echo esc_html( sprintf( __( 'Preparo: %s min', 'vemcomer' ), $prep_time ) ); ?></span>
                  <?php endif; ?>
                </div>
              </div>
              <div class="vc-menu-item__price">
                <span class="vc-price"><?php echo esc_html( $price ); ?></span>
                <button 
                  class="vc-btn vc-btn--small vc-menu-item__add" 
                  data-item-id="<?php echo esc_attr( (string) $mid ); ?>"
                  data-item-title="<?php echo esc_attr( $title ); ?>"
                  data-item-price="<?php echo esc_attr( $raw_price ); ?>"
                  data-item-description="<?php echo esc_attr( $desc ); ?>"
                  data-restaurant-id="<?php echo esc_attr( (string) $rid ); ?>"
                  <?php echo $is_avail ? '' : 'disabled'; ?>
                  <?php
                  $image_url = '';
                  if ( has_post_thumbnail( $mid ) ) {
                      $image_id = get_post_thumbnail_id( $mid );
                      $image_url = wp_get_attachment_image_url( $image_id, 'medium' );
                  }
                  if ( $image_url ) {
                      echo ' data-item-image="' . esc_url( $image_url ) . '"';
                  }
                  ?>
                >
                  <?php echo esc_html__( 'Adicionar', 'vemcomer' ); ?>
                </button>
              </div>
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
