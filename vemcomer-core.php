<?php
/**
 * Plugin Name: Vemcomer Core (MVP)
 * Description: Core do marketplace VemComer (shortcodes, mapas, checkout geolocalizado, KDS, etc.).
 * Version: 0.5.2-test1
 * Author: Vemcomer
 *
 * GitHub Plugin URI: Staryannfree/vemcomer-core
 * Primary Branch: main
 */

if (!defined('ABSPATH')) exit;

// cache-buster para JS/CSS – mude sempre que alterar assets
if (!defined('VEMCOMER_CORE_VERSION')) {
  define('VEMCOMER_CORE_VERSION', '0.5.2-test1');
}

define('VEMCOMER_CORE_URL', plugin_dir_url(__FILE__));
define('VEMCOMER_CORE_PATH', plugin_dir_path(__FILE__));

// ======================================================
// 0) Helpers
// ======================================================
function vc_haversine_km($lat1, $lon1, $lat2, $lon2) {
  $earthRadius = 6371;
  $dLat = deg2rad($lat2 - $lat1);
  $dLon = deg2rad($lon2 - $lon1);
  $a = sin($dLat/2) * sin($dLat/2) +
       cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
       sin($dLon/2) * sin($dLon/2);
  $c = 2 * atan2(sqrt($a), sqrt(1-$a));
  return $earthRadius * $c;
}

function vc_is_open_now($post_id, $tz = 'America/Sao_Paulo'){
  $json = get_post_meta($post_id, '_rc_hours_json', true);
  if (!$json) return true;
  $hours = json_decode($json, true);
  if (!is_array($hours)) return true;
  try { $dt = new DateTime('now', new DateTimeZone($tz)); }
  catch (Exception $e) { $dt = new DateTime('now'); }
  $dow = strtolower($dt->format('D')); // mon..sun
  $time = $dt->format('H:i');
  $map = ['mon','tue','wed','thu','fri','sat','sun'];
  $key = in_array($dow, $map) ? $dow : strtolower($dt->format('D'));
  $intervals = isset($hours[$key]) ? $hours[$key] : [];
  foreach($intervals as $i){
    $open  = isset($i['open']) ? $i['open'] : null;
    $close = isset($i['close']) ? $i['close'] : null;
    if ($open && $close && $open <= $time && $time <= $close) return true;
  }
  return false;
}

function vc_get_product_restaurant_id($product_id){
  $rid = get_post_meta($product_id, '_rc_restaurant_id', true);
  if ($rid) return (int)$rid;
  $author = (int)get_post_field('post_author', $product_id);
  $rid = get_user_meta($author, '_rc_restaurant_id', true);
  return $rid ? (int)$rid : 0;
}
function vc_get_order_restaurant_id($order){
  $rid = (int)$order->get_meta('_rc_restaurant_id');
  if ($rid) return $rid;
  foreach($order->get_items() as $item){
    $rid = vc_get_product_restaurant_id($item->get_product_id());
    if ($rid) return $rid;
  }
  return 0;
}

// ======================================================
// 1) CPT, taxonomias e metas
// ======================================================
add_action('init', function () {
  register_post_type('restaurant', [
    'label' => 'Restaurantes',
    'public' => true,
    'show_in_rest' => true,
    'menu_icon' => 'dashicons-store',
    'supports' => ['title','editor','thumbnail','author'],
    'has_archive' => true,
    'rewrite' => ['slug' => 'restaurantes'],
  ]);
  register_taxonomy('cuisine', 'restaurant', [
    'label' => 'Cozinha', 'public' => true, 'show_in_rest' => true, 'hierarchical' => true,
  ]);
  register_taxonomy('district', 'restaurant', [
    'label' => 'Bairro/Zona', 'public' => true, 'show_in_rest' => true, 'hierarchical' => true,
  ]);

  $metas = [
    '_rc_whatsapp'           => 'string',
    '_rc_address'            => 'string',
    '_rc_address_number'     => 'string',
    '_rc_address_complement' => 'string',
    '_rc_city'               => 'string',
    '_rc_state'              => 'string',
    '_rc_zip'                => 'string',
    '_rc_lat'                => 'number',
    '_rc_lng'                => 'number',
    '_rc_delivery'           => 'boolean',
    '_rc_pickup'             => 'boolean',
    '_rc_delivery_radius_km' => 'number',
    '_rc_delivery_time_min'  => 'integer',
    '_rc_delivery_time_max'  => 'integer',
    '_rc_min_order'          => 'number',
    '_rc_delivery_fee_base'  => 'number',
    '_rc_delivery_fee_per_km'=> 'number',
    '_rc_free_delivery_over' => 'number',
    '_rc_accept_cash'        => 'boolean',
    '_rc_accept_pix'         => 'boolean',
    '_rc_accept_card_on_delivery' => 'boolean',
    '_rc_pix_key'            => 'string',
    '_rc_hours_json'         => 'string',
    '_rc_featured'           => 'boolean',
    '_rc_logo_id'            => 'integer',
    '_rc_cover_id'           => 'integer',
    '_rc_owner_user_id'      => 'integer',
    '_rc_accepting_orders'   => 'boolean',
  ];
  foreach($metas as $key=>$type){
    register_post_meta('restaurant', $key, [
      'type' => $type, 'single' => true, 'show_in_rest' => true,
      'auth_callback' => function() { return current_user_can('edit_posts'); }
    ]);
  }

  // Produto -> restaurante
  register_post_meta('product', '_rc_restaurant_id', [
    'type' => 'integer','single'=>true,'show_in_rest'=>true,
    'auth_callback'=>function(){ return current_user_can('edit_products'); }
  ]);
  register_post_meta('product', '_rc_menu_section', [
    'type' => 'string','single'=>true,'show_in_rest'=>true,
    'auth_callback'=>function(){ return current_user_can('edit_products'); }
  ]);
  register_post_meta('product', '_rc_external_url', [
    'type' => 'string','single'=>true,'show_in_rest'=>true,
    'auth_callback'=>function(){ return current_user_can('edit_products'); }
  ]);
}, 5);

// ======================================================
// 2) Papel lojista e assets (inclui Leaflet e KDS)
// ======================================================
add_action('init', function(){
  if (!get_role('lojista')) {
    add_role('lojista','Lojista', [
      'read'=>true,
      'edit_products'=>true,
      'publish_products'=>true,
      'edit_published_products'=>true,
      'delete_products'=>true,
      'upload_files'=>true,
      'edit_others_products'=>false,
      'delete_others_products'=>false,
    ]);
  }
}, 9);

add_action('wp_enqueue_scripts', function(){
  // base
  wp_register_style('vemcomer-core', VEMCOMER_CORE_URL . 'assets/style.css', [], VEMCOMER_CORE_VERSION);
  wp_register_script('vemcomer-explore', VEMCOMER_CORE_URL . 'assets/explore.js', [], VEMCOMER_CORE_VERSION, true);
  wp_register_script('vemcomer-checkout-geo', VEMCOMER_CORE_URL . 'assets/checkout-geo.js', ['jquery'], VEMCOMER_CORE_VERSION, true);
  wp_register_script('vemcomer-geo-address', VEMCOMER_CORE_URL . 'assets/geo-address.js', ['jquery'], VEMCOMER_CORE_VERSION, true);

  // Leaflet
  wp_register_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
  wp_register_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
  wp_register_script('vemcomer-explore-map', VEMCOMER_CORE_URL . 'assets/explore-map.js', ['leaflet'], VEMCOMER_CORE_VERSION, true);
  wp_register_script('vemcomer-restaurant-map', VEMCOMER_CORE_URL . 'assets/restaurant-map.js', ['leaflet'], VEMCOMER_CORE_VERSION, true);

  // KDS
  wp_register_script('vemcomer-kds', VEMCOMER_CORE_URL . 'assets/kds.js', [], VEMCOMER_CORE_VERSION, true);
});

