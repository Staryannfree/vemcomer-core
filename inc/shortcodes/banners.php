<?php
/**
 * [vc_banners]
 * Atributos:
 *  - limit (opcional, default 5)
 *  - orderby (opcional, default order)
 *  - restaurant_id (opcional, filtrar por restaurante)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'vc_banners', function( $atts = [] ) {
    vc_sc_mark_used();

    // Enfileirar assets
    wp_enqueue_style( 'vemcomer-front' );
    wp_enqueue_script( 'vemcomer-front' );
    wp_enqueue_style( 'vemcomer-banners' );

    $a = shortcode_atts([
        'limit'        => '5',
        'orderby'      => 'order',
        'restaurant_id' => '',
    ], $atts, 'vc_banners' );

    $limit = max( 1, min( 20, (int) $a['limit'] ) );
    $orderby = in_array( $a['orderby'], [ 'order', 'date', 'title' ], true ) ? $a['orderby'] : 'order';
    $restaurant_id = $a['restaurant_id'] ? (int) $a['restaurant_id'] : 0;

    $meta_query = [];
    $meta_query[] = [
        'key'   => '_vc_banner_active',
        'value' => '1',
    ];

    if ( $restaurant_id > 0 ) {
        $meta_query[] = [
            'key'   => '_vc_banner_restaurant_id',
            'value' => (string) $restaurant_id,
        ];
    }

    $q = new WP_Query([
        'post_type'      => 'vc_banner',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'orderby'        => $orderby === 'order' ? 'meta_value_num' : $orderby,
        'meta_key'       => $orderby === 'order' ? '_vc_banner_order' : '',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ]);

    if ( ! $q->have_posts() ) {
        return '';
    }

    ob_start();
    ?>
    <div class="vc-banners" data-limit="<?php echo esc_attr( (string) $limit ); ?>">
        <div class="vc-banners__slider">
            <?php while ( $q->have_posts() ) : $q->the_post(); ?>
                <?php
                $banner_id = get_the_ID();
                $image_id = get_post_thumbnail_id( $banner_id );
                $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
                $link = get_post_meta( $banner_id, '_vc_banner_link', true );
                $title = get_the_title();
                $restaurant_id_banner = (int) get_post_meta( $banner_id, '_vc_banner_restaurant_id', true );
                $size = (string) get_post_meta( $banner_id, '_vc_banner_size', true );
                if ( empty( $size ) ) {
                    $size = 'medium'; // Tamanho padrão
                }
                $size_class = 'vc-banner-item--' . esc_attr( $size );
                ?>
                <div class="vc-banner-item <?php echo esc_attr( $size_class ); ?>">
                    <?php if ( $link ) : ?>
                        <a href="<?php echo esc_url( $link ); ?>" class="vc-banner-item__link">
                    <?php endif; ?>
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="vc-banner-item__image" loading="lazy" />
                    <?php endif; ?>
                    <?php if ( $title ) : ?>
                        <div class="vc-banner-item__overlay">
                            <h3 class="vc-banner-item__title"><?php echo esc_html( $title ); ?></h3>
                        </div>
                    <?php endif; ?>
                    <?php if ( $link ) : ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
    wp_reset_postdata();
    return ob_get_clean();
});

