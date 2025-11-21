/**
 * JavaScript para a Home do VemComer
 * Gerencia tabs e scroll suave
 * @package VemComerCore
 */

(function() {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    // Tabs na seção "Para você"
    const tabs = document.querySelectorAll('.vc-home-tab');
    const tabContents = document.querySelectorAll('.vc-home-tab-content');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const targetTab = tab.dataset.tab;

        // Remover active de todos
        tabs.forEach(t => t.classList.remove('vc-home-tab--active'));
        tabContents.forEach(content => {
          content.classList.remove('vc-home-tab-content--active');
        });

        // Adicionar active no selecionado
        tab.classList.add('vc-home-tab--active');
        const targetContent = document.getElementById(`vc-tab-${targetTab}`);
        if (targetContent) {
          targetContent.classList.add('vc-home-tab-content--active');
        }
      });
    });

    // Scroll suave para âncoras
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href === '#' || href === '#!') {
          return;
        }

        const target = document.querySelector(href);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
          });
        }
      });
    });

    // Manter parâmetros de busca na URL ao fazer scroll
    const searchForm = document.querySelector('.vc-home-hero__search-form');
    if (searchForm) {
      const urlParams = new URLSearchParams(window.location.search);
      const searchParam = urlParams.get('s');
      if (searchParam) {
        const searchInput = searchForm.querySelector('input[name="s"]');
        if (searchInput) {
          searchInput.value = searchParam;
        }
      }
    }
  });

})();