// ======================================================
// 3) Botões Add to Cart/externo e bloqueio por "aceitando pedidos"
// ======================================================
add_filter('woocommerce_loop_add_to_cart_link', function($button, $product){
  $url = get_post_meta($product->get_id(), '_rc_external_url', true);
  if ($url) {
    return sprintf('<a class="button" target="_blank" rel="noopener" href="%s">%s</a>',
      esc_url($url), esc_html__('Pedir agora', 'vemcomer'));
  }
  return $button;
}, 10, 2);

// Bloqueia compra se restaurante pausado
add_filter('woocommerce_is_purchasable', function($purchasable, $product){
  $rid = vc_get_product_restaurant_id($product->get_id());
  if ($rid){
    $accepting = get_post_meta($rid,'_rc_accepting_orders', true);
    if ($accepting !== '1') return false;
  }
  return $purchasable;
}, 10, 2);

// ======================================================
// 4) Um restaurante por carrinho
// ======================================================
add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id){
  $rid_new = vc_get_product_restaurant_id($product_id);
  if (!$rid_new) return $passed;
  if (WC()->cart && !WC()->cart->is_empty()) {
    foreach (WC()->cart->get_cart() as $item) {
      $rid_existing = vc_get_product_restaurant_id($item['product_id']);
      if ($rid_existing && $rid_existing !== $rid_new) {
        wc_add_notice(__('Seu carrinho já tem itens de outro restaurante. Finalize ou esvazie para continuar.', 'vemcomer'), 'error');
        return false;
      }
    }
  }
  return $passed;
}, 10, 2);

// ======================================================
// 5) Checkout: campos, validações e taxas (com endereço textual)
// ======================================================
add_filter('woocommerce_checkout_fields', function($fields){
  if (isset($fields['billing']['billing_phone'])) {
    $fields['billing']['billing_phone']['required'] = true;
    $fields['billing']['billing_phone']['priority'] = 25;
  }
  $fields['order']['rc_delivery_method'] = [
    'type'=>'select','label'=>__('Entrega ou retirada?', 'vemcomer'),'required'=>true,
    'options'=>['delivery'=>__('Entrega','vemcomer'),'pickup'=>__('Retirada no local','vemcomer')],'priority'=>1
  ];
  $fields['order']['rc_payment_mode'] = [
    'type'=>'select','label'=>__('Forma de pagamento', 'vemcomer'),'required'=>true,
    'options'=>['card_on_delivery'=>__('Cartão na entrega','vemcomer'),'pix_on_delivery'=>__('Pix (na entrega)','vemcomer'),'cash'=>__('Dinheiro','vemcomer')],'priority'=>2
  ];
  $fields['order']['rc_cash_needs_change'] = ['type'=>'checkbox','label'=>__('Precisa de troco?','vemcomer'),'required'=>false, 'priority'=>3];
  $fields['order']['rc_cash_change_for'] = ['type'=>'text','label'=>__('Troco para quanto?','vemcomer'),'required'=>false,'priority'=>4];

  // Endereço textual + lat/lng ocultos
  $fields['shipping']['rc_shipping_address_text'] = [
    'type'=>'text','label'=>__('Endereço de entrega (busca por texto)', 'vemcomer'),
    'required'=>false,'priority'=>85,'placeholder'=>__('Ex.: Rua, número, bairro, cidade', 'vemcomer'),
    'class'=>['form-row-wide']
  ];
  $fields['shipping']['rc_shipping_lat'] = ['type'=>'hidden','label'=>'','required'=>false];
  $fields['shipping']['rc_shipping_lng'] = ['type'=>'hidden','label'=>'','required'=>false];
  return $fields;
});

add_action('wp', function(){
  if (function_exists('is_checkout') && is_checkout()) {
    wp_enqueue_script('vemcomer-checkout-geo');
    wp_enqueue_script('vemcomer-geo-address');
  }
});

