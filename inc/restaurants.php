<?php
if (!defined('ABSPATH')) exit;

// Helpers de restaurante (serão expandidos nas próximas etapas)
function vc_restaurant_accepting_orders($product_id){
  $flag = get_post_meta($product_id, '_rc_accepting_orders', true);
  return $flag === 'yes' || $flag === '1' || $flag === 1;
}
