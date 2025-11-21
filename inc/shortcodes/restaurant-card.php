<?php
/**
 * [vc_restaurant]
 * Atributos:
 *  - id (opcional). Se vazio, usa o post do loop atual.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Carregar helpers
if ( ! function_exists( 'vc_restaurant_get_rating' ) && class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
    require_once VEMCOMER_CORE_DIR . 'inc/Utils/Rating_Helper.php';
}
if ( ! function_exists( 'vc_restaurant_is_open' ) && class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
    require_once VEMCOMER_CORE_DIR . 'inc/Utils/Schedule_Helper.php';
}

add_shortcode( 'vc_restaurant', function( $atts = [] ) {
    vc_sc_mark_used();

    $a = shortcode_atts([
        'id' => ''
    ], $atts, 'vc_restaurant' );

    $pid = $a['id'] ? (int) $a['id'] : get_the_ID();
    if ( ! $pid || 'vc_restaurant' !== get_post_type( $pid ) ) { return ''; }

    $address  = get_post_meta( $pid, 'vc_restaurant_address', true );
    // Usar horários estruturados se disponível, senão fallback para texto
    $schedule = [];
    $hours_text = '';
    if ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
        $schedule = \VC\Utils\Schedule_Helper::get_schedule( $pid );
        if ( empty( $schedule ) ) {
            // Fallback para campo texto legado
            $hours_text = get_post_meta( $pid, 'vc_restaurant_open_hours', true );
        }
    } else {
        $hours_text = get_post_meta( $pid, 'vc_restaurant_open_hours', true );
    }
    $delivery = get_post_meta( $pid, 'vc_restaurant_delivery', true ) === '1';
    $cuisines = wp_get_post_terms( $pid, 'vc_cuisine', [ 'fields' => 'names' ] );
    $locs     = wp_get_post_terms( $pid, 'vc_location', [ 'fields' => 'names' ] );
    
    // Obter rating
    $rating = [];
    if ( function_exists( 'vc_restaurant_get_rating' ) ) {
        $rating = vc_restaurant_get_rating( $pid );
    } elseif ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
        $rating = \VC\Utils\Rating_Helper::get_rating( $pid );
    }
    
    // Verificar se está aberto
    $is_open = false;
    $next_open_time = null;
    if ( function_exists( 'vc_restaurant_is_open' ) ) {
        $is_open = vc_restaurant_is_open( $pid );
    } elseif ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
        $is_open = \VC\Utils\Schedule_Helper::is_open( $pid );
        if ( ! $is_open ) {
            $next_open_time = \VC\Utils\Schedule_Helper::get_next_open_time( $pid );
        }
    }

    ob_start();
    ?>
    <article class="vc-card vc-restaurant" data-id="<?php echo esc_attr( (string) $pid ); ?>">
      <a class="vc-card__link" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
        <div class="vc-card__thumb">
          <?php echo get_the_post_thumbnail( $pid, 'medium' ); ?>
        </div>
        <div class="vc-card__body">
          <h3 class="vc-card__title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
          <div class="vc-card__status">
            <?php if ( $is_open ) : ?>
              <span class="vc-badge vc-badge--open" aria-label="<?php echo esc_attr__( 'Restaurante aberto', 'vemcomer' ); ?>">
                <span class="vc-status-dot vc-status-dot--open"></span>
                <?php echo esc_html__( 'Aberto', 'vemcomer' ); ?>
              </span>
            <?php else : ?>
              <span class="vc-badge vc-badge--closed" aria-label="<?php echo esc_attr__( 'Restaurante fechado', 'vemcomer' ); ?>">
                <span class="vc-status-dot vc-status-dot--closed"></span>
                <?php echo esc_html__( 'Fechado', 'vemcomer' ); ?>
              </span>
              <?php if ( $next_open_time && isset( $next_open_time['time'] ) ) : ?>
                <span class="vc-next-open"><?php echo esc_html( sprintf( __( 'Abre às %s', 'vemcomer' ), $next_open_time['time'] ) ); ?></span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <?php if ( ! empty( $rating ) && $rating['count'] > 0 ) : ?>
            <div class="vc-card__rating">
              <div class="vc-rating-stars" aria-label="<?php echo esc_attr( sprintf( __( 'Avaliação: %.1f de 5 estrelas', 'vemcomer' ), $rating['avg'] ) ); ?>">
                <?php
                $avg_rounded = round( $rating['avg'] * 2 ) / 2; // Arredondar para 0.5
                $full_stars = floor( $avg_rounded );
                $half_star = ( $avg_rounded - $full_stars ) >= 0.5;
                $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
                
                // Estrelas cheias
                for ( $i = 0; $i < $full_stars; $i++ ) {
                    echo '<span class="vc-star vc-star--full">★</span>';
                }
                // Meia estrela
                if ( $half_star ) {
                    echo '<span class="vc-star vc-star--half">★</span>';
                }
                // Estrelas vazias
                for ( $i = 0; $i < $empty_stars; $i++ ) {
                    echo '<span class="vc-star vc-star--empty">☆</span>';
                }
                ?>
              </div>
              <span class="vc-rating-text"><?php echo esc_html( $rating['formatted'] ); ?></span>
            </div>
          <?php endif; ?>
          <?php if ( $address ) : ?><p class="vc-card__line"><?php echo esc_html( $address ); ?></p><?php endif; ?>
          <?php if ( ! empty( $schedule ) ) : ?>
            <p class="vc-card__line vc-card__hours">
              <?php
              $day_names_pt = [
                  'monday'    => __( 'Seg', 'vemcomer' ),
                  'tuesday'   => __( 'Ter', 'vemcomer' ),
                  'wednesday' => __( 'Qua', 'vemcomer' ),
                  'thursday'  => __( 'Qui', 'vemcomer' ),
                  'friday'    => __( 'Sex', 'vemcomer' ),
                  'saturday'  => __( 'Sáb', 'vemcomer' ),
                  'sunday'    => __( 'Dom', 'vemcomer' ),
              ];
              $hours_parts = [];
              foreach ( $schedule as $day => $day_data ) {
                  if ( ! empty( $day_data['enabled'] ) && ! empty( $day_data['periods'] ) ) {
                      $periods_str = [];
                      foreach ( $day_data['periods'] as $period ) {
                          $open = $period['open'] ?? '';
                          $close = $period['close'] ?? '';
                          if ( $open && $close ) {
                              $periods_str[] = $open . ' - ' . $close;
                          }
                      }
                      if ( ! empty( $periods_str ) ) {
                          $day_label = $day_names_pt[ $day ] ?? ucfirst( $day );
                          $hours_parts[] = $day_label . ': ' . implode( ', ', $periods_str );
                      }
                  }
              }
              if ( ! empty( $hours_parts ) ) {
                  echo esc_html( implode( ' | ', $hours_parts ) );
              }
              ?>
            </p>
          <?php elseif ( $hours_text ) : ?>
            <p class="vc-card__line"><?php echo esc_html( $hours_text ); ?></p>
          <?php endif; ?>
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