add_action('woocommerce_checkout_process', function(){
  $method = isset($_POST['rc_delivery_method']) ? sanitize_text_field($_POST['rc_delivery_method']) : '';
  $payment = isset($_POST['rc_payment_mode']) ? sanitize_text_field($_POST['rc_payment_mode']) : '';
  $needs_change = isset($_POST['rc_cash_needs_change']) ? (bool)$_POST['rc_cash_needs_change'] : false;
  $change_for = isset($_POST['rc_cash_change_for']) ? trim($_POST['rc_cash_change_for']) : '';

  if (!$method) wc_add_notice(__('Selecione entrega ou retirada.', 'vemcomer'), 'error');
  if (!$payment) wc_add_notice(__('Selecione a forma de pagamento.', 'vemcomer'), 'error');
  if ($payment === 'cash' && $needs_change && $change_for === '') wc_add_notice(__('Informe o valor para troco.', 'vemcomer'), 'error');

  if (!WC()->cart->is_empty()){
    $rid = 0; foreach(WC()->cart->get_cart() as $item){ $rid = vc_get_product_restaurant_id($item['product_id']); if ($rid) break; }
    if ($rid){
      if (!vc_is_open_now($rid)) wc_add_notice(__('O restaurante está fechado no momento.', 'vemcomer'), 'error');
      $min = floatval(get_post_meta($rid, '_rc_min_order', true));
      if ($min > 0 && WC()->cart->get_subtotal() < $min) wc_add_notice(sprintf(__('Pedido mínimo é %s.', 'vemcomer'), wc_price($min)), 'error');

      if ($method === 'delivery'){
        $lat = isset($_POST['rc_shipping_lat']) ? floatval($_POST['rc_shipping_lat']) : 0;
        $lng = isset($_POST['rc_shipping_lng']) ? floatval($_POST['rc_shipping_lng']) : 0;
        $addr = isset($_POST['rc_shipping_address_text']) ? trim(wp_unslash($_POST['rc_shipping_address_text'])) : '';

        if ((!$lat || !$lng) && !$addr){
          wc_add_notice(__('Informe um endereço de entrega ou use o botão de localização.', 'vemcomer'), 'error');
        } else {
          if ((!$lat || !$lng) && $addr){
            $resp = wp_remote_get('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($addr), ['timeout'=>6,'headers'=>['User-Agent'=>'Vemcomer/0.5 (WordPress)']]);
            if (!is_wp_error($resp) && 200 === wp_remote_retrieve_response_code($resp)){
              $body = json_decode(wp_remote_retrieve_body($resp), true);
              if (!empty($body[0]['lat']) && !empty($body[0]['lon'])){
                $lat = floatval($body[0]['lat']); $lng = floatval($body[0]['lon']);
                $_POST['rc_shipping_lat'] = $lat; $_POST['rc_shipping_lng'] = $lng;
              }
            }
          }
          if ($lat && $lng){
            $rlat = floatval(get_post_meta($rid,'_rc_lat',true));
            $rlng = floatval(get_post_meta($rid,'_rc_lng',true));
            $radius = floatval(get_post_meta($rid,'_rc_delivery_radius_km',true));
            if ($rlat && $rlng && $radius){
              $dist = vc_haversine_km($rlat,$rlng,$lat,$lng);
              if ($dist > $radius + 0.01) wc_add_notice(__('Endereço fora da área de entrega.', 'vemcomer'), 'error');
            }
          }
        }
      }
    }
  }
});

add_action('woocommerce_checkout_create_order', function($order, $data){
  $method = sanitize_text_field($_POST['rc_delivery_method'] ?? 'delivery');
  $payment = sanitize_text_field($_POST['rc_payment_mode'] ?? 'cash');
  $needs_change = !empty($_POST['rc_cash_needs_change']) ? 1 : 0;
  $change_for = sanitize_text_field($_POST['rc_cash_change_for'] ?? '');
  $lat = sanitize_text_field($_POST['rc_shipping_lat'] ?? '');
  $lng = sanitize_text_field($_POST['rc_shipping_lng'] ?? '');
  $addr = sanitize_text_field($_POST['rc_shipping_address_text'] ?? '');

  $rid = 0; foreach(WC()->cart->get_cart() as $item){ $rid = vc_get_product_restaurant_id($item['product_id']); if ($rid) break; }
  if ($rid) $order->update_meta_data('_rc_restaurant_id', $rid);

  $order->update_meta_data('_rc_delivery_method', $method);
  $order->update_meta_data('_rc_payment_mode', $payment);
  $order->update_meta_data('_rc_cash_needs_change', $needs_change);
  $order->update_meta_data('_rc_cash_change_for', $change_for);
  $order->update_meta_data('_rc_contact_phone', $data['billing_phone'] ?? '');
  if ($addr) $order->update_meta_data('_rc_shipping_address_text', $addr);
  if ($lat && $lng){
    $order->update_meta_data('_rc_shipping_lat', $lat);
    $order->update_meta_data('_rc_shipping_lng', $lng);
    if ($rid){
      $rlat = floatval(get_post_meta($rid,'_rc_lat',true));
      $rlng = floatval(get_post_meta($rid,'_rc_lng',true));
      if ($rlat && $rlng){
        $dist = vc_haversine_km($rlat,$rlng, floatval($lat), floatval($lng));
        $order->update_meta_data('_rc_distance_km', $dist);
      }
    }
  }
  $order->set_status('wc-awaiting_confirmation');
}, 10, 2);

add_action('woocommerce_cart_calculate_fees', function($cart){
  if (is_admin() && !defined('DOING_AJAX')) return;
  if (WC()->cart->is_empty()) return;

  $rid = 0; foreach($cart->get_cart() as $item){ $rid = vc_get_product_restaurant_id($item['product_id']); if ($rid) break; }
  if (!$rid) return;

  $method = isset($_POST['rc_delivery_method']) ? sanitize_text_field($_POST['rc_delivery_method']) : 'delivery';
  if ($method !== 'delivery') return;

  $base = floatval(get_post_meta($rid,'_rc_delivery_fee_base',true));
  $per_km = floatval(get_post_meta($rid,'_rc_delivery_fee_per_km',true));
  $free_over = floatval(get_post_meta($rid,'_rc_free_delivery_over',true));

  $fee = $base;
  $rlat = floatval(get_post_meta($rid,'_rc_lat',true));
  $rlng = floatval(get_post_meta($rid,'_rc_lng',true));
  $lat = isset($_POST['rc_shipping_lat']) ? floatval($_POST['rc_shipping_lat']) : 0;
  $lng = isset($_POST['rc_shipping_lng']) ? floatval($_POST['rc_shipping_lng']) : 0;

  if ($rlat && $rlng && $lat && $lng && $per_km > 0){
    $dist = vc_haversine_km($rlat,$rlng,$lat,$lng);
    $fee += $per_km * max(0, $dist);
  }
  if ($free_over > 0 && $cart->get_subtotal() >= $free_over) $fee = 0;
  if ($fee > 0) $cart->add_fee(__('Entrega', 'vemcomer'), $fee, false);
});

// ======================================================
// 6) Status de pedido personalizados + hooks
// ======================================================
add_action('init', function(){
  register_post_status('wc-awaiting_confirmation', [
    'label' => 'Aguardando confirmação','public' => true,'show_in_admin_status_list' => true,'show_in_admin_all_list' => true,
    'label_count' => _n_noop('Aguardando confirmação (%s)', 'Aguardando confirmação (%s)')
  ]);
  register_post_status('wc-confirmed', [
    'label' => 'Confirmado','public' => true,'show_in_admin_status_list' => true,'show_in_admin_all_list' => true,
    'label_count' => _n_noop('Confirmado (%s)', 'Confirmado (%s)')
  ]);
  register_post_status('wc-preparing', [
    'label' => 'Em preparo','public' => true,'show_in_admin_status_list' => true,'show_in_admin_all_list' => true,
    'label_count' => _n_noop('Em preparo (%s)', 'Em preparo (%s)')
  ]);
  register_post_status('wc-out_for_delivery', [
    'label' => 'A caminho','public' => true,'show_in_admin_status_list' => true,'show_in_admin_all_list' => true,
    'label_count' => _n_noop('A caminho (%s)', 'A caminho (%s)')
  ]);
  register_post_status('wc-delivered', [
    'label' => 'Entregue','public' => true,'show_in_admin_status_list' => true,'show_in_admin_all_list' => true,
    'label_count' => _n_noop('Entregue (%s)', 'Entregue (%s)')
  ]);
});
add_filter('wc_order_statuses', function($statuses){
  $new = [];
  foreach($statuses as $k=>$v){
    $new[$k] = $v;
    if ($k === 'wc-pending'){
      $new['wc-awaiting_confirmation'] = 'Aguardando confirmação';
      $new['wc-confirmed'] = 'Confirmado';
      $new['wc-preparing'] = 'Em preparo';
      $new['wc-out_for_delivery'] = 'A caminho';
      $new['wc-delivered'] = 'Entregue';
    }
  }
  return $new;
});
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status, $order){
  $now = current_time('mysql');
  switch($new_status){
    case 'awaiting_confirmation': update_post_meta($order_id, '_rc_confirmed_at', ''); break;
    case 'confirmed': update_post_meta($order_id, '_rc_confirmed_at', $now); do_action('rc_order_confirmed', $order_id); break;
    case 'preparing': update_post_meta($order_id, '_rc_preparing_at', $now); do_action('rc_order_preparing', $order_id); break;
    case 'out_for_delivery': update_post_meta($order_id, '_rc_out_at', $now); do_action('rc_order_out', $order_id); break;
    case 'delivered': update_post_meta($order_id, '_rc_delivered_at', $now); do_action('rc_order_delivered', $order_id); break;
    case 'cancelled': update_post_meta($order_id, '_rc_cancelled_at', $now); do_action('rc_order_cancelled', $order_id); break;
  }
  do_action('rc_status_changed', $order_id, $old_status, $new_status);
}, 10, 4);

// ======================================================
// 7) Shortcodes (menu, orders, tracker, explore, forms, favorites, history)
// ======================================================
add_shortcode('vc_restaurant_menu', function($atts){
  $a = shortcode_atts(['id'=>0], $atts);
  $rid = (int)$a['id'];
  if (!$rid) return '<p>Restaurante inválido.</p>';
  wp_enqueue_style('vemcomer-core');

  // Mapa
  $lat = get_post_meta($rid,'_rc_lat',true);
  $lng = get_post_meta($rid,'_rc_lng',true);
  if ($lat && $lng){
    wp_enqueue_style('leaflet');
    wp_enqueue_script('leaflet');
    wp_enqueue_script('vemcomer-restaurant-map');
    wp_localize_script('vemcomer-restaurant-map', 'VC_RESTAURANT_MAP', [
      'lat' => (float)$lat, 'lng' => (float)$lng, 'title' => get_the_title($rid),
      'tiles' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    ]);
    echo '<div id="vc-restaurant-map" style="height:260px; margin-bottom:12px; border-radius:12px; overflow:hidden;"></div>';
  }

  // Botão de favoritos
  echo do_shortcode('[vc_favorite_button id="'.$rid.'"]');

  // Lista produtos
  $q = new WP_Query([ 'post_type'=>'product','posts_per_page'=>-1,
    'meta_query'=>[['key'=>'_rc_restaurant_id','value'=>$rid,'compare'=>'=']]
  ]);
  ob_start(); ?>
  <div class="vc-menu">
    <?php if ($q->have_posts()): while($q->have_posts()): $q->the_post();
      global $product; ?>
      <article class="vc-menu-item">
        <div class="vc-menu-body">
          <h4><?php the_title(); ?></h4>
          <div class="vc-menu-price"><?php echo $product ? $product->get_price_html() : ''; ?></div>
          <div class="vc-menu-actions"><?php woocommerce_template_loop_add_to_cart(); ?></div>
        </div>
      </article>
    <?php endwhile; wp_reset_postdata(); else: ?>
      <p>Este restaurante ainda não cadastrou produtos.</p>
    <?php endif; ?>
  </div>
  <?php
  return ob_get_clean();
});

