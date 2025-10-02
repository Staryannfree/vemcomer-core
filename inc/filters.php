<?php
if (!defined('ABSPATH')) exit;

// Função utilitária para expor filtros com defaults
function vc_filters_default($key, $default){
  return apply_filters($key, $default);
}

// Assinaturas de filtros (defaults serão definidos na próxima etapa)
function vc_kds_poll_interval(){ return (int) vc_filters_default('vemcomer_kds_poll', 7000); }
function vc_tiles_url(){ return (string) vc_filters_default('vemcomer_tiles_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'); }
function vc_default_radius(){ return (float) vc_filters_default('vemcomer_default_radius', 5); }
function vc_checkout_labels($arr){ return (array) apply_filters('vemcomer_checkout_labels', $arr); }
function vc_order_webhook_payload($arr){ return (array) apply_filters('vemcomer_order_webhook_payload', $arr); }
