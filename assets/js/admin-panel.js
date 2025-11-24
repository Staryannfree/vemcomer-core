/* admin-panel.js - Lógica do Painel do Restaurante */

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('vc-pricing-modal');
    if (!modal) return;

    const openBtns = document.querySelectorAll('a[href="#upgrade-modal"], .vc-btn-upgrade-sm, .vc-btn-upgrade-lg, .vc-limit-alert a');
    const closeBtn = modal.querySelector('.vc-modal-close');
    const overlay = modal.querySelector('.vc-modal-overlay');
    
    // Função para abrir modal
    function openModal(e) {
        if (e) e.preventDefault();
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden'; // Bloqueia scroll do body
    }

    // Função para fechar modal
    function closeModal(e) {
        if (e) e.preventDefault();
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    // Listeners de abertura
    openBtns.forEach(btn => {
        btn.addEventListener('click', openModal);
    });

    // Listeners de fechamento
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);

    // Fechar com ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    // Botões de Assinar (WhatsApp)
    const subscribeBtns = modal.querySelectorAll('.vc-pricing-btn');
    const supportPhone = '5511999999999'; // Substituir pelo número real
    
    // Tenta obter nome do restaurante do DOM
    const restaurantNameEl = document.querySelector('.vc-panel__title');
    const restaurantName = restaurantNameEl ? restaurantNameEl.textContent.trim().replace('Plano Vitrine', '') : 'Meu Restaurante';

    subscribeBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const plan = this.dataset.plan;
            const text = `Olá! Gostaria de assinar o plano *${plan}* para o restaurante *${restaurantName}*. Como procedemos?`;
            const url = `https://wa.me/${supportPhone}?text=${encodeURIComponent(text)}`;
            window.open(url, '_blank');
        });
    });
});

