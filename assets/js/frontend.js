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
  function saveCart(){ localStorage.setItem(CART_KEY, JSON.stringify(cart)); }

  // ===== UI: render de carrinho =====
  function renderCart(root){
    const list = cart.map(i=>`<div class="vc-row"><span>${i.title}</span><div class="vc-qtd"><button class="vc-qbtn vc-dec" data-id="${i.id}">-</button><span>${i.qtd}</span><button class="vc-qbtn vc-inc" data-id="${i.id}">+</button></div><div>R$ ${floatToBR(i.qtd*currencyToFloat(i.price))}</div></div>`).join('');
    const subtotal = cart.reduce((s,i)=> s + i.qtd*currencyToFloat(i.price), 0);
    root.querySelector('.vc-cart').innerHTML = list || '<div class="vc-empty">Carrinho vazio</div>';
    root.querySelector('.vc-subtotal').innerHTML = `Subtotal: <strong>R$ ${floatToBR(subtotal)}</strong>`;
    root.dataset.subtotal = String(subtotal);
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
      const found = cart.find(i=>i.id===id);
      if(found) {found.qtd++;} else {cart.push({id, rid, title, price, qtd:1});}
      saveCart();
      const checkout = document.querySelector('.vc-checkout');
      if(checkout) {renderCart(checkout);}
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
    const rid = Number(root.dataset.restaurant||0) || (cart[0] && cart[0].rid) || 0;
    const subtotal = Number(root.dataset.subtotal||0);

    const coupon = (root.querySelector('.vc-coupon')?.value||'').trim().toUpperCase();
    const c = applyCoupon(coupon, subtotal);
    const url = `${VemComer.rest.base}/shipping/quote?restaurant_id=${rid}&subtotal=${subtotal}`;
    const res = await fetch(url); const shipData = await res.json();
    let ship = Number(shipData.ship||0);
    if(c.ok && c.freightFree) {ship = 0;}

    const discount = c.ok ? (c.discount||0) : 0;
    const total = Math.max(0, subtotal - discount + ship);

    root.querySelector('.vc-quote-result').innerHTML = shipData.free || (c.ok && c.freightFree) ? 'Frete grátis' : `Frete: R$ ${floatToBR(ship)}`;
    root.querySelector('.vc-freight').innerHTML = `Frete: <strong>R$ ${floatToBR(ship)}</strong>`;
    root.querySelector('.vc-discount').innerHTML = c.ok ? `Desconto: <strong>- R$ ${floatToBR(discount)}</strong>` : '';
    root.querySelector('.vc-total').innerHTML = `Total: <strong>R$ ${floatToBR(total)}</strong>`;

    root.dataset.ship = String(ship);
    root.dataset.discount = String(discount);
    if(c.ok) {localStorage.setItem(COUPON_KEY, coupon);} else {localStorage.removeItem(COUPON_KEY);}
    root.querySelector('.vc-place-order').disabled = cart.length===0;
  });

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.vc-place-order');
    if(!btn) {return;}
    const root = btn.closest('.vc-checkout');
    const subtotal = Number(root.dataset.subtotal||0);
    const ship = Number(root.dataset.ship||0);
    const discount = Number(root.dataset.discount||0);
    const total = Math.max(0, subtotal - discount + ship);

    const itens = cart.map(i=>({ produto_id: i.id, qtd: i.qtd }));
    const payload = { itens, total: floatToBR(total) };
    const res = await fetch(`${VemComer.rest.base}/pedidos`,{
      method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce': VemComer.nonce}, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data && data.id){
      root.querySelector('.vc-order-result').innerHTML = `Pedido criado #${data.id}. <button class="vc-btn vc-track" data-id="${data.id}">Acompanhar</button>`;
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
