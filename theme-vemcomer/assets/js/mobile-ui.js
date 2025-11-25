/**
 * Mobile UI JavaScript - Funcionalidades do novo design
 * Stories, Carousel, Quick Actions, etc.
 * @package VemComerCore
 */

(function() {
    'use strict';

    const API_BASE = '/wp-json/vemcomer/v1';

    // ============ BANNER CAROUSEL ============
    function initBannerCarousel() {
        const carousel = document.getElementById('bannerCarousel');
        const dots = document.querySelectorAll('.banner-dot');
        
        if (!carousel || !dots.length) return;

        let currentIndex = 0;
        let autoPlayInterval = null;

        function updateCarousel(index) {
            currentIndex = index;
            const offset = -index * 100;
            carousel.style.transform = `translateX(${offset}%)`;
            
            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                updateCarousel(parseInt(dot.dataset.index));
                resetAutoPlay();
            });
        });

        function nextBanner() {
            const nextIndex = (currentIndex + 1) % dots.length;
            updateCarousel(nextIndex);
        }

        function resetAutoPlay() {
            clearInterval(autoPlayInterval);
            autoPlayInterval = setInterval(nextBanner, 5000);
        }

        // Swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        carousel.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        carousel.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            if (touchStartX - touchEndX > 50) {
                const nextIndex = Math.min(currentIndex + 1, dots.length - 1);
                updateCarousel(nextIndex);
                resetAutoPlay();
            }
            if (touchEndX - touchStartX > 50) {
                const prevIndex = Math.max(currentIndex - 1, 0);
                updateCarousel(prevIndex);
                resetAutoPlay();
            }
        }

        // Auto-play
        resetAutoPlay();
    }

    // ============ STORIES ============
    let currentStoryGroup = null;
    let currentStoryIndex = 0;
    let storyTimer = null;
    let progressInterval = null;

    function initStories() {
        // Render stories from API or data
        renderStories();
        
        // Story viewer event listeners
        const closeBtn = document.getElementById('storyCloseBtn');
        const tapLeft = document.getElementById('storyTapLeft');
        const tapRight = document.getElementById('storyTapRight');
        const content = document.getElementById('storyContent');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeStoryViewer);
        }
        if (tapLeft) {
            tapLeft.addEventListener('click', previousStory);
        }
        if (tapRight) {
            tapRight.addEventListener('click', nextStory);
        }

        // Pause on hold
        if (content) {
            content.addEventListener('touchstart', () => {
                clearTimeout(storyTimer);
                clearInterval(progressInterval);
            });

            content.addEventListener('touchend', () => {
                if (currentStoryGroup) {
                    const story = currentStoryGroup.stories[currentStoryIndex];
                    const progress = document.getElementById(`storyProgress${currentStoryIndex}`);
                    if (progress) {
                        const currentWidth = parseFloat(progress.style.width);
                        const remaining = story.duration * (1 - currentWidth / 100);
                        startStoryProgress(currentStoryIndex, remaining);
                    }
                }
            });
        }
    }

    function renderStories() {
        const container = document.getElementById('storiesScroll');
        if (!container) return;

        // TODO: Carregar stories da API
        // Por enquanto, placeholder vazio ou dados mock
        container.innerHTML = ''; // Será preenchido via API
    }

    function openStory(groupId) {
        // TODO: Carregar story group da API
        // Por enquanto, placeholder
        console.log('Open story:', groupId);
    }

    function renderStoryProgressBars(count) {
        const container = document.getElementById('storyProgressBars');
        if (!container) return;
        
        container.innerHTML = Array(count).fill(0).map((_, i) => `
            <div class="story-progress-bar">
                <div class="story-progress-fill" id="storyProgress${i}"></div>
            </div>
        `).join('');
    }

    function showStory(index) {
        if (!currentStoryGroup || index >= currentStoryGroup.stories.length) {
            closeStoryViewer();
            return;
        }

        const story = currentStoryGroup.stories[index];
        currentStoryIndex = index;

        // Update header
        const avatar = document.getElementById('storyHeaderAvatar');
        const name = document.getElementById('storyHeaderName');
        const time = document.getElementById('storyHeaderTime');
        
        if (avatar) avatar.src = currentStoryGroup.restaurant.avatar;
        if (name) name.textContent = currentStoryGroup.restaurant.name;
        if (time) time.textContent = `há ${story.timestamp}`;

        // Update media
        const media = document.getElementById('storyMedia');
        if (media) media.src = story.url;

        // Reset progress bars
        for (let i = 0; i < currentStoryGroup.stories.length; i++) {
            const progress = document.getElementById(`storyProgress${i}`);
            if (progress) {
                if (i < index) {
                    progress.style.width = '100%';
                } else if (i === index) {
                    progress.style.width = '0%';
                } else {
                    progress.style.width = '0%';
                }
            }
        }

        startStoryProgress(index, story.duration);
    }

    function startStoryProgress(index, duration) {
        clearInterval(progressInterval);
        clearTimeout(storyTimer);

        const progress = document.getElementById(`storyProgress${index}`);
        if (!progress) return;

        let elapsed = 0;
        const interval = 50;

        progressInterval = setInterval(() => {
            elapsed += interval;
            const percentage = (elapsed / duration) * 100;
            progress.style.width = `${Math.min(percentage, 100)}%`;
        }, interval);

        storyTimer = setTimeout(() => {
            nextStory();
        }, duration);
    }

    function nextStory() {
        if (currentStoryIndex < currentStoryGroup.stories.length - 1) {
            showStory(currentStoryIndex + 1);
        } else {
            closeStoryViewer();
        }
    }

    function previousStory() {
        if (currentStoryIndex > 0) {
            showStory(currentStoryIndex - 1);
        }
    }

    function closeStoryViewer() {
        clearInterval(progressInterval);
        clearTimeout(storyTimer);
        
        const viewer = document.getElementById('storyViewer');
        if (viewer) {
            viewer.classList.remove('active');
        }
        document.body.style.overflow = '';
        
        currentStoryGroup = null;
        currentStoryIndex = 0;
    }

    // ============ NOTIFICATIONS ============
    function initNotifications() {
        const btn = document.getElementById('notificationBtn');
        const badge = document.getElementById('notificationBadge');
        
        if (!btn) return;

        // TODO: Carregar contagem de notificações da API
        function updateNotificationBadge() {
            fetch(`${API_BASE}/notifications/unread-count`)
                .then(res => res.json())
                .then(data => {
                    if (badge && data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.style.display = 'flex';
                    } else if (badge) {
                        badge.style.display = 'none';
                    }
                })
                .catch(() => {
                    // Silently fail
                });
        }

        btn.addEventListener('click', () => {
            // TODO: Abrir modal de notificações
            window.location.href = '/notificacoes/';
        });

        // Atualizar badge a cada 30 segundos
        updateNotificationBadge();
        setInterval(updateNotificationBadge, 30000);
    }

    // ============ CART BUTTON ============
    function initCartButton() {
        const cartBtn = document.getElementById('cartBtn');
        const cartBadge = document.getElementById('cartBadge');
        
        if (!cartBtn) return;

        function updateCart() {
            // Verificar se há itens no carrinho (localStorage ou API)
            const cart = JSON.parse(localStorage.getItem('vc_cart') || '[]');
            const itemCount = cart.reduce((sum, item) => sum + (item.quantity || 0), 0);
            
            if (itemCount > 0) {
                cartBtn.classList.add('show');
                if (cartBadge) {
                    cartBadge.textContent = itemCount > 99 ? '99+' : itemCount;
                }
            } else {
                cartBtn.classList.remove('show');
            }
        }

        cartBtn.addEventListener('click', () => {
            window.location.href = '/carrinho/';
        });

        // Atualizar quando o carrinho mudar
        window.addEventListener('storage', (e) => {
            if (e.key === 'vc_cart') {
                updateCart();
            }
        });

        // Atualizar ao carregar
        updateCart();
    }

    // ============ INITIALIZE ============
    document.addEventListener('DOMContentLoaded', () => {
        if (window.innerWidth <= 768) {
            initBannerCarousel();
            initStories();
            initNotifications();
            initCartButton();
        }
    });

})();