add_shortcode('vc_restaurant_orders', function(){
  if (!is_user_logged_in()) return '<p>Entre para ver os pedidos.</p>';
  $user = wp_get_current_user();
  $rid = (int)get_user_meta($user->ID, '_rc_restaurant_id', true);
  if (!$rid) return '<p>Você não tem restaurante vinculado.</p>';
  wp_enqueue_style('vemcomer-core');

  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['vc_order_action']) && isset($_POST['_vc_order_nonce']) && wp_verify_nonce($_POST['_vc_order_nonce'], 'vc_order_'.$rid)){
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = sanitize_text_field($_POST['vc_order_action']);
    $order = wc_get_order($order_id);
    if ($order && (int)$order->get_meta('_rc_restaurant_id') === $rid){
      switch($action){
        case 'confirm': $order->update_status('wc-confirmed'); break;
        case 'prepare': $order->update_status('wc-preparing'); break;
        case 'out': $order->update_status('wc-out_for_delivery'); break;
        case 'delivered': $order->update_status('wc-delivered'); break;
        case 'cancel': $order->update_status('wc-cancelled'); break;
      }
      echo '<div class="vc-notice vc-success">Status atualizado.</div>';
    } else { echo '<div class="vc-notice">Pedido inválido.</div>'; }
  }

  $orders = wc_get_orders([ 'limit'=>20,'orderby'=>'date','order'=>'DESC',
    'meta_key'=>'_rc_restaurant_id','meta_value'=>$rid,'meta_compare'=>'=',
    'status'=>['wc-awaiting_confirmation','wc-confirmed','wc-preparing','wc-out_for_delivery','wc-delivered','wc-pending','wc-processing','wc-on-hold']
  ]);

  ob_start(); ?>
  <div class="vc-orders">
    <h3>Pedidos do meu restaurante</h3>
    <form method="post" style="display:none;"><?php wp_nonce_field('vc_order_'.$rid, '_vc_order_nonce'); ?></form>
    <div class="vc-orders-list">
      <?php if (empty($orders)): ?>
        <p>Sem pedidos recentes.</p>
      <?php else: foreach($orders as $order):
        $status = $order->get_status(); $oid = $order->get_id();
        $items = $order->get_items();
        $phone = $order->get_meta('_rc_contact_phone');
        $method = $order->get_meta('_rc_delivery_method');
        $pay = $order->get_meta('_rc_payment_mode');
        $dist = $order->get_meta('_rc_distance_km');
      ?>
      <div class="vc-order-card">
        <div class="vc-order-head">
          <strong>#<?php echo $oid; ?></strong> • <?php echo wc_get_order_status_name($status); ?> • <?php echo esc_html($order->get_date_created()->date_i18n('d/m H:i')); ?>
        </div>
        <div class="vc-order-body">
          <div class="vc-order-col">
            <div class="vc-order-sub">Itens</div>
            <ul class="vc-items">
              <?php foreach($items as $it): ?>
                <li><?php echo esc_html($it->get_name()); ?> x <?php echo esc_html($it->get_quantity()); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="vc-order-col">
            <div class="vc-order-sub">Entrega</div>
            <div><?php echo $method==='pickup' ? 'Retirada' : 'Entrega'; ?><?php if ($dist) echo ' • ~'.number_format($dist,1,',','.').' km'; ?></div>
            <div class="vc-order-sub">Pagamento</div>
            <div><?php echo $pay==='card_on_delivery' ? 'Cartão na entrega' : ($pay==='pix_on_delivery' ? 'Pix na entrega' : 'Dinheiro'); ?></div>
            <?php if ($phone): ?><div><strong>Telefone:</strong> <?php echo esc_html($phone); ?></div><?php endif; ?>
          </div>
          <div class="vc-order-col vc-order-actions">
            <form method="post">
              <?php wp_nonce_field('vc_order_'.$rid, '_vc_order_nonce'); ?>
              <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
              <div class="vc-actions-row">
                <?php if ($status==='awaiting_confirmation'): ?>
                  <button class="button" name="vc_order_action" value="confirm">Confirmar</button>
                <?php endif; ?>
                <?php if (in_array($status, ['confirmed','awaiting_confirmation'])): ?>
                  <button class="button" name="vc_order_action" value="prepare">Iniciar preparo</button>
                <?php endif; ?>
                <?php if (in_array($status, ['preparing'])): ?>
                  <button class="button" name="vc_order_action" value="out">Saiu p/ entrega</button>
                <?php endif; ?>
                <?php if (in_array($status, ['out_for_delivery'])): ?>
                  <button class="button" name="vc_order_action" value="delivered">Entregue</button>
                <?php endif; ?>
                <?php if (!in_array($status, ['delivered','cancelled'])): ?>
                  <button class="button button-outline" name="vc_order_action" value="cancel">Cancelar</button>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
        <div class="vc-order-foot"><strong>Total:</strong> <?php echo $order->get_formatted_order_total(); ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

add_shortcode('vc_order_tracker', function($atts){
  $a = shortcode_atts(['order_id'=>0], $atts);
  $order = null;
  if ($a['order_id']) $order = wc_get_order((int)$a['order_id']);
  elseif (is_user_logged_in()){
    $customer_id = get_current_user_id();
    $orders = wc_get_orders(['customer_id'=>$customer_id,'limit'=>1,'orderby'=>'date','order'=>'DESC']);
    $order = $orders ? $orders[0] : null;
  }
  if (!$order) return '<p>Nenhum pedido encontrado.</p>';
  wp_enqueue_style('vemcomer-core');

  $status = $order->get_status();
  $map = ['awaiting_confirmation'=>1, 'confirmed'=>2, 'preparing'=>3, 'out_for_delivery'=>4, 'delivered'=>5];
  $step = isset($map[$status]) ? $map[$status] : 1;

  ob_start(); ?>
  <div class="vc-tracker" id="rastreador">
    <div class="vc-steps">
      <div class="vc-step <?php echo $step>=1?'is-done':''; ?>">1<span>Aguardando</span></div>
      <div class="vc-step <?php echo $step>=2?'is-done':''; ?>">2<span>Confirmado</span></div>
      <div class="vc-step <?php echo $step>=3?'is-done':''; ?>">3<span>Em preparo</span></div>
      <div class="vc-step <?php echo $step>=4?'is-done':''; ?>">4<span>A caminho</span></div>
      <div class="vc-step <?php echo $step>=5?'is-done':''; ?>">5<span>Entregue</span></div>
    </div>
    <div class="vc-tracker-info">
      <div><strong>Pedido:</strong> #<?php echo $order->get_id(); ?></div>
      <div><strong>Status:</strong> <?php echo wc_get_order_status_name($order->get_status()); ?></div>
      <div><strong>Total:</strong> <?php echo $order->get_formatted_order_total(); ?></div>
    </div>
  </div>
  <?php
  return ob_get_clean();
});

