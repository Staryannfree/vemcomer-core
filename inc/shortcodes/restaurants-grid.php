<?php
/**
 * [vc_restaurants]
 * Atributos:
 *  - cuisine="pizza"        (slug)
 *  - location="centro"      (slug)
 *  - delivery="true|false"  (bool)
 *  - search="texto"
 *  - per_page="12"
 *  - page="1"               (padrão: query var paged)
 *  - orderby="title|date"
 *  - order="ASC|DESC"
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_restaurants', function( $atts = [] ) {
    vc_sc_mark_used();

    $a = shortcode_atts([
        'cuisine'  => '',
        'location' => '',
        'delivery' => '',
        'search'   => '',
        'per_page' => '12',
        'page'     => '',
        'orderby'  => 'title',
        'order'    => 'ASC',
    ], $atts, 'vc_restaurants' );

    $paged = $a['page'] !== '' ? max(1, (int) $a['page']) : max( 1, get_query_var('paged') );

    $tax_query = [];
    if ( $a['cuisine'] ) {
        $tax_query[] = [ 'taxonomy' => 'vc_cuisine', 'field' => 'slug', 'terms' => sanitize_title( $a['cuisine'] ) ];
    }
    if ( $a['location'] ) {
        $tax_query[] = [ 'taxonomy' => 'vc_location', 'field' => 'slug', 'terms' => sanitize_title( $a['location'] ) ];
    }
    if ( count( $tax_query ) > 1 ) { $tax_query['relation'] = 'AND'; }

    $meta_query = [];
    if ( $a['delivery'] !== '' ) {
        $meta_query[] = [ 'key' => 'vc_restaurant_delivery', 'value' => vc_sc_bool( $a['delivery'] ) ? '1' : '0' ];
    }
    
    // Suportar novos filtros via GET
    $min_rating = isset( $_GET['min_rating'] ) ? (float) $_GET['min_rating'] : '';
    $is_open_now = isset( $_GET['is_open_now'] ) ? (bool) filter_var( $_GET['is_open_now'], FILTER_VALIDATE_BOOLEAN ) : false;
    $has_delivery = isset( $_GET['has_delivery'] ) ? (bool) filter_var( $_GET['has_delivery'], FILTER_VALIDATE_BOOLEAN ) : false;
    
    if ( $has_delivery ) {
        $meta_query[] = [ 'key' => '_vc_has_delivery', 'value' => '1' ];
    }

    $q = new WP_Query([
        'post_type'      => 'vc_restaurant',
        'posts_per_page' => max(1, (int) $a['per_page']),
        'paged'          => $paged,
        's'              => sanitize_text_field( $a['search'] ),
        'tax_query'      => $tax_query ?: '',
        'meta_query'     => $meta_query ?: '',
        'orderby'        => in_array( strtolower($a['orderby']), ['title','date'], true ) ? $a['orderby'] : 'title',
        'order'          => in_array( strtoupper($a['order']), ['ASC','DESC'], true ) ? strtoupper($a['order']) : 'ASC',
        'no_found_rows'  => false,
    ]);
    
    // Filtrar por rating mínimo e is_open_now após query (filtros mais complexos)
    if ( $q->have_posts() && ( $min_rating > 0 || $is_open_now ) ) {
        $filtered_posts = [];
        while ( $q->have_posts() ) {
            $q->the_post();
            $rid = get_the_ID();
            
            // Verificar rating mínimo
            if ( $min_rating > 0 ) {
                if ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
                    $rating = \VC\Utils\Rating_Helper::get_rating( $rid );
                    if ( $rating['avg'] < $min_rating ) {
                        continue;
                    }
                }
            }
            
            // Verificar se está aberto
            if ( $is_open_now ) {
                if ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
                    if ( ! \VC\Utils\Schedule_Helper::is_open( $rid ) ) {
                        continue;
                    }
                }
            }
            
            $filtered_posts[] = get_post( $rid );
        }
        wp_reset_postdata();
        
        // Recriar query com posts filtrados
        $q = new WP_Query([
            'post_type'      => 'vc_restaurant',
            'post__in'       => wp_list_pluck( $filtered_posts, 'ID' ),
            'posts_per_page' => max(1, (int) $a['per_page']),
            'paged'          => $paged,
            'orderby'        => 'post__in',
            'no_found_rows'  => false,
        ]);
    }

    ob_start();
    ?>
    <div class="vc-sc vc-restaurants">
      <div class="vc-grid">
        <?php if ( $q->have_posts() ) : ?>
          <?php while ( $q->have_posts() ) : $q->the_post(); ?>
            <?php echo do_shortcode( '[vc_restaurant]' ); ?>
          <?php endwhile; ?>
        <?php else : ?>
          <p class="vc-empty"><?php echo esc_html__( 'Nenhum restaurante encontrado.', 'vemcomer' ); ?></p>
        <?php endif; ?>
      </div>
      <?php if ( $q->max_num_pages > 1 ) : ?>
        <nav class="vc-pagination">
          <?php
            echo paginate_links([
              'total'   => $q->max_num_pages,
              'current' => $paged,
            ]);
          ?>
        </nav>
      <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
});
