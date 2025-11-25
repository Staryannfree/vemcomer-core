/**
 * Reverse Geocoding usando Nominatim (OpenStreetMap)
 * Converte latitude/longitude em endereço e preenche campos automaticamente
 * @package VemComerCore
 */

(function() {
    'use strict';

    /**
     * Faz reverse geocoding usando Nominatim
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     * @returns {Promise<Object>} Dados do endereço
     */
    async function reverseGeocode(lat, lng) {
        const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;
        
        try {
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'Pedevem Marketplace' // Nominatim requer User-Agent
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            return parseAddress(data);
        } catch (error) {
            console.error('Erro no reverse geocoding:', error);
            throw error;
        }
    }

    /**
     * Parse do endereço retornado pelo Nominatim
     * @param {Object} data - Dados do Nominatim
     * @returns {Object} Endereço formatado
     */
    function parseAddress(data) {
        const addr = data.address || {};
        
        // Extrair componentes do endereço
        const street = addr.road || addr.pedestrian || '';
        const number = addr.house_number || '';
        const neighborhood = addr.suburb || addr.neighbourhood || addr.quarter || '';
        const city = addr.city || addr.town || addr.village || addr.municipality || '';
        const state = addr.state || addr.region || '';
        const postcode = addr.postcode || '';
        const country = addr.country || '';
        
        // Montar endereço completo
        let fullAddress = '';
        if (street) {
            fullAddress = street;
            if (number) {
                fullAddress += ', ' + number;
            }
        }
        
        if (neighborhood) {
            if (fullAddress) fullAddress += ' - ';
            fullAddress += neighborhood;
        }
        
        if (city) {
            if (fullAddress) fullAddress += ', ';
            fullAddress += city;
        }
        
        if (state) {
            if (fullAddress) fullAddress += ' - ';
            fullAddress += state;
        }
        
        return {
            street: street,
            number: number,
            complement: '',
            neighborhood: neighborhood,
            city: city,
            state: state,
            postcode: postcode,
            country: country,
            fullAddress: fullAddress || data.display_name || '',
            displayName: data.display_name || fullAddress,
            lat: parseFloat(data.lat),
            lng: parseFloat(data.lon),
            raw: data
        };
    }

    /**
     * Preenche campos de endereço no checkout
     * @param {Object} address - Dados do endereço
     */
    function fillCheckoutFields(address) {
        // Procurar campos do checkout
        const checkout = document.querySelector('.vc-checkout');
        if (!checkout) return;

        // Campo de CEP
        const zipInput = checkout.querySelector('.vc-zip');
        if (zipInput && address.postcode) {
            zipInput.value = address.postcode;
        }

        // Campo de endereço completo
        const addressInput = checkout.querySelector('.vc-customer-address');
        if (addressInput && address.fullAddress) {
            addressInput.value = address.fullAddress;
        }

        // Salvar coordenadas no dataset do checkout
        if (address.lat && address.lng) {
            checkout.dataset.customerLat = address.lat;
            checkout.dataset.customerLng = address.lng;
        }

        // Disparar evento para outros scripts
        const event = new CustomEvent('vemcomer:address:filled', {
            detail: address
        });
        document.dispatchEvent(event);
    }

    /**
     * Preenche campos de endereço em formulários genéricos
     * @param {Object} address - Dados do endereço
     */
    function fillGenericFields(address) {
        // Procurar campos comuns
        const fields = {
            'street': ['input[name*="street"]', 'input[name*="rua"]', 'input[name*="logradouro"]'],
            'number': ['input[name*="number"]', 'input[name*="numero"]'],
            'neighborhood': ['input[name*="neighborhood"]', 'input[name*="bairro"]'],
            'city': ['input[name*="city"]', 'input[name*="cidade"]'],
            'state': ['input[name*="state"]', 'input[name*="estado"]', 'select[name*="state"]'],
            'postcode': ['input[name*="postcode"]', 'input[name*="zip"]', 'input[name*="cep"]'],
            'address': ['input[name*="address"]', 'input[name*="endereco"]', 'textarea[name*="address"]']
        };

        Object.keys(fields).forEach(key => {
            if (!address[key]) return;

            fields[key].forEach(selector => {
                const input = document.querySelector(selector);
                if (input && !input.value) {
                    if (input.tagName === 'SELECT') {
                        // Para selects, tentar encontrar option pelo texto
                        const options = input.querySelectorAll('option');
                        options.forEach(opt => {
                            if (opt.textContent.toLowerCase().includes(address[key].toLowerCase())) {
                                input.value = opt.value;
                            }
                        });
                    } else {
                        input.value = address[key];
                    }
                }
            });
        });
    }

    /**
     * Obtém localização e preenche campos
     * @param {Object} options - Opções
     * @param {Function} options.onSuccess - Callback de sucesso
     * @param {Function} options.onError - Callback de erro
     * @param {boolean} options.fillCheckout - Preencher campos do checkout
     * @param {boolean} options.fillGeneric - Preencher campos genéricos
     */
    async function getLocationAndFill(options = {}) {
        const {
            onSuccess,
            onError,
            fillCheckout = true,
            fillGeneric = false
        } = options;

        if (!navigator.geolocation) {
            const error = new Error('Geolocalização não suportada pelo navegador.');
            if (onError) onError(error);
            return;
        }

        try {
            const position = await new Promise((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(
                    resolve,
                    reject,
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            });

            const lat = position.coords.latitude;
            const lng = position.coords.longitude;

            // Fazer reverse geocoding
            const address = await reverseGeocode(lat, lng);

            // Preencher campos
            if (fillCheckout) {
                fillCheckoutFields(address);
            }

            if (fillGeneric) {
                fillGenericFields(address);
            }

            // Salvar localização e endereço
            localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
            localStorage.setItem('vc_user_address', JSON.stringify(address));
            
            // Salvar cidade e bairro separadamente para fácil acesso
            if (address.city) {
                localStorage.setItem('vc_user_city', address.city);
            }
            if (address.neighborhood) {
                localStorage.setItem('vc_user_neighborhood', address.neighborhood);
            }

            if (onSuccess) {
                onSuccess(address, { lat, lng });
            }

            return { address, coordinates: { lat, lng } };
        } catch (error) {
            console.error('Erro ao obter localização:', error);
            if (onError) {
                onError(error);
            }
            throw error;
        }
    }

    // Expor função globalmente
    window.VemComerReverseGeocode = {
        reverseGeocode,
        getLocationAndFill,
        fillCheckoutFields,
        fillGenericFields,
        parseAddress
    };

    // Integrar com botão do checkout
    function initCheckoutLocation() {
        const checkoutLocationBtn = document.getElementById('vc-use-location-checkout');
        if (checkoutLocationBtn) {
            checkoutLocationBtn.addEventListener('click', async () => {
                checkoutLocationBtn.disabled = true;
                checkoutLocationBtn.classList.add('is-loading');
                checkoutLocationBtn.innerHTML = '<span>📍</span><span>Obtendo localização...</span>';

                try {
                    await getLocationAndFill({
                        fillCheckout: true,
                        onSuccess: (address) => {
                            checkoutLocationBtn.classList.remove('is-loading');
                            checkoutLocationBtn.disabled = false;
                            checkoutLocationBtn.innerHTML = '<span>📍</span><span>Usar minha localização</span>';
                            
                            // Mostrar notificação
                            if (window.showNotification) {
                                window.showNotification('Endereço preenchido automaticamente!', 'success');
                            } else {
                                alert('Endereço preenchido com sucesso!');
                            }
                            
                            // Disparar cálculo de frete se botão existir
                            const quoteBtn = document.querySelector('.vc-quote');
                            if (quoteBtn) {
                                setTimeout(() => {
                                    quoteBtn.click();
                                }, 500);
                            }
                        },
                        onError: (error) => {
                            checkoutLocationBtn.classList.remove('is-loading');
                            checkoutLocationBtn.disabled = false;
                            checkoutLocationBtn.innerHTML = '<span>📍</span><span>Usar minha localização</span>';
                            
                            let errorMsg = 'Não foi possível obter sua localização.';
                            if (error.code === error.PERMISSION_DENIED) {
                                errorMsg = 'Permissão de localização negada.';
                            } else if (error.code === error.POSITION_UNAVAILABLE) {
                                errorMsg = 'Localização indisponível.';
                            } else if (error.code === error.TIMEOUT) {
                                errorMsg = 'Tempo de espera esgotado.';
                            }
                            
                            alert(errorMsg);
                        }
                    });
                } catch (error) {
                    checkoutLocationBtn.classList.remove('is-loading');
                    checkoutLocationBtn.disabled = false;
                    checkoutLocationBtn.innerHTML = '<span>📍</span><span>Usar minha localização</span>';
                }
            });
        }
    }

    // Integrar com botão do hero
    document.addEventListener('DOMContentLoaded', () => {
        // Inicializar checkout
        initCheckoutLocation();
        const heroLocationBtn = document.getElementById('vc-use-location');
        if (heroLocationBtn) {
            heroLocationBtn.addEventListener('click', async () => {
                heroLocationBtn.disabled = true;
                heroLocationBtn.classList.add('is-loading');

                try {
                    await getLocationAndFill({
                        onSuccess: (address) => {
                            heroLocationBtn.classList.remove('is-loading');
                            heroLocationBtn.classList.add('is-active');
                            heroLocationBtn.disabled = false;
                            
                            // Mostrar notificação
                            if (window.showNotification) {
                                window.showNotification('Localização e endereço salvos!', 'success');
                            }
                        },
                        onError: (error) => {
                            heroLocationBtn.classList.remove('is-loading');
                            heroLocationBtn.disabled = false;
                            
                            let errorMsg = 'Não foi possível obter sua localização.';
                            if (error.code === error.PERMISSION_DENIED) {
                                errorMsg = 'Permissão de localização negada.';
                            } else if (error.code === error.POSITION_UNAVAILABLE) {
                                errorMsg = 'Localização indisponível.';
                            } else if (error.code === error.TIMEOUT) {
                                errorMsg = 'Tempo de espera esgotado.';
                            }
                            
                            alert(errorMsg);
                        }
                    });
                } catch (error) {
                    heroLocationBtn.classList.remove('is-loading');
                    heroLocationBtn.disabled = false;
                }
            });
        }

        // Integrar com popup de primeira visita
        const welcomeLocationBtn = document.getElementById('welcome-popup-location-btn');
        if (welcomeLocationBtn) {
            welcomeLocationBtn.addEventListener('click', async () => {
                welcomeLocationBtn.disabled = true;
                welcomeLocationBtn.classList.add('is-loading');
                welcomeLocationBtn.innerHTML = '<span>Obtendo localização...</span>';

                try {
                    await getLocationAndFill({
                        onSuccess: (address) => {
                            // Fechar popup
                            const popup = document.getElementById('welcome-popup');
                            if (popup) {
                                popup.classList.remove('is-open');
                                document.cookie = 'vc_welcome_popup_seen=1; path=/; max-age=' + (30 * 24 * 60 * 60);
                            }
                            
                            // Mostrar notificação
                            if (window.showNotification) {
                                window.showNotification('Localização e endereço salvos!', 'success');
                            }
                        },
                        onError: (error) => {
                            welcomeLocationBtn.disabled = false;
                            welcomeLocationBtn.classList.remove('is-loading');
                            welcomeLocationBtn.innerHTML = '<span class="btn-icon">📍</span><span>Usar minha localização</span>';
                            
                            let errorMsg = 'Não foi possível obter sua localização.';
                            if (error.code === error.PERMISSION_DENIED) {
                                errorMsg = 'Permissão de localização negada. Você pode permitir nas configurações do navegador.';
                            }
                            
                            alert(errorMsg);
                        }
                    });
                } catch (error) {
                    welcomeLocationBtn.disabled = false;
                    welcomeLocationBtn.classList.remove('is-loading');
                    welcomeLocationBtn.innerHTML = '<span class="btn-icon">📍</span><span>Usar minha localização</span>';
                }
            });
        }
    });

})();

