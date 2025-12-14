<?php
/**
 * Plugin Name: Pedevem Core
 * Description: Core do marketplace Pedevem — Instalador de páginas com shortcodes
 * Version: 0.8.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Pedevem
 * Text Domain: vemcomer
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// CRÍTICO: Detectar ativação o mais cedo possível para prevenir execução de hooks
// Verificar se estamos em processo de ativação ANTES de qualquer outra coisa
$is_activating_early = ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' && isset( $_GET['plugin'] ) )
                        || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) )
                        || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING );
if ( $is_activating_early && function_exists( 'set_transient' ) ) {
    // CRÍTICO: Suprimir erros durante detecção precoce de ativação
    $old_error_reporting = error_reporting( 0 );
    @ini_set( 'display_errors', 0 );
    
    set_transient( 'vemcomer_activating', true, 60 );
    
    // Restaurar configurações
    error_reporting( $old_error_reporting );
}

if ( ! defined( 'VEMCOMER_CORE_VERSION' ) ) {
    define( 'VEMCOMER_CORE_VERSION', '0.8.0' );
}

if ( ! defined( 'VEMCOMER_CORE_FILE' ) ) {
    define( 'VEMCOMER_CORE_FILE', __FILE__ );
}

if ( ! defined( 'VEMCOMER_CORE_DIR' ) ) {
    define( 'VEMCOMER_CORE_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'VEMCOMER_CORE_URL' ) ) {
    define( 'VEMCOMER_CORE_URL', plugin_dir_url( __FILE__ ) );
}

$vemcomer_autoload = VEMCOMER_CORE_DIR . 'vendor/autoload.php';
if ( file_exists( $vemcomer_autoload ) ) {
    require_once $vemcomer_autoload;
}

require_once VEMCOMER_CORE_DIR . 'inc/bootstrap.php';
require_once VEMCOMER_CORE_DIR . 'inc/logging.php';

// autoloads (legado + PSR-4) — mesmos dos pacotes anteriores
spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC_' ) ) {
        $path = VEMCOMER_CORE_DIR . 'inc/' . 'class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
        if ( file_exists( $path ) ) { require_once $path; }
    }
} );

spl_autoload_register( function ( $class ) {
    if ( str_starts_with( $class, 'VC\\' ) ) {
        $relative = str_replace( 'VC\\', '', $class );
        $relative = str_replace( '\\', '/', $relative );
        $path = VEMCOMER_CORE_DIR . 'inc/' . $relative . '.php';
        if ( file_exists( $path ) ) { require_once $path; }
    }
} );

require_once VEMCOMER_CORE_DIR . 'inc/helpers-sanitize.php';
// Helper central para restaurante do usuário (fonte de verdade)
require_once VEMCOMER_CORE_DIR . 'inc/Utils/Restaurant_Helper.php';
// Para desativar checkout temporariamente, comente a linha abaixo:
// define( 'VEMCOMER_DISABLE_CHECKOUT', true ); // Adicione no wp-config.php ou descomente aqui
if ( ! defined( 'VEMCOMER_DISABLE_CHECKOUT' ) || ! VEMCOMER_DISABLE_CHECKOUT ) {
    require_once VEMCOMER_CORE_DIR . 'inc/checkout.php';
}

/**
 * Corrige automaticamente o WP Pusher para PHP 8.2+
 */
