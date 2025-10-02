(function($){
  $(function(){
    var txt = $('input[name="rc_shipping_address_text"]');
    var lat = $('input[name="rc_shipping_lat"]');
    var lng = $('input[name="rc_shipping_lng"]');
    if (!txt.length) return;

    // Botões
    var bar = $('<p class="form-row form-row-wide" />');
    var btnSearch = $('<a href="#" class="button" style="margin-right:8px;">Buscar endereço</a>');
    var btnLocate = $('<a href="#" class="button button-outline">Detectar localização</a>');
    bar.append(btnSearch).append(btnLocate);
    txt.closest('.form-row').after(bar);

    function geocode(q){
      var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q='+encodeURIComponent(q);
      return fetch(url, { headers: { 'Accept':'application/json' } }).then(function(r){ return r.json(); });
    }
    function reverse(latv, lngv){
      var url = 'https://nominatim.openstreetmap.org/reverse?format=json&zoom=18&lat='+encodeURIComponent(latv)+'&lon='+encodeURIComponent(lngv);
      return fetch(url, { headers: { 'Accept':'application/json' } }).then(function(r){ return r.json(); });
    }
    function fillFromReverse(json){
      try{
        var addr = json.display_name || '';
        if (addr && txt.val().trim() === '') txt.val(addr);
        var comp = json.address || {};
        var map = {
          'shipping_address_1': [comp.road, comp.house_number].filter(Boolean).join(', '),
          'shipping_city': comp.city || comp.town || comp.village || comp.suburb || '',
          'shipping_postcode': comp.postcode || '',
          'shipping_state': comp.state || ''
        };
        Object.keys(map).forEach(function(k){
          var el = $('[name="'+k+'"]');
          if (el.length && !el.val()) el.val(map[k]);
        });
      }catch(e){}
    }

    btnSearch.on('click', function(e){
      e.preventDefault();
      var q = txt.val().trim();
      if (!q){ alert('Digite um endereço para buscar.'); return; }
      btnSearch.text('Buscando...').prop('disabled', true);
      geocode(q).then(function(res){
        if (res && res[0]){
          lat.val(res[0].lat); lng.val(res[0].lon);
          $(document.body).trigger('update_checkout');
        } else {
          alert('Endereço não encontrado. Ajuste e tente novamente.');
        }
      }).catch(function(){ alert('Falha na busca de endereço.'); })
        .finally(function(){ btnSearch.text('Buscar endereço').prop('disabled', false); });
    });

    btnLocate.on('click', function(e){
      e.preventDefault();
      if (!navigator.geolocation){ alert('Geolocalização indisponível.'); return; }
      btnLocate.text('Localizando...').prop('disabled', true);
      navigator.geolocation.getCurrentPosition(function(pos){
        var la = pos.coords.latitude.toFixed(6), lo = pos.coords.longitude.toFixed(6);
        lat.val(la); lng.val(lo);
        reverse(la, lo).then(function(j){ fillFromReverse(j); });
        $(document.body).trigger('update_checkout');
      }, function(){
        alert('Não foi possível obter sua localização.');
      }, { enableHighAccuracy:true, timeout:8000 });
    btnLocate.text('Detectar localização').prop('disabled', false);
    });
  });
})(jQuery);
