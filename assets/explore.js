(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.getElementById('vc-use-location');
    if (!btn || !navigator.geolocation) return;
    btn.addEventListener('click', function(e){
      e.preventDefault();
      btn.classList.add('is-loading');
      navigator.geolocation.getCurrentPosition(function(pos){
        var lat = pos.coords.latitude.toFixed(6);
        var lng = pos.coords.longitude.toFixed(6);
        var form = btn.closest('form');
        if (!form) return;
        var latInput = form.querySelector('input[name="lat"]');
        var lngInput = form.querySelector('input[name="lng"]');
        var radiusInput = form.querySelector('input[name="radius"]');
        if (latInput && lngInput){
          latInput.value = lat; lngInput.value = lng;
          if (radiusInput && !radiusInput.value) radiusInput.value = 5;
          form.submit();
        }
      }, function(err){
        alert('Não foi possível obter sua localização.');
        btn.classList.remove('is-loading');
      }, { enableHighAccuracy: true, timeout: 8000 });
    });
  });
})();