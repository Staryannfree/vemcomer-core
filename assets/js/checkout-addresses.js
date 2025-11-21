/**
 * Integração de Endereços no Checkout
 * Gerencia seleção de endereços salvos no checkout
 * @package VemComerCore
 */

(function() {
  'use strict';

  if (typeof window === 'undefined' || !window.VemComerAddresses) {
    return;
  }

  // Inicializar quando DOM estiver pronto
  document.addEventListener('DOMContentLoaded', () => {
    const checkout = document.querySelector('.vc-checkout');
    if (!checkout) return;

    const loadAddressesBtn = document.getElementById('vc-load-addresses');
    const addAddressBtn = document.getElementById('vc-add-address');
    const addressesContainer = checkout.querySelector('.vc-addresses-list-container');

    if (!loadAddressesBtn || !addAddressBtn || !addressesContainer) return;

    // Carregar endereços ao clicar
    loadAddressesBtn.addEventListener('click', async () => {
      if (addressesContainer.style.display === 'none') {
        addressesContainer.style.display = 'block';
        addressesContainer.innerHTML = '<p>Carregando endereços...</p>';

        const addresses = await window.VemComerAddresses.load();
        window.VemComerAddresses.render(addressesContainer, addresses, (address) => {
          // Ao selecionar endereço, preencher campos
          const addressInput = checkout.querySelector('.vc-customer-address');
          const zipInput = checkout.querySelector('.vc-zip');
          
          if (addressInput) {
            addressInput.value = `${address.street || ''}, ${address.number || ''}${address.complement ? ' - ' + address.complement : ''} - ${address.neighborhood || ''}, ${address.city || ''}`;
          }
          if (zipInput) {
            zipInput.value = address.postcode || '';
          }

          // Salvar endereço selecionado no dataset
          checkout.dataset.selectedAddress = JSON.stringify(address);
          checkout.dataset.customerLat = address.lat || '';
          checkout.dataset.customerLng = address.lng || '';

          // Ocultar lista
          addressesContainer.style.display = 'none';

          // Disparar evento para recalcular frete se necessário
          const quoteBtn = checkout.querySelector('.vc-quote');
          if (quoteBtn) {
            quoteBtn.click();
          }
        }, null, null);
      } else {
        addressesContainer.style.display = 'none';
      }
    });

    // Adicionar novo endereço (abrir modal ou formulário)
    addAddressBtn.addEventListener('click', () => {
      // Por enquanto, apenas focar no campo de endereço
      const addressInput = checkout.querySelector('.vc-customer-address');
      if (addressInput) {
        addressInput.focus();
      }
    });
  });

})();

