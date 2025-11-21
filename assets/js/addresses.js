/**
 * Sistema de Endereços de Entrega
 * Gerencia endereços salvos do usuário
 * @package VemComerCore
 */

(function() {
  'use strict';

  if (typeof window === 'undefined' || !window.VemComer) {
    console.warn('VemComer REST helpers indisponíveis.');
    return;
  }

  const REST_BASE = window.VemComer.rest.base;
  const NONCE = window.VemComer.nonce;

  /**
   * Carrega endereços do usuário
   */
  async function loadAddresses() {
    try {
      const response = await fetch(`${REST_BASE}/addresses`, {
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao carregar endereços');
      }

      const data = await response.json();
      return data.addresses || [];
    } catch (error) {
      console.error('Erro ao carregar endereços:', error);
      return [];
    }
  }

  /**
   * Cria novo endereço
   */
  async function createAddress(addressData) {
    try {
      const response = await fetch(`${REST_BASE}/addresses`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': NONCE,
        },
        body: JSON.stringify(addressData),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Erro ao criar endereço');
      }

      return await response.json();
    } catch (error) {
      console.error('Erro ao criar endereço:', error);
      throw error;
    }
  }

  /**
   * Atualiza endereço existente
   */
  async function updateAddress(addressId, addressData) {
    try {
      const response = await fetch(`${REST_BASE}/addresses/${addressId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': NONCE,
        },
        body: JSON.stringify(addressData),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Erro ao atualizar endereço');
      }

      return await response.json();
    } catch (error) {
      console.error('Erro ao atualizar endereço:', error);
      throw error;
    }
  }

  /**
   * Deleta endereço
   */
  async function deleteAddress(addressId) {
    try {
      const response = await fetch(`${REST_BASE}/addresses/${addressId}`, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao deletar endereço');
      }

      return true;
    } catch (error) {
      console.error('Erro ao deletar endereço:', error);
      throw error;
    }
  }

  /**
   * Define endereço como principal
   */
  async function setPrimaryAddress(addressId) {
    try {
      const response = await fetch(`${REST_BASE}/addresses/${addressId}/set-primary`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao definir endereço principal');
      }

      return true;
    } catch (error) {
      console.error('Erro ao definir endereço principal:', error);
      throw error;
    }
  }

  /**
   * Renderiza lista de endereços
   */
  function renderAddressesList(container, addresses, onSelect, onEdit, onDelete) {
    if (!addresses || addresses.length === 0) {
      container.innerHTML = '<p class="vc-addresses-empty">Nenhum endereço cadastrado.</p>';
      return;
    }

    let html = '<div class="vc-addresses-list">';
    addresses.forEach((address) => {
      const isPrimary = address.is_primary || false;
      html += `
        <div class="vc-address-item ${isPrimary ? 'is-primary' : ''}" data-address-id="${address.id}">
          <div class="vc-address-item__header">
            <h4 class="vc-address-item__title">${address.name || 'Endereço'}</h4>
            ${isPrimary ? '<span class="vc-badge vc-badge--primary">Principal</span>' : ''}
          </div>
          <div class="vc-address-item__content">
            <p>${address.street || ''}, ${address.number || ''}${address.complement ? ' - ' + address.complement : ''}</p>
            <p>${address.neighborhood || ''} - ${address.city || ''}</p>
            <p>CEP: ${address.postcode || ''}</p>
          </div>
          <div class="vc-address-item__actions">
            ${onSelect ? `<button class="vc-btn vc-btn--small vc-address-select" data-address-id="${address.id}">Usar este endereço</button>` : ''}
            ${onEdit ? `<button class="vc-btn vc-btn--small vc-btn--ghost vc-address-edit" data-address-id="${address.id}">Editar</button>` : ''}
            ${onDelete ? `<button class="vc-btn vc-btn--small vc-btn--ghost vc-address-delete" data-address-id="${address.id}">Excluir</button>` : ''}
            ${!isPrimary ? `<button class="vc-btn vc-btn--small vc-btn--ghost vc-address-primary" data-address-id="${address.id}">Definir como principal</button>` : ''}
          </div>
        </div>
      `;
    });
    html += '</div>';

    container.innerHTML = html;

    // Vincular eventos
    if (onSelect) {
      container.querySelectorAll('.vc-address-select').forEach(btn => {
        btn.addEventListener('click', () => {
          const addressId = Number(btn.dataset.addressId);
          const address = addresses.find(a => a.id === addressId);
          if (address && onSelect) {
            onSelect(address);
          }
        });
      });
    }

    if (onEdit) {
      container.querySelectorAll('.vc-address-edit').forEach(btn => {
        btn.addEventListener('click', () => {
          const addressId = Number(btn.dataset.addressId);
          const address = addresses.find(a => a.id === addressId);
          if (address && onEdit) {
            onEdit(address);
          }
        });
      });
    }

    if (onDelete) {
      container.querySelectorAll('.vc-address-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
          if (!confirm('Tem certeza que deseja excluir este endereço?')) {
            return;
          }
          const addressId = Number(btn.dataset.addressId);
          try {
            await deleteAddress(addressId);
            if (container) {
              const addresses = await loadAddresses();
              renderAddressesList(container, addresses, onSelect, onEdit, onDelete);
            }
          } catch (error) {
            alert('Erro ao excluir endereço: ' + error.message);
          }
        });
      });
    }

    container.querySelectorAll('.vc-address-primary').forEach(btn => {
      btn.addEventListener('click', async () => {
        const addressId = Number(btn.dataset.addressId);
        try {
          await setPrimaryAddress(addressId);
          if (container) {
            const addresses = await loadAddresses();
            renderAddressesList(container, addresses, onSelect, onEdit, onDelete);
          }
        } catch (error) {
          alert('Erro ao definir endereço principal: ' + error.message);
        }
      });
    });
  }

  // Expor funções globalmente
  window.VemComerAddresses = {
    load: loadAddresses,
    create: createAddress,
    update: updateAddress,
    delete: deleteAddress,
    setPrimary: setPrimaryAddress,
    render: renderAddressesList,
  };

})();

