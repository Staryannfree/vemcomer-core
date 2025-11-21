/**
 * Sistema de Notificações
 * Carrega e gerencia notificações do usuário
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
   * Carrega notificações do usuário
   */
  async function loadNotifications(page = 1, perPage = 10) {
    try {
      const response = await fetch(`${REST_BASE}/notifications?per_page=${perPage}&page=${page}`, {
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao carregar notificações');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Erro ao carregar notificações:', error);
      return null;
    }
  }

  /**
   * Marca notificação como lida
   */
  async function markAsRead(notificationId) {
    try {
      const response = await fetch(`${REST_BASE}/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao marcar notificação como lida');
      }

      return true;
    } catch (error) {
      console.error('Erro ao marcar notificação como lida:', error);
      return false;
    }
  }

  /**
   * Marca todas as notificações como lidas
   */
  async function markAllAsRead() {
    try {
      const response = await fetch(`${REST_BASE}/notifications/read-all`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao marcar todas como lidas');
      }

      return true;
    } catch (error) {
      console.error('Erro ao marcar todas como lidas:', error);
      return false;
    }
  }

  /**
   * Formata data relativa
   */
  function formatRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) {
      return 'Agora';
    } else if (diffMins < 60) {
      return `${diffMins} min atrás`;
    } else if (diffHours < 24) {
      return `${diffHours}h atrás`;
    } else if (diffDays < 7) {
      return `${diffDays} dias atrás`;
    } else {
      return date.toLocaleDateString('pt-BR');
    }
  }

  /**
   * Obtém ícone baseado no tipo de notificação
   */
  function getNotificationIcon(type) {
    const icons = {
      'new_order': '🛵',
      'order_status': '📦',
      'restaurant_open': '🟢',
      'promotion': '🎉',
      'default': '🔔',
    };
    return icons[type] || icons.default;
  }

  /**
   * Renderiza lista de notificações
   */
  function renderNotifications(container, notifications, unreadCount) {
    if (!notifications || notifications.length === 0) {
      container.innerHTML = '<div class="vc-notifications-empty"><p>Nenhuma notificação.</p></div>';
      return;
    }

    let html = '';
    notifications.forEach((notification) => {
      const isUnread = !notification.read;
      const icon = getNotificationIcon(notification.type || 'default');
      const time = formatRelativeTime(notification.created_at || notification.date || new Date().toISOString());

      html += `
        <div class="vc-notification-item ${isUnread ? 'is-unread' : 'is-read'}" data-notification-id="${notification.id}">
          <div class="vc-notification-item__icon">${icon}</div>
          <div class="vc-notification-item__content">
            <h4 class="vc-notification-item__title">${notification.title || 'Notificação'}</h4>
            <p class="vc-notification-item__message">${notification.message || ''}</p>
            <div class="vc-notification-item__meta">
              <span class="vc-notification-item__time">${time}</span>
            </div>
          </div>
          ${isUnread ? `
            <div class="vc-notification-item__actions">
              <button class="vc-notification-item__read-btn" data-notification-id="${notification.id}">Marcar como lida</button>
            </div>
          ` : ''}
        </div>
      `;
    });

    container.innerHTML = html;

    // Atualizar badge
    const badge = document.getElementById('vc-notifications-badge');
    if (badge) {
      if (unreadCount > 0) {
        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badge.style.display = 'inline-block';
      } else {
        badge.style.display = 'none';
      }
    }

    // Mostrar/ocultar botão "marcar todas"
    const markAllBtn = document.getElementById('vc-notifications-mark-all');
    if (markAllBtn) {
      markAllBtn.style.display = unreadCount > 0 ? 'inline-block' : 'none';
    }

    // Vincular eventos
    container.querySelectorAll('.vc-notification-item__read-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const notificationId = Number(btn.dataset.notificationId);
        const success = await markAsRead(notificationId);
        if (success) {
          // Recarregar notificações
          const notificationsContainer = document.querySelector('.vc-notifications');
          if (notificationsContainer) {
            const perPage = Number(notificationsContainer.dataset.perPage || 10);
            const data = await loadNotifications(1, perPage);
            if (data) {
              renderNotifications(container, data.notifications || [], data.unread_count || 0);
            }
          }
        }
      });
    });

    // Marcar como lida ao clicar no item
    container.querySelectorAll('.vc-notification-item').forEach(item => {
      item.addEventListener('click', async () => {
        const notificationId = Number(item.dataset.notificationId);
        const isUnread = item.classList.contains('is-unread');
        if (isUnread) {
          const success = await markAsRead(notificationId);
          if (success) {
            item.classList.remove('is-unread');
            item.classList.add('is-read');
            const readBtn = item.querySelector('.vc-notification-item__read-btn');
            if (readBtn) {
              readBtn.remove();
            }
          }
        }
      });
    });
  }

  // Inicializar quando DOM estiver pronto
  document.addEventListener('DOMContentLoaded', () => {
    const notificationsContainer = document.querySelector('.vc-notifications');
    if (!notificationsContainer) {
      return;
    }

    const perPage = Number(notificationsContainer.dataset.perPage || 10);
    const listContainer = document.getElementById('vc-notifications-list');

    if (!listContainer) {
      return;
    }

    // Carregar notificações iniciais
    loadNotifications(1, perPage).then((data) => {
      if (data) {
        renderNotifications(listContainer, data.notifications || [], data.unread_count || 0);
      } else {
        listContainer.innerHTML = '<div class="vc-notifications-empty"><p>Erro ao carregar notificações.</p></div>';
      }
    });

    // Handler para "marcar todas como lidas"
    const markAllBtn = document.getElementById('vc-notifications-mark-all');
    if (markAllBtn) {
      markAllBtn.addEventListener('click', async () => {
        const success = await markAllAsRead();
        if (success) {
          const data = await loadNotifications(1, perPage);
          if (data) {
            renderNotifications(listContainer, data.notifications || [], data.unread_count || 0);
          }
        }
      });
    }
  });

})();

