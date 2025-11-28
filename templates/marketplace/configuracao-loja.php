<?php
/**
 * Template Name: Marketplace - Configuracao Loja
 * Description: Placeholder for a dynamic version of templates/marketplace/configuracao-loja.html.
 */

if (!defined('ABSPATH')) {
    exit;
}

$vc_marketplace_inline = defined('VC_MARKETPLACE_INLINE') && VC_MARKETPLACE_INLINE;

require_once __DIR__ . '/static-loader.php';
require_once __DIR__ . '/helpers.php';

$restaurant      = vc_marketplace_current_restaurant();
$config_prefill  = vc_marketplace_collect_restaurant_data( $restaurant );

if (! $vc_marketplace_inline) {
    get_header();
}

vc_marketplace_render_static_template('configuracao-loja.html');

?>
<script>
  window.vcConfigPrefill = <?php echo wp_json_encode( $config_prefill ); ?>;
  (function(){
    const data = window.vcConfigPrefill || {};
    if (!data || !data.id) return;

    const logo = document.getElementById('logo-preview');
    if (logo && data.logo) logo.src = data.logo;

    const capa = document.getElementById('capa-preview');
    if (capa && data.cover) capa.src = data.cover;

    const nome = document.getElementById('nomeRestaurante');
    if (nome && data.nome) nome.value = data.nome;

    const desc = document.getElementById('descricao');
    if (desc && data.descricao) desc.value = data.descricao;

    if (Array.isArray(data.banners) && data.banners.length) {
      const group = document.querySelector('.banners-group');
      if (group) {
        group.innerHTML = '';
        data.banners.forEach(function(url){
          const img = document.createElement('img');
          img.src = url;
          img.className = 'banner-thumb';
          img.alt = 'Banner';
          group.appendChild(img);
        });
      }
    }

    const reservaToggle = document.getElementById('switchReserva');
    if (reservaToggle) {
      reservaToggle.checked = !!(data.metodos && data.metodos.reserva);
    }

    const reservaMsg = document.querySelector('.switch-row input[type="text"]');
    if (reservaMsg && data.reservation_message) {
      reservaMsg.value = data.reservation_message;
    }

    const horarios = data.horarios || {};
    Object.keys(horarios).forEach(function(slug){
      const info = horarios[slug] || {};
      const cb = document.getElementById(slug + '-checkbox');
      const abre = document.getElementById(slug + '-abre');
      const fecha = document.getElementById(slug + '-fecha');
      const enabled = !!info.enabled;
      if (cb) cb.checked = enabled;
      if (abre) {
        abre.disabled = !enabled;
        if (info.ranges && info.ranges[0] && info.ranges[0].open) {
          abre.value = info.ranges[0].open;
        } else if (!enabled) {
          abre.value = '';
        }
      }
      if (fecha) {
        fecha.disabled = !enabled;
        if (info.ranges && info.ranges[0] && info.ranges[0].close) {
          fecha.value = info.ranges[0].close;
        } else if (!enabled) {
          fecha.value = '';
        }
      }
    });

    const shipping = data.shipping || {};
    const raio = document.getElementById('kmRaio');
    if (raio && typeof shipping.radius === 'number') {
      raio.value = shipping.radius;
    }
    const taxaBase = document.getElementById('taxaBase');
    if (taxaBase && typeof shipping.base_fee === 'number') {
      taxaBase.value = shipping.base_fee;
    }

    if (shipping.mode === 'neighborhood' && Array.isArray(shipping.neighborhoods)) {
      const radioBairro = document.getElementById('frete-bairro');
      const radioRaio = document.getElementById('frete-raio');
      const bairroConfig = document.getElementById('bairroConfig');
      if (radioBairro) radioBairro.checked = true;
      if (radioRaio) radioRaio.checked = false;
      if (bairroConfig) {
        bairroConfig.style.display = 'block';
        bairroConfig.innerHTML = '';
        shipping.neighborhoods.forEach(function(item){
          const div = document.createElement('div');
          div.className = 'bairro-list-item';
          div.textContent = item.name + ' ';
          const input = document.createElement('input');
          input.type = 'number';
          input.min = '0';
          input.max = '20';
          input.step = '0.1';
          input.value = typeof item.price === 'number' ? item.price : '';
          div.appendChild(input);
          bairroConfig.appendChild(div);
        });
      }
    }
  })();
</script>
<?php

if (! $vc_marketplace_inline) {
    get_footer();
}
