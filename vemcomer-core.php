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
    // Corrige WP Pusher automaticamente
    vemcomer_fix_wppusher_php82();
    
    if ( class_exists( '\\VC\\Admin\\Installer' ) ) {
        ( new \VC\Admin\Installer() )->install_defaults();
    }
} );

// Tentar corrigir WP Pusher automaticamente no carregamento (se necessário)
add_action( 'plugins_loaded', function () {
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
    // (carrega os módulos já existentes dos Pacotes 1..7)
    if ( class_exists( 'VC_Loader' ) ) { ( new \VC_Loader() )->init(); }
    if ( class_exists( 'VC_CPT_Produto' ) ) { ( new \VC_CPT_Produto() )->init(); }
    if ( class_exists( 'VC_CPT_Pedido' ) )  { ( new \VC_CPT_Pedido() )->init(); }
    if ( class_exists( 'VC_Admin_Menu' ) )  { ( new \VC_Admin_Menu() )->init(); }
    if ( class_exists( 'VC_REST' ) )        { ( new \VC_REST() )->init(); }

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
    if ( class_exists( '\\VC\\Admin\\Menu_Restaurant' ) )     { ( new \VC\Admin\Menu_Restaurant() )->init(); }
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
    if ( class_exists( '\\VC\\REST\\Quick_Toggle_Controller' ) ) { ( new \VC\REST\Quick_Toggle_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Subscription_Controller' ) ) { ( new \VC\REST\Subscription_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Addresses_Controller' ) ) { ( new \VC\REST\Addresses_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Notifications_Controller' ) ) { ( new \VC\REST\Notifications_Controller() )->init(); }
    if ( class_exists( '\\VC\\Utils\\Image_Optimizer' ) ) { ( new \VC\Utils\Image_Optimizer() )->init(); }
    if ( class_exists( '\\VC\\Cache\\Cache_Manager' ) ) { ( new \VC\Cache\Cache_Manager() )->init(); }
    if ( class_exists( '\\VC\\Cache\\Auto_Cache_Clear' ) ) { ( new \VC\Cache\Auto_Cache_Clear() )->init(); }
    if ( class_exists( '\\VC\\REST\\Reports_Controller' ) ) { ( new \VC\REST\Reports_Controller() )->init(); }
    if ( class_exists( '\\VC\\Model\\CPT_Coupon' ) ) { ( new \VC\Model\CPT_Coupon() )->init(); }
    if ( class_exists( '\\VC\\REST\\Coupons_Controller' ) ) { ( new \VC\REST\Coupons_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Admin_Controller' ) ) { ( new \VC\REST\Admin_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Audit_Controller' ) ) { ( new \VC\REST\Audit_Controller() )->init(); }
    if ( class_exists( '\\VC\\Analytics\\Analytics_Controller' ) ) { ( new \VC\Analytics\Analytics_Controller() )->init(); }
    if ( class_exists( '\\VC\\Analytics\\Tracking_Middleware' ) ) { ( new \VC\Analytics\Tracking_Middleware() )->init(); }
    if ( class_exists( '\\VC\\Analytics\\Tracking_Controller' ) ) { ( new \VC\Analytics\Tracking_Controller() )->init(); }
    if ( class_exists( '\\VC\\Admin\\Modifiers_Metabox' ) ) { ( new \VC\Admin\Modifiers_Metabox() )->init(); }

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

    if ( class_exists( '\\VC\\Admin\\Settings' ) )            { ( new \VC\Admin\Settings() )->init(); }
    if ( class_exists( '\\VC\\Order\\Statuses' ) )            { ( new \VC\Order\Statuses() )->init(); }
    if ( class_exists( '\\VC\\REST\\Webhooks_Controller' ) )  { ( new \VC\REST\Webhooks_Controller() )->init(); }
    if ( class_exists( '\\VC\\CLI\\Seed' ) )                  { ( new \VC\CLI\Seed() )->init(); }

    if ( class_exists( '\\VC\\Frontend\\Shortcodes' ) )        { ( new \VC\Frontend\Shortcodes() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Shipping' ) )          { ( new \VC\Frontend\Shipping() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Signups' ) )           { ( new \VC\Frontend\Signups() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\RestaurantPanel' ) )   { ( new \VC\Frontend\RestaurantPanel() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\AccessValidation' ) )  { ( new \VC\Frontend\AccessValidation() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Onboarding' ) )       { ( new \VC\Frontend\Onboarding() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Marketplace_Templates' ) ) { ( new \VC\Frontend\Marketplace_Templates() )->init(); }
    if ( class_exists( '\\VC\\Frontend\\Home_Template' ) )     { ( new \VC\Frontend\Home_Template() )->init(); }
    if ( class_exists( '\\VC\\REST\\Shipping_Controller' ) )   { ( new \VC\REST\Shipping_Controller() )->init(); }

    if ( class_exists( '\\VC\\Frontend\\Coupons' ) )           { ( new \VC\Frontend\Coupons() )->init(); }
    if ( class_exists( '\\VC\\REST\\Coupons_Controller' ) )    { ( new \VC\REST\Coupons_Controller() )->init(); }
    if ( class_exists( '\\VC\\REST\\Orders_Controller' ) )     { ( new \VC\REST\Orders_Controller() )->init(); }
    if ( class_exists( '\\VC\\Email\\Templates' ) )            { ( new \VC\Email\Templates() )->init(); }
    if ( class_exists( '\\VC\\Email\\Events' ) )               { ( new \VC\Email\Events() )->init(); }

    if ( class_exists( '\\VC\\Admin\\Reports' ) )              { ( new \VC\Admin\Reports() )->init(); }
    if ( class_exists( '\\VC\\Admin\\Export' ) )               { ( new \VC\Admin\Export() )->init(); }
    if ( class_exists( '\\VC\\REST\\Cache_Middleware' ) )      { ( new \VC\REST\Cache_Middleware() )->init(); }
    if ( class_exists( '\\VC\\REST\\Invalidation' ) )          { ( new \VC\REST\Invalidation() )->init(); }
    if ( class_exists( '\\VC\\Integration\\MercadoPago\\Webhook_Handler' ) ) { ( new \VC\Integration\MercadoPago\Webhook_Handler() )->init(); }
    if ( class_exists( '\\VC\\Integration\\SMClick' ) )        { ( new \VC\Integration\SMClick() )->init(); }

    // Pacote 8 — Instalador de Páginas
    if ( class_exists( '\\VC\\Admin\\Installer' ) )            { ( new \VC\Admin\Installer() )->init(); }
    if ( class_exists( '\\VC\\Admin\\Geocoding_Test' ) )        { ( new \VC\Admin\Geocoding_Test() )->init(); }

    // Seed automático de planos (uma vez)
    if ( class_exists( '\\VC\\Utils\\Plan_Seeder' ) ) {
        $seeded = get_option( 'vemcomer_plans_seeded' );
        if ( ! $seeded ) {
            \VC\Utils\Plan_Seeder::seed();
            update_option( 'vemcomer_plans_seeded', true );
        }
    }

} );

// Seed automático de categorias de cozinha (vc_cuisine) – roda uma vez, após taxonomias existirem
add_action( 'init', function () {
    if ( class_exists( '\\VC\\Utils\\Cuisine_Seeder' ) && taxonomy_exists( 'vc_cuisine' ) ) {
        \VC\Utils\Cuisine_Seeder::seed();
    }
    
    if ( class_exists( '\\VC\\Utils\\Facility_Seeder' ) && taxonomy_exists( 'vc_facility' ) ) {
        \VC\Utils\Facility_Seeder::seed();
    }
    if ( class_exists( '\\VC\\Utils\\Addon_Catalog_Seeder' ) && post_type_exists( 'vc_addon_group' ) ) {
        \VC\Utils\Addon_Catalog_Seeder::seed();
        // Atualizar itens dos grupos existentes (adiciona itens aos grupos já criados)
        \VC\Utils\Addon_Catalog_Seeder::update_group_items();
    }
}, 20 );

// --- Bootstrap do módulo Restaurantes ---
$vc_inc_base = VEMCOMER_CORE_DIR . 'inc/';
if ( file_exists( $vc_inc_base . 'init-restaurants.php' ) ) {
    require_once $vc_inc_base . 'init-restaurants.php';
}
