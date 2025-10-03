<?php
/**
 * [vc_restaurant]
 * Atributos:
 *  - id (opcional). Se vazio, usa o post do loop atual.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_restaurant', function( $atts = [] ) {
    vc_sc_mark_used();

    $a = shortcode_atts([
        'id' => ''
    ], $atts, 'vc_restaurant' );

    $pid = $a['id'] ? (int) $a['id'] : get_the_ID();
    if ( ! $pid || 'vc_restaurant' !== get_post_type( $pid ) ) { return ''; }

    $address  = get_post_meta( $pid, 'vc_restaurant_address', true );
    $hours    = get_post_meta( $pid, 'vc_restaurant_open_hours', true );
    $delivery = get_post_meta( $pid, 'vc_restaurant_delivery', true ) === '1';
    $cuisines = wp_get_post_terms( $pid, 'vc_cuisine', [ 'fields' => 'names' ] );
    $locs     = wp_get_post_terms( $pid, 'vc_location', [ 'fields' => 'names' ] );

    ob_start();
    ?>
    <article class="vc-card vc-restaurant" data-id="<?php echo esc_attr( (string) $pid ); ?>">
      <a class="vc-card__link" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
        <div class="vc-card__thumb">
          <?php echo get_the_post_thumbnail( $pid, 'medium' ); ?>
        </div>
        <div class="vc-card__body">
          <h3 class="vc-card__title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
          <?php if ( $address ) : ?><p class="vc-card__line"><?php echo esc_html( $address ); ?></p><?php endif; ?>
          <?php if ( $hours )   : ?><p class="vc-card__line"><?php echo esc_html( $hours ); ?></p><?php endif; ?>
          <p class="vc-card__tags">
            <?php if ( ! empty( $cuisines ) ) : ?>
              <span class="vc-tag"><?php echo esc_html( implode( ', ', $cuisines ) ); ?></span>
            <?php endif; ?>
            <?php if ( ! empty( $locs ) ) : ?>
              <span class="vc-tag vc-tag--muted"><?php echo esc_html( implode( ', ', $locs ) ); ?></span>
            <?php endif; ?>
            <?php if ( $delivery ) : ?>
              <span class="vc-badge vc-badge--ok"><?php echo esc_html__( 'Delivery', 'vemcomer' ); ?></span>
            <?php endif; ?>
          </p>
        </div>
      </a>
    </article>
    <?php
    return ob_get_clean();
});
