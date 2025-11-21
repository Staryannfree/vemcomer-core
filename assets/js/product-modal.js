/**
 * Modal de Produto com Modificadores
 * Integra com REST API para carregar e validar modificadores
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
   * Classe principal do Modal de Produto
   */
  class ProductModal {
    constructor() {
      this.modal = null;
      this.currentItem = null;
      this.modifiers = [];
      this.selectedModifiers = {};
      this.basePrice = 0;
      this.init();
    }

    init() {
      // Criar estrutura do modal
      this.createModal();
      
      // Event listeners
      document.addEventListener('click', (e) => {
        const btn = e.target.closest('.vc-menu-item__add, .vc-add[data-item-id], .vc-btn.vc-add[data-item-id]');
        if (btn) {
          e.preventDefault();
          e.stopPropagation();
          const itemId = btn.dataset.itemId || btn.getAttribute('data-item-id');
          if (itemId) {
            this.open(btn);
          }
        }
      });

      // Fechar ao clicar no backdrop
      if (this.modal) {
        const backdrop = this.modal.querySelector('.vc-product-modal__backdrop');
        if (backdrop) {
          backdrop.addEventListener('click', () => this.close());
        }

        const closeBtn = this.modal.querySelector('.vc-product-modal__close');
        if (closeBtn) {
          closeBtn.addEventListener('click', () => this.close());
        }
      }

      // Fechar com ESC
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && this.modal && this.modal.classList.contains('is-open')) {
          this.close();
        }
      });
    }

    createModal() {
      const modalHTML = `
        <div class="vc-product-modal" id="vc-product-modal" role="dialog" aria-labelledby="vc-product-modal-title" aria-modal="true">
          <div class="vc-product-modal__backdrop"></div>
          <div class="vc-product-modal__dialog">
            <div class="vc-product-modal__header">
              <h2 class="vc-product-modal__title" id="vc-product-modal-title"></h2>
              <button class="vc-product-modal__close" aria-label="Fechar">&times;</button>
            </div>
            <div class="vc-product-modal__body">
              <div class="vc-product-modal__loading">Carregando...</div>
            </div>
            <div class="vc-product-modal__footer" style="display: none;">
              <div class="vc-product-modal__total">
                <span class="vc-product-modal__total-label">Total</span>
                <span class="vc-product-modal__total-value">R$ 0,00</span>
              </div>
              <button class="vc-product-modal__add-btn" disabled>Adicionar ao Carrinho</button>
            </div>
          </div>
        </div>
      `;

      // Inserir no body se não existir
      if (!document.getElementById('vc-product-modal')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('vc-product-modal');
      } else {
        this.modal = document.getElementById('vc-product-modal');
      }
    }

    async open(button) {
      if (!this.modal) return;

      const itemId = button.dataset.itemId || button.getAttribute('data-item-id');
      const itemTitle = button.dataset.itemTitle || button.getAttribute('data-item-title') || '';
      const itemPrice = button.dataset.itemPrice || button.getAttribute('data-item-price') || '0';
      const itemDescription = button.dataset.itemDescription || button.getAttribute('data-item-description') || '';
      const itemImage = button.dataset.itemImage || button.getAttribute('data-item-image') || '';
      const restaurantId = button.dataset.restaurantId || button.getAttribute('data-restaurant-id') || '';

      this.currentItem = {
        id: parseInt(itemId, 10),
        title: itemTitle,
        price: itemPrice,
        description: itemDescription,
        image: itemImage,
        restaurantId: parseInt(restaurantId, 10)
      };

      this.basePrice = this.parsePrice(itemPrice);
      this.selectedModifiers = {};
      this.modifiers = [];

      // Mostrar modal
      this.modal.classList.add('is-open');
      document.body.style.overflow = 'hidden';

      // Atualizar título
      const titleEl = this.modal.querySelector('.vc-product-modal__title');
      if (titleEl) {
        titleEl.textContent = itemTitle;
      }

      // Carregar modificadores
      await this.loadModifiers(itemId);

      // Renderizar conteúdo
      this.render();
    }

    async loadModifiers(itemId) {
      const body = this.modal.querySelector('.vc-product-modal__body');
      if (!body) return;

      body.innerHTML = '<div class="vc-product-modal__loading">Carregando modificadores...</div>';

      try {
        const response = await fetch(`${REST_BASE}/menu-items/${itemId}/modifiers`, {
          method: 'GET',
          headers: {
            'X-WP-Nonce': NONCE
          }
        });

        if (!response.ok) {
          throw new Error('Erro ao carregar modificadores');
        }

        const data = await response.json();
        this.modifiers = Array.isArray(data) ? data : [];
      } catch (error) {
        console.error('Erro ao carregar modificadores:', error);
        body.innerHTML = '<div class="vc-product-modal__error">Erro ao carregar modificadores. Tente novamente.</div>';
        this.modifiers = [];
      }
    }

    render() {
      const body = this.modal.querySelector('.vc-product-modal__body');
      const footer = this.modal.querySelector('.vc-product-modal__footer');
      if (!body || !footer) return;

      const { title, description, image, price } = this.currentItem;

      let html = '';

      // Imagem
      if (image) {
        html += `<img src="${this.escapeHtml(image)}" alt="${this.escapeHtml(title)}" class="vc-product-modal__image" loading="lazy">`;
      }

      // Descrição
      if (description) {
        html += `<div class="vc-product-modal__description">${this.escapeHtml(description)}</div>`;
      }

      // Preço base
      html += `<div class="vc-product-modal__price">${this.formatPrice(this.basePrice)}</div>`;

      // Modificadores
      if (this.modifiers.length > 0) {
        html += '<div class="vc-product-modal__modifiers">';
        html += '<h3 class="vc-product-modal__modifiers-title">Personalize seu pedido</h3>';

        this.modifiers.forEach((modifier) => {
          html += this.renderModifierGroup(modifier);
        });

        html += '</div>';
      }

      body.innerHTML = html;

      // Adicionar event listeners aos modificadores
      this.attachModifierListeners();

      // Atualizar footer
      footer.style.display = 'flex';
      this.updateTotal();
      this.validateSelection();
    }

    renderModifierGroup(modifier) {
      const isRequired = modifier.type === 'required';
      // Se max é null ou > 1, permite múltiplas seleções (checkbox)
      // Se max é 1, permite apenas uma (radio)
      const maxSelections = modifier.max === null ? 999 : modifier.max;
      const inputType = maxSelections === 1 ? 'radio' : 'checkbox';
      const groupName = `modifier-${modifier.id}`;
      const min = modifier.min || 0;
      const max = maxSelections;

      let html = `<div class="vc-product-modal__modifier-group" data-modifier-id="${modifier.id}">`;
      html += `<div class="vc-product-modal__modifier-group-title">`;
      html += `<span>${this.escapeHtml(modifier.title)}</span>`;
      if (isRequired) {
        html += `<span class="vc-badge vc-badge--required">Obrigatório</span>`;
      } else {
        html += `<span class="vc-badge">Opcional</span>`;
      }
      html += `</div>`;

      if (modifier.description) {
        html += `<p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 8px;">${this.escapeHtml(modifier.description)}</p>`;
      }

      html += '<div class="vc-product-modal__modifier-options">';

      // Cada modificador é uma opção individual
      const optionPrice = parseFloat(modifier.price || 0);
      const isFree = optionPrice === 0;
      const optionId = modifier.id;

      html += `<label class="vc-product-modal__modifier-option">`;
      html += `<input type="${inputType}" name="${groupName}" value="${optionId}" data-price="${optionPrice}" data-modifier-id="${modifier.id}">`;
      html += `<span class="vc-product-modal__modifier-option-label">${this.escapeHtml(modifier.title)}</span>`;
      html += `<span class="vc-product-modal__modifier-option-price ${isFree ? 'vc-product-modal__modifier-option-price--free' : ''}">`;
      if (isFree) {
        html += 'Grátis';
      } else {
        html += this.formatPrice(optionPrice);
      }
      html += `</span>`;
      html += `</label>`;

      html += '</div>';

      // Mostrar min/max se aplicável
      if (min > 0 || (max > 0 && max < 999)) {
        html += `<div class="vc-product-modal__modifier-minmax">`;
        if (min > 0 && max > 0 && max < 999) {
          html += `Selecione entre ${min} e ${max} opção(ões)`;
        } else if (min > 0) {
          html += `Selecione pelo menos ${min} opção(ões)`;
        } else if (max > 0 && max < 999) {
          html += `Selecione no máximo ${max} opção(ões)`;
        }
        html += `</div>`;
      }

      html += '</div>';

      return html;
    }

    attachModifierListeners() {
      const inputs = this.modal.querySelectorAll('.vc-product-modal__modifier-option input');
      inputs.forEach((input) => {
        input.addEventListener('change', () => {
          this.handleModifierChange(input);
        });
      });
    }

    handleModifierChange(input) {
      const modifierId = parseInt(input.dataset.modifierId, 10);
      const optionId = parseInt(input.value, 10);
      const price = parseFloat(input.dataset.price || 0);
      const modifier = this.modifiers.find(m => m.id === modifierId);

      if (!modifier) return;

      const isRadio = input.type === 'radio';
      const groupName = input.name;
      const maxSelections = modifier.max === null ? 999 : modifier.max;

      if (isRadio || maxSelections === 1) {
        // Radio ou checkbox com max=1: apenas uma seleção por grupo
        this.selectedModifiers[modifierId] = input.checked ? [{
          id: optionId,
          modifierId: modifierId,
          title: modifier.title,
          price: price
        }] : [];
      } else {
        // Checkbox com múltiplas seleções permitidas
        if (!this.selectedModifiers[modifierId]) {
          this.selectedModifiers[modifierId] = [];
        }

        if (input.checked) {
          // Verificar limite máximo
          if (maxSelections > 0 && this.selectedModifiers[modifierId].length >= maxSelections) {
            input.checked = false;
            return;
          }
          this.selectedModifiers[modifierId].push({
            id: optionId,
            modifierId: modifierId,
            title: modifier.title,
            price: price
          });
        } else {
          this.selectedModifiers[modifierId] = this.selectedModifiers[modifierId].filter(
            m => m.id !== optionId
          );
        }
      }

      // Atualizar visual
      this.updateModifierVisuals(groupName);
      this.updateTotal();
      this.validateSelection();
    }

    updateModifierVisuals(groupName) {
      const inputs = this.modal.querySelectorAll(`input[name="${groupName}"]`);
      inputs.forEach((input) => {
        const option = input.closest('.vc-product-modal__modifier-option');
        if (option) {
          if (input.checked) {
            option.classList.add('is-selected');
          } else {
            option.classList.remove('is-selected');
          }
        }
      });
    }

    updateTotal() {
      let total = this.basePrice;

      Object.values(this.selectedModifiers).forEach((modifiers) => {
        modifiers.forEach((mod) => {
          total += mod.price || 0;
        });
      });

      const totalEl = this.modal.querySelector('.vc-product-modal__total-value');
      if (totalEl) {
        totalEl.textContent = this.formatPrice(total);
      }
    }

    validateSelection() {
      let isValid = true;
      const errors = [];

      this.modifiers.forEach((modifier) => {
        const selected = this.selectedModifiers[modifier.id] || [];
        const count = selected.length;
        const min = modifier.min || 0;
        const max = modifier.max === null ? 999 : modifier.max;

        if (modifier.type === 'required' && count === 0) {
          isValid = false;
          errors.push(`"${modifier.title}" é obrigatório`);
        }

        if (min > 0 && count < min) {
          isValid = false;
          errors.push(`"${modifier.title}" requer pelo menos ${min} seleção(ões)`);
        }

        if (max > 0 && max < 999 && count > max) {
          isValid = false;
          errors.push(`"${modifier.title}" permite no máximo ${max} seleção(ões)`);
        }
      });

      const addBtn = this.modal.querySelector('.vc-product-modal__add-btn');
      if (addBtn) {
        addBtn.disabled = !isValid;
      }

      // Mostrar erros
      const body = this.modal.querySelector('.vc-product-modal__body');
      const existingError = body.querySelector('.vc-product-modal__error');
      if (existingError) {
        existingError.remove();
      }

      if (!isValid && errors.length > 0) {
        const errorHTML = `<div class="vc-product-modal__error">${errors.join('<br>')}</div>`;
        const modifiersEl = body.querySelector('.vc-product-modal__modifiers');
        if (modifiersEl) {
          modifiersEl.insertAdjacentHTML('beforebegin', errorHTML);
        }
      }
    }

    close() {
      if (this.modal) {
        this.modal.classList.remove('is-open');
        document.body.style.overflow = '';
        this.currentItem = null;
        this.selectedModifiers = {};
        this.modifiers = [];
      }
    }

    addToCart() {
      if (!this.currentItem) return;

      // Usar o carrinho do frontend.js se disponível
      const CART_KEY = 'vc_cart_v1';
      let cart = JSON.parse(localStorage.getItem(CART_KEY) || '[]');

      // Preparar item com modificadores
      const modifiersArray = Object.values(this.selectedModifiers).flat();
      const cartItem = {
        id: this.currentItem.id,
        rid: this.currentItem.restaurantId,
        title: this.currentItem.title,
        price: this.currentItem.price,
        modifiers: modifiersArray.map(m => ({
          id: m.id,
          modifierId: m.modifierId,
          title: m.title,
          price: m.price
        }))
      };

      // Verificar se já existe item idêntico (mesmo ID e mesmos modificadores)
      const modifiersKey = JSON.stringify(cartItem.modifiers);
      const found = cart.find(i => 
        i.id === cartItem.id && 
        JSON.stringify(i.modifiers || []) === modifiersKey
      );

      if (found) {
        found.qtd++;
      } else {
        cart.push({...cartItem, qtd: 1});
      }

      // Salvar no localStorage
      localStorage.setItem(CART_KEY, JSON.stringify(cart));

      // Disparar evento customizado para atualizar UI
      window.dispatchEvent(new CustomEvent('vemcomer:cart:updated'));

      // Tentar atualizar checkout se existir
      const checkout = document.querySelector('.vc-checkout');
      if (checkout && typeof window.renderCart === 'function') {
        window.renderCart(checkout);
      }

      // Fechar modal
      this.close();

      // Mostrar feedback
      this.showFeedback('Item adicionado ao carrinho!');
    }

    showFeedback(message) {
      // Criar toast simples
      const toast = document.createElement('div');
      toast.style.cssText = 'position: fixed; bottom: 20px; right: 20px; background: #2f9e44; color: #fff; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 100000;';
      toast.textContent = message;
      document.body.appendChild(toast);

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
      }, 2000);
    }

    // Utilitários
    parsePrice(price) {
      if (typeof price !== 'string') return parseFloat(price) || 0;
      const cleaned = price.replace(/[^0-9,.]/g, '');
      const normalized = cleaned.replace(',', '.');
      return parseFloat(normalized) || 0;
    }

    formatPrice(price) {
      return 'R$ ' + parseFloat(price).toFixed(2).replace('.', ',');
    }

    escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  }

  // Inicializar quando DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      new ProductModal();
    });
  } else {
    new ProductModal();
  }

  // Adicionar listener para botão "Adicionar ao Carrinho" do modal
  document.addEventListener('click', (e) => {
    if (e.target.closest('.vc-product-modal__add-btn')) {
      e.preventDefault();
      const modal = document.getElementById('vc-product-modal');
      if (modal && modal.classList.contains('is-open')) {
        const instance = window.vemcomerProductModal;
        if (instance && typeof instance.addToCart === 'function') {
          instance.addToCart();
        }
      }
    }
  });

  // Expor instância globalmente para acesso externo
  document.addEventListener('DOMContentLoaded', () => {
    window.vemcomerProductModal = new ProductModal();
  });

})();
