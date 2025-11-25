/**
 * Mobile App Navigation - JavaScript
 * Funcionalidades para navegação estilo app nativo
 * @package VemComer
 */

(function() {
    'use strict';
    
    /**
     * Atualiza o texto do endereço no top bar mobile
     */
    function updateMobileAddress() {
        const addressText = document.getElementById('mobile-address-text');
        if (!addressText) return;
        
        // Tentar obter cidade do localStorage
        let cityName = localStorage.getItem('vc_user_city');
        
        // Se não tem cidade, tentar do cookie
        if (!cityName) {
            const savedLocationCookie = getCookie('vc_user_location');
            if (savedLocationCookie) {
                try {
                    const locationData = JSON.parse(savedLocationCookie);
                    if (locationData.city) {
                        cityName = locationData.city;
                    } else if (locationData.address) {
                        cityName = locationData.address;
                    }
                } catch (e) {
                    // Ignorar erro
                }
            }
        }
        
        // Se ainda não tem, tentar do localStorage location
        if (!cityName) {
            const savedLocation = localStorage.getItem('vc_user_location');
            if (savedLocation) {
                try {
                    const locationData = JSON.parse(savedLocation);
                    if (locationData.city) {
                        cityName = locationData.city;
                    } else if (locationData.address) {
                        cityName = locationData.address;
                    }
                } catch (e) {
                    // Ignorar erro
                }
            }
        }
        
        // Atualizar texto
        if (cityName) {
            addressText.textContent = cityName;
        } else {
            addressText.textContent = 'Selecione um endereço';
        }
    }
    
    /**
     * Função auxiliar para ler cookie
     */
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }
    
    /**
     * Handler para seletor de endereço mobile
     */
    function initMobileAddressSelector() {
        const addressSelector = document.getElementById('mobile-address-selector');
        if (!addressSelector) return;
        
        addressSelector.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Se existe função de geolocalização, usar ela
            if (typeof window.showNotification === 'function') {
                // Disparar evento de geolocalização
                const locationBtn = document.getElementById('welcome-popup-location-btn');
                if (locationBtn) {
                    locationBtn.click();
                } else {
                    // Se não tem popup, pedir localização diretamente
                    requestLocation();
                }
            } else {
                // Fallback: redirecionar para página de busca/endereço
                window.location.href = '/restaurantes/';
            }
        });
        
        // Suporte para teclado
        addressSelector.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                addressSelector.click();
            }
        });
    }
    
    /**
     * Solicitar localização diretamente
     */
    function requestLocation() {
        if (!navigator.geolocation) {
            alert('Geolocalização não é suportada pelo seu navegador.');
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Fazer reverse geocoding
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`, {
                    headers: {
                        'User-Agent': 'Pedevem Marketplace'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    const city = data.address?.city || data.address?.town || data.address?.village || 'Localização';
                    localStorage.setItem('vc_user_city', city);
                    updateMobileAddress();
                    
                    if (typeof window.showNotification === 'function') {
                        window.showNotification('Localização atualizada!', 'success');
                    }
                })
                .catch(error => {
                    console.error('Erro ao obter endereço:', error);
                });
            },
            function(error) {
                console.error('Erro ao obter localização:', error);
                alert('Não foi possível obter sua localização.');
            }
        );
    }
    
    /**
     * Marcar item ativo na bottom nav baseado na URL atual
     */
    function updateActiveBottomNavItem() {
        const currentPath = window.location.pathname;
        const bottomNavItems = document.querySelectorAll('.bottom-nav__item');
        
        bottomNavItems.forEach(item => {
            const href = item.getAttribute('href');
            if (!href) return;
            
            // Remover classe active de todos
            item.classList.remove('active');
            item.removeAttribute('aria-current');
            
            // Verificar se é a página ativa
            if (currentPath === '/' && href === homeUrl) {
                item.classList.add('active');
                item.setAttribute('aria-current', 'page');
            } else if (currentPath !== '/' && href.includes(currentPath)) {
                item.classList.add('active');
                item.setAttribute('aria-current', 'page');
            }
        });
    }
    
    /**
     * Inicialização
     */
    function init() {
        // Atualizar endereço no top bar
        updateMobileAddress();
        
        // Inicializar seletor de endereço
        initMobileAddressSelector();
        
        // Marcar item ativo na bottom nav
        updateActiveBottomNavItem();
        
        // Escutar mudanças de localização (quando o popup atualizar)
        window.addEventListener('storage', function(e) {
            if (e.key === 'vc_user_city' || e.key === 'vc_user_location') {
                updateMobileAddress();
            }
        });
        
        // Escutar eventos customizados de atualização de localização
        document.addEventListener('vemcomer:location-updated', function() {
            updateMobileAddress();
        });
    }
    
    // Variáveis globais
    const homeUrl = typeof vemcomerTheme !== 'undefined' ? vemcomerTheme.homeUrl : '/';
    
    // Aguardar DOM carregado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expor função globalmente para atualização manual
    window.updateMobileAddress = updateMobileAddress;
    
})();

