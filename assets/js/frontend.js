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
    const list = cart.map(i=>`<div class="vc-row"><span>${i.title}</span><div class="vc-qtd"><button class="vc-qbtn vc-dec" data-id="${i.id}">-</button><span>${i.qtd}</span><button class="vc-qbtn vc-inc" data-id="${i.id}">+</button></div><div>R$ ${floatToBR(i.qtd*currencyToFloat(i.price))}</div></div>`).join('');
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

  // ===== Cupom simples =====
  const COUPON_KEY = 'vc_coupon_v1';
  function applyCoupon(code, subtotal){
    code = (code||'').trim().toUpperCase();
    if(!code) {return {ok:false, msg:'Cupom vazio.'};}
    // Regras simples (poderá vir de REST no futuro)
    const rules = {
      'DESC10': { type:'percent', value:10 },
      'DESC5':  { type:'money', value:5.00 },
      'FRETEGRATIS': { type:'freight', value:1 }
    };
    const r = rules[code];
    if(!r) {return {ok:false, msg:'Cupom inválido.'};}
    let discount = 0, freightFree = false;
    if(r.type==='percent') {discount = subtotal * (r.value/100);}
    if(r.type==='money') {discount = r.value;}
    if(r.type==='freight') {freightFree = true;}
    return {ok:true, code, discount, freightFree};
  }

  // ===== Eventos globais =====
  document.addEventListener('click', function(e){
    const add = e.target.closest('.vc-add');
    if(add){
      const id  = Number(add.dataset.id);
      const rid = Number(add.dataset.restaurant);
      const title = add.dataset.title; const price = add.dataset.price;
      const currentRid = getCartRestaurantId();
      const checkoutRoot = document.querySelector('.vc-checkout');
      const lockedRid = checkoutRoot ? Number(checkoutRoot.dataset.restaurant||0) : 0;
      if((currentRid && currentRid !== rid) || (lockedRid && lockedRid !== rid)){
        alert('O checkout aceita itens de um único restaurante. Esvazie o carrinho para trocar.');
        return;
      }
      const found = cart.find(i=>i.id===id);
      if(found) {found.qtd++;} else {cart.push({id, rid, title, price, qtd:1});}
      saveCart();
      if(checkoutRoot) {renderCart(checkoutRoot);}
    }
  });

  document.addEventListener('click', function(e){
    const dec = e.target.closest('.vc-dec');
    const inc = e.target.closest('.vc-inc');
    if(!dec && !inc) {return;}
    const id = Number((dec||inc).dataset.id);
    const found = cart.find(i=>i.id===id); if(!found) {return;}
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
    const c = coupon ? applyCoupon(coupon, subtotal) : {ok:false};
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
    const method = methods[0];
    const methodShip = Number(method.amount||0);
    const etaText = method.eta || '';
    let ship = methodShip;
    let discount = c.ok ? (c.discount||0) : 0;
    if(c.ok && c.freightFree){
      discount += ship;
    }
    const total = Math.max(0, subtotal - discount + ship);

    const labelText = method.label || 'Entrega';
    const etaLabel = etaText ? ` • ${etaText}` : '';
    root.querySelector('.vc-quote-result').innerHTML = `${labelText}${etaLabel}`;
    root.querySelector('.vc-freight').innerHTML = `Frete: <strong>R$ ${floatToBR(methodShip)}</strong>`;
    root.querySelector('.vc-discount').innerHTML = discount>0 ? `Descontos: <strong>- R$ ${floatToBR(discount)}</strong>` : '';
    root.querySelector('.vc-total').innerHTML = `Total: <strong>R$ ${floatToBR(total)}</strong>`;
    root.querySelector('.vc-eta').innerHTML = etaText ? `Entrega estimada: <strong>${etaText}</strong>` : '';

    root.dataset.ship = String(ship);
    root.dataset.discount = String(discount);
    root.dataset.fulfillmentMethod = method.id || '';
    root.dataset.fulfillmentLabel = method.label || '';
    root.dataset.fulfillmentEta = etaText;
    if(c.ok) {localStorage.setItem(COUPON_KEY, coupon);} else {localStorage.removeItem(COUPON_KEY);}
    root.querySelector('.vc-place-order').disabled = cart.length===0;
  });

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.vc-place-order');
    if(!btn) {return;}
    const root = btn.closest('.vc-checkout');
    const restaurantId = Number(root.dataset.restaurant||0);
    if(!restaurantId){ alert('Selecione um restaurante antes de finalizar.'); return; }
    const subtotal = Number(root.dataset.subtotal||0);
    const ship = Number(root.dataset.ship||0);
    const discount = Number(root.dataset.discount||0);
    const total = Math.max(0, subtotal - discount + ship);
    const fulfillmentMethod = root.dataset.fulfillmentMethod || '';
    if(!fulfillmentMethod){ alert('Calcule o frete e escolha um método de entrega antes de finalizar.'); return; }

    const itens = cart.map(i=>({ produto_id: i.id, qtd: i.qtd }));
    const payload = {
      restaurant_id: restaurantId,
      itens,
      subtotal: floatToBR(subtotal),
      fulfillment: {
        method: fulfillmentMethod,
        ship_total: floatToBR(ship)
      }
    };
    const res = await fetch(`${VemComer.rest.base}/pedidos`,{
      method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce': VemComer.nonce}, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data && data.id){
      const fulfillmentLabel = data.fulfillment && data.fulfillment.label ? data.fulfillment.label : '';
      const etaInfo = data.fulfillment && data.fulfillment.eta ? ` ETA: ${data.fulfillment.eta}` : '';
      root.querySelector('.vc-order-result').innerHTML = `Pedido criado #${data.id}. Frete ${fulfillmentLabel}.${etaInfo} <button class="vc-btn vc-track" data-id="${data.id}">Acompanhar</button>`;
      cart.length = 0; saveCart(); renderCart(root);
      root.querySelector('.vc-freight').innerHTML='';
      root.querySelector('.vc-total').innerHTML='';
      root.querySelector('.vc-quote-result').innerHTML='';
      root.querySelector('.vc-discount').innerHTML='';
      root.querySelector('.vc-place-order').disabled = true;
    } else {
      alert('Falha ao criar pedido');
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
