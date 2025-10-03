(function(){
  var state = { ordersById: {}, enabledSound: true, poll: null };
  function beep(){
    if (!state.enabledSound) {return;}
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var o = ctx.createOscillator();
      var g = ctx.createGain();
      o.type = 'sine'; o.frequency.setValueAtTime(880, ctx.currentTime);
      o.connect(g); g.connect(ctx.destination);
      g.gain.setValueAtTime(0.001, ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.2, ctx.currentTime + 0.01);
      o.start();
      setTimeout(function(){ g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25); o.stop(ctx.currentTime + 0.25); }, 180);
    } catch{}
  }
  function render(orders){
    var cols = {
      awaiting_confirmation: document.querySelector('.vc-kds-col[data-col="awaiting_confirmation"] .vc-kds-list'),
      confirmed: document.querySelector('.vc-kds-col[data-col="confirmed"] .vc-kds-list'),
      preparing: document.querySelector('.vc-kds-col[data-col="preparing"] .vc-kds-list'),
      out_for_delivery: document.querySelector('.vc-kds-col[data-col="out_for_delivery"] .vc-kds-list'),
    };
    Object.keys(cols).forEach(function(k){ cols[k].innerHTML = ''; });
    var newOrChanged = false;
    orders.forEach(function(o){
      var prev = state.ordersById[o.id];
      if (!prev || prev.status !== o.status) {newOrChanged = true;}
      state.ordersById[o.id] = o;
      var colKey = o.status; if (!cols[colKey]) {return;}
      var div = document.createElement('div');
      div.className = 'vc-kds-ticket';
      var itemsHtml = (o.items||[]).map(function(it){ return '<li>'+it.name+' x '+it.qty+'</li>'; }).join('');
      var actions = '';
      if (o.status === 'awaiting_confirmation') {actions = '<button data-act="confirm" data-id="'+o.id+'" class="button">Confirmar</button> <button data-act="cancel" data-id="'+o.id+'" class="button button-outline">Cancelar</button>';}
      if (o.status === 'confirmed') {actions = '<button data-act="prepare" data-id="'+o.id+'" class="button">Iniciar preparo</button>';}
      if (o.status === 'preparing') {actions = '<button data-act="out" data-id="'+o.id+'" class="button">Saiu p/ entrega</button>';}
      if (o.status === 'out_for_delivery') {actions = '<button data-act="delivered" data-id="'+o.id+'" class="button">Entregue</button>';}
      div.innerHTML =
        '<div class="vc-kds-head"><strong>#'+o.id+'</strong><span class="vc-kds-badge">'+o.status_label+'</span></div>'+
        '<ul class="vc-kds-items">'+itemsHtml+'</ul>'+
        '<div class="vc-kds-actions">'+actions+'</div>'+
        '<div class="vc-kds-foot"><strong>Total:</strong> '+o.total_html+'</div>';
      cols[colKey].appendChild(div);
    });
    if (newOrChanged) {beep();}
  }
  function fetchOrders(){
    var url = VC_KDS.rest + '/orders?rid=' + encodeURIComponent(VC_KDS.rid);
    fetch(url, { headers: { 'X-WP-Nonce': VC_KDS.nonce } })
      .then(function(r){ return r.json(); })
      .then(function(data){ render(data.orders || []); })
      .catch(function(){ });
  }
  function mutate(id, action){
    var url = VC_KDS.rest + '/orders/' + id + '/status';
    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-WP-Nonce': VC_KDS.nonce },
      body: JSON.stringify({ action: action })
    }).then(function(){ fetchOrders(); });
  }
  function init(){
    var root = document.getElementById('vc-kds');
    if (!root) {return;}
    document.addEventListener('click', function(e){
      var b = e.target.closest('button[data-act]');
      if (!b) {return;} e.preventDefault();
      mutate(b.getAttribute('data-id'), b.getAttribute('data-act'));
    });
    var btnRefresh = root.querySelector('[data-kds-refresh]');
    if (btnRefresh) {btnRefresh.addEventListener('click', function(e){ e.preventDefault(); fetchOrders(); });}
    var chkSound = root.querySelector('[data-kds-sound]');
    if (chkSound) {chkSound.addEventListener('change', function(){ state.enabledSound = !!this.checked; });}
    fetchOrders();
    state.poll = setInterval(fetchOrders, VC_KDS.poll || 7000);
  }
  if (document.readyState !== 'loading') {init();}
  else {document.addEventListener('DOMContentLoaded', init);}
})();
