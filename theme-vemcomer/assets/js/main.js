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
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true';
                menuToggle.setAttribute('aria-expanded', !isExpanded);
                navigation.classList.toggle('is-open');
                
                // Prevenir scroll do body quando menu aberto
                if (!isExpanded) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            });
            
            // Fechar menu ao clicar em link
            const navLinks = navigation.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    navigation.classList.remove('is-open');
                    document.body.style.overflow = '';
                });
            });
            
            // Fechar menu ao clicar fora
            document.addEventListener('click', (e) => {
                if (!navigation.contains(e.target) && !menuToggle.contains(e.target)) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    navigation.classList.remove('is-open');
                    document.body.style.overflow = '';
                }
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

        // Popup de Seleção de Cadastro
        const btnCadastro = document.getElementById('btn-cadastro');
        const signupPopup = document.getElementById('signup-popup');
        const signupClose = signupPopup?.querySelector('.signup-popup__close');
        const signupOverlay = signupPopup?.querySelector('.signup-popup__overlay');
        
        if (btnCadastro && signupPopup) {
            btnCadastro.addEventListener('click', (e) => {
                e.preventDefault();
                signupPopup.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            });
        }
        
        function closeSignupPopup() {
            if (signupPopup) {
                signupPopup.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        }
        
        if (signupClose) {
            signupClose.addEventListener('click', closeSignupPopup);
        }
        
        if (signupOverlay) {
            signupOverlay.addEventListener('click', closeSignupPopup);
        }
        
        // Fechar popup com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && signupPopup?.classList.contains('is-open')) {
                closeSignupPopup();
            }
        });
    });

})();

