(function(){
  function esc(str){
    return String(str || '').replace(/[&<>"']/g, function(ch){
      return ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch]);
    });
  }

  function buildPopup(data, strings){
    var parts = ['<strong>'+esc(data.title||'')+'</strong>'];
    if (data.cuisine){ parts.push('<div class="vc-map__popup-line">'+esc(data.cuisine)+'</div>'); }
    if (data.address){ parts.push('<div class="vc-map__popup-line">'+esc(data.address)+'</div>'); }
    if (typeof data.distance === 'number'){ parts.push('<div class="vc-map__popup-line">'+data.distance.toFixed(1)+' km</div>'); }
    if (data.url){
      var label = strings.viewRestaurant || 'Ver restaurante';
      parts.push('<div class="vc-map__popup-line"><a class="vc-link" href="'+esc(data.url)+'">'+esc(label)+'</a></div>');
    }
    return parts.join('');
  }

  function toNumber(v){
    var n = Number(v);
    return Number.isNaN(n) ? null : n;
  }

  function init(){
    if (typeof VC_RESTAURANTS_MAP === 'undefined' || typeof L === 'undefined') {return;}
    var cfg = VC_RESTAURANTS_MAP;
    var mapEl = document.getElementById('vc-restaurants-map');
    if (!mapEl) {return;}

    var map = L.map(mapEl);
    var tiles = cfg.tiles || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    L.tileLayer(tiles, {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(map);

    var clusterEnabled = !!cfg.useCluster && typeof L.markerClusterGroup !== 'undefined';
    var layer = clusterEnabled ? L.markerClusterGroup() : L.layerGroup();
    layer.addTo(map);

    var statusEl = document.getElementById('vc-map-status');
    var radiusInput = document.getElementById('vc-map-radius');
    var useBtn = document.getElementById('vc-map-use-location');
    var resetBtn = document.getElementById('vc-map-reset');
    var strings = cfg.strings || {};

    var userMarker = null; var userCircle = null;
    function setUser(lat, lng){
      if (userMarker){ map.removeLayer(userMarker); userMarker = null; }
      if (userCircle){ map.removeLayer(userCircle); userCircle = null; }
      var nLat = toNumber(lat), nLng = toNumber(lng);
      if (nLat === null || nLng === null) {return;}
      userMarker = L.marker([nLat, nLng]);
      userCircle = L.circle([nLat, nLng], {radius: 180, color:'#111827', fillColor:'#111827', fillOpacity:0.12});
      userCircle.addTo(map);
      userMarker.addTo(map);
    }

    function updateStatus(text){ if(statusEl){ statusEl.textContent = text || ''; } }

    function renderMarkers(list){
      if (typeof layer.clearLayers === 'function'){ layer.clearLayers(); }
      var bounds = [];
      (Array.isArray(list) ? list : []).forEach(function(item){
        var lat = toNumber(item.lat), lng = toNumber(item.lng);
        if (lat === null || lng === null) {return;}
        var marker = L.marker([lat, lng]);
        marker.bindPopup(buildPopup(item, strings));
        layer.addLayer(marker);
        bounds.push([lat, lng]);
      });
      if (bounds.length){ map.fitBounds(bounds, {padding:[16,16]}); }
      else { map.setView([-14.235004, -51.925282], 4); }
    }

    function cleanMarkers(raw){
      return (Array.isArray(raw) ? raw : []).map(function(item){
        return {
          id: item.id,
          title: item.title || '',
          lat: toNumber(item.lat),
          lng: toNumber(item.lng),
          address: item.address || '',
          cuisine: item.cuisine || '',
          url: item.url || '',
          distance: toNumber(item.distance)
        };
      }).filter(function(item){ return item.lat !== null && item.lng !== null; });
    }

    function fetchNearby(lat, lng, radius){
      var base = (cfg.restBase || '').replace(/\/+$/, '');
      var url = base + '/restaurants/nearby?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) + '&radius=' + encodeURIComponent(radius);
      updateStatus(strings.searching || '');
      fetch(url)
        .then(function(res){ return res.json(); })
        .then(function(data){
          var cleaned = cleanMarkers(data);
          renderMarkers(cleaned);
          setUser(lat, lng);
          if (cleaned.length){ updateStatus((strings.found || '').replace('%d', cleaned.length)); }
          else { updateStatus(strings.noResults || ''); }
        })
        .catch(function(){ updateStatus(strings.noGeo || ''); });
    }

    renderMarkers(cleanMarkers(cfg.markers));
    if ((cfg.markers||[]).length){ updateStatus(strings.allLocations || strings.all || ''); }

    if (useBtn){
      if (!navigator.geolocation){
        useBtn.disabled = true;
        useBtn.title = strings.noGeo || '';
      }
      useBtn.addEventListener('click', function(e){
        e.preventDefault();
        if (!navigator.geolocation){ return; }
        useBtn.classList.add('is-loading');
        navigator.geolocation.getCurrentPosition(function(pos){
          useBtn.classList.remove('is-loading');
          var radius = toNumber(radiusInput && radiusInput.value) || cfg.defaultRadius || 5;
          fetchNearby(pos.coords.latitude, pos.coords.longitude, radius);
        }, function(){
          useBtn.classList.remove('is-loading');
          updateStatus(strings.noGeo || '');
        }, { enableHighAccuracy: true, timeout: 8000 });
      });
    }

    if (resetBtn){
      resetBtn.addEventListener('click', function(e){
        e.preventDefault();
        renderMarkers(cleanMarkers(cfg.markers));
        setUser(null, null);
        updateStatus(strings.allLocations || strings.all || '');
      });
    }
  }

  if (document.readyState !== 'loading') {init();}
  else {document.addEventListener('DOMContentLoaded', init);}
})();
