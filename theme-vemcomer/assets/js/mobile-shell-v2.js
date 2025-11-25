// ============ CONFIGURAÇÃO ============
const API_BASE = '/wp-json/vemcomer/v1';

// ============ DADOS DOS STORIES ============
const storiesData = [
    {
        id: 1,
        restaurant: {
            name: 'Pizzaria Bella',
            avatar: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=200'
        },
        stories: [
            {
                id: 1,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800',
                timestamp: '2h',
                duration: 5000
            },
            {
                id: 2,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=800',
                timestamp: '2h',
                duration: 5000
            }
        ],
        viewed: false,
        hasNew: true
    },
    {
        id: 2,
        restaurant: {
            name: 'Sushi House',
            avatar: 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=200'
        },
        stories: [
            {
                id: 3,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1580822184713-fc5400e7fe10?w=800',
                timestamp: '4h',
                duration: 5000
            }
        ],
        viewed: false,
        hasNew: true
    },
    {
        id: 3,
        restaurant: {
            name: 'Churrascaria Premium',
            avatar: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=200'
        },
        stories: [
            {
                id: 4,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1558030006-450675393462?w=800',
                timestamp: '6h',
                duration: 5000
            },
            {
                id: 5,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1529692236671-f1f6cf9683ba?w=800',
                timestamp: '6h',
                duration: 5000
            },
            {
                id: 6,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=800',
                timestamp: '7h',
                duration: 5000
            }
        ],
        viewed: true,
        hasNew: false
    },
    {
        id: 4,
        restaurant: {
            name: 'Trattoria Italiana',
            avatar: 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=200'
        },
        stories: [
            {
                id: 7,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=800',
                timestamp: '8h',
                duration: 5000
            }
        ],
        viewed: false,
        hasNew: false
    },
    {
        id: 5,
        restaurant: {
            name: 'Açaí da Vila',
            avatar: 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=200'
        },
        stories: [
            {
                id: 8,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1488900128323-21503983a07e?w=800',
                timestamp: '10h',
                duration: 5000
            },
            {
                id: 9,
                type: 'image',
                url: 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800',
                timestamp: '11h',
                duration: 5000
            }
        ],
        viewed: true,
        hasNew: false
    }
];

// ============ OUTROS DADOS ============
const dishesData = [
    {
        id: 1,
        name: 'Feijoada Completa',
        restaurant: 'Churrascaria do Gaúcho',
        description: 'Feijoada tradicional com todos os acompanhamentos',
        price: 'R$ 38,90',
        image: 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?w=400',
        badge: 'ESPECIAL'
    },
    {
        id: 2,
        name: 'Sushi Premium Mix',
        restaurant: 'Sushi House',
        description: '30 peças variadas de sushi e sashimi premium',
        price: 'R$ 89,90',
        image: 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=400',
        badge: 'DESTAQUE'
    },
    {
        id: 3,
        name: 'Pizza Margherita Especial',
        restaurant: 'Pizzaria Bella Napoli',
        description: 'Molho de tomate, mussarela de búfala e manjericão',
        price: 'R$ 54,90',
        image: 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=400',
        badge: '20% OFF'
    }
];

const eventsData = [
    {
        id: 1,
        title: 'Jazz ao Vivo',
        restaurant: 'Trattoria Italiana',
        date: { day: '24', month: 'NOV' },
        time: '20:00',
        price: 'Entrada Grátis',
        image: 'https://images.unsplash.com/photo-1511735111819-9a3f7709049c?w=400',
        isLive: false
    },
    {
        id: 2,
        title: 'Samba & Churrasco',
        restaurant: 'Churrascaria do Gaúcho',
        date: { day: '24', month: 'NOV' },
        time: '19:30',
        price: 'A partir de R$ 45',
        image: 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=400',
        isLive: true
    },
    {
        id: 3,
        title: 'Noite de MPB',
        restaurant: 'Restaurante do Parque',
        date: { day: '25', month: 'NOV' },
        time: '21:00',
        price: 'R$ 30 couvert',
        image: 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?w=400',
        isLive: false
    }
];

