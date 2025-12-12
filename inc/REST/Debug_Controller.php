<?php
/**
 * Debug_Controller — Endpoint REST para capturar TODAS as variáveis do sistema
 * @package VemComerCore
 */

namespace VC\REST;

use VC\Services\Restaurant_Status_Service;
use VC\Utils\Restaurant_Helper;
use VC\Utils\Category_Helper;
use VC\Utils\Cuisine_Helper;
use function VC\Logging\log_event;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Debug_Controller extends WP_REST_Controller {
    
    protected $namespace = 'vemcomer/v1';
    protected $rest_base = 'debug';

    public function init(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/debug/state', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_full_state' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/phpinfo', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_phpinfo' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/globals', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_globals' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/hooks', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_hooks' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/rest-routes', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_rest_routes' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/current-user', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_current_user_data' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/restaurant-state', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_restaurant_state' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/restaurant-status', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_restaurant_status' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/restaurant-relations', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_restaurant_relations' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );

        register_rest_route( $this->namespace, '/debug/archetypes', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [ $this, 'get_archetypes_debug' ],
            'permission_callback' => [ $this, 'can_access_debug' ],
        ] );
    }

    public function can_access_debug( WP_REST_Request $request ): bool {
        // Apenas em ambiente local ou para administradores
        if ( defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE === 'local' ) {
            return true;
        }
        
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        return user_can( $user, 'manage_options' ) || in_array( 'administrator', $user->roles, true );
    }

    public function get_full_state( WP_REST_Request $request ): WP_REST_Response {
        $state = [
            'timestamp' => current_time( 'mysql', true ),
            'wordpress' => $this->get_wordpress_state(),
            'php' => $this->get_php_state(),
            'constants' => $this->get_constants(),
            'globals' => $this->get_globals_data(),
            'current_user' => $this->get_current_user_data_internal(),
            'restaurant' => $this->get_restaurant_state_internal(),
            'options' => $this->get_options(),
            'transients' => $this->get_transients(),
            'hooks' => $this->get_hooks_data(),
            'rest_routes' => $this->get_rest_routes_data(),
            'post_types' => $this->get_post_types(),
            'taxonomies' => $this->get_taxonomies(),
            'performance' => $this->get_performance_metrics(),
        ];

        return new WP_REST_Response( $state, 200 );
    }

    private function get_wordpress_state(): array {
        global $wp_version, $wp_db_version, $tinymce_version, $required_php_version, $required_mysql_version;

        return [
            'version' => $wp_version ?? 'unknown',
            'db_version' => $wp_db_version ?? 'unknown',
            'tinymce_version' => $tinymce_version ?? 'unknown',
            'required_php_version' => $required_php_version ?? 'unknown',
            'required_mysql_version' => $required_mysql_version ?? 'unknown',
            'is_multisite' => is_multisite(),
            'is_admin' => is_admin(),
            'is_ajax' => wp_doing_ajax(),
            'is_rest' => defined( 'REST_REQUEST' ) && REST_REQUEST,
            'is_cron' => wp_doing_cron(),
            'memory_limit' => ini_get( 'memory_limit' ),
            'max_execution_time' => ini_get( 'max_execution_time' ),
        ];
    }

    private function get_php_state(): array {
        return [
            'version' => PHP_VERSION,
            'sapi' => php_sapi_name(),
            'extensions' => get_loaded_extensions(),
            'ini_settings' => [
                'memory_limit' => ini_get( 'memory_limit' ),
                'max_execution_time' => ini_get( 'max_execution_time' ),
                'post_max_size' => ini_get( 'post_max_size' ),
                'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
                'max_input_vars' => ini_get( 'max_input_vars' ),
                'display_errors' => ini_get( 'display_errors' ),
                'error_reporting' => error_reporting(),
            ],
            'memory_usage' => [
                'current' => memory_get_usage( true ),
                'peak' => memory_get_peak_usage( true ),
                'current_mb' => round( memory_get_usage( true ) / 1024 / 1024, 2 ),
                'peak_mb' => round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ),
            ],
        ];
    }

    private function get_constants(): array {
        $constants = [];
        $wp_constants = [
            'ABSPATH', 'WP_CONTENT_DIR', 'WP_CONTENT_URL', 'WP_PLUGIN_DIR', 'WP_PLUGIN_URL',
            'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY', 'SCRIPT_DEBUG', 'SAVEQUERIES',
            'WP_ENVIRONMENT_TYPE', 'WP_CACHE', 'COMPRESS_CSS', 'COMPRESS_SCRIPTS',
            'CONCATENATE_SCRIPTS', 'ENFORCE_GZIP',
        ];
        $vc_constants = [
            'VEMCOMER_CORE_VERSION', 'VEMCOMER_CORE_FILE', 'VEMCOMER_CORE_DIR', 'VEMCOMER_CORE_URL',
            'VEMCOMER_CORE_PATH', 'VC_DEBUG', 'VEMCOMER_DISABLE_CHECKOUT',
        ];

        foreach ( array_merge( $wp_constants, $vc_constants ) as $constant ) {
            if ( defined( $constant ) ) {
                $value = constant( $constant );
                // Não incluir paths completos por segurança, apenas indicar que existe
                if ( strpos( $constant, 'PATH' ) !== false || strpos( $constant, 'DIR' ) !== false || strpos( $constant, 'URL' ) !== false || strpos( $constant, 'FILE' ) !== false ) {
                    $constants[ $constant ] = $value ? 'defined' : 'not defined';
                } else {
                    $constants[ $constant ] = $value;
                }
            }
        }

        return $constants;
    }

    private function get_globals_data(): array {
        global $wpdb, $wp_query, $post, $wp_rewrite, $wp_roles, $wp_object_cache;

        $globals = [
            'wpdb' => [
                'last_query' => $wpdb->last_query ?? null,
                'last_error' => $wpdb->last_error ?? null,
                'num_queries' => $wpdb->num_queries ?? 0,
                'queries' => defined( 'SAVEQUERIES' ) && SAVEQUERIES ? ( $wpdb->queries ?? [] ) : 'disabled',
            ],
            'wp_query' => $wp_query ? [
                'is_single' => $wp_query->is_single ?? false,
                'is_page' => $wp_query->is_page ?? false,
                'is_admin' => $wp_query->is_admin ?? false,
                'post_count' => $wp_query->post_count ?? 0,
                'found_posts' => $wp_query->found_posts ?? 0,
            ] : null,
            'post' => $post ? [
                'ID' => $post->ID ?? null,
                'post_type' => $post->post_type ?? null,
                'post_status' => $post->post_status ?? null,
            ] : null,
            'wp_rewrite' => $wp_rewrite ? [
                'permalink_structure' => $wp_rewrite->permalink_structure ?? null,
            ] : null,
        ];

        return $globals;
    }

    private function get_current_user_data_internal(): array {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->ID ) {
            return [ 'logged_in' => false ];
        }

        $user_meta = get_user_meta( $user->ID );
        $all_meta = [];
        foreach ( $user_meta as $key => $value ) {
            $all_meta[ $key ] = is_array( $value ) && count( $value ) === 1 ? $value[0] : $value;
        }

        return [
            'logged_in' => true,
            'ID' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'roles' => $user->roles,
            'caps' => $user->allcaps,
            'capabilities' => array_keys( array_filter( $user->allcaps ) ),
            'meta' => $all_meta,
            'restaurant_id' => (int) get_user_meta( $user->ID, 'vc_restaurant_id', true ),
        ];
    }

    public function get_current_user_data( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( $this->get_current_user_data_internal(), 200 );
    }

    private function get_restaurant_state_internal(): array {
        $user_id = get_current_user_id();
        $restaurant_id = Restaurant_Helper::get_restaurant_id_for_user( $user_id );

        if ( $restaurant_id <= 0 ) {
            return [ 'found' => false ];
        }

        $restaurant = get_post( $restaurant_id );
        if ( ! $restaurant ) {
            return [ 'found' => false ];
        }

        $post_meta = get_post_meta( $restaurant_id );
        $all_meta = [];
        foreach ( $post_meta as $key => $value ) {
            $all_meta[ $key ] = is_array( $value ) && count( $value ) === 1 ? $value[0] : $value;
        }

        // Buscar termos das taxonomias
        $cuisine_terms = wp_get_post_terms( $restaurant_id, 'vc_cuisine', [ 'fields' => 'all' ] );
        $menu_categories = wp_get_post_terms( $restaurant_id, 'vc_menu_category', [ 'fields' => 'all' ] );
        $facilities = wp_get_post_terms( $restaurant_id, 'vc_facility', [ 'fields' => 'all' ] );

        return [
            'found' => true,
            'ID' => $restaurant_id,
            'title' => $restaurant->post_title,
            'status' => $restaurant->post_status,
            'author' => $restaurant->post_author,
            'post_meta' => $all_meta,
            'taxonomies' => [
                'vc_cuisine' => array_map( function( $term ) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'meta' => get_term_meta( $term->term_id ),
                    ];
                }, is_array( $cuisine_terms ) && ! is_wp_error( $cuisine_terms ) ? $cuisine_terms : [] ),
                'vc_menu_category' => array_map( function( $term ) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'meta' => get_term_meta( $term->term_id ),
                    ];
                }, is_array( $menu_categories ) && ! is_wp_error( $menu_categories ) ? $menu_categories : [] ),
                'vc_facility' => array_map( function( $term ) {
                    return [
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    ];
                }, is_array( $facilities ) && ! is_wp_error( $facilities ) ? $facilities : [] ),
            ],
        ];
    }

    public function get_restaurant_state( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( $this->get_restaurant_state_internal(), 200 );
    }

    private function get_options(): array {
        $important_options = [
            'active_plugins',
            'vemcomer_settings',
            'vemcomer_menu_categories_seeded',
            'vemcomer_cuisines_seeded',
            'vemcomer_facilities_seeded',
            'vemcomer_addon_catalog_seeded',
            'vemcomer_addon_items_updated',
        ];

        $options = [];
        foreach ( $important_options as $option_name ) {
            $value = get_option( $option_name );
            if ( $value !== false ) {
                $options[ $option_name ] = $value;
            }
        }

        return $options;
    }

    private function get_transients(): array {
        global $wpdb;
        $transients = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' OR option_name LIKE '_transient_timeout_%' 
            LIMIT 50",
            ARRAY_A
        );

        $result = [];
        foreach ( $transients as $transient ) {
            $result[ $transient['option_name'] ] = maybe_unserialize( $transient['option_value'] );
        }

        return $result;
    }

    private function get_hooks_data(): array {
        global $wp_filter;
        $hooks = [];

        if ( ! isset( $wp_filter ) || ! is_array( $wp_filter ) ) {
            return [ 'error' => 'Hooks not available' ];
        }

        $vc_hooks = [];
        foreach ( $wp_filter as $hook_name => $hook_data ) {
            if ( strpos( $hook_name, 'vemcomer' ) !== false || strpos( $hook_name, 'vc_' ) !== false ) {
                $vc_hooks[ $hook_name ] = [
                    'callbacks_count' => is_array( $hook_data->callbacks ) ? count( $hook_data->callbacks ) : 0,
                ];
            }
        }

        return [
            'total_hooks' => count( $wp_filter ),
            'vc_hooks' => $vc_hooks,
        ];
    }

    public function get_hooks( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( $this->get_hooks_data(), 200 );
    }

    private function get_rest_routes_data(): array {
        $rest_server = rest_get_server();
        $routes = $rest_server->get_routes();
        
        $vc_routes = [];
        foreach ( $routes as $route => $handlers ) {
            if ( strpos( $route, '/vemcomer/v1/' ) !== false ) {
                $vc_routes[ $route ] = [
                    'methods' => array_keys( $handlers[0]['methods'] ?? [] ),
                    'callback' => isset( $handlers[0]['callback'] ) ? 'callable' : 'not callable',
                ];
            }
        }

        return [
            'total_routes' => count( $routes ),
            'vc_routes' => $vc_routes,
        ];
    }

    public function get_rest_routes( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( $this->get_rest_routes_data(), 200 );
    }

    private function get_post_types(): array {
        $post_types = get_post_types( [], 'objects' );
        $vc_post_types = [];

        foreach ( $post_types as $post_type => $object ) {
            if ( strpos( $post_type, 'vc_' ) === 0 ) {
                $vc_post_types[ $post_type ] = [
                    'label' => $object->label ?? '',
                    'public' => $object->public ?? false,
                    'has_archive' => $object->has_archive ?? false,
                    'supports' => $object->supports ?? [],
                ];
            }
        }

        return $vc_post_types;
    }

    private function get_taxonomies(): array {
        $taxonomies = get_taxonomies( [], 'objects' );
        $vc_taxonomies = [];

        foreach ( $taxonomies as $taxonomy => $object ) {
            if ( strpos( $taxonomy, 'vc_' ) === 0 ) {
                $vc_taxonomies[ $taxonomy ] = [
                    'label' => $object->label ?? '',
                    'public' => $object->public ?? false,
                    'hierarchical' => $object->hierarchical ?? false,
                    'object_type' => $object->object_type ?? [],
                ];
            }
        }

        return $vc_taxonomies;
    }

    private function get_performance_metrics(): array {
        global $wpdb;

        return [
            'queries_count' => $wpdb->num_queries ?? 0,
            'memory_usage_mb' => round( memory_get_usage( true ) / 1024 / 1024, 2 ),
            'peak_memory_mb' => round( memory_get_peak_usage( true ) / 1024 / 1024, 2 ),
            'load_time' => function_exists( 'timer_stop' ) ? timer_stop( 0, 3 ) : 'N/A',
        ];
    }

    public function get_phpinfo( WP_REST_Request $request ): WP_REST_Response {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();

        return new WP_REST_Response( [
            'phpinfo' => $phpinfo,
        ], 200 );
    }

    public function get_globals( WP_REST_Request $request ): WP_REST_Response {
        return new WP_REST_Response( $this->get_globals_data(), 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/debug/restaurant-status
     * Retorna o status completo do restaurante do usuário
     */
    public function get_restaurant_status( WP_REST_Request $request ): WP_REST_Response {
        $user_id = get_current_user_id();
        $status = Restaurant_Status_Service::get_status_for_user( $user_id );

        return new WP_REST_Response( $status, 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/debug/restaurant-relations
     * Retorna todas as relações do restaurante (produtos, categorias, etc.)
     */
    public function get_restaurant_relations( WP_REST_Request $request ): WP_REST_Response {
        $user_id = get_current_user_id();
        $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );

        if ( ! $restaurant ) {
            return new WP_REST_Response( [
                'found' => false,
                'message' => __( 'Restaurante não encontrado para este usuário.', 'vemcomer' ),
            ], 404 );
        }

        $restaurant_id = $restaurant->ID;

        // Contar produtos
        $products_query = new \WP_Query( [
            'post_type'      => 'vc_menu_item',
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'no_found_rows'  => false,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => '_vc_restaurant_id',
                    'value' => $restaurant_id,
                ],
            ],
        ] );

        $products_count = $products_query->found_posts;
        wp_reset_postdata();

        // Buscar categorias
        $categories = Category_Helper::query_restaurant_categories( $restaurant_id );

        // Contar produtos por categoria
        $products_by_category = [];
        foreach ( $categories as $category ) {
            $count_query = new \WP_Query( [
                'post_type'      => 'vc_menu_item',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'no_found_rows'  => false,
                'fields'         => 'ids',
                'tax_query'      => [
                    [
                        'taxonomy' => 'vc_menu_category',
                        'field'    => 'term_id',
                        'terms'    => $category->term_id,
                    ],
                ],
                'meta_query'     => [
                    [
                        'key'   => '_vc_restaurant_id',
                        'value' => $restaurant_id,
                    ],
                ],
            ] );

            $products_by_category[ $category->term_id ] = [
                'name'  => $category->name,
                'count' => $count_query->found_posts,
            ];

            wp_reset_postdata();
        }

        return new WP_REST_Response( [
            'restaurant_id'         => $restaurant_id,
            'restaurant_title'       => $restaurant->post_title,
            'products_total'         => $products_count,
            'categories_total'       => count( $categories ),
            'categories'             => array_map( function( $term ) {
                return [
                    'id'    => $term->term_id,
                    'name'  => $term->name,
                    'slug'  => $term->slug,
                    'count' => $term->count,
                ];
            }, $categories ),
            'products_by_category'   => $products_by_category,
            'status'                 => Restaurant_Status_Service::get_status_for_user( $user_id ),
        ], 200 );
    }

    /**
     * GET /wp-json/vemcomer/v1/debug/archetypes
     * Debug de arquétipos para um restaurante
     */
    public function get_archetypes_debug( WP_REST_Request $request ): WP_REST_Response {
        $restaurant_id = (int) $request->get_param( 'restaurant_id' );
        
        if ( $restaurant_id <= 0 ) {
            $user_id = get_current_user_id();
            $restaurant = Restaurant_Helper::get_restaurant_for_user( $user_id );
            if ( $restaurant ) {
                $restaurant_id = $restaurant->ID;
            }
        }

        if ( $restaurant_id <= 0 ) {
            return new WP_REST_Response( [
                'error' => 'Restaurante não encontrado',
            ], 404 );
        }

        // Buscar cuisines do restaurante
        $cuisines = wp_get_object_terms( $restaurant_id, 'vc_cuisine', [ 'fields' => 'all' ] );
        
        $cuisine_details = [];
        if ( ! is_wp_error( $cuisines ) && ! empty( $cuisines ) ) {
            foreach ( $cuisines as $cuisine ) {
                $archetype = Cuisine_Helper::get_archetype_for_cuisine( $cuisine );
                $archetype_meta = get_term_meta( $cuisine->term_id, '_vc_cuisine_archetype', true );
                $is_primary = get_term_meta( $cuisine->term_id, '_vc_is_primary_cuisine', true );
                
                $cuisine_details[] = [
                    'id' => $cuisine->term_id,
                    'name' => $cuisine->name,
                    'slug' => $cuisine->slug,
                    'is_primary' => $is_primary === '1',
                    'archetype_meta' => $archetype_meta,
                    'archetype_resolved' => $archetype,
                ];
            }
        }

        // Buscar arquétipos do restaurante
        $archetypes = Cuisine_Helper::get_archetypes_for_restaurant( $restaurant_id );

        // Buscar categorias recomendadas
        $catalog_categories = get_terms( [
            'taxonomy'   => 'vc_menu_category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => '_vc_is_catalog_category',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
        ] );

        $categories_by_archetype = [];
        if ( ! is_wp_error( $catalog_categories ) && ! empty( $catalog_categories ) ) {
            foreach ( $catalog_categories as $category ) {
                $recommended_archetypes = get_term_meta( $category->term_id, '_vc_recommended_for_archetypes', true );
                $recommended_cuisines = get_term_meta( $category->term_id, '_vc_recommended_for_cuisines', true );
                
                $category_archetypes = [];
                if ( ! empty( $recommended_archetypes ) ) {
                    $category_archetypes = json_decode( $recommended_archetypes, true ) ?: [];
                }
                
                $matches = ! empty( array_intersect( $archetypes, $category_archetypes ) );
                
                $categories_by_archetype[] = [
                    'id' => $category->term_id,
                    'name' => $category->name,
                    'recommended_for_archetypes' => $category_archetypes,
                    'recommended_for_cuisines' => json_decode( $recommended_cuisines, true ) ?: [],
                    'matches_restaurant_archetypes' => $matches,
                ];
            }
        }

        return new WP_REST_Response( [
            'restaurant_id' => $restaurant_id,
            'cuisines' => $cuisine_details,
            'archetypes' => $archetypes,
            'catalog_categories' => $categories_by_archetype,
        ], 200 );
    }
}

