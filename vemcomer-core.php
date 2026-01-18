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

// CRÍTICO: Suprimir TODOS os erros/avisos durante carregamento do plugin
// Isso previne que avisos de outros plugins (Wordfence, WP Pusher, etc) sejam capturados como output
// Aplicar ANTES de qualquer outra coisa, incluindo detecção de ativação
$is_activating_context = (
    ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' && isset( $_GET['plugin'] ) )
    || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) )
    || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
    || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() && isset( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'activate' ) !== false )
);

if ( $is_activating_context ) {
    // Salvar configurações atuais
    $vemcomer_old_error_reporting = error_reporting( 0 );
    $vemcomer_old_display_errors = @ini_get( 'display_errors' );
    @ini_set( 'display_errors', 0 );
    @ini_set( 'log_errors', 0 );
}

// CRÍTICO: Detectar ativação o mais cedo possível para prevenir execução de hooks
// Verificar se estamos em processo de ativação ANTES de qualquer outra coisa
// Nota: A supressão de erros já foi aplicada acima se $is_activating_context for true
if ( $is_activating_context && function_exists( 'set_transient' ) ) {
    set_transient( 'vemcomer_activating', true, 60 );
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
    // Nota: A supressão já foi aplicada no início do arquivo, mas reforçamos aqui
    $old_error_reporting = error_reporting( 0 );
    $old_display_errors = @ini_get( 'display_errors' );
    @ini_set( 'display_errors', 0 );
    @ini_set( 'log_errors', 0 );
    
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
    
    // CRÍTICO: Remover a flag APÓS a ativação completar
    // Usar hook 'activated_plugin' para garantir que a ativação terminou
    // Isso permite que o plugin funcione imediatamente após ativação
    add_action( 'activated_plugin', function ( $plugin ) {
        if ( strpos( $plugin, 'vemcomer-core' ) !== false ) {
            delete_transient( 'vemcomer_activating' );
        }
    }, 999 );
    
    // NOTA: Não restaurar configurações aqui - deixar o WordPress fazer isso
    // Restaurar pode causar output se houver erros durante a restauração
} );

