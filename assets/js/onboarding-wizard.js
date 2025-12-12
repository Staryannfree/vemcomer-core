/**
 * Onboarding Wizard JavaScript
 * Gerencia o fluxo do wizard de onboarding de lojistas
 * 
 * @package VemComerCore
 */

(function() {
    'use strict';

    // ============================================================================
    // SISTEMA DE DEBUG E LOGGING
    // ============================================================================
    const DEBUG_MODE = true; // Habilitado temporariamente para diagnosticar categorias
    const DEBUG_PREFIX = '[VC-Onboarding]';
    
    function debugLog(message, data = null) {
        if (!DEBUG_MODE) return;
        const timestamp = new Date().toISOString();
        const logMessage = `${DEBUG_PREFIX} [${timestamp}] ${message}`;
        console.log(logMessage, data || '');
    }
    
    function debugError(message, error) {
        console.error(`${DEBUG_PREFIX} [ERROR] ${message}`, error);
    }
    
    // Capturar erros não tratados
    window.addEventListener('error', function(event) {
        if (event.message && event.message.includes('onboarding')) {
            debugError('Uncaught Error', event.error || event.message);
        }
    });
    
    // Variáveis globais (serão preenchidas via wp_localize_script)
    const REST_BASE = window.vcOnboardingWizard?.restBase || '';
    const REST_NONCE = window.vcOnboardingWizard?.restNonce || '';
    const RESTAURANT_ID = window.vcOnboardingWizard?.restaurantId || 0;
    const CURRENT_STEP = window.vcOnboardingWizard?.currentStep || 1;
    const CUISINE_OPTIONS_PRIMARY = window.vcOnboardingWizard?.cuisineOptionsPrimary || [];
    const INITIAL_WIZARD_DATA = window.vcOnboardingWizard?.initialWizardData || {};
    
    // FORÇAR INÍCIO DO PASSO 1: Ignora o passo salvo no banco para sempre começar do início
    let wizardStep = 1; 
    // let wizardStep = CURRENT_STEP; // Comportamento anterior: continuar de onde parou
    const TOTAL_STEPS = 7;
    
    // Dados temporários do wizard (inicializar com dados salvos se disponíveis)
    let wizardData = {
        cuisine_ids: INITIAL_WIZARD_DATA.cuisine_ids || [],
        name: INITIAL_WIZARD_DATA.name || '',
        whatsapp: INITIAL_WIZARD_DATA.whatsapp || '',
        logo: INITIAL_WIZARD_DATA.logo || '',
        address: INITIAL_WIZARD_DATA.address || '',
        neighborhood: INITIAL_WIZARD_DATA.neighborhood || '',
        city: INITIAL_WIZARD_DATA.city || '',
        zipcode: INITIAL_WIZARD_DATA.zipcode || '',
        delivery: INITIAL_WIZARD_DATA.delivery !== undefined ? INITIAL_WIZARD_DATA.delivery : true,
        pickup: INITIAL_WIZARD_DATA.pickup === true,
        schedule: INITIAL_WIZARD_DATA.schedule || {},
        category_names: INITIAL_WIZARD_DATA.category_names || [],
        products: INITIAL_WIZARD_DATA.products || [],
        addon_groups: INITIAL_WIZARD_DATA.addon_groups || [],
    };

    // Inicializar wizard
    function initWizard() {
        renderStep();
    }

    // Renderizar passo atual
    function renderStep() {
        const stepTitle = getStepTitle(wizardStep);
        const stepTitleEl = document.getElementById('vcWizardStepTitle');
        const progressBarEl = document.getElementById('vcWizardProgressBar');
        
        if (stepTitleEl) {
            stepTitleEl.textContent = `Passo ${wizardStep} de ${TOTAL_STEPS} - ${stepTitle}`;
        }
        if (progressBarEl) {
            progressBarEl.style.width = `${(wizardStep / TOTAL_STEPS) * 100}%`;
        }
        
        // Carregar conteúdo do passo via AJAX
        loadStepContent(wizardStep);
        
        // Atualizar botões
        const btnPrev = document.getElementById('vcWizardBtnPrev');
        const btnNext = document.getElementById('vcWizardBtnNext');
        
        if (btnPrev) {
            btnPrev.style.display = wizardStep > 1 ? 'block' : 'none';
        }
        if (btnNext) {
            btnNext.textContent = wizardStep === TOTAL_STEPS ? 'Ativar minha loja' : 'Continuar';
        }
    }

    // Carregar conteúdo do passo via AJAX
    function loadStepContent(step) {
        const contentEl = document.getElementById('vcWizardContent');
        if (!contentEl) return;

        // Preparar parâmetros da requisição
        const params = new URLSearchParams({
            step: step,
            restaurant_id: RESTAURANT_ID,
        });

        // Para o passo 4, passar os cuisine_ids selecionados para recomendações
        if (step === 4 && wizardData.cuisine_ids && wizardData.cuisine_ids.length > 0) {
            params.append('cuisine_ids', wizardData.cuisine_ids.join(','));
        }

        // Fazer requisição AJAX
        const requestUrl = `${REST_BASE}/onboarding/step-content?${params.toString()}`;
        
        fetch(requestUrl, {
            headers: { 'X-WP-Nonce': REST_NONCE },
        })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.html) {
                // IMPORTANTE: Atualizar wizardData ANTES de inserir HTML
                // Isso garante que os dados salvos estejam disponíveis quando o HTML for renderizado
                if (data.saved_data) {
                    updateWizardDataFromSaved(data.saved_data);
                }
                
                contentEl.innerHTML = data.html;
                
                // Lógica específica por passo após carregar HTML
                if (step === 5) {
                    // ESTRATÉGIA ZERO LATÊNCIA (RESTAURADA - Funcionava antes):
                    // Tentar múltiplas vezes para garantir que o select existe e está preenchido
                    const tryPopulateCategories = (attempt = 1) => {
                        const select = document.getElementById('wizardProductCategory');
                        debugLog(`Tentativa ${attempt} de preencher categorias`, {
                            selectExists: !!select,
                            category_names: wizardData.category_names,
                            full_categories: wizardData.full_categories,
                            wizardData: wizardData
                        });
                        
                        if (!select) {
                            if (attempt < 5) {
                                setTimeout(() => tryPopulateCategories(attempt + 1), 100);
                            } else {
                                debugError('Select não encontrado após 5 tentativas');
                            }
                            return;
                        }
                        
                        // Verificar se já está preenchido
                        if (select.options.length > 1) {
                            debugLog('Select já preenchido, pulando');
                            return;
                        }
                        
                        // Tentar usar category_names primeiro (Zero Latency)
                        if (wizardData.category_names && wizardData.category_names.length > 0) {
                            debugLog('Zero Latency: Preenchendo com category_names', wizardData.category_names);
                            populateProductCategoriesFromNames(wizardData.category_names);
                            return;
                        }
                        
                        // Fallback 1: full_categories do backend
                        if (wizardData.full_categories && wizardData.full_categories.length > 0) {
                            debugLog('Fallback 1: Preenchendo com full_categories', wizardData.full_categories);
                            populateProductCategories(wizardData.full_categories);
                            return;
                        }
                        
                        // Fallback 2: API
                        if (attempt === 1) {
                            debugLog('Fallback 2: Carregando categorias do servidor via API');
                            setTimeout(() => loadProductCategories(), 1000);
                        }
                    };
                    
                    // Iniciar tentativas após um pequeno delay para garantir que o DOM está pronto
                    setTimeout(() => tryPopulateCategories(1), 50);
                    
                    // Renderizar produtos salvos (MANTIDO - Correção dos produtos)
                    setTimeout(() => {
                        if (wizardData.products && wizardData.products.length > 0) {
                            renderProductsList();
                        }
                    }, 200);
                } else if (step === 3) {
                    // Inicializar mapa no passo 3
                    initWizardMap();
                    // Inicializar horários do DOM no wizardData após um pequeno delay
                    setTimeout(() => {
                        collectScheduleFromDOM();
                    }, 200);
                } else if (step === 6) {
                    loadRecommendedAddons();
                }
                
                // Restaurar seleções visuais
                updateStepSelections(step);
            }
        })
        .catch(err => {
            debugError('Erro ao carregar conteúdo', err);
            contentEl.innerHTML = '<div style="text-align:center;padding:40px;color:#999;">Erro ao carregar conteúdo. Recarregue a página.</div>';
        });
    }

    // Atualizar wizardData com dados salvos do backend
    function updateWizardDataFromSaved(savedData) {
        if (!savedData) return;
        
        if (savedData.full_categories) {
            wizardData.full_categories = savedData.full_categories;
        }

        // CRÍTICO: Preservar category_names (Zero Latency)
        // Se o servidor enviou category_names, usar. Se não, manter os locais se existirem.
        if (savedData.category_names && Array.isArray(savedData.category_names) && savedData.category_names.length > 0) {
            wizardData.category_names = savedData.category_names;
            debugLog('category_names atualizados do servidor', savedData.category_names);
        } else if (!wizardData.category_names || wizardData.category_names.length === 0) {
            // Se não temos nem do servidor nem localmente, tentar extrair de full_categories
            if (savedData.full_categories && Array.isArray(savedData.full_categories)) {
                wizardData.category_names = savedData.full_categories.map(cat => cat.name || cat);
                debugLog('category_names extraídos de full_categories', wizardData.category_names);
            }
        } else {
            debugLog('Mantendo category_names locais', wizardData.category_names);
        }
        
        // Atualizar outros campos conforme necessário
        if (savedData.cuisine_ids) wizardData.cuisine_ids = savedData.cuisine_ids;
        if (savedData.name) wizardData.name = savedData.name;
        if (savedData.whatsapp) wizardData.whatsapp = savedData.whatsapp;
        if (savedData.address) wizardData.address = savedData.address;
        if (savedData.neighborhood) wizardData.neighborhood = savedData.neighborhood;
        if (savedData.city) wizardData.city = savedData.city;
        if (savedData.zipcode) wizardData.zipcode = savedData.zipcode;
        if (savedData.lat) wizardData.lat = savedData.lat;
        if (savedData.lng) wizardData.lng = savedData.lng;
        if (savedData.delivery !== undefined) wizardData.delivery = savedData.delivery;
        if (savedData.pickup !== undefined) wizardData.pickup = savedData.pickup;
        if (savedData.schedule && typeof savedData.schedule === 'object') {
            wizardData.schedule = savedData.schedule;
        }
        
        // PRODUTOS: Sempre carregar produtos salvos do servidor quando voltar para um passo
        // Isso garante que produtos já cadastrados apareçam ao voltar
        if (savedData.products && Array.isArray(savedData.products)) {
            // Se estamos voltando para o passo 5, sempre usar dados do servidor
            // Se estamos no passo 5 e não temos produtos locais, usar do servidor
            // Se temos produtos locais não salvos, mesclar (priorizar servidor para evitar duplicatas)
            if (wizardStep === 5) {
                // Quando volta para o passo 5, sempre carregar do servidor
                // Produtos locais não salvos serão perdidos, mas isso é esperado ao voltar
                wizardData.products = savedData.products;
                debugLog('Produtos carregados do servidor', savedData.products);
            } else {
                // Em outros passos, sempre atualizar
                wizardData.products = savedData.products;
            }
        }
        
        if (savedData.addon_groups) wizardData.addon_groups = savedData.addon_groups;
    }

    // Atualizar seleções visuais baseadas em wizardData
    function updateStepSelections(step) {
        switch(step) {
            case 1:
                if (wizardData.cuisine_ids) {
                    document.querySelectorAll('.cuisine-option').forEach(el => el.classList.remove('selected'));
                    wizardData.cuisine_ids.forEach(id => {
                        const el = document.querySelector(`.cuisine-option[data-id="${id}"]`);
                        if (el) el.classList.add('selected');
                    });
                }
                updatePrimaryCuisineWarning();
                break;
            case 2:
                const nameInput = document.getElementById('wizardName');
                const whatsappInput = document.getElementById('wizardWhatsapp');
                if (nameInput && wizardData.name) nameInput.value = wizardData.name;
                if (whatsappInput && wizardData.whatsapp) whatsappInput.value = wizardData.whatsapp;
                if (wizardData.logo) {
                    const preview = document.getElementById('wizardLogoPreview');
                    if (preview && !preview.querySelector('img')) {
                        preview.innerHTML = `<img src="${wizardData.logo}" style="max-width:200px;max-height:200px;border-radius:8px;margin-top:8px;">`;
                    }
                }
                break;
            case 3:
                const addressInput = document.getElementById('wizardAddress');
                const neighborhoodInput = document.getElementById('wizardNeighborhood');
                const cityInput = document.getElementById('wizardCity');
                const zipcodeInput = document.getElementById('wizardZipcode');
                
                if (addressInput && wizardData.address) addressInput.value = wizardData.address;
                if (neighborhoodInput && wizardData.neighborhood) neighborhoodInput.value = wizardData.neighborhood;
                if (cityInput && wizardData.city) cityInput.value = wizardData.city;
                if (zipcodeInput && wizardData.zipcode) zipcodeInput.value = wizardData.zipcode;
                
                // Restaurar checkboxes de delivery/pickup
                const deliveryCheck = document.getElementById('wizardDelivery');
                const pickupCheck = document.getElementById('wizardPickup');
                if (deliveryCheck) deliveryCheck.checked = wizardData.delivery === true;
                if (pickupCheck) pickupCheck.checked = wizardData.pickup === true;
                
                // Restaurar horários
                if (wizardData.schedule && typeof wizardData.schedule === 'object') {
                    const days = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
                    days.forEach(function(day) {
                        const dayData = wizardData.schedule[day];
                        if (dayData) {
                            const checkbox = document.getElementById('schedule_' + day);
                            const openInput = document.getElementById('schedule_' + day + '_open');
                            const closeInput = document.getElementById('schedule_' + day + '_close');
                            
                            if (checkbox) {
                                checkbox.checked = dayData.enabled === true;
                            }
                            
                            if (openInput && closeInput) {
                                openInput.disabled = !dayData.enabled;
                                closeInput.disabled = !dayData.enabled;
                                
                                if (dayData.ranges && dayData.ranges.length > 0) {
                                    openInput.value = dayData.ranges[0].open || '';
                                    closeInput.value = dayData.ranges[0].close || '';
                                }
                            }
                        }
                    });
                }
                break;
            case 4:
                if (wizardData.category_names) {
                    wizardData.category_names.forEach(name => {
                        const checkbox = document.querySelector(`#wizardRecommendedCategories input[value="${escapeHtml(name)}"]`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                break;
            case 5:
                // Renderizar produtos salvos quando volta para o passo 5
                if (wizardData.products && wizardData.products.length > 0) {
                    renderProductsList();
                }
                break;
            case 6:
                if (wizardData.addon_groups) {
                    wizardData.addon_groups.forEach(groupId => {
                        const checkbox = document.getElementById(`onboarding-group-${groupId}`);
                        if (checkbox) checkbox.checked = true;
                    });
                }
                break;
        }
    }

    // Atualizar aviso de primárias no passo 1
    function updatePrimaryCuisineWarning() {
        const warningEl = document.getElementById('wizardStep1Warning');
        if (!warningEl) return;

        const selectedPrimary = wizardData.cuisine_ids.filter(id => {
            return CUISINE_OPTIONS_PRIMARY.some(opt => opt.id === id);
        });

        warningEl.style.display = (selectedPrimary.length === 0 && wizardData.cuisine_ids.length > 0) ? 'block' : 'none';
    }

    function getStepTitle(step) {
        const titles = {
            1: 'Tipo de Restaurante',
            2: 'Dados Básicos',
            3: 'Endereço e Horários',
            4: 'Categorias do Cardápio',
            5: 'Primeiros Produtos',
            6: 'Adicionais',
            7: 'Revisão',
        };
        return titles[step] || '';
    }

    // ============================================================================
    // FUNÇÕES DE NAVEGAÇÃO E AÇÕES (Expostas globalmente)
    // ============================================================================

    window.vcWizardNext = function() {
        if (!validateStep(wizardStep)) return;

        // Mostrar loading
        const btnNext = document.getElementById('vcWizardBtnNext');
        const originalText = btnNext ? btnNext.textContent : '';
        if (btnNext) {
            btnNext.disabled = true;
            btnNext.textContent = 'Salvando...';
        }

        saveStep(wizardStep).then((response) => {
            // Restaurar botão
            if (btnNext) {
                btnNext.disabled = false;
                btnNext.textContent = originalText;
            }

            // Se for o passo 4 (categorias), atualizar wizardData com as categorias criadas
            if (wizardStep === 4) {
                // CRÍTICO: Preservar category_names locais ANTES de qualquer atualização
                const localCategoryNames = wizardData.category_names && wizardData.category_names.length > 0 
                    ? [...wizardData.category_names] 
                    : [];
                
                debugLog('Estado ANTES de atualizar (Passo 4):', {
                    localCategoryNames: localCategoryNames,
                    responseCreatedCategories: response.created_categories
                });
                
                // Atualizar apenas se o backend retornar dados válidos e não vazios
                if (response.created_categories && Array.isArray(response.created_categories) && response.created_categories.length > 0) {
                    wizardData.category_names = response.created_categories.map(cat => cat.name || cat);
                    debugLog('Categorias atualizadas após salvar passo 4 (do response)', wizardData.category_names);
                } else if (localCategoryNames.length > 0) {
                    // CRÍTICO: Se o backend retornou vazio mas temos dados locais, MANTER os locais
                    wizardData.category_names = localCategoryNames;
                    debugLog('Backend retornou vazio, mantendo category_names da memória local', wizardData.category_names);
                } else {
                    debugError('ATENÇÃO: Passo 4 salvo mas category_names está vazio!', {
                        response: response,
                        localCategoryNames: localCategoryNames,
                        wizardData: wizardData
                    });
                }
            }
            
            if (wizardStep < TOTAL_STEPS) {
                wizardStep++;
                debugLog(`Avançando para passo ${wizardStep}`, {
                    category_names: wizardData.category_names,
                    full_categories: wizardData.full_categories
                });
                renderStep();
            } else {
                completeOnboarding();
            }
        }).catch(err => {
            // Restaurar botão
            if (btnNext) {
                btnNext.disabled = false;
                btnNext.textContent = originalText;
            }

            // Mostrar erro de forma mais clara
            const errorMsg = err.message || 'Erro desconhecido ao salvar';
            showError('❌ ' + errorMsg);
            
            // Scroll para o topo para ver o erro
            const content = document.getElementById('vcWizardContent');
            if (content) {
                content.scrollTop = 0;
            }
            
            debugError('Erro ao avançar passo', { 
                step: wizardStep, 
                error: err,
                message: err.message,
                stack: err.stack
            });
        });
    };

    window.vcWizardPrev = function() {
        if (wizardStep > 1) {
            saveStep(wizardStep).then(() => {
                wizardStep--;
                renderStep();
            }).catch(err => {
                wizardStep--; // Volta mesmo com erro
                renderStep();
            });
        }
    };

    window.vcCloseOnboardingWizard = function() {
        if (confirm('Tem certeza que deseja sair? Seu progresso será salvo.')) {
            const wizardEl = document.getElementById('vcOnboardingWizard');
            if (wizardEl) wizardEl.style.display = 'none';
            location.reload();
        }
    };

    // ============================================================================
    // VALIDAÇÃO E SALVAMENTO
    // ============================================================================

    function validateStep(step) {
        const errorDiv = document.getElementById('vcWizardError');
        if (errorDiv) errorDiv.remove();

        switch(step) {
            case 1:
                if (!wizardData.cuisine_ids || wizardData.cuisine_ids.length === 0) {
                    showError('Selecione pelo menos um tipo de restaurante.');
                    return false;
                }
                break;
            case 2:
                const name = document.getElementById('wizardName')?.value.trim();
                const whatsapp = document.getElementById('wizardWhatsapp')?.value.trim();
                if (!name || !whatsapp) {
                    showError('Nome e WhatsApp são obrigatórios.');
                    return false;
                }
                wizardData.name = name;
                wizardData.whatsapp = whatsapp;
                break;
            case 3:
                const address = document.getElementById('wizardAddress')?.value.trim();
                if (!address) {
                    showError('Endereço é obrigatório.');
                    return false;
                }
                wizardData.address = address;
                wizardData.neighborhood = document.getElementById('wizardNeighborhood')?.value.trim() || '';
                wizardData.city = document.getElementById('wizardCity')?.value.trim() || '';
                wizardData.zipcode = document.getElementById('wizardZipcode')?.value.trim() || '';
                wizardData.lat = document.getElementById('wizardLat')?.value || '';
                wizardData.lng = document.getElementById('wizardLng')?.value || '';
                wizardData.delivery = document.getElementById('wizardDelivery')?.checked || false;
                wizardData.pickup = document.getElementById('wizardPickup')?.checked || false;
                // Coletar horários do DOM antes de validar
                collectScheduleFromDOM();
                
                // Validar que pelo menos um dia está habilitado com horários
                const hasValidSchedule = Object.keys(wizardData.schedule || {}).some(day => {
                    const dayData = wizardData.schedule[day];
                    return dayData && dayData.enabled && 
                           dayData.ranges && dayData.ranges.length > 0 &&
                           dayData.ranges[0].open && dayData.ranges[0].close;
                });
                
                if (!hasValidSchedule) {
                    showError('Configure pelo menos um dia de funcionamento com horários.');
                    return false;
                }
                
                // Validar campos obrigatórios e mostrar qual está faltando
                const missingFields = [];
                if (!wizardData.address || wizardData.address.trim() === '') {
                    missingFields.push('Endereço');
                }
                
                if (missingFields.length > 0) {
                    showError('Preencha os seguintes campos obrigatórios: ' + missingFields.join(', ') + '.');
                    return false;
                }
                break;
            case 4:
                if (!wizardData.category_names || wizardData.category_names.length === 0) {
                    showError('Selecione pelo menos uma categoria.');
                    return false;
                }
                break;
            case 5:
                if (!wizardData.products || wizardData.products.length === 0) {
                    if (confirm('Deseja continuar sem cadastrar produtos?')) return true;
                    showError('Cadastre pelo menos um produto.');
                    return false;
                }
                break;
        }
        return true;
    }

    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.id = 'vcWizardError';
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        const content = document.getElementById('vcWizardContent');
        if (content) content.insertBefore(errorDiv, content.firstChild);
    }

    async function saveStep(step) {
        // Validar configuração básica
        if (!REST_BASE || !REST_NONCE) {
            const errorMsg = 'Configuração do servidor não encontrada. Recarregue a página.';
            debugError('Configuração faltando', { REST_BASE, REST_NONCE });
            throw new Error(errorMsg);
        }

        const stepSlugs = { 1: 'welcome', 2: 'basic_data', 3: 'address_schedule', 4: 'categories', 5: 'products', 6: 'addons' };
        
        const payload = {
            step: step,
            step_slug: stepSlugs[step] || '',
        };

        // Preencher payload conforme o passo
        try {
            switch(step) {
                case 1: 
                    payload.cuisine_ids = wizardData.cuisine_ids || []; 
                    break;
                case 2: 
                    payload.name = wizardData.name || ''; 
                    payload.whatsapp = wizardData.whatsapp || ''; 
                    if (wizardData.logo) payload.logo = wizardData.logo;
                    break;
                case 3:
                    // Garantir que os horários sejam coletados antes de salvar
                    collectScheduleFromDOM();
                    
                    payload.address = wizardData.address || '';
                    payload.neighborhood = wizardData.neighborhood || '';
                    payload.city = wizardData.city || '';
                    payload.zipcode = wizardData.zipcode || '';
                    payload.lat = wizardData.lat || '';
                    payload.lng = wizardData.lng || '';
                    payload.delivery = wizardData.delivery !== undefined ? wizardData.delivery : true;
                    payload.pickup = wizardData.pickup === true;
                    
                    // Validar e limpar schedule antes de enviar
                    const schedule = wizardData.schedule || {};
                    const cleanedSchedule = {};
                    const days = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
                    
                    days.forEach(day => {
                        if (schedule[day] && typeof schedule[day] === 'object') {
                            const dayData = schedule[day];
                            cleanedSchedule[day] = {
                                enabled: dayData.enabled === true,
                                ranges: []
                            };
                            
                            if (dayData.ranges && Array.isArray(dayData.ranges) && dayData.ranges.length > 0) {
                                dayData.ranges.forEach(range => {
                                    if (range && typeof range === 'object' && range.open && range.close) {
                                        cleanedSchedule[day].ranges.push({
                                            open: String(range.open).trim(),
                                            close: String(range.close).trim()
                                        });
                                    }
                                });
                            }
                        } else {
                            cleanedSchedule[day] = {
                                enabled: false,
                                ranges: []
                            };
                        }
                    });
                    
                    payload.schedule = cleanedSchedule;
                    
                    debugLog('Salvando Passo 3 - Payload:', {
                        address: payload.address,
                        schedule_days: Object.keys(cleanedSchedule),
                        schedule_sample: cleanedSchedule.seg
                    });
                    break;
                case 4: 
                    payload.category_names = wizardData.category_names || []; 
                    break;
                case 5: 
                    payload.products = wizardData.products || [];
                    debugLog('Salvando Passo 5 - Produtos enviados:', {
                        count: wizardData.products?.length || 0,
                        products: wizardData.products
                    });
                    break;
                case 6: 
                    payload.addon_groups = wizardData.addon_groups || []; 
                    break;
            }
        } catch (err) {
            debugError('Erro ao preparar payload', { step, error: err, wizardData });
            throw new Error('Erro ao preparar dados para salvar: ' + err.message);
        }

        const url = `${REST_BASE}/onboarding/step`;
        debugLog(`Tentando salvar passo ${step}`, { url, payload: step === 3 ? { ...payload, schedule: '...' } : payload });

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-WP-Nonce': REST_NONCE 
                },
                body: JSON.stringify(payload),
            });

            // Verificar se a resposta foi recebida
            if (!response) {
                throw new Error('Nenhuma resposta do servidor. Verifique sua conexão.');
            }

            // Tentar ler a resposta como JSON
            let data;
            try {
                const text = await response.text();
                debugLog(`Resposta do servidor (passo ${step}):`, { status: response.status, text: text.substring(0, 200) });
                
                if (!text) {
                    throw new Error('Resposta vazia do servidor.');
                }
                
                data = JSON.parse(text);
            } catch (parseError) {
                debugError('Erro ao parsear resposta', { step, status: response.status, parseError });
                throw new Error('Resposta inválida do servidor. Status: ' + response.status);
            }

            if (!response.ok) {
                const errorDetails = { 
                    step, 
                    status: response.status, 
                    data,
                    url,
                    payload: step === 3 ? { ...payload, schedule: Object.keys(payload.schedule || {}) } : payload
                };
                debugError('Erro ao salvar passo', errorDetails);
                console.error('Detalhes completos do erro:', errorDetails);
                
                // Mensagem mais detalhada
                let errorMessage = 'Erro ao salvar passo ' + step + '. ';
                if (data && data.message) {
                    errorMessage += data.message;
                } else if (response.status === 400) {
                    errorMessage += 'Dados inválidos. Verifique os campos preenchidos.';
                } else if (response.status === 403) {
                    errorMessage += 'Permissão negada. Faça login novamente.';
                } else if (response.status === 404) {
                    errorMessage += 'Endpoint não encontrado.';
                } else if (response.status === 500) {
                    errorMessage += 'Erro interno do servidor. Tente novamente.';
                } else {
                    errorMessage += 'Status: ' + response.status;
                }
                
                throw new Error(errorMessage);
            }

            debugLog(`Passo ${step} salvo com sucesso`, data);
            return data;
            
        } catch (fetchError) {
            // Erro de rede ou fetch
            debugError('Erro na requisição fetch', { 
                step, 
                error: fetchError, 
                url,
                message: fetchError.message,
                stack: fetchError.stack
            });
            
            let errorMessage = 'Erro ao conectar com o servidor. ';
            
            if (fetchError.message && fetchError.message.includes('fetch')) {
                errorMessage += 'Verifique sua conexão com a internet.';
            } else if (fetchError.message && fetchError.message.includes('Failed to fetch')) {
                errorMessage += 'Não foi possível conectar ao servidor. Verifique se o servidor está online.';
            } else {
                errorMessage += fetchError.message || 'Erro desconhecido.';
            }
            
            console.error('Erro completo:', fetchError);
            throw new Error(errorMessage);
        }
    }

    async function completeOnboarding() {
        try {
            await fetch(`${REST_BASE}/onboarding/complete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': REST_NONCE },
            });
            alert('Loja ativada com sucesso!');
            location.reload();
        } catch (err) {
            alert('Erro ao ativar loja: ' + err.message);
        }
    }

    // ============================================================================
    // FUNÇÕES ESPECÍFICAS DOS PASSOS (Expostas globalmente)
    // ============================================================================

    window.vcToggleCuisine = function(id) {
        if (!wizardData.cuisine_ids) wizardData.cuisine_ids = [];
        const idx = wizardData.cuisine_ids.indexOf(id);
        const el = document.querySelector(`.cuisine-option[data-id="${id}"]`);
        
        if (idx > -1) {
            wizardData.cuisine_ids.splice(idx, 1);
            if (el) el.classList.remove('selected');
        } else {
            if (wizardData.cuisine_ids.length < 3) {
                wizardData.cuisine_ids.push(id);
                if (el) el.classList.add('selected');
            } else {
                alert('Máximo 3 tipos.');
            }
        }
        updatePrimaryCuisineWarning();
    };

    window.vcHandleLogoUpload = function(event) {
        const file = event.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            wizardData.logo = e.target.result;
            const preview = document.getElementById('wizardLogoPreview');
            if (preview) preview.innerHTML = `<img src="${e.target.result}" style="max-width:200px;max-height:200px;border-radius:8px;margin-top:8px;">`;
        };
        reader.readAsDataURL(file);
    };

    window.vcToggleCategory = function(name) {
        if (!wizardData.category_names) wizardData.category_names = [];
        const idx = wizardData.category_names.indexOf(name);
        if (idx > -1) {
            wizardData.category_names.splice(idx, 1);
        } else {
            wizardData.category_names.push(name);
        }
        debugLog('Categoria toggleada no Passo 4', {
            name: name,
            category_names: wizardData.category_names
        });
    };

    window.vcSkipCategories = function() {
        if (confirm('Pular criação de categorias?')) {
            wizardData.category_names = [];
            window.vcWizardNext();
        }
    };

    // Passo 5: Adicionar Produto
    window.vcAddProduct = function() {
        const name = document.getElementById('wizardProductName')?.value.trim();
        let categoryId = document.getElementById('wizardProductCategory')?.value;
        const categorySelect = document.getElementById('wizardProductCategory');
        const categoryName = categorySelect?.options[categorySelect.selectedIndex]?.text || '';
        const price = document.getElementById('wizardProductPrice')?.value;
        const description = document.getElementById('wizardProductDescription')?.value.trim();

        if (!name || !categoryId || !price) {
            alert('Preencha nome, categoria e preço.');
            return;
        }

        // Lógica Zero Latency: Se categoryId for "name:...", enviar 0 e deixar backend resolver pelo nome
        if (categoryId && categoryId.toString().startsWith('name:')) {
            categoryId = 0;
        }

        const product = {
            name: name,
            category: categoryName,
            category_id: categoryId,
            price: parseFloat(price),
            description: description,
        };

        if (!wizardData.products) wizardData.products = [];
        wizardData.products.push(product);
        
        debugLog('Produto adicionado no Passo 5', {
            product: product,
            totalProducts: wizardData.products.length,
            allProducts: wizardData.products
        });
        
        // Limpar campos e atualizar lista
        document.getElementById('wizardProductName').value = '';
        document.getElementById('wizardProductPrice').value = '';
        document.getElementById('wizardProductDescription').value = '';
        
        // Resetar input de arquivo se existir (novo para evitar confusão)
        const fileInput = document.getElementById('wizardProductImage');
        if (fileInput) fileInput.value = '';

        renderProductsList(); // Atualizar apenas a lista localmente
    };

    window.vcRemoveProduct = function(index) {
        if (confirm('Remover este produto?')) {
            wizardData.products.splice(index, 1);
            renderProductsList(); // Atualizar apenas a lista localmente
        }
    };

    // Renderizar lista de produtos localmente (sem recarregar HTML do servidor)
    function renderProductsList() {
        const container = document.getElementById('wizardProductsList');
        if (!container) return;

        if (!wizardData.products || wizardData.products.length === 0) {
            container.innerHTML = '<div style="text-align:center;padding:20px;color:#999;">Nenhum produto cadastrado ainda.</div>';
            return;
        }

        const html = wizardData.products.map((product, idx) => `
            <div class="product-item" style="background:#fff;padding:12px;border:1px solid #ddd;border-radius:8px;margin-bottom:8px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        ${product.image ? `<img src="${product.image}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">` : ''}
                        <strong style="font-size:16px;">${escapeHtml(product.name)}</strong>
                    </div>
                    <button onclick="vcRemoveProduct(${idx})" style="background:#ffe7e7;color:#ea5252;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;font-weight:600;">Remover</button>
                </div>
                <div style="color:#6b7672;font-size:14px;">
                    Categoria: ${escapeHtml(product.category || 'Sem categoria')} | 
                    Preço: R$ ${parseFloat(product.price).toFixed(2).replace('.', ',')}
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
        
        // Atualizar contador (se existir elemento de aviso)
        const warningDiv = container.nextElementSibling?.nextElementSibling; // Tentativa de achar o div de aviso
        if (warningDiv && warningDiv.innerText.includes('Você já cadastrou')) {
             warningDiv.innerHTML = `Você já cadastrou <strong>${wizardData.products.length}</strong> produto(s). Recomendamos pelo menos 3.`;
        }
    }

    // Passo 5: Popular categorias a partir de lista de objetos {id, name}
    function populateProductCategories(categories) {
        const select = document.getElementById('wizardProductCategory');
        if (!select || !Array.isArray(categories)) return;

        select.innerHTML = '<option value="">Selecione a categoria</option>';
        
        categories.forEach(cat => {
            const option = document.createElement('option');
            option.value = cat.id; 
            option.textContent = escapeHtml(cat.name);
            select.appendChild(option);
        });
    }

    // Passo 5: Popular categorias (Zero Latency) - LEGADO (mantido por compatibilidade)
    function populateProductCategoriesFromNames(categoryNames) {
        const select = document.getElementById('wizardProductCategory');
        if (!select) return;

        select.innerHTML = '<option value="">Selecione a categoria</option>';
        
        categoryNames.forEach(name => {
            const option = document.createElement('option');
            option.value = 'name:' + name; 
            option.textContent = escapeHtml(name);
            select.appendChild(option);
        });
    }

    // Fallback: carregar do servidor
    async function loadProductCategories() {
        try {
            const response = await fetch(`${REST_BASE}/menu-categories?t=${Date.now()}`, {
                headers: { 'X-WP-Nonce': REST_NONCE },
                cache: 'no-cache'
            });
            if (response.ok) {
                const categories = await response.json();
                const select = document.getElementById('wizardProductCategory');
                if (select && Array.isArray(categories)) {
                    select.innerHTML = '<option value="">Selecione a categoria</option>';
                    categories.forEach(cat => {
                        const option = document.createElement('option');
                        option.value = cat.id;
                        option.textContent = escapeHtml(cat.name);
                        select.appendChild(option);
                    });
                }
            }
        } catch (e) { console.error(e); }
    }

    window.vcToggleAddonGroup = function(groupId) {
        if (!wizardData.addon_groups) wizardData.addon_groups = [];
        const idx = wizardData.addon_groups.indexOf(groupId);
        if (idx > -1) {
            wizardData.addon_groups.splice(idx, 1);
        } else {
            wizardData.addon_groups.push(groupId);
        }
    };

    window.vcSkipAddons = function() {
        wizardData.addon_groups = [];
        window.vcWizardNext();
    };

    async function loadRecommendedAddons() {
        const container = document.getElementById('wizardRecommendedAddons');
        if (!container) return;
        
        try {
            const response = await fetch(`${REST_BASE}/addon-catalog/recommended-groups`, {
                headers: { 'X-WP-Nonce': REST_NONCE }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            debugLog('Recommended groups response', data);
            
            // O endpoint retorna { success: true, groups: [...] }
            const groups = data.groups || [];
            
            if (!Array.isArray(groups) || groups.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><p>Nenhum grupo recomendado encontrado para seu restaurante.</p></div>';
                return;
            }
            
            // Filtrar apenas grupos básicos
            const basicGroups = groups.filter(g => (g.difficulty_level || 'basic') === 'basic');
            
            if (basicGroups.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:40px;color:#999;"><p>Nenhum grupo básico encontrado.</p></div>';
                return;
            }
            
            // Renderizar grupos
            container.innerHTML = basicGroups.map(group => `
                <label class="category-checkbox" style="display:block;padding:12px;margin-bottom:8px;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;">
                    <input type="checkbox" value="${group.id}" 
                        ${wizardData.addon_groups?.includes(group.id) ? 'checked' : ''} 
                        onchange="vcToggleAddonGroup(${group.id})"
                        style="margin-right:8px;">
                    <span style="font-weight:600;">${escapeHtml(group.name)}</span>
                    ${group.description ? `<div style="font-size:0.9em;color:#6b7672;margin-top:4px;">${escapeHtml(group.description)}</div>` : ''}
                </label>
            `).join('');
            
        } catch (e) {
            debugError('Erro ao carregar grupos recomendados', e);
            container.innerHTML = '<div style="text-align:center;padding:40px;color:#d32f2f;"><p>Erro ao carregar grupos recomendados. Tente recarregar a página.</p></div>';
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ============================================================================
    // MAPA DO PASSO 3 (Endereço)
    // ============================================================================

    function initWizardMap() {
        // Verificar se Leaflet está disponível
        if (typeof L === 'undefined') {
            // Tentar novamente após um pequeno delay (caso esteja carregando)
            let attempts = 0;
            const checkLeaflet = setInterval(() => {
                attempts++;
                if (typeof L !== 'undefined') {
                    clearInterval(checkLeaflet);
                    setTimeout(initWizardMap, 100);
                } else if (attempts > 20) {
                    clearInterval(checkLeaflet);
                    console.error('Leaflet não pôde ser carregado. O mapa não funcionará.');
                    const mapEl = document.getElementById('vcWizardMap');
                    if (mapEl) {
                        mapEl.innerHTML = '<div style="padding:40px;text-align:center;color:#999;">Erro ao carregar o mapa. Recarregue a página.</div>';
                    }
                }
            }, 100);
            return;
        }

        const mapEl = document.getElementById('vcWizardMap');
        if (!mapEl) {
            console.error('Elemento do mapa não encontrado');
            return;
        }

        // Remover mensagem de carregamento
        const loadingEl = document.getElementById('vcWizardMapLoading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }

        // Garantir que o mapa tenha altura visível
        mapEl.style.height = '300px';
        mapEl.style.minHeight = '300px';
        if (window.innerWidth <= 768) {
            mapEl.style.height = '250px';
            mapEl.style.minHeight = '250px';
        }

        // Coordenadas padrão (Brasil central)
        const defaultLat = -14.235004;
        const defaultLng = -51.925282;
        
        // Inicializar mapa com delay para garantir renderização
        setTimeout(() => {
            try {
                const map = L.map(mapEl, {
                    tap: true, // Habilitar toque no mobile
                    touchZoom: true,
                    doubleClickZoom: true,
                    scrollWheelZoom: true,
                    boxZoom: false,
                    keyboard: false,
                    dragging: true
                }).setView([defaultLat, defaultLng], 4);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Forçar invalidateSize após um pequeno delay (importante no mobile)
                setTimeout(() => {
                    map.invalidateSize();
                }, 100);
                
                // Adicionar listener para resize (importante quando o teclado aparece no mobile)
                let resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        map.invalidateSize();
                    }, 250);
                });

                // Continuar com o resto da inicialização
                initMapFeatures(map);
            } catch (error) {
                console.error('Erro ao inicializar mapa:', error);
                showMapError('Erro ao carregar o mapa. Tente recarregar a página.');
            }
        }, 100);
    }

    function initMapFeatures(map) {

        let marker = null;
        const latInput = document.getElementById('wizardLat');
        const lngInput = document.getElementById('wizardLng');

        function setMarker(lat, lng) {
            lat = Number(lat);
            lng = Number(lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;
            
            const roundedLat = Number(lat.toFixed(6));
            const roundedLng = Number(lng.toFixed(6));
            
            if (latInput) latInput.value = String(roundedLat);
            if (lngInput) lngInput.value = String(roundedLng);
            
            if (!marker) {
                marker = L.marker([roundedLat, roundedLng]).addTo(map);
            } else {
                marker.setLatLng([roundedLat, roundedLng]);
            }
            
            // Garantir que o mapa seja atualizado
            map.invalidateSize();
        }

        // Clique/toque no mapa (mobile e desktop)
        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
            reverseGeocodeWizard(e.latlng.lat, e.latlng.lng);
            // Mostrar mensagem de sucesso
            hideMapError();
            showMapSuccess();
        });
        
        // Também escutar touchstart para melhor suporte mobile
        map.getContainer().addEventListener('touchstart', function(e) {
            // Leaflet já trata isso, mas garantimos que funciona
        }, { passive: true });

        // Função para formatar texto (remover hífens e capitalizar)
        function formatText(text) {
            if (!text) return '';
            // Remover hífens e substituir por espaços
            text = text.replace(/-/g, ' ');
            // Capitalizar cada palavra
            return text.split(' ').map(word => {
                if (word.length === 0) return '';
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            }).join(' ');
        }

        // Reverse geocoding para preencher campos
        function reverseGeocodeWizard(lat, lng) {
            const addressInput = document.getElementById('wizardAddress');
            const neighborhoodInput = document.getElementById('wizardNeighborhood');
            const cityInput = document.getElementById('wizardCity');
            const zipcodeInput = document.getElementById('wizardZipcode');

            if (!addressInput && !neighborhoodInput && !cityInput) return;

            const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + 
                       encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + 
                       '&addressdetails=1';

            fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'User-Agent': 'VemComer Marketplace'
                }
            })
            .then(function(resp) {
                return resp.ok ? resp.json() : null;
            })
            .then(function(data) {
                if (!data) return;

                const addr = data.address || {};

                // Endereço completo
                if (addressInput && data.display_name) {
                    addressInput.value = data.display_name;
                    // Atualizar wizardData
                    wizardData.address = data.display_name;
                }

                // Bairro (formatado corretamente)
                if (neighborhoodInput && addr) {
                    const bairro = addr.suburb || addr.neighbourhood || addr.neighborhood || 
                                  addr.quarter || addr.city_district || '';
                    if (bairro) {
                        const formattedBairro = formatText(bairro);
                        neighborhoodInput.value = formattedBairro;
                        wizardData.neighborhood = formattedBairro;
                    }
                }

                // Cidade
                if (cityInput && addr) {
                    const cidade = addr.city || addr.town || addr.municipality || 
                                 addr.county || '';
                    if (cidade) {
                        const formattedCidade = formatText(cidade);
                        cityInput.value = formattedCidade;
                        wizardData.city = formattedCidade;
                    }
                }

                // CEP
                if (zipcodeInput && addr.postcode) {
                    zipcodeInput.value = addr.postcode;
                    wizardData.zipcode = addr.postcode;
                }
            })
            .catch(function(err) {
                console.error('Erro no reverse geocoding:', err);
            });
        }

        // Botão "Usar minha localização"
        const geoBtn = document.getElementById('vcWizardUseLocation');
        if (geoBtn) {
            if (!navigator.geolocation) {
                geoBtn.disabled = true;
                geoBtn.textContent = 'Geolocalização não suportada';
                geoBtn.style.opacity = '0.5';
            } else {
                geoBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Feedback visual imediato
                    const originalText = geoBtn.textContent;
                    geoBtn.textContent = '📍 Detectando...';
                    geoBtn.disabled = true;
                    geoBtn.style.opacity = '0.7';
                    
                    // Limpar erros anteriores
                    hideMapError();
                    
                    // Opções de geolocalização otimizadas para mobile
                    const geoOptions = {
                        enableHighAccuracy: true,
                        timeout: 15000, // Aumentado para mobile
                        maximumAge: 0 // Sempre buscar nova localização
                    };
                    
                    navigator.geolocation.getCurrentPosition(
                        function(pos) {
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            
                            // Atualizar mapa
                            setMarker(lat, lng);
                            map.setView([lat, lng], 16);
                            
                            // Forçar atualização do tamanho do mapa
                            setTimeout(() => {
                                map.invalidateSize();
                            }, 200);
                            
                            // Fazer reverse geocoding
                            reverseGeocodeWizard(lat, lng);
                            
                            // Mostrar mensagem de sucesso
                            hideMapError();
                            showMapSuccess();
                            
                            // Restaurar botão
                            geoBtn.textContent = originalText;
                            geoBtn.disabled = false;
                            geoBtn.style.opacity = '1';
                        },
                        function(err) {
                            let errorMsg = 'Não foi possível obter sua localização automaticamente. ';
                            let suggestion = '';
                            
                            switch(err.code) {
                                case err.PERMISSION_DENIED:
                                    // Verificar se é site não seguro
                                    const isInsecure = window.location.protocol === 'http:' && window.location.hostname !== 'localhost' && !window.location.hostname.includes('127.0.0.1');
                                    if (isInsecure) {
                                        errorMsg = '⚠️ Site não seguro: A geolocalização só funciona em sites HTTPS (seguros). ';
                                        suggestion = 'Clique no mapa abaixo para selecionar sua localização manualmente.';
                                    } else {
                                        errorMsg += 'Permissão de localização negada. ';
                                        suggestion = 'Você pode permitir nas configurações do navegador ou clicar no mapa para selecionar manualmente.';
                                    }
                                    break;
                                case err.POSITION_UNAVAILABLE:
                                    errorMsg += 'Localização indisponível. ';
                                    suggestion = 'Clique no mapa para selecionar o endereço manualmente.';
                                    break;
                                case err.TIMEOUT:
                                    errorMsg += 'Tempo esgotado. ';
                                    suggestion = 'Clique no mapa para selecionar o endereço manualmente.';
                                    break;
                                default:
                                    errorMsg += 'Erro desconhecido. ';
                                    suggestion = 'Clique no mapa para selecionar o endereço manualmente.';
                                    break;
                            }
                            
                            const fullMessage = errorMsg + suggestion;
                            showMapError(fullMessage);
                            geoBtn.textContent = originalText;
                            geoBtn.disabled = false;
                            geoBtn.style.opacity = '1';
                        },
                        geoOptions
                    );
                }, { passive: false });
            }
        }

        // Se já houver coordenadas salvas, posicionar o mapa
        if (wizardData.lat && wizardData.lng) {
            const lat = parseFloat(wizardData.lat);
            const lng = parseFloat(wizardData.lng);
            if (!isNaN(lat) && !isNaN(lng)) {
                setMarker(lat, lng);
                map.setView([lat, lng], 16);
                setTimeout(() => {
                    map.invalidateSize();
                }, 200);
            }
        }
    }

    function showMapError(message) {
        const errorEl = document.getElementById('vcWizardMapError');
        const errorTextEl = document.getElementById('vcWizardMapErrorText');
        const successEl = document.getElementById('vcWizardMapSuccess');
        
        if (errorEl) {
            if (errorTextEl) {
                errorTextEl.textContent = message;
            } else {
                errorEl.textContent = message;
            }
            errorEl.style.display = 'block';
        }
        
        if (successEl) {
            successEl.style.display = 'none';
        }
    }

    function hideMapError() {
        const errorEl = document.getElementById('vcWizardMapError');
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    function showMapSuccess() {
        const errorEl = document.getElementById('vcWizardMapError');
        const successEl = document.getElementById('vcWizardMapSuccess');
        
        if (errorEl) {
            errorEl.style.display = 'none';
        }
        
        if (successEl) {
            successEl.style.display = 'block';
            // Esconder após 5 segundos
            setTimeout(() => {
                if (successEl) {
                    successEl.style.display = 'none';
                }
            }, 5000);
        }
    }

    // ============================================================================
    // FUNÇÕES DE HORÁRIOS DO PASSO 3
    // ============================================================================

    // Inicializar schedule se não existir
    if (!wizardData.schedule || typeof wizardData.schedule !== 'object') {
        wizardData.schedule = {};
    }

    window.vcToggleScheduleDay = function(dayKey) {
        const checkbox = document.getElementById('schedule_' + dayKey);
        const openInput = document.getElementById('schedule_' + dayKey + '_open');
        const closeInput = document.getElementById('schedule_' + dayKey + '_close');
        
        if (!checkbox || !openInput || !closeInput) return;
        
        const enabled = checkbox.checked;
        
        // Habilitar/desabilitar campos de horário
        openInput.disabled = !enabled;
        closeInput.disabled = !enabled;
        
        // Atualizar wizardData
        if (!wizardData.schedule[dayKey]) {
            wizardData.schedule[dayKey] = {
                enabled: enabled,
                ranges: [ { open: '09:00', close: '18:00' } ]
            };
        } else {
            wizardData.schedule[dayKey].enabled = enabled;
        }
        
        // Se desabilitado, limpar horários
        if (!enabled) {
            openInput.value = '';
            closeInput.value = '';
            wizardData.schedule[dayKey].ranges = [];
        } else {
            // Se habilitado e não tiver horário, usar padrão
            if (!openInput.value) {
                openInput.value = '09:00';
            }
            if (!closeInput.value) {
                closeInput.value = '18:00';
            }
            vcUpdateSchedule(dayKey);
        }
    };

    window.vcUpdateSchedule = function(dayKey) {
        const checkbox = document.getElementById('schedule_' + dayKey);
        const openInput = document.getElementById('schedule_' + dayKey + '_open');
        const closeInput = document.getElementById('schedule_' + dayKey + '_close');
        
        if (!checkbox || !openInput || !closeInput) return;
        
        const enabled = checkbox.checked;
        const open = openInput.value.trim();
        const close = closeInput.value.trim();
        
        // Atualizar wizardData
        if (!wizardData.schedule[dayKey]) {
            wizardData.schedule[dayKey] = {
                enabled: enabled,
                ranges: []
            };
        }
        
        wizardData.schedule[dayKey].enabled = enabled;
        
        if (enabled && open && close) {
            wizardData.schedule[dayKey].ranges = [
                { open: open, close: close }
            ];
        } else {
            wizardData.schedule[dayKey].ranges = [];
        }
    };

    window.vcCopyScheduleToAll = function() {
        // Encontrar o primeiro dia habilitado ou usar o primeiro dia como referência
        const days = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
        let sourceDay = null;
        let sourceOpen = '09:00';
        let sourceClose = '18:00';
        
        // Tentar encontrar um dia já habilitado
        for (const day of days) {
            const checkbox = document.getElementById('schedule_' + day);
            if (checkbox && checkbox.checked) {
                const openInput = document.getElementById('schedule_' + day + '_open');
                const closeInput = document.getElementById('schedule_' + day + '_close');
                if (openInput && closeInput && openInput.value && closeInput.value) {
                    sourceDay = day;
                    sourceOpen = openInput.value;
                    sourceClose = closeInput.value;
                    break;
                }
            }
        }
        
        // Se não encontrou, usar o primeiro dia (mesmo que desabilitado)
        if (!sourceDay) {
            sourceDay = days[0];
            const openInput = document.getElementById('schedule_' + sourceDay + '_open');
            const closeInput = document.getElementById('schedule_' + sourceDay + '_close');
            if (openInput && closeInput) {
                sourceOpen = openInput.value || '09:00';
                sourceClose = closeInput.value || '18:00';
            }
        }
        
        // Copiar para todos os dias
        days.forEach(function(day) {
            const checkbox = document.getElementById('schedule_' + day);
            const openInput = document.getElementById('schedule_' + day + '_open');
            const closeInput = document.getElementById('schedule_' + day + '_close');
            
            if (!checkbox || !openInput || !closeInput) return;
            
            // Habilitar o dia
            checkbox.checked = true;
            
            // Copiar horários
            openInput.value = sourceOpen;
            closeInput.value = sourceClose;
            
            // Habilitar campos
            openInput.disabled = false;
            closeInput.disabled = false;
            
            // Atualizar wizardData
            if (!wizardData.schedule[day]) {
                wizardData.schedule[day] = {
                    enabled: true,
                    ranges: []
                };
            }
            wizardData.schedule[day].enabled = true;
            wizardData.schedule[day].ranges = [
                { open: sourceOpen, close: sourceClose }
            ];
        });
    };

    // Coletar horários do DOM quando necessário
    function collectScheduleFromDOM() {
        const days = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
        
        days.forEach(function(day) {
            const checkbox = document.getElementById('schedule_' + day);
            const openInput = document.getElementById('schedule_' + day + '_open');
            const closeInput = document.getElementById('schedule_' + day + '_close');
            
            if (!checkbox || !openInput || !closeInput) return;
            
            const enabled = checkbox.checked;
            const open = openInput.value.trim();
            const close = closeInput.value.trim();
            
            if (!wizardData.schedule[day]) {
                wizardData.schedule[day] = {
                    enabled: enabled,
                    ranges: []
                };
            }
            
            wizardData.schedule[day].enabled = enabled;
            
            if (enabled && open && close) {
                wizardData.schedule[day].ranges = [
                    { open: open, close: close }
                ];
            } else {
                wizardData.schedule[day].ranges = [];
            }
        });
    }

    // Inicializar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWizard);
    } else {
        initWizard();
    }

})();