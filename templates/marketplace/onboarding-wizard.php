<?php
/**
 * Template: Wizard de Onboarding para Lojistas
 * 
 * Este wizard guia o lojista através dos passos essenciais para configurar sua loja:
 * 1. Tipo de restaurante
 * 2. Dados básicos
 * 3. Endereço e horários
 * 4. Categorias do cardápio
 * 5. Primeiros produtos
 * 6. Adicionais (opcional)
 * 7. Revisão e ativação
 * 
 * @package VemComerCore
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$restaurant = vc_marketplace_current_restaurant();
if ( ! $restaurant ) {
    return;
}

$restaurant_data = vc_marketplace_collect_restaurant_data( $restaurant );
$onboarding_status = \VC\Utils\Onboarding_Helper::get_onboarding_status( $restaurant->ID );
$current_step = $onboarding_status['current_step'] ?? 1;

// Buscar categorias de cozinha disponíveis
$all_cuisines = get_terms( [
    'taxonomy'   => 'vc_cuisine',
    'hide_empty' => false,
] );

$cuisine_options = [];
if ( ! is_wp_error( $all_cuisines ) && $all_cuisines ) {
    foreach ( $all_cuisines as $term ) {
        // Filtrar apenas termos filhos (não grupos pais)
        if ( $term->parent === 0 && str_starts_with( (string) $term->slug, 'grupo-' ) ) {
            continue;
        }
        $cuisine_options[] = [
            'id'   => $term->term_id,
            'name' => $term->name,
        ];
    }
}

// Buscar categorias recomendadas de cardápio
$recommended_categories = [];
if ( $restaurant_data['primary_cuisine'] ) {
    // Buscar categorias recomendadas baseadas no tipo de restaurante
    // Isso será feito via JavaScript chamando o endpoint REST
}

$rest_nonce = wp_create_nonce( 'wp_rest' );
$rest_url   = rest_url( 'vemcomer/v1' );
?>

<div id="vcOnboardingWizard" style="position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.85);z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:16px;max-width:800px;width:100%;max-height:90vh;overflow-y:auto;position:relative;box-shadow:0 8px 32px rgba(0,0,0,0.3);">
        <!-- Barra de Progresso -->
        <div style="position:sticky;top:0;background:#2d8659;color:#fff;padding:12px 24px;border-radius:16px 16px 0 0;z-index:10;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <div id="vcWizardStepTitle" style="font-weight:700;font-size:16px;">Passo 1 de 7</div>
                <button onclick="vcCloseOnboardingWizard()" style="background:transparent;border:none;color:#fff;font-size:24px;cursor:pointer;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='transparent'">×</button>
            </div>
            <div style="background:rgba(255,255,255,0.3);height:4px;border-radius:2px;overflow:hidden;">
                <div id="vcWizardProgressBar" style="background:#fff;height:100%;width:14.28%;transition:width 0.3s;"></div>
            </div>
        </div>

        <!-- Conteúdo do Wizard -->
        <div style="padding:32px;">
            <div id="vcWizardContent">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Botões de Navegação -->
        <div style="padding:20px 32px;border-top:2px solid #eaf8f1;display:flex;justify-content:space-between;gap:12px;background:#f9f9f9;border-radius:0 0 16px 16px;">
            <button id="vcWizardBtnPrev" onclick="vcWizardPrev()" style="background:#fff;color:#2d8659;border:2px solid #2d8659;padding:12px 24px;border-radius:8px;font-weight:700;cursor:pointer;display:none;">Voltar</button>
            <div style="flex:1;"></div>
            <button id="vcWizardBtnNext" onclick="vcWizardNext()" style="background:#2d8659;color:#fff;border:none;padding:12px 32px;border-radius:8px;font-weight:700;cursor:pointer;min-width:120px;">Continuar</button>
        </div>
    </div>
</div>

<style>
    #vcOnboardingWizard * { box-sizing:border-box; }
    #vcOnboardingWizard input[type="text"],
    #vcOnboardingWizard input[type="tel"],
    #vcOnboardingWizard input[type="email"],
    #vcOnboardingWizard textarea,
    #vcOnboardingWizard select {
        width:100%;padding:12px;border:2px solid #eaf8f1;border-radius:8px;font-size:15px;font-family:inherit;margin-bottom:16px;
    }
    #vcOnboardingWizard input:focus,
    #vcOnboardingWizard textarea:focus,
    #vcOnboardingWizard select:focus {
        outline:none;border-color:#2d8659;
    }
    #vcOnboardingWizard .wizard-title {
        font-size:24px;font-weight:900;color:#2d8659;margin-bottom:8px;
    }
    #vcOnboardingWizard .wizard-subtitle {
        font-size:15px;color:#6b7672;margin-bottom:24px;line-height:1.5;
    }
    #vcOnboardingWizard .cuisine-option {
        display:inline-block;padding:12px 20px;margin:6px;background:#f5f5f5;border:2px solid #e0e0e0;border-radius:8px;cursor:pointer;transition:all 0.2s;font-weight:600;
    }
    #vcOnboardingWizard .cuisine-option:hover {
        background:#eaf8f1;border-color:#2d8659;
    }
    #vcOnboardingWizard .cuisine-option.selected {
        background:#2d8659;color:#fff;border-color:#2d8659;
    }
    #vcOnboardingWizard .category-checkbox {
        display:flex;align-items:center;padding:12px;margin-bottom:8px;background:#f9f9f9;border-radius:8px;cursor:pointer;transition:background 0.2s;
    }
    #vcOnboardingWizard .category-checkbox:hover {
        background:#eaf8f1;
    }
    #vcOnboardingWizard .category-checkbox input[type="checkbox"] {
        width:20px;height:20px;margin-right:12px;cursor:pointer;
    }
    #vcOnboardingWizard .product-item {
        background:#f9f9f9;border-radius:8px;padding:16px;margin-bottom:16px;
    }
    #vcOnboardingWizard .schedule-day {
        display:flex;align-items:center;gap:12px;padding:12px;background:#f9f9f9;border-radius:8px;margin-bottom:8px;
    }
    #vcOnboardingWizard .schedule-day input[type="checkbox"] {
        width:20px;height:20px;
    }
    #vcOnboardingWizard .schedule-day input[type="time"] {
        width:120px;margin:0;
    }
    #vcOnboardingWizard .error-message {
        background:#ffe7e7;color:#ea5252;padding:12px;border-radius:8px;margin-bottom:16px;font-weight:600;
    }
</style>

<script>
(function() {
    'use strict';

    const REST_BASE = '<?php echo esc_js( $rest_url ); ?>';
    const REST_NONCE = '<?php echo esc_js( $rest_nonce ); ?>';
    const RESTAURANT_ID = <?php echo $restaurant->ID; ?>;
    const CURRENT_STEP = <?php echo $current_step; ?>;
    
    let wizardStep = CURRENT_STEP;
    const TOTAL_STEPS = 7;
    
    const cuisineOptions = <?php echo wp_json_encode( $cuisine_options ); ?>;
    const restaurantData = <?php echo wp_json_encode( $restaurant_data ); ?>;
    
    // Dados temporários do wizard
    let wizardData = {
        cuisine_ids: [],
        name: restaurantData.nome || '',
        whatsapp: restaurantData.whatsapp || '',
        logo: restaurantData.logo || '',
        address: restaurantData.endereco || '',
        neighborhood: restaurantData.bairro || '',
        city: '',
        zipcode: '',
        delivery: true,
        pickup: false,
        schedule: {},
        category_names: [],
        products: [],
        addon_groups: [],
    };

    // Inicializar wizard
    function initWizard() {
        renderStep();
    }

    // Renderizar passo atual
    function renderStep() {
        const stepTitle = getStepTitle(wizardStep);
        document.getElementById('vcWizardStepTitle').textContent = `Passo ${wizardStep} de ${TOTAL_STEPS} - ${stepTitle}`;
        document.getElementById('vcWizardProgressBar').style.width = `${(wizardStep / TOTAL_STEPS) * 100}%`;
        
        const content = getStepContent(wizardStep);
        document.getElementById('vcWizardContent').innerHTML = content;
        
        // Atualizar botões
        const btnPrev = document.getElementById('vcWizardBtnPrev');
        const btnNext = document.getElementById('vcWizardBtnNext');
        
        btnPrev.style.display = wizardStep > 1 ? 'block' : 'none';
        btnNext.textContent = wizardStep === TOTAL_STEPS ? 'Ativar minha loja' : 'Continuar';
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

    function getStepContent(step) {
        switch(step) {
            case 1: return getStep1Content();
            case 2: return getStep2Content();
            case 3: return getStep3Content();
            case 4: return getStep4Content();
            case 5: return getStep5Content();
            case 6: return getStep6Content();
            case 7: return getStep7Content();
            default: return '';
        }
    }

    // PASSO 1: Tipo de Restaurante
    function getStep1Content() {
        const selected = wizardData.cuisine_ids || [];
        const optionsHtml = cuisineOptions.map(c => {
            const isSelected = selected.includes(c.id);
            return `<div class="cuisine-option ${isSelected ? 'selected' : ''}" onclick="vcToggleCuisine(${c.id})">${escapeHtml(c.name)}</div>`;
        }).join('');

        return `
            <div class="wizard-title">Bem-vindo ao PedeVem!</div>
            <div class="wizard-subtitle">Vamos colocar sua loja no ar em poucos minutos. Primeiro, diga que tipo de negócio você tem.</div>
            <div style="margin-top:24px;">
                ${optionsHtml}
            </div>
            <div style="margin-top:16px;font-size:13px;color:#6b7672;">Você pode selecionar até 3 tipos de restaurante.</div>
        `;
    }

    // PASSO 2: Dados Básicos
    function getStep2Content() {
        return `
            <div class="wizard-title">Dados básicos da sua loja</div>
            <div class="wizard-subtitle">Essas informações aparecem para os clientes. Você pode alterar depois quando quiser.</div>
            
            <label style="display:block;font-weight:700;margin-bottom:8px;color:#232a2c;">Nome da loja *</label>
            <input type="text" id="wizardName" value="${escapeHtml(wizardData.name)}" placeholder="Ex: Hamburgueria do João" required>
            
            <label style="display:block;font-weight:700;margin-bottom:8px;color:#232a2c;">Telefone / WhatsApp *</label>
            <input type="tel" id="wizardWhatsapp" value="${escapeHtml(wizardData.whatsapp)}" placeholder="(00) 00000-0000" required>
            
            <label style="display:block;font-weight:700;margin-bottom:8px;color:#232a2c;">Logo (opcional)</label>
            <div style="margin-bottom:16px;">
                <input type="file" id="wizardLogo" accept="image/*" onchange="vcHandleLogoUpload(event)" style="margin-bottom:8px;">
                <div id="wizardLogoPreview" style="margin-top:12px;"></div>
            </div>
        `;
    }

    // PASSO 3: Endereço e Horários
    function getStep3Content() {
        const days = [
            { key: 'seg', name: 'Segunda-feira' },
            { key: 'ter', name: 'Terça-feira' },
            { key: 'qua', name: 'Quarta-feira' },
            { key: 'qui', name: 'Quinta-feira' },
            { key: 'sex', name: 'Sexta-feira' },
            { key: 'sab', name: 'Sábado' },
            { key: 'dom', name: 'Domingo' },
        ];

        const scheduleHtml = days.map(day => {
            const dayData = wizardData.schedule[day.key] || { enabled: false, ranges: [{ open: '09:00', close: '18:00' }] };
            return `
                <div class="schedule-day">
                    <input type="checkbox" id="schedule_${day.key}" ${dayData.enabled ? 'checked' : ''} onchange="vcToggleScheduleDay('${day.key}')">
                    <label for="schedule_${day.key}" style="flex:1;font-weight:600;">${day.name}</label>
                    <input type="time" id="schedule_${day.key}_open" value="${dayData.ranges[0]?.open || '09:00'}" onchange="vcUpdateSchedule('${day.key}')" ${!dayData.enabled ? 'disabled' : ''}>
                    <span>até</span>
                    <input type="time" id="schedule_${day.key}_close" value="${dayData.ranges[0]?.close || '18:00'}" onchange="vcUpdateSchedule('${day.key}')" ${!dayData.enabled ? 'disabled' : ''}>
                </div>
            `;
        }).join('');

        return `
            <div class="wizard-title">Endereço e horários</div>
            <div class="wizard-subtitle">Precisamos saber onde sua loja fica e quando ela está aberta para aceitar pedidos.</div>
            
            <h3 style="font-size:18px;font-weight:700;margin:24px 0 12px 0;color:#2d8659;">Endereço</h3>
            <input type="text" id="wizardAddress" value="${escapeHtml(wizardData.address)}" placeholder="Endereço completo" required>
            <input type="text" id="wizardNeighborhood" value="${escapeHtml(wizardData.neighborhood)}" placeholder="Bairro">
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;">
                <input type="text" id="wizardCity" value="${escapeHtml(wizardData.city)}" placeholder="Cidade">
                <input type="text" id="wizardZipcode" value="${escapeHtml(wizardData.zipcode)}" placeholder="CEP">
            </div>
            
            <h3 style="font-size:18px;font-weight:700;margin:24px 0 12px 0;color:#2d8659;">Método de Atendimento</h3>
            <label style="display:flex;align-items:center;padding:12px;background:#f9f9f9;border-radius:8px;margin-bottom:8px;cursor:pointer;">
                <input type="checkbox" id="wizardDelivery" ${wizardData.delivery ? 'checked' : ''} style="width:20px;height:20px;margin-right:12px;">
                <span style="font-weight:600;">Entrega própria</span>
            </label>
            <label style="display:flex;align-items:center;padding:12px;background:#f9f9f9;border-radius:8px;margin-bottom:8px;cursor:pointer;">
                <input type="checkbox" id="wizardPickup" ${wizardData.pickup ? 'checked' : ''} style="width:20px;height:20px;margin-right:12px;">
                <span style="font-weight:600;">Apenas retirada no local</span>
            </label>
            
            <h3 style="font-size:18px;font-weight:700;margin:24px 0 12px 0;color:#2d8659;">Horários de Funcionamento</h3>
            <div style="margin-bottom:12px;">
                <button type="button" onclick="vcCopyScheduleToAll()" style="background:#facb32;color:#232a2c;border:none;padding:8px 16px;border-radius:6px;font-weight:600;cursor:pointer;margin-bottom:12px;">Copiar para todos os dias</button>
            </div>
            ${scheduleHtml}
        `;
    }

    // PASSO 4: Categorias do Cardápio
    function getStep4Content() {
        return `
            <div class="wizard-title">Categorias do seu cardápio</div>
            <div class="wizard-subtitle">Sugerimos algumas categorias de cardápio para o tipo de restaurante que você escolheu. Você pode editar depois.</div>
            <div id="wizardRecommendedCategories" style="margin-top:24px;">
                <div style="text-align:center;padding:40px;color:#999;">Carregando categorias recomendadas...</div>
            </div>
            <div style="margin-top:16px;">
                <a href="#" onclick="vcSkipCategories();return false;" style="color:#2d8659;text-decoration:underline;font-size:14px;">Pular (vou criar manualmente depois)</a>
            </div>
        `;
    }

    // PASSO 5: Primeiros Produtos
    function getStep5Content() {
        const productsHtml = wizardData.products.map((p, idx) => `
            <div class="product-item">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <strong style="font-size:16px;">${escapeHtml(p.name || 'Produto ' + (idx + 1))}</strong>
                    <button onclick="vcRemoveProduct(${idx})" style="background:#ffe7e7;color:#ea5252;border:none;padding:4px 12px;border-radius:6px;cursor:pointer;font-weight:600;">Remover</button>
                </div>
                <div style="color:#6b7672;font-size:14px;">Categoria: ${escapeHtml(p.category || 'Sem categoria')} | Preço: R$ ${parseFloat(p.price || 0).toFixed(2)}</div>
            </div>
        `).join('');

        return `
            <div class="wizard-title">Cadastre seus primeiros produtos</div>
            <div class="wizard-subtitle">Comece pelos seus campeões de venda. Recomendamos cadastrar pelo menos 3.</div>
            
            <div id="wizardProductsList" style="margin-top:24px;margin-bottom:24px;">
                ${productsHtml || '<div style="text-align:center;padding:20px;color:#999;">Nenhum produto cadastrado ainda.</div>'}
            </div>
            
            <div style="background:#eaf8f1;padding:16px;border-radius:8px;margin-bottom:16px;">
                <div style="font-weight:700;margin-bottom:12px;color:#2d8659;">Adicionar Produto</div>
                <input type="text" id="wizardProductName" placeholder="Nome do produto *" style="margin-bottom:12px;">
                <select id="wizardProductCategory" style="margin-bottom:12px;">
                    <option value="">Selecione a categoria</option>
                </select>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
                    <input type="number" id="wizardProductPrice" placeholder="Preço (R$) *" step="0.01" min="0">
                    <input type="file" id="wizardProductImage" accept="image/*">
                </div>
                <textarea id="wizardProductDescription" placeholder="Descrição (opcional)" rows="2" style="margin-bottom:12px;"></textarea>
                <button onclick="vcAddProduct()" style="background:#2d8659;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:700;cursor:pointer;width:100%;">Adicionar Produto</button>
            </div>
            
            <div style="background:#fffbe2;padding:12px;border-radius:8px;font-size:14px;color:#856404;">
                Você já cadastrou <strong>${wizardData.products.length}</strong> produto(s). Recomendamos pelo menos 3.
            </div>
        `;
    }

    // PASSO 6: Adicionais (Opcional)
    function getStep6Content() {
        return `
            <div class="wizard-title">Quer oferecer adicionais?</div>
            <div class="wizard-subtitle">Adicionais como queijos extras, bebidas do combo e molhos podem aumentar o valor de cada pedido.</div>
            <div id="wizardRecommendedAddons" style="margin-top:24px;">
                <div style="text-align:center;padding:40px;color:#999;">Carregando grupos recomendados...</div>
            </div>
            <div style="margin-top:24px;text-align:center;">
                <button onclick="vcSkipAddons()" style="background:transparent;color:#6b7672;border:2px solid #e0e0e0;padding:12px 24px;border-radius:8px;font-weight:600;cursor:pointer;">Pular por enquanto</button>
            </div>
        `;
    }

    // PASSO 7: Revisão
    function getStep7Content() {
        const hasAddons = wizardData.addon_groups && wizardData.addon_groups.length > 0;
        
        return `
            <div class="wizard-title">Seu restaurante está quase pronto!</div>
            <div style="margin-top:24px;">
                <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
                    <span style="font-size:24px;margin-right:12px;">✔</span>
                    <span style="font-weight:600;">Dados básicos da loja</span>
                </div>
                <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
                    <span style="font-size:24px;margin-right:12px;">✔</span>
                    <span style="font-weight:600;">Endereço e horários</span>
                </div>
                <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
                    <span style="font-size:24px;margin-right:12px;">✔</span>
                    <span style="font-weight:600;">Categorias do cardápio (${wizardData.category_names.length} criadas)</span>
                </div>
                <div style="display:flex;align-items:center;padding:12px;background:#eaf8f1;border-radius:8px;margin-bottom:8px;">
                    <span style="font-size:24px;margin-right:12px;">✔</span>
                    <span style="font-weight:600;">Produtos cadastrados (${wizardData.products.length} produtos)</span>
                </div>
                <div style="display:flex;align-items:center;padding:12px;background:${hasAddons ? '#eaf8f1' : '#fffbe2'};border-radius:8px;margin-bottom:8px;">
                    <span style="font-size:24px;margin-right:12px;">${hasAddons ? '✔' : '⭕'}</span>
                    <span style="font-weight:600;">Adicionais configurados ${hasAddons ? '' : '(opcional)'}</span>
                </div>
            </div>
            
            <div style="margin-top:32px;padding:20px;background:#f9f9f9;border-radius:8px;">
                <div style="font-weight:700;margin-bottom:12px;color:#2d8659;">Resumo da sua loja</div>
                <div style="margin-bottom:8px;"><strong>Nome:</strong> ${escapeHtml(wizardData.name)}</div>
                <div style="margin-bottom:8px;"><strong>WhatsApp:</strong> ${escapeHtml(wizardData.whatsapp)}</div>
                <div style="margin-bottom:8px;"><strong>Endereço:</strong> ${escapeHtml(wizardData.address)}</div>
                <div style="margin-top:16px;">
                    <strong>Produtos:</strong>
                    <ul style="margin:8px 0 0 20px;padding:0;">
                        ${wizardData.products.slice(0, 3).map(p => `<li>${escapeHtml(p.name)} - R$ ${parseFloat(p.price || 0).toFixed(2)}</li>`).join('')}
                    </ul>
                </div>
            </div>
        `;
    }

    // Funções de navegação
    function vcWizardNext() {
        if (!validateStep(wizardStep)) {
            return;
        }

        saveStep(wizardStep).then(() => {
            if (wizardStep < TOTAL_STEPS) {
                wizardStep++;
                renderStep();
            } else {
                completeOnboarding();
            }
        }).catch(err => {
            alert('Erro ao salvar: ' + (err.message || 'Erro desconhecido'));
        });
    }

    function vcWizardPrev() {
        if (wizardStep > 1) {
            wizardStep--;
            renderStep();
        }
    }

    function vcCloseOnboardingWizard() {
        if (confirm('Tem certeza que deseja sair? Seu progresso será salvo.')) {
            document.getElementById('vcOnboardingWizard').style.display = 'none';
            location.reload();
        }
    }

    // Validação de passos
    function validateStep(step) {
        const errorDiv = document.getElementById('vcWizardError');
        if (errorDiv) {
            errorDiv.remove();
        }

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
                if (!name) {
                    showError('Nome da loja é obrigatório.');
                    return false;
                }
                if (!whatsapp) {
                    showError('Telefone/WhatsApp é obrigatório.');
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
                wizardData.neighborhood = document.getElementById('wizardNeighborhood')?.value.trim();
                wizardData.city = document.getElementById('wizardCity')?.value.trim();
                wizardData.zipcode = document.getElementById('wizardZipcode')?.value.trim();
                wizardData.delivery = document.getElementById('wizardDelivery')?.checked || false;
                wizardData.pickup = document.getElementById('wizardPickup')?.checked || false;
                
                // Validar pelo menos um dia de funcionamento
                let hasSchedule = false;
                for (const day of ['seg','ter','qua','qui','sex','sab','dom']) {
                    const enabled = document.getElementById(`schedule_${day}`)?.checked;
                    if (enabled) {
                        hasSchedule = true;
                        break;
                    }
                }
                if (!hasSchedule) {
                    showError('Configure pelo menos um dia de funcionamento.');
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
        content.insertBefore(errorDiv, content.firstChild);
    }

    // Salvar passo
    async function saveStep(step) {
        const stepSlugs = {
            1: 'welcome',
            2: 'basic_data',
            3: 'address_schedule',
            4: 'categories',
            5: 'products',
            6: 'addons',
        };

        const payload = {
            step: step,
            step_slug: stepSlugs[step] || '',
        };

        // Adicionar dados específicos do passo
        switch(step) {
            case 1:
                payload.cuisine_ids = wizardData.cuisine_ids;
                break;
            case 2:
                payload.name = wizardData.name;
                payload.whatsapp = wizardData.whatsapp;
                if (wizardData.logo) {
                    payload.logo = wizardData.logo;
                }
                break;
            case 3:
                payload.address = wizardData.address;
                payload.neighborhood = wizardData.neighborhood;
                payload.city = wizardData.city;
                payload.zipcode = wizardData.zipcode;
                payload.delivery = wizardData.delivery;
                payload.pickup = wizardData.pickup;
                payload.schedule = wizardData.schedule;
                break;
            case 4:
                payload.category_names = wizardData.category_names;
                break;
            case 5:
                payload.products = wizardData.products;
                break;
            case 6:
                payload.addon_groups = wizardData.addon_groups;
                break;
        }

        const response = await fetch(`${REST_BASE}/onboarding/step`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': REST_NONCE,
            },
            body: JSON.stringify(payload),
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message || 'Erro ao salvar passo');
        }

        return await response.json();
    }

    // Completar onboarding
    async function completeOnboarding() {
        try {
            const response = await fetch(`${REST_BASE}/onboarding/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': REST_NONCE,
                },
            });

            if (!response.ok) {
                throw new Error('Erro ao completar onboarding');
            }

            alert('Parabéns! Sua loja está ativa e pronta para receber pedidos!');
            location.reload();
        } catch (err) {
            alert('Erro ao ativar loja: ' + err.message);
        }
    }

    // Funções auxiliares globais
    window.vcToggleCuisine = function(id) {
        if (!wizardData.cuisine_ids) {
            wizardData.cuisine_ids = [];
        }
        const idx = wizardData.cuisine_ids.indexOf(id);
        if (idx > -1) {
            wizardData.cuisine_ids.splice(idx, 1);
        } else {
            if (wizardData.cuisine_ids.length < 3) {
                wizardData.cuisine_ids.push(id);
            } else {
                alert('Você pode selecionar no máximo 3 tipos de restaurante.');
                return;
            }
        }
        renderStep();
    };

    window.vcHandleLogoUpload = function(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            wizardData.logo = e.target.result;
            const preview = document.getElementById('wizardLogoPreview');
            if (preview) {
                preview.innerHTML = `<img src="${e.target.result}" style="max-width:200px;max-height:200px;border-radius:8px;margin-top:8px;">`;
            }
        };
        reader.readAsDataURL(file);
    };

    window.vcToggleScheduleDay = function(day) {
        const checkbox = document.getElementById(`schedule_${day}`);
        const openInput = document.getElementById(`schedule_${day}_open`);
        const closeInput = document.getElementById(`schedule_${day}_close`);
        
        if (openInput) openInput.disabled = !checkbox.checked;
        if (closeInput) closeInput.disabled = !checkbox.checked;
        
        vcUpdateSchedule(day);
    };

    window.vcUpdateSchedule = function(day) {
        if (!wizardData.schedule) {
            wizardData.schedule = {};
        }
        const checkbox = document.getElementById(`schedule_${day}`);
        const openInput = document.getElementById(`schedule_${day}_open`);
        const closeInput = document.getElementById(`schedule_${day}_close`);
        
        wizardData.schedule[day] = {
            enabled: checkbox?.checked || false,
            ranges: [{
                open: openInput?.value || '09:00',
                close: closeInput?.value || '18:00',
            }],
        };
    };

    window.vcCopyScheduleToAll = function() {
        const segOpen = document.getElementById('schedule_seg_open')?.value || '09:00';
        const segClose = document.getElementById('schedule_seg_close')?.value || '18:00';
        
        for (const day of ['seg','ter','qua','qui','sex','sab','dom']) {
            const checkbox = document.getElementById(`schedule_${day}`);
            const openInput = document.getElementById(`schedule_${day}_open`);
            const closeInput = document.getElementById(`schedule_${day}_close`);
            
            if (checkbox && openInput && closeInput) {
                checkbox.checked = true;
                openInput.value = segOpen;
                closeInput.value = segClose;
                openInput.disabled = false;
                closeInput.disabled = false;
                vcUpdateSchedule(day);
            }
        }
    };

    // Carregar categorias recomendadas (Passo 4)
    async function loadRecommendedCategories() {
        try {
            const response = await fetch(`${REST_BASE}/menu-categories/recommended`, {
                headers: {
                    'X-WP-Nonce': REST_NONCE,
                },
            });

            if (!response.ok) {
                throw new Error('Erro ao carregar categorias');
            }

            const categories = await response.json();
            const container = document.getElementById('wizardRecommendedCategories');
            
            if (!container) return;

            if (!categories || categories.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:20px;color:#999;">Nenhuma categoria recomendada encontrada.</div>';
                return;
            }

            const html = categories.map(cat => `
                <label class="category-checkbox">
                    <input type="checkbox" value="${escapeHtml(cat.name)}" onchange="vcToggleCategory('${escapeHtml(cat.name)}')">
                    <span style="font-weight:600;">${escapeHtml(cat.name)}</span>
                </label>
            `).join('');

            container.innerHTML = html;
        } catch (err) {
            console.error('Erro ao carregar categorias:', err);
        }
    }

    window.vcToggleCategory = function(name) {
        if (!wizardData.category_names) {
            wizardData.category_names = [];
        }
        const idx = wizardData.category_names.indexOf(name);
        if (idx > -1) {
            wizardData.category_names.splice(idx, 1);
        } else {
            wizardData.category_names.push(name);
        }
    };

    window.vcSkipCategories = function() {
        if (confirm('Tem certeza que deseja pular? Você pode criar categorias manualmente depois.')) {
            wizardData.category_names = [];
            wizardStep++;
            renderStep();
        }
    };

    // Funções de produtos (Passo 5)
    async function loadProductCategories() {
        try {
            const response = await fetch(`${REST_BASE}/menu-categories`, {
                headers: {
                    'X-WP-Nonce': REST_NONCE,
                },
            });

            if (!response.ok) {
                throw new Error('Erro ao carregar categorias');
            }

            const categories = await response.json();
            const select = document.getElementById('wizardProductCategory');
            
            if (!select) return;

            select.innerHTML = '<option value="">Selecione a categoria</option>' +
                categories.map(cat => `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`).join('');
        } catch (err) {
            console.error('Erro ao carregar categorias:', err);
        }
    }

    window.vcAddProduct = function() {
        const name = document.getElementById('wizardProductName')?.value.trim();
        const categoryId = document.getElementById('wizardProductCategory')?.value;
        const price = document.getElementById('wizardProductPrice')?.value;
        const description = document.getElementById('wizardProductDescription')?.value.trim();

        if (!name) {
            alert('Nome do produto é obrigatório.');
            return;
        }
        if (!categoryId) {
            alert('Selecione uma categoria.');
            return;
        }
        if (!price || parseFloat(price) <= 0) {
            alert('Preço é obrigatório e deve ser maior que zero.');
            return;
        }

        const categorySelect = document.getElementById('wizardProductCategory');
        const categoryName = categorySelect?.options[categorySelect.selectedIndex]?.text || '';

        const product = {
            name: name,
            category: categoryName,
            category_id: categoryId,
            price: parseFloat(price),
            description: description,
        };

        // Handle image upload
        const imageInput = document.getElementById('wizardProductImage');
        if (imageInput?.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                product.image = e.target.result;
                addProductToList(product);
            };
            reader.readAsDataURL(imageInput.files[0]);
        } else {
            addProductToList(product);
        }
    };

    function addProductToList(product) {
        if (!wizardData.products) {
            wizardData.products = [];
        }
        wizardData.products.push(product);
        
        // Limpar formulário
        document.getElementById('wizardProductName').value = '';
        document.getElementById('wizardProductCategory').value = '';
        document.getElementById('wizardProductPrice').value = '';
        document.getElementById('wizardProductDescription').value = '';
        document.getElementById('wizardProductImage').value = '';
        
        renderStep();
    }

    window.vcRemoveProduct = function(index) {
        if (confirm('Tem certeza que deseja remover este produto?')) {
            wizardData.products.splice(index, 1);
            renderStep();
        }
    };

    // Carregar adicionais recomendados (Passo 6)
    async function loadRecommendedAddons() {
        try {
            const response = await fetch(`${REST_BASE}/addon-catalog/recommended-groups`, {
                headers: {
                    'X-WP-Nonce': REST_NONCE,
                },
            });

            if (!response.ok) {
                throw new Error('Erro ao carregar adicionais');
            }

            const groups = await response.json();
            const container = document.getElementById('wizardRecommendedAddons');
            
            if (!container) return;

            if (!groups || groups.length === 0) {
                container.innerHTML = '<div style="text-align:center;padding:20px;color:#999;">Nenhum grupo de adicionais recomendado encontrado.</div>';
                return;
            }

            const basicGroups = groups.filter(g => g.difficulty_level === 'basic');
            const html = basicGroups.map(group => `
                <label class="category-checkbox">
                    <input type="checkbox" value="${group.id}" onchange="vcToggleAddonGroup(${group.id})">
                    <span style="font-weight:600;">${escapeHtml(group.name)}</span>
                </label>
            `).join('');

            container.innerHTML = html || '<div style="text-align:center;padding:20px;color:#999;">Nenhum grupo básico disponível.</div>';
        } catch (err) {
            console.error('Erro ao carregar adicionais:', err);
        }
    }

    window.vcToggleAddonGroup = function(groupId) {
        if (!wizardData.addon_groups) {
            wizardData.addon_groups = [];
        }
        const idx = wizardData.addon_groups.indexOf(groupId);
        if (idx > -1) {
            wizardData.addon_groups.splice(idx, 1);
        } else {
            wizardData.addon_groups.push(groupId);
        }
    };

    window.vcSkipAddons = function() {
        wizardData.addon_groups = [];
        wizardStep++;
        renderStep();
    };

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Expor funções globalmente
    window.vcWizardNext = vcWizardNext;
    window.vcWizardPrev = vcWizardPrev;
    window.vcCloseOnboardingWizard = vcCloseOnboardingWizard;

    // Inicializar quando o passo 4 for renderizado
    const originalRenderStep = renderStep;
    renderStep = function() {
        originalRenderStep();
        if (wizardStep === 4) {
            loadRecommendedCategories();
        } else if (wizardStep === 5) {
            loadProductCategories();
        } else if (wizardStep === 6) {
            loadRecommendedAddons();
        }
    };

    // Inicializar wizard
    document.addEventListener('DOMContentLoaded', function() {
        initWizard();
    });

    // Se o DOM já estiver carregado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWizard);
    } else {
        initWizard();
    }
})();
</script>

