(function($){
  $(function(){
    // Pequena melhoria UX: aviso de CNPJ duplicado na tela de edição
    const $cnpj = $('#vc_restaurant_cnpj');
    $cnpj.on('blur', function(){
      const val = $(this).val().trim();
      if(!val) return;
      // Somente validação de formato básico; validação de duplicidade exigiria endpoint REST/busca
      const justNums = val.replace(/\D/g,'');
      if(justNums.length < 14){
        alert('CNPJ parece incompleto (14 dígitos).');
      }
    });
  });
})(jQuery);
