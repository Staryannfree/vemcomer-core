/**
 * Histórico de Pedidos
 * Carrega e exibe histórico de pedidos do usuário
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
   * Carrega pedidos do usuário
   */
  async function loadOrders(page = 1, perPage = 10, status = '') {
    try {
      let url = `${REST_BASE}/orders?per_page=${perPage}&page=${page}`;
      if (status) {
        url += `&status=${encodeURIComponent(status)}`;
      }

      const response = await fetch(url, {
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao carregar pedidos');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Erro ao carregar pedidos:', error);
      return null;
    }
  }

  /**
   * Formata data
   */
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  /**
   * Formata status
   */
  function formatStatus(status) {
    const statusMap = {
      'vc-pending': { label: 'Pendente', class: 'pending' },
      'vc-confirmed': { label: 'Confirmado', class: 'confirmed' },
      'vc-preparing': { label: 'Preparando', class: 'preparing' },
      'vc-ready': { label: 'Pronto', class: 'ready' },
      'vc-delivering': { label: 'Em entrega', class: 'delivering' },
      'vc-completed': { label: 'Concluído', class: 'completed' },
      'vc-cancelled': { label: 'Cancelado', class: 'cancelled' },
    };

    return statusMap[status] || { label: status, class: 'pending' };
  }

  /**
   * Formata preço
   */
  function formatPrice(price) {
    return 'R$ ' + parseFloat(price).toFixed(2).replace('.', ',');
  }

  /**
   * Renderiza lista de pedidos
   */
  function renderOrders(container, orders) {
    if (!orders || orders.length === 0) {
      container.innerHTML = '<div class="vc-orders-empty"><p>Nenhum pedido encontrado.</p></div>';
      return;
    }

    let html = '';
    orders.forEach((order) => {
      const statusInfo = formatStatus(order.status || 'vc-pending');
      const date = formatDate(order.date || order.created_at || new Date().toISOString());
      const restaurantName = order.restaurant_name || 'Restaurante';
      const items = order.items || [];
      const total = order.total || order.subtotal || 0;
      const deliveryFee = order.delivery_fee || 0;
      const discount = order.discount || 0;

      html += `
        <div class="vc-order-item" data-order-id="${order.id}">
          <div class="vc-order-item__header">
            <div class="vc-order-item__info">
              <h3 class="vc-order-item__number">Pedido #${order.id}</h3>
              <p class="vc-order-item__date">${date}</p>
            </div>
            <span class="vc-order-item__status vc-order-item__status--${statusInfo.class}">${statusInfo.label}</span>
          </div>
          <div class="vc-order-item__restaurant">
            <strong>Restaurante:</strong> ${restaurantName}
          </div>
          ${items.length > 0 ? `
            <div class="vc-order-item__items">
              <h4 class="vc-order-item__items-title">Itens do pedido:</h4>
              <ul class="vc-order-item__items-list">
                ${items.map(item => `
                  <li>
                    <span>${item.quantity || 1}x ${item.name || item.title || 'Item'}</span>
                    <strong>${formatPrice((item.price || 0) * (item.quantity || 1))}</strong>
                  </li>
                `).join('')}
              </ul>
            </div>
          ` : ''}
          <div class="vc-order-item__summary">
            <div>
              ${deliveryFee > 0 ? `<p style="font-size: 0.9rem; color: #6b7280; margin: 4px 0;">Frete: ${formatPrice(deliveryFee)}</p>` : ''}
              ${discount > 0 ? `<p style="font-size: 0.9rem; color: #059669; margin: 4px 0;">Desconto: -${formatPrice(discount)}</p>` : ''}
              <div class="vc-order-item__total">Total: ${formatPrice(total)}</div>
            </div>
            <div class="vc-order-item__actions">
              <a href="${order.tracking_url || '#'}" class="vc-btn vc-btn--small">Acompanhar</a>
            </div>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  /**
   * Renderiza paginação
   */
  function renderPagination(container, currentPage, totalPages, onPageChange) {
    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }

    let html = '';

    if (currentPage > 1) {
      html += `<button class="vc-pagination-prev" data-page="${currentPage - 1}">« Anterior</button>`;
    }

    // Mostrar algumas páginas ao redor da atual
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    if (startPage > 1) {
      html += `<button class="vc-pagination-page" data-page="1">1</button>`;
      if (startPage > 2) {
        html += `<span>...</span>`;
      }
    }

    for (let i = startPage; i <= endPage; i++) {
      html += `<button class="vc-pagination-page ${i === currentPage ? 'is-active' : ''}" data-page="${i}">${i}</button>`;
    }

    if (endPage < totalPages) {
      if (endPage < totalPages - 1) {
        html += `<span>...</span>`;
      }
      html += `<button class="vc-pagination-page" data-page="${totalPages}">${totalPages}</button>`;
    }

    if (currentPage < totalPages) {
      html += `<button class="vc-pagination-next" data-page="${currentPage + 1}">Próxima »</button>`;
    }

    container.innerHTML = html;

    // Vincular eventos
    container.querySelectorAll('button[data-page]').forEach(btn => {
      btn.addEventListener('click', () => {
        const page = Number(btn.dataset.page);
        if (onPageChange) {
          onPageChange(page);
        }
      });
    });
  }

  // Inicializar quando DOM estiver pronto
  document.addEventListener('DOMContentLoaded', () => {
    const ordersContainer = document.querySelector('.vc-orders-history');
    if (!ordersContainer) {
      return;
    }

    const perPage = Number(ordersContainer.dataset.perPage || 10);
    const initialStatus = ordersContainer.dataset.status || '';
    const listContainer = document.getElementById('vc-orders-list');
    const paginationContainer = document.getElementById('vc-orders-pagination');
    const statusFilter = document.getElementById('vc-orders-status-filter');

    if (!listContainer) {
      return;
    }

    let currentPage = 1;
    let currentStatus = initialStatus;

    // Função para carregar e renderizar pedidos
    async function refreshOrders(page = 1) {
      currentPage = page;
      listContainer.innerHTML = '<p class="vc-orders-history__loading">Carregando pedidos...</p>';

      const data = await loadOrders(page, perPage, currentStatus);
      if (data) {
        renderOrders(listContainer, data.orders || []);
        if (paginationContainer) {
          renderPagination(paginationContainer, page, data.total_pages || 1, (newPage) => {
            refreshOrders(newPage);
          });
        }
      } else {
        listContainer.innerHTML = '<div class="vc-orders-empty"><p>Erro ao carregar pedidos.</p></div>';
      }
    }

    // Carregar pedidos iniciais
    refreshOrders(1);

    // Handler para filtro de status
    if (statusFilter) {
      statusFilter.addEventListener('change', () => {
        currentStatus = statusFilter.value;
        refreshOrders(1);
      });
    }
  });

})();

