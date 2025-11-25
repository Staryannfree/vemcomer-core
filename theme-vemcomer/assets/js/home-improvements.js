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

    // ===== Carrossel de Categorias (Scroll Nativo com CSS Grid) =====
    function initCategoriesCarousel() {
        // Nova implementação: CSS Grid com scroll nativo
        const track = document.querySelector('.vc-force-row-track');
        if (!track) {
            // Fallback para estrutura antiga (caso ainda exista)
            const carousel = document.getElementById('categories-carousel');
            if (!carousel) return;
            const oldTrack = carousel.querySelector('.categories-carousel__track');
            if (!oldTrack) return;
            track = oldTrack;
        }

        // Com CSS Grid e scroll nativo, não precisamos de botões ou transform
        // Apenas suporte para arrastar com mouse/touch usando scroll nativo
        
        let isDown = false;
        let startX;
        let scrollLeft;

        // Adicionar cursor grab ao hover
        track.style.cursor = 'grab';

        // Suporte para arrastar com mouse (desktop)
        track.addEventListener('mousedown', (e) => {
            isDown = true;
            track.style.cursor = 'grabbing';
            startX = e.pageX - track.offsetLeft;
            scrollLeft = track.scrollLeft;
        });

        track.addEventListener('mouseleave', () => {
            isDown = false;
            track.style.cursor = 'grab';
        });

        track.addEventListener('mouseup', () => {
            isDown = false;
            track.style.cursor = 'grab';
        });

        track.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - track.offsetLeft;
            const walk = (x - startX) * 1.5; // Velocidade do scroll
            track.scrollLeft = scrollLeft - walk;
        });

        // Suporte para touch (mobile) - scroll nativo já funciona, mas podemos melhorar
        let touchStartX = 0;
        let touchScrollLeft = 0;

        track.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].pageX - track.offsetLeft;
            touchScrollLeft = track.scrollLeft;
        }, { passive: true });

        track.addEventListener('touchmove', (e) => {
            // Deixar o scroll nativo funcionar normalmente
        }, { passive: true });
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
                            // Salvar bairro se disponível
                            saveNeighborhood(address);
                            
                            // Atualizar título do hero (usa cidade)
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
        const popup = document.getElementById('welcome-popup');
        if (!popup) {
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
        // ABORDAGEM 1: Tentar encontrar popup imediatamente
        let popup = document.getElementById('welcome-popup');
        if (!popup) {
            // ABORDAGEM 2: Tentar novamente após um delay
            setTimeout(() => {
                popup = document.getElementById('welcome-popup');
                if (popup) {
                    setupPopupListeners(popup);
                }
            }, 500);
            
            // ABORDAGEM 3: Usar MutationObserver para detectar quando popup é adicionado
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            const foundPopup = node.id === 'welcome-popup' ? node : node.querySelector('#welcome-popup');
                            if (foundPopup) {
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
                    setupPopupListeners(popup);
                    clearInterval(checkInterval);
                } else if (attempts > 20) {
                    clearInterval(checkInterval);
                }
            }, 200);
            
            return;
        }
        
        setupPopupListeners(popup);
    }
    
    function setupPopupListeners(popup) {
        if (!popup) {
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
                e.preventDefault();
                e.stopPropagation();
                popupEl.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                return;
            }
            
            const skipBtn = target.closest('#welcome-popup-skip-btn');
            if (skipBtn) {
                e.preventDefault();
                e.stopPropagation();
                popupEl.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                return;
            }
            
            const locationBtn = target.closest('#welcome-popup-location-btn');
            if (locationBtn) {
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
                e.preventDefault();
                e.stopPropagation();
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            }, true);
        }
        
        if (skipBtn) {
            skipBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                popup.classList.remove('is-open');
                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
            }, true);
        }
        
        if (locationBtn) {
            locationBtn.addEventListener('click', function(e) {
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

        // Verificar se deve mostrar o popup:
        // 1. Se já tem localização salva, não mostrar
        if (savedLocation) {
            return; // Já tem localização, não precisa mostrar popup
        }

        // 2. Verificar se popup já foi visto (cookie)
        const popupSeen = document.cookie.split(';').some(c => c.trim().startsWith('vc_welcome_popup_seen=1'));
        if (popupSeen) {
            return; // Já foi visto antes, não mostrar novamente
        }

        // 3. Se chegou aqui, mostrar popup (primeira visita e sem localização)
        setTimeout(() => {
            popup.classList.add('is-open');
        }, 1500);

        // Função para lidar com o clique no botão de localização
        function handleLocationButtonClick(btn, popupElement) {
            if (!navigator.geolocation) {
                alert('Geolocalização não suportada pelo seu navegador.');
                return;
            }

            // ATUALIZAR TEXTO DO HERO IMEDIATAMENTE (antes de pedir permissão)
            // Tentar obter cidade salva de cookie ou localStorage
            let cityName = localStorage.getItem('vc_user_city');
            
            // Se não tem cidade no localStorage, tentar obter do cookie
            if (!cityName) {
                // Função auxiliar para ler cookie
                function getCookie(name) {
                    const value = `; ${document.cookie}`;
                    const parts = value.split(`; ${name}=`);
                    if (parts.length === 2) return parts.pop().split(';').shift();
                    return null;
                }
                
                const savedLocationCookie = getCookie('vc_user_location');
                if (savedLocationCookie) {
                    try {
                        const locationData = JSON.parse(savedLocationCookie);
                        if (locationData.city) {
                            cityName = locationData.city;
                        }
                    } catch (e) {
                        // Ignorar erro
                    }
                }
            }
            
            // Se ainda não tem cidade, tentar do localStorage location
            if (!cityName) {
                const savedLocation = localStorage.getItem('vc_user_location');
                if (savedLocation) {
                    try {
                        const locationData = JSON.parse(savedLocation);
                        if (locationData.city) {
                            cityName = locationData.city;
                        }
                    } catch (e) {
                        // Ignorar erro
                    }
                }
            }
            
            // Atualizar hero title imediatamente
            if (cityName) {
                updateHeroTitle(cityName);
            } else {
                // Texto genérico enquanto obtém localização
                const heroTitle = document.getElementById('hero-title');
                if (heroTitle) {
                    heroTitle.textContent = 'Peça dos melhores estabelecimentos da sua cidade';
                }
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
                        // Salvar bairro se disponível
                        saveNeighborhood(address);
                        
                        localStorage.setItem('vc_location_accepted', 'true');
                        const finalCityName = address.city || address.displayName || cityName;
                        updateHeroTitle(finalCityName);
                        
                        btn.classList.remove('is-loading');
                        btn.disabled = false;
                        btn.innerHTML = originalHTML;
                        
                        popupElement.classList.remove('is-open');
                        document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                        
                        const heroLocationBtn = document.getElementById('vc-use-location');
                        if (heroLocationBtn) {
                            heroLocationBtn.classList.add('is-active');
                        }
                        
                        // Atualizar subtítulo com número de restaurantes
                        if (window.updateHeroSubtitleWithRestaurantCount) {
                            window.updateHeroSubtitleWithRestaurantCount(finalCityName);
                        }
                        
                        // Filtrar restaurantes por cidade
                        if (window.filterRestaurantsByCity) {
                            window.filterRestaurantsByCity(finalCityName);
                        } else {
                            loadRestaurantsWithLocation(coordinates.lat, coordinates.lng);
                        }
                        showNotification('Localização atualizada!', 'success');
                        
                        // REMOVIDO: scrollIntoView - manter página no topo
                        // Manter scroll no topo da página
                        window.scrollTo({ top: 0, behavior: 'smooth' });
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
                        
                        // Obter nome da cidade via reverse geocoding
                        fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + lat + '&lon=' + lng + '&addressdetails=1')
                            .then(response => response.json())
                            .then(data => {
                                const finalCityName = data.address?.city || 
                                    data.address?.town || 
                                    data.address?.municipality || 
                                    data.address?.county || 
                                    data.display_name?.split(',')[0] || 
                                    cityName || 
                                    'sua cidade';
                                
                                localStorage.setItem('vc_user_city', finalCityName);
                                updateHeroTitle(finalCityName);
                                
                                btn.classList.remove('is-loading');
                                btn.disabled = false;
                                btn.innerHTML = originalHTML;
                                
                                popupElement.classList.remove('is-open');
                                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                                
                                const heroLocationBtn = document.getElementById('vc-use-location');
                                if (heroLocationBtn) {
                                    heroLocationBtn.classList.add('is-active');
                                }
                                
                                loadRestaurantsWithLocation(lat, lng);
                                showNotification('Localização atualizada!', 'success');
                                
                                // REMOVIDO: scrollIntoView - manter página no topo
                                // Manter scroll no topo da página
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            })
                            .catch(error => {
                                console.error('Erro ao obter nome da cidade:', error);
                                // Mesmo sem cidade, atualizar com texto genérico
                                updateHeroTitle(cityName || 'sua cidade');
                                
                                btn.classList.remove('is-loading');
                                btn.disabled = false;
                                btn.innerHTML = originalHTML;
                                
                                popupElement.classList.remove('is-open');
                                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                                
                                showNotification('Localização atualizada!', 'success');
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                            });
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
            heroTitle.textContent = `Peça dos melhores estabelecimentos de ${cityName}`;
            // Salvar cidade no localStorage
            localStorage.setItem('vc_user_city', cityName);
            
            // Disparar evento para atualizar mobile top bar
            document.dispatchEvent(new CustomEvent('vemcomer:location-updated', {
                detail: { city: cityName }
            }));
        }
    }
    
    // Salvar bairro quando disponível no endereço
    function saveNeighborhood(address) {
        if (address && address.neighborhood) {
            localStorage.setItem('vc_user_neighborhood', address.neighborhood);
            // Salvar também no cookie para acesso no PHP
            document.cookie = `vc_user_neighborhood=${encodeURIComponent(address.neighborhood)}; path=/; max-age=${30 * 24 * 60 * 60}`;
            
            // Disparar evento para atualizar mobile top bar
            document.dispatchEvent(new CustomEvent('vemcomer:location-updated', {
                detail: { neighborhood: address.neighborhood }
            }));
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
            
            // Atualizar subtítulo se já tiver cidade salva
            const savedCity = localStorage.getItem('vc_user_city');
            if (savedCity && window.updateHeroSubtitleWithRestaurantCount) {
                window.updateHeroSubtitleWithRestaurantCount(savedCity);
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
    
    // Expor função globalmente
    window.showNotification = showNotification;

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
    
    // Função global para atualizar subtítulo com número de restaurantes
    window.updateHeroSubtitleWithRestaurantCount = function(cityName) {
        const subtitle = document.getElementById('hero-subtitle');
        if (!subtitle) return;
        
        const REST_BASE = window.vemcomerTheme?.restUrl || '/wp-json/vemcomer/v1/';
        const NONCE = window.vemcomerTheme?.nonce || '';
        const cleanCityName = cityName.split(',')[0].trim();
        
        // Buscar restaurantes da cidade
        fetch(`${REST_BASE}restaurants?city=${encodeURIComponent(cleanCityName)}&per_page=100`, {
            headers: {
                'X-WP-Nonce': NONCE,
            },
        })
        .then(res => res.json())
        .then(data => {
            let restaurants = data || [];
            
            // Se não encontrou via API, buscar todos e filtrar no frontend
            if (restaurants.length === 0) {
                return fetch(`${REST_BASE}restaurants?per_page=100`, {
                    headers: {
                        'X-WP-Nonce': NONCE,
                    },
                }).then(res => res.json());
            }
            return restaurants;
        })
        .then(restaurants => {
            // Filtrar por cidade no frontend se necessário
            if (restaurants && restaurants.length > 0) {
                const filtered = restaurants.filter(rest => {
                    const address = rest.address || '';
                    return address.toLowerCase().includes(cleanCityName.toLowerCase());
                });
                
                const count = filtered.length;
                
                // Atualizar subtítulo
                if (count > 0) {
                    subtitle.textContent = `${count} ${count === 1 ? 'restaurante cadastrado' : 'restaurantes cadastrados'} na sua região`;
                } else {
                    subtitle.textContent = 'Nenhum restaurante encontrado na sua região no momento';
                }
            } else {
                subtitle.textContent = 'Nenhum restaurante encontrado na sua região no momento';
            }
        })
        .catch(err => {
            console.error('Erro ao buscar contagem de restaurantes:', err);
            // Manter subtítulo original em caso de erro
        });
    };
    
    // Função global para filtrar restaurantes por cidade
    window.filterRestaurantsByCity = function(cityName) {
        if (!cityName) {
            console.error('Nome da cidade não fornecido');
            return;
        }
        
        // Atualizar subtítulo com número de restaurantes
        updateHeroSubtitleWithRestaurantCount(cityName);
        
        const container = document.getElementById('restaurants-content') || document.querySelector('.vc-restaurants') || document.querySelector('.vc-grid');
        if (!container) {
            console.error('Container de restaurantes não encontrado');
            return;
        }
        
        // Mostrar skeleton loading
        const skeleton = document.getElementById('skeleton-loading');
        if (skeleton) {
            skeleton.style.display = 'grid';
        }
        container.style.display = 'none';
        
        // Buscar restaurantes filtrados por cidade
        const REST_BASE = window.vemcomerTheme?.restUrl || '/wp-json/vemcomer/v1/';
        const NONCE = window.vemcomerTheme?.nonce || '';
        
        // Extrair nome da cidade (remover informações extras)
        const cleanCityName = cityName.split(',')[0].trim();
        
        fetch(`${REST_BASE}restaurants?city=${encodeURIComponent(cleanCityName)}&per_page=50`, {
            headers: {
                'X-WP-Nonce': NONCE,
            },
        })
        .then(res => res.json())
        .then(data => {
            // Se não encontrou restaurantes, tentar buscar todos e filtrar no frontend
            if (!data || data.length === 0) {
                return fetch(`${REST_BASE}restaurants?per_page=100`, {
                    headers: {
                        'X-WP-Nonce': NONCE,
                    },
                }).then(res => res.json());
            }
            return data;
        })
        .then(restaurants => {
            // Filtrar por cidade no frontend se necessário
            if (restaurants && restaurants.length > 0) {
                const filtered = restaurants.filter(rest => {
                    const address = rest.address || '';
                    return address.toLowerCase().includes(cleanCityName.toLowerCase());
                });
                
                // Se encontrou restaurantes filtrados, atualizar a lista
                if (filtered.length > 0) {
                    updateRestaurantsList(filtered, container);
                } else {
                    // Mostrar mensagem se não encontrou
                    container.innerHTML = `<div class="vc-empty"><p>Nenhum restaurante encontrado em ${cleanCityName}.</p></div>`;
                    container.style.display = 'block';
                }
            } else {
                container.innerHTML = `<div class="vc-empty"><p>Nenhum restaurante encontrado em ${cleanCityName}.</p></div>`;
                container.style.display = 'block';
            }
            
            if (skeleton) {
                skeleton.style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Erro ao filtrar restaurantes:', err);
            if (skeleton) {
                skeleton.style.display = 'none';
            }
            container.style.display = 'block';
        });
    };
    
    // Função para atualizar a lista de restaurantes
    function updateRestaurantsList(restaurants, container) {
        // Se o container já tem cards renderizados, substituir
        if (container.classList.contains('vc-restaurants') || container.classList.contains('vc-grid')) {
            // Limpar container
            container.innerHTML = '';
            
            // Renderizar novos cards
            restaurants.forEach(rest => {
                const card = createRestaurantCard(rest);
                container.appendChild(card);
            });
        } else {
            // Se não é o container de cards, procurar pelo container correto
            const cardsContainer = document.querySelector('.vc-restaurants') || document.querySelector('.vc-grid');
            if (cardsContainer) {
                cardsContainer.innerHTML = '';
                restaurants.forEach(rest => {
                    const card = createRestaurantCard(rest);
                    cardsContainer.appendChild(card);
                });
            }
        }
        
        container.style.display = 'block';
    }
    
    // Função para criar um card de restaurante
    function createRestaurantCard(rest) {
        const card = document.createElement('div');
        card.className = 'vc-card';
        card.dataset.id = rest.id;
        
        const isOpen = rest.is_open ? 'Aberto' : 'Fechado';
        const rating = rest.rating?.average || 0;
        const ratingCount = rest.rating?.count || 0;
        
        card.innerHTML = `
            <div class="vc-card__favorite">
                <button class="vc-favorite-btn" data-restaurant-id="${rest.id}" aria-label="Adicionar aos favoritos">🤍</button>
            </div>
            <a class="vc-card__link" href="/restaurante/?restaurant_id=${rest.id}">
                <div class="vc-card__thumb">
                    <img src="${rest.image || ''}" alt="${rest.title}" loading="lazy" />
                </div>
                <div class="vc-card__body">
                    <h3 class="vc-card__title">${rest.title}</h3>
                    <div class="vc-card__status">
                        <span class="vc-badge vc-badge--${rest.is_open ? 'open' : 'closed'}">
                            ${isOpen}
                        </span>
                    </div>
                    ${rest.address ? `<p class="vc-card__line">${rest.address}</p>` : ''}
                    ${rating > 0 ? `
                        <div class="vc-card__rating">
                            <span class="vc-rating-stars">${'⭐'.repeat(Math.floor(rating))}</span>
                            <span class="vc-rating-text">${rating.toFixed(1)} (${ratingCount})</span>
                        </div>
                    ` : ''}
                </div>
            </a>
        `;
        
        return card;
    }

    function renderRestaurants(restaurants) {
        // Implementar renderização dinâmica se necessário
        // Por enquanto, os shortcodes já fazem isso
    }

    // ===== Inicializar tudo =====
    function initAll() {
        initQuickFilters();
        initGeolocation();
        initCategoriesCarousel();
        initSearchAutocomplete();
        initSkeletonLoading();
        initLazyLoad();
        initStickyHeader();
        initBottomNav();
        initDarkMode();
        initPromoBar();
        checkHeroLocationButton();
        
        // Inicializar popup com múltiplas tentativas
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
        initWelcomePopup();
    });
    
    // Tentar novamente após um delay maior
    setTimeout(() => {
        initWelcomePopup();
    }, 2000);
    
    // Última tentativa após 5 segundos (apenas se necessário)
    setTimeout(() => {
        const popup = document.getElementById('welcome-popup');
        if (popup) {
            setupPopupListeners(popup);
            
            // Verificar novamente se deve mostrar
            const savedLocation = localStorage.getItem('vc_user_location');
            const popupSeen = document.cookie.split(';').some(c => c.trim().startsWith('vc_welcome_popup_seen=1'));
            
            if (!savedLocation && !popupSeen) {
                setTimeout(() => {
                    popup.classList.add('is-open');
                }, 500);
            }
        }
    }, 5000);

})();

