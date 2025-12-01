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
  const vcConfigEndpoint = '<?php echo esc_url_raw( rest_url( 'vemcomer/v1/merchant/settings' ) ); ?>';
  const vcRestNonce = '<?php echo wp_create_nonce( 'wp_rest' ); ?>';

  document.addEventListener('DOMContentLoaded', function(){
    const heading = Array.from(document.querySelectorAll('.container h1')).find(function(node){
      return node.textContent && node.textContent.toLowerCase().includes('configurações da loja');
    });
    const container = heading ? heading.closest('.container') : null;

    if (container) {
      const saveBtn = container.querySelector('.btn-save');
      const extraHtml = `
        <section class="vc-extra-section">
          <h2>Contato e Documento</h2>
          <div class="input-row"><input type="text" id="vcCnpj" placeholder="CNPJ"></div>
          <div class="input-row"><input type="text" id="vcWhatsapp" placeholder="WhatsApp"></div>
          <div class="input-row"><input type="text" id="vcSite" placeholder="Site"></div>
        </section>
        <section class="vc-extra-section">
          <h2>Localização & Delivery</h2>
          <textarea id="vcEndereco" placeholder="Endereço completo"></textarea>
          <div class="input-row">
            <input type="text" id="vcLatitude" placeholder="Latitude" />
            <input type="text" id="vcLongitude" placeholder="Longitude" />
          </div>
          <div class="switch-row">
            <label class="switch-label">Oferece delivery?</label>
            <label class="switch">
              <input type="checkbox" id="vcDeliveryFlag" />
              <span class="slider"></span>
            </label>
          </div>
          <div class="input-row">
            <input type="text" id="vcDeliveryEta" placeholder="Tempo médio de entrega (ex: 35-50 min)">
            <input type="text" id="vcDeliveryFee" placeholder="Taxa de entrega (texto)">
          </div>
          <div class="input-row">
            <input type="text" id="vcDeliveryType" placeholder="Tipo de entrega (ex: Entrega Própria)">
          </div>
          <div class="input-row">
            <input type="number" id="vcPriceKm" step="0.01" placeholder="Preço por km (R$)">
            <input type="number" id="vcFreeAbove" step="0.01" placeholder="Frete grátis acima de (R$)">
            <input type="number" id="vcMinOrder" step="0.01" placeholder="Pedido mínimo (R$)">
          </div>
          <div class="input-row">
            <input type="text" id="vcAccessUrl" placeholder="Token de acesso (access_url)">
          </div>
        </section>
        <section class="vc-extra-section">
          <h2>Horários e Feriados</h2>
          <textarea id="vcHorarioLegado" placeholder="Horário de funcionamento (texto livre - legado)"></textarea>
          <textarea id="vcHolidays" placeholder="Feriados (YYYY-MM-DD por linha)"></textarea>
        </section>
        <section class="vc-extra-section">
          <h2>Experiência do Perfil</h2>
          <div class="input-row">
            <input type="number" id="vcOrdersCount" placeholder="Total de pedidos">
            <input type="text" id="vcPlanName" placeholder="Nome do plano">
          </div>
          <div class="input-row">
            <input type="number" id="vcPlanLimit" placeholder="Limite de itens do plano">
            <input type="number" id="vcPlanUsed" placeholder="Itens usados no plano">
          </div>
          <textarea id="vcHighlights" placeholder="Destaques (uma etiqueta por linha)"></textarea>
          <textarea id="vcFilters" placeholder="Filtros do cardápio (uma opção por linha)"></textarea>
          <textarea id="vcPayments" placeholder="Formas de pagamento (uma por linha)"></textarea>
          <textarea id="vcFacilities" placeholder="Facilidades/etiquetas (uma por linha)"></textarea>
          <textarea id="vcObservations" placeholder="Observações extras"></textarea>
          <textarea id="vcFaq" placeholder="FAQ (Pergunta | Resposta por linha)"></textarea>
        </section>
      `;
      const insertTarget = saveBtn ? saveBtn : container.lastElementChild;
      if (insertTarget) {
        insertTarget.insertAdjacentHTML('beforebegin', extraHtml);
      }
    }

    const createStatusBox = () => {
      const box = document.createElement('div');
      box.id = 'vcConfigStatus';
      box.style.margin = '12px 0 4px';
      box.style.padding = '10px 12px';
      box.style.borderRadius = '8px';
      box.style.display = 'none';
      box.style.fontWeight = '600';
      box.style.fontSize = '0.96em';
      box.style.background = '#f1f5f9';
      box.style.color = '#0f172a';
      return box;
    };

    const ensureStatusBox = () => {
      let box = document.getElementById('vcConfigStatus');
      if (!box) {
        const target = container ? container.querySelector('.btn-save') : null;
        box = createStatusBox();
        if (target && target.parentNode) {
          target.parentNode.insertBefore(box, target);
        }
      }
      return box;
    };

    const data = window.vcConfigPrefill || {};
    if (!data || !data.id) return;

    const setValue = (id, value) => {
      const el = document.getElementById(id);
      if (el && value !== undefined && value !== null && value !== '') {
        el.value = value;
      }
    };

    const logo = document.getElementById('logo-preview');
    if (logo && data.logo) logo.src = data.logo;

    const capa = document.getElementById('capa-preview');
    if (capa && data.cover) capa.src = data.cover;

    setValue('nomeRestaurante', data.nome);
    setValue('descricao', data.descricao);
    setValue('vcCnpj', data.cnpj);
    setValue('vcWhatsapp', data.whatsapp);
    setValue('vcSite', data.site);
    setValue('vcEndereco', data.endereco);
    setValue('vcLatitude', data.geo ? data.geo.lat : '');
    setValue('vcLongitude', data.geo ? data.geo.lng : '');
    setValue('vcAccessUrl', data.access_url);
    setValue('vcDeliveryEta', data.delivery_eta);
    setValue('vcDeliveryFee', data.delivery_fee);
    setValue('vcDeliveryType', data.delivery_type);
    setValue('vcPriceKm', data.shipping ? data.shipping.price_per_km : '');
    setValue('vcFreeAbove', data.shipping ? data.shipping.free_above : '');
    setValue('vcMinOrder', data.shipping ? data.shipping.min_order : '');
    setValue('vcHorarioLegado', data.horario_legado);
    setValue('vcOrdersCount', data.orders_count);
    setValue('vcPlanName', data.plan_name);
    setValue('vcPlanLimit', data.plan_limit);
    setValue('vcPlanUsed', data.plan_used);

    const destaqueBadge = document.querySelector('.badge-selo');
    if (destaqueBadge && data.destaque) {
      destaqueBadge.textContent = 'Destaque ativo';
    }

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

    const holidays = Array.isArray(data.holidays) ? data.holidays.join("\n") : '';
    setValue('vcHolidays', holidays);

    const deliveryFlag = document.getElementById('vcDeliveryFlag');
    if (deliveryFlag && data.metodos) {
      deliveryFlag.checked = !!data.metodos.delivery;
    }

    const highlights = Array.isArray(data.destaques) ? data.destaques.join("\n") : '';
    setValue('vcHighlights', highlights);

    const filters = Array.isArray(data.filters) ? data.filters.join("\n") : '';
    setValue('vcFilters', filters);

    const payments = Array.isArray(data.pagamentos) ? data.pagamentos.join("\n") : '';
    setValue('vcPayments', payments);

    const facilities = data.facilities ? data.facilities : '';
    setValue('vcFacilities', facilities);

    const observations = data.observations ? data.observations : '';
    setValue('vcObservations', observations);

    const faq = data.faq ? data.faq : '';
    setValue('vcFaq', faq);

    const saveBtn = document.querySelector('.btn-save');
    const salvarConfiguracoes = async function(ev){
      if (ev) ev.preventDefault();
      const button = ev && ev.currentTarget ? ev.currentTarget : saveBtn;
      const statusBox = ensureStatusBox();
      if (statusBox) {
        statusBox.style.display = 'block';
        statusBox.style.background = '#e0e7ff';
        statusBox.style.color = '#1e3a8a';
        statusBox.textContent = 'Enviando configurações...';
      }
      if (button) {
        button.disabled = true;
        button.textContent = 'Salvando...';
      }

      const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
      };

      const collectLines = (id) => getVal(id).split(/\n+/).map(t => t.trim()).filter(Boolean);

      const horarios = ['seg','ter','qua','qui','sex','sab','dom'].reduce((acc, slug) => {
        const enabled = document.getElementById(slug + '-checkbox')?.checked;
        const open = document.getElementById(slug + '-abre')?.value || '';
        const close = document.getElementById(slug + '-fecha')?.value || '';
        acc[slug] = { enabled: !!enabled, ranges: [ { open, close } ] };
        return acc;
      }, {});

      const shippingModeBairro = document.getElementById('frete-bairro')?.checked;
      const neighborhoods = [];
      if (shippingModeBairro) {
        document.querySelectorAll('#bairroConfig .bairro-list-item').forEach(function(item){
          const name = (item.childNodes[0] && item.childNodes[0].textContent) ? item.childNodes[0].textContent.trim() : '';
          const priceInput = item.querySelector('input[type="number"]');
          const price = priceInput && priceInput.value !== '' ? parseFloat(priceInput.value) : null;
          if (name) neighborhoods.push({ name, price });
        });
      }

      const shipping = shippingModeBairro ? {
        mode: 'neighborhood',
        neighborhoods,
        base_fee: getVal('taxaBase') || null,
      } : {
        mode: 'radius',
        radius: getVal('kmRaio') || null,
        base_fee: getVal('taxaBase') || null,
        price_per_km: getVal('vcPriceKm') || null,
      };

      shipping.free_above = getVal('vcFreeAbove') || null;
      shipping.min_order = getVal('vcMinOrder') || null;

      const banners = Array.from(document.querySelectorAll('.banners-group img')).map(img => img.src).filter(Boolean);
      const reservaMsg = document.querySelector('.switch-row input[type="text"]');

      const payload = {
        title: getVal('nomeRestaurante'),
        description: getVal('descricao'),
        cnpj: getVal('vcCnpj'),
        whatsapp: getVal('vcWhatsapp'),
        site: getVal('vcSite'),
        address: getVal('vcEndereco'),
        lat: getVal('vcLatitude'),
        lng: getVal('vcLongitude'),
        delivery: !!document.getElementById('vcDeliveryFlag')?.checked,
        delivery_eta: getVal('vcDeliveryEta'),
        delivery_fee: getVal('vcDeliveryFee'),
        delivery_type: getVal('vcDeliveryType'),
        access_url: getVal('vcAccessUrl'),
        horario_legado: getVal('vcHorarioLegado'),
        banners,
        highlights: collectLines('vcHighlights'),
        filters: collectLines('vcFilters'),
        payments: collectLines('vcPayments'),
        facilities: getVal('vcFacilities'),
        observations: getVal('vcObservations'),
        faq: getVal('vcFaq'),
        orders_count: getVal('vcOrdersCount'),
        plan_name: getVal('vcPlanName'),
        plan_limit: getVal('vcPlanLimit'),
        plan_used: getVal('vcPlanUsed'),
        reservation_enabled: !!document.getElementById('switchReserva')?.checked,
        reservation_message: reservaMsg ? reservaMsg.value : '',
        holidays: collectLines('vcHolidays'),
        shipping,
        schedule: horarios,
        logo: (document.getElementById('logo-preview')?.src) || '',
        cover: (document.getElementById('capa-preview')?.src) || '',
      };

      try {
        const res = await fetch(vcConfigEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': vcRestNonce,
          },
          body: JSON.stringify(payload),
        });

        if (!res.ok) {
          let serverMessage = '';
          try {
            const errorData = await res.json();
            serverMessage = errorData && errorData.message ? errorData.message : JSON.stringify(errorData);
          } catch (parseErr) {
            serverMessage = res.statusText || 'Erro desconhecido';
          }
          const composed = `Falha ao salvar (${res.status}): ${serverMessage || 'sem detalhes'}`;
          throw new Error(composed);
        }

        if (statusBox) {
          statusBox.style.display = 'block';
          statusBox.style.background = '#dcfce7';
          statusBox.style.color = '#166534';
          statusBox.textContent = 'Configurações salvas com sucesso!';
        }
        alert('Configurações salvas com sucesso!');
      } catch (err) {
        console.error(err);
        if (statusBox) {
          statusBox.style.display = 'block';
          statusBox.style.background = '#fee2e2';
          statusBox.style.color = '#991b1b';
          statusBox.textContent = err && err.message ? err.message : 'Não foi possível salvar as configurações.';
        }
        alert(statusBox && statusBox.textContent ? statusBox.textContent : 'Não foi possível salvar as configurações.');
      } finally {
        if (button) {
          button.disabled = false;
          button.textContent = 'Salvar Configurações';
        }
      }
    };

    if (saveBtn) {
      saveBtn.removeAttribute('onclick');
      saveBtn.addEventListener('click', salvarConfiguracoes);
    }

    window.salvar = salvarConfiguracoes;

    const suggestionBank = {
      highlights: ['Entrega Própria', 'Entrega Grátis acima de R$50', 'Vegana', 'Sem Glúten', 'Feijoada especial', 'Eventos & Reservas', 'Promoções', 'Café da manhã', 'Almoço executivo', 'Jantar ao vivo', 'Música ao vivo', 'Rodízio'],
      filters: ['Vegano', 'Sem glúten', 'Promo', 'Vegetariano', 'Low carb', 'Sem lactose', 'Keto', 'Halal', 'Kosher'],
      payments: ['Cartão', 'Pix', 'Dinheiro', 'VA/VR', 'Débito', 'Crédito', 'NFC', 'Apple Pay', 'Google Pay'],
      facilities: ['Acessibilidade', 'Wi-Fi grátis', 'Espaço eventos', 'Ar-condicionado', 'Pet friendly', 'Estacionamento', 'Playground', 'Área externa', 'Drive-thru']
    };

    const ensureChips = (textareaId, label, options) => {
      const field = document.getElementById(textareaId);
      if (!field || !options || !options.length) return;
      const wrapper = document.createElement('div');
      wrapper.className = 'vc-chip-bank';
      const title = document.createElement('div');
      title.className = 'vc-chip-bank-title';
      title.textContent = label;
      wrapper.appendChild(title);
      options.forEach(function(option){
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'vc-chip';
        btn.textContent = option;
        btn.addEventListener('click', function(){
          const lines = field.value ? field.value.split(/\n+/).map(t => t.trim()).filter(Boolean) : [];
          if (!lines.includes(option)) {
            lines.push(option);
            field.value = lines.join('\n');
          }
        });
        wrapper.appendChild(btn);
      });
      field.insertAdjacentElement('afterend', wrapper);
    };

    const chipStyle = document.createElement('style');
    chipStyle.textContent = `
      .vc-chip-bank { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
      .vc-chip-bank-title { width:100%; font-size:0.95em; font-weight:700; color:#1f2937; margin-bottom:2px; }
      .vc-chip { border:1px solid #e2e8f0; background:#fff; border-radius:18px; padding:6px 10px; cursor:pointer; font-size:0.92em; color:#111827; transition:.12s; }
      .vc-chip:hover { background:#e0f2fe; border-color:#bae6fd; }
    `;
    document.head.appendChild(chipStyle);

    ensureChips('vcHighlights', 'Sugestões de destaques', suggestionBank.highlights);
    ensureChips('vcFilters', 'Sugestões de filtros do cardápio', suggestionBank.filters);
    ensureChips('vcPayments', 'Sugestões de formas de pagamento', suggestionBank.payments);
    ensureChips('vcFacilities', 'Sugestões de facilidades', suggestionBank.facilities);
  });
</script>
<?php

if (! $vc_marketplace_inline) {
    get_footer();
}
