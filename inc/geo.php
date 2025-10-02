<?php
/**
 * Geolocalização — helpers do VemComer Core
 * - Cache simples de geocodificação (transients)
 * - Distância Haversine (km)
 */

if (!defined('ABSPATH')) exit;

/**
 * Retorna um valor de cache de geocodificação.
 * A chave é normalizada via md5 para evitar problemas de tamanho/charset.
 *
 * @param string $key  Chave original (ex.: query ou "lat:lng")
 * @return mixed       Valor armazenado ou false se não existir
 */
function vc_geo_cache_get($key){
  return get_transient('vc_geo_' . md5((string)$key));
}

/**
 * Define um valor no cache de geocodificação.
 *
 * @param string $key  Chave original (ex.: query ou "lat:lng")
 * @param mixed  $value Valor a armazenar (array/obj/string)
 * @param int    $ttl  Tempo em segundos (default: 6h)
 * @return void
 */
function vc_geo_cache_set($key, $value, $ttl = 6 * HOUR_IN_SECONDS){
  set_transient('vc_geo_' . md5((string)$key), $value, (int)$ttl);
}

/**
 * Calcula a distância entre dois pontos (lat/lng) usando a fórmula de Haversine.
 * O resultado é em quilômetros.
 *
 * @param float $lat1
 * @param float $lon1
 * @param float $lat2
 * @param float $lon2
 * @return float km
 */
if (!function_exists('vc_haversine_km')) {
  function vc_haversine_km($lat1, $lon1, $lat2, $lon2){
    $R = 6371; // raio médio da Terra em km
    $dLat = deg2rad((float)$lat2 - (float)$lat1);
    $dLon = deg2rad((float)$lon2 - (float)$lon1);
    $a = sin($dLat/2) * sin($dLat/2)
       + cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2))
       * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return (float) ($R * $c);
  }
}
```0