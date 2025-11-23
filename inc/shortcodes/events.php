<?php
/**
 * [vc_events]
 * Atributos:
 *  - restaurant_id (opcional, filtra por restaurante)
 *  - featured (opcional, default false - só eventos em destaque)
 *  - per_page (opcional, default 10)
 *  - status (opcional: upcoming, ongoing, completed, cancelled)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_events', function( $atts = [] ) {
    vc_sc_mark_used();

    if ( ! post_type_exists( 'vc_event' ) ) {
        return '<p class="vc-empty">' . esc_html__( 'Eventos indisponíveis.', 'vemcomer' ) . '</p>';
    }

    $a = shortcode_atts( [
        'restaurant_id' => '',
        'featured'      => 'false',
        'per_page'      => '10',
        'status'        => 'upcoming',
    ], $atts, 'vc_events' );

    $restaurant_id = $a['restaurant_id'] ? (int) $a['restaurant_id'] : 0;
    $featured = filter_var( $a['featured'], FILTER_VALIDATE_BOOLEAN );
    $per_page = max( 1, (int) $a['per_page'] );
    $status = sanitize_text_field( $a['status'] );

    $args = [
        'post_type'      => 'vc_event',
        'posts_per_page' => $per_page,
        'post_status'    => 'publish',
        'orderby'        => 'meta_value',
        'meta_key'       => '_vc_event_date',
        'order'          => 'ASC',
        'meta_query'     => [],
    ];

    // Filtrar por restaurante
    if ( $restaurant_id > 0 ) {
        $args['meta_query'][] = [
            'key'   => '_vc_event_restaurant_id',
            'value' => (string) $restaurant_id,
        ];
    }

    // Filtrar por destaque
    if ( $featured ) {
        $args['meta_query'][] = [
            'key'   => '_vc_event_is_featured',
            'value' => '1',
        ];
    }

    // Filtrar por status
    if ( in_array( $status, [ 'upcoming', 'ongoing', 'completed', 'cancelled' ], true ) ) {
        $args['meta_query'][] = [
            'key'   => '_vc_event_status',
            'value' => $status,
        ];
    }

    // Filtrar apenas eventos futuros ou em andamento
    if ( in_array( $status, [ 'upcoming', 'ongoing' ], true ) ) {
        $args['meta_query'][] = [
            'key'     => '_vc_event_date',
            'value'   => date( 'Y-m-d' ),
            'compare' => '>=',
            'type'    => 'DATE',
        ];
    }

    $q = new WP_Query( $args );

    ob_start();
    ?>
    <div class="vc-sc vc-events">
        <?php if ( $q->have_posts() ) : ?>
            <div class="vc-events-list">
                <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                    <?php
                    $event_id = get_the_ID();
                    $restaurant_id_event = (int) get_post_meta( $event_id, '_vc_event_restaurant_id', true );
                    $event_date = get_post_meta( $event_id, '_vc_event_date', true );
                    $event_time = get_post_meta( $event_id, '_vc_event_time', true );
                    $event_end_time = get_post_meta( $event_id, '_vc_event_end_time', true );
                    $event_location = get_post_meta( $event_id, '_vc_event_location', true );
                    $event_price = get_post_meta( $event_id, '_vc_event_price', true );
                    $event_capacity = get_post_meta( $event_id, '_vc_event_capacity', true );
                    $event_status = get_post_meta( $event_id, '_vc_event_status', true ) ?: 'upcoming';
                    $is_featured = (bool) get_post_meta( $event_id, '_vc_event_is_featured', true );
                    $restaurant = $restaurant_id_event > 0 ? get_post( $restaurant_id_event ) : null;
                    $image_id = get_post_thumbnail_id( $event_id );
                    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';

                    // Formatar data
                    $date_formatted = '';
                    if ( $event_date ) {
                        $date_obj = DateTime::createFromFormat( 'Y-m-d', $event_date );
                        if ( $date_obj ) {
                            $date_formatted = $date_obj->format( 'd/m/Y' );
                        }
                    }
                    ?>
                    <div class="vc-event-card <?php echo $is_featured ? 'is-featured' : ''; ?>">
                        <?php if ( $image_url ) : ?>
                            <div class="vc-event-card__image">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy" />
                                <?php if ( $is_featured ) : ?>
                                    <span class="vc-event-card__badge"><?php esc_html_e( 'Destaque', 'vemcomer' ); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="vc-event-card__content">
                            <h3 class="vc-event-card__title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>

                            <?php if ( $restaurant ) : ?>
                                <div class="vc-event-card__restaurant">
                                    <a href="<?php echo esc_url( get_permalink( $restaurant_id_event ) ); ?>">
                                        <?php echo esc_html( $restaurant->post_title ); ?>
                                    </a>
                                </div>
                            <?php endif; ?>

                            <?php if ( has_excerpt() ) : ?>
                                <p class="vc-event-card__description">
                                    <?php echo wp_trim_words( get_the_excerpt(), 20 ); ?>
                                </p>
                            <?php endif; ?>

                            <div class="vc-event-card__meta">
                                <?php if ( $date_formatted ) : ?>
                                    <div class="vc-event-card__date">
                                        <span class="vc-event-card__icon">📅</span>
                                        <span><?php echo esc_html( $date_formatted ); ?></span>
                                        <?php if ( $event_time ) : ?>
                                            <span class="vc-event-card__time">
                                                <?php echo esc_html( $event_time ); ?>
                                                <?php if ( $event_end_time ) : ?>
                                                    - <?php echo esc_html( $event_end_time ); ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $event_location ) : ?>
                                    <div class="vc-event-card__location">
                                        <span class="vc-event-card__icon">📍</span>
                                        <span><?php echo esc_html( $event_location ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $event_price ) : ?>
                                    <div class="vc-event-card__price">
                                        <span class="vc-event-card__icon">💰</span>
                                        <span><?php echo esc_html( $event_price ); ?></span>
                                    </div>
                                <?php endif; ?>

                                <?php if ( $event_capacity ) : ?>
                                    <div class="vc-event-card__capacity">
                                        <span class="vc-event-card__icon">👥</span>
                                        <span><?php echo esc_html( sprintf( __( '%d vagas', 'vemcomer' ), $event_capacity ) ); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <a href="<?php the_permalink(); ?>" class="vc-event-card__button">
                                <?php esc_html_e( 'Ver Detalhes', 'vemcomer' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p class="vc-empty"><?php esc_html_e( 'Nenhum evento encontrado.', 'vemcomer' ); ?></p>
        <?php endif; ?>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
} );

