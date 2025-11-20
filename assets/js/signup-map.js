(function(){
  function init(){
    if (typeof L === 'undefined') {return;}
    var form = document.querySelector('form[data-vc-form="restaurant"]');
    var mapEl = document.getElementById('vc-restaurant-map-picker');
    if (!form || !mapEl) {return;}

    var latInput = form.querySelector('input[name="restaurant_lat"]');
    var lngInput = form.querySelector('input[name="restaurant_lng"]');
    if (!latInput || !lngInput) {return;}

    var defaultLat = parseFloat(latInput.value) || -14.235004;
    var defaultLng = parseFloat(lngInput.value) || -51.925282;
    var map = L.map(mapEl).setView([defaultLat, defaultLng], 4);
    var tiles = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    L.tileLayer(tiles, {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);

    var marker = null;
    function setMarker(lat, lng){
      lat = Number(lat);
      lng = Number(lng);
      if (Number.isNaN(lat) || Number.isNaN(lng)) {return;}
      var roundedLat = Number(lat.toFixed(6));
      var roundedLng = Number(lng.toFixed(6));
      latInput.value = String(roundedLat);
      lngInput.value = String(roundedLng);
      if (!marker) {
        marker = L.marker([roundedLat, roundedLng]).addTo(map);
      } else {
        marker.setLatLng([roundedLat, roundedLng]);
      }
    }

    map.on('click', function(e){
      setMarker(e.latlng.lat, e.latlng.lng);
      reverseGeocode(e.latlng.lat, e.latlng.lng);
    });

    var geoBtn = document.getElementById('vc-use-my-location');
    var addressInput = form.querySelector('input[name="restaurant_address"]');
    var reverseGeocode = function(lat, lng){
      if(!addressInput){ return; }
      var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function(resp){ return resp.ok ? resp.json() : null; })
        .then(function(data){
          if(data && data.display_name){
            addressInput.value = data.display_name;
          }
        })
        .catch(function(){});
    };

    if (geoBtn){
      if (!navigator.geolocation){
        geoBtn.disabled = true;
        geoBtn.title = 'Geolocalização não suportada neste navegador.';
      }
      geoBtn.addEventListener('click', function(e){
        e.preventDefault();
        if (!navigator.geolocation){ return; }
        geoBtn.classList.add('is-loading');
        navigator.geolocation.getCurrentPosition(function(pos){
          var lat = pos.coords.latitude;
          var lng = pos.coords.longitude;
          setMarker(lat, lng);
          map.setView([lat, lng], 16);
          reverseGeocode(lat, lng);
          geoBtn.classList.remove('is-loading');
        }, function(){
          alert('Não foi possível obter sua localização.');
          geoBtn.classList.remove('is-loading');
        }, { enableHighAccuracy: true, timeout: 8000 });
      });
    }
  }

  if (document.readyState !== 'loading') {init();}
  else {document.addEventListener('DOMContentLoaded', init);}
})();
