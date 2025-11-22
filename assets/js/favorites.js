/**
 * Sistema de Favoritos
 * Gerencia favoritos de restaurantes e itens do cardápio
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
   * Verifica se usuário está autenticado
   */
  function isAuthenticated() {
    // Verificar se há nonce (indica que usuário pode estar logado)
    return NONCE && NONCE !== '';
  }

  /**
   * Adiciona restaurante aos favoritos
   */
  async function addRestaurantFavorite(restaurantId) {
    if (!isAuthenticated()) {
      alert('Faça login para adicionar aos favoritos.');
      return false;
    }

    try {
      const response = await fetch(`${REST_BASE}/favorites/restaurants/${restaurantId}`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        // Verificar se a resposta é JSON antes de tentar fazer parse
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const error = await response.json();
          throw new Error(error.message || 'Erro ao adicionar favorito');
        } else {
          // Se não for JSON, provavelmente é HTML (página de erro)
          throw new Error('Erro no servidor. Tente novamente mais tarde.');
        }
      }

      return true;
    } catch (error) {
      console.error('Erro ao adicionar favorito:', error);
      alert(error.message || 'Erro ao adicionar aos favoritos.');
      return false;
    }
  }

  /**
   * Remove restaurante dos favoritos
   */
  async function removeRestaurantFavorite(restaurantId) {
    if (!isAuthenticated()) {
      return false;
    }

    try {
      const response = await fetch(`${REST_BASE}/favorites/restaurants/${restaurantId}`, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        // Verificar se a resposta é JSON antes de tentar fazer parse
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const error = await response.json();
          throw new Error(error.message || 'Erro ao remover favorito');
        } else {
          throw new Error('Erro no servidor. Tente novamente mais tarde.');
        }
      }

      return true;
    } catch (error) {
      console.error('Erro ao remover favorito:', error);
      return false;
    }
  }

  /**
   * Verifica se restaurante está nos favoritos
   */
  async function isRestaurantFavorite(restaurantId) {
    if (!isAuthenticated()) {
      return false;
    }

    try {
      const response = await fetch(`${REST_BASE}/favorites/restaurants`, {
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        return false;
      }

      // Verificar se a resposta é JSON antes de tentar fazer parse
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        console.error('Resposta não é JSON:', contentType);
        return false;
      }

      const data = await response.json();
      const favorites = data.restaurants || [];
      return favorites.some(fav => fav.id === restaurantId);
    } catch (error) {
      console.error('Erro ao verificar favorito:', error);
      return false;
    }
  }

  /**
   * Adiciona item do cardápio aos favoritos
   */
  async function addMenuItemFavorite(itemId) {
    if (!isAuthenticated()) {
      alert('Faça login para adicionar aos favoritos.');
      return false;
    }

    try {
      const response = await fetch(`${REST_BASE}/favorites/menu-items/${itemId}`, {
        method: 'POST',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        // Verificar se a resposta é JSON antes de tentar fazer parse
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const error = await response.json();
          throw new Error(error.message || 'Erro ao adicionar favorito');
        } else {
          throw new Error('Erro no servidor. Tente novamente mais tarde.');
        }
      }

      return true;
    } catch (error) {
      console.error('Erro ao adicionar favorito:', error);
      alert(error.message || 'Erro ao adicionar aos favoritos.');
      return false;
    }
  }

  /**
   * Remove item do cardápio dos favoritos
   */
  async function removeMenuItemFavorite(itemId) {
    if (!isAuthenticated()) {
      return false;
    }

    try {
      const response = await fetch(`${REST_BASE}/favorites/menu-items/${itemId}`, {
        method: 'DELETE',
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        // Verificar se a resposta é JSON antes de tentar fazer parse
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const error = await response.json();
          throw new Error(error.message || 'Erro ao remover favorito');
        } else {
          throw new Error('Erro no servidor. Tente novamente mais tarde.');
        }
      }

      return true;
    } catch (error) {
      console.error('Erro ao remover favorito:', error);
      return false;
    }
  }

  /**
   * Atualiza visual do botão de favorito
   */
  function updateFavoriteButton(button, isFavorite) {
    if (!button) return;

    if (isFavorite) {
      button.classList.add('is-favorite');
      button.setAttribute('aria-label', 'Remover dos favoritos');
      button.innerHTML = '❤️';
    } else {
      button.classList.remove('is-favorite');
      button.setAttribute('aria-label', 'Adicionar aos favoritos');
      button.innerHTML = '🤍';
    }
  }

  /**
   * Carrega lista de favoritos
   */
  async function loadFavorites(type) {
    if (!isAuthenticated()) {
      return null;
    }

    try {
      const endpoint = type === 'menu-items' ? 'menu-items' : 'restaurants';
      const response = await fetch(`${REST_BASE}/favorites/${endpoint}`, {
        headers: {
          'X-WP-Nonce': NONCE,
        },
      });

      if (!response.ok) {
        throw new Error('Erro ao carregar favoritos');
      }

      // Verificar se a resposta é JSON antes de tentar fazer parse
      const contentType = response.headers.get('content-type');
      if (!contentType || !contentType.includes('application/json')) {
        console.error('Resposta não é JSON ao carregar favoritos:', contentType);
        throw new Error('Erro no servidor. A resposta não é válida.');
      }

      const data = await response.json();
      return type === 'menu-items' ? (data.menu_items || []) : (data.restaurants || []);
    } catch (error) {
      console.error('Erro ao carregar favoritos:', error);
      return null;
    }
  }

  /**
   * Renderiza lista de favoritos
   */
  function renderFavoritesList(container, favorites, type) {
    if (!favorites || favorites.length === 0) {
      container.innerHTML = `
        <div class="vc-favorites-empty">
          <p>${type === 'restaurants' ? 'Você ainda não tem restaurantes favoritos.' : 'Você ainda não tem itens favoritos.'}</p>
        </div>
      `;
      return;
    }

    let html = '';
    if (type === 'restaurants') {
      favorites.forEach((restaurant) => {
        const image = restaurant.image || '';
        const address = restaurant.address || '';
        html += `
          <div class="vc-card">
            ${image ? `<div class="vc-card__thumb"><img src="${image}" alt="${restaurant.name || ''}" loading="lazy" /></div>` : ''}
            <div class="vc-card__body">
              <h3 class="vc-card__title">${restaurant.name || ''}</h3>
              ${address ? `<p class="vc-card__line">${address}</p>` : ''}
              <a href="${restaurant.url || '#'}" class="vc-btn">Ver cardápio</a>
            </div>
          </div>
        `;
      });
    } else {
      favorites.forEach((item) => {
        const image = item.image || '';
        const price = item.price || '';
        html += `
          <div class="vc-card">
            ${image ? `<div class="vc-card__thumb"><img src="${image}" alt="${item.name || ''}" loading="lazy" /></div>` : ''}
            <div class="vc-card__body">
              <h3 class="vc-card__title">${item.name || ''}</h3>
              ${price ? `<p class="vc-card__line">${price}</p>` : ''}
            </div>
          </div>
        `;
      });
    }

    container.innerHTML = html;
  }

  // Inicializar quando DOM estiver pronto
  document.addEventListener('DOMContentLoaded', () => {
    // Carregar estado inicial dos favoritos para todos os botões
    document.querySelectorAll('.vc-favorite-btn[data-restaurant-id]').forEach(async (btn) => {
      const restaurantId = Number(btn.dataset.restaurantId);
      if (restaurantId) {
        const isFav = await isRestaurantFavorite(restaurantId);
        updateFavoriteButton(btn, isFav);
      }
    });

    // Carregar lista de favoritos se houver container
    const favoritesContainer = document.querySelector('.vc-favorites');
    if (favoritesContainer) {
      const type = favoritesContainer.dataset.type || 'restaurants';
      const listContainer = document.getElementById('vc-favorites-list');
      
      if (listContainer) {
        loadFavorites(type).then((favorites) => {
          if (favorites !== null) {
            renderFavoritesList(listContainer, favorites, type);
          } else {
            listContainer.innerHTML = '<div class="vc-favorites-empty"><p>Erro ao carregar favoritos.</p></div>';
          }
        });
      }
    }

    // Handler para botões de favorito de restaurante
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.vc-favorite-btn[data-restaurant-id]');
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      const restaurantId = Number(btn.dataset.restaurantId);
      if (!restaurantId) return;

      const isCurrentlyFavorite = btn.classList.contains('is-favorite');

      if (isCurrentlyFavorite) {
        const success = await removeRestaurantFavorite(restaurantId);
        if (success) {
          updateFavoriteButton(btn, false);
        }
      } else {
        const success = await addRestaurantFavorite(restaurantId);
        if (success) {
          updateFavoriteButton(btn, true);
        }
      }
    });

    // Handler para botões de favorito de item do cardápio
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.vc-favorite-btn[data-menu-item-id]');
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      const itemId = Number(btn.dataset.menuItemId);
      if (!itemId) return;

      const isCurrentlyFavorite = btn.classList.contains('is-favorite');

      if (isCurrentlyFavorite) {
        const success = await removeMenuItemFavorite(itemId);
        if (success) {
          updateFavoriteButton(btn, false);
        }
      } else {
        const success = await addMenuItemFavorite(itemId);
        if (success) {
          updateFavoriteButton(btn, true);
        }
      }
    });
  });

})();

