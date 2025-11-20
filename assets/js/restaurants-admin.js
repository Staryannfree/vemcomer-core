(function($){
  $(function(){
    // Pequena melhoria UX: aviso de CNPJ duplicado na tela de edição
    const formatCnpj = (value) => {
      const digits = (value || '').replace(/\D/g,'').slice(0,14);
      const parts = [
        digits.slice(0,2),
        digits.slice(2,5),
        digits.slice(5,8),
        digits.slice(8,12),
        digits.slice(12,14),
      ];

      let formatted = parts[0] || '';
      if(parts[1]) { formatted += '.' + parts[1]; }
      if(parts[2]) { formatted += '.' + parts[2]; }
      if(parts[3]) { formatted += '/' + parts[3]; }
      if(parts[4]) { formatted += '-' + parts[4]; }

      return formatted;
    };

    const sanitizePhone = (value) => {
      return (value || '').replace(/\D/g, '').slice(0, 11);
    };

    const $cnpj = $('#vc_restaurant_cnpj');
    $cnpj.attr('maxlength', '18');
    $cnpj.on('input', function(){
      const formatted = formatCnpj($(this).val());
      $(this).val(formatted);
    });
    $cnpj.on('blur', function(){
      const val = $(this).val().trim();
      if(!val) {return;}
      // Somente validação de formato básico; validação de duplicidade exigiria endpoint REST/busca
      const justNums = val.replace(/\D/g,'');
      if(justNums.length < 14){
        alert('CNPJ parece incompleto (14 dígitos).');
      }
    });

    const $whatsapp = $('#vc_restaurant_whatsapp');
    $whatsapp.attr('placeholder', '61981872528');
    $whatsapp.attr('maxlength', '11');
    $whatsapp.on('input', function(){
      $(this).val(sanitizePhone($(this).val()));
    });
  });
})(jQuery);
