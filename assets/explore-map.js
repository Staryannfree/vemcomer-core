(function(){
  function init(){
    if (typeof VC_EXPLORE_MAP === 'undefined') {return;}
    var el = document.getElementById('vc-map');
    if (!el) {return;}
    var m = L.map(el);
    var tiles = VC_EXPLORE_MAP.tiles || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    L.tileLayer(tiles, {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(m);

    var markers = VC_EXPLORE_MAP.markers || [];
    var bounds = [];
    markers.forEach(function(r){
      var mk = L.marker([r.lat, r.lng]).addTo(m);
      mk.bindPopup('<strong>'+r.title+'</strong><br><a href="'+r.url+'">Ver cardápio</a>');
      bounds.push([r.lat, r.lng]);
    });
    if (VC_EXPLORE_MAP.user && VC_EXPLORE_MAP.user.lat && VC_EXPLORE_MAP.user.lng){
      var u = VC_EXPLORE_MAP.user;
      L.circle([u.lat, u.lng], {radius: 200, color:'#111827'}).addTo(m);
      bounds.push([u.lat, u.lng]);
    }
    if (bounds.length) {m.fitBounds(bounds, {padding: [20,20]});}
    else {m.setView([0,0], 2);}
  }
  if (document.readyState !== 'loading') {init();}
  else {document.addEventListener('DOMContentLoaded', init);}
})();
