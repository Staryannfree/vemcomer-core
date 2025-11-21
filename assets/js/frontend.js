(function(){
  // ===== Utilidades de moeda =====
  function currencyToFloat(v){
    if(typeof v !== 'string') {return Number(v||0);}
    v = v.replace(/[^0-9,\.]/g,'');
    if(v.indexOf(',')>-1 && v.lastIndexOf(',')>v.lastIndexOf('.')){
      v = v.replace(/\./g,'').replace(',', '.');
    }
    return Number(v||0);
  }
  function floatToBR(n){
    return (Number(n)||0).toFixed(2).replace('.', ',');
  }

  // ===== Carrinho persistente =====
  const CART_KEY = 'vc_cart_v1';
  const cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');
  function getCartRestaurantId(){
    return cart.length ? Number(cart[0].rid) : 0;
  }
  function saveCart(){ localStorage.setItem(CART_KEY, JSON.stringify(cart)); }

  // ===== UI: render de carrinho =====
  function renderCart(root){
    const list = cart.map(i=>{
      let modifiersHTML = '';
      if(i.modifiers && i.modifiers.length > 0){
        modifiersHTML = '<div class="vc-cart-item-modifiers">' + 
          i.modifiers.map(m => `+ ${m.title}${m.price > 0 ? ` (R$ ${floatToBR(m.price)})` : ''}`).join('<br>') + 
          '</div>';
      }
      return `<div class="vc-row">
        <div class="vc-row-content">
          <span class="vc-row-title">${i.title}</span>
          ${modifiersHTML}
        </div>
        <div class="vc-qtd">
          <button class="vc-qbtn vc-dec" data-id="${i.id}" data-modifiers="${i.modifiers ? JSON.stringify(i.modifiers).replace(/"/g, '&quot;') : ''}">-</button>
          <span>${i.qtd}</span>
          <button class="vc-qbtn vc-inc" data-id="${i.id}" data-modifiers="${i.modifiers ? JSON.stringify(i.modifiers).replace(/"/g, '&quot;') : ''}">+</button>
        </div>
        <div>R$ ${floatToBR(i.qtd*currencyToFloat(i.price))}</div>
      </div>`;
    }).join('');
    const subtotal = cart.reduce((s,i)=> s + i.qtd*currencyToFloat(i.price), 0);
    root.querySelector('.vc-cart').innerHTML = list || '<div class="vc-empty">Carrinho vazio</div>';
    root.querySelector('.vc-subtotal').innerHTML = `Subtotal: <strong>R$ ${floatToBR(subtotal)}</strong>`;
    root.dataset.subtotal = String(subtotal);
    const rid = getCartRestaurantId();
    root.dataset.restaurant = rid ? String(rid) : (root.dataset.restaurant||'');
    root.dataset.ship = '0';
    root.dataset.discount = '0';
    root.dataset.fulfillmentMethod = '';
    root.dataset.fulfillmentLabel = '';
    root.dataset.fulfillmentEta = '';
    const freightBox = root.querySelector('.vc-freight'); if(freightBox){freightBox.innerHTML='';}
    const discountBox = root.querySelector('.vc-discount'); if(discountBox){discountBox.innerHTML='';}
    const totalBox = root.querySelector('.vc-total'); if(totalBox){totalBox.innerHTML='';}
    const etaBox = root.querySelector('.vc-eta'); if(etaBox){etaBox.innerHTML='';}
    const quoteResult = root.querySelector('.vc-quote-result'); if(quoteResult){quoteResult.innerHTML='';}
    const has = cart.length>0; const place = root.querySelector('.vc-place-order'); if(place) {place.disabled = !has;}
  }

  // ===== Cupom via REST API =====
  const COUPON_KEY = 'vc_coupon_v1';
  async function applyCoupon(code, restaurantId, subtotal){
    code = (code||'').trim().toUpperCase();
    if(!code) {return {ok:false, msg:'Cupom vazio.'};}
    
    try {
      const url = `${VemComer.rest.base}/coupons/validate?code=${encodeURIComponent(code)}&restaurant_id=${restaurantId}&subtotal=${subtotal}`;
      const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': VemComer.nonce },
      });
      
      if(!res.ok) {
        const error = await res.json();
        return {ok:false, msg: error.message || 'Cupom inválido.'};
      }
      
      const data = await res.json();
      if(data.valid) {
        let discount = 0;
        let freightFree = false;
        
        if(data.type === 'percent') {
          discount = subtotal * (data.value / 100);
        } else if(data.type === 'money') {
          discount = data.value;
        } else if(data.type === 'freight') {
          freightFree = true;
        }
        
        return {ok:true, code, discount, freightFree, data};
      } else {
        return {ok:false, msg: data.message || 'Cupom inválido.'};
      }
    } catch(err) {
      console.error('Erro ao validar cupom:', err);
      return {ok:false, msg:'Erro ao validar cupom. Tente novamente.'};
    }
  }

  // ===== Eventos globais =====
  document.addEventListener('click', function(e){
    const add = e.target.closest('.vc-add');
    if(add){
      const id  = Number(add.dataset.id);
      const rid = Number(add.dataset.restaurant);
      const title = add.dataset.title; 
      const price = add.dataset.price;
      const currentRid = getCartRestaurantId();
      const checkoutRoot = document.querySelector('.vc-checkout');
      const lockedRid = checkoutRoot ? Number(checkoutRoot.dataset.restaurant||0) : 0;
      
      // Verificar se é de restaurante diferente
      if((currentRid && currentRid !== rid) || (lockedRid && lockedRid !== rid)){
        alert('O checkout aceita itens de um único restaurante. Esvazie o carrinho para trocar.');
        return;
      }

      // Se o modal de produto estiver disponível, abrir modal
      if(typeof window.vcOpenProductModal === 'function'){
        const itemData = {
          title: title,
          description: add.dataset.description || '',
          price: price,
          restaurant_id: rid,
          image: add.dataset.image || null,
        };
        window.vcOpenProductModal(id, itemData);
      } else {
        // Fallback: adicionar diretamente (compatibilidade)
        const found = cart.find(i=>i.id===id);
        if(found) {found.qtd++;} else {cart.push({id, rid, title, price, qtd:1});}
        saveCart();
        if(checkoutRoot) {renderCart(checkoutRoot);}
      }
    }
  });

  // Escutar evento do modal de produto
  document.addEventListener('vc:add-to-cart', function(e){
    const detail = e.detail;
    const item = detail.item;
    const modifiers = detail.modifiers || [];
    const currentRid = getCartRestaurantId();
    const checkoutRoot = document.querySelector('.vc-checkout');
    const lockedRid = checkoutRoot ? Number(checkoutRoot.dataset.restaurant||0) : 0;
    
    // Verificar restaurante
    if((currentRid && currentRid !== item.restaurant_id) || (lockedRid && lockedRid !== item.restaurant_id)){
      alert('O checkout aceita itens de um único restaurante. Esvazie o carrinho para trocar.');
      return;
    }

    // Adicionar ao carrinho com modificadores
    const cartItem = {
      id: item.id,
      rid: item.restaurant_id,
      title: item.title,
      price: detail.totalPrice ? floatToBR(detail.totalPrice) : item.price,
      qtd: 1,
      modifiers: modifiers,
    };

    const found = cart.find(i=>i.id===item.id && JSON.stringify(i.modifiers||[]) === JSON.stringify(modifiers));
    if(found) {
      found.qtd++;
    } else {
      cart.push(cartItem);
    }
    
    saveCart();
    if(checkoutRoot) {renderCart(checkoutRoot);}
  });

  document.addEventListener('click', function(e){
    const dec = e.target.closest('.vc-dec');
    const inc = e.target.closest('.vc-inc');
    if(!dec && !inc) {return;}
    const id = Number((dec||inc).dataset.id);
    const modifiersStr = (dec||inc).dataset.modifiers;
    let modifiers = [];
    if(modifiersStr){
      try {
        modifiers = JSON.parse(modifiersStr.replace(/&quot;/g, '"'));
      } catch(e) {
        modifiers = [];
      }
    }
    // Encontrar item com mesmo ID e modificadores
    const found = cart.find(i=>i.id===id && JSON.stringify(i.modifiers||[]) === JSON.stringify(modifiers));
    if(!found) {return;}
    if(dec){ found.qtd = Math.max(0, found.qtd-1); }
    if(inc){ found.qtd++; }
    for(let i=cart.length-1;i>=0;i--){ if(cart[i].qtd===0) {cart.splice(i,1);} }
    saveCart();
    const checkout = document.querySelector('.vc-checkout');
    if(checkout) {renderCart(checkout);}
  });

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.vc-quote');
    if(!btn) {return;}
    const root = btn.closest('.vc-checkout');
    const ridAttr = Number(root.dataset.restaurant||0);
    const rid = ridAttr || getCartRestaurantId();
    if(!rid){ alert('Selecione um restaurante antes de calcular o frete.'); return; }
    root.dataset.restaurant = String(rid);
    const subtotal = Number(root.dataset.subtotal||0);
    if(subtotal<=0){ alert('Adicione itens ao carrinho antes de calcular o frete.'); return; }

    const coupon = (root.querySelector('.vc-coupon')?.value||'').trim().toUpperCase();
    const c = coupon ? await applyCoupon(coupon, rid, subtotal) : {ok:false};
    const url = `${VemComer.rest.base}/shipping/quote?restaurant_id=${rid}&subtotal=${subtotal}`;
    let shipData = null;
    try {
      const res = await fetch(url);
      shipData = await res.json();
    } catch(err) {
      root.querySelector('.vc-quote-result').innerHTML = 'Erro ao consultar frete.';
      return;
    }
    const methods = Array.isArray(shipData.methods) ? shipData.methods : [];
    if(!methods.length){
      const msg = shipData && shipData.message ? shipData.message : 'Nenhum método disponível.';
      root.querySelector('.vc-quote-result').innerHTML = msg;
      root.dataset.fulfillmentMethod = '';
      root.dataset.ship = '0';
      return;
    }

    // Exibir opções de métodos
    let methodsHTML = '<div class="vc-fulfillment-methods">';
    methods.forEach((method, index) => {
      const methodId = method.id || method.slug || `method_${index}`;
      const methodLabel = method.label || 'Entrega';
      const methodAmount = Number(method.amount || 0);
      const methodEta = method.eta || '';
      const isAvailable = method.details?.available !== false;
      const isSelected = index === 0;
      
      methodsHTML += `
        <label class="vc-fulfillment-method ${isSelected ? 'is-selected' : ''} ${!isAvailable ? 'is-unavailable' : ''}" data-method-id="${methodId}">
          <input type="radio" name="fulfillment_method" value="${methodId}" ${isSelected ? 'checked' : ''} ${!isAvailable ? 'disabled' : ''} />
          <div class="vc-fulfillment-method-content">
            <div class="vc-fulfillment-method-header">
              <span class="vc-fulfillment-method-label">${methodLabel}</span>
              <span class="vc-fulfillment-method-price">R$ ${floatToBR(methodAmount)}</span>
            </div>
            ${methodEta ? `<div class="vc-fulfillment-method-eta">${methodEta}</div>` : ''}
            ${!isAvailable && method.details?.reason ? `<div class="vc-fulfillment-method-reason">${method.details.reason}</div>` : ''}
          </div>
        </label>
      `;
    });
    methodsHTML += '</div>';
    
    root.querySelector('.vc-quote-result').innerHTML = methodsHTML;

    // Selecionar primeiro método disponível por padrão
    const firstAvailable = methods.find(m => m.details?.available !== false) || methods[0];
    if(firstAvailable) {
      selectFulfillmentMethod(root, firstAvailable, c, subtotal);
    }

    // Handler para mudança de método
    root.querySelectorAll('input[name="fulfillment_method"]').forEach(radio => {
      radio.addEventListener('change', function() {
        if(this.checked) {
          const methodId = this.value;
          const method = methods.find(m => (m.id || m.slug || `method_${methods.indexOf(m)}`) === methodId);
          if(method) {
            selectFulfillmentMethod(root, method, c, subtotal);
          }
        }
      });
    });

    // Atualizar visual de seleção
    root.querySelectorAll('.vc-fulfillment-method').forEach(label => {
      const radio = label.querySelector('input[type="radio"]');
      if(radio && radio.checked) {
        label.classList.add('is-selected');
      } else {
        label.classList.remove('is-selected');
      }
    });

    if(c.ok) {localStorage.setItem(COUPON_KEY, coupon);} else {localStorage.removeItem(COUPON_KEY);}
    root.querySelector('.vc-place-order').disabled = cart.length===0;
  });

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.vc-place-order');
    if(!btn || btn.disabled) {return;}
    const root = btn.closest('.vc-checkout');
    const restaurantId = Number(root.dataset.restaurant||0);
    if(!restaurantId){ alert('Selecione um restaurante antes de finalizar.'); return; }
    const subtotal = Number(root.dataset.subtotal||0);
    const ship = Number(root.dataset.ship||0);
    const discount = Number(root.dataset.discount||0);
    const total = Math.max(0, subtotal - discount + ship);
    const fulfillmentMethod = root.dataset.fulfillmentMethod || '';
    if(!fulfillmentMethod){ alert('Calcule o frete e escolha um método de entrega antes de finalizar.'); return; }

    // Desabilitar botão durante processamento
    btn.disabled = true;
    btn.textContent = 'Processando...';
    const resultBox = root.querySelector('.vc-order-result');
    resultBox.innerHTML = '<p class="vc-loading">Validando pedido...</p>';

    // Preparar dados do pedido
    const items = cart.map(i => {
      const itemPrice = currencyToFloat(i.price);
      const modifiers = (i.modifiers || []).map(m => ({
        id: m.id,
        title: m.title,
        price: m.price || 0,
      }));
      
      return {
        id: i.id,
        name: i.title,
        quantity: i.qtd,
        price: itemPrice,
        modifiers: modifiers,
      };
    });

    // Obter dados do cliente (se disponíveis)
    const customerName = root.dataset.customerName || '';
    const customerPhone = root.dataset.customerPhone || '';
    const customerAddress = root.dataset.customerAddress || '';
    const customerLat = root.dataset.customerLat ? Number(root.dataset.customerLat) : null;
    const customerLng = root.dataset.customerLng ? Number(root.dataset.customerLng) : null;

    // 1. Validar pedido
    const validatePayload = {
      restaurant_id: restaurantId,
      items: items,
      fulfillment: {
        type: fulfillmentMethod.includes('pickup') ? 'pickup' : 'delivery',
        fee: ship,
      },
      customer_lat: customerLat,
      customer_lng: customerLng,
    };

    try {
      const validateRes = await fetch(`${VemComer.rest.base}/orders/validate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': VemComer.nonce },
        body: JSON.stringify(validatePayload),
      });

      const validateData = await validateRes.json();

      if(!validateData.valid) {
        const errors = validateData.errors || ['Erro ao validar pedido.'];
        resultBox.innerHTML = '<div class="vc-error">' + errors.map(e => `<p>${e}</p>`).join('') + '</div>';
        btn.disabled = false;
        btn.textContent = 'Finalizar pedido';
        return;
      }

      // 2. Gerar mensagem WhatsApp
      resultBox.innerHTML = '<p class="vc-loading">Gerando mensagem...</p>';

      const whatsappPayload = {
        restaurant_id: restaurantId,
        items: items,
        customer: {
          name: customerName,
          phone: customerPhone,
          address: customerAddress,
        },
        fulfillment: {
          type: fulfillmentMethod.includes('pickup') ? 'pickup' : 'delivery',
          fee: ship,
        },
      };

      const whatsappRes = await fetch(`${VemComer.rest.base}/orders/prepare-whatsapp`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': VemComer.nonce },
        body: JSON.stringify(whatsappPayload),
      });

      if(!whatsappRes.ok) {
        const error = await whatsappRes.json();
        resultBox.innerHTML = '<div class="vc-error"><p>' + (error.message || 'Erro ao gerar mensagem WhatsApp.') + '</p></div>';
        btn.disabled = false;
        btn.textContent = 'Finalizar pedido';
        return;
      }

      const whatsappData = await whatsappRes.json();

      // 3. Abrir WhatsApp
      if(whatsappData.whatsapp_url) {
        window.open(whatsappData.whatsapp_url, '_blank');
        resultBox.innerHTML = '<div class="vc-success"><p>Mensagem gerada! Abra o WhatsApp para enviar o pedido.</p></div>';
        
        // Limpar carrinho após sucesso
        cart.length = 0;
        saveCart();
        renderCart(root);
        root.querySelector('.vc-freight').innerHTML='';
        root.querySelector('.vc-total').innerHTML='';
        root.querySelector('.vc-quote-result').innerHTML='';
        root.querySelector('.vc-discount').innerHTML='';
        root.querySelector('.vc-place-order').disabled = true;
      } else {
        resultBox.innerHTML = '<div class="vc-error"><p>Erro ao gerar link do WhatsApp.</p></div>';
        btn.disabled = false;
        btn.textContent = 'Finalizar pedido';
      }
    } catch(err) {
      console.error('Erro ao processar pedido:', err);
      resultBox.innerHTML = '<div class="vc-error"><p>Erro ao processar pedido. Tente novamente.</p></div>';
      btn.disabled = false;
      btn.textContent = 'Finalizar pedido';
    }
  });

  // ===== Acompanhamento (polling) =====
  let trackingTimer = null;
  async function pollStatus(orderId, box){
    const url = `${VemComer.rest.base}/orders/${orderId}`;
    try{
      const r = await fetch(url); const j = await r.json();
      if(j && j.id){
        box.innerHTML = `<div class="vc-status-banner">Status: <strong>${j.status_label||j.status}</strong></div>`;
        if(['vc-completed','vc-cancelled'].includes(j.status)){
          clearInterval(trackingTimer); trackingTimer = null;
        }
      }
    }catch{ /* silencioso */ }
  }

  document.addEventListener('click', function(e){
    const t = e.target.closest('.vc-track');
    if(!t) {return;}
    const id = Number(t.dataset.id);
    const root = t.closest('.vc-checkout');
    const box = root.querySelector('.vc-order-result');
    if(trackingTimer) {clearInterval(trackingTimer);}
    pollStatus(id, box); // imediato
    trackingTimer = setInterval(()=>pollStatus(id, box), 5000);
  });

  // Função para selecionar método de fulfillment
  function selectFulfillmentMethod(root, method, coupon, subtotal) {
    const methodShip = Number(method.amount || 0);
    const etaText = method.eta || '';
    const methodId = method.id || method.slug || '';
    const methodLabel = method.label || 'Entrega';
    
    let ship = methodShip;
    let discount = coupon.ok ? (coupon.discount || 0) : 0;
    if(coupon.ok && coupon.freightFree){
      discount += ship;
    }
    const total = Math.max(0, subtotal - discount + ship);

    root.querySelector('.vc-freight').innerHTML = `Frete: <strong>R$ ${floatToBR(methodShip)}</strong>`;
    root.querySelector('.vc-discount').innerHTML = discount>0 ? `Descontos: <strong>- R$ ${floatToBR(discount)}</strong>` : '';
    root.querySelector('.vc-total').innerHTML = `Total: <strong>R$ ${floatToBR(total)}</strong>`;
    root.querySelector('.vc-eta').innerHTML = etaText ? `Entrega estimada: <strong>${etaText}</strong>` : '';

    root.dataset.ship = String(ship);
    root.dataset.discount = String(discount);
    root.dataset.fulfillmentMethod = methodId;
    root.dataset.fulfillmentLabel = methodLabel;
    root.dataset.fulfillmentEta = etaText;
  }

  // Render inicial se existir checkout na página
  window.addEventListener('DOMContentLoaded', ()=>{
    const checkout = document.querySelector('.vc-checkout');
    if(checkout) {
      renderCart(checkout);
      const coupon = localStorage.getItem(COUPON_KEY)||'';
      const input = document.querySelector('.vc-coupon'); if(input) {input.value = coupon;}
    }
  });
})();
