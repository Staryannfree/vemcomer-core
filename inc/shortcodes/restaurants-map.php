<?php
/**
 * [vc_restaurants_map] — mapa público com todos os restaurantes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode(
    'vc_restaurants_map',
    function ( $atts = [] ) {
        vc_sc_mark_used();

        $atts = shortcode_atts(
            [
                'height'      => 460,
                'radius'      => vc_default_radius(),
                'use_cluster' => '1',
            ],
            $atts,
            'vc_restaurants_map'
        );

        $height      = max( 240, (int) $atts['height'] );
        $radius      = max( 0.5, (float) $atts['radius'] );
        $use_cluster = $atts['use_cluster'] !== '0';

        $q = new WP_Query(
            [
                'post_type'      => 'vc_restaurant',
                'posts_per_page' => 500,
                'post_status'    => 'publish',
                'no_found_rows'  => true,
                'meta_query'     => [
                    [ 'key' => 'vc_restaurant_lat', 'compare' => '!=', 'value' => '' ],
                    [ 'key' => 'vc_restaurant_lng', 'compare' => '!=', 'value' => '' ],
                ],
            ]
        );

        $markers = [];
        while ( $q->have_posts() ) {
            $q->the_post();
            $rid  = get_the_ID();
            $lat  = (float) get_post_meta( $rid, 'vc_restaurant_lat', true );
            $lng  = (float) get_post_meta( $rid, 'vc_restaurant_lng', true );

            if ( ! $lat || ! $lng ) {
                continue;
            }

            $cuisines  = wp_get_post_terms( $rid, 'vc_cuisine', [ 'fields' => 'names' ] );
            $markers[] = [
                'id'      => $rid,
                'title'   => get_the_title(),
                'lat'     => $lat,
                'lng'     => $lng,
                'address' => (string) get_post_meta( $rid, 'vc_restaurant_address', true ),
                'cuisine' => ! is_wp_error( $cuisines ) ? implode( ', ', $cuisines ) : '',
                'url'     => get_permalink( $rid ),
            ];
        }
        wp_reset_postdata();

        wp_enqueue_style( 'leaflet' );
        wp_enqueue_style( 'leaflet-markercluster' );
        wp_enqueue_style( 'leaflet-markercluster-default' );
        wp_enqueue_script( 'leaflet' );
        wp_enqueue_script( 'leaflet-markercluster' );
        wp_enqueue_script( 'vemcomer-restaurants-map' );

        wp_localize_script(
            'vemcomer-restaurants-map',
            'VC_RESTAURANTS_MAP',
            [
                'markers'       => array_values( $markers ),
                'tiles'         => function_exists( 'vc_tiles_url' ) ? vc_tiles_url() : 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'defaultRadius' => $radius,
                'restBase'      => esc_url_raw( rest_url( 'vemcomer/v1' ) ),
                'useCluster'    => $use_cluster,
                'strings'       => [
                    'all'            => __( 'Mapa de restaurantes', 'vemcomer' ),
                    'allLocations'   => __( 'Pins carregados da cidade/região.', 'vemcomer' ),
                    'viewRestaurant' => __( 'Ver restaurante', 'vemcomer' ),
                    'searching'      => __( 'Buscando restaurantes próximos...', 'vemcomer' ),
                    'noGeo'          => __( 'Não foi possível obter sua localização.', 'vemcomer' ),
                    'noResults'      => __( 'Nenhum restaurante dentro desse raio.', 'vemcomer' ),
                    'found'          => __( 'Encontramos %d restaurante(s) perto de você.', 'vemcomer' ),
                ],
            ]
        );

        ob_start();
        ?>
        <div class="vc-map-board">
                <div class="vc-map-board__controls">
                        <div class="vc-map__field">
                                <label for="vc-map-radius"><?php echo esc_html__( 'Raio (km)', 'vemcomer' ); ?></label>
                                <input type="number" id="vc-map-radius" name="vc-map-radius" value="<?php echo esc_attr( (string) $radius ); ?>" min="0.5" step="0.5" />
                        </div>
                        <div class="vc-map__actions">
                                <button class="vc-btn" id="vc-map-use-location"><?php echo esc_html__( 'Usar minha localização', 'vemcomer' ); ?></button>
                                <button class="vc-btn vc-btn--ghost" id="vc-map-reset"><?php echo esc_html__( 'Mostrar todos', 'vemcomer' ); ?></button>
                        </div>
                </div>
                <p class="vc-map__status" id="vc-map-status"><?php echo esc_html__( 'Pins carregados da cidade/região.', 'vemcomer' ); ?></p>
                <div id="vc-restaurants-map" class="vc-map" style="height: <?php echo esc_attr( (string) $height ); ?>px"></div>
        </div>
        <?php
        return ob_get_clean();
    }
);
