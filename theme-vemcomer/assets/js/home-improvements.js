/**
 * JavaScript para melhorias da Home
 * @package VemComer
 */

(function() {
    'use strict';
    
    console.log('home-improvements.js carregado!');

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

        btn.addEventListener('click', async () => {
            if (!navigator.geolocation) {
                alert('Geolocalização não suportada pelo seu navegador.');
                return;
            }

            btn.classList.add('is-loading');
            btn.disabled = true;

            try {
                // Usar reverse geocoding se disponível
                if (window.VemComerReverseGeocode) {
                    await window.VemComerReverseGeocode.getLocationAndFill({
                        fillCheckout: false,
                        onSuccess: (address, coordinates) => {
                            // Atualizar título do hero
                            updateHeroTitle(address.city || address.displayName);
                            
                            // Atualizar UI
                            btn.classList.remove('is-loading');
                            btn.classList.add('is-active');
                            btn.disabled = false;
                            
                            // Recarregar restaurantes com distância
                            loadRestaurantsWithLocation(coordinates.lat, coordinates.lng);
                            
                            showNotification('Localização atualizada!', 'success');
                        },
                        onError: (error) => {
                            btn.classList.remove('is-loading');
                            btn.disabled = false;
                            alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
                        }
                    });
                } else {
                    // Fallback sem reverse geocoding
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
                }
            } catch (error) {
                btn.classList.remove('is-loading');
                btn.disabled = false;
                alert('Erro ao processar localização.');
            }
        });

        // Verificar se já tem localização salva
        const savedLocation = localStorage.getItem('vc_user_location');
        if (savedLocation) {
            try {
                const { lat, lng } = JSON.parse(savedLocation);
                btn.classList.add('is-active');
                updateHeroTitleFromLocation();
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

    // ===== Popup Boas-Vindas - Geolocalização =====
    function initWelcomePopup() {
        const popup = document.getElementById('welcome-popup');
        if (!popup) return;

        // Verificar se já tem localização aceita para mostrar botão
        const savedLocation = localStorage.getItem('vc_user_location');
        const locationAccepted = localStorage.getItem('vc_location_accepted') === 'true';
        
        if (savedLocation && locationAccepted) {
            // Se já tem localização aceita, atualizar título
            updateHeroTitleFromLocation();
        }

        // Verificar se popup já foi visto
        const popupSeen = document.cookie.split(';').some(c => c.trim().startsWith('vc_welcome_popup_seen=1'));
        
        // Função para anexar event listeners quando popup abrir
        const attachPopupListeners = () => {
            // Primeiro anexar todos os listeners básicos
            const { locationBtn } = attachAllPopupListeners();
            
            if (locationBtn && !locationBtn.dataset.listenerAttached) {
                locationBtn.dataset.listenerAttached = 'true';
                console.log('Anexando event listener ao botão de localização do popup');
                locationBtn.style.cursor = 'pointer';
                locationBtn.style.pointerEvents = 'auto';
                locationBtn.style.position = 'relative';
                locationBtn.style.zIndex = '1001';
                
                // Usar exatamente a mesma lógica do botão da home que funciona
                locationBtn.addEventListener('click', async () => {
                    console.log('Clique no botão do popup detectado!');
                    if (!navigator.geolocation) {
                        alert('Geolocalização não suportada pelo seu navegador.');
                        return;
                    }

                    locationBtn.classList.add('is-loading');
                    locationBtn.disabled = true;

                    try {
                        // Usar reverse geocoding se disponível (mesma lógica do botão da home)
                        if (window.VemComerReverseGeocode) {
                            await window.VemComerReverseGeocode.getLocationAndFill({
                                fillCheckout: false,
                                onSuccess: (address, coordinates) => {
                                    // Salvar que aceitou localização
                                    localStorage.setItem('vc_location_accepted', 'true');
                                    
                                    // Atualizar título do hero
                                    updateHeroTitle(address.city || address.displayName);
                                    
                                    // Atualizar UI do botão do popup
                                    locationBtn.classList.remove('is-loading');
                                    locationBtn.disabled = false;
                                    
                                    // Fechar popup
                                    closePopup();
                                    
                                    // Ativar botão da home
                                    const heroLocationBtn = document.getElementById('vc-use-location');
                                    if (heroLocationBtn) {
                                        heroLocationBtn.classList.add('is-active');
                                    }
                                    
                                    // Recarregar restaurantes com distância
                                    loadRestaurantsWithLocation(coordinates.lat, coordinates.lng);
                                    
                                    showNotification('Localização atualizada!', 'success');
                                    
                                    // Scroll suave para restaurantes
                                    setTimeout(() => {
                                        const restaurantsSection = document.getElementById('restaurants-list');
                                        if (restaurantsSection) {
                                            restaurantsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                        }
                                    }, 500);
                                },
                                onError: (error) => {
                                    locationBtn.classList.remove('is-loading');
                                    locationBtn.disabled = false;
                                    alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
                                }
                            });
                        } else {
                            // Fallback sem reverse geocoding (mesma lógica do botão da home)
                            navigator.geolocation.getCurrentPosition(
                                (position) => {
                                    const lat = position.coords.latitude;
                                    const lng = position.coords.longitude;
                                    
                                    // Salvar no localStorage
                                    localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                                    localStorage.setItem('vc_location_accepted', 'true');
                                    
                                    // Atualizar UI do botão do popup
                                    locationBtn.classList.remove('is-loading');
                                    locationBtn.disabled = false;
                                    
                                    // Fechar popup
                                    closePopup();
                                    
                                    // Ativar botão da home
                                    const heroLocationBtn = document.getElementById('vc-use-location');
                                    if (heroLocationBtn) {
                                        heroLocationBtn.classList.add('is-active');
                                    }
                                    
                                    // Recarregar restaurantes com distância
                                    loadRestaurantsWithLocation(lat, lng);
                                    
                                    showNotification('Localização atualizada!', 'success');
                                    
                                    // Scroll suave para restaurantes
                                    setTimeout(() => {
                                        const restaurantsSection = document.getElementById('restaurants-list');
                                        if (restaurantsSection) {
                                            restaurantsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                        }
                                    }, 500);
                                },
                                (error) => {
                                    locationBtn.classList.remove('is-loading');
                                    locationBtn.disabled = false;
                                    alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
                                }
                            );
                        }
                    } catch (error) {
                        locationBtn.classList.remove('is-loading');
                        locationBtn.disabled = false;
                        alert('Erro ao processar localização.');
                    }
                });
            }
        };
        
        // Mostrar popup apenas se não foi visto antes
        if (!popupSeen) {
            setTimeout(() => {
                popup.classList.add('is-open');
                // Anexar listeners quando popup abrir
                setTimeout(() => {
                    attachPopupListeners();
                }, 100);
            }, 1500);
        } else {
            // Se popup não será mostrado, anexar listeners mesmo assim
            attachPopupListeners();
        }

        function closePopup(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            console.log('Fechando popup...');
            popup.classList.remove('is-open');
            // Salvar cookie (expira em 30 dias)
            document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
        }

        // Função para anexar todos os listeners do popup
        const attachAllPopupListeners = () => {
            const closeBtn = popup.querySelector('.welcome-popup__close');
            const locationBtn = popup.querySelector('#welcome-popup-location-btn');
            const skipBtn = popup.querySelector('#welcome-popup-skip-btn');
            
            console.log('=== DEBUG POPUP ===');
            console.log('Popup encontrado:', popup);
            console.log('Botão fechar encontrado:', closeBtn);
            console.log('Botão localização encontrado:', locationBtn);
            console.log('Botão pular encontrado:', skipBtn);
            console.log('Popup está aberto?', popup.classList.contains('is-open'));
            console.log('==================');

            // Botão de fechar
            if (closeBtn) {
                closeBtn.style.cursor = 'pointer';
                closeBtn.style.pointerEvents = 'auto';
                closeBtn.style.zIndex = '1001';
                // Remover listeners anteriores
                const newCloseBtn = closeBtn.cloneNode(true);
                closeBtn.parentNode.replaceChild(newCloseBtn, closeBtn);
                newCloseBtn.addEventListener('click', (e) => {
                    console.log('Clique no botão fechar detectado!');
                    e.preventDefault();
                    e.stopPropagation();
                    closePopup(e);
                });
                newCloseBtn.addEventListener('mousedown', (e) => {
                    console.log('Mousedown no botão fechar detectado!');
                    e.preventDefault();
                    e.stopPropagation();
                    closePopup(e);
                });
            } else {
                console.warn('Botão de fechar do popup não encontrado');
            }

            // Botão pular
            if (skipBtn) {
                skipBtn.style.cursor = 'pointer';
                skipBtn.style.pointerEvents = 'auto';
                skipBtn.style.zIndex = '1001';
                skipBtn.style.position = 'relative';
                // Remover listeners anteriores
                const newSkipBtn = skipBtn.cloneNode(true);
                skipBtn.parentNode.replaceChild(newSkipBtn, skipBtn);
                newSkipBtn.addEventListener('click', (e) => {
                    console.log('Clique no botão pular detectado!');
                    e.preventDefault();
                    e.stopPropagation();
                    closePopup(e);
                });
                newSkipBtn.addEventListener('mousedown', (e) => {
                    console.log('Mousedown no botão pular detectado!');
                    e.preventDefault();
                    e.stopPropagation();
                    closePopup(e);
                });
            } else {
                console.warn('Botão "Pular" do popup não encontrado');
            }
            
            return { locationBtn, skipBtn: skipBtn || popup.querySelector('#welcome-popup-skip-btn') };
        };

        // Anexar listeners iniciais
        attachAllPopupListeners();
        attachPopupListeners();

        // Fechar ao clicar fora
        popup.addEventListener('click', (e) => {
            if (e.target === popup) {
                closePopup();
            }
        }, true);

        // Garantir que o dialog seja clicável
        const dialog = popup.querySelector('.welcome-popup__dialog');
        if (dialog) {
            dialog.style.pointerEvents = 'auto';
            dialog.addEventListener('click', (e) => {
                e.stopPropagation();
            }, true);
        }
    }
    
    // Atualizar título do hero com nome da cidade
    function updateHeroTitle(cityName) {
        const heroTitle = document.getElementById('hero-title');
        if (heroTitle && cityName) {
            heroTitle.textContent = `Peça dos melhores restaurantes de ${cityName}`;
            // Salvar cidade no localStorage
            localStorage.setItem('vc_user_city', cityName);
        }
    }
    
    // Atualizar título do hero a partir da localização salva
    function updateHeroTitleFromLocation() {
        const savedCity = localStorage.getItem('vc_user_city');
        if (savedCity) {
            updateHeroTitle(savedCity);
        } else {
            // Tentar obter cidade do endereço salvo
            const savedAddress = localStorage.getItem('vc_user_address');
            if (savedAddress) {
                try {
                    const address = JSON.parse(savedAddress);
                    if (address.city) {
                        updateHeroTitle(address.city);
                    }
                } catch (e) {
                    // Ignorar erro
                }
            }
        }
    }
    
    // Verificar se deve mostrar botão no hero ao carregar
    function checkHeroLocationButton() {
        // Garantir que o botão sempre apareça
        const heroLocationActions = document.getElementById('hero-location-actions');
        if (heroLocationActions) {
            heroLocationActions.style.display = 'block';
        }
        
        const locationAccepted = localStorage.getItem('vc_location_accepted') === 'true';
        const savedLocation = localStorage.getItem('vc_user_location');
        
        if (locationAccepted && savedLocation) {
            updateHeroTitleFromLocation();
            const heroLocationBtn = document.getElementById('vc-use-location');
            if (heroLocationBtn) {
                heroLocationBtn.classList.add('is-active');
            }
        }
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
        // Inicializar popup com um pequeno delay para garantir que o DOM esteja pronto
        setTimeout(() => {
            initWelcomePopup();
        }, 100);
        checkHeroLocationButton();
    });

})();

