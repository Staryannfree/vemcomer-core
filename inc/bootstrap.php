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

// Registro/enqueue virão em etapas futuras para evitar conflitos.