function vemcomer_fix_wppusher_php82() {
    $wp_root = ABSPATH;
    $plugin_dir = $wp_root . 'wp-content/plugins/wppusher';
    
    if ( ! is_dir( $plugin_dir ) ) {
        return false;
    }
    
    $targets = [
        [
            'file' => $plugin_dir . '/Pusher/Log/Logger.php',
            'class' => 'Logger',
            'property' => 'protected string $file = \'\';',
        ],
        [
            'file' => $plugin_dir . '/Pusher/Dashboard.php',
            'class' => 'Dashboard',
            'property' => 'protected $pusher = null;',
        ],
    ];
    
    $fixed = false;
    
    foreach ( $targets as $target ) {
        $file = $target['file'];
        if ( ! file_exists( $file ) ) {
            continue;
        }
        
        $content = file_get_contents( $file );
        if ( false === $content ) {
            continue;
        }
        
        $updated = $content;
        
        // Adicionar #[\AllowDynamicProperties] se não existir
        if ( ! str_contains( $updated, '#[\\AllowDynamicProperties]' ) ) {
            $updated = preg_replace(
                '/(class\s+' . $target['class'] . '\b)/',
                "#[\\AllowDynamicProperties]\n$1",
                $updated,
                1
            );
        }
        
        // Adicionar propriedade se não existir
        if ( ! str_contains( $updated, $target['property'] ) && ! str_contains( $updated, '$' . explode( ' ', $target['property'] )[1] ) ) {
            $updated = preg_replace(
                '/(class\s+' . $target['class'] . '[^{]*\{)/',
                "$1\n    " . $target['property'] . "\n",
                $updated,
                1
            );
        }
        
        if ( $updated !== $content ) {
            file_put_contents( $file, $updated );
            $fixed = true;
        }
    }
    
    return $fixed;
}

register_activation_hook( __FILE__, function () {
    // CRÍTICO: Suprimir TODOS os erros/avisos durante ativação para evitar output
    // WordPress captura qualquer output e reporta como "output inesperado"
    $old_error_reporting = error_reporting( 0 );
    $old_display_errors = ini_get( 'display_errors' );
    @ini_set( 'display_errors', 0 );
    
    // CRÍTICO: Definir flag de ativação ANTES de qualquer operação
    // Isso previne que hooks 'init' e 'plugins_loaded' executem durante ativação
    set_transient( 'vemcomer_activating', true, 60 ); // 60 segundos para garantir
    
    // CRÍTICO: NÃO manipular output buffering - WordPress já gerencia isso
    // Interferir com ob_start/ob_end_clean causa erros "headers already sent"
    
    // Corrige WP Pusher automaticamente (sem output)
    @vemcomer_fix_wppusher_php82();

    // CRÍTICO: NÃO executar install_defaults() durante ativação
    // Isso cria muitas páginas e abre muitas conexões de banco
    // O usuário pode executar isso manualmente depois via admin
    // $already_installed = get_option( 'vemcomer_pages', false );
    // if ( ! $already_installed && class_exists( '\\VC\\Admin\\Installer' ) ) {
    //     ( new \VC\Admin\Installer() )->install_defaults();
    // }
    
    // Restaurar configurações de erro após ativação
    error_reporting( $old_error_reporting );
    if ( $old_display_errors !== false ) {
        @ini_set( 'display_errors', $old_display_errors );
    }
    
    // CRÍTICO: NÃO remover a flag imediatamente - deixar expirar após 60 segundos
    // Isso garante que hooks subsequentes não executem durante a ativação
    // delete_transient será chamado automaticamente após 60 segundos
} );

// Tentar corrigir WP Pusher automaticamente no carregamento (se necessário)
add_action( 'plugins_loaded', function () {
    // CRÍTICO: Não executar durante ativação
    if ( get_transient( 'vemcomer_activating' ) || defined( 'WP_INSTALLING' ) ) {
        return;
    }
    
    // Verifica se há erro do WP Pusher e tenta corrigir
    if ( version_compare( PHP_VERSION, '8.2.0', '>=' ) ) {
        $wppusher_fixed = get_transient( 'vemcomer_wppusher_fixed' );
        if ( ! $wppusher_fixed ) {
            vemcomer_fix_wppusher_php82();
            set_transient( 'vemcomer_wppusher_fixed', true, DAY_IN_SECONDS );
        }
    }
}, 1 );