const featuredData = [
    {
        id: 1,
        name: 'Churrascaria Premium',
        rating: 4.9,
        tags: ['Brasileira', 'Carnes'],
        deliveryTime: '35-45 min',
        deliveryFee: 'R$ 7,00',
        image: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400',
        hasReservation: true
    },
    {
        id: 2,
        name: 'Sushi House Premium',
        rating: 4.8,
        tags: ['Japonesa', 'Sushi'],
        deliveryTime: '40-50 min',
        deliveryFee: 'Grátis',
        image: 'https://images.unsplash.com/photo-1579584425555-c3ce17fd4351?w=400',
        hasReservation: true
    },
    {
        id: 3,
        name: 'Trattoria Nonna Rosa',
        rating: 4.9,
        tags: ['Italiana', 'Massas'],
        deliveryTime: '30-40 min',
        deliveryFee: 'R$ 5,00',
        image: 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=400',
        hasReservation: true
    }
];

const restaurantsData = [
    {
        id: 4,
        name: 'Pizzaria Bella Napoli',
        rating: 4.9,
        deliveryTime: '25-35 min',
        deliveryFee: 'Grátis',
        image: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400',
        isOpen: true
    },
    {
        id: 5,
        name: 'Hamburgeria Artesanal',
        rating: 4.7,
        deliveryTime: '20-30 min',
        deliveryFee: 'R$ 3,00',
        image: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400',
        isOpen: true
    },
    {
        id: 6,
        name: 'Açaí da Vila',
        rating: 4.6,
        deliveryTime: '15-25 min',
        deliveryFee: 'R$ 2,50',
        image: 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400',
        isOpen: false
    }
];

// ============ RENDER STORIES ============
function renderStories() {
    const container = document.getElementById('storiesScroll');
    if (!container) return;
    
    container.innerHTML = storiesData.map(story => `
        <div class="story-item" onclick="openStory(${story.id})">
            <div class="story-avatar-wrapper">
                <div class="story-ring ${story.viewed ? 'viewed' : ''}">
                    <div class="story-avatar-container">
                        <img src="${story.restaurant.avatar}" alt="${story.restaurant.name}" class="story-avatar">
                    </div>
                </div>
                ${story.hasNew ? '<div class="story-new-badge">+</div>' : ''}
            </div>
            <div class="story-name">${story.restaurant.name}</div>
        </div>
    `).join('');
}

// ============ STORY VIEWER ============
let currentStoryGroup = null;
let currentStoryIndex = 0;
let storyTimer = null;
let progressInterval = null;

function openStory(groupId) {
    const storyGroup = storiesData.find(s => s.id === groupId);
    if (!storyGroup) return;

    currentStoryGroup = storyGroup;
    currentStoryIndex = 0;
    
    const viewer = document.getElementById('storyViewer');
    if (!viewer) return;
    
    viewer.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    renderStoryProgressBars(storyGroup.stories.length);
    showStory(0);
    
    // Marcar como visto
    storyGroup.viewed = true;
    renderStories();
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
    const headerAvatar = document.getElementById('storyHeaderAvatar');
    const headerName = document.getElementById('storyHeaderName');
    const headerTime = document.getElementById('storyHeaderTime');
    if (headerAvatar) headerAvatar.src = currentStoryGroup.restaurant.avatar;
    if (headerName) headerName.textContent = currentStoryGroup.restaurant.name;
    if (headerTime) headerTime.textContent = `há ${story.timestamp}`;

    // Update media
    const media = document.getElementById('storyMedia');
    if (media) media.src = story.url;

    // Reset all progress bars
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

    // Start progress animation
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
        // Go to next restaurant's stories
        const currentGroupIndex = storiesData.findIndex(s => s.id === currentStoryGroup.id);
        if (currentGroupIndex < storiesData.length - 1) {
            const nextGroup = storiesData[currentGroupIndex + 1];
            openStory(nextGroup.id);
        } else {
            closeStoryViewer();
        }
    }
}