// Tentar corrigir WP Pusher automaticamente no carregamento (se necessário)
add_action( 'plugins_loaded', function () {
    // CRÍTICO: Não executar durante ativação
    // IMPORTANTE: Verificar apenas se estamos REALMENTE em processo de ativação
    $is_activating = ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
                     || ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' && isset( $_GET['plugin'] ) && strpos( $_GET['plugin'], 'vemcomer-core' ) !== false )
                     || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) && strpos( $_REQUEST['plugin'], 'vemcomer-core' ) !== false );
    
    if ( $is_activating ) {
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
    static $execution_count = 0;
    $execution_count++;
    
    // CRÍTICO: Não executar durante ativação do plugin
    // Isso previne múltiplas conexões de banco durante ativação
    // IMPORTANTE: Verificar apenas se estamos REALMENTE em processo de ativação
    // Não bloquear se o transient ainda existir mas a ativação já terminou
    $is_activating = ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
                     || ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' && isset( $_GET['plugin'] ) && strpos( $_GET['plugin'], 'vemcomer-core' ) !== false )
                     || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) && strpos( $_REQUEST['plugin'], 'vemcomer-core' ) !== false );
    
    // Se não estamos em processo de ativação, remover transient se existir (limpeza)
    if ( ! $is_activating && get_transient( 'vemcomer_activating' ) ) {
        delete_transient( 'vemcomer_activating' );
    }
    
    // Bloquear apenas se realmente estivermos em processo de ativação
    if ( $is_activating ) {
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
    
    // CORS Handler - sempre necessário para requisições REST do frontend
    // Inicializa sempre, não apenas em contexto REST, para garantir que funcione
    if ( class_exists( '\\VC\\REST\\CORS_Handler' ) ) { 
        ( new \VC\REST\CORS_Handler() )->init(); 
    }
    
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
    $log_file = 'c:\\Users\\Adm-Sup\\Local Sites\\pedevem-local\\.cursor\\debug.log';
    $start_time = microtime(true);
    $log_data = json_encode([
        'id' => 'init_hook_seeds_start',
        'timestamp' => $start_time * 1000,
        'location' => 'vemcomer-core.php:384',
        'message' => 'init hook seeds started',
        'data' => ['is_admin' => is_admin(), 'is_rest' => defined('REST_REQUEST'), 'is_frontend' => !is_admin() && !defined('REST_REQUEST')],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'A'
    ]) . "\n";
    @file_put_contents($log_file, $log_data, FILE_APPEND);
    // #endregion
    
    // CRÍTICO: Não executar durante ativação/desativação do plugin
    // Verificação melhorada para capturar ativação de forma mais confiável
    // IMPORTANTE: Verificar apenas se estamos REALMENTE em processo de ativação
    // Não bloquear se o transient ainda existir mas a ativação já terminou
    $is_activating = ( defined( 'WP_INSTALLING' ) && WP_INSTALLING )
                     || ( isset( $_GET['action'] ) && $_GET['action'] === 'activate' && isset( $_GET['plugin'] ) && strpos( $_GET['plugin'], 'vemcomer-core' ) !== false )
                     || ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] === 'activate' && isset( $_REQUEST['plugin'] ) && strpos( $_REQUEST['plugin'], 'vemcomer-core' ) !== false );
    
    // Se não estamos em processo de ativação, remover transient se existir (limpeza)
    if ( ! $is_activating && get_transient( 'vemcomer_activating' ) ) {
        delete_transient( 'vemcomer_activating' );
    }
    
    if ( defined( 'WP_UNINSTALL_PLUGIN' ) 
         || defined( 'WP_INSTALLING' ) 
         || $is_activating ) {
        // #region agent log
        $log_data = json_encode([
            'id' => 'init_hook_seeds_skipped',
            'timestamp' => microtime(true) * 1000,
            'location' => 'vemcomer-core.php:393',
            'message' => 'init hook seeds skipped',
            'data' => ['reason' => 'activation_or_uninstall'],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'A'
        ]) . "\n";
        @file_put_contents($log_file, $log_data, FILE_APPEND);
        // #endregion
        return;
    }
    
    // CRÍTICO: Executar seeds apenas em admin ou na primeira vez
    // No frontend público, não há necessidade de executar seeds
    $is_admin_context = is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST );
    
    // OTIMIZAÇÃO CRÍTICA: Usar wp_prime_option_caches() para carregar todas as flags de uma vez
    // Isso evita 4 queries separadas no frontend
    if ( ! $is_admin_context ) {
        // Carregar todas as flags de seed de uma vez (1 query ao invés de 4)
        $seed_flags = wp_prime_option_caches( [
            'vemcomer_cuisines_seeded',
            'vemcomer_facilities_seeded',
            'vemcomer_addon_catalog_seeded',
            'vemcomer_menu_categories_seeded'
        ] );
        
        $all_seeds_done = get_option( 'vemcomer_cuisines_seeded' ) 
                       && get_option( 'vemcomer_facilities_seeded' )
                       && get_option( 'vemcomer_addon_catalog_seeded' )
                       && get_option( 'vemcomer_menu_categories_seeded' );
        
        // #region agent log
        $log_data = json_encode([
            'id' => 'init_hook_frontend_check',
            'timestamp' => microtime(true) * 1000,
            'location' => 'vemcomer-core.php:407',
            'message' => 'frontend seeds check',
            'data' => ['all_seeds_done' => $all_seeds_done, 'queries_saved' => 3],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'B'
        ]) . "\n";
        @file_put_contents($log_file, $log_data, FILE_APPEND);
        // #endregion
        
        if ( $all_seeds_done ) {
            // #region agent log
            $end_time = microtime(true);
            $log_data = json_encode([
                'id' => 'init_hook_seeds_skipped_frontend',
                'timestamp' => $end_time * 1000,
                'location' => 'vemcomer-core.php:408',
                'message' => 'init hook seeds skipped - frontend all done',
                'data' => ['duration_ms' => ($end_time - $start_time) * 1000],
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'B'
            ]) . "\n";
            @file_put_contents($log_file, $log_data, FILE_APPEND);
            // #endregion
            return; // Pular completamente no frontend se tudo já foi feito
        }
    }
    
    // Cuisine Seeder - com flag para evitar execução repetida
    // CRÍTICO: NUNCA executar durante ativação - faz muitas queries (wp_insert_term, get_term_by, etc.)
    // IMPORTANTE: $is_activating já foi verificado acima e o transient foi limpo se necessário
    if ( class_exists( '\\VC\\Utils\\Cuisine_Seeder' ) && taxonomy_exists( 'vc_cuisine' ) && ! $is_activating ) {
        // #region agent log
        $seed_start = microtime(true);
        $log_data = json_encode([
            'id' => 'cuisine_seeder_start',
            'timestamp' => $seed_start * 1000,
            'location' => 'vemcomer-core.php:413',
            'message' => 'Cuisine_Seeder::seed() starting',
            'data' => [],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C'
        ]) . "\n";
        @file_put_contents($log_file, $log_data, FILE_APPEND);
        // #endregion
        
        \VC\Utils\Cuisine_Seeder::seed();
        
        // #region agent log
        $seed_end = microtime(true);
        $log_data = json_encode([
            'id' => 'cuisine_seeder_end',
            'timestamp' => $seed_end * 1000,
            'location' => 'vemcomer-core.php:415',
            'message' => 'Cuisine_Seeder::seed() completed',
            'data' => ['duration_ms' => ($seed_end - $seed_start) * 1000],
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'C'
        ]) . "\n";
        @file_put_contents($log_file, $log_data, FILE_APPEND);
        // #endregion
        
        // CRÍTICO: update_existing_terms() executa get_terms() que busca TODOS os termos
        // Adicionar flag para executar apenas UMA VEZ
        // OTIMIZAÇÃO: Verificar flag ANTES de carregar a classe (se possível)
        $cuisine_terms_updated = get_option( 'vemcomer_cuisine_terms_updated', false );
        if ( ! $cuisine_terms_updated ) {
            // #region agent log
            $update_start = microtime(true);
            $log_data = json_encode([
                'id' => 'update_existing_terms_start',
                'timestamp' => $update_start * 1000,
                'location' => 'vemcomer-core.php:420',
                'message' => 'update_existing_terms() starting - WARNING: calls get_terms() for ALL terms',
                'data' => [],
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'D'
            ]) . "\n";
            @file_put_contents($log_file, $log_data, FILE_APPEND);
            // #endregion
            
            \VC\Utils\Cuisine_Seeder::update_existing_terms();
            update_option( 'vemcomer_cuisine_terms_updated', true );
            
            // #region agent log
            $update_end = microtime(true);
            $log_data = json_encode([
                'id' => 'update_existing_terms_end',
                'timestamp' => $update_end * 1000,
                'location' => 'vemcomer-core.php:422',
                'message' => 'update_existing_terms() completed',
                'data' => ['duration_ms' => ($update_end - $update_start) * 1000],
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'D'
            ]) . "\n";
            @file_put_contents($log_file, $log_data, FILE_APPEND);
            // #endregion
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
    // IMPORTANTE: $is_activating já foi verificado acima
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
    
    // #region agent log
    $end_time = microtime(true);
    $log_data = json_encode([
        'id' => 'init_hook_seeds_end',
        'timestamp' => $end_time * 1000,
        'location' => 'vemcomer-core.php:475',
        'message' => 'init hook seeds completed',
        'data' => ['total_duration_ms' => ($end_time - $start_time) * 1000],
        'sessionId' => 'debug-session',
        'runId' => 'run1',
        'hypothesisId' => 'A'
    ]) . "\n";
    @file_put_contents($log_file, $log_data, FILE_APPEND);
    // #endregion
}, 20 );

