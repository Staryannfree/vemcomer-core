<?php
// Bootstrap do VemComer Core (stub inicial)
if (!defined('ABSPATH')) exit;

// Se o arquivo principal já define as constantes, não redefina.
if (!defined('VEMCOMER_CORE_PATH')) define('VEMCOMER_CORE_PATH', plugin_dir_path(__FILE__) . '..' . DIRECTORY_SEPARATOR);
if (!defined('VEMCOMER_CORE_URL'))  define('VEMCOMER_CORE_URL', plugin_dir_url(__FILE__) . '..' . '/');

if (!defined('VC_DEBUG')) {
    $vc_settings = (array) get_option('vemcomer_settings', []);
    $debug_option = !empty($vc_settings['debug_logging']);
    $wp_debug = defined('WP_DEBUG') && WP_DEBUG;
    $debug_flag = (bool) apply_filters('vemcomer_debug_flag', $debug_option || $wp_debug, $vc_settings);
    define('VC_DEBUG', $debug_flag);
}

if (!function_exists('vc_get_asset_version')) {
    function vc_get_asset_version(): string {
        return defined('VEMCOMER_CORE_VERSION') ? VEMCOMER_CORE_VERSION : '1.0.0';
    }
}

/**
 * Handles registrados:
 *
 * Frontend
 * - vemcomer-front (css/js)
 * - vemcomer-style (css genérico)
 * - vc-shortcodes (css legado)
 * - vemcomer-checkout (js exemplos checkout)
 * - vemcomer-checkout-geo (js – botão “Usar minha localização” no checkout)
 * - vemcomer-geo-address (js – buscador de endereço no checkout)
 * - vemcomer-explore e vemcomer-explore-map (js – explorar + mapa Leaflet)
 * - vemcomer-restaurant-map (js – mapa no single de restaurante)
 * - vemcomer-kds (js – painel da cozinha)
 *
 * Admin
 * - vemcomer-admin (css/js)
 * - vc-restaurants-admin (css/js para CPT vc_restaurant)
 * - vc-preenchedor (css do submenu “Preenchedor”)
 */
if (!function_exists('vc_register_front_assets')) {
    function vc_register_front_assets(): void {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $base_url = defined('VEMCOMER_CORE_URL') ? VEMCOMER_CORE_URL : plugin_dir_url(__FILE__) . '..' . '/';
        $ver = vc_get_asset_version();

        // Styles
        wp_register_style('vemcomer-front', $base_url . 'assets/css/frontend.css', [], $ver);
        wp_register_style('vemcomer-style', $base_url . 'assets/style.css', [], $ver);
        wp_register_style('vc-shortcodes', $base_url . 'assets/css/shortcodes.css', [], $ver);

        // Scripts
        wp_register_script('vemcomer-front', $base_url . 'assets/js/frontend.js', [], $ver, true);
        wp_register_script('vemcomer-checkout', $base_url . 'assets/js/checkout.js', [], $ver, true);
        wp_register_script('vemcomer-checkout-geo', $base_url . 'assets/checkout-geo.js', ['jquery'], $ver, true);
        wp_register_script('vemcomer-geo-address', $base_url . 'assets/geo-address.js', ['jquery'], $ver, true);
        wp_register_script('vemcomer-explore', $base_url . 'assets/explore.js', [], $ver, true);
        wp_register_script('vemcomer-explore-map', $base_url . 'assets/explore-map.js', [], $ver, true);
        wp_register_script('vemcomer-restaurant-map', $base_url . 'assets/restaurant-map.js', [], $ver, true);
        wp_register_script('vemcomer-kds', $base_url . 'assets/kds.js', [], $ver, true);
    }
}

if (!function_exists('vc_register_admin_assets')) {
    function vc_register_admin_assets(): void {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $base_url = defined('VEMCOMER_CORE_URL') ? VEMCOMER_CORE_URL : plugin_dir_url(__FILE__) . '..' . '/';
        $ver = vc_get_asset_version();

        // Styles
        wp_register_style('vemcomer-admin', $base_url . 'assets/css/admin.css', [], $ver);
        wp_register_style('vc-restaurants-admin', $base_url . 'assets/css/restaurants-admin.css', [], $ver);
        wp_register_style('vc-preenchedor', $base_url . 'assets/css/preenchedor.css', [], $ver);

        // Scripts
        wp_register_script('vemcomer-admin', $base_url . 'assets/js/admin.js', [], $ver, true);
        wp_register_script('vc-restaurants-admin', $base_url . 'assets/js/restaurants-admin.js', ['jquery'], $ver, true);
    }
}

add_action('init', 'vc_register_front_assets', 5);
add_action('admin_init', 'vc_register_admin_assets', 5);
