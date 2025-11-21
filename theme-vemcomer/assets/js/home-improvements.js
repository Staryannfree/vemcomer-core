/**
 * JavaScript para melhorias da Home
 * @package VemComer
 */

(function() {
    'use strict';

    const REST_BASE = window.vemcomerTheme?.restUrl || '/wp-json/vemcomer/v1/';
    const NONCE = window.vemcomerTheme?.nonce || '';

    // ===== Filtros Rápidos =====
    function initQuickFilters() {
        const chips = document.querySelectorAll('.filter-chip');
        const clearBtn = document.getElementById('clear-filters');
        const countEl = document.getElementById('filter-count');
        let activeFilters = {};

        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const filter = chip.dataset.filter;
                const value = chip.dataset.value || true;

                if (chip.classList.contains('is-active')) {
                    chip.classList.remove('is-active');
                    delete activeFilters[filter];
                } else {
                    chip.classList.add('is-active');
                    activeFilters[filter] = value;
                }

                updateFilterCount();
                applyFilters();
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                chips.forEach(chip => chip.classList.remove('is-active'));
                activeFilters = {};
                updateFilterCount();
                applyFilters();
            });
        }

        function updateFilterCount() {
            const count = Object.keys(activeFilters).length;
            if (countEl) {
                if (count > 0) {
                    countEl.textContent = `${count} filtro(s) ativo(s)`;
                    countEl.style.display = 'inline-block';
                } else {
                    countEl.style.display = 'none';
                }
            }
        }

        function applyFilters() {
            const params = new URLSearchParams();
            Object.entries(activeFilters).forEach(([key, value]) => {
                if (value === true) {
                    params.append(key, '1');
                } else {
                    params.append(key, value);
                }
            });

            // Atualizar URL e recarregar restaurantes
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({}, '', newUrl);
            
            // Recarregar restaurantes via AJAX
            loadRestaurants(params);
        }
    }

    // ===== Geolocalização =====
    function initGeolocation() {
        const btn = document.getElementById('vc-use-location');
        if (!btn) return;

        btn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Geolocalização não suportada pelo seu navegador.');
                return;
            }

            btn.classList.add('is-loading');
            btn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Salvar no localStorage
                    localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                    
                    // Atualizar UI
                    btn.classList.remove('is-loading');
                    btn.classList.add('is-active');
                    btn.disabled = false;
                    
                    // Recarregar restaurantes com distância
                    loadRestaurantsWithLocation(lat, lng);
                },
                (error) => {
                    btn.classList.remove('is-loading');
                    btn.disabled = false;
                    alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
                }
            );
        });

        // Verificar se já tem localização salva
        const savedLocation = localStorage.getItem('vc_user_location');
        if (savedLocation) {
            try {
                const { lat, lng } = JSON.parse(savedLocation);
                btn.classList.add('is-active');
                loadRestaurantsWithLocation(lat, lng);
            } catch (e) {
                // Ignorar erro
            }
        }
    }

    // ===== Busca com Autocomplete =====
    function initSearchAutocomplete() {
        const input = document.getElementById('hero-search-input');
        const autocomplete = document.getElementById('search-autocomplete');
        if (!input || !autocomplete) return;

        let timeout;
        let selectedIndex = -1;

        input.addEventListener('input', (e) => {
            clearTimeout(timeout);
            const query = e.target.value.trim();

            if (query.length < 2) {
                autocomplete.style.display = 'none';
                return;
            }

            timeout = setTimeout(() => {
                fetchAutocomplete(query);
            }, 300);
        });

        input.addEventListener('keydown', (e) => {
            const items = autocomplete.querySelectorAll('.search-autocomplete__item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex].click();
            } else if (e.key === 'Escape') {
                autocomplete.style.display = 'none';
            }
        });

        document.addEventListener('click', (e) => {
            if (!autocomplete.contains(e.target) && e.target !== input) {
                autocomplete.style.display = 'none';
            }
        });

        function fetchAutocomplete(query) {
            fetch(`${REST_BASE}restaurants?search=${encodeURIComponent(query)}&per_page=5`, {
                headers: {
                    'X-WP-Nonce': NONCE,
                },
            })
            .then(res => res.json())
            .then(data => {
                renderAutocomplete(data);
            })
            .catch(err => {
                console.error('Erro ao buscar:', err);
            });
        }

        function renderAutocomplete(items) {
            if (!items || items.length === 0) {
                autocomplete.style.display = 'none';
                return;
            }

            let html = '';
            items.forEach(item => {
                html += `
                    <div class="search-autocomplete__item" data-id="${item.id}">
                        <p class="search-autocomplete__title">
                            <span class="search-autocomplete__icon">🍽️</span>
                            ${item.title}
                        </p>
                        ${item.address ? `<p class="search-autocomplete__subtitle">${item.address}</p>` : ''}
                    </div>
                `;
            });

            autocomplete.innerHTML = html;
            autocomplete.style.display = 'block';

            // Vincular cliques
            autocomplete.querySelectorAll('.search-autocomplete__item').forEach(item => {
                item.addEventListener('click', () => {
                    const id = item.dataset.id;
                    window.location.href = `/restaurante/?restaurant_id=${id}`;
                });
            });
        }

        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('is-selected');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('is-selected');
                }
            });
        }
    }

    // ===== Skeleton Loading =====
    function initSkeletonLoading() {
        const skeleton = document.getElementById('skeleton-loading');
        const content = document.getElementById('restaurants-content');
        
        if (!skeleton || !content) return;

        // Simular carregamento
        setTimeout(() => {
            skeleton.style.display = 'none';
            content.style.display = 'block';
            
            // Animar cards
            const cards = content.querySelectorAll('.vc-card, .vc-restaurant');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s, transform 0.5s';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 50);
            });
        }, 800);
    }

    // ===== Lazy Load Imagens =====
    function initLazyLoad() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // ===== Sticky Header Compacto =====
    function initStickyHeader() {
        const header = document.querySelector('.site-header');
        if (!header) return;

        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('is-compact');
            } else {
                header.classList.remove('is-compact');
            }
            
            lastScroll = currentScroll;
        });
    }

    // ===== Bottom Navigation Mobile =====
    function initBottomNav() {
        if (window.innerWidth > 768) return;

        const currentPath = window.location.pathname;
        const navItems = document.querySelectorAll('.bottom-nav__item');
        
        navItems.forEach(item => {
            const href = item.getAttribute('href');
            if (href && currentPath.includes(href.replace(homeUrl, ''))) {
                item.classList.add('is-active');
            }
        });
    }

    // ===== Modo Escuro =====
    function initDarkMode() {
        const toggle = document.getElementById('dark-mode-toggle');
        if (!toggle) return;

        const isDark = localStorage.getItem('vc_dark_mode') === 'true';
        if (isDark) {
            document.body.classList.add('dark-mode');
        }

        toggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('vc_dark_mode', document.body.classList.contains('dark-mode'));
        });
    }

    // ===== Barra de Promoção =====
    function initPromoBar() {
        const promoBar = document.getElementById('promo-bar');
        if (!promoBar) return;

        const closeBtn = promoBar.querySelector('.promo-bar__close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                promoBar.style.display = 'none';
                // Salvar cookie (expira em 7 dias)
                document.cookie = 'vc_promo_bar_dismissed=1; path=/; max-age=' + (7 * 24 * 60 * 60);
            });
        }
    }

    // ===== Popup Primeira Visita - Geolocalização =====
    function initWelcomePopup() {
        const popup = document.getElementById('welcome-popup');
        if (!popup) return;

        // Verificar se já tem localização
        const savedLocation = localStorage.getItem('vc_user_location');
        if (savedLocation) {
            return; // Não mostrar popup se já tem localização
        }

        setTimeout(() => {
            popup.classList.add('is-open');
        }, 1500);

        const closeBtn = popup.querySelector('.welcome-popup__close');
        const locationBtn = popup.querySelector('#welcome-popup-location-btn');
        const skipBtn = popup.querySelector('#welcome-popup-skip-btn');

        function closePopup() {
            popup.classList.remove('is-open');
            // Salvar cookie (expira em 30 dias)
            document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closePopup);
        }

        if (skipBtn) {
            skipBtn.addEventListener('click', closePopup);
        }

        if (locationBtn) {
            locationBtn.addEventListener('click', () => {
                if (!navigator.geolocation) {
                    alert('Geolocalização não suportada pelo seu navegador.');
                    return;
                }

                locationBtn.disabled = true;
                locationBtn.classList.add('is-loading');
                locationBtn.innerHTML = '<span>Carregando...</span>';

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Salvar no localStorage
                        localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                        
                        // Atualizar botão de geolocalização no hero se existir
                        const heroLocationBtn = document.getElementById('vc-use-location');
                        if (heroLocationBtn) {
                            heroLocationBtn.classList.add('is-active');
                        }
                        
                        // Fechar popup
                        closePopup();
                        
                        // Recarregar restaurantes com distância
                        loadRestaurantsWithLocation(lat, lng);
                        
                        // Mostrar mensagem de sucesso
                        showNotification('Localização salva! Mostrando restaurantes próximos.', 'success');
                    },
                    (error) => {
                        locationBtn.disabled = false;
                        locationBtn.classList.remove('is-loading');
                        locationBtn.innerHTML = '<span class="btn-icon">📍</span><span>Usar minha localização</span>';
                        
                        let errorMsg = 'Não foi possível obter sua localização.';
                        if (error.code === error.PERMISSION_DENIED) {
                            errorMsg = 'Permissão de localização negada. Você pode permitir nas configurações do navegador.';
                        } else if (error.code === error.POSITION_UNAVAILABLE) {
                            errorMsg = 'Localização indisponível.';
                        } else if (error.code === error.TIMEOUT) {
                            errorMsg = 'Tempo de espera esgotado. Tente novamente.';
                        }
                        
                        alert(errorMsg);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });
        }

        // Fechar ao clicar fora
        popup.addEventListener('click', (e) => {
            if (e.target === popup) {
                closePopup();
            }
        });
    }

    // ===== Notificação =====
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification--${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('is-visible');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('is-visible');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // ===== Carregar Restaurantes =====
    function loadRestaurants(params = new URLSearchParams()) {
        const container = document.getElementById('restaurants-content');
        if (!container) return;

        const skeleton = document.getElementById('skeleton-loading');
        if (skeleton) {
            skeleton.style.display = 'grid';
        }
        container.style.display = 'none';

        const url = `${REST_BASE}restaurants?${params.toString()}`;
        fetch(url, {
            headers: {
                'X-WP-Nonce': NONCE,
            },
        })
        .then(res => res.json())
        .then(data => {
            // Renderizar restaurantes
            renderRestaurants(data);
            
            if (skeleton) {
                skeleton.style.display = 'none';
            }
            container.style.display = 'block';
        })
        .catch(err => {
            console.error('Erro ao carregar restaurantes:', err);
            if (skeleton) {
                skeleton.style.display = 'none';
            }
            container.style.display = 'block';
        });
    }

    function loadRestaurantsWithLocation(lat, lng) {
        // Adicionar distância aos cards
        const cards = document.querySelectorAll('.vc-restaurant, .vc-card');
        cards.forEach(card => {
            const id = card.dataset.id;
            if (!id) return;

            // Calcular distância (simplificado - usar API real)
            // Por enquanto, apenas adicionar badge "Mais próximo" no primeiro
        });
    }

    function renderRestaurants(restaurants) {
        // Implementar renderização dinâmica se necessário
        // Por enquanto, os shortcodes já fazem isso
    }

    // ===== Inicializar tudo =====
    document.addEventListener('DOMContentLoaded', () => {
        initQuickFilters();
        initGeolocation();
        initSearchAutocomplete();
        initSkeletonLoading();
        initLazyLoad();
        initStickyHeader();
        initBottomNav();
        initDarkMode();
        initPromoBar();
        initWelcomePopup();
    });

})();

