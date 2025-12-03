<?php
/**
 * Onboarding_Controller — REST endpoints para o wizard de onboarding
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Utils\Onboarding_Helper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Onboarding_Controller {
    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // GET: Status do onboarding
        register_rest_route( 'vemcomer/v1', '/onboarding/status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_status' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );

        // POST: Salvar passo do onboarding
        register_rest_route( 'vemcomer/v1', '/onboarding/step', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'save_step' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );

        // POST: Completar onboarding
        register_rest_route( 'vemcomer/v1', '/onboarding/complete', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [ $this, 'complete' ],
            'permission_callback' => [ $this, 'can_access' ],
        ] );
    }

    public function can_access(): bool {
        return is_user_logged_in() && ( current_user_can( 'edit_posts' ) || user_can( get_current_user_id(), 'lojista' ) );
    }

    public function get_status( WP_REST_Request $request ): WP_REST_Response {
        $restaurant_id = $request->get_param( 'restaurant_id' );
        $status        = Onboarding_Helper::get_onboarding_status( $restaurant_id ? (int) $restaurant_id : null );

        return new WP_REST_Response( $status, 200 );
    }

    public function save_step( WP_REST_Request $request ): WP_REST_Response {
        $restaurant = $this->get_restaurant_for_user();
        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ], 404 );
        }

        $payload = (array) $request->get_json_params();
        $step    = isset( $payload['step'] ) ? (int) $payload['step'] : 0;
        $step_slug = isset( $payload['step_slug'] ) ? sanitize_text_field( $payload['step_slug'] ) : '';

        if ( $step < 1 || $step > 7 ) {
            return new WP_REST_Response( [ 'message' => __( 'Passo inválido.', 'vemcomer' ) ], 400 );
        }

        // Processar dados do passo
        $result = $this->process_step_data( $restaurant->ID, $step, $payload );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [ 'message' => $result->get_error_message() ], 400 );
        }

        // Marcar passo como concluído
        if ( $step_slug ) {
            Onboarding_Helper::mark_step_completed( $restaurant->ID, $step_slug );
        }
        Onboarding_Helper::update_current_step( $restaurant->ID, $step );

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Passo salvo com sucesso.', 'vemcomer' ),
        ], 200 );
    }

    public function complete( WP_REST_Request $request ): WP_REST_Response {
        $restaurant = $this->get_restaurant_for_user();
        if ( ! $restaurant ) {
            return new WP_REST_Response( [ 'message' => __( 'Restaurante não encontrado.', 'vemcomer' ) ], 404 );
        }

        Onboarding_Helper::complete_onboarding( $restaurant->ID );

        return new WP_REST_Response( [
            'success' => true,
            'message' => __( 'Onboarding concluído com sucesso!', 'vemcomer' ),
        ], 200 );
    }

    /**
     * Processa os dados de cada passo do wizard
     */
    private function process_step_data( int $restaurant_id, int $step, array $payload ) {
        switch ( $step ) {
            case 1: // Tipo de restaurante
                return $this->save_cuisine_type( $restaurant_id, $payload );

            case 2: // Dados básicos
                return $this->save_basic_data( $restaurant_id, $payload );

            case 3: // Endereço e horários
                return $this->save_address_schedule( $restaurant_id, $payload );

            case 4: // Categorias do cardápio
                return $this->save_menu_categories( $restaurant_id, $payload );

            case 5: // Primeiros produtos
                return $this->save_products( $restaurant_id, $payload );

            case 6: // Adicionais
                return $this->save_addons( $restaurant_id, $payload );

            default:
                return true;
        }
    }

    private function save_cuisine_type( int $restaurant_id, array $payload ) {
        if ( ! isset( $payload['cuisine_ids'] ) || ! is_array( $payload['cuisine_ids'] ) ) {
            return new WP_Error( 'invalid_data', __( 'IDs de categorias não fornecidos.', 'vemcomer' ) );
        }

        $cuisine_ids = array_map( 'intval', $payload['cuisine_ids'] );
        $cuisine_ids = array_slice( array_unique( array_filter( $cuisine_ids ) ), 0, 3 ); // Máximo 3

        if ( empty( $cuisine_ids ) ) {
            return new WP_Error( 'invalid_data', __( 'Selecione pelo menos uma categoria.', 'vemcomer' ) );
        }

        $primary   = array_shift( $cuisine_ids );
        $secondary = array_slice( $cuisine_ids, 0, 2 );

        update_post_meta( $restaurant_id, '_vc_primary_cuisine', $primary );
        update_post_meta( $restaurant_id, '_vc_secondary_cuisines', wp_json_encode( $secondary ) );

        // Atualizar taxonomia
        if ( taxonomy_exists( 'vc_cuisine' ) ) {
            $all_ids = array_merge( [ $primary ], $secondary );
            wp_set_object_terms( $restaurant_id, $all_ids, 'vc_cuisine', false );
        }

        return true;
    }

    private function save_basic_data( int $restaurant_id, array $payload ) {
        $name = isset( $payload['name'] ) ? sanitize_text_field( $payload['name'] ) : '';
        $whatsapp = isset( $payload['whatsapp'] ) ? sanitize_text_field( $payload['whatsapp'] ) : '';

        if ( empty( $name ) ) {
            return new WP_Error( 'invalid_data', __( 'Nome da loja é obrigatório.', 'vemcomer' ) );
        }

        if ( empty( $whatsapp ) ) {
            return new WP_Error( 'invalid_data', __( 'Telefone/WhatsApp é obrigatório.', 'vemcomer' ) );
        }

        // Atualizar título do post
        wp_update_post( [
            'ID'         => $restaurant_id,
            'post_title' => $name,
        ] );

        // Atualizar WhatsApp
        update_post_meta( $restaurant_id, 'vc_restaurant_whatsapp', $whatsapp );

        // Logo (opcional)
        if ( isset( $payload['logo'] ) && ! empty( $payload['logo'] ) ) {
            $logo_url = $this->handle_image_upload( $payload['logo'], $restaurant_id, 'logo' );
            if ( $logo_url ) {
                update_post_meta( $restaurant_id, 'vc_restaurant_logo', $logo_url );
            }
        }

        return true;
    }

    private function save_address_schedule( int $restaurant_id, array $payload ) {
        // Endereço
        $address = isset( $payload['address'] ) ? sanitize_text_field( $payload['address'] ) : '';
        $neighborhood = isset( $payload['neighborhood'] ) ? sanitize_text_field( $payload['neighborhood'] ) : '';
        $city = isset( $payload['city'] ) ? sanitize_text_field( $payload['city'] ) : '';
        $zipcode = isset( $payload['zipcode'] ) ? sanitize_text_field( $payload['zipcode'] ) : '';

        if ( empty( $address ) ) {
            return new WP_Error( 'invalid_data', __( 'Endereço é obrigatório.', 'vemcomer' ) );
        }

        update_post_meta( $restaurant_id, 'vc_restaurant_address', $address );

        // Coordenadas (se fornecidas)
        if ( isset( $payload['lat'] ) && isset( $payload['lng'] ) ) {
            update_post_meta( $restaurant_id, 'vc_restaurant_lat', (float) $payload['lat'] );
            update_post_meta( $restaurant_id, 'vc_restaurant_lng', (float) $payload['lng'] );
        }

        // Métodos de atendimento
        $delivery_enabled = isset( $payload['delivery'] ) && $payload['delivery'] === true;
        $pickup_enabled   = isset( $payload['pickup'] ) && $payload['pickup'] === true;

        update_post_meta( $restaurant_id, 'vc_restaurant_delivery', $delivery_enabled ? '1' : '0' );

        // Horários - converter formato do wizard para formato do sistema
        if ( isset( $payload['schedule'] ) && is_array( $payload['schedule'] ) ) {
            $schedule_valid = false;
            $schedule_formatted = [];
            
            $days_map = [
                'seg' => 'monday',
                'ter' => 'tuesday',
                'qua' => 'wednesday',
                'qui' => 'thursday',
                'sex' => 'friday',
                'sab' => 'saturday',
                'dom' => 'sunday',
            ];

            foreach ( $days_map as $slug => $meta_key ) {
                $day_data = $payload['schedule'][ $slug ] ?? [];
                $enabled = ! empty( $day_data['enabled'] );
                $ranges = $day_data['ranges'] ?? [];

                $periods = [];
                if ( is_array( $ranges ) ) {
                    foreach ( $ranges as $range ) {
                        $open  = isset( $range['open'] ) ? sanitize_text_field( (string) $range['open'] ) : '';
                        $close = isset( $range['close'] ) ? sanitize_text_field( (string) $range['close'] ) : '';
                        if ( $open || $close ) {
                            $periods[] = [ 'open' => $open, 'close' => $close ];
                            $schedule_valid = true;
                        }
                    }
                }

                $schedule_formatted[ $meta_key ] = [
                    'enabled' => $enabled,
                    'periods' => $periods ?: [],
                ];
            }

            if ( ! $schedule_valid ) {
                return new WP_Error( 'invalid_data', __( 'Configure pelo menos um dia de funcionamento.', 'vemcomer' ) );
            }

            update_post_meta( $restaurant_id, '_vc_restaurant_schedule', wp_json_encode( $schedule_formatted ) );
        } else {
            return new WP_Error( 'invalid_data', __( 'Horários não fornecidos.', 'vemcomer' ) );
        }

        return true;
    }

    private function save_menu_categories( int $restaurant_id, array $payload ) {
        if ( ! isset( $payload['category_names'] ) || ! is_array( $payload['category_names'] ) ) {
            return new WP_Error( 'invalid_data', __( 'Categorias não fornecidas.', 'vemcomer' ) );
        }

        $category_names = array_filter( array_map( 'sanitize_text_field', $payload['category_names'] ) );

        if ( empty( $category_names ) ) {
            return new WP_Error( 'invalid_data', __( 'Selecione pelo menos uma categoria.', 'vemcomer' ) );
        }

        // Criar categorias diretamente usando o método do controller
        $controller = new Menu_Categories_Controller();
        $order      = 0;

        foreach ( $category_names as $name ) {
            $order++;
            // Criar request simulado
            $request = new WP_REST_Request( 'POST', '/vemcomer/v1/menu-categories' );
            $request->set_body_params( [
                'name'  => $name,
                'order' => $order,
            ] );
            
            // Verificar permissão
            if ( ! $controller->can_manage_categories() ) {
                continue;
            }
            
            // Chamar o método diretamente
            $result = $controller->create_category( $request );
            
            // Se retornar erro, logar mas continuar
            if ( is_wp_error( $result ) ) {
                error_log( 'Erro ao criar categoria no onboarding: ' . $result->get_error_message() );
            }
        }

        return true;
    }

    private function save_products( int $restaurant_id, array $payload ) {
        if ( ! isset( $payload['products'] ) || ! is_array( $payload['products'] ) ) {
            return new WP_Error( 'invalid_data', __( 'Produtos não fornecidos.', 'vemcomer' ) );
        }

        if ( empty( $payload['products'] ) ) {
            return new WP_Error( 'invalid_data', __( 'Cadastre pelo menos um produto.', 'vemcomer' ) );
        }

        // Criar produtos diretamente usando o método do controller
        $controller = new Menu_Items_Controller();

        foreach ( $payload['products'] as $product_data ) {
            // Preparar dados do produto
            $product_payload = [
                'title'       => isset( $product_data['name'] ) ? sanitize_text_field( $product_data['name'] ) : '',
                'description' => isset( $product_data['description'] ) ? sanitize_textarea_field( $product_data['description'] ) : '',
                'price'       => isset( $product_data['price'] ) ? (float) $product_data['price'] : 0,
                'category_id' => isset( $product_data['category_id'] ) ? (int) $product_data['category_id'] : 0,
                'is_available' => true,
            ];

            if ( isset( $product_data['image'] ) && ! empty( $product_data['image'] ) ) {
                $product_payload['image'] = $product_data['image'];
            }

            $request = new WP_REST_Request( 'POST', '/vemcomer/v1/menu-items' );
            $request->set_body_params( $product_payload );
            
            // Verificar permissão
            if ( ! $controller->can_manage_menu_items() ) {
                continue;
            }
            
            // Chamar o método diretamente
            $result = $controller->create_menu_item( $request );
            
            // Se retornar erro, logar mas continuar
            if ( is_wp_error( $result ) ) {
                error_log( 'Erro ao criar produto no onboarding: ' . $result->get_error_message() );
            }
        }

        return true;
    }

    private function save_addons( int $restaurant_id, array $payload ) {
        // Adicionais são opcionais, então não retorna erro se não houver
        if ( ! isset( $payload['addon_groups'] ) || ! is_array( $payload['addon_groups'] ) ) {
            return true;
        }

        // Usar o endpoint existente de addons
        $controller = new Addon_Catalog_Controller();

        foreach ( $payload['addon_groups'] as $group_id ) {
            // Copiar grupo para a loja e linkar aos produtos
            // Implementação simplificada - pode ser expandida depois
        }

        return true;
    }

    private function handle_image_upload( string $image_data, int $restaurant_id, string $type = 'logo' ): ?string {
        // Se for data:image, converter para arquivo
        if ( strpos( $image_data, 'data:image' ) === 0 ) {
            // Reutilizar lógica do Merchant_Settings_Controller
            $controller = new Merchant_Settings_Controller();
            $reflection = new \ReflectionClass( $controller );
            $method     = $reflection->getMethod( 'maybe_handle_data_image' );
            $method->setAccessible( true );
            $result = $method->invoke( $controller, $image_data, 'logo', $restaurant_id );

            if ( $result && is_string( $result ) ) {
                return $result;
            }
        }

        return null;
    }

    private function get_restaurant_for_user() {
        if ( function_exists( '\\vc_marketplace_current_restaurant' ) ) {
            return \vc_marketplace_current_restaurant();
        }

        $user = wp_get_current_user();
        if ( ! $user || 0 === $user->ID ) {
            return null;
        }

        $meta_id = (int) get_user_meta( $user->ID, 'vc_restaurant_id', true );
        if ( $meta_id ) {
            $restaurant = get_post( $meta_id );
            if ( $restaurant && 'vc_restaurant' === $restaurant->post_type ) {
                return $restaurant;
            }
        }

        return null;
    }
}

