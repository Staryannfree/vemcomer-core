<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vc_marketplace_current_restaurant' ) ) {
    function vc_marketplace_current_restaurant(): ?WP_Post {
        $user = wp_get_current_user();
        if ( ! ( $user instanceof WP_User ) || 0 === $user->ID ) {
            return null;
        }

        $filtered = (int) apply_filters( 'vemcomer/restaurant_id_for_user', 0, $user );
        if ( $filtered > 0 ) {
            $candidate = get_post( $filtered );
            if ( $candidate instanceof WP_Post && 'vc_restaurant' === $candidate->post_type ) {
                return $candidate;
            }
        }

        $meta_id = (int) get_user_meta( $user->ID, 'vc_restaurant_id', true );
        if ( $meta_id ) {
            $candidate = get_post( $meta_id );
            if ( $candidate instanceof WP_Post && 'vc_restaurant' === $candidate->post_type ) {
                return $candidate;
            }
        }

        $q = new WP_Query([
            'post_type'      => 'vc_restaurant',
            'author'         => $user->ID,
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'no_found_rows'  => true,
        ]);

        if ( $q->have_posts() ) {
            $candidate = $q->posts[0];
            wp_reset_postdata();
            return $candidate instanceof WP_Post ? $candidate : null;
        }

        wp_reset_postdata();
        return null;
    }
}

if ( ! function_exists( 'vc_marketplace_collect_restaurant_data' ) ) {
    function vc_marketplace_collect_restaurant_data( ?WP_Post $restaurant = null ): array {
        $data = [
            'id'          => $restaurant instanceof WP_Post ? $restaurant->ID : 0,
            'nome'        => $restaurant instanceof WP_Post ? get_the_title( $restaurant ) : '',
            'descricao'   => $restaurant instanceof WP_Post ? wp_strip_all_tags( get_the_excerpt( $restaurant ) ) : '',
            'endereco'    => '',
            'bairro'      => '',
            'cidade'      => '',
            'whatsapp'    => '',
            'instagram'   => '',
            'logo'        => '',
            'cover'       => '',
            'banners'     => [],
            'fav_bairro'  => false,
            'metodos'     => [
                'delivery' => false,
                'retirada' => false,
                'local'    => false,
                'reserva'  => false,
            ],
            'pagamentos'  => [],
            'tipos'       => [],
            'horarios'    => [
                'seg' => [ 'enabled' => false, 'ranges' => [] ],
                'ter' => [ 'enabled' => false, 'ranges' => [] ],
                'qua' => [ 'enabled' => false, 'ranges' => [] ],
                'qui' => [ 'enabled' => false, 'ranges' => [] ],
                'sex' => [ 'enabled' => false, 'ranges' => [] ],
                'sab' => [ 'enabled' => false, 'ranges' => [] ],
                'dom' => [ 'enabled' => false, 'ranges' => [] ],
            ],
            'reservation_message' => '',
            'shipping'    => [
                'mode'          => 'radius',
                'radius'        => null,
                'base_fee'      => null,
                'price_per_km'  => null,
                'neighborhoods' => [],
            ],
            'permalink' => $restaurant instanceof WP_Post ? get_permalink( $restaurant ) : '',
        ];

        if ( ! ( $restaurant instanceof WP_Post ) ) {
            return $data;
        }

        $data['endereco']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['address'], true );
        $data['whatsapp']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['whatsapp'], true );
        $data['instagram'] = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['instagram'], true );
        $data['logo']      = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['logo'], true );
        $data['cover']     = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['cover'], true );

        if ( ! $data['logo'] ) {
            $thumb = get_the_post_thumbnail_url( $restaurant, 'full' );
            if ( $thumb ) {
                $data['logo'] = $thumb;
            }
        }

        $banners_raw = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['banners'], true );
        if ( $banners_raw ) {
            $parts = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $banners_raw ) ?: [] ) );
            if ( $parts ) {
                $data['banners'] = $parts;
            }
        }

        $highlight = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['featured_badge'], true );
        $data['fav_bairro'] = ! empty( $highlight );

        $delivery_enabled = (bool) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery'], true );
        $reservation_enabled = (bool) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['reservation_enabled'], true );

        $data['metodos']['delivery'] = $delivery_enabled;
        $data['metodos']['retirada'] = $delivery_enabled;
        $data['metodos']['reserva']  = $reservation_enabled;
        $data['reservation_message'] = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['reservation_notes'], true );

        $payment_raw = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['payment_methods'], true );
        if ( is_string( $payment_raw ) && $payment_raw ) {
            $methods = array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $payment_raw ) ?: [] ) );
            if ( $methods ) {
                $data['pagamentos'] = $methods;
            }
        } elseif ( is_array( $payment_raw ) ) {
            $data['pagamentos'] = array_filter( array_map( 'trim', $payment_raw ) );
        }

        $cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'names' ] );
        if ( ! is_wp_error( $cuisine_terms ) && $cuisine_terms ) {
            $data['tipos'] = array_values( $cuisine_terms );
        }

        $location_terms = wp_get_post_terms( $restaurant->ID, 'vc_location', [ 'fields' => 'names' ] );
        if ( ! is_wp_error( $location_terms ) && ! empty( $location_terms ) ) {
            $data['bairro'] = (string) $location_terms[0];
        }

        $schedule_json = get_post_meta( $restaurant->ID, '_vc_restaurant_schedule', true );
        $schedule_arr  = $schedule_json ? json_decode( $schedule_json, true ) : [];
        $days_map      = [
            'monday'    => 'seg',
            'tuesday'   => 'ter',
            'wednesday' => 'qua',
            'thursday'  => 'qui',
            'friday'    => 'sex',
            'saturday'  => 'sab',
            'sunday'    => 'dom',
        ];

        if ( is_array( $schedule_arr ) ) {
            foreach ( $days_map as $key => $slug ) {
                $enabled = ! empty( $schedule_arr[ $key ]['enabled'] );
                $periods = $schedule_arr[ $key ]['periods'] ?? [];
                $ranges  = [];
                if ( is_array( $periods ) ) {
                    foreach ( $periods as $period ) {
                        $open  = isset( $period['open'] ) ? (string) $period['open'] : '';
                        $close = isset( $period['close'] ) ? (string) $period['close'] : '';
                        if ( $open || $close ) {
                            $ranges[] = [ 'open' => $open, 'close' => $close ];
                        }
                    }
                }
                $data['horarios'][ $slug ] = [
                    'enabled' => $enabled,
                    'ranges'  => $ranges,
                ];
            }
        }

        $radius      = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_radius'], true );
        $base_price  = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_base_price'], true );
        $price_per_km = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_price_per_km'], true );
        $neighborhoods = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_neighborhoods'], true );

        if ( $neighborhoods ) {
            $decoded = json_decode( (string) $neighborhoods, true );
            if ( is_array( $decoded ) ) {
                foreach ( $decoded as $item ) {
                    $name  = isset( $item['name'] ) ? (string) $item['name'] : '';
                    $price = isset( $item['price'] ) ? (float) $item['price'] : 0.0;
                    if ( $name ) {
                        $data['shipping']['neighborhoods'][] = [ 'name' => $name, 'price' => $price ];
                    }
                }
            }
        }

        if ( $data['shipping']['neighborhoods'] ) {
            $data['shipping']['mode'] = 'neighborhood';
        }

        if ( $radius !== '' ) {
            $data['shipping']['radius'] = (float) $radius;
        }
        if ( $base_price !== '' ) {
            $data['shipping']['base_fee'] = (float) $base_price;
        }
        if ( $price_per_km !== '' ) {
            $data['shipping']['price_per_km'] = (float) $price_per_km;
        }

        return $data;
    }
}
