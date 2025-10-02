<?php
if (!defined('ABSPATH')) exit;

// Cache simples (transients) — preencheremos uso mais adiante
function vc_geo_cache_get($key){ return get_transient('vc_geo_' . md5($key)); }
function vc_geo_cache_set($key, $value, $ttl = 6 * HOUR_IN_SECONDS){ set_transient('vc_geo_' . md5($key), $value, $ttl); }

if (!function_exists('vc_haversine_km')) {
  function vc_haversine_km($lat1, $lon1, $lat2, $lon2){
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
  }
}