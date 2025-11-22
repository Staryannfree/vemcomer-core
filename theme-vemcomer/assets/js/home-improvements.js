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
    // MÚLTIPLAS ABORDAGENS PARA GARANTIR FUNCIONAMENTO
    
    // Função global para lidar com cliques no popup (pode ser chamada de qualquer lugar)
    window.handleWelcomePopupClick = function(btnId) {
        console.log('handleWelcomePopupClick chamada para:', btnId);
        const popup = document.getElementById('welcome-popup');
        if (!popup) {
            console.warn('Popup não encontrado!');
            return;
        }
        
        if (btnId === 'close' || btnId === 'skip') {
            popup.classList.remove('is-open');
            document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
        } else if (btnId === 'location') {
            const locationBtn = document.getElementById('welcome-popup-location-btn');
            if (locationBtn) {
                handleLocationButtonClick(locationBtn, popup);
            }
        }
    };
    
    function initWelcomePopup() {
        console.log('=== initWelcomePopup chamada - MÚLTIPLAS ABORDAGENS ===');
        console.log('Document ready state:', document.readyState);
        console.log('Window loaded:', window.performance.timing.loadEventEnd > 0);
        
        // ABORDAGEM 1: Tentar encontrar popup imediatamente
        let popup = document.getElementById('welcome-popup');
        console.log('Tentativa 1 - Popup encontrado?', !!popup);
        if (!popup) {
            console.warn('❌ Popup não encontrado imediatamente, tentando novamente...');
            console.log('Todos os elementos com ID:', Array.from(document.querySelectorAll('[id]')).map(el => el.id));
            // ABORDAGEM 2: Tentar novamente após um delay
            setTimeout(() => {
                popup = document.getElementById('welcome-popup');
                if (popup) {
                    console.log('Popup encontrado no segundo try!');
                    setupPopupListeners(popup);
                } else {
                    console.error('Popup ainda não encontrado após delay!');
                }
            }, 500);
            
            // ABORDAGEM 3: Usar MutationObserver para detectar quando popup é adicionado
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            const foundPopup = node.id === 'welcome-popup' ? node : node.querySelector('#welcome-popup');
                            if (foundPopup) {
                                console.log('Popup detectado via MutationObserver!');
                                setupPopupListeners(foundPopup);
                                observer.disconnect();
                            }
                        }
                    });
                });
            });
            observer.observe(document.body, { childList: true, subtree: true });
            
            // ABORDAGEM 4: Verificar periodicamente
            let attempts = 0;
            const checkInterval = setInterval(() => {
                attempts++;
                popup = document.getElementById('welcome-popup');
                if (popup) {
                    console.log('Popup encontrado via setInterval (tentativa ' + attempts + ')!');
                    setupPopupListeners(popup);
                    clearInterval(checkInterval);
                } else if (attempts > 20) {
                    console.error('Popup não encontrado após 20 tentativas!');
                    clearInterval(checkInterval);
                }
            }, 200);
            
            return;
        }
        
        console.log('✅ Popup encontrado imediatamente!', popup);
        console.log('Popup classes:', popup.className);
        console.log('Popup parent:', popup.parentElement);
        console.log('Popup no DOM?', document.body.contains(popup));
        setupPopupListeners(popup);
    }
    
    function setupPopupListeners(popup) {
        console.log('=== setupPopupListeners chamada ===');
        console.log('Popup recebido:', popup);
        console.log('Popup ID:', popup.id);
        console.log('Popup classes:', popup.className);
        console.log('Popup no DOM?', document.body.contains(popup));
        
        if (!popup) {
            console.error('❌ Popup é null ou undefined!');
            return;
        }
        
        // ABORDAGEM 1: Event delegation no document (capture phase)
        function handlePopupClick(e) {
            const target = e.target;
            const popupEl = document.getElementById('welcome-popup');
            if (!popupEl || !popupEl.classList.contains('is-open')) {
                return;
            }
            
            if (target.closest('.welcome-popup__close')) {
                console.log('Clique fechar (delegation)');
                e.preventDefault();
                e.stopPropagation();
                popupEl.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                return;
            }
            
            const skipBtn = target.closest('#welcome-popup-skip-btn');
            if (skipBtn) {
                console.log('Clique pular (delegation)');
                e.preventDefault();
                e.stopPropagation();
                popupEl.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                return;
            }
            
            const locationBtn = target.closest('#welcome-popup-location-btn');
            if (locationBtn) {
                console.log('Clique localização (delegation)');
                e.preventDefault();
                e.stopPropagation();
                handleLocationButtonClick(locationBtn, popupEl);
                return;
            }
        }
        document.addEventListener('click', handlePopupClick, true);
        
        // ABORDAGEM 2: Listeners diretos nos botões
        const closeBtn = popup.querySelector('.welcome-popup__close');
        const locationBtn = popup.querySelector('#welcome-popup-location-btn');
        const skipBtn = popup.querySelector('#welcome-popup-skip-btn');
        
        if (closeBtn) {
            closeBtn.onclick = function(e) {
                console.log('Clique fechar (onclick direto)');
                e.preventDefault();
                e.stopPropagation();
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            };
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.pointerEvents = 'auto';
        }
        
        if (skipBtn) {
            skipBtn.onclick = function(e) {
                console.log('Clique pular (onclick direto)');
                e.preventDefault();
                e.stopPropagation();
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            };
            skipBtn.style.cursor = 'pointer';
            skipBtn.style.pointerEvents = 'auto';
        }
        
        if (locationBtn) {
            locationBtn.onclick = function(e) {
                console.log('Clique localização (onclick direto)');
                e.preventDefault();
                e.stopPropagation();
                handleLocationButtonClick(locationBtn, popup);
            };
            locationBtn.style.cursor = 'pointer';
            locationBtn.style.pointerEvents = 'auto';
        }
        
        // ABORDAGEM 3: Adicionar também addEventListener como backup
        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                console.log('Clique fechar (addEventListener)');
                e.preventDefault();
                e.stopPropagation();
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            }, true);
        }
        
        if (skipBtn) {
            skipBtn.addEventListener('click', function(e) {
                console.log('Clique pular (addEventListener)');
                e.preventDefault();
                e.stopPropagation();
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            }, true);
        }
        
        if (locationBtn) {
            locationBtn.addEventListener('click', function(e) {
                console.log('Clique localização (addEventListener)');
                e.preventDefault();
                e.stopPropagation();
                handleLocationButtonClick(locationBtn, popup);
            }, true);
        }

        // Verificar se já tem localização aceita para mostrar botão
        const savedLocation = localStorage.getItem('vc_user_location');
        const locationAccepted = localStorage.getItem('vc_location_accepted') === 'true';
        
        if (savedLocation && locationAccepted) {
            // Se já tem localização aceita, atualizar título
            updateHeroTitleFromLocation();
        }

        // Verificar se popup já foi visto
        const popupSeen = document.cookie.split(';').some(c => c.trim().startsWith('vc_welcome_popup_seen=1'));
        console.log('Cookie popup visto:', popupSeen);
        console.log('Cookies atuais:', document.cookie);
        
        // TEMPORÁRIO: Forçar exibição do popup para debug (remover depois)
        // Mostrar popup sempre, ignorando cookie por enquanto
        console.log('Forçando exibição do popup...');
        setTimeout(() => {
            popup.classList.add('is-open');
            console.log('Popup deve estar visível agora. Classe is-open adicionada:', popup.classList.contains('is-open'));
            console.log('Popup element:', popup);
            console.log('Popup display:', window.getComputedStyle(popup).display);
            console.log('Popup opacity:', window.getComputedStyle(popup).opacity);
            console.log('Popup visibility:', window.getComputedStyle(popup).visibility);
            console.log('Popup z-index:', window.getComputedStyle(popup).zIndex);
        }, 1500);

        // Função para lidar com o clique no botão de localização
        function handleLocationButtonClick(btn, popupElement) {
            if (!navigator.geolocation) {
                alert('Geolocalização não suportada pelo seu navegador.');
                return;
            }

            btn.classList.add('is-loading');
            btn.disabled = true;
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<span>Obtendo localização...</span>';

            // Usar exatamente a mesma lógica do botão da home que funciona
            if (window.VemComerReverseGeocode) {
                window.VemComerReverseGeocode.getLocationAndFill({
                    fillCheckout: false,
                    onSuccess: (address, coordinates) => {
                        localStorage.setItem('vc_location_accepted', 'true');
                        updateHeroTitle(address.city || address.displayName);
                        
                        btn.classList.remove('is-loading');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        
                        popupElement.classList.remove('is-open');
                        document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                        
                        const heroLocationBtn = document.getElementById('vc-use-location');
                        if (heroLocationBtn) {
                            heroLocationBtn.classList.add('is-active');
                        }
                        
                        // Salvar localização no localStorage
                        localStorage.setItem('vc_user_location', JSON.stringify({ lat: coordinates.lat, lng: coordinates.lng }));
                        
                        // Redirecionar para home com coordenadas na URL
                        // Formato: seusite.com/?lat=-16.68&lng=-49.26
                        const url = new URL(window.location.href);
                        url.searchParams.set('lat', coordinates.lat.toFixed(6));
                        url.searchParams.set('lng', coordinates.lng.toFixed(6));
                        
                        // Remover âncora se existir e adicionar #restaurants-list
                        url.hash = 'restaurants-list';
                        
                        // Redirecionar
                        window.location.href = url.toString();
                    },
                    onError: (error) => {
                        btn.classList.remove('is-loading');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
                    }
                });
            } else {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
                        localStorage.setItem('vc_location_accepted', 'true');
                        
                        btn.classList.remove('is-loading');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        
                        popupElement.classList.remove('is-open');
                        document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                        
                        const heroLocationBtn = document.getElementById('vc-use-location');
                        if (heroLocationBtn) {
                            heroLocationBtn.classList.add('is-active');
                        }
                        
                        // Redirecionar para home com coordenadas na URL
                        // Formato: seusite.com/?lat=-16.68&lng=-49.26
                        const url = new URL(window.location.href);
                        url.searchParams.set('lat', lat.toFixed(6));
                        url.searchParams.set('lng', lng.toFixed(6));
                        
                        // Remover âncora se existir e adicionar #restaurants-list
                        url.hash = 'restaurants-list';
                        
                        // Redirecionar
                        window.location.href = url.toString();
                    },
                    (error) => {
                        btn.classList.remove('is-loading');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
                    }
                );
            }
        }
        
        // Fechar ao clicar fora do dialog
        popup.addEventListener('click', (e) => {
            if (e.target === popup) {
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            }
        }, true);
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
    function initAll() {
        console.log('Inicializando home-improvements...');
        initQuickFilters();
        initGeolocation();
        initSearchAutocomplete();
        initSkeletonLoading();
        initLazyLoad();
        initStickyHeader();
        initBottomNav();
        initDarkMode();
        initPromoBar();
        checkHeroLocationButton();
        
        // Inicializar popup com múltiplas tentativas
        console.log('Tentando inicializar popup...');
        initWelcomePopup();
    }
    
    // Tentar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        // DOM já está pronto
        initAll();
    }
    
    // Também tentar quando window estiver completamente carregado
    window.addEventListener('load', () => {
        console.log('Window load - tentando inicializar popup novamente...');
        initWelcomePopup();
    });
    
    // Tentar novamente após um delay maior
    setTimeout(() => {
        console.log('Delay adicional (2s) - tentando inicializar popup...');
        initWelcomePopup();
    }, 2000);
    
    // Última tentativa após 5 segundos
    setTimeout(() => {
        console.log('Última tentativa (5s) - tentando inicializar popup...');
        const popup = document.getElementById('welcome-popup');
        if (popup) {
            console.log('✅ Popup encontrado na última tentativa!');
            setupPopupListeners(popup);
            // Forçar exibição
            setTimeout(() => {
                popup.classList.add('is-open');
                console.log('Popup forçado a aparecer na última tentativa');
            }, 500);
        } else {
            console.error('❌ Popup AINDA não encontrado após 5 segundos!');
            console.log('Todos os elementos no body:', Array.from(document.body.children).map(el => el.tagName + (el.id ? '#' + el.id : '')));
        }
    }, 5000);

})();

