<?php
/**
 * Melhorias de SEO - Schema.org, Meta Tags, Breadcrumbs
 * @package VemComer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Adiciona Schema.org markup para restaurantes
 */
add_action( 'wp_head', 'vemcomer_add_restaurant_schema' );
function vemcomer_add_restaurant_schema() {
    if ( ! is_singular( 'vc_restaurant' ) ) {
        return;
    }

    global $post;
    $restaurant_id = $post->ID;

    $rating = [];
    if ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
        $rating = \VC\Utils\Rating_Helper::get_rating( $restaurant_id );
    }

    $address = get_post_meta( $restaurant_id, '_vc_address', true );
    $phone = get_post_meta( $restaurant_id, '_vc_phone', true );
    $lat = get_post_meta( $restaurant_id, 'vc_restaurant_lat', true );
    $lng = get_post_meta( $restaurant_id, 'vc_restaurant_lng', true );

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'FoodEstablishment',
        'name' => get_the_title( $restaurant_id ),
        'description' => wp_strip_all_tags( get_the_excerpt( $restaurant_id ) ),
        'url' => get_permalink( $restaurant_id ),
    ];

    if ( $address ) {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $address,
        ];
    }

    if ( $phone ) {
        $schema['telephone'] = $phone;
    }

    if ( $lat && $lng ) {
        $schema['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }

    if ( ! empty( $rating ) && $rating['count'] > 0 ) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating['avg'],
            'reviewCount' => $rating['count'],
        ];
    }

    if ( has_post_thumbnail( $restaurant_id ) ) {
        $image_url = get_the_post_thumbnail_url( $restaurant_id, 'large' );
        if ( $image_url ) {
            $schema['image'] = $image_url;
        }
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}

/**
 * Adiciona meta tags Open Graph
 */
add_action( 'wp_head', 'vemcomer_add_og_tags' );
function vemcomer_add_og_tags() {
    if ( ! is_singular( 'vc_restaurant' ) ) {
        return;
    }

    global $post;
    $restaurant_id = $post->ID;

    $title = get_the_title( $restaurant_id );
    $description = wp_strip_all_tags( get_the_excerpt( $restaurant_id ) );
    $url = get_permalink( $restaurant_id );
    $image = has_post_thumbnail( $restaurant_id ) ? get_the_post_thumbnail_url( $restaurant_id, 'large' ) : '';

    echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
    echo '<meta property="og:type" content="restaurant" />' . "\n";
    if ( $image ) {
        echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
    }
}

/**
 * Adiciona breadcrumbs
 */
add_action( 'vemcomer_before_content', 'vemcomer_breadcrumbs' );
function vemcomer_breadcrumbs() {
    if ( is_front_page() ) {
        return;
    }

    $items = [
        [
            'label' => __( 'Início', 'vemcomer' ),
            'url' => home_url( '/' ),
        ],
    ];

    if ( is_singular( 'vc_restaurant' ) ) {
        $items[] = [
            'label' => __( 'Restaurantes', 'vemcomer' ),
            'url' => get_post_type_archive_link( 'vc_restaurant' ),
        ];
        $items[] = [
            'label' => get_the_title(),
            'url' => get_permalink(),
        ];
    } elseif ( is_post_type_archive( 'vc_restaurant' ) ) {
        $items[] = [
            'label' => __( 'Restaurantes', 'vemcomer' ),
            'url' => get_post_type_archive_link( 'vc_restaurant' ),
        ];
    }

    if ( count( $items ) <= 1 ) {
        return;
    }

    ob_start();
    ?>
    <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumbs', 'vemcomer' ); ?>">
        <ol class="breadcrumbs__list">
            <?php foreach ( $items as $index => $item ) : ?>
                <li class="breadcrumbs__item">
                    <?php if ( $index < count( $items ) - 1 ) : ?>
                        <a href="<?php echo esc_url( $item['url'] ); ?>" class="breadcrumbs__link">
                            <?php echo esc_html( $item['label'] ); ?>
                        </a>
                        <span class="breadcrumbs__separator">/</span>
                    <?php else : ?>
                        <span class="breadcrumbs__current"><?php echo esc_html( $item['label'] ); ?></span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
    echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