add_action( 'plugins_loaded', function () {
    // #region agent log
    $log_file = __DIR__ . '/.cursor/debug.log';
    $is_activating = get_transient( 'vemcomer_activating' );
    $log_data = json_encode([
        'id' => 'plugins_loaded_hook',
        'timestamp' => microtime(true) * 1000,
        'location' => 'vemcomer-core.php:206',
        'message' => 'plugins_loaded hook executed',
        'data' => ['is_activating' => (bool)$is_activating, 'wp_installing' => defined('WP_INSTALLING'), 'ob_level' => ob_get_level()],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'A'
    ]) . "\n";
    @file_put_contents($log_file, $log_data, FILE_APPEND);
    // #endregion
    
    // CRÍTICO: Não executar durante ativação do plugin
    // Isso previne múltiplas conexões de banco durante ativação
    if ( $is_activating || defined( 'WP_INSTALLING' ) ) {
        // #region agent log
        $log_data = json_encode([
            'id' => 'plugins_loaded_skipped',
            'timestamp' => microtime(true) * 1000,
            'location' => 'vemcomer-core.php:209',
            'message' => 'plugins_loaded skipped due to activation',
            'data' => [],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A'
        ]) . "\n";
        @file_put_contents($log_file, $log_data, FILE_APPEND);
        // #endregion
        return;
    }
    
    // CRÍTICO: Determinar contexto ANTES de inicializar classes
    // No frontend público, não precisamos de classes admin ou REST (exceto se for requisição REST)
    $is_admin_context = is_admin();
    $is_rest_context = defined( 'REST_REQUEST' ) && REST_REQUEST;
    $is_frontend = ! $is_admin_context && ! $is_rest_context;
    
    // Carregar melhorias de logging se VC_DEBUG estiver ativo
    // DESABILITADO temporariamente para melhorar performance do wizard
    // if ( defined( 'VC_DEBUG' ) && VC_DEBUG ) {
    //     $vc_debug_file = __DIR__ . '/scripts/enhance-debug-logging.php';
    //     if ( file_exists( $vc_debug_file ) ) {
    //         require_once $vc_debug_file;
    //     }
    // }
    
    // (carrega os módulos já existentes dos Pacotes 1..7)
    if ( class_exists( 'VC_Loader' ) ) { ( new \VC_Loader() )->init(); }
    if ( class_exists( 'VC_CPT_Produto' ) ) { ( new \VC_CPT_Produto() )->init(); }
    if ( class_exists( 'VC_CPT_Pedido' ) )  { ( new \VC_CPT_Pedido() )->init(); }
    // Classes admin só no admin
    if ( $is_admin_context && class_exists( 'VC_Admin_Menu' ) )  { ( new \VC_Admin_Menu() )->init(); }
    // REST só em requisições REST ou admin
    if ( ( $is_rest_context || $is_admin_context ) && class_exists( 'VC_REST' ) )        { ( new \VC_REST() )->init(); }

    // CPTs Model - sempre necessários (registram post types)
    if ( class_exists( '\\VC\\Model\\CPT_Restaurant' ) )      { ( new \VC\Model\CPT_Restaurant() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_MenuItem' ) )        { ( new \VC\Model\CPT_MenuItem() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_ProductModifier' ) ) { ( new \VC\Model\CPT_ProductModifier() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_AddonCatalogGroup' ) ) { ( new \VC\Model\CPT_AddonCatalogGroup() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_AddonCatalogItem' ) ) { ( new \VC\Model\CPT_AddonCatalogItem() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_Review' ) ) { ( new \VC\Model\CPT_Review() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_AnalyticsEvent' ) ) { ( new \VC\Model\CPT_AnalyticsEvent() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_Banner' ) ) { ( new \VC\Model\CPT_Banner() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_Event' ) ) { ( new \VC\Model\CPT_Event() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_Story' ) ) { ( new \VC\Model\CPT_Story() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_SubscriptionPlan' ) ) { ( new \VC\Model\CPT_SubscriptionPlan() )->init(); }
    if ( class_exists( '\\VC\\Subscription\\Limits_Validator' ) ) { ( new \VC\Subscription\Limits_Validator() )->init(); }
    
    // Classes Admin - APENAS no admin
    if ( $is_admin_context ) {
        if ( class_exists( '\\VC\\Admin\\Menu_Restaurant' ) )     { ( new \VC\Admin\Menu_Restaurant() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Settings' ) )            { ( new \VC\Admin\Settings() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Archetypes_Manager' ) )  { ( new \VC\Admin\Archetypes_Manager() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Modifiers_Metabox' ) ) { ( new \VC\Admin\Modifiers_Metabox() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Reports' ) )              { ( new \VC\Admin\Reports() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Export' ) )               { ( new \VC\Admin\Export() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Installer' ) )            { ( new \VC\Admin\Installer() )->init(); }
        if ( class_exists( '\\VC\\Admin\\Geocoding_Test' ) )        { ( new \VC\Admin\Geocoding_Test() )->init(); }
    }
    
    // Controllers REST - APENAS em requisições REST ou admin
    // CRÍTICO: Não inicializar no frontend público para evitar conexões desnecessárias
    if ( $is_rest_context || $is_admin_context ) {
        if ( class_exists( '\\VC\\REST\\Restaurant_Controller' ) ) { ( new \VC\REST\Restaurant_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Merchant_Settings_Controller' ) ) { ( new \VC\REST\Merchant_Settings_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Modifiers_Controller' ) ) { ( new \VC\REST\Modifiers_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Reviews_Controller' ) ) { ( new \VC\REST\Reviews_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Favorites_Controller' ) ) { ( new \VC\REST\Favorites_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Banners_Controller' ) ) { ( new \VC\REST\Banners_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Events_Controller' ) ) { ( new \VC\REST\Events_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Stories_Controller' ) ) { ( new \VC\REST\Stories_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Menu_Items_Controller' ) ) { ( new \VC\REST\Menu_Items_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Menu_Items_Status_Controller' ) ) { ( new \VC\REST\Menu_Items_Status_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Menu_Categories_Controller' ) ) { ( new \VC\REST\Menu_Categories_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Addon_Catalog_Controller' ) ) { ( new \VC\REST\Addon_Catalog_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Onboarding_Controller' ) ) { ( new \VC\REST\Onboarding_Controller() )->init(); }
        // Registrar Debug Controller
        if ( class_exists( '\\VC\\REST\\Debug_Controller' ) ) {
            ( new \VC\REST\Debug_Controller() )->init();
        }
        // Registrar Browser Debug Controller
        if ( class_exists( '\\VC\\REST\\Browser_Debug_Controller' ) ) {
            ( new \VC\REST\Browser_Debug_Controller() )->init();
        }
        if ( class_exists( '\\VC\\REST\\Quick_Toggle_Controller' ) ) { ( new \VC\REST\Quick_Toggle_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Subscription_Controller' ) ) { ( new \VC\REST\Subscription_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Addresses_Controller' ) ) { ( new \VC\REST\Addresses_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Notifications_Controller' ) ) { ( new \VC\REST\Notifications_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Reports_Controller' ) ) { ( new \VC\REST\Reports_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Coupons_Controller' ) ) { ( new \VC\REST\Coupons_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Admin_Controller' ) ) { ( new \VC\REST\Admin_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Audit_Controller' ) ) { ( new \VC\REST\Audit_Controller() )->init(); }
        if ( class_exists( '\\VC\\Analytics\\Analytics_Controller' ) ) { ( new \VC\Analytics\Analytics_Controller() )->init(); }
        if ( class_exists( '\\VC\\Analytics\\Tracking_Controller' ) ) { ( new \VC\Analytics\Tracking_Controller() )->init(); }
    }
    
    // Tracking Middleware - sempre necessário (pode rastrear no frontend)
    if ( class_exists( '\\VC\\Analytics\\Tracking_Middleware' ) ) { ( new \VC\Analytics\Tracking_Middleware() )->init(); }
    
    // Utils e Cache - sempre necessários
    if ( class_exists( '\\VC\\Utils\\Image_Optimizer' ) ) { ( new \VC\Utils\Image_Optimizer() )->init(); }
    if ( class_exists( '\\VC\\Cache\\Cache_Manager' ) ) { ( new \VC\Cache\Cache_Manager() )->init(); }
    if ( class_exists( '\\VC\\Cache\\Auto_Cache_Clear' ) ) { ( new \VC\Cache\Auto_Cache_Clear() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_Coupon' ) ) { ( new \VC\Model\CPT_Coupon() )->init(); }

    // Utils
    if ( class_exists( '\\VC\\Utils\\Schedule_Helper' ) ) {
        // Classe já está disponível via autoload, função helper global registrada
    }
    if ( class_exists( '\\VC\\Utils\\Rating_Helper' ) ) {
        // Classe já está disponível via autoload, função helper global registrada
    }
    if ( class_exists( '\\VC\\Utils\\Favorites_Helper' ) ) {
        // Classe já está disponível via autoload
    }

    // Order Statuses - sempre necessário
    if ( class_exists( '\\VC\\Order\\Statuses' ) )            { ( new \VC\Order\Statuses() )->init(); }
    
    // Webhooks - apenas em REST ou admin
    if ( ( $is_rest_context || $is_admin_context ) && class_exists( '\\VC\\REST\\Webhooks_Controller' ) )  { ( new \VC\REST\Webhooks_Controller() )->init(); }
    
    // CLI - apenas em WP-CLI
    if ( defined( 'WP_CLI' ) && WP_CLI ) {
        if ( class_exists( '\\VC\\CLI\\Seed' ) )                  { ( new \VC\CLI\Seed() )->init(); }
        if ( class_exists( '\\VC\\CLI\\Migrate_Command' ) )       { ( new \VC\CLI\Migrate_Command() )->init(); }
        if ( class_exists( '\\VC\\CLI\\Migrate_Cuisine_Archetypes' ) ) { ( new \VC\CLI\Migrate_Cuisine_Archetypes() )->init(); }
    }
    
    // Frontend - sempre necessário (shortcodes, templates, etc)
    if ( class_exists( '\\VC\\Frontend\\Shortcodes' ) )        { ( new \VC\Frontend\Shortcodes() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Shipping' ) )          { ( new \VC\Frontend\Shipping() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Signups' ) )           { ( new \VC\Frontend\Signups() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\RestaurantPanel' ) )   { ( new \VC\Frontend\RestaurantPanel() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\AccessValidation' ) )  { ( new \VC\Frontend\AccessValidation() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Onboarding' ) )       { ( new \VC\Frontend\Onboarding() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Marketplace_Templates' ) ) { ( new \VC\Frontend\Marketplace_Templates() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Home_Template' ) )     { ( new \VC\Frontend\Home_Template() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Coupons' ) )           { ( new \VC\Frontend\Coupons() )->init(); }
    
    // REST Controllers adicionais - apenas em REST ou admin
    if ( $is_rest_context || $is_admin_context ) {
        if ( class_exists( '\\VC\\REST\\Shipping_Controller' ) )   { ( new \VC\REST\Shipping_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Seeder_Controller' ) )     { ( new \VC\REST\Seeder_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Orders_Controller' ) )     { ( new \VC\REST\Orders_Controller() )->init(); }
        if ( class_exists( '\\VC\\REST\\Cache_Middleware' ) )      { ( new \VC\REST\Cache_Middleware() )->init(); }
        if ( class_exists( '\\VC\\REST\\Invalidation' ) )          { ( new \VC\REST\Invalidation() )->init(); }
    }
    
    // Email - sempre necessário (pode ser usado no frontend)
    if ( class_exists( '\\VC\\Email\\Templates' ) )            { ( new \VC\Email\Templates() )->init(); }
    if ( class_exists( '\\VC\\Email\\Events' ) )               { ( new \VC\Email\Events() )->init(); }
    
    // Integrações - sempre necessárias (webhooks podem vir do frontend)
    if ( class_exists( '\\VC\\Integration\\MercadoPago\\Webhook_Handler' ) ) { ( new \VC\Integration\MercadoPago\Webhook_Handler() )->init(); }
    if ( class_exists( '\\VC\\Integration\\SMClick' ) )        { ( new \VC\Integration\SMClick() )->init(); }

    // Seed automático de planos (uma vez) - apenas em admin ou primeira vez
    // CRÍTICO: NUNCA executar durante ativação para evitar múltiplas conexões
    $is_activating_check = get_transient( 'vemcomer_activating' ) || defined( 'WP_INSTALLING' );
    if ( ( $is_admin_context || $is_rest_context ) && ! $is_activating_check ) {
        if ( class_exists( '\\VC\\Utils\\Plan_Seeder' ) ) {
            $seeded = get_option( 'vemcomer_plans_seeded' );
            if ( ! $seeded ) {
                \VC\Utils\Plan_Seeder::seed();
                update_option( 'vemcomer_plans_seeded', true );
            }
        }
    }

} );

// Seed automático de categorias de cozinha (vc_cuisine) – roda uma vez, após taxonomias existirem
// OTIMIZADO: Executa apenas em admin ou quando necessário, com flags para evitar execução repetida
add_action( 'init', function () {
    // #region agent log
    $log_file = __DIR__ . '/.cursor/debug.log';
    $is_activating = get_transient( 'vemcomer_activating' );
    $log_data = json_encode([
        'id' => 'init_hook',
        'timestamp' => microtime(true) * 1000,
        'location' => 'vemcomer-core.php:448',
        'message' => 'init hook executed',
        'data' => ['is_activating' => (bool)$is_activating, 'wp_installing' => defined('WP_INSTALLING'), 'is_admin' => is_admin()],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'D'
    ]) . "\n";
    @file_put_contents($log_file, $log_data, FILE_APPEND);
    // #endregion
    
    // CRÍTICO: Não executar durante ativação/desativação do plugin
    // Verificação melhorada para capturar ativação de forma mais confiável
    if ( defined( 'WP_UNINSTALL_PLUGIN' ) 
         || defined( 'WP_INSTALLING' ) 
         || $is_activating
         || ( is_admin() && isset( $_GET['action'], $_GET['plugin'] ) && $_GET['action'] === 'activate' ) ) {
        // #region agent log
        $log_data = json_encode([
            'id' => 'init_hook_skipped',
            'timestamp' => microtime(true) * 1000,
            'location' => 'vemcomer-core.php:454',
            'message' => 'init hook skipped due to activation',
            'data' => [],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'D'
        ]) . "\n";
        @file_put_contents($log_file, $log_data, FILE_APPEND);
        // #endregion
        return;
    }
    
    // CRÍTICO: Executar seeds apenas em admin ou na primeira vez
    // No frontend público, não há necessidade de executar seeds
    $is_admin_context = is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
    
    // Se não for admin e todos os seeds já foram executados, pular completamente
    if ( ! $is_admin_context ) {
        $all_seeds_done = get_option( 'vemcomer_cuisines_seeded' ) 
                       && get_option( 'vemcomer_facilities_seeded' )
                       && get_option( 'vemcomer_addon_catalog_seeded' )
                       && get_option( 'vemcomer_menu_categories_seeded' );
        
        if ( $all_seeds_done ) {
            return; // Pular completamente no frontend se tudo já foi feito
        }
    }
    
    // Cuisine Seeder - com flag para evitar execução repetida
    // CRÍTICO: NUNCA executar durante ativação - faz muitas queries (wp_insert_term, get_term_by, etc.)
    if ( class_exists( '\\VC\\Utils\\Cuisine_Seeder' ) && taxonomy_exists( 'vc_cuisine' ) && ! $is_activating ) {
        \VC\Utils\Cuisine_Seeder::seed();
        
        // CRÍTICO: update_existing_terms() executa get_terms() que busca TODOS os termos
        // Adicionar flag para executar apenas UMA VEZ
        $cuisine_terms_updated = get_option( 'vemcomer_cuisine_terms_updated', false );
        if ( ! $cuisine_terms_updated ) {
            \VC\Utils\Cuisine_Seeder::update_existing_terms();
            update_option( 'vemcomer_cuisine_terms_updated', true );
        }
    }
    
    // Facility Seeder - já tem flag interna, mas verificar antes de chamar
    // CRÍTICO: NUNCA executar durante ativação
    if ( class_exists( '\\VC\\Utils\\Facility_Seeder' ) && taxonomy_exists( 'vc_facility' ) && ! $is_activating ) {
        $facilities_seeded = get_option( 'vemcomer_facilities_seeded' );
        if ( ! $facilities_seeded ) {
            \VC\Utils\Facility_Seeder::seed();
        }
    }
    
    // Addon Catalog Seeder - já verifica internamente, mas otimizar
    // CRÍTICO: NUNCA executar durante ativação
    if ( class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) && post_type_exists( 'vc_addon_group' ) && ! $is_activating ) {
        $addon_catalog_seeded = get_option( 'vemcomer_addon_catalog_seeded', false );
        if ( ! $addon_catalog_seeded ) {
            // Verificar se já existe antes de chamar seed (evita get_posts desnecessário)
            $existing = get_posts( [
                'post_type'      => 'vc_addon_group',
                'posts_per_page' => 1,
                'post_status'    => 'any',
                'fields'         => 'ids', // Apenas IDs para economizar memória
            ] );
            
            if ( empty( $existing ) ) {
                \VC\Utils\Addon_Catalog_Seeder::seed();
            }
            // Definir flag independentemente de seed() ter sido executado ou posts já existirem
            // Isso previne queries desnecessárias em execuções subsequentes
            update_option( 'vemcomer_addon_catalog_seeded', true );
        }
    }
    
    // Seed automático de categorias de cardápio sugeridas
    // CRÍTICO: NUNCA executar durante ativação
    if ( class_exists( '\\VC\\Utils\\Menu_Category_Catalog_Seeder' ) && taxonomy_exists( 'vc_menu_category' ) && taxonomy_exists( 'vc_cuisine' ) && ! $is_activating ) {
        $menu_categories_seeded = get_option( 'vemcomer_menu_categories_seeded' );
        if ( ! $menu_categories_seeded ) {
            \VC\Utils\Menu_Category_Catalog_Seeder::seed();
            update_option( 'vemcomer_menu_categories_seeded', true );
        }
    }
    
    // Atualizar itens dos grupos existentes apenas uma vez (adiciona itens aos grupos já criados)
    // Executar apenas em requisições admin normais, não durante ativação
    if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) && is_admin() && ! wp_doing_ajax() && ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
        $update_flag = get_option( 'vemcomer_addon_items_updated', false );
        if ( ! $update_flag && class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) && post_type_exists( 'vc_addon_group' ) ) {
            \VC\Utils\Addon_Catalog_Seeder::update_group_items();
            update_option( 'vemcomer_addon_items_updated', true );
        }
    }
}, 20 );

// --- Bootstrap do módulo Restaurantes ---
$vc_inc_base = VEMCOMER_CORE_DIR . 'inc/';
if ( file_exists( $vc_inc_base . 'init-restaurants.php' ) ) {
    require_once $vc_inc_base . 'init-restaurants.php';
}