add_shortcode('vc_explore', function($atts){
  $a = shortcode_atts([ 'per_page' => 12, 'default_radius' => 5 ], $atts);
  wp_enqueue_style('vemcomer-core'); 
  wp_enqueue_script('vemcomer-explore');

  $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
  $cuisine = isset($_GET['cuisine']) ? sanitize_text_field($_GET['cuisine']) : '';
  $district = isset($_GET['district']) ? sanitize_text_field($_GET['district']) : '';
  $open = isset($_GET['open']) ? (int)$_GET['open'] : 0;
  $featured = isset($_GET['featured']) ? (int)$_GET['featured'] : 0;
  $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
  $lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
  $radius = isset($_GET['radius']) ? floatval($_GET['radius']) : floatval($a['default_radius']);

  $args = [
    'post_type'=>'restaurant','post_status'=>'publish','posts_per_page'=>-1,'s'=>$q,
    'tax_query' => ['relation'=>'AND'],'meta_query' => ['relation'=>'AND'],
  ];
  if ($cuisine) $args['tax_query'][] = ['taxonomy'=>'cuisine','field'=>'slug','terms'=>[$cuisine]];
  if ($district) $args['tax_query'][] = ['taxonomy'=>'district','field'=>'slug','terms'=>[$district]];
  if ($featured) $args['meta_query'][] = ['key'=>'_rc_featured','value'=>'1'];
  if (!is_null($lat) && !is_null($lng)){
    $args['meta_query'][] = ['key'=>'_rc_lat','compare'=>'EXISTS'];
    $args['meta_query'][] = ['key'=>'_rc_lng','compare'=>'EXISTS'];
  }

  $loop = new WP_Query($args);
  $items = []; $markers = [];
  if ($loop->have_posts()){
    while($loop->have_posts()){ $loop->the_post();
      $id = get_the_ID();
      $r_lat = get_post_meta($id,'_rc_lat',true);
      $r_lng = get_post_meta($id,'_rc_lng',true);
      $open_now = vc_is_open_now($id)?1:0;

      $item = [
        'ID'=>$id,
        'title'=>get_the_title(),
        'permalink'=>get_permalink(),
        'address'=>get_post_meta($id,'_rc_address',true),
        'featured'=>get_post_meta($id,'_rc_featured',true)=='1',
        'open'=>$open_now,
        'lat'=>$r_lat,
        'lng'=>$r_lng,
        'thumb'=>get_the_post_thumbnail_url($id, 'medium')
      ];
      if (!is_null($lat) && !is_null($lng) && $item['lat'] && $item['lng']) {
        $item['distance'] = vc_haversine_km(floatval($lat), floatval($lng), floatval($item['lat']), floatval($item['lng']));
      } else { $item['distance'] = null; }

      if (!is_null($lat) && !is_null($lng) && $item['distance']!==null && $radius>0) {
        if ($item['distance'] > $radius) continue;
      }
      if ($open && !$item['open']) continue;

      $items[] = $item;

      if ($r_lat && $r_lng){
        $markers[] = [
          'id'=>$id, 'title'=>get_the_title(),
          'lat'=>(float)$r_lat, 'lng'=>(float)$r_lng,
          'url'=>get_permalink(), 'open'=>$open_now,
          'featured'=>get_post_meta($id,'_rc_featured',true)=='1',
        ];
      }
    } wp_reset_postdata();
  }
  usort($items, function($a,$b){
    if ($a['featured'] != $b['featured']) return $a['featured'] ? -1 : 1;
    if ($a['distance'] !== $b['distance']) {
      if ($a['distance'] === null) return 1;
      if ($b['distance'] === null) return -1;
      return $a['distance'] <=> $b['distance'];
    }
    return strcasecmp($a['title'],$b['title']);
  });

  if (!empty($markers)){
    wp_enqueue_style('leaflet'); wp_enqueue_script('leaflet');
    wp_enqueue_script('vemcomer-explore-map');
    wp_localize_script('vemcomer-explore-map', 'VC_EXPLORE_MAP', [
      'markers' => $markers,
      'user' => (!is_null($lat) && !is_null($lng)) ? ['lat'=>(float)$lat, 'lng'=>(float)$lng] : null,
      'tiles' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    ]);
  }

  $per = max(1, (int)$a['per_page']);
  $page = isset($_GET['pg']) ? max(1,(int)$_GET['pg']) : 1;
  $total = count($items);
  $pages = max(1, ceil($total/$per));
  $slice = array_slice($items, ($page-1)*$per, $per);

  ob_start(); ?>
  <div class="vc-explore">
    <form class="vc-filters" method="get">
      <input type="text" name="q" placeholder="Buscar restaurantes..." value="<?php echo esc_attr($q); ?>">
      <input type="text" name="cuisine" placeholder="Cozinha (slug)" value="<?php echo esc_attr($cuisine); ?>">
      <input type="text" name="district" placeholder="Bairro (slug)" value="<?php echo esc_attr($district); ?>">
      <label><input type="checkbox" name="open" value="1" <?php checked($open,1); ?>> Aberto agora</label>
      <label><input type="checkbox" name="featured" value="1" <?php checked($featured,1); ?>> Destaque</label>
      <div class="vc-grid-3">
        <div><input type="number" step="0.000001" name="lat" placeholder="Lat" value="<?php echo esc_attr($lat); ?>"></div>
        <div><input type="number" step="0.000001" name="lng" placeholder="Lng" value="<?php echo esc_attr($lng); ?>"></div>
        <div><input type="number" step="0.1" name="radius" placeholder="Raio (km)" value="<?php echo esc_attr($radius); ?>"></div>
      </div>
      <button class="button">Filtrar</button>
      <a class="button button-outline" id="vc-use-location" href="javascript:void(0)">Usar minha localização</a>
    </form>

    <?php if (!empty($markers)): ?>
      <div id="vc-map" style="height:360px; margin-bottom:16px; border-radius:12px; overflow:hidden;"></div>
      <small style="display:block; margin-top:-10px; margin-bottom:12px; color:#6b7280;">Mapa por Leaflet • Tiles © OpenStreetMap contributors</small>
    <?php endif; ?>

    <div class="vc-grid">
      <?php if (empty($slice)): ?>
        <p>Nenhum restaurante encontrado.</p>
      <?php else: foreach($slice as $it): ?>
        <article class="vc-card">
          <?php if ($it['thumb']): ?><img src="<?php echo esc_url($it['thumb']); ?>" alt="<?php echo esc_attr($it['title']); ?>"><?php endif; ?>
          <div class="vc-card-body">
            <h4><a href="<?php echo esc_url($it['permalink']); ?>"><?php echo esc_html($it['title']); ?></a></h4>
            <div class="vc-badges">
              <?php if ($it['featured']): ?><span class="vc-badge vc-badge--featured">Destaque</span><?php endif; ?>
              <?php if ($it['open']): ?><span class="vc-badge vc-badge--open">Aberto agora</span><?php else: ?><span class="vc-badge">Fechado</span><?php endif; ?>
              <?php if (!is_null($it['distance'])): ?><span class="vc-badge"><?php echo number_format($it['distance'], 1, ',', '.'); ?> km</span><?php endif; ?>
            </div>
            <?php echo do_shortcode('[vc_favorite_button id="'.$it['ID'].'"]'); ?>
            <div><a class="button" href="<?php echo esc_url(get_permalink($it['ID']).'?menu=1'); ?>">Ver cardápio</a></div>
          </div>
        </article>
      <?php endforeach; endif; ?>
    </div>

    <?php if ($pages>1): ?>
      <div class="vc-pagination">
        <?php for($i=1;$i<=$pages;$i++): 
          $qs = $_GET; $qs['pg']=$i; $url = esc_url(add_query_arg($qs));
        ?>
          <a class="vc-page <?php if($i==$page) echo 'is-active'; ?>" href="<?php echo $url; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php return ob_get_clean();
});

// ========== Form Restaurante (com horários fáceis) ==========
add_shortcode('vc_restaurant_form', function($atts){
  if (!is_user_logged_in()) return '<p>Entre para cadastrar seu restaurante.</p>';
  wp_enqueue_style('vemcomer-core');

  $user = wp_get_current_user();
  $post_id = get_user_meta($user->ID, '_rc_restaurant_id', true);
  $is_edit = $post_id && get_post_type($post_id)==='restaurant';

  $hours_json = $is_edit ? get_post_meta($post_id,'_rc_hours_json',true) : '';
  $hours = $hours_json ? json_decode($hours_json, true) : [];
  $days = ['mon'=>'Seg','tue'=>'Ter','wed'=>'Qua','thu'=>'Qui','fri'=>'Sex','sat'=>'Sáb','sun'=>'Dom'];
  $extract = function($k,$idx='open') use ($hours){
    if (isset($hours[$k][0][$idx])) return esc_attr($hours[$k][0][$idx]);
    return '';
  };

  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['_rc_restaurant_nonce']) && wp_verify_nonce($_POST['_rc_restaurant_nonce'],'rc_rest_form')) {
    $title = sanitize_text_field($_POST['rc_title'] ?? '');
    $fields = ['_rc_whatsapp','_rc_address','_rc_address_number','_rc_address_complement','_rc_city','_rc_state','_rc_zip','_rc_lat','_rc_lng','_rc_delivery_radius_km','_rc_min_order','_rc_delivery_fee_base','_rc_delivery_fee_per_km','_rc_free_delivery_over','_rc_accept_cash','_rc_accept_pix','_rc_accept_card_on_delivery','_rc_accepting_orders'];
    $data = []; foreach($fields as $f){ $data[$f] = sanitize_text_field($_POST[$f] ?? ''); }

    $keys = ['mon','tue','wed','thu','fri','sat','sun']; $new_hours = [];
    foreach($keys as $k){
      $on = !empty($_POST['open_'.$k]);
      $o = sanitize_text_field($_POST['time_'.$k.'_open'] ?? '');
      $c = sanitize_text_field($_POST['time_'.$k.'_close'] ?? '');
      $new_hours[$k] = ($on && $o && $c) ? [['open'=>$o,'close'=>$c]] : [];
    }
    $data['_rc_hours_json'] = wp_json_encode($new_hours);

    if (!$is_edit){
      $post_id = wp_insert_post(['post_type'=>'restaurant','post_status'=>'pending','post_title'=>$title ?: 'Meu Restaurante','post_author'=>$user->ID]);
      update_user_meta($user->ID,'_rc_restaurant_id',$post_id);
      update_post_meta($post_id,'_rc_owner_user_id', $user->ID);
      $user->add_role('lojista');
    } else {
      wp_update_post(['ID'=>$post_id,'post_title'=>$title]);
    }
    foreach($data as $k=>$v){ update_post_meta($post_id, $k, $v); }
    echo '<div class="vc-notice vc-success">Dados salvos! Aguarde aprovação.</div>';
  }

  $p = $is_edit ? get_post($post_id) : null;
  $v = function($meta) use ($post_id){ return esc_attr( $post_id ? get_post_meta($post_id,$meta,true) : '' ); };

  ob_start(); ?>
  <form method="post" class="vc-form">
    <h3>Meu Restaurante</h3>
    <?php wp_nonce_field('rc_rest_form','_rc_restaurant_nonce'); ?>
    <label>Nome do restaurante</label><input name="rc_title" value="<?php echo esc_attr($p? $p->post_title : ''); ?>" required>
    <label>WhatsApp</label><input name="_rc_whatsapp" value="<?php echo $v('_rc_whatsapp'); ?>">
    <label>Endereço</label><input name="_rc_address" value="<?php echo $v('_rc_address'); ?>">

    <div class="vc-grid-3">
      <div><label>Número</label><input name="_rc_address_number" value="<?php echo $v('_rc_address_number'); ?>"></div>
      <div><label>Complemento</label><input name="_rc_address_complement" value="<?php echo $v('_rc_address_complement'); ?>"></div>
      <div><label>Bairro</label><input name="_rc_city" value="<?php echo $v('_rc_city'); ?>"></div>
    </div>
    <div class="vc-grid-3">
      <div><label>Estado</label><input name="_rc_state" value="<?php echo $v('_rc_state'); ?>"></div>
      <div><label>CEP</label><input name="_rc_zip" value="<?php echo $v('_rc_zip'); ?>"></div>
      <div><label>Raio entrega (km)</label><input name="_rc_delivery_radius_km" type="number" step="0.1" value="<?php echo $v('_rc_delivery_radius_km'); ?>"></div>
    </div>
    <div class="vc-grid-3">
      <div><label>Latitude</label><input name="_rc_lat" type="number" step="0.000001" value="<?php echo $v('_rc_lat'); ?>"></div>
      <div><label>Longitude</label><input name="_rc_lng" type="number" step="0.000001" value="<?php echo $v('_rc_lng'); ?>"></div>
      <div><small>Use o mapa para pegar lat/lng ou preencha manualmente.</small></div>
    </div>
    <div class="vc-grid-3">
      <div><label>Mínimo do pedido</label><input name="_rc_min_order" type="number" step="0.01" value="<?php echo $v('_rc_min_order'); ?>"></div>
      <div><label>Taxa base entrega</label><input name="_rc_delivery_fee_base" type="number" step="0.01" value="<?php echo $v('_rc_delivery_fee_base'); ?>"></div>
      <div><label>Taxa por km</label><input name="_rc_delivery_fee_per_km" type="number" step="0.01" value="<?php echo $v('_rc_delivery_fee_per_km'); ?>"></div>
    </div>
    <div class="vc-grid-3">
      <div><label>Frete grátis acima de</label><input name="_rc_free_delivery_over" type="number" step="0.01" value="<?php echo $v('_rc_free_delivery_over'); ?>"></div>
      <div><label><input type="checkbox" name="_rc_accept_cash" value="1" <?php checked($v('_rc_accept_cash'),'1'); ?>> Aceita dinheiro</label></div>
      <div><label><input type="checkbox" name="_rc_accept_pix" value="1" <?php checked($v('_rc_accept_pix'),'1'); ?>> Aceita Pix</label></div>
    </div>
    <label><input type="checkbox" name="_rc_accepting_orders" value="1" <?php checked($v('_rc_accepting_orders'),'1'); ?>> Aceitando pedidos</label>

    <h4>Horários (fácil)</h4>
    <div class="vc-grid-3">
      <?php foreach($days as $key=>$label): ?>
        <div style="border:1px solid #e5e7eb; border-radius:10px; padding:10px;">
          <label style="display:flex; gap:8px; align-items:center;">
            <input type="checkbox" name="open_<?php echo esc_attr($key); ?>" <?php checked(!empty($hours[$key])); ?>>
            <strong><?php echo esc_html($label); ?></strong>
          </label>
          <div style="display:flex; gap:8px; margin-top:8px;">
            <input type="time" name="time_<?php echo esc_attr($key); ?>_open" value="<?php echo $extract($key,'open'); ?>">
            <input type="time" name="time_<?php echo esc_attr($key); ?>_close" value="<?php echo $extract($key,'close'); ?>">
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <small class="vc-muted">Para horários quebrados (almoço/jantar), podemos habilitar intervalos múltiplos numa próxima versão.</small>

    <button type="submit" class="button">Salvar</button>
  </form>
  <?php return ob_get_clean();
});

add_shortcode('vc_product_form', function(){
  if (!is_user_logged_in()) return '<p>Entre para cadastrar produtos.</p>';
  wp_enqueue_style('vemcomer-core');

  $user = wp_get_current_user();
  $rid = (int)get_user_meta($user->ID,'_rc_restaurant_id',true);
  if (!$rid) return '<p>Cadastre seu restaurante primeiro.</p>';

  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['_rc_prod_nonce']) && wp_verify_nonce($_POST['_rc_prod_nonce'],'rc_prod_form')) {
    $title = sanitize_text_field($_POST['title'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $section = sanitize_text_field($_POST['menu_section'] ?? '');

    $pid = wp_insert_post([ 'post_type'=>'product','post_title'=>$title,'post_status'=>'publish','post_author'=>$user->ID ]);
    update_post_meta($pid, '_regular_price', $price);
    update_post_meta($pid, '_price', $price);
    update_post_meta($pid, '_rc_restaurant_id', $rid);
    update_post_meta($pid, '_rc_menu_section', $section);

    echo '<div class="vc-notice vc-success">Produto criado.</div>';
  }

  ob_start(); ?>
  <form method="post" class="vc-form">
    <?php wp_nonce_field('rc_prod_form','_rc_prod_nonce'); ?>
    <h3>Novo Produto</h3>
    <label>Nome do prato</label>
    <input name="title" required>
    <label>Preço</label>
    <input name="price" type="number" step="0.01">
    <label>Seção do cardápio</label>
    <input name="menu_section" placeholder="Ex.: Pizzas, Bebidas">
    <button type="submit" class="button">Adicionar</button>
  </form>
  <?php return ob_get_clean();
});

