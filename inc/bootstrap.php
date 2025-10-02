<?php
// Bootstrap do VemComer Core (stub inicial)
if (!defined('ABSPATH')) exit;

// Se o arquivo principal já define as constantes, não redefina.
if (!defined('VEMCOMER_CORE_PATH')) define('VEMCOMER_CORE_PATH', plugin_dir_path(__FILE__) . '..' . DIRECTORY_SEPARATOR);
if (!defined('VEMCOMER_CORE_URL'))  define('VEMCOMER_CORE_URL', plugin_dir_url(__FILE__) . '..' . '/');

// Registro/enqueue virão em etapas futuras para evitar conflitos.
