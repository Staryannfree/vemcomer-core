/**
 * Product Modal - Modal para seleção de modificadores de produto
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
   * Utilitários de moeda
   */
  function currencyToFloat(v) {
    if (typeof v !== 'string') { return Number(v || 0); }
    v = v.replace(/[^0-9,\.]/g, '');
    if (v.indexOf(',') > -1 && v.lastIndexOf(',') > v.lastIndexOf('.')) {
      v = v.replace(/\./g, '').replace(',', '.');
    }
    return Number(v || 0);
  }

  function floatToBR(n) {
    return (Number(n) || 0).toFixed(2).replace('.', ',');
  }

  /**
   * Classe principal do Modal de Produto
   */
  class ProductModal {
    constructor() {
      this.modal = null;
      this.currentItem = null;
      this.modifiers = [];
      this.selectedModifiers = {}; // { modifierId: quantity }
      this.basePrice = 0;
      this.init();
    }

    init() {
      this.createModal();
      this.bindEvents();
    }

    /**
     * Cria a estrutura HTML do modal
     */
    createModal() {
      const modalHTML = `
        <div id="vc-product-modal" class="vc-product-modal" role="dialog" aria-labelledby="vc-product-modal-title" aria-hidden="true">
          <div class="vc-product-modal__overlay" data-close="modal"></div>
          <div class="vc-product-modal__dialog">
            <button class="vc-product-modal__close" aria-label="Fechar modal" data-close="modal">×</button>
            <div class="vc-product-modal__header">
              <h2 id="vc-product-modal-title" class="vc-product-modal__title"></h2>
              <p class="vc-product-modal__description"></p>
            </div>
            <div class="vc-product-modal__image"></div>
            <div class="vc-product-modal__body">
              <div class="vc-product-modal__modifiers"></div>
              <div class="vc-product-modal__errors"></div>
            </div>
            <div class="vc-product-modal__footer">
              <div class="vc-product-modal__price">
                <span class="vc-product-modal__price-label">Total:</span>
                <span class="vc-product-modal__price-value">R$ 0,00</span>
              </div>
              <div class="vc-product-modal__actions">
                <button class="vc-btn vc-btn--ghost vc-product-modal__cancel" data-close="modal">Cancelar</button>
                <button class="vc-btn vc-product-modal__add" disabled>Adicionar ao Carrinho</button>
              </div>
            </div>
          </div>
        </div>
      `;

      document.body.insertAdjacentHTML('beforeend', modalHTML);
      this.modal = document.getElementById('vc-product-modal');
    }

    /**
     * Vincula eventos
     */
    bindEvents() {
      // Fechar modal ao clicar no overlay ou botão fechar
      this.modal.addEventListener('click', (e) => {
        if (e.target.dataset.close === 'modal' || e.target.closest('[data-close="modal"]')) {
          this.close();
        }
      });

      // Fechar com ESC
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !this.modal.classList.contains('vc-product-modal--hidden')) {
          this.close();
        }
      });

      // Botão adicionar ao carrinho
      const addBtn = this.modal.querySelector('.vc-product-modal__add');
      addBtn.addEventListener('click', () => {
        this.addToCart();
      });

      // Prevenir fechamento ao clicar dentro do dialog
      const dialog = this.modal.querySelector('.vc-product-modal__dialog');
      dialog.addEventListener('click', (e) => {
        e.stopPropagation();
      });
    }

    /**
     * Abre o modal para um item do cardápio
     */
    async open(itemId, itemData) {
      this.currentItem = {
        id: itemId,
        title: itemData.title || '',
        description: itemData.description || '',
        price: itemData.price || '0,00',
        restaurant_id: itemData.restaurant_id || 0,
        image: itemData.image || null,
      };

      this.basePrice = currencyToFloat(this.currentItem.price);
      this.selectedModifiers = {};
      this.modifiers = [];

      // Atualizar header
      this.modal.querySelector('.vc-product-modal__title').textContent = this.currentItem.title;
      this.modal.querySelector('.vc-product-modal__description').textContent = this.currentItem.description || '';

      // Atualizar imagem
      const imageContainer = this.modal.querySelector('.vc-product-modal__image');
      if (this.currentItem.image) {
        imageContainer.innerHTML = `<img src="${this.currentItem.image}" alt="${this.currentItem.title}" />`;
      } else {
        imageContainer.innerHTML = '';
      }

      // Mostrar loading
      this.showLoading();

      // Carregar modificadores
      try {
        await this.loadModifiers(itemId);
      } catch (error) {
        console.error('Erro ao carregar modificadores:', error);
        this.showError('Erro ao carregar opções do produto.');
      }

      // Renderizar modificadores
      this.renderModifiers();

      // Atualizar preço
      this.updatePrice();

      // Mostrar modal
      this.modal.classList.remove('vc-product-modal--hidden');
      this.modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';

      // Focar no modal para acessibilidade
      this.modal.focus();
    }

    /**
     * Carrega modificadores do item via REST API
     */
    async loadModifiers(itemId) {
      const url = `${REST_BASE}/menu-items/${itemId}/modifiers`;
      const response = await fetch(url);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const data = await response.json();
      this.modifiers = Array.isArray(data) ? data : [];

      // Separar obrigatórios e opcionais
      this.modifiers.sort((a, b) => {
        if (a.type === 'required' && b.type !== 'required') return -1;
        if (a.type !== 'required' && b.type === 'required') return 1;
        return 0;
      });
    }

    /**
     * Renderiza os modificadores no modal
     */
    renderModifiers() {
      const container = this.modal.querySelector('.vc-product-modal__modifiers');
      container.innerHTML = '';

      if (this.modifiers.length === 0) {
        container.innerHTML = '<p class="vc-product-modal__no-modifiers">Este produto não possui opções adicionais.</p>';
        return;
      }

      this.modifiers.forEach((modifier) => {
        const modifierEl = this.createModifierElement(modifier);
        container.appendChild(modifierEl);
      });
    }

    /**
     * Cria elemento HTML para um modificador
     */
    createModifierElement(modifier) {
      const div = document.createElement('div');
      div.className = `vc-product-modal__modifier vc-product-modal__modifier--${modifier.type}`;
      div.dataset.modifierId = modifier.id;

      const isRequired = modifier.type === 'required';
      const min = modifier.min || 0;
      const max = modifier.max || null;
      const price = modifier.price || 0;
      const priceText = price > 0 ? `R$ ${floatToBR(price)}` : 'Grátis';

      let inputHTML = '';
      if (max === null || max > 1) {
        // Múltipla seleção (checkbox ou quantidade)
        inputHTML = this.createMultipleSelectionInput(modifier, min, max);
      } else {
        // Seleção única (radio)
        inputHTML = this.createSingleSelectionInput(modifier);
      }

      div.innerHTML = `
        <div class="vc-product-modal__modifier-header">
          <h3 class="vc-product-modal__modifier-title">
            ${modifier.title}
            ${isRequired ? '<span class="vc-product-modal__required">*</span>' : ''}
          </h3>
          <span class="vc-product-modal__modifier-price">${priceText}</span>
        </div>
        ${modifier.description ? `<p class="vc-product-modal__modifier-description">${modifier.description}</p>` : ''}
        <div class="vc-product-modal__modifier-options">
          ${inputHTML}
        </div>
        ${isRequired && min > 0 ? `<p class="vc-product-modal__modifier-hint">Selecione pelo menos ${min} opção${min > 1 ? 'ões' : ''}</p>` : ''}
        ${max && max > 0 ? `<p class="vc-product-modal__modifier-hint">Máximo ${max} opção${max > 1 ? 'ões' : ''}</p>` : ''}
      `;

      // Vincular eventos
      this.bindModifierEvents(div, modifier);

      return div;
    }

    /**
     * Cria input para seleção múltipla (checkbox)
     */
    createMultipleSelectionInput(modifier, min, max) {
      // Para múltipla seleção, vamos usar checkboxes simples
      // A quantidade será sempre 1 quando selecionado
      return `
        <label class="vc-product-modal__option">
          <input type="checkbox" 
                 data-modifier-id="${modifier.id}" 
                 data-price="${modifier.price || 0}"
                 ${modifier.type === 'required' && min > 0 ? 'required' : ''} />
          <span class="vc-product-modal__option-label">${modifier.title}</span>
        </label>
      `;
    }

    /**
     * Cria input para seleção única (radio)
     */
    createSingleSelectionInput(modifier) {
      return `
        <label class="vc-product-modal__option">
          <input type="radio" 
                 name="modifier_${modifier.id}" 
                 data-modifier-id="${modifier.id}" 
                 data-price="${modifier.price || 0}"
                 ${modifier.type === 'required' ? 'required' : ''} />
          <span class="vc-product-modal__option-label">${modifier.title}</span>
        </label>
      `;
    }

    /**
     * Vincula eventos de um modificador
     */
    bindModifierEvents(element, modifier) {
      const inputs = element.querySelectorAll('input[type="checkbox"], input[type="radio"]');
      inputs.forEach((input) => {
        input.addEventListener('change', () => {
          this.handleModifierChange(modifier.id, input.checked, modifier);
          this.updatePrice();
          this.validateForm();
        });
      });
    }

    /**
     * Manipula mudança em modificador
     */
    handleModifierChange(modifierId, checked, modifier) {
      if (checked) {
        this.selectedModifiers[modifierId] = 1;
      } else {
        delete this.selectedModifiers[modifierId];
      }
    }

    /**
     * Valida o formulário
     */
    validateForm() {
      const errors = [];
      const addBtn = this.modal.querySelector('.vc-product-modal__add');

      // Verificar modificadores obrigatórios
      this.modifiers.forEach((modifier) => {
        if (modifier.type === 'required') {
          const min = modifier.min || 0;
          const selected = this.selectedModifiers[modifier.id] || 0;

          if (selected < min) {
            errors.push(`"${modifier.title}" é obrigatório (mínimo ${min})`);
          }

          if (modifier.max && selected > modifier.max) {
            errors.push(`"${modifier.title}" permite no máximo ${modifier.max} opção${modifier.max > 1 ? 'ões' : ''}`);
          }
        }
      });

      // Exibir erros
      const errorsContainer = this.modal.querySelector('.vc-product-modal__errors');
      if (errors.length > 0) {
        errorsContainer.innerHTML = errors.map((e) => `<p class="vc-product-modal__error">${e}</p>`).join('');
        addBtn.disabled = true;
      } else {
        errorsContainer.innerHTML = '';
        addBtn.disabled = false;
      }

      return errors.length === 0;
    }

    /**
     * Atualiza o preço total
     */
    updatePrice() {
      let total = this.basePrice;

      Object.keys(this.selectedModifiers).forEach((modifierId) => {
        const modifier = this.modifiers.find((m) => m.id === Number(modifierId));
        if (modifier) {
          const quantity = this.selectedModifiers[modifierId] || 1;
          total += (modifier.price || 0) * quantity;
        }
      });

      const priceEl = this.modal.querySelector('.vc-product-modal__price-value');
      priceEl.textContent = `R$ ${floatToBR(total)}`;
    }

    /**
     * Adiciona item ao carrinho
     */
    addToCart() {
      if (!this.validateForm()) {
        return;
      }

      // Disparar evento customizado para o frontend.js adicionar ao carrinho
      const event = new CustomEvent('vc:add-to-cart', {
        detail: {
          item: {
            id: this.currentItem.id,
            title: this.currentItem.title,
            price: floatToBR(this.basePrice),
            restaurant_id: this.currentItem.restaurant_id,
          },
          modifiers: Object.keys(this.selectedModifiers).map((modifierId) => {
            const modifier = this.modifiers.find((m) => m.id === Number(modifierId));
            return {
              id: Number(modifierId),
              title: modifier ? modifier.title : '',
              price: modifier ? modifier.price : 0,
              quantity: this.selectedModifiers[modifierId] || 1,
            };
          }),
          totalPrice: this.calculateTotalPrice(),
        },
      });

      document.dispatchEvent(event);
      this.close();
    }

    /**
     * Calcula preço total incluindo modificadores
     */
    calculateTotalPrice() {
      let total = this.basePrice;
      Object.keys(this.selectedModifiers).forEach((modifierId) => {
        const modifier = this.modifiers.find((m) => m.id === Number(modifierId));
        if (modifier) {
          total += (modifier.price || 0) * (this.selectedModifiers[modifierId] || 1);
        }
      });
      return total;
    }

    /**
     * Fecha o modal
     */
    close() {
      this.modal.classList.add('vc-product-modal--hidden');
      this.modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      this.currentItem = null;
      this.modifiers = [];
      this.selectedModifiers = {};
    }

    /**
     * Mostra loading
     */
    showLoading() {
      const container = this.modal.querySelector('.vc-product-modal__modifiers');
      container.innerHTML = '<p class="vc-product-modal__loading">Carregando opções...</p>';
    }

    /**
     * Mostra erro
     */
    showError(message) {
      const container = this.modal.querySelector('.vc-product-modal__modifiers');
      container.innerHTML = `<p class="vc-product-modal__error">${message}</p>`;
    }
  }

  // Inicializar quando DOM estiver pronto
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      window.VemComerProductModal = new ProductModal();
    });
  } else {
    window.VemComerProductModal = new ProductModal();
  }

  // Expor função global para abrir modal
  window.vcOpenProductModal = function(itemId, itemData) {
    if (window.VemComerProductModal) {
      window.VemComerProductModal.open(itemId, itemData);
    }
  };

  // Handler para botões .vc-menu-item__add (shortcode vc_menu_items)
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.vc-menu-item__add');
    if (!btn || btn.disabled) {
      return;
    }

    const itemId = Number(btn.dataset.itemId);
    if (!itemId) {
      return;
    }

    const itemData = {
      title: btn.dataset.itemTitle || '',
      description: btn.dataset.itemDescription || '',
      price: btn.dataset.itemPrice || '0,00',
      restaurant_id: Number(btn.dataset.restaurantId) || 0,
      image: btn.dataset.itemImage || null,
    };

    if (window.vcOpenProductModal) {
      window.vcOpenProductModal(itemId, itemData);
    }
  });

})();

