(function($){
  $(function(){
    var lat = $('input[name="rc_shipping_lat"]');
    var lng = $('input[name="rc_shipping_lng"]');
    if (!lat.length || !lng.length || !navigator.geolocation) return;

    // Mantemos este helper para cenários onde o cliente quer apenas GPS
    var html = '<p class="form-row form-row-wide"><a href="#" id="vc-btn-geo" class="button">Usar minha localização</a></p>';
    $(html).insertAfter(lat.closest('.form-row').parent());

    $('#vc-btn-geo').on('click', function(e){
      e.preventDefault();
      var $btn = $(this);
      $btn.prop('disabled', true).text('Localizando...');
      navigator.geolocation.getCurrentPosition(function(pos){
        lat.val(pos.coords.latitude.toFixed(6));
        lng.val(pos.coords.longitude.toFixed(6));
        $btn.prop('disabled', false).text('Usar minha localização');
        $(document.body).trigger('update_checkout');
      }, function(){
        alert('Não foi possível obter sua localização.');
        $btn.prop('disabled', false).text('Usar minha localização');
      }, { enableHighAccuracy:true, timeout:8000 });
    });
  });
})(jQuery);