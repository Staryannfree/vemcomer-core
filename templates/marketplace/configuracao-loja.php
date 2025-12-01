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

// Carrega Select2 para busca nas categorias
wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', [], '4.1.0');
wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0', true);

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
      const firstSection = container.querySelector('section');
      const extraHtml = `
        <section class="vc-extra-section">
          <h2>Categorias do Perfil</h2>
          <div class="input-row">
            <select id="vcCuisine1" style="flex:1;" class="vc-cuisine-select">
              <option value="">Selecione a primeira categoria</option>
            </select>
          </div>
          <div class="input-row">
            <select id="vcCuisine2" style="flex:1;" class="vc-cuisine-select">
              <option value="">Selecione a segunda categoria (opcional)</option>
            </select>
          </div>
          <div class="input-row">
            <select id="vcCuisine3" style="flex:1;" class="vc-cuisine-select">
              <option value="">Selecione a terceira categoria (opcional)</option>
            </select>
          </div>
          <p style="font-size: 0.82rem; color: #6b7672; margin-top: 4px;">
            Escolha até 3 categorias que melhor descrevem seu estabelecimento. A primeira será a categoria principal.
          </p>
        </section>
        <section class="vc-extra-section">
          <h2>Contato e Documento</h2>
          <div class="input-row">
            <input type="text" id="vcCnpj" placeholder="CNPJ" readonly style="background:#f5f5f5;cursor:not-allowed;">
            <small style="font-size:0.85em;color:#6b7672;align-self:center;">CNPJ não pode ser alterado</small>
          </div>
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
          
          <div class="switch-row" style="margin-bottom:20px;">
            <label class="switch-label">Oferece delivery?</label>
            <label class="switch">
              <input type="checkbox" id="vcDeliveryFlag" />
              <span class="slider"></span>
            </label>
          </div>
          
          <div style="margin-top:20px;margin-bottom:16px;">
            <label class="vc-field-label">Tempo de entrega</label>
            <div style="position:relative;max-width:300px;">
              <input type="number" id="vcDeliveryEta" placeholder="35" min="0" step="1" style="width:100%;padding-right:50px;">
              <span style="position:absolute;right:15px;top:50%;transform:translateY(-50%);color:#6b7672;font-weight:600;pointer-events:none;">min</span>
            </div>
          </div>
          
          <div style="margin-bottom:16px;">
            <label class="vc-field-label">Taxa padrão de entrega</label>
            <div style="position:relative;max-width:300px;">
              <span style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6b7672;font-weight:600;z-index:1;pointer-events:none;">R$</span>
              <input type="number" id="vcDeliveryFee" placeholder="5.00" min="0" step="0.01" style="width:100%;padding-left:35px;">
            </div>
          </div>
          
          <div style="margin-bottom:16px;">
            <label class="vc-field-label">Raio de aplicação da taxa padrão</label>
            <div class="input-row" style="max-width:300px;">
              <input type="number" id="vcDeliveryRadius" step="0.1" min="0" placeholder="Ex: 5.0" style="flex:1;">
              <span style="align-self:center;color:#6b7672;font-weight:600;margin-left:8px;">km</span>
            </div>
          </div>
          
          <div style="margin-bottom:16px;">
            <label class="vc-field-label">Valor adicional por KM</label>
            <div style="position:relative;max-width:300px;">
              <span style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6b7672;font-weight:600;z-index:1;pointer-events:none;">R$</span>
              <input type="number" id="vcPriceKm" step="0.01" min="0" placeholder="2.50" style="width:100%;padding-left:35px;">
            </div>
          </div>
          
          <div class="switch-row" style="margin-top:20px;margin-bottom:12px;">
            <label class="switch-label">Seu restaurante oferece frete grátis?</label>
            <label class="switch">
              <input type="checkbox" id="vcFreeShippingToggle" />
              <span class="slider"></span>
            </label>
          </div>
          <div id="vcFreeShippingContainer" style="display:none;margin-bottom:16px;">
            <label class="vc-field-label">Frete grátis acima de</label>
            <div style="position:relative;max-width:300px;">
              <span style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6b7672;font-weight:600;z-index:1;pointer-events:none;">R$</span>
              <input type="number" id="vcFreeAbove" step="0.01" min="0" placeholder="50.00 (digite o valor)" style="width:100%;padding-left:35px;">
            </div>
          </div>
          
          <div class="switch-row" style="margin-top:20px;margin-bottom:12px;">
            <label class="switch-label">Seu restaurante tem restrição de pedido mínimo?</label>
            <label class="switch">
              <input type="checkbox" id="vcMinOrderToggle" />
              <span class="slider"></span>
            </label>
          </div>
          <div id="vcMinOrderContainer" style="display:none;">
            <label class="vc-field-label">Pedido mínimo</label>
            <div style="position:relative;max-width:300px;">
              <span style="position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#6b7672;font-weight:600;z-index:1;pointer-events:none;">R$</span>
              <input type="number" id="vcMinOrder" step="0.01" min="0" placeholder="30.00 (digite o valor)" style="width:100%;padding-left:35px;">
            </div>
          </div>
        </section>
        <section class="vc-extra-section">
          <h2>Horários e Feriados</h2>
          
          <!-- Horário de Funcionamento -->
          <div style="margin-bottom:30px;">
            <h3 style="font-size:1.05em;font-weight:700;color:var(--primary);margin-bottom:8px;">Horário de funcionamento</h3>
            <p style="font-size:0.9em;color:#6b7672;margin-bottom:16px;">Defina em quais dias e horários sua loja aceita pedidos. Você pode deixar algum dia fechado se quiser.</p>
            
            <!-- Botões de atalho -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
              <button type="button" class="vc-shortcut-btn" data-shortcut="comercial">⏱ Usar horário comercial (seg–sex, 09:00–18:00)</button>
              <button type="button" class="vc-shortcut-btn" data-shortcut="jantar">🌙 Só jantar (seg–dom, 18:00–23:00)</button>
              <button type="button" class="vc-shortcut-btn" data-shortcut="24h">🕛 24 horas (seg–dom, 00:00–23:59)</button>
            </div>
            
            <!-- Checkbox mesmo horário -->
            <div style="margin-bottom:12px;">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" id="vcSameHoursAllDays" style="width:18px;height:18px;cursor:pointer;">
                <span style="font-weight:600;color:var(--primary);">Mesmo horário todos os dias</span>
              </label>
            </div>
            
            <!-- Tabela de horários -->
            <div style="overflow-x:auto;margin-bottom:16px;">
              <table class="vc-hours-table" style="width:100%;border-collapse:collapse;">
                <thead>
                  <tr style="background:var(--primary-light);">
                    <th style="padding:10px;text-align:left;font-weight:700;color:var(--primary);font-size:0.9em;">Dia</th>
                    <th style="padding:10px;text-align:center;font-weight:700;color:var(--primary);font-size:0.9em;">Abre às</th>
                    <th style="padding:10px;text-align:center;font-weight:700;color:var(--primary);font-size:0.9em;">Fecha às</th>
                    <th style="padding:10px;text-align:center;font-weight:700;color:var(--primary);font-size:0.9em;">Fechado</th>
                    <th style="padding:10px;text-align:center;font-weight:700;color:var(--primary);font-size:0.9em;">Ações</th>
                  </tr>
                </thead>
                <tbody id="vcHoursTableBody">
                  <!-- Linhas serão inseridas via JavaScript -->
                </tbody>
              </table>
            </div>
            
            <!-- Configurações avançadas (colapsável) -->
            <details style="margin-top:16px;">
              <summary style="cursor:pointer;font-weight:600;color:var(--primary);padding:8px;background:var(--primary-light);border-radius:8px;">Configurações avançadas ▾</summary>
              <div style="padding:12px;background:#f9f9f9;border-radius:8px;margin-top:8px;">
                <textarea id="vcHorarioLegado" placeholder="Horário de funcionamento (texto livre - legado)" style="width:100%;min-height:80px;margin-top:8px;"></textarea>
                <p style="font-size:0.85em;color:#6b7672;margin-top:4px;">Use apenas se precisar de regras especiais que não cabem na tabela acima.</p>
              </div>
            </details>
          </div>
          
          <!-- Feriados e Dias Fechados -->
          <div style="margin-top:30px;padding-top:20px;border-top:2px solid var(--primary-light);">
            <h3 style="font-size:1.05em;font-weight:700;color:var(--primary);margin-bottom:8px;">Feriados e dias fechados</h3>
            <p style="font-size:0.9em;color:#6b7672;margin-bottom:16px;">Selecione os dias em que sua loja ficará fechada, além dos domingos (se for o caso).</p>
            
            <!-- Toggle feriados nacionais -->
            <div style="margin-bottom:16px;">
              <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                <input type="checkbox" id="vcAutoCloseHolidays" style="width:18px;height:18px;cursor:pointer;">
                <span style="font-weight:600;color:var(--primary);">Fechar minha loja nos feriados nacionais automaticamente</span>
              </label>
            </div>
            
            <!-- Adicionar feriado personalizado -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;align-items:flex-end;">
              <div style="flex:1;min-width:200px;">
                <label style="display:block;font-weight:600;color:var(--primary);font-size:0.9em;margin-bottom:4px;">Adicionar dia fechado</label>
                <input type="date" id="vcHolidayDate" style="width:100%;padding:10px;border-radius:8px;border:1.2px solid #cdf9e0;">
              </div>
              <div style="flex:1;min-width:200px;">
                <label style="display:block;font-weight:600;color:var(--primary);font-size:0.9em;margin-bottom:4px;">Descrição (opcional)</label>
                <input type="text" id="vcHolidayDesc" placeholder="Ex: Aniversário da cidade" style="width:100%;padding:10px;border-radius:8px;border:1.2px solid #cdf9e0;">
              </div>
              <button type="button" id="vcAddHolidayBtn" class="vc-btn vc-btn--primary" style="padding:10px 20px;white-space:nowrap;">Adicionar</button>
            </div>
            
            <!-- Lista de feriados adicionados -->
            <div id="vcHolidaysList" style="display:flex;flex-wrap:wrap;gap:8px;min-height:40px;">
              <!-- Chips serão inseridos via JavaScript -->
            </div>
          </div>
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
      if (firstSection) {
        firstSection.insertAdjacentHTML('afterend', extraHtml);
      } else if (saveBtn) {
        saveBtn.insertAdjacentHTML('beforebegin', extraHtml);
      } else {
        container.insertAdjacentHTML('beforeend', extraHtml);
      }
    }

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
    // Tempo de entrega: remove "min" se existir, mantém só o número
    const etaValue = data.delivery_eta ? String(data.delivery_eta).replace(/\s*min\s*/gi, '').trim() : '';
    setValue('vcDeliveryEta', etaValue);
    
    // Taxa de entrega: remove "R$" se existir, mantém só o número
    const feeValue = data.delivery_fee ? String(data.delivery_fee).replace(/R\$\s*/gi, '').trim() : '';
    setValue('vcDeliveryFee', feeValue);
    
    setValue('vcDeliveryRadius', data.shipping ? data.shipping.radius : '');
    setValue('vcPriceKm', data.shipping ? data.shipping.price_per_km : '');
    
    // Checkbox e campo condicional para frete grátis
    const freeShippingToggle = document.getElementById('vcFreeShippingToggle');
    const freeShippingContainer = document.getElementById('vcFreeShippingContainer');
    const freeAboveValue = data.shipping ? data.shipping.free_above : '';
    if (freeShippingToggle && freeShippingContainer) {
      const hasFreeShipping = freeAboveValue && parseFloat(freeAboveValue) > 0;
      freeShippingToggle.checked = hasFreeShipping;
      freeShippingContainer.style.display = hasFreeShipping ? 'block' : 'none';
      if (hasFreeShipping) {
        setValue('vcFreeAbove', freeAboveValue);
      }
      freeShippingToggle.addEventListener('change', function() {
        freeShippingContainer.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
          document.getElementById('vcFreeAbove').value = '';
        }
      });
    }
    
    // Checkbox e campo condicional para pedido mínimo
    const minOrderToggle = document.getElementById('vcMinOrderToggle');
    const minOrderContainer = document.getElementById('vcMinOrderContainer');
    const minOrderValue = data.shipping ? data.shipping.min_order : '';
    if (minOrderToggle && minOrderContainer) {
      const hasMinOrder = minOrderValue && parseFloat(minOrderValue) > 0;
      minOrderToggle.checked = hasMinOrder;
      minOrderContainer.style.display = hasMinOrder ? 'block' : 'none';
      if (hasMinOrder) {
        setValue('vcMinOrder', minOrderValue);
      }
      minOrderToggle.addEventListener('change', function() {
        minOrderContainer.style.display = this.checked ? 'block' : 'none';
        if (!this.checked) {
          document.getElementById('vcMinOrder').value = '';
        }
      });
    }
    setValue('vcHorarioLegado', data.horario_legado);
    
    // Sistema de Horários e Feriados
    function initHoursAndHolidays(data) {
      const days = [
        { key: 'seg', name: 'Segunda', short: 'Seg' },
        { key: 'ter', name: 'Terça', short: 'Ter' },
        { key: 'qua', name: 'Quarta', short: 'Qua' },
        { key: 'qui', name: 'Quinta', short: 'Qui' },
        { key: 'sex', name: 'Sexta', short: 'Sex' },
        { key: 'sab', name: 'Sábado', short: 'Sáb' },
        { key: 'dom', name: 'Domingo', short: 'Dom' }
      ];
      
      const tableBody = document.getElementById('vcHoursTableBody');
      if (!tableBody) return;
      
      // Parse horários existentes
      const existingHours = data.horarios || {};
      
      // Renderizar tabela
      tableBody.innerHTML = '';
      days.forEach(function(day) {
        const dayData = existingHours[day.key] || { enabled: false, ranges: [{ open: '08:00', close: '18:00' }] };
        const row = document.createElement('tr');
        row.innerHTML = `
          <td style="padding:10px;font-weight:600;color:var(--primary);">${day.name}</td>
          <td style="padding:10px;text-align:center;">
            <input type="time" class="vc-hour-open" data-day="${day.key}" value="${dayData.ranges && dayData.ranges[0] ? dayData.ranges[0].open : '08:00'}" ${!dayData.enabled ? 'disabled' : ''} style="width:100%;max-width:120px;padding:6px;border-radius:6px;border:1.2px solid #cdf9e0;">
          </td>
          <td style="padding:10px;text-align:center;">
            <input type="time" class="vc-hour-close" data-day="${day.key}" value="${dayData.ranges && dayData.ranges[0] ? dayData.ranges[0].close : '18:00'}" ${!dayData.enabled ? 'disabled' : ''} style="width:100%;max-width:120px;padding:6px;border-radius:6px;border:1.2px solid #cdf9e0;">
          </td>
          <td style="padding:10px;text-align:center;">
            <input type="checkbox" class="vc-day-closed" data-day="${day.key}" ${!dayData.enabled ? 'checked' : ''} style="width:18px;height:18px;cursor:pointer;">
          </td>
          <td style="padding:10px;text-align:center;">
            <button type="button" class="vc-copy-day-btn" data-day="${day.key}" style="background:var(--primary);color:#fff;border:none;padding:4px 10px;border-radius:6px;font-size:0.85em;cursor:pointer;font-weight:600;">Copiar</button>
          </td>
        `;
        tableBody.appendChild(row);
      });
      
      // Event listeners para checkboxes "fechado"
      document.querySelectorAll('.vc-day-closed').forEach(function(cb) {
        cb.addEventListener('change', function() {
          const day = this.dataset.day;
          const openInput = document.querySelector(`.vc-hour-open[data-day="${day}"]`);
          const closeInput = document.querySelector(`.vc-hour-close[data-day="${day}"]`);
          openInput.disabled = this.checked;
          closeInput.disabled = this.checked;
          if (this.checked) {
            openInput.value = '';
            closeInput.value = '';
          }
        });
      });
      
      // Event listeners para botões "Copiar"
      document.querySelectorAll('.vc-copy-day-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          const sourceDay = this.dataset.day;
          const sourceOpen = document.querySelector(`.vc-hour-open[data-day="${sourceDay}"]`).value;
          const sourceClose = document.querySelector(`.vc-hour-close[data-day="${sourceDay}"]`).value;
          const sourceClosed = document.querySelector(`.vc-day-closed[data-day="${sourceDay}"]`).checked;
          
          days.forEach(function(day) {
            if (day.key === sourceDay) return;
            const openInput = document.querySelector(`.vc-hour-open[data-day="${day.key}"]`);
            const closeInput = document.querySelector(`.vc-hour-close[data-day="${day.key}"]`);
            const closedCheck = document.querySelector(`.vc-day-closed[data-day="${day.key}"]`);
            
            closedCheck.checked = sourceClosed;
            if (!sourceClosed) {
              openInput.value = sourceOpen;
              closeInput.value = sourceClose;
            }
            openInput.disabled = sourceClosed;
            closeInput.disabled = sourceClosed;
          });
        });
      });
      
      // Checkbox "Mesmo horário todos os dias"
      const sameHoursCheck = document.getElementById('vcSameHoursAllDays');
      if (sameHoursCheck) {
        sameHoursCheck.addEventListener('change', function() {
          if (this.checked) {
            const firstOpen = document.querySelector('.vc-hour-open').value;
            const firstClose = document.querySelector('.vc-hour-close').value;
            const firstClosed = document.querySelector('.vc-day-closed').checked;
            
            days.forEach(function(day) {
              const openInput = document.querySelector(`.vc-hour-open[data-day="${day.key}"]`);
              const closeInput = document.querySelector(`.vc-hour-close[data-day="${day.key}"]`);
              const closedCheck = document.querySelector(`.vc-day-closed[data-day="${day.key}"]`);
              
              closedCheck.checked = firstClosed;
              if (!firstClosed) {
                openInput.value = firstOpen;
                closeInput.value = firstClose;
              }
              openInput.disabled = firstClosed;
              closeInput.disabled = firstClosed;
            });
          }
        });
      }
      
      // Botões de atalho
      document.querySelectorAll('.vc-shortcut-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
          const shortcut = this.dataset.shortcut;
          let openTime = '08:00', closeTime = '18:00', daysToApply = days;
          
          if (shortcut === 'comercial') {
            openTime = '09:00';
            closeTime = '18:00';
            daysToApply = days.filter(d => ['seg','ter','qua','qui','sex'].includes(d.key));
          } else if (shortcut === 'jantar') {
            openTime = '18:00';
            closeTime = '23:00';
          } else if (shortcut === '24h') {
            openTime = '00:00';
            closeTime = '23:59';
          }
          
          daysToApply.forEach(function(day) {
            const openInput = document.querySelector(`.vc-hour-open[data-day="${day.key}"]`);
            const closeInput = document.querySelector(`.vc-hour-close[data-day="${day.key}"]`);
            const closedCheck = document.querySelector(`.vc-day-closed[data-day="${day.key}"]`);
            
            closedCheck.checked = false;
            openInput.value = openTime;
            closeInput.value = closeTime;
            openInput.disabled = false;
            closeInput.disabled = false;
          });
        });
      });
      
      // Sistema de Feriados
      const holidaysList = document.getElementById('vcHolidaysList');
      const addHolidayBtn = document.getElementById('vcAddHolidayBtn');
      const holidayDateInput = document.getElementById('vcHolidayDate');
      const holidayDescInput = document.getElementById('vcHolidayDesc');
      let holidays = [];
      
      // Parse feriados existentes
      if (data.holidays) {
        let holidaysRaw = [];
        if (typeof data.holidays === 'string') {
          holidaysRaw = data.holidays.split('\n').filter(l => l.trim());
        } else if (Array.isArray(data.holidays)) {
          holidaysRaw = data.holidays;
        }
        
        holidaysRaw.forEach(function(line) {
          const trimmed = String(line).trim();
          if (trimmed) {
            const parts = trimmed.split('|');
            const datePart = parts[0].trim();
            if (datePart.match(/^\d{4}-\d{2}-\d{2}$/)) {
              holidays.push({
                date: datePart,
                desc: parts[1] ? parts[1].trim() : ''
              });
            }
          }
        });
      }
      
      // Parse auto_close_holidays
      const autoCloseCheck = document.getElementById('vcAutoCloseHolidays');
      if (autoCloseCheck && data.auto_close_holidays !== undefined) {
        autoCloseCheck.checked = !!data.auto_close_holidays;
      }
      
      function renderHolidays() {
        if (!holidaysList) return;
        holidaysList.innerHTML = '';
        if (holidays.length === 0) {
          holidaysList.innerHTML = '<p style="color:#6b7672;font-size:0.9em;">Nenhum dia fechado adicionado</p>';
          return;
        }
        
        holidays.forEach(function(holiday, index) {
          const chip = document.createElement('div');
          chip.className = 'vc-holiday-chip';
          const dateObj = new Date(holiday.date + 'T00:00:00');
          const formattedDate = dateObj.toLocaleDateString('pt-BR');
          chip.innerHTML = `
            <span>${formattedDate}</span>
            ${holiday.desc ? '<span style="opacity:0.8;">(' + holiday.desc + ')</span>' : ''}
            <button type="button" class="vc-holiday-chip-remove" data-index="${index}">×</button>
          `;
          holidaysList.appendChild(chip);
        });
        
        // Event listeners para remover
        document.querySelectorAll('.vc-holiday-chip-remove').forEach(function(btn) {
          btn.addEventListener('click', function() {
            const index = parseInt(this.dataset.index, 10);
            holidays.splice(index, 1);
            renderHolidays();
          });
        });
      }
      
      renderHolidays();
      
      if (addHolidayBtn && holidayDateInput) {
        addHolidayBtn.addEventListener('click', function() {
          const date = holidayDateInput.value;
          const desc = holidayDescInput ? holidayDescInput.value : '';
          
          if (!date) {
            alert('Por favor, selecione uma data.');
            return;
          }
          
          if (holidays.some(h => h.date === date)) {
            alert('Esta data já foi adicionada.');
            return;
          }
          
          holidays.push({ date: date, desc: desc });
          renderHolidays();
          
          holidayDateInput.value = '';
          if (holidayDescInput) holidayDescInput.value = '';
        });
      }
      
      // Expor funções para o salvamento
      window.vcGetHours = function() {
        const hours = {};
        days.forEach(function(day) {
          const closedCheck = document.querySelector(`.vc-day-closed[data-day="${day.key}"]`);
          const openInput = document.querySelector(`.vc-hour-open[data-day="${day.key}"]`);
          const closeInput = document.querySelector(`.vc-hour-close[data-day="${day.key}"]`);
          
          if (closedCheck && !closedCheck.checked && openInput && closeInput && openInput.value && closeInput.value) {
            hours[day.key] = {
              enabled: true,
              ranges: [{ open: openInput.value, close: closeInput.value }]
            };
          } else {
            hours[day.key] = { enabled: false, ranges: [] };
          }
        });
        return hours;
      };
      
      window.vcGetHolidays = function() {
        return holidays.map(function(h) {
          return h.desc ? h.date + ' | ' + h.desc : h.date;
        }).join('\n');
      };
      
      window.vcGetAutoCloseHolidays = function() {
        return autoCloseCheck ? autoCloseCheck.checked : false;
      };
    }
    
    // Inicializar sistema de horários e feriados
    initHoursAndHolidays(data);
    setValue('vcOrdersCount', data.orders_count);
    setValue('vcPlanName', data.plan_name);
    setValue('vcPlanLimit', data.plan_limit);
    setValue('vcPlanUsed', data.plan_used);

    // Preencher categorias nos 3 selects com busca
    if (Array.isArray(data.cuisine_terms) && data.cuisine_terms.length) {
      const select1 = document.getElementById('vcCuisine1');
      const select2 = document.getElementById('vcCuisine2');
      const select3 = document.getElementById('vcCuisine3');
      
      // Monta lista de todas as categorias
      const allTerms = data.cuisine_terms;
      
      // Primeira categoria = principal
      const primaryId = data.primary_cuisine || null;
      const secondaryIds = Array.isArray(data.secondary_cuisines) ? data.secondary_cuisines : [];
      
      // Preenche os 3 selects com todas as opções
      [select1, select2, select3].forEach(function(select, index) {
        if (!select) return;
        
        // Limpa opções existentes (exceto a primeira que é placeholder)
        while (select.options.length > 1) {
          select.remove(1);
        }
        
        // Adiciona todas as categorias
        allTerms.forEach(function(term) {
          const opt = document.createElement('option');
          opt.value = term.id;
          opt.textContent = term.name;
          select.appendChild(opt);
        });
        
        // Seleciona o valor apropriado
        if (index === 0 && primaryId) {
          select.value = primaryId;
        } else if (index === 1 && secondaryIds[0]) {
          select.value = secondaryIds[0];
        } else if (index === 2 && secondaryIds[1]) {
          select.value = secondaryIds[1];
        }
      });
      
      // Inicializa Select2 para busca (aguarda carregamento se necessário)
      function initSelect2() {
        if (typeof jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
          jQuery('.vc-cuisine-select').select2({
            placeholder: function() {
              return jQuery(this).find('option:first').text();
            },
            allowClear: true,
            width: '100%',
            language: {
              noResults: function() {
                return 'Nenhuma categoria encontrada';
              },
              searching: function() {
                return 'Buscando...';
              }
            }
          });
        } else {
          // Se Select2 ainda não carregou, tenta novamente após um delay
          setTimeout(initSelect2, 100);
        }
      }
      
      // Aguarda um pouco para garantir que Select2 foi carregado
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
          setTimeout(initSelect2, 200);
        });
      } else {
        setTimeout(initSelect2, 200);
      }
    }

    const destaqueBadge = document.querySelector('.badge-selo');
    if (destaqueBadge && data.destaque) {
      destaqueBadge.textContent = 'Destaque ativo';
    }

    // Galeria da Loja (até 4 imagens)
    const galeriaGrid = document.getElementById('galeria-grid');
    const galeriaUpload = document.getElementById('galeria-upload');
    const galeriaAddBtn = document.getElementById('galeria-add-btn');
    let galeriaImages = Array.isArray(data.banners) ? data.banners.slice(0, 4) : [];
    
    // Expõe galeriaImages globalmente para o salvamento
    window.galeriaImages = galeriaImages;
    
    function renderGaleria() {
      if (!galeriaGrid) return;
      galeriaGrid.innerHTML = '';
      
      galeriaImages.forEach(function(url, index){
        const item = document.createElement('div');
        item.className = 'galeria-item';
        item.title = 'Clique para ver em tamanho maior';
        
        // Permite clicar na miniatura para abrir lightbox
        item.onclick = function(e){
          if (e.target.tagName !== 'BUTTON') {
            openGaleriaLightbox(index);
          }
        };
        
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'Imagem da galeria ' + (index + 1);
        img.loading = 'lazy';
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'galeria-item-remove';
        removeBtn.innerHTML = '×';
        removeBtn.title = 'Remover imagem';
        removeBtn.onclick = function(e){
          e.stopPropagation(); // Evita abrir a imagem ao clicar no X
          galeriaImages.splice(index, 1);
          window.galeriaImages = galeriaImages; // Atualiza global
          renderGaleria();
          updateGaleriaAddBtn();
        };
        item.appendChild(img);
        item.appendChild(removeBtn);
        galeriaGrid.appendChild(item);
      });
      
      updateGaleriaAddBtn();
    }
    
    function updateGaleriaAddBtn() {
      if (galeriaAddBtn) {
        galeriaAddBtn.disabled = galeriaImages.length >= 4;
        if (galeriaImages.length >= 4) {
          galeriaAddBtn.textContent = 'Máximo de 4 imagens atingido';
        } else {
          galeriaAddBtn.innerHTML = '<span style="font-size:1.5em;line-height:1;">+</span> Adicionar Imagem';
        }
      }
    }
    
    if (galeriaAddBtn && galeriaUpload) {
      galeriaAddBtn.onclick = function(){
        if (galeriaImages.length < 4) {
          galeriaUpload.click();
        }
      };
      
      galeriaUpload.addEventListener('change', function(e){
        const files = Array.from(e.target.files || []);
        if (files.length === 0) return;
        
        files.forEach(function(file){
          if (galeriaImages.length >= 4) return;
          if (!file.type.startsWith('image/')) {
            alert('Por favor, selecione apenas imagens.');
            return;
          }
          
          const reader = new FileReader();
          reader.onload = function(event){
            const dataUrl = event.target.result;
            galeriaImages.push(dataUrl);
            window.galeriaImages = galeriaImages; // Atualiza global
            renderGaleria();
          };
          reader.readAsDataURL(file);
        });
        
        e.target.value = '';
      });
    }
    
    // Lightbox para visualizar imagens da galeria
    let lightboxCurrentIndex = 0;
    let lightboxKeyHandler = null;
    
    function openGaleriaLightbox(index) {
      if (!galeriaImages || galeriaImages.length === 0) return;
      
      lightboxCurrentIndex = Math.max(0, Math.min(index, galeriaImages.length - 1));
      let lightbox = document.getElementById('vc-galeria-lightbox');
      
      if (!lightbox) {
        lightbox = document.createElement('div');
        lightbox.id = 'vc-galeria-lightbox';
        lightbox.style.cssText = 'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;cursor:pointer;';
        lightbox.innerHTML = `
          <div style="position:relative;max-width:90vw;max-height:90vh;text-align:center;">
            <img id="vc-lightbox-img" style="max-width:100%;max-height:90vh;object-fit:contain;border-radius:8px;" />
            <button id="vc-lightbox-close" style="position:absolute;top:-40px;right:0;background:#fff;border:none;width:36px;height:36px;border-radius:50%;font-size:24px;cursor:pointer;color:#333;">×</button>
            <button id="vc-lightbox-prev" style="position:absolute;left:-50px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;color:#333;">‹</button>
            <button id="vc-lightbox-next" style="position:absolute;right:-50px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.9);border:none;width:40px;height:40px;border-radius:50%;font-size:20px;cursor:pointer;color:#333;">›</button>
            <div style="position:absolute;bottom:-30px;left:50%;transform:translateX(-50%);color:#fff;font-size:14px;" id="vc-lightbox-counter"></div>
          </div>
        `;
        document.body.appendChild(lightbox);
        
        const img = lightbox.querySelector('#vc-lightbox-img');
        const close = lightbox.querySelector('#vc-lightbox-close');
        const prev = lightbox.querySelector('#vc-lightbox-prev');
        const next = lightbox.querySelector('#vc-lightbox-next');
        const counter = lightbox.querySelector('#vc-lightbox-counter');
        
        function updateLightbox() {
          if (galeriaImages[lightboxCurrentIndex]) {
            img.src = galeriaImages[lightboxCurrentIndex];
            counter.textContent = (lightboxCurrentIndex + 1) + ' / ' + galeriaImages.length;
            prev.style.display = lightboxCurrentIndex === 0 ? 'none' : 'block';
            next.style.display = lightboxCurrentIndex === galeriaImages.length - 1 ? 'none' : 'block';
          }
        }
        
        close.onclick = function(e) {
          e.stopPropagation();
          lightbox.style.display = 'none';
          if (lightboxKeyHandler) {
            document.removeEventListener('keydown', lightboxKeyHandler);
            lightboxKeyHandler = null;
          }
        };
        
        prev.onclick = function(e) {
          e.stopPropagation();
          if (lightboxCurrentIndex > 0) {
            lightboxCurrentIndex--;
            updateLightbox();
          }
        };
        
        next.onclick = function(e) {
          e.stopPropagation();
          if (lightboxCurrentIndex < galeriaImages.length - 1) {
            lightboxCurrentIndex++;
            updateLightbox();
          }
        };
        
        lightbox.onclick = function(e) {
          if (e.target === lightbox || e.target === img) {
            lightbox.style.display = 'none';
            if (lightboxKeyHandler) {
              document.removeEventListener('keydown', lightboxKeyHandler);
              lightboxKeyHandler = null;
            }
          }
        };
        
        lightboxKeyHandler = function(e) {
          if (lightbox.style.display === 'none') return;
          if (e.key === 'Escape') {
            lightbox.style.display = 'none';
            document.removeEventListener('keydown', lightboxKeyHandler);
            lightboxKeyHandler = null;
          } else if (e.key === 'ArrowLeft' && lightboxCurrentIndex > 0) {
            lightboxCurrentIndex--;
            updateLightbox();
          } else if (e.key === 'ArrowRight' && lightboxCurrentIndex < galeriaImages.length - 1) {
            lightboxCurrentIndex++;
            updateLightbox();
          }
        };
        
        document.addEventListener('keydown', lightboxKeyHandler);
        window.updateLightbox = updateLightbox;
      }
      
      // Atualiza a imagem exibida
      const img = lightbox.querySelector('#vc-lightbox-img');
      const counter = lightbox.querySelector('#vc-lightbox-counter');
      const prev = lightbox.querySelector('#vc-lightbox-prev');
      const next = lightbox.querySelector('#vc-lightbox-next');
      
      if (galeriaImages[lightboxCurrentIndex]) {
        img.src = galeriaImages[lightboxCurrentIndex];
        counter.textContent = (lightboxCurrentIndex + 1) + ' / ' + galeriaImages.length;
        prev.style.display = lightboxCurrentIndex === 0 ? 'none' : 'block';
        next.style.display = lightboxCurrentIndex === galeriaImages.length - 1 ? 'none' : 'block';
      }
      
      lightbox.style.display = 'flex';
    }
    
    window.openGaleriaLightbox = openGaleriaLightbox;
    
    renderGaleria();
    
    // Mantém compatibilidade com banners antigos (se existir)
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
      if (button) {
        button.disabled = true;
        button.textContent = 'Salvando...';
      }

      const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? el.value.trim() : '';
      };

      const collectLines = (id) => getVal(id).split(/\n+/).map(t => t.trim()).filter(Boolean);

      // Usa a nova função de horários se disponível, senão usa o método antigo
      const horarios = window.vcGetHours ? window.vcGetHours() : (function() {
        return ['seg','ter','qua','qui','sex','sab','dom'].reduce((acc, slug) => {
          const enabled = document.getElementById(slug + '-checkbox')?.checked;
          const open = document.getElementById(slug + '-abre')?.value || '';
          const close = document.getElementById(slug + '-fecha')?.value || '';
          acc[slug] = { enabled: !!enabled, ranges: [ { open, close } ] };
          return acc;
        }, {});
      })();

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
        radius: getVal('vcDeliveryRadius') || getVal('kmRaio') || null,
        base_fee: getVal('taxaBase') || null,
        price_per_km: getVal('vcPriceKm') || null,
      };

      // Frete grátis e pedido mínimo só se os checkboxes estiverem marcados
      const freeShippingChecked = document.getElementById('vcFreeShippingToggle')?.checked;
      const minOrderChecked = document.getElementById('vcMinOrderToggle')?.checked;
      
      shipping.free_above = freeShippingChecked ? (getVal('vcFreeAbove') || null) : null;
      shipping.min_order = minOrderChecked ? (getVal('vcMinOrder') || null) : null;

      // Galeria da Loja (até 4 imagens) - prioriza a galeria nova
      const galeriaUrls = (window.galeriaImages && Array.isArray(window.galeriaImages)) ? window.galeriaImages.slice(0, 4) : [];
      
      // Mantém compatibilidade com banners antigos (se não houver galeria nova)
      const banners = Array.from(document.querySelectorAll('.banners-group img')).map(img => img.src).filter(Boolean);
      // Prioriza galeria nova, depois banners antigos
      const finalBanners = galeriaUrls.length > 0 ? galeriaUrls : banners;
      const reservaMsg = document.querySelector('.switch-row input[type="text"]');

      const payload = {
        title: getVal('nomeRestaurante'),
        description: getVal('descricao'),
        // CNPJ não é enviado (campo readonly, não pode ser alterado)
        whatsapp: getVal('vcWhatsapp'),
        site: getVal('vcSite'),
        address: getVal('vcEndereco'),
        lat: getVal('vcLatitude'),
        lng: getVal('vcLongitude'),
        delivery: !!document.getElementById('vcDeliveryFlag')?.checked,
        delivery_eta: (function(){
          const val = getVal('vcDeliveryEta');
          return val ? val + ' min' : '';
        })(),
        delivery_fee: (function(){
          const val = getVal('vcDeliveryFee');
          if (!val) return '';
          const num = parseFloat(val);
          if (isNaN(num)) return '';
          return 'R$ ' + num.toFixed(2).replace('.', ',');
        })(),
        horario_legado: getVal('vcHorarioLegado'),
        banners: finalBanners,
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
        holidays: window.vcGetHolidays ? window.vcGetHolidays() : collectLines('vcHolidays'),
        auto_close_holidays: window.vcGetAutoCloseHolidays ? window.vcGetAutoCloseHolidays() : false,
        primary_cuisine: (function(){
          const el = document.getElementById('vcCuisine1');
          return el && el.value ? parseInt(el.value, 10) : null;
        })(),
        secondary_cuisines: (function(){
          const el2 = document.getElementById('vcCuisine2');
          const el3 = document.getElementById('vcCuisine3');
          const secondary = [];
          if (el2 && el2.value) {
            const val = parseInt(el2.value, 10);
            if (!isNaN(val)) secondary.push(val);
          }
          if (el3 && el3.value) {
            const val = parseInt(el3.value, 10);
            if (!isNaN(val)) secondary.push(val);
          }
          return secondary.slice(0, 2); // Máximo 2 secundárias
        })(),
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
          throw new Error('Falha ao salvar');
        }

        alert('Configurações salvas com sucesso!');
      } catch (err) {
        console.error(err);
        alert('Não foi possível salvar as configurações.');
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
  });
</script>
<?php

if (! $vc_marketplace_inline) {
    get_footer();
}
