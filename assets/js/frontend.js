(function(){
  function currencyToFloat(v){
    if(typeof v !== 'string') return Number(v||0);
    v = v.replace(/[^0-9,\.]/g,'');
    // tenta formato brasileiro
    if(v.indexOf(',')>-1 && v.lastIndexOf(',')>v.lastIndexOf('.')){
      v = v.replace(/\./g,'').replace(',', '.');
    }
    return Number(v||0);
  }
  function floatToBR(n){
    return (Number(n)||0).toFixed(2).replace('.', ',');
  }

  const cart = [];
  function renderCart(root){
    const list = cart.map(i=>`<div class="vc-row"><span>${i.title}</span><div>x${i.qtd}</div><div>R$ ${floatToBR(i.qtd*currencyToFloat(i.price))}</div></div>`).join('');
    const subtotal = cart.reduce((s,i)=> s + i.qtd*currencyToFloat(i.price), 0);
    root.querySelector('.vc-cart').innerHTML = list || '<div class="vc-empty">Carrinho vazio</div>';
    root.querySelector('.vc-subtotal').innerHTML = `Subtotal: <strong>R$ ${floatToBR(subtotal)}</strong>`;
    root.dataset.subtotal = String(subtotal);
  }

  document.addEventListener('click', function(e){
    const add = e.target.closest('.vc-add');
    if(add){
      const id  = Number(add.dataset.id);
      const rid = Number(add.dataset.restaurant);
      const title = add.dataset.title;
      const price = add.dataset.price;
      const found = cart.find(i=>i.id===id);
      if(found) found.qtd++; else cart.push({id, rid, title, price, qtd:1});
      const checkout = document.querySelector('.vc-checkout');
      if(checkout) renderCart(checkout);
    }
  });

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.vc-quote');
    if(!btn) return;
    const root = btn.closest('.vc-checkout');
    const rid = Number(root.dataset.restaurant||0) || (cart[0] && cart[0].rid) || 0;
    const zip = root.querySelector('.vc-zip').value;
    const subtotal = Number(root.dataset.subtotal||0);
    if(!rid){ alert('Selecione itens de um restaurante.'); return; }
    const url = `${VemComer.rest.base}/shipping/quote?restaurant_id=${rid}&subtotal=${subtotal}`;
    const res = await fetch(url);
    const data = await res.json();
    root.dataset.ship = String(data.ship||0);
    root.querySelector('.vc-quote-result').innerHTML = data.free ? 'Frete grátis' : `Frete: R$ ${floatToBR(data.ship)}`;
    root.querySelector('.vc-freight').innerHTML = `Frete: <strong>R$ ${floatToBR(data.ship||0)}</strong>`;
    const total = subtotal + Number(data.ship||0);
    root.querySelector('.vc-total').innerHTML = `Total: <strong>R$ ${floatToBR(total)}</strong>`;
    root.querySelector('.vc-place-order').disabled = cart.length===0;
  });

  document.addEventListener('click', async function(e){
    const btn = e.target.closest('.vc-place-order');
    if(!btn) return;
    const root = btn.closest('.vc-checkout');
    const subtotal = Number(root.dataset.subtotal||0);
    const ship = Number(root.dataset.ship||0);
    const total = subtotal + ship;

    const itens = cart.map(i=>({ produto_id: i.id, qtd: i.qtd }));
    const res = await fetch(`${VemComer.rest.base}/pedidos`,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-WP-Nonce': VemComer.nonce},
      body: JSON.stringify({ itens, total: floatToBR(total) })
    });
    const data = await res.json();
    if(data && data.id){
      root.querySelector('.vc-order-result').innerHTML = `Pedido criado #${data.id}`;
      cart.length = 0; renderCart(root);
      root.querySelector('.vc-freight').innerHTML='';
      root.querySelector('.vc-total').innerHTML='';
      root.querySelector('.vc-quote-result').innerHTML='';
      root.querySelector('.vc-place-order').disabled = true;
    } else {
      alert('Falha ao criar pedido');
    }
  });

  // Render inicial se existir checkout na página
  window.addEventListener('DOMContentLoaded', ()=>{
    const checkout = document.querySelector('.vc-checkout');
    if(checkout) renderCart(checkout);
  });
})();
