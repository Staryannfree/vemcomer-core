/**
 * Reviews - Carregamento e criação de avaliações
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
   * Renderiza estrelas de rating
   */
  function renderStars(rating) {
    const avg = parseFloat(rating) || 0;
    const avgRounded = Math.round(avg * 2) / 2;
    const fullStars = Math.floor(avgRounded);
    const halfStar = (avgRounded - fullStars) >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

    let html = '';
    for (let i = 0; i < fullStars; i++) {
      html += '<span class="vc-star vc-star--full">★</span>';
    }
    if (halfStar) {
      html += '<span class="vc-star vc-star--half">★</span>';
    }
    for (let i = 0; i < emptyStars; i++) {
      html += '<span class="vc-star vc-star--empty">☆</span>';
    }
    return html;
  }

  /**
   * Carrega reviews de um restaurante
   */
  async function loadReviews(restaurantId, page = 1, perPage = 10) {
    const url = `${REST_BASE}/restaurants/${restaurantId}/reviews?per_page=${perPage}&page=${page}`;
    
    try {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Erro ao carregar reviews:', error);
      return null;
    }
  }

  /**
   * Renderiza lista de reviews
   */
  function renderReviews(container, reviews, restaurantId) {
    if (!reviews || reviews.length === 0) {
      container.innerHTML = '<p class="vc-reviews__empty">' + 'Nenhuma avaliação ainda.' + '</p>';
      return;
    }

    let html = '<div class="vc-reviews__items">';
    reviews.forEach((review) => {
      const date = review.date ? new Date(review.date).toLocaleDateString('pt-BR') : '';
      html += `
        <div class="vc-reviews__item">
          <div class="vc-reviews__item-header">
            <div class="vc-reviews__item-author">
              <strong>${review.customer_name || 'Anônimo'}</strong>
              ${date ? `<span class="vc-reviews__item-date">${date}</span>` : ''}
            </div>
            <div class="vc-reviews__item-rating">
              ${renderStars(review.rating)}
            </div>
          </div>
          ${review.comment ? `<div class="vc-reviews__item-comment">${review.comment}</div>` : ''}
        </div>
      `;
    });
    html += '</div>';

    container.innerHTML = html;
  }

  /**
   * Renderiza paginação
   */
  function renderPagination(container, currentPage, totalPages, restaurantId, perPage) {
    if (totalPages <= 1) {
      container.innerHTML = '';
      return;
    }

    let html = '<div class="vc-pagination">';
    
    if (currentPage > 1) {
      html += `<button class="vc-pagination__prev" data-page="${currentPage - 1}">« Anterior</button>`;
    }
    
    html += `<span class="vc-pagination__info">Página ${currentPage} de ${totalPages}</span>`;
    
    if (currentPage < totalPages) {
      html += `<button class="vc-pagination__next" data-page="${currentPage + 1}">Próxima »</button>`;
    }
    
    html += '</div>';

    container.innerHTML = html;

    // Vincular eventos
    container.querySelectorAll('.vc-pagination__prev, .vc-pagination__next').forEach(btn => {
      btn.addEventListener('click', async () => {
        const page = Number(btn.dataset.page);
        const reviewsContainer = document.getElementById('vc-reviews-list');
        reviewsContainer.innerHTML = '<p class="vc-reviews__loading">Carregando...</p>';
        
        const data = await loadReviews(restaurantId, page, perPage);
        if (data) {
          renderReviews(reviewsContainer, data.reviews, restaurantId);
          renderPagination(container, page, data.total_pages || 1, restaurantId, perPage);
        }
      });
    });
  }

  /**
   * Cria uma nova review
   */
  async function createReview(restaurantId, rating, comment) {
    const url = `${REST_BASE}/reviews`;
    
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': NONCE,
        },
        body: JSON.stringify({
          restaurant_id: restaurantId,
          rating: rating,
          comment: comment || '',
        }),
      });

      if (!response.ok) {
        const error = await response.json();
        throw new Error(error.message || 'Erro ao criar avaliação');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Erro ao criar review:', error);
      throw error;
    }
  }

  // Inicializar quando DOM estiver pronto
  document.addEventListener('DOMContentLoaded', () => {
    const reviewsContainer = document.querySelector('.vc-reviews');
    if (!reviewsContainer) {
      return;
    }

    const restaurantId = Number(reviewsContainer.dataset.restaurantId);
    const perPage = Number(reviewsContainer.dataset.perPage || 10);

    if (!restaurantId) {
      return;
    }

    // Carregar reviews iniciais
    loadReviews(restaurantId, 1, perPage).then((data) => {
      if (data) {
        const listContainer = document.getElementById('vc-reviews-list');
        const paginationContainer = document.getElementById('vc-reviews-pagination');
        
        if (listContainer) {
          renderReviews(listContainer, data.reviews, restaurantId);
        }
        
        if (paginationContainer) {
          renderPagination(paginationContainer, 1, data.total_pages || 1, restaurantId, perPage);
        }
      }
    });

    // Handler do formulário de review
    const form = document.getElementById('vc-review-form');
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const rating = Number(form.querySelector('input[name="rating"]:checked')?.value);
        const comment = form.querySelector('textarea[name="comment"]')?.value || '';
        const messageBox = form.querySelector('.vc-reviews__form-message');
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!rating) {
          if (messageBox) {
            messageBox.innerHTML = '<p class="vc-error">Selecione uma avaliação.</p>';
          }
          return;
        }

        // Desabilitar botão
        submitBtn.disabled = true;
        submitBtn.textContent = 'Enviando...';
        if (messageBox) {
          messageBox.innerHTML = '';
        }

        try {
          await createReview(restaurantId, rating, comment);
          
          // Sucesso
          if (messageBox) {
            messageBox.innerHTML = '<p class="vc-success">Avaliação enviada com sucesso! Aguarde aprovação.</p>';
          }
          
          // Limpar formulário
          form.reset();
          
          // Recarregar reviews após 1 segundo
          setTimeout(() => {
            loadReviews(restaurantId, 1, perPage).then((data) => {
              if (data) {
                const listContainer = document.getElementById('vc-reviews-list');
                const paginationContainer = document.getElementById('vc-reviews-pagination');
                
                if (listContainer) {
                  renderReviews(listContainer, data.reviews, restaurantId);
                }
                
                if (paginationContainer) {
                  renderPagination(paginationContainer, 1, data.total_pages || 1, restaurantId, perPage);
                }
              }
            });
          }, 1000);
        } catch (error) {
          if (messageBox) {
            messageBox.innerHTML = '<p class="vc-error">' + error.message + '</p>';
          }
        } finally {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Enviar avaliação';
        }
      });

      // Melhorar UX do input de rating
      const ratingInputs = form.querySelectorAll('input[name="rating"]');
      ratingInputs.forEach((input, index) => {
        input.addEventListener('change', () => {
          // Atualizar visual das estrelas
          ratingInputs.forEach((inp, idx) => {
            const label = form.querySelector(`label[for="${inp.id}"]`);
            if (label) {
              if (idx < 5 - Number(input.value) + 1) {
                label.classList.add('is-selected');
              } else {
                label.classList.remove('is-selected');
              }
            }
          });
        });
      });
    }
  });

})();

