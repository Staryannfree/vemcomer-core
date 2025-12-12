<?php
/**
 * Merchant_Settings_Controller — Atualização de dados do restaurante via painel marketplace
 */

namespace VC\REST;

use VC\Utils\Restaurant_Helper;
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
        $restaurant = Restaurant_Helper::get_restaurant_for_user();

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
        $restaurant = Restaurant_Helper::get_restaurant_for_user();

        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado para este usuário.', 'vemcomer' ) ], 404 );
        }

        $payload = (array) $request->get_json_params();

        $this->update_post_core( $restaurant->ID, $payload );
        $this->update_meta_fields( $restaurant->ID, $payload );

        return new WP_REST_Response( [ 'success' => true ] );
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
            
            // Define também como featured image (imagem destacada) para aparecer no perfil
            if ( $logo_url ) {
                // Tenta encontrar o attachment pela URL (caso tenha sido criado pelo maybe_handle_data_image)
                $attachment_id = attachment_url_to_postid( $logo_url );
                if ( $attachment_id ) {
                    set_post_thumbnail( $restaurant_id, $attachment_id );
                } else {
                    // Se não encontrou, tenta criar/definir via método auxiliar
                    $this->set_logo_as_featured_image( $restaurant_id, $logo_url );
                }
            }
        }

        if ( isset( $payload['cover'] ) ) {
            $cover_url = $this->maybe_handle_data_image( $payload['cover'], 'cover', $restaurant_id );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['cover'], esc_url_raw( $cover_url ) );
        }

        if ( isset( $payload['banners'] ) && is_array( $payload['banners'] ) ) {
            // Processa cada imagem da galeria: se for data:image, cria attachment; se for URL, mantém
            $processed_urls = [];
            foreach ( $payload['banners'] as $banner_item ) {
                $banner_item = trim( (string) $banner_item );
                if ( empty( $banner_item ) ) {
                    continue;
                }
                
                // Se for data:image, processa como attachment (igual ao logo)
                if ( str_starts_with( $banner_item, 'data:image' ) ) {
                    $processed_url = $this->maybe_handle_data_image( $banner_item, 'gallery', $restaurant_id );
                    if ( $processed_url ) {
                        $processed_urls[] = $processed_url;
                    }
                } else {
                    // Se já for URL, mantém como está
                    $processed_urls[] = esc_url_raw( $banner_item );
                }
            }
            
            // Limita a 4 imagens e salva
            $processed_urls = array_slice( $processed_urls, 0, 4 );
            update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['banners'], implode( "\n", $processed_urls ) );
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

        // Facilities: novo sistema (array de IDs de termos da taxonomia vc_facility)
        if ( isset( $payload['facilities'] ) ) {
            if ( is_array( $payload['facilities'] ) ) {
                // Novo sistema: array de IDs de termos
                $facility_ids = array_map( 'intval', array_filter( $payload['facilities'], 'is_numeric' ) );
                if ( taxonomy_exists( 'vc_facility' ) ) {
                    wp_set_object_terms( $restaurant_id, $facility_ids, 'vc_facility', false );
                }
                // Mantém legado para compatibilidade
                $facility_names = [];
                foreach ( $facility_ids as $term_id ) {
                    $term = get_term( $term_id, 'vc_facility' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $facility_names[] = $term->name;
                    }
                }
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['facilities'], implode( "\n", $facility_names ) );
            } else {
                // Legado: texto livre
                update_post_meta( $restaurant_id, VC_META_RESTAURANT_FIELDS['facilities'], wp_kses_post( (string) $payload['facilities'] ) );
            }
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

        // Detecta o tipo MIME da imagem
        $mime_type = 'image/png';
        if ( str_contains( $value, 'data:image/jpeg' ) || str_contains( $value, 'data:image/jpg' ) ) {
            $mime_type = 'image/jpeg';
            $ext       = 'jpg';
        } elseif ( str_contains( $value, 'data:image/webp' ) ) {
            $mime_type = 'image/webp';
            $ext       = 'webp';
        } else {
            $ext = 'png';
        }

        $filename = sprintf( '%s-%d-%s.%s', $prefix, $restaurant_id, wp_generate_password( 6, false ), $ext );
        $upload   = wp_upload_bits( $filename, null, $decoded );

        if ( $upload['error'] ) {
            return '';
        }

        // Cria um attachment no WordPress para que possa ser usado como featured image
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $attachment_data = [
            'post_mime_type' => $mime_type,
            'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
        
        $attachment_id = wp_insert_attachment( $attachment_data, $upload['file'], $restaurant_id );
        
        if ( ! is_wp_error( $attachment_id ) ) {
            $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
            wp_update_attachment_metadata( $attachment_id, $attach_data );
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

    /**
     * Define o logo como featured image do restaurante.
     * Se a URL já for de um attachment, usa diretamente. Caso contrário, cria um attachment.
     *
     * @param int    $restaurant_id ID do restaurante.
     * @param string $logo_url      URL do logo.
     */
    private function set_logo_as_featured_image( int $restaurant_id, string $logo_url ): void {
        if ( ! $logo_url ) {
            return;
        }

        // Verifica se a URL já é de um attachment existente
        $attachment_id = attachment_url_to_postid( $logo_url );
        
        if ( $attachment_id ) {
            // Já existe um attachment, apenas define como featured image
            set_post_thumbnail( $restaurant_id, $attachment_id );
            return;
        }

        // Se não for um attachment, tenta criar um a partir da URL
        // Primeiro, verifica se é uma URL local
        $upload_dir = wp_upload_dir();
        $base_url   = $upload_dir['baseurl'];
        
        if ( str_starts_with( $logo_url, $base_url ) ) {
            // É uma URL local, tenta encontrar o arquivo
            $file_path = str_replace( $base_url, $upload_dir['basedir'], $logo_url );
            
            if ( file_exists( $file_path ) ) {
                // Cria attachment a partir do arquivo local
                $file_type = wp_check_filetype( basename( $file_path ), null );
                $attachment_data = [
                    'post_mime_type' => $file_type['type'],
                    'post_title'     => sanitize_file_name( pathinfo( $file_path, PATHINFO_FILENAME ) ),
                    'post_content'   => '',
                    'post_status'    => 'inherit',
                ];
                
                $attachment_id = wp_insert_attachment( $attachment_data, $file_path, $restaurant_id );
                
                if ( ! is_wp_error( $attachment_id ) ) {
                    require_once ABSPATH . 'wp-admin/includes/image.php';
                    $attach_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
                    wp_update_attachment_metadata( $attachment_id, $attach_data );
                    set_post_thumbnail( $restaurant_id, $attachment_id );
                }
            }
        } else {
            // É uma URL externa, faz download e cria attachment
            $this->create_attachment_from_url( $restaurant_id, $logo_url );
        }
    }

    /**
     * Cria um attachment a partir de uma URL externa.
     *
     * @param int    $restaurant_id ID do restaurante.
     * @param string $image_url     URL da imagem.
     */
    private function create_attachment_from_url( int $restaurant_id, string $image_url ): void {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Faz download temporário da imagem
        $tmp = download_url( $image_url );
        
        if ( is_wp_error( $tmp ) ) {
            return;
        }

        $file_array = [
            'name'     => basename( $image_url ),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload( $file_array, $restaurant_id );
        
        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $restaurant_id, $attachment_id );
        }
    }
}