add_shortcode('vc_product_edit', function($atts){
  if (!is_user_logged_in()) return '<p>Entre para editar.</p>';
  wp_enqueue_style('vemcomer-core');

  $user = wp_get_current_user();
  $pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
  if (!$pid || get_post_type($pid)!=='product' || (int)get_post_field('post_author',$pid)!==$user->ID){
    return '<p>Produto inválido.</p>';
  }
  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['_rc_prod_edit_nonce']) && wp_verify_nonce($_POST['_rc_prod_edit_nonce'],'rc_prod_edit_'.$pid)) {
    wp_update_post(['ID'=>$pid,'post_title'=>sanitize_text_field($_POST['title']??'')]);
    update_post_meta($pid,'_regular_price', floatval($_POST['price']??0));
    update_post_meta($pid,'_price', floatval($_POST['price']??0));
    update_post_meta($pid,'_rc_menu_section', sanitize_text_field($_POST['menu_section']??''));
    echo '<div class="vc-notice vc-success">Produto atualizado.</div>';
  }

  $title = get_the_title($pid);
  $price = get_post_meta($pid,'_price',true);
  $section = get_post_meta($pid,'_rc_menu_section',true);

  ob_start(); ?>
  <form method="post" class="vc-form">
    <?php wp_nonce_field('rc_prod_edit_'.$pid,'_rc_prod_edit_nonce'); ?>
    <h3>Editar Produto</h3>
    <label>Nome</label><input name="title" value="<?php echo esc_attr($title); ?>">
    <label>Preço</label><input name="price" type="number" step="0.01" value="<?php echo esc_attr($price); ?>">
    <label>Seção do cardápio</label><input name="menu_section" value="<?php echo esc_attr($section); ?>">
    <button type="submit" class="button">Salvar</button>
  </form>
  <?php return ob_get_clean();
});

// ========== Favoritos ==========
function vc_get_user_favorites($user_id){
  $arr = get_user_meta($user_id, '_rc_favorites', true);
  if (!is_array($arr)) $arr = [];
  return array_values(array_unique(array_map('intval', $arr)));
}
function vc_save_user_favorites($user_id, $arr){
  $arr = array_values(array_unique(array_filter(array_map('intval', (array)$arr))));
  update_user_meta($user_id, '_rc_favorites', $arr);
}
function vc_is_favorite($user_id, $rid){
  $arr = vc_get_user_favorites($user_id);
  return in_array((int)$rid, $arr, true);
}
add_action('init', function(){
  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['vc_fav_action']) && isset($_POST['_vc_fav_nonce'])){
    if (!is_user_logged_in()) return;
    if (!wp_verify_nonce($_POST['_vc_fav_nonce'], 'vc_fav_nonce')) return;
    $user_id = get_current_user_id();
    $rid = isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0;
    if ($rid && get_post_type($rid)==='restaurant'){
      $favs = vc_get_user_favorites($user_id);
      if (in_array($rid, $favs, true)) $favs = array_values(array_diff($favs, [$rid]));
      else $favs[] = $rid;
      vc_save_user_favorites($user_id, $favs);
      wp_safe_redirect(wp_get_referer() ? wp_get_referer() : home_url('/')); exit;
    }
  }
});
add_shortcode('vc_favorite_button', function($atts){
  $a = shortcode_atts(['id'=>0,'label_add'=>'❤ Favoritar','label_remove'=>'✕ Remover favorito'], $atts);
  $rid = (int)$a['id'];
  if (!$rid || get_post_type($rid)!=='restaurant') return '';
  wp_enqueue_style('vemcomer-core');
  if (!is_user_logged_in()){
    return '<a href="'.esc_url(wp_login_url(get_permalink($rid))).'" class="vc-fav-btn">❤ Favoritar</a>';
  }
  $is_fav = vc_is_favorite(get_current_user_id(), $rid);
  ob_start(); ?>
  <form method="post" class="vc-fav-form">
    <?php wp_nonce_field('vc_fav_nonce', '_vc_fav_nonce'); ?>
    <input type="hidden" name="restaurant_id" value="<?php echo $rid; ?>">
    <button class="vc-fav-btn <?php echo $is_fav ? 'is-fav':''; ?>" name="vc_fav_action" value="toggle">
      <?php echo $is_fav ? esc_html($a['label_remove']) : esc_html($a['label_add']); ?>
    </button>
  </form>
  <?php return ob_get_clean();
});
add_shortcode('vc_favorites', function($atts){
  if (!is_user_logged_in()) return '<p>Entre para ver seus favoritos.</p>';
  wp_enqueue_style('vemcomer-core');
  $user_id = get_current_user_id();
  $favs = vc_get_user_favorites($user_id);
  if (empty($favs)) return '<p>Você ainda não favoritou nenhum restaurante.</p>';

  $q = new WP_Query([ 'post_type'=>'restaurant','post__in'=>$favs,'posts_per_page'=>-1,'orderby'=>'post__in' ]);
  ob_start(); ?>
  <div class="vc-favs">
    <h3>Meus favoritos</h3>
    <div class="vc-grid">
      <?php if ($q->have_posts()): while($q->have_posts()): $q->the_post(); $rid = get_the_ID(); ?>
        <article class="vc-card">
          <?php if (has_post_thumbnail()): the_post_thumbnail('medium'); endif; ?>
          <div class="vc-card-body">
            <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
            <div class="vc-badges">
              <?php if (vc_is_open_now($rid)): ?><span class="vc-badge vc-badge--open">Aberto agora</span><?php else: ?><span class="vc-badge">Fechado</span><?php endif; ?>
            </div>
            <?php echo do_shortcode('[vc_favorite_button id="'.$rid.'"]'); ?>
          </div>
        </article>
      <?php endwhile; wp_reset_postdata(); endif; ?>
    </div>
  </div>
  <?php return ob_get_clean();
});

// ========== Histórico ==========
add_shortcode('vc_customer_history', function($atts){
  if (!is_user_logged_in()) return '<p>Entre para ver seu histórico.</p>';
  wp_enqueue_style('vemcomer-core');

  $user_id = get_current_user_id();
  $orders = wc_get_orders(['customer_id'=>$user_id,'limit'=>50,'orderby'=>'date','order'=>'DESC']);
  $freq = [];

  ob_start(); ?>
  <div class="vc-history">
    <h3>Meus pedidos</h3>
    <?php if (empty($orders)): ?>
      <p>Você ainda não fez pedidos.</p>
    <?php else: ?>
      <div class="vc-orders-list">
        <?php foreach($orders as $order):
          $rid = vc_get_order_restaurant_id($order);
          if ($rid) $freq[$rid] = ($freq[$rid] ?? 0) + 1;
          $rname = $rid ? get_the_title($rid) : 'Restaurante';
          $items = $order->get_items();
        ?>
        <div class="vc-order-card">
          <div class="vc-order-head">
            <strong>#<?php echo $order->get_id(); ?></strong> • <?php echo esc_html($rname); ?> • <?php echo wc_get_order_status_name($order->get_status()); ?> • <?php echo esc_html($order->get_date_created()->date_i18n('d/m H:i')); ?>
          </div>
          <div class="vc-order-body">
            <div class="vc-order-col">
              <div class="vc-order-sub">Itens</div>
              <ul class="vc-items">
                <?php $i=0; foreach($items as $it){ $i++; ?>
                  <li><?php echo esc_html($it->get_name()); ?> x <?php echo esc_html($it->get_quantity()); ?></li>
                <?php if ($i>=5) { echo '<li>…</li>'; break; } } ?>
              </ul>
            </div>
            <div class="vc-order-col">
              <div class="vc-order-sub">Total</div>
              <div><?php echo $order->get_formatted_order_total(); ?></div>
              <div class="vc-order-sub">Acompanhar</div>
              <div><a class="button" href="<?php echo esc_url(add_query_arg(['order_id'=>$order->get_id()], get_permalink())); ?>#rastreador">Ver status</a></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h3>Restaurantes mais pedidos</h3>
    <?php
      arsort($freq);
      $top = array_keys($freq);
      if (empty($top)) {
        echo '<p>Sem histórico ainda.</p>';
      } else {
        $q = new WP_Query(['post_type'=>'restaurant','post__in'=>$top,'posts_per_page'=>12,'orderby'=>'post__in']);
        echo '<div class="vc-grid">';
        if ($q->have_posts()){ while($q->have_posts()){ $q->the_post(); $rid = get_the_ID(); ?>
          <article class="vc-card">
            <?php if (has_post_thumbnail()): the_post_thumbnail('medium'); endif; ?>
            <div class="vc-card-body">
              <h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
              <div class="vc-badges"><span class="vc-badge"><?php echo intval($freq[$rid]); ?> pedido(s)</span></div>
              <?php echo do_shortcode('[vc_favorite_button id="'.$rid.'"]'); ?>
            </div>
          </article>
        <?php } wp_reset_postdata(); }
        echo '</div>';
      }
    ?>
  </div>
  <?php
  return ob_get_clean();
});