function previousStory() {
    if (currentStoryIndex > 0) {
        showStory(currentStoryIndex - 1);
    } else {
        // Go to previous restaurant's stories
        const currentGroupIndex = storiesData.findIndex(s => s.id === currentStoryGroup.id);
        if (currentGroupIndex > 0) {
            const prevGroup = storiesData[currentGroupIndex - 1];
            currentStoryGroup = prevGroup;
            showStory(prevGroup.stories.length - 1);
        }
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

// Story viewer event listeners
document.addEventListener('DOMContentLoaded', function() {
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
        content.addEventListener('touchstart', (e) => {
            clearTimeout(storyTimer);
            clearInterval(progressInterval);
        });
        content.addEventListener('touchend', (e) => {
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
});

// ============ BANNER CAROUSEL ============
let currentBannerIndex = 0;
let bannerCarousel = null;
let bannerDots = null;

function initBannerCarousel() {
    bannerCarousel = document.getElementById('bannerCarousel');
    bannerDots = document.querySelectorAll('.banner-dot');
    
    if (!bannerCarousel || !bannerDots.length) return;

    function updateBannerCarousel(index) {
        currentBannerIndex = index;
        const offset = -index * 100;
        bannerCarousel.style.transform = `translateX(${offset}%)`;
        
        bannerDots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    bannerDots.forEach(dot => {
        dot.addEventListener('click', () => {
            updateBannerCarousel(parseInt(dot.dataset.index));
        });
    });

    setInterval(() => {
        const nextIndex = (currentBannerIndex + 1) % bannerDots.length;
        updateBannerCarousel(nextIndex);
    }, 5000);

    // Swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    bannerCarousel.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
    });

    bannerCarousel.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    function handleSwipe() {
        if (touchStartX - touchEndX > 50) {
            const nextIndex = Math.min(currentBannerIndex + 1, bannerDots.length - 1);
            updateBannerCarousel(nextIndex);
        }
        if (touchEndX - touchStartX > 50) {
            const prevIndex = Math.max(currentBannerIndex - 1, 0);
            updateBannerCarousel(prevIndex);
        }
    }
}

// ============ RENDER OTHER SECTIONS ============
function renderDishes() {
    const container = document.getElementById('dishesScroll');
    if (!container) return;
    
    container.innerHTML = dishesData.map(dish => `
        <div class="dish-card" onclick="openDish(${dish.id})">
            <div class="dish-image-wrapper">
                <img src="${dish.image}" alt="${dish.name}" class="dish-image" loading="lazy">
                <div class="dish-badge">${dish.badge}</div>
                <div class="dish-price-badge">${dish.price}</div>
            </div>
            <div class="dish-content">
                <div class="dish-restaurant">${dish.restaurant}</div>
                <div class="dish-name">${dish.name}</div>
                <div class="dish-description">${dish.description}</div>
            </div>
        </div>
    `).join('');
}

function renderEvents() {
    const container = document.getElementById('eventsScroll');
    if (!container) return;
    
    container.innerHTML = eventsData.map(event => `
        <div class="event-card" onclick="openEvent(${event.id})">
            <div class="event-image-wrapper">
                <img src="${event.image}" alt="${event.title}" class="event-image" loading="lazy">
                <div class="event-date-badge">
                    <div class="event-day">${event.date.day}</div>
                    <div class="event-month">${event.date.month}</div>
                </div>
                ${event.isLive ? '<div class="event-live-badge">● AO VIVO</div>' : ''}
            </div>
            <div class="event-content">
                <div class="event-restaurant">${event.restaurant}</div>
                <div class="event-title">${event.title}</div>
                <div class="event-info">
                    <div class="event-info-item">
                        <svg class="event-info-icon" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                        ${event.time}
                    </div>
                    <div class="event-info-item">
                        <svg class="event-info-icon" viewBox="0 0 24 24">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                        ${event.price}
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function renderFeatured() {
    const container = document.getElementById('featuredGrid');
    if (!container) return;
    
    container.innerHTML = featuredData.map(restaurant => `
        <div class="featured-card">
            <div class="featured-image-wrapper">
                <img src="${restaurant.image}" alt="${restaurant.name}" class="featured-image" loading="lazy">
                <div class="featured-badge">⭐ DESTAQUE</div>
            </div>
            <div class="featured-content">
                <div class="featured-header">
                    <div class="featured-name">${restaurant.name}</div>
                    <div class="featured-rating">
                        <svg class="star-icon" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                        ${restaurant.rating}
                    </div>
                </div>
                <div class="featured-tags">
                    ${restaurant.tags.map(tag => `<span class="featured-tag">${tag}</span>`).join('')}
                </div>
                <div class="featured-info">
                    <span>${restaurant.deliveryTime}</span>
                    <span class="info-dot"></span>
                    <span>${restaurant.deliveryFee}</span>
                </div>
                ${restaurant.hasReservation ? `
                    <button class="featured-reserve-btn" onclick="openReservation(${restaurant.id}, event)">
                        <svg class="reserve-icon" viewBox="0 0 24 24">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/>
                        </svg>
                        Reservar Mesa
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

function renderRestaurants() {
    const container = document.getElementById('restaurantsGrid');
    if (!container) return;
    
    container.innerHTML = restaurantsData.map(restaurant => `
        <div class="restaurant-card" onclick="openRestaurant(${restaurant.id})">
            <div class="card-image-wrapper">
                <img src="${restaurant.image}" alt="${restaurant.name}" class="card-image" loading="lazy">
                <div class="card-badges">
                    <div class="card-badge ${restaurant.isOpen ? 'badge-open' : 'badge-closed'}">
                        ${restaurant.isOpen ? '• Aberto' : 'Fechado'}
                    </div>
                </div>
                <button class="favorite-btn" onclick="toggleFavorite(event, ${restaurant.id})">
                    <svg class="favorite-icon" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </button>
            </div>
            <div class="card-content">
                <div class="card-header">
                    <div class="card-title">${restaurant.name}</div>
                    <div class="card-rating">
                        <svg class="star-icon" viewBox="0 0 24 24">
                            <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                        </svg>
                        ${restaurant.rating}
                    </div>
                </div>
                <div class="card-info">
                    <span>${restaurant.deliveryTime}</span>
                    <span class="info-dot"></span>
                    <span>${restaurant.deliveryFee}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// ============ EVENT HANDLERS ============
function openDish(id) {
    window.location.href = `/prato/${id}`;
}

function openEvent(id) {
    window.location.href = `/evento/${id}`;
}

function openRestaurant(id) {
    window.location.href = `/restaurante/${id}`;
}

function openReservation(id, event) {
    event.stopPropagation();
    window.location.href = `/reservar/${id}`;
}

function toggleFavorite(event, id) {
    event.stopPropagation();
}

// ============ LOCATION MANAGEMENT ============
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days) {
    const expires = new Date();
    expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
}

function updateLocationText() {
    const locationTextEl = document.getElementById('locationText');
    if (!locationTextEl) return;
    
    // Prioridade: bairro > cidade > endereço completo > padrão
    const neighborhood = localStorage.getItem('vc_user_neighborhood') || getCookie('vc_user_neighborhood');
    const city = localStorage.getItem('vc_user_city') || getCookie('vc_user_city');
    const savedLocation = localStorage.getItem('vc_user_location');
    
    let displayText = 'Selecione um endereço';
    
    if (neighborhood) {
        displayText = neighborhood;
    } else if (city) {
        displayText = city;
    } else if (savedLocation) {
        try {
            const locData = JSON.parse(savedLocation);
            if (locData.address) {
                displayText = locData.address;
            }
        } catch (e) {
            console.error('Erro ao parsear localização:', e);
        }
    }
    
    locationTextEl.textContent = displayText;
}

async function reverseGeocode(lat, lng) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${encodeURIComponent(lat)}&lon=${encodeURIComponent(lng)}&zoom=18&addressdetails=1`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'User-Agent': 'Pedevem Marketplace'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        const addr = data.address || {};
        
        return {
            neighborhood: addr.suburb || addr.neighbourhood || addr.quarter || '',
            city: addr.city || addr.town || addr.village || addr.municipality || '',
            state: addr.state || addr.region || '',
            fullAddress: data.display_name || ''
        };
    } catch (error) {
        console.error('Erro no reverse geocoding:', error);
        throw error;
    }
}

function showLocationModal() {
    const modal = document.getElementById('locationModal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function hideLocationModal() {
    const modal = document.getElementById('locationModal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

function showLocationError(message) {
    const errorEl = document.getElementById('locationModalError');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.style.display = 'block';
    }
}

function hideLocationError() {
    const errorEl = document.getElementById('locationModalError');
    if (errorEl) {
        errorEl.style.display = 'none';
    }
}

function setLoadingState(isLoading) {
    const btn = document.getElementById('locationModalBtn');
    const btnText = btn?.querySelector('.btn-text');
    const btnLoader = btn?.querySelector('.btn-loader');
    
    if (!btn) return;
    
    if (isLoading) {
        btn.disabled = true;
        if (btnText) btnText.style.display = 'none';
        if (btnLoader) btnLoader.style.display = 'flex';
    } else {
        btn.disabled = false;
        if (btnText) btnText.style.display = 'block';
        if (btnLoader) btnLoader.style.display = 'none';
    }
}

async function requestLocationPermission() {
    if (!navigator.geolocation) {
        showLocationError('Geolocalização não é suportada pelo seu navegador.');
        setLoadingState(false);
        return;
    }
    
    setLoadingState(true);
    hideLocationError();
    
    try {
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 0
                }
            );
        });
        
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        // Fazer reverse geocoding para obter bairro
        const address = await reverseGeocode(lat, lng);
        
        // Salvar localização
        localStorage.setItem('vc_user_location', JSON.stringify({ lat, lng }));
        
        if (address.neighborhood) {
            localStorage.setItem('vc_user_neighborhood', address.neighborhood);
            setCookie('vc_user_neighborhood', address.neighborhood, 30);
        }
        
        if (address.city) {
            localStorage.setItem('vc_user_city', address.city);
            setCookie('vc_user_city', address.city, 30);
        }
        
        // Atualizar texto do locationText
        updateLocationText();
        
        // Fechar modal
        hideLocationModal();
        
        // Disparar evento para outros scripts
        const event = new CustomEvent('vc_location_updated', {
            detail: { lat, lng, address }
        });
        document.dispatchEvent(event);
        
    } catch (error) {
        console.error('Erro ao obter localização:', error);
        setLoadingState(false);
        
        let errorMsg = 'Não foi possível obter sua localização.';
        if (error.code === error.PERMISSION_DENIED || error.code === 1) {
            errorMsg = 'Permissão de localização negada. Por favor, permita o acesso nas configurações do navegador e tente novamente.';
        } else if (error.code === error.POSITION_UNAVAILABLE || error.code === 2) {
            errorMsg = 'Localização indisponível. Verifique se o GPS está ativado.';
        } else if (error.code === error.TIMEOUT || error.code === 3) {
            errorMsg = 'Tempo de espera esgotado. Tente novamente.';
        } else if (error.message) {
            errorMsg = error.message;
        }
        
        showLocationError(errorMsg);
    }
}

function checkLocationAndShowModal() {
    // Verificar se já tem localização salva
    const neighborhood = localStorage.getItem('vc_user_neighborhood') || getCookie('vc_user_neighborhood');
    const city = localStorage.getItem('vc_user_city') || getCookie('vc_user_city');
    const savedLocation = localStorage.getItem('vc_user_location');
    
    // Se não tiver nenhuma localização, mostrar modal obrigatório
    if (!neighborhood && !city && !savedLocation) {
        showLocationModal();
    } else {
        // Atualizar texto do locationText
        updateLocationText();
    }
}

// ============ INITIALIZE ============
document.addEventListener('DOMContentLoaded', function() {
    // Verificar localização e mostrar modal se necessário
    checkLocationAndShowModal();
    
    // Event listener para o botão do modal
    const locationModalBtn = document.getElementById('locationModalBtn');
    if (locationModalBtn) {
        locationModalBtn.addEventListener('click', requestLocationPermission);
    }
    
    // Renderizar conteúdo
    renderStories();
    renderDishes();
    renderEvents();
    renderFeatured();
    renderRestaurants();
    initBannerCarousel();
});

