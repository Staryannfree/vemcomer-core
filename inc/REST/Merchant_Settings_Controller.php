<?php
/**
 * Merchant_Settings_Controller — Atualização de dados do restaurante via painel marketplace
 */

namespace VC\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Merchant_Settings_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( 'vemcomer/v1', '/merchant/settings', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'update_settings' ],
            'permission_callback' => [ $this, 'can_edit' ],
        ] );
    }

    public function can_edit(): bool {
        // Tenta resolver o restaurante do usuário logado
        $restaurant = $this->get_restaurant_for_user();

        // Admin sempre pode editar (útil para testes e suporte)
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        if ( ! $restaurant instanceof \WP_Post ) {
            // Sem restaurante vinculado: bloqueia para lojista / usuários comuns
            return false;
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        // Dono do restaurante (autor do post) sempre pode editar
        if ( (int) $restaurant->post_author === (int) $user_id ) {
            return true;
        }

        // Verifica capabilities mapeadas pelo CPT (edit_post) e caps customizadas
        if ( current_user_can( 'edit_post', $restaurant->ID ) ) {
            return true;
        }

        if ( current_user_can( 'edit_vc_restaurant', $restaurant->ID ) ) {
            return true;
        }

        return false;
    }

    public function update_settings( WP_REST_Request $request ) {
        $restaurant = $this->get_restaurant_for_user();

        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado para este usuário.', 'vemcomer' ) ], 404 );
        }

        $payload = (array) $request->get_json_params();

        $this->update_post_core( $restaurant->ID, $payload );
        $this->update_meta_fields( $restaurant->ID, $payload );

        return new WP_REST_Response( [ 'success' => true ] );
    }

    private function get_restaurant_for_user() {
        // Se o helper global do marketplace existir, reutiliza a mesma lógica
        if ( function_exists( '\\vc_marketplace_current_restaurant' ) ) {
            $candidate = \vc_marketplace_current_restaurant();
            if ( $candidate instanceof \WP_Post && 'vc_restaurant' === $candidate->post_type ) {
                return $candidate;
            }
        }

        $user = wp_get_current_user();

        if ( ! ( $user instanceof \WP_User ) || 0 === $user->ID ) {
            return null;
        }

        $filtered = (int) apply_filters( 'vemcomer/restaurant_id_for_user', 0, $user );
        if ( $filtered > 0 ) {
            $candidate = get_post( $filtered );
            if ( $candidate instanceof \WP_Post && 'vc_restaurant' === $candidate->post_type ) {
                return $candidate;
            }
        }

        $meta_id = (int) get_user_meta( $user->ID, 'vc_restaurant_id', true );
        if ( $meta_id ) {
            $candidate = get_post( $meta_id );
            if ( $candidate instanceof \WP_Post && 'vc_restaurant' === $candidate->post_type ) {
                return $candidate;
            }
        }

        $q = new \WP_Query([
            'post_type'      => 'vc_restaurant',
            'author'         => $user->ID,
            'posts_per_page' => 1,
            'post_status'    => [ 'publish', 'pending', 'draft' ],
            'no_found_rows'  => true,
        ]);

        if ( $q->have_posts() ) {
            $candidate = $q->posts[0];
            wp_reset_postdata();
            return $candidate instanceof \WP_Post ? $candidate : null;
        }

        wp_reset_postdata();
        return null;
    }

    private function update_post_core( int $restaurant_id, array $payload ): void {
        $title = isset( $payload['title'] ) ? sanitize_text_field( (string) $payload['title'] ) : null;
        $excerpt = isset( $payload['description'] ) ? wp_kses_post( (string) $payload['description'] ) : null;

        if ( $title ) {
            wp_update_post([
                'ID'         => $restaurant_id,
                'post_title' => $title,
            ]);
        }

        if ( null !== $excerpt ) {
            wp_update_post([
                'ID'           => $restaurant_id,
                'post_excerpt' => $excerpt,
            ]);
        }
    }

    private function update_meta_fields( int $restaurant_id, array $payload ): void {
        $map = [
            'cnpj'         => VC_META_RESTAURANT_FIELDS['cnpj'],
            'whatsapp'     => VC_META_RESTAURANT_FIELDS['whatsapp'],
            'site'         => VC_META_RESTAURANT_FIELDS['site'],
            'address'      => VC_META_RESTAURANT_FIELDS['address'],
            'lat'          => VC_META_RESTAURANT_FIELDS['lat'],
            'lng'          => VC_META_RESTAURANT_FIELDS['lng'],
            'access_url'   => VC_META_RESTAURANT_FIELDS['access_url'],
            'delivery_eta' => VC_META_RESTAURANT_FIELDS['delivery_eta'],
            'delivery_fee' => VC_META_RESTAURANT_FIELDS['delivery_fee'],
            'delivery_type'=> VC_META_RESTAURANT_FIELDS['delivery_type'],
            'orders_count' => VC_META_RESTAURANT_FIELDS['orders_count'],
            'plan_name'    => VC_META_RESTAURANT_FIELDS['plan_name'],
            'plan_limit'   => VC_META_RESTAURANT_FIELDS['plan_limit'],
            'plan_used'    => VC_META_RESTAURANT_FIELDS['plan_used'],
            'horario_legado' => VC_META_RESTAURANT_FIELDS['open_hours'],
        ];

        foreach ( $map as $key => $meta_key ) {
            if ( array_key_exists( $key, $payload ) ) {
                $value = $payload[ $key ];
                $value = is_string( $value ) ? wp_kses_post( $value ) : $value;
                update_post_meta( $restaurant_id, $meta_key, $value );
            }
        }

        if ( array_key_exists( 'delivery', $payload ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery'], ! empty( $payload['delivery'] ) ? '1' : '0' );
        }

        if ( isset( $payload['logo'] ) ) {
            $logo_url = $this->maybe_handle_data_image( $payload['logo'], 'logo', $restaurant_id );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['logo'], esc_url_raw( $logo_url ) );
        }

        if ( isset( $payload['cover'] ) ) {
            $cover_url = $this->maybe_handle_data_image( $payload['cover'], 'cover', $restaurant_id );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['cover'], esc_url_raw( $cover_url ) );
        }

        if ( isset( $payload['banners'] ) && is_array( $payload['banners'] ) ) {
            $lines = array_filter( array_map( 'trim', $payload['banners'] ) );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['banners'], implode( "\n", $lines ) );
        }

        if ( isset( $payload['highlights'] ) && is_array( $payload['highlights'] ) ) {
            $lines = array_filter( array_map( 'trim', $payload['highlights'] ) );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['highlight_tags'], implode( "\n", $lines ) );
        }

        if ( isset( $payload['filters'] ) && is_array( $payload['filters'] ) ) {
            $lines = array_filter( array_map( 'trim', $payload['filters'] ) );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['menu_filters'], implode( "\n", $lines ) );
        }

        if ( isset( $payload['payments'] ) && is_array( $payload['payments'] ) ) {
            $lines = array_filter( array_map( 'trim', $payload['payments'] ) );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['payment_methods'], implode( "\n", $lines ) );
        }

        if ( isset( $payload['facilities'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['facilities'], wp_kses_post( (string) $payload['facilities'] ) );
        }

        if ( isset( $payload['observations'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['observations'], wp_kses_post( (string) $payload['observations'] ) );
        }

        if ( isset( $payload['faq'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['faq'], wp_kses_post( (string) $payload['faq'] ) );
        }

        if ( isset( $payload['reservation_enabled'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['reservation_enabled'], ! empty( $payload['reservation_enabled'] ) ? '1' : '0' );
        }

        if ( isset( $payload['reservation_link'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['reservation_link'], esc_url_raw( (string) $payload['reservation_link'] ) );
        }

        if ( isset( $payload['reservation_phone'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['reservation_phone'], sanitize_text_field( (string) $payload['reservation_phone'] ) );
        }

        if ( isset( $payload['reservation_message'] ) ) {
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['reservation_notes'], wp_kses_post( (string) $payload['reservation_message'] ) );
        }

        if ( isset( $payload['shipping'] ) && is_array( $payload['shipping'] ) ) {
            $shipping = $payload['shipping'];
            if ( isset( $shipping['radius'] ) ) {
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery_radius'], (string) $shipping['radius'] );
            }
            if ( isset( $shipping['price_per_km'] ) ) {
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery_price_per_km'], (string) $shipping['price_per_km'] );
            }
            if ( isset( $shipping['base_fee'] ) ) {
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery_base_price'], (string) $shipping['base_fee'] );
            }
            if ( isset( $shipping['free_above'] ) ) {
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery_free_above'], (string) $shipping['free_above'] );
            }
            if ( isset( $shipping['min_order'] ) ) {
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery_min_order'], (string) $shipping['min_order'] );
            }

            if ( isset( $shipping['neighborhoods'] ) && is_array( $shipping['neighborhoods'] ) ) {
                $prepared = [];
                foreach ( $shipping['neighborhoods'] as $item ) {
                    $name  = isset( $item['name'] ) ? sanitize_text_field( (string) $item['name'] ) : '';
                    $price = isset( $item['price'] ) ? (float) $item['price'] : null;
                    if ( $name ) {
                        $prepared[] = [ 'name' => $name, 'price' => $price ];
                    }
                }
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['delivery_neighborhoods'], wp_json_encode( $prepared ) );
            }
        }

        if ( isset( $payload['holidays'] ) && is_array( $payload['holidays'] ) ) {
            $clean = array_values( array_filter( array_map( 'trim', $payload['holidays'] ) ) );
            update_post_meta( $restaurant_id, '_vc_restaurant_holidays', wp_json_encode( $clean ) );
        }

        if ( isset( $payload['schedule'] ) && is_array( $payload['schedule'] ) ) {
            $this->persist_schedule( $restaurant_id, $payload['schedule'] );
        }

        // Categoria principal e secundárias (vc_cuisine)
        if ( taxonomy_exists( 'vc_cuisine' ) ) {
            $primary   = isset( $payload['primary_cuisine'] ) ? (int) $payload['primary_cuisine'] : 0;
            $secondary = [];
            if ( isset( $payload['secondary_cuisines'] ) && is_array( $payload['secondary_cuisines'] ) ) {
                $secondary = array_map( 'intval', $payload['secondary_cuisines'] );
            }

            $secondary = array_slice( array_values( array_unique( array_filter( $secondary ) ) ), 0, 3 );

            update_post_meta( $restaurant_id, '_vc_primary_cuisine', $primary ?: '' );
            update_post_meta( $restaurant_id, '_vc_secondary_cuisines', wp_json_encode( $secondary ) );

            $terms = [];
            if ( $primary ) {
                $terms[] = $primary;
            }
            if ( $secondary ) {
                $terms = array_merge( $terms, $secondary );
            }
            $terms = array_values( array_unique( array_filter( $terms ) ) );

            if ( ! empty( $terms ) ) {
                wp_set_object_terms( $restaurant_id, $terms, 'vc_cuisine', false );
            }
        }
    }

    private function persist_schedule( int $restaurant_id, array $schedule ): void {
        $days = [
            'seg' => 'monday',
            'ter' => 'tuesday',
            'qua' => 'wednesday',
            'qui' => 'thursday',
            'sex' => 'friday',
            'sab' => 'saturday',
            'dom' => 'sunday',
        ];

        $payload = [];

        foreach ( $days as $slug => $meta_key ) {
            $info = $schedule[ $slug ] ?? [];
            $enabled = ! empty( $info['enabled'] );
            $ranges  = $info['ranges'] ?? [];

            $periods = [];
            if ( is_array( $ranges ) ) {
                foreach ( $ranges as $range ) {
                    $open  = isset( $range['open'] ) ? sanitize_text_field( (string) $range['open'] ) : '';
                    $close = isset( $range['close'] ) ? sanitize_text_field( (string) $range['close'] ) : '';
                    if ( $open || $close ) {
                        $periods[] = [ 'open' => $open, 'close' => $close ];
                    }
                }
            }

            $payload[ $meta_key ] = [
                'enabled' => $enabled,
                'periods' => $periods ?: [ [ 'open' => '09:00', 'close' => '22:00' ] ],
            ];
        }

        update_post_meta( $restaurant_id, '_vc_restaurant_schedule', wp_json_encode( $payload ) );
    }

    private function maybe_handle_data_image( string $value, string $prefix, int $restaurant_id ): string {
        $value = trim( $value );
        if ( ! str_starts_with( $value, 'data:image' ) ) {
            return $value;
        }

        $decoded = $this->decode_data_image( $value );
        if ( is_wp_error( $decoded ) ) {
            return '';
        }

        $filename = sprintf( '%s-%d-%s.png', $prefix, $restaurant_id, wp_generate_password( 6, false ) );
        $upload   = wp_upload_bits( $filename, null, $decoded );

        if ( $upload['error'] ) {
            return '';
        }

        return esc_url_raw( $upload['url'] );
    }

    private function decode_data_image( string $data ) {
        $parts = explode( ',', $data, 2 );
        if ( count( $parts ) !== 2 ) {
            return new WP_Error( 'invalid_image', __( 'Imagem inválida.', 'vemcomer' ) );
        }

        $decoded = base64_decode( $parts[1] );
        if ( false === $decoded ) {
            return new WP_Error( 'invalid_image', __( 'Imagem inválida.', 'vemcomer' ) );
        }

        return $decoded;
    }
}

