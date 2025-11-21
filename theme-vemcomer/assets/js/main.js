/**
 * JavaScript principal do tema VemComer
 * @package VemComer
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        // Menu mobile toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const navigation = document.querySelector('.main-navigation');
        
        if (menuToggle && navigation) {
            menuToggle.addEventListener('click', () => {
                const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
                menuToggle.setAttribute('aria-expanded', !isExpanded);
                navigation.classList.toggle('is-open');
            });
        }

        // User menu dropdown
        const userMenuToggle = document.querySelector('.user-menu__toggle');
        const userMenuDropdown = document.querySelector('.user-menu__dropdown');
        
        if (userMenuToggle && userMenuDropdown) {
            userMenuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const isExpanded = userMenuToggle.getAttribute('aria-expanded') === 'true';
                userMenuToggle.setAttribute('aria-expanded', !isExpanded);
                userMenuDropdown.classList.toggle('is-open');
            });

            // Fechar ao clicar fora
            document.addEventListener('click', () => {
                userMenuToggle.setAttribute('aria-expanded', 'false');
                userMenuDropdown.classList.remove('is-open');
            });
        }

        // Tabs
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;

                // Remover active de todos
                tabs.forEach(t => t.classList.remove('tab--active'));
                tabContents.forEach(content => {
                    content.classList.remove('tab-content--active');
                });

                // Adicionar active no selecionado
                tab.classList.add('tab--active');
                const targetContent = document.getElementById(`tab-${targetTab}`);
                if (targetContent) {
                    targetContent.classList.add('tab-content--active');
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
    });

})();

