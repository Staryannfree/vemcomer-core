(function(){
  function init(){
    if (typeof VC_RESTAURANT_MAP === 'undefined') return;
    var el = document.getElementById('vc-restaurant-map');
    if (!el) return;
    var m = L.map(el).setView([VC_RESTAURANT_MAP.lat, VC_RESTAURANT_MAP.lng], 15);
    var tiles = VC_RESTAURANT_MAP.tiles || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    L.tileLayer(tiles, {maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'}).addTo(m);
    var mk = L.marker([VC_RESTAURANT_MAP.lat, VC_RESTAURANT_MAP.lng]).addTo(m);
    mk.bindPopup('<strong>'+VC_RESTAURANT_MAP.title+'</strong>').openPopup();
  }
  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);
})();