// --- Bootstrap do módulo Restaurantes ---
$vc_inc_base = VEMCOMER_CORE_DIR . 'inc/';
if ( file_exists( $vc_inc_base . 'init-restaurants.php' ) ) {
    require_once $vc_inc_base . 'init-restaurants.php';
}

// --- CORS Fallback para garantir funcionamento em produção ---
// Adiciona headers CORS diretamente no hook rest_api_init como fallback
add_action( 'rest_api_init', function() {
    // Remove filtro padrão do WordPress
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    
    // Adiciona headers CORS personalizados
    add_filter( 'rest_pre_serve_request', function( $value ) {
        $allowed_origins = [
            'https://47191717-b1f5-4559-bdab-f069bc62cec6.lovableproject.com',
            'https://id-preview--47191717-b1f5-4559-bdab-f069bc62cec6.lovable.app',
            'https://hungry-hub-core.lovable.app',
            'http://localhost:5173',
            'http://pedevem-local.local',
            'https://periodic-symbol.localsite.io',
        ];
        
        $origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
        
        if ( $origin && in_array( $origin, $allowed_origins, true ) ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
            header( 'Access-Control-Allow-Credentials: true' );
        }
        
        return $value;
    }, 15 );
}, 15 );

// Tratar preflight OPTIONS diretamente no init
add_action( 'init', function() {
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS' ) {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        
        // Apenas processa se for requisição para a API REST
        if ( strpos( $request_uri, '/wp-json/vemcomer/v1/' ) !== false ) {
            $allowed_origins = [
                'https://47191717-b1f5-4559-bdab-f069bc62cec6.lovableproject.com',
                'https://id-preview--47191717-b1f5-4559-bdab-f069bc62cec6.lovable.app',
                'https://hungry-hub-core.lovable.app',
                'http://localhost:5173',
                'http://pedevem-local.local',
                'https://periodic-symbol.localsite.io',
            ];
            
            $origin = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
            
            if ( $origin && in_array( $origin, $allowed_origins, true ) ) {
                header( 'Access-Control-Allow-Origin: ' . $origin );
            } else {
                header( 'Access-Control-Allow-Origin: *' );
            }
            
            header( 'Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Content-Type, Authorization' );
            header( 'Access-Control-Allow-Credentials: true' );
            status_header( 200 );
            exit();
        }
    }
}, 1 );