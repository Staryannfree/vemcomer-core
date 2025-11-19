<?php
if (!defined('ABSPATH')) exit;

function vc_filters_default($key, $default, $settings_key = null){
  $settings = function_exists('vc_get_settings_with_defaults') ? vc_get_settings_with_defaults() : [];
  if ($settings_key && array_key_exists($settings_key, $settings)) {
    $value = $settings[$settings_key];
    if ($value !== '' && $value !== null) {
      $default = $value;
    }
  }
  return apply_filters($key, $default, $settings);
}

function vc_kds_poll_interval(){ return (int) vc_filters_default('vemcomer_kds_poll', 7000, 'kds_poll'); }
function vc_tiles_url(){ return (string) vc_filters_default('vemcomer_tiles_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 'tiles_url'); }
function vc_default_radius(){ return (float) vc_filters_default('vemcomer_default_radius', 5, 'default_radius'); }
function vc_default_freight_base(){ return (float) vc_filters_default('vemcomer_freight_base', 9.9, 'freight_base'); }
function vc_default_freight_per_km(){ return (float) vc_filters_default('vemcomer_freight_per_km', 1.5, 'freight_per_km'); }
function vc_freight_free_above(){ return (float) vc_filters_default('vemcomer_freight_free_above', 150, 'freight_free_above'); }
function vc_checkout_labels($arr = []){
  $settings = function_exists('vc_get_settings_with_defaults') ? vc_get_settings_with_defaults() : [];
  $defaults = [
    'pix'    => isset($settings['payment_text_pix']) ? (string) $settings['payment_text_pix'] : __('Pagamento via Pix na entrega.', 'vemcomer'),
    'card'   => isset($settings['payment_text_card']) ? (string) $settings['payment_text_card'] : __('Cartão na entrega (maquininha).', 'vemcomer'),
    'cash'   => isset($settings['payment_text_cash']) ? (string) $settings['payment_text_cash'] : __('Dinheiro (informe se precisa de troco).', 'vemcomer'),
  ];
  $arr = wp_parse_args($arr, $defaults);
  return (array) apply_filters('vemcomer_checkout_labels', $arr, $settings);
}
function vc_order_webhook_payload($arr){
  $settings = function_exists('vc_get_settings_with_defaults') ? vc_get_settings_with_defaults() : [];
  return (array) apply_filters('vemcomer_order_webhook_payload', $arr, $settings);
}
