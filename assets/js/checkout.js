(function(){
  if(typeof window === 'undefined'){ return; }
  if(!window.VemComer){ console.warn('VemComer REST helpers indisponíveis.'); return; }

  async function exampleQuote(){
    const url = `${VemComer.rest.base}/shipping/quote?restaurant_id=1&subtotal=59.90`;
    const res = await fetch(url);
    return res.json();
  }

  async function exampleOrder(){
    const payload = {
      restaurant_id: 1,
      itens: [ { produto_id: 10, qtd: 1 } ],
      subtotal: '59,90',
      fulfillment: { method: 'flat_rate_delivery', ship_total: '9,90' }
    };
    const res = await fetch(`${VemComer.rest.base}/pedidos`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': VemComer.nonce },
      body: JSON.stringify(payload)
    });
    return res.json();
  }

  window.VemComerCheckoutExamples = { exampleQuote, exampleOrder };
})();