// ========== Onboarding (cliente x restaurante) ==========
add_shortcode('vc_onboarding', function(){
  wp_enqueue_style('vemcomer-core');
  $my_account = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : wp_login_url();
  $rest_form = site_url('/minha-loja/');
  ob_start(); ?>
  <div class="vc-grid">
    <div class="vc-card">
      <div class="vc-card-body">
        <h3>Sou Cliente</h3>
        <p>Faça seu cadastro para acompanhar pedidos, ver histórico e salvar favoritos.</p>
        <a class="button" href="<?php echo esc_url($my_account); ?>">Entrar / Cadastrar</a>
      </div>
    </div>
    <div class="vc-card">
      <div class="vc-card-body">
        <h3>Sou Restaurante</h3>
        <p>Cadastre sua loja: endereço, raio, cardápio e comece a receber pedidos.</p>
        <?php if (is_user_logged_in()): ?>
          <a class="button" href="<?php echo esc_url($rest_form); ?>">Começar cadastro</a>
        <?php else: ?>
          <a class="button" href="<?php echo esc_url( wp_login_url($rest_form) ); ?>">Entrar para começar</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php return ob_get_clean();
});

// ======================================================
// 8) KDS + REST endpoints
// ======================================================
add_action('rest_api_init', function(){
  register_rest_route('vemcomer/v1', '/orders', [
    'methods' => 'GET',
    'permission_callback' => function(){ return is_user_logged_in(); },
    'callback' => function(WP_REST_Request $req){
      $rid = (int)$req->get_param('rid');
      if (!$rid) return new WP_Error('no_rid', 'Restaurante não informado', ['status'=>400]);
      $user_id = get_current_user_id();
      $user_rid = (int)get_user_meta($user_id, '_rc_restaurant_id', true);
      if ($user_rid !== $rid && !current_user_can('manage_options')){
        return new WP_Error('forbidden', 'Sem permissão para este restaurante', ['status'=>403]);
      }
      $statuses = $req->get_param('status');
      if ($statuses && is_string($statuses)) $statuses = array_map('trim', explode(',', $statuses));
      else $statuses = ['wc-awaiting_confirmation','wc-confirmed','wc-preparing','wc-out_for_delivery'];

      $orders = wc_get_orders([ 'limit'=>50, 'orderby'=>'date', 'order'=>'DESC',
        'meta_key'=>'_rc_restaurant_id', 'meta_value'=>$rid, 'meta_compare'=>'=',
        'status'=>$statuses
      ]);
      $out = [];
      foreach($orders as $o){
        $items = [];
        foreach($o->get_items() as $it){
          $items[] = ['name'=>$it->get_name(), 'qty'=>$it->get_quantity()];
        }
        $out[] = [
          'id'=>$o->get_id(),
          'status'=>$o->get_status(),
          'status_label'=>wc_get_order_status_name($o->get_status()),
          'created'=>$o->get_date_created() ? $o->get_date_created()->date_i18n('Y-m-d H:i:s') : '',
          'total'=>$o->get_total(),
          'total_html'=>$o->get_formatted_order_total(),
          'items'=>$items,
        ];
      }
      return rest_ensure_response(['orders'=>$out, 'server_time'=>current_time('mysql')]);
    }
  ]);
  register_rest_route('vemcomer/v1', '/orders/(?P<id>\d+)/status', [
    'methods' => 'POST',
    'permission_callback' => function(){ return is_user_logged_in(); },
    'callback' => function(WP_REST_Request $req){
      $order_id = (int)$req['id'];
      $action = sanitize_text_field($req->get_param('action'));
      if (!$order_id || !$action) return new WP_Error('bad_request','Dados inválidos', ['status'=>400]);
      $order = wc_get_order($order_id);
      if (!$order) return new WP_Error('not_found','Pedido não encontrado', ['status'=>404]);

      $user_id = get_current_user_id();
      $rid = (int)$order->get_meta('_rc_restaurant_id');
      $user_rid = (int)get_user_meta($user_id, '_rc_restaurant_id', true);
      if ($user_rid !== $rid && !current_user_can('manage_options')){
        return new WP_Error('forbidden','Sem permissão', ['status'=>403]);
      }
      switch($action){
        case 'confirm': $order->update_status('wc-confirmed'); break;
        case 'prepare': $order->update_status('wc-preparing'); break;
        case 'out': $order->update_status('wc-out_for_delivery'); break;
        case 'delivered': $order->update_status('wc-delivered'); break;
        case 'cancel': $order->update_status('wc-cancelled'); break;
        default: return new WP_Error('bad_action','Ação inválida', ['status'=>400]);
      }
      return rest_ensure_response(['ok'=>true, 'new_status'=>$order->get_status(), 'label'=>wc_get_order_status_name($order->get_status())]);
    }
  ]);
});

add_shortcode('vc_kds', function($atts){
  if (!is_user_logged_in()) return '<p>Entre para ver o KDS.</p>';
  $user = wp_get_current_user();
  $rid = (int)get_user_meta($user->ID, '_rc_restaurant_id', true);
  if (!$rid) return '<p>Você não tem restaurante vinculado.</p>';
  wp_enqueue_style('vemcomer-core');
  wp_enqueue_script('vemcomer-kds');
  wp_localize_script('vemcomer-kds', 'VC_KDS', [
    'rid' => $rid, 'rest' => esc_url_raw(get_rest_url(null, '/vemcomer/v1')),
    'nonce' => wp_create_nonce('wp_rest'), 'poll' => 7000,
  ]);
  ob_start(); ?>
  <div id="vc-kds">
    <div class="vc-kds-head">
      <h3>Kitchen Display System</h3>
      <div class="vc-kds-actions">
        <button class="button button-outline" data-kds-refresh>Atualizar</button>
        <label style="margin-left:8px;"><input type="checkbox" data-kds-sound checked> Som</label>
      </div>
    </div>
    <div class="vc-kds-columns">
      <div class="vc-kds-col" data-col="awaiting_confirmation"><h4>Aguardando</h4><div class="vc-kds-list"></div></div>
      <div class="vc-kds-col" data-col="confirmed"><h4>Confirmado</h4><div class="vc-kds-list"></div></div>
      <div class="vc-kds-col" data-col="preparing"><h4>Em preparo</h4><div class="vc-kds-list"></div></div>
      <div class="vc-kds-col" data-col="out_for_delivery"><h4>A caminho</h4><div class="vc-kds-list"></div></div>
    </div>
    <small class="vc-muted">Atualiza automaticamente a cada 7s.</small>
  </div>
  <?php return ob_get_clean();
});

// ======================================================
// 9) Assinaturas (exemplo)
// ======================================================
add_action('woocommerce_subscription_status_active', function($subscription){
  $user_id = method_exists($subscription,'get_user_id') ? $subscription->get_user_id() : 0;
  if (!$user_id) return;
  $user = new WP_User($user_id);
  $user->add_role('lojista');
  $rest_id = get_user_meta($user_id,'_rc_restaurant_id',true);
  if (!$rest_id){
    $rest_id = wp_insert_post([ 'post_type'=>'restaurant','post_status'=>'pending','post_title'=>'Novo Restaurante','post_author'=>$user_id ]);
    update_user_meta($user_id,'_rc_restaurant_id',$rest_id);
  } else {
    wp_update_post(['ID'=>$rest_id,'post_status'=>'pending']);
  }
});
