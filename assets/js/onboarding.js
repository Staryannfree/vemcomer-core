/**
 * JavaScript do Sistema de Onboarding
 * VemComer Core
 */

(function() {
    'use strict';

    const Onboarding = {
        init: function() {
            const onboarding = document.querySelector('.vc-onboarding');
            if (!onboarding) {
                return;
            }

            this.onboarding = onboarding;
            this.modal = onboarding ? onboarding.querySelector('.vc-onboarding__modal') : null;
            this.userId = onboarding ? onboarding.dataset.userId : null;
            this.nonce = this.getNonce();

            this.bindEvents();
            this.bindOpenButton();
        },

        bindOpenButton: function() {
            // Botão para abrir o onboarding
            const openBtn = document.querySelector('[data-action="open-onboarding"]');
            if (openBtn) {
                openBtn.addEventListener('click', () => this.open());
            }
        },

        open: function() {
            if (!this.onboarding) {
                // Tenta encontrar novamente (caso tenha sido adicionado dinamicamente)
                this.onboarding = document.querySelector('.vc-onboarding');
                if (!this.onboarding) {
                    return;
                }
                this.modal = this.onboarding.querySelector('.vc-onboarding__modal');
                this.userId = this.onboarding.dataset.userId;
            }

            // Remove classe hidden e mostra
            this.onboarding.classList.remove('vc-onboarding--hidden');
            this.onboarding.style.display = 'block';
            
            // Força display dos elementos internos
            const overlay = this.onboarding.querySelector('.vc-onboarding__overlay');
            if (overlay) {
                overlay.style.display = 'block';
            }
            if (this.modal) {
                this.modal.style.display = 'block';
            }
            
            // Previne scroll do body
            this.preventBodyScroll(true);
            
            // Foca no modal para acessibilidade
            if (this.modal) {
                // Adiciona tabindex para permitir foco
                if (!this.modal.hasAttribute('tabindex')) {
                    this.modal.setAttribute('tabindex', '-1');
                }
                this.modal.focus();
            }
        },

        getNonce: function() {
            // Busca o nonce do WordPress (geralmente em wp_localize_script)
            if (typeof vemcomerOnboarding !== 'undefined' && vemcomerOnboarding.nonce) {
                return vemcomerOnboarding.nonce;
            }
            // Fallback: busca do meta tag ou gera via AJAX
            const meta = document.querySelector('meta[name="vc-onboarding-nonce"]');
            return meta ? meta.content : '';
        },

        bindEvents: function() {
            // Botão fechar
            const closeBtn = this.onboarding.querySelector('.vc-onboarding__close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.dismiss());
            }

            // Overlay - fechar ao clicar fora
            const overlay = this.onboarding.querySelector('.vc-onboarding__overlay');
            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        this.dismiss();
                    }
                });
            }

            // Botão pular
            const skipBtn = this.onboarding.querySelector('.vc-onboarding__skip');
            if (skipBtn) {
                skipBtn.addEventListener('click', () => this.dismiss());
            }

            // Botão próximo/concluído
            const nextBtn = this.onboarding.querySelector('.vc-onboarding__next');
            if (nextBtn) {
                nextBtn.addEventListener('click', () => {
                    const action = nextBtn.dataset.action;
                    const step = nextBtn.dataset.step;

                    if (action === 'next') {
                        this.nextStep();
                    } else if (action === 'complete') {
                        this.completeStep(step);
                    }
                });
            }

            // ESC para fechar
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.onboarding && this.isVisible()) {
                    this.dismiss();
                }
            });

            // Prevenir scroll do body quando modal está aberto
            this.preventBodyScroll(true);
        },

        isVisible: function() {
            return this.onboarding && !this.onboarding.classList.contains('vc-onboarding--hidden');
        },

        preventBodyScroll: function(prevent) {
            if (prevent) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        },

        nextStep: function() {
            // Encontra o step atual
            const currentStepEl = this.modal.querySelector('.vc-onboarding__step--current');
            if (!currentStepEl) {
                return;
            }

            const currentStepKey = currentStepEl.dataset.stepKey;
            const steps = this.getStepsOrder();

            // Encontra o índice do step atual
            const currentIndex = steps.indexOf(currentStepKey);
            if (currentIndex === -1 || currentIndex === steps.length - 1) {
                // Último step ou não encontrado
                return;
            }

            // Move para o próximo step
            const nextStepKey = steps[currentIndex + 1];
            const nextStepEl = this.modal.querySelector(`[data-step-key="${nextStepKey}"]`);

            if (nextStepEl) {
                // Atualiza classes
                currentStepEl.classList.remove('vc-onboarding__step--current');
                currentStepEl.classList.add('vc-onboarding__step--completed');
                nextStepEl.classList.add('vc-onboarding__step--current');
                nextStepEl.classList.remove('vc-onboarding__step--completed');

                // Atualiza conteúdo do modal
                this.updateStepContent(nextStepKey);
                this.scrollToTop();
            }
        },

        getStepsOrder: function() {
            // Retorna a ordem dos steps baseado no HTML
            const steps = [];
            const stepElements = this.modal.querySelectorAll('.vc-onboarding__step');
            stepElements.forEach(el => {
                const key = el.dataset.stepKey;
                if (key) {
                    steps.push(key);
                }
            });
            return steps;
        },

        updateStepContent: function(stepKey) {
            // Atualiza título e descrição baseado no step
            // O conteúdo já está no HTML, apenas atualiza o que é visível
            const stepConfig = {
                'welcome': {
                    title: 'Bem-vindo ao VemComer!',
                    description: 'Vamos configurar seu restaurante em poucos passos.',
                    showAction: false
                },
                'complete_profile': {
                    title: 'Complete seu perfil',
                    description: 'Adicione informações importantes como horários, telefone e endereço.',
                    showAction: true,
                    actionText: 'Editar restaurante'
                },
                'add_menu_items': {
                    title: 'Adicione itens ao cardápio',
                    description: 'Crie pelo menos 3 itens para começar a receber pedidos.',
                    showAction: true,
                    actionText: 'Gerenciar cardápio'
                },
                'configure_delivery': {
                    title: 'Configure delivery',
                    description: 'Defina se oferece delivery e valores de entrega.',
                    showAction: true,
                    actionText: 'Editar restaurante'
                },
                'view_public_page': {
                    title: 'Veja sua página pública',
                    description: 'Confira como os clientes veem seu restaurante.',
                    showAction: true,
                    actionText: 'Ver página pública'
                }
            };

            const config = stepConfig[stepKey];
            if (!config) {
                return;
            }

            // Atualiza título
            const titleEl = this.modal.querySelector('.vc-onboarding__title');
            if (titleEl) {
                titleEl.textContent = config.title;
            }

            // Atualiza descrição
            const descEl = this.modal.querySelector('.vc-onboarding__description');
            if (descEl) {
                descEl.textContent = config.description;
            }

            // Atualiza botão de ação
            const actionsEl = this.modal.querySelector('.vc-onboarding__actions');
            if (actionsEl) {
                if (config.showAction && actionsEl.querySelector('.vc-btn')) {
                    actionsEl.style.display = 'block';
                } else {
                    actionsEl.style.display = 'none';
                }
            }

            // Atualiza botão próximo
            const nextBtn = this.modal.querySelector('.vc-onboarding__next');
            if (nextBtn) {
                if (stepKey === 'welcome') {
                    nextBtn.dataset.action = 'next';
                    nextBtn.textContent = 'Começar';
                } else {
                    nextBtn.dataset.action = 'complete';
                    nextBtn.dataset.step = stepKey;
                    nextBtn.textContent = 'Concluído';
                }
            }
        },

        scrollToTop: function() {
            if (this.modal) {
                this.modal.scrollTop = 0;
            }
        },

        completeStep: function(step) {
            if (!step || !this.userId) {
                return;
            }

            this.setLoading(true);

            // Requisição AJAX
            const formData = new FormData();
            formData.append('action', 'vc_onboarding_complete_step');
            formData.append('step', step);
            formData.append('nonce', this.nonce);

            const ajaxUrl = (typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof vemcomerOnboarding !== 'undefined' && vemcomerOnboarding.ajaxurl ? vemcomerOnboarding.ajaxurl : '/wp-admin/admin-ajax.php'));

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                this.setLoading(false);

                if (data.success) {
                    // Atualiza o progresso
                    this.updateProgress(data.data.progress);

                    // Marca o step como completo
                    const stepElement = this.modal.querySelector(`[data-step="${step}"]`);
                    if (stepElement) {
                        stepElement.classList.remove('vc-onboarding__step--current');
                        stepElement.classList.add('vc-onboarding__step--completed');
                    }

                    // Se todos completos, finaliza
                    if (data.data.completed) {
                        this.completeAll();
                    } else {
                        // Move para o próximo step
                        this.nextStep();
                    }
                } else {
                    this.showError(data.data?.message || 'Erro ao completar step');
                }
            })
            .catch(error => {
                this.setLoading(false);
                console.error('Erro ao completar step:', error);
                this.showError('Erro ao salvar progresso. Tente novamente.');
            });
        },

        updateProgress: function(progress) {
            const progressBar = this.modal.querySelector('.vc-onboarding__progress-bar');
            const progressText = this.modal.querySelector('.vc-onboarding__progress-text');

            if (progressBar) {
                progressBar.style.width = progress + '%';
            }

            if (progressText) {
                progressText.textContent = progress + '% completo';
            }
        },

        completeAll: function() {
            // Mostra mensagem de conclusão
            const content = this.modal.querySelector('.vc-onboarding__content');
            if (content) {
                content.innerHTML = `
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">🎉</div>
                        <h3 style="margin: 0 0 16px; font-size: 24px; color: #111827;">
                            Parabéns! Você completou a configuração inicial.
                        </h3>
                        <p style="margin: 0; color: #6b7280; font-size: 16px;">
                            Seu restaurante está pronto para receber pedidos!
                        </p>
                    </div>
                `;
            }

            // Esconde footer
            const footer = this.modal.querySelector('.vc-onboarding__footer');
            if (footer) {
                footer.style.display = 'none';
            }

            // Fecha automaticamente após 3 segundos
            setTimeout(() => {
                this.close();
            }, 3000);
        },

        dismiss: function() {
            if (!this.userId) {
                this.close();
                return;
            }

            const formData = new FormData();
            formData.append('action', 'vc_onboarding_dismiss');
            formData.append('nonce', this.nonce);

            const ajaxUrl = (typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof vemcomerOnboarding !== 'undefined' && vemcomerOnboarding.ajaxurl ? vemcomerOnboarding.ajaxurl : '/wp-admin/admin-ajax.php'));

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.close();
                } else {
                    this.showError(data.data?.message || 'Erro ao dispensar onboarding');
                }
            })
            .catch(error => {
                console.error('Erro ao dispensar onboarding:', error);
                this.close(); // Fecha mesmo em caso de erro
            });
        },

        close: function() {
            this.preventBodyScroll(false);
            
            if (this.onboarding) {
                this.onboarding.classList.add('vc-onboarding--hidden');
                
                // Esconde após animação
                setTimeout(() => {
                    if (this.onboarding) {
                        this.onboarding.style.display = 'none';
                    }
                }, 300);
            }
        },

        setLoading: function(loading) {
            if (loading) {
                this.modal.classList.add('vc-onboarding__modal--loading');
            } else {
                this.modal.classList.remove('vc-onboarding__modal--loading');
            }
        },

        showError: function(message) {
            // Mostra mensagem de erro (pode ser um toast ou alert)
            const errorDiv = document.createElement('div');
            errorDiv.className = 'vc-onboarding__error';
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #fee2e2;
                color: #991b1b;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000000;
                animation: slideInRight 0.3s ease-out;
            `;
            errorDiv.textContent = message;

            document.body.appendChild(errorDiv);

            // Remove após 5 segundos
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.style.animation = 'slideOutRight 0.3s ease-out';
                    setTimeout(() => {
                        if (errorDiv.parentNode) {
                            errorDiv.parentNode.removeChild(errorDiv);
                        }
                    }, 300);
                }
            }, 5000);
        }
    };

    // Inicializa quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => Onboarding.init());
    } else {
        Onboarding.init();
    }

    // Adiciona animações CSS dinâmicas
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        .vc-onboarding--hidden {
            opacity: 0;
            pointer-events: none;
        }
        .vc-onboarding--hidden .vc-onboarding__modal {
            transform: translate(-50%, -40%);
            opacity: 0;
        }
    `;
    document.head.appendChild(style);

})();

