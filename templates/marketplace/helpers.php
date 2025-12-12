<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'vc_marketplace_current_restaurant' ) ) {
    function vc_marketplace_current_restaurant(): ?WP_Post {
        return \VC\Utils\Restaurant_Helper::get_restaurant_for_user();
    }
}

if ( ! function_exists( 'vc_marketplace_collect_restaurant_data' ) ) {
    function vc_marketplace_collect_restaurant_data( ?WP_Post $restaurant = null ): array {
        $data = [
            'id'          => $restaurant instanceof WP_Post ? $restaurant->ID : 0,
            'nome'        => $restaurant instanceof WP_Post ? get_the_title( $restaurant ) : '',
            'descricao'   => $restaurant instanceof WP_Post ? wp_strip_all_tags( get_the_excerpt( $restaurant ) ) : '',
            'cnpj'        => '',
            'endereco'    => '',
            'bairro'      => '',
            'cidade'      => '',
            'whatsapp'    => '',
            'site'        => '',
            'instagram'   => '',
            'logo'        => '',
            'cover'       => '',
            'banners'     => [],
            'fav_bairro'  => false,
            'destaque'    => false,
            'metodos'     => [
                'delivery' => false,
                'retirada' => false,
                'local'    => false,
                'reserva'  => false,
            ],
            'pagamentos'  => [],
            'tipos'       => [],
            'filters'     => [],
            'destaques'   => [],
            'facilities'  => '',
            'observations' => '',
            'faq'          => '',
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
            'holidays'   => [],
            'horario_legado' => '',
            'shipping'    => [
                'mode'          => 'radius',
                'radius'        => null,
                'base_fee'      => null,
                'price_per_km'  => null,
                'free_above'    => null,
                'min_order'     => null,
                'neighborhoods' => [],
            ],
            'geo' => [
                'lat' => null,
                'lng' => null,
            ],
            'orders_count' => null,
            'delivery_eta' => '',
            'delivery_fee' => '',
            'delivery_type' => '',
            'access_url' => '',
            'plan_name'  => '',
            'plan_limit' => null,
            'plan_used'  => null,
            'permalink' => $restaurant instanceof WP_Post ? get_permalink( $restaurant ) : '',
        ];

        if ( ! ( $restaurant instanceof WP_Post ) ) {
            return $data;
        }

        $data['cnpj']      = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['cnpj'], true );
        $data['endereco']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['address'], true );
        $data['whatsapp']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['whatsapp'], true );
        $data['site']      = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['site'], true );
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
        $data['destaque']   = ! empty( $highlight );

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

        // Facilities (legado: texto livre; novo: taxonomia vc_facility)
        $facilities_legacy = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['facilities'], true );
        $facilities_terms = wp_get_post_terms( $restaurant->ID, 'vc_facility', [ 'fields' => 'all' ] );
        $data['facilities'] = $facilities_legacy; // Mantém legado para compatibilidade
        $data['facilities_selected'] = [];
        if ( ! is_wp_error( $facilities_terms ) && $facilities_terms ) {
            $data['facilities_selected'] = array_map( function( $t ) {
                return (int) $t->term_id;
            }, $facilities_terms );
        }
        
        // Busca todas as facilities disponíveis (organizadas por grupo)
        $all_facilities = get_terms( [
            'taxonomy'   => 'vc_facility',
            'hide_empty' => false,
            'parent'     => 0, // Apenas grupos (pais)
        ] );
        $data['facilities_groups'] = [];
        if ( ! is_wp_error( $all_facilities ) && $all_facilities ) {
            foreach ( $all_facilities as $group ) {
                $children = get_terms( [
                    'taxonomy'   => 'vc_facility',
                    'hide_empty' => false,
                    'parent'     => $group->term_id,
                ] );
                if ( ! is_wp_error( $children ) && $children ) {
                    $data['facilities_groups'][] = [
                        'id'    => $group->term_id,
                        'name'  => $group->name,
                        'items' => array_map( function( $child ) {
                            return [
                                'id'   => $child->term_id,
                                'name' => $child->name,
                            ];
                        }, $children ),
                    ];
                }
            }
        }
        
        $data['observations'] = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['observations'], true );
        $data['faq']          = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['faq'], true );

        // Cozinhas / categorias
        $cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'all' ] );
        if ( ! is_wp_error( $cuisine_terms ) && $cuisine_terms ) {
            $data['tipos'] = array_values( array_map( function( $t ) {
                return $t->name;
            }, $cuisine_terms ) );
        }

        // Categoria principal e secundárias (por ID) - apenas para pré-seleção
        $primary_cuisine   = (int) get_post_meta( $restaurant->ID, '_vc_primary_cuisine', true );
        $secondary_raw     = get_post_meta( $restaurant->ID, '_vc_secondary_cuisines', true );
        $secondary_cuisines = [];
        if ( is_string( $secondary_raw ) && '' !== $secondary_raw ) {
            $decoded = json_decode( $secondary_raw, true );
            if ( is_array( $decoded ) ) {
                $secondary_cuisines = array_map( 'intval', $decoded );
            }
        }

        // Se não houver meta, infere a partir da taxonomia do restaurante
        $restaurant_cuisine_terms = wp_get_post_terms( $restaurant->ID, 'vc_cuisine', [ 'fields' => 'all' ] );
        if ( ! $primary_cuisine && ! empty( $restaurant_cuisine_terms ) && ! is_wp_error( $restaurant_cuisine_terms ) ) {
            $ids = array_map( fn( $t ) => (int) $t->term_id, $restaurant_cuisine_terms );
            if ( $ids ) {
                $primary_cuisine = (int) array_shift( $ids );
                $secondary_cuisines = array_slice( $ids, 0, 2 ); // Máximo 2 secundárias
            }
        }

        $data['primary_cuisine']    = $primary_cuisine ?: null;
        $data['secondary_cuisines'] = $secondary_cuisines;

        // Lista de TODAS as categorias disponíveis (não apenas as do restaurante)
        // Isso permite que o lojista escolha qualquer categoria, não apenas a que já tem
        $all_cuisine_terms = get_terms( [
            'taxonomy'   => 'vc_cuisine',
            'hide_empty' => false, // Inclui todas, mesmo sem restaurantes
            'orderby'    => 'name',
            'order'      => 'ASC',
        ] );
        
        if ( ! is_wp_error( $all_cuisine_terms ) && $all_cuisine_terms ) {
            // Filtra apenas termos filhos (não os grupos pais que começam com "grupo-")
            $child_terms = array_filter( $all_cuisine_terms, function( $term ) {
                // Exclui termos que são grupos pais (parent = 0 e slug começa com "grupo-")
                if ( $term->parent === 0 && str_starts_with( (string) $term->slug, 'grupo-' ) ) {
                    return false;
                }
                // Inclui apenas termos que têm parent (são filhos) ou são termos raiz válidos
                return $term->parent !== 0 || ! str_starts_with( (string) $term->slug, 'grupo-' );
            } );
            
            $data['cuisine_terms'] = array_values( array_map( function( $t ) {
                return [
                    'id'   => (int) $t->term_id,
                    'name' => $t->name,
                ];
            }, $child_terms ) );
        } else {
            $data['cuisine_terms'] = [];
        }

        $filters_raw = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['menu_filters'], true );
        if ( $filters_raw ) {
            $filters = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $filters_raw ) ?: [] ) );
            if ( $filters ) {
                $data['filters'] = $filters;
            }
        }

        $destaques_raw = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['highlight_tags'], true );
        if ( $destaques_raw ) {
            $tags = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', $destaques_raw ) ?: [] ) );
            if ( $tags ) {
                $data['destaques'] = $tags;
            }
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

        $holidays_json = get_post_meta( $restaurant->ID, '_vc_restaurant_holidays', true );
        $holidays      = $holidays_json ? json_decode( $holidays_json, true ) : [];
        if ( is_array( $holidays ) ) {
            $data['holidays'] = array_values( array_filter( array_map( 'trim', $holidays ) ) );
        }

        $data['horario_legado'] = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['open_hours'], true );

        $radius      = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_radius'], true );
        $base_price  = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_base_price'], true );
        $price_per_km = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_price_per_km'], true );
        $free_above   = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_free_above'], true );
        $min_order    = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_min_order'], true );
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
        if ( $free_above !== '' ) {
            $data['shipping']['free_above'] = (float) $free_above;
        }
        if ( $min_order !== '' ) {
            $data['shipping']['min_order'] = (float) $min_order;
        }

        $data['geo']['lat'] = (float) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['lat'], true );
        $data['geo']['lng'] = (float) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['lng'], true );

        $orders_count = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['orders_count'], true );
        $data['orders_count'] = $orders_count !== '' ? (int) $orders_count : null;

        $data['delivery_eta']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_eta'], true );
        $data['delivery_fee']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_fee'], true );
        $data['delivery_type'] = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['delivery_type'], true );

        $data['access_url'] = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['access_url'], true );

        $data['plan_name']  = (string) get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['plan_name'], true );
        $plan_limit_meta    = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['plan_limit'], true );
        $plan_used_meta     = get_post_meta( $restaurant->ID, VC_META_RESTAURANT_FIELDS['plan_used'], true );
        $data['plan_limit'] = $plan_limit_meta !== '' ? (int) $plan_limit_meta : null;
        $data['plan_used']  = $plan_used_meta !== '' ? (int) $plan_used_meta : null;

        return $data;
    }
}
