// ============ CONFIGURAÇÃO ============
const API_BASE = '/wp-json/vemcomer/v1';
const TEMPLATE_PATH = '/wp-content/themes/theme-vemcomer/templates/marketplace/';

// ============ PLACEHOLDERS INTELIGENTES POR CATEGORIA ============
const PLACEHOLDERS = {
    // Entidades Genéricas
    default: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400&h=300&fit=crop', // Salada colorida
    restaurant_logo: null, // Vamos gerar via CSS (Avatar com iniciais)
    
    // Categorias Específicas (Mapeamento Inteligente)
    lanches: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop',
    burger: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop',
    hamburguer: 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop',
    pizza: 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400&h=300&fit=crop',
    japonesa: 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&h=300&fit=crop',
    sushi: 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?w=400&h=300&fit=crop',
    brasileira: 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=400&h=300&fit=crop', // Feijoada style
    acai: 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&h=300&fit=crop',
    açaí: 'https://images.unsplash.com/photo-1590301157890-4810ed352733?w=400&h=300&fit=crop',
    bebidas: 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=400&h=300&fit=crop',
    drinks: 'https://images.unsplash.com/photo-1551024709-8f23befc6f87?w=400&h=300&fit=crop',
    sobremesa: 'https://images.unsplash.com/photo-1563729768-74361497816e?w=400&h=300&fit=crop',
    doce: 'https://images.unsplash.com/photo-1563729768-74361497816e?w=400&h=300&fit=crop',
    massas: 'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?w=400&h=300&fit=crop',
    italiana: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=400&h=300&fit=crop',
    chinesa: 'https://images.unsplash.com/photo-1563379091339-03246963d29a?w=400&h=300&fit=crop',
    mexicana: 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=400&h=300&fit=crop',
    cafe: 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&h=300&fit=crop',
    café: 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=400&h=300&fit=crop',
    
    // Helper de validação
    isValid: (url) => url && typeof url === 'string' && url.length > 10 && !url.includes('placeholder')
};

/**
 * Normaliza string removendo acentos e convertendo para lowercase
 */
function normalizeString(str) {
    if (!str) return '';
    return str
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remove acentos
        .trim();
}

/**
 * Função Smart Fallback - Analisa texto para decidir qual placeholder usar
 * @param {string|string[]} contextText - Texto do contexto (categoria, nome do prato, tags)
 * @returns {string} URL do placeholder
 */
function getSmartImage(contextText) {
    if (!contextText) return PLACEHOLDERS.default;
    
    // Se for array, juntar tudo em uma string
    const text = Array.isArray(contextText) 
        ? contextText.join(' ') 
        : String(contextText);
    
    // Normaliza texto: "Açaí & Sorvetes" -> "acai  sorvetes"
    const term = normalizeString(text);
    
    // Varre as chaves do objeto PLACEHOLDERS
    for (const key in PLACEHOLDERS) {
        if (key === 'default' || key === 'restaurant_logo' || key === 'isValid') continue;
        if (term.includes(key)) {
            return PLACEHOLDERS[key];
        }
    }
    
    // Palavras chave adicionais (match parcial mais inteligente)
    if (term.includes('hamburguer') || term.includes('sanduiche') || term.includes('x-burger')) {
        return PLACEHOLDERS.lanches;
    }
    if (term.includes('refri') || term.includes('suco') || term.includes('agua') || term.includes('cerveja') || term.includes('cola')) {
        return PLACEHOLDERS.bebidas;
    }
    if (term.includes('temaki') || term.includes('hot roll') || term.includes('sashimi')) {
        return PLACEHOLDERS.japonesa;
    }
    if (term.includes('almoco') || term.includes('jantar') || term.includes('prato feito') || term.includes('feijoada')) {
        return PLACEHOLDERS.brasileira;
    }
    if (term.includes('bolo') || term.includes('torta') || term.includes('sorvete')) {
        return PLACEHOLDERS.sobremesa;
    }
    
    return PLACEHOLDERS.default;
}

/**
 * Retorna o placeholder apropriado baseado na categoria (compatibilidade)
 * @param {string|string[]} category - Categoria ou array de categorias
 * @returns {string} URL do placeholder
 */
function getFallbackImage(category) {
    return getSmartImage(category);
}

/**
 * Gera HTML de avatar com inicial e cor para logos faltantes
 * @param {string} name - Nome do restaurante
 * @returns {string} HTML do avatar
 */
function getLogoFallback(name) {
    const initial = name ? name.charAt(0).toUpperCase() : '?';
    const colors = ['#F44336', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#2196F3', '#009688', '#4CAF50', '#FF9800', '#FF5722'];
    
    // Hash simples para escolher a cor baseada no nome (consistência visual)
    let hash = 0;
    const nameStr = String(name || '');
    for (let i = 0; i < nameStr.length; i++) {
        hash = nameStr.charCodeAt(i) + ((hash << 5) - hash);
    }
    const color = colors[Math.abs(hash) % colors.length];
    
    return `
        <div class="logo-fallback" style="background-color: ${color}; color: white; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2em; border-radius: inherit;">
            ${initial}
        </div>
    `;
}

// Mantido para compatibilidade
const PLACEHOLDER_IMAGE = PLACEHOLDERS.default;

/**
 * Função Helper Robusta para verificar URL de imagem
 * @param {string} url - URL da imagem
 * @returns {boolean} true se URL é válida
 */
const isValidImage = (url) => {
    return url && typeof url === 'string' && url.length > 10 && !url.includes('null') && !url.includes('undefined') && !url.includes('placeholder');
};

// ============ MAPEAMENTO DE DADOS DA API ============
function mapApiBannerToBanner(apiBanner) {
    // Se o banner tem restaurant_id/slug, criar link para o restaurante
    let link = apiBanner.link || null;

    if (!link && (apiBanner.restaurant_id || apiBanner.restaurant_slug)) {
        // Usa SEMPRE o helper centralizado
        link = getRestaurantProfileUrl(
            apiBanner.restaurant_slug,
            apiBanner.restaurant_id
        );
    }

    const image = apiBanner.image && apiBanner.image.trim() !== '' 
        ? apiBanner.image 
        : PLACEHOLDERS.default;

    return {
        id: apiBanner.id,
        title: apiBanner.title || '',
        subtitle: '',
        image: image,
        link: link,
        restaurantId: apiBanner.restaurant_id || null
    };
}

async function getRestaurantImage(restaurantId, cuisines = [], restaurantName = '') {
    try {
        // Tentar buscar imagem via WordPress REST API padrão
        const response = await fetch(`/wp-json/wp/v2/vc_restaurant/${restaurantId}?_embed=true`);
        if (response.ok) {
            const data = await response.json();
            if (data._embedded && data._embedded['wp:featuredmedia'] && data._embedded['wp:featuredmedia'][0]) {
                const imageUrl = data._embedded['wp:featuredmedia'][0].source_url;
                if (imageUrl && PLACEHOLDERS.isValid(imageUrl)) {
                    return imageUrl;
                }
            }
        }
    } catch (error) {
        console.error('Erro ao buscar imagem do restaurante:', error);
    }
    // Se não encontrou imagem, usar smart fallback baseado na categoria ou nome
    const contextText = cuisines.length > 0 ? cuisines.join(' ') : restaurantName;
    return getSmartImage(contextText);
}

function buildSlugFromName(name) {
    if (!name) return null;
    return normalizeString(name)
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function extractSlugFromUrl(url) {
    if (!url) return null;

    // Captura slug em URLs /restaurant/{slug}/
    const match = url.match(/\/restaurant\/([^/?#]+)/i);
    if (match && match[1]) {
        return match[1];
    }

    return null;
}

function getRestaurantProfileUrl(slug, id, name = '') {
    const slugOrId = slug || id || buildSlugFromName(name);
    if (!slugOrId) return null;
    return `/restaurant/${slugOrId}/`;
}

function normalizeRestaurantUrl(url, slug, id, name = '') {
    // Evitar links estáticos para o template HTML do marketplace
    const isStaticTemplate = typeof url === 'string' && url.includes('templates/marketplace/perfil-restaurante');
    const slugFromUrl = extractSlugFromUrl(url);
    const derivedSlug = slug || slugFromUrl || buildSlugFromName(name);
    const canonicalUrl = getRestaurantProfileUrl(derivedSlug, id, name);

    if (!url || isStaticTemplate) {
        return canonicalUrl || '#';
    }

    // Se a URL já aponta para /restaurant/{slug}/, usar ela como base
    if (slugFromUrl) {
        return getRestaurantProfileUrl(slugFromUrl, id, name) || canonicalUrl || url;
    }

    return canonicalUrl || url;
}

function mapApiRestaurantToRestaurant(apiRestaurant) {
    const rating = apiRestaurant.rating?.average || 0;
    const ratingCount = apiRestaurant.rating?.count || 0;
    const cuisines = apiRestaurant.cuisines || [];
    const restaurantName = apiRestaurant.title || '';
    
    // Define a categoria principal para o fallback
    const mainCategory = cuisines.length > 0 ? cuisines.join(' ') : restaurantName;
    
    // Calcular tempo de entrega (placeholder - pode vir da API depois)
    const deliveryTime = '30-45 min';
    
    // Calcular taxa de entrega (placeholder - pode vir da API depois)
    const deliveryFee = apiRestaurant.has_delivery ? 'R$ 5,00' : 'Grátis';
    
    // Lógica de Capa (Hero Image) - FORÇA FALLBACK AQUI se não tiver imagem válida
    let finalImage = isValidImage(apiRestaurant.featured_media_url) 
        ? apiRestaurant.featured_media_url 
        : (isValidImage(apiRestaurant.image) 
            ? apiRestaurant.image 
            : getSmartImage(mainCategory)); // Força o fallback aqui!
    
    // Lógica de Logo
    let hasLogo = isValidImage(apiRestaurant.logo);
    let finalLogo = hasLogo ? apiRestaurant.logo : null;
    
    // URL do restaurante - priorizar slug/ID e evitar templates estáticos
    const slug = apiRestaurant.slug
        || apiRestaurant.post_name
        || apiRestaurant.restaurant_slug
        || extractSlugFromUrl(apiRestaurant.url || apiRestaurant.link)
        || null;
    const id = apiRestaurant.id || apiRestaurant.ID || apiRestaurant.restaurant_id || null;
    // Sempre priorizar o permalink canônico baseado no slug/ID/nome
    const url = normalizeRestaurantUrl(apiRestaurant.url || apiRestaurant.link, slug, id, restaurantName)
        || getRestaurantProfileUrl(slug, id, restaurantName)
        || '#';
    
    return {
        id: apiRestaurant.id,
        slug: slug,
        url: url,
        name: restaurantName,
        rating: rating > 0 ? rating.toFixed(1) : 'Novo',
        deliveryTime: deliveryTime,
        deliveryFee: deliveryFee,
        image: finalImage, // URL garantida (nunca null)
        logo: finalLogo,
        hasLogo: hasLogo,
        isOpen: apiRestaurant.is_open || false,
        cuisines: cuisines,
        category: cuisines.length > 0 ? cuisines[0] : 'Restaurante',
        address: apiRestaurant.address || '',
        phone: apiRestaurant.phone || ''
    };
}

function mapApiRestaurantToFeatured(apiRestaurant) {
    const base = mapApiRestaurantToRestaurant(apiRestaurant);
    return {
        ...base,
        tags: apiRestaurant.cuisines || [],
        hasReservation: true // Placeholder - pode vir da API depois
    };
}

// ============ FUNÇÕES DE BUSCA DE DADOS ============
async function fetchBanners() {
    try {
        const response = await fetch(`${API_BASE}/banners`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return Array.isArray(data) ? data.map(mapApiBannerToBanner) : [];
    } catch (error) {
        console.error('Erro ao buscar banners:', error);
        return [];
    }
}

async function fetchRestaurants(params = {}) {
    try {
        const queryParams = new URLSearchParams();
        if (params.per_page) queryParams.append('per_page', params.per_page);
        if (params.orderby) queryParams.append('orderby', params.orderby);
        if (params.order) queryParams.append('order', params.order);
        if (params.search) queryParams.append('search', params.search);
        
        const url = `${API_BASE}/restaurants${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
        console.log('🔍 mobile-shell-v2.js: Buscando restaurantes em:', url);
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        console.log('📡 mobile-shell-v2.js: Resposta da API:', response.status, response.statusText);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('📦 mobile-shell-v2.js: Dados recebidos:', Array.isArray(data) ? `${data.length} restaurantes` : 'Não é array');
        
        if (!Array.isArray(data)) {
            console.warn('⚠️ mobile-shell-v2.js: Resposta da API não é um array:', data);
            return [];
        }
        
        // Mapear restaurantes
        const restaurants = data.map(mapApiRestaurantToRestaurant);
        
        // Buscar imagens em paralelo (limitado para não sobrecarregar)
        const imagePromises = restaurants.slice(0, 20).map(async (restaurant) => {
            const imageUrl = await getRestaurantImage(restaurant.id, restaurant.cuisines);
            // Só atualizar se encontrou uma imagem real (não é placeholder)
            if (imageUrl && imageUrl !== restaurant.image) {
                restaurant.image = imageUrl;
            }
            return restaurant;
        });
        
        await Promise.all(imagePromises);
        
        return restaurants;
    } catch (error) {
        console.error('Erro ao buscar restaurantes:', error);
        return [];
    }
}

async function fetchFeaturedRestaurants() {
    // Primeiro tentar buscar restaurantes marcados como featured
    try {
        const featuredResponse = await fetch(`${API_BASE}/restaurants?featured=true&per_page=4`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (featuredResponse.ok) {
            const featured = await featuredResponse.json();
            if (Array.isArray(featured) && featured.length > 0) {
                return featured.map(mapApiRestaurantToFeatured);
            }
        }
    } catch (error) {
        console.error('Erro ao buscar restaurantes em destaque:', error);
    }
    
    // Fallback: Buscar restaurantes ordenados por rating (maior primeiro)
    const restaurants = await fetchRestaurants({
        per_page: 4,
        orderby: 'rating',
        order: 'desc'
    });
    
    return restaurants.map(mapApiRestaurantToFeatured);
}

// ============ DADOS DOS STORIES (Carregados da API) ============
let storiesData = [];

/**
 * Busca stories da API REST
 */
async function fetchStories() {
    try {
        const url = `${API_BASE}/stories`;
        console.log('Buscando stories da API:', url);
        
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            console.error(`Erro HTTP ao buscar stories: ${response.status} ${response.statusText}`);
            const errorText = await response.text();
            console.error('Resposta do erro:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Resposta da API de stories:', data);
        
        // Verificar se é array ou objeto com propriedade groups
        if (Array.isArray(data)) {
            return data;
        } else if (data && Array.isArray(data.groups)) {
            return data.groups;
        } else if (data && Array.isArray(data.data)) {
            return data.data;
        }
        
        console.warn('Formato de resposta inesperado:', data);
        return [];
    } catch (error) {
        console.error('Erro ao buscar stories:', error);
        return [];
    }
}

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
async function renderStories() {
    const container = document.getElementById('storiesScroll');
    if (!container) {
        console.warn('Container storiesScroll não encontrado no DOM');
        return;
    }
    
    // Mostrar skeleton loading
    container.innerHTML = `
        <div class="story-item skeleton">
            <div class="story-avatar-wrapper">
                <div class="story-ring">
                    <div class="story-avatar-container" style="background: #eee; border-radius: 50%; width: 56px; height: 56px;"></div>
                </div>
            </div>
            <div class="story-name" style="background: #eee; height: 12px; width: 60px; border-radius: 4px; margin-top: 4px;"></div>
        </div>
    `.repeat(5);
    
    // Buscar stories da API
    try {
        storiesData = await fetchStories();
        console.log('Stories carregados da API:', storiesData);
    } catch (error) {
        console.error('Erro ao carregar stories:', error);
        container.innerHTML = '';
        return;
    }
    
    if (!storiesData || storiesData.length === 0) {
        console.log('Nenhum story encontrado na API');
        container.innerHTML = '';
        return;
    }
    
    // Renderizar stories
    container.innerHTML = storiesData.map(story => {
        // Usar avatar do restaurante ou fallback
        const avatar = story.restaurant.avatar || getLogoFallback(story.restaurant.name);
        const avatarHtml = isValidImage(story.restaurant.avatar) 
            ? `<img src="${story.restaurant.avatar}" alt="${story.restaurant.name}" class="story-avatar" onerror="this.onerror=null; this.parentElement.innerHTML='${getLogoFallback(story.restaurant.name)}';">`
            : getLogoFallback(story.restaurant.name);
        
        return `
            <div class="story-item" onclick="window.openStory(${story.id})" style="cursor: pointer;">
            <div class="story-avatar-wrapper">
                <div class="story-ring ${story.viewed ? 'viewed' : ''}">
                    <div class="story-avatar-container">
                            ${avatarHtml}
                    </div>
                </div>
                ${story.hasNew ? '<div class="story-new-badge">+</div>' : ''}
            </div>
            <div class="story-name">${story.restaurant.name}</div>
        </div>
        `;
    }).join('');
    
    // Anexar event listeners após renderização
    attachStoryListeners();
}

/**
 * Anexa event listeners para os stories
 */
function attachStoryListeners() {
    // Event delegation já está no onclick inline, mas podemos adicionar mais lógica aqui se necessário
}

// ============ STORY VIEWER ============
let currentStoryGroup = null;
let currentStoryIndex = 0;
let storyTimer = null;
let progressInterval = null;

function openStory(groupId) {
    const storyGroup = storiesData.find(s => s.id === groupId);
    if (!storyGroup || !storyGroup.stories || storyGroup.stories.length === 0) {
        console.warn('Story group não encontrado ou sem stories:', groupId);
    return;
    }

    currentStoryGroup = storyGroup;
    currentStoryIndex = 0;
    
    const viewer = document.getElementById('storyViewer');
    if (!viewer) {
        console.warn('Story viewer não encontrado no DOM');
        return;
    }
    
    viewer.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    renderStoryProgressBars(storyGroup.stories.length);
    showStory(0);
    
    // Marcar como visto via API
    markStoryAsViewed(storyGroup.stories[0].id);
    
    // Atualizar estado local
    storyGroup.viewed = true;
    renderStories();
}

// Expor função globalmente para onclick inline
window.openStory = openStory;

/**
 * Marca um story como visto via API
 */
async function markStoryAsViewed(storyId) {
    // Apenas se usuário estiver logado
    if (!window.VemComer || !window.VemComer.nonce) {
        return;
    }
    
    try {
        await fetch(`${API_BASE}/stories/${storyId}/view`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': window.VemComer.nonce
            }
        });
    } catch (error) {
        console.error('Erro ao marcar story como visto:', error);
    }
}

function renderStoryProgressBars(count) {
    const container = document.getElementById('storyProgressBars');
    if (!container) return;
    
    container.innerHTML = Array(count).fill(0).map((_, i) => `
        <div class="progress-bar story-progress-bar">
            <div class="progress-fill story-progress-fill" id="storyProgress${i}"></div>
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
    
    if (headerAvatar) {
        const avatarUrl = currentStoryGroup.restaurant.avatar;
        if (isValidImage(avatarUrl)) {
            headerAvatar.src = avatarUrl;
            headerAvatar.style.display = '';
        } else {
            headerAvatar.style.display = 'none';
        }
    }
    if (headerName) headerName.textContent = currentStoryGroup.restaurant.name;
    if (headerTime) headerTime.textContent = story.timestamp || 'agora';

    // Update media
    const media = document.getElementById('storyMedia');
    if (media) {
        if (story.type === 'video') {
            // Para vídeo, criar elemento <video>
            if (media.tagName !== 'VIDEO') {
                const video = document.createElement('video');
                video.className = 'story-media';
                video.controls = false;
                video.autoplay = true;
                video.muted = true;
                video.loop = false;
                media.parentNode.replaceChild(video, media);
                document.getElementById('storyMedia').src = story.url;
            } else {
                media.src = story.url;
            }
        } else {
            // Para imagem, usar <img>
            if (media.tagName !== 'IMG') {
                const img = document.createElement('img');
                img.className = 'story-media';
                img.alt = 'Story';
                media.parentNode.replaceChild(img, media);
                document.getElementById('storyMedia').src = story.url || PLACEHOLDERS.default;
            } else {
                media.src = story.url || PLACEHOLDERS.default;
            }
        }
    }
    
    // Marcar story atual como visto
    if (story.id) {
        markStoryAsViewed(story.id);
    }

    // Atualizar botão CTA
    const ctaBtn = document.getElementById('storyCtaBtn');
    if (ctaBtn) {
        console.log('Story data para CTA:', {
            link_type: story.link_type,
            link: story.link,
            link_text: story.link_text,
            story_id: story.id
        });

        // Sempre resetar primeiro
        ctaBtn.classList.remove('is-visible');
        ctaBtn.style.visibility = 'hidden';
        ctaBtn.style.opacity = '0';
        ctaBtn.style.pointerEvents = 'none';
        ctaBtn.onclick = null;
        
        if (story.link_type === 'profile' || story.link_type === 'menu') {
            const btnText = story.link_text || (story.link_type === 'profile' ? 'Ver Perfil' : 'Ver Cardápio');
            ctaBtn.textContent = btnText;
            ctaBtn.classList.add('is-visible');
            ctaBtn.style.visibility = 'visible';
            ctaBtn.style.opacity = '1';
            ctaBtn.style.pointerEvents = 'auto';
            ctaBtn.onclick = (e) => {
                e.stopPropagation();
                e.preventDefault();
                handleStoryCta(story);
            };
            console.log('✅ Botão CTA configurado:', btnText, 'link_type:', story.link_type);
        } else if (story.link) {
            // Compatibilidade com link customizado antigo
            ctaBtn.textContent = story.link_text || 'Ver Mais';
            ctaBtn.classList.add('is-visible');
            ctaBtn.style.visibility = 'visible';
            ctaBtn.style.opacity = '1';
            ctaBtn.style.pointerEvents = 'auto';
            ctaBtn.onclick = (e) => {
                e.stopPropagation();
                e.preventDefault();
                window.location.href = story.link;
            };
            console.log('✅ Botão CTA configurado (link antigo):', story.link_text);
        } else {
            console.log('⚠️ Botão CTA ocultado - sem link_type ou link');
        }
    } else {
        console.error('❌ Botão CTA não encontrado no DOM!');
    }

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

/**
 * Lida com o clique no botão CTA do story
 */
function handleStoryCta(story) {
    console.log('handleStoryCta chamado:', {
        link_type: story.link_type,
        story_restaurant_id: story.restaurant_id,
        currentStoryGroup: currentStoryGroup
    });
    
    if (story.link_type === 'profile') {
        // Redirecionar para perfil do restaurante
        const restaurantId = story.restaurant_id || (currentStoryGroup && currentStoryGroup.restaurant ? currentStoryGroup.restaurant.id : null);
        const restaurantSlug = story.restaurant_slug || currentStoryGroup?.restaurant?.slug;
        const profileUrl = getRestaurantProfileUrl(restaurantSlug, restaurantId);

        if (profileUrl) {
            window.location.href = profileUrl;
        } else {
            console.error('❌ Restaurant ID não encontrado para perfil');
        }
    } else if (story.link_type === 'menu') {
        // Mostrar modal de cardápio para escolher um item
        // Tentar obter restaurant_id de várias fontes
        let restaurantId = story.restaurant_id;
        
        if (!restaurantId && currentStoryGroup && currentStoryGroup.restaurant) {
            restaurantId = currentStoryGroup.restaurant.id;
        }
        
        // Se ainda não tiver, tentar buscar do grupo atual
        if (!restaurantId && currentStoryGroup) {
            // O grupo pode ter o restaurant_id diretamente
            restaurantId = currentStoryGroup.restaurant_id;
        }
        
        console.log('Restaurant ID para cardápio:', {
            story_restaurant_id: story.restaurant_id,
            current_group_restaurant_id: currentStoryGroup?.restaurant?.id,
            final_restaurant_id: restaurantId
        });
        
        if (restaurantId) {
            showStoryMenuModal(restaurantId);
        } else {
            console.error('❌ Restaurant ID não encontrado para cardápio');
            console.error('Story completo:', story);
            console.error('Current Story Group:', currentStoryGroup);
            alert('Erro: Restaurante não identificado. Não foi possível carregar o cardápio.');
        }
    }
}

/**
 * Mostra modal com cardápio do restaurante para escolher um item
 */
async function showStoryMenuModal(restaurantId) {
    console.log('showStoryMenuModal chamado com restaurantId:', restaurantId);
    
    const modal = document.getElementById('storyMenuModal');
    const modalBody = document.getElementById('storyMenuModalBody');
    const modalTitle = document.getElementById('storyMenuModalTitle');
    
    if (!modal) {
        console.error('❌ Modal storyMenuModal não encontrado no DOM!');
        return;
    }
    
    if (!modalBody) {
        console.error('❌ Modal body storyMenuModalBody não encontrado no DOM!');
        return;
    }
    
    console.log('✅ Elementos do modal encontrados');
    
    // Buscar nome do restaurante
    if (modalTitle) {
        if (currentStoryGroup && currentStoryGroup.restaurant) {
            modalTitle.textContent = `Escolha um item - ${currentStoryGroup.restaurant.name}`;
        } else {
            modalTitle.textContent = 'Escolha um item do cardápio';
        }
    }
    
    // Mostrar loading
    modalBody.innerHTML = '<div class="story-menu-loading">Carregando cardápio...</div>';
    modal.classList.add('active');
    console.log('✅ Modal aberto, carregando cardápio...');
    
    try {
        // Buscar cardápio da API (usar endpoint de categorias)
        const url = `${API_BASE}/restaurants/${restaurantId}/menu-categories`;
        console.log('Buscando cardápio da API:', url);
        
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });
        
        console.log('Resposta da API:', {
            status: response.status,
            statusText: response.statusText,
            ok: response.ok
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro HTTP:', errorText);
            throw new Error(`Erro ao carregar cardápio: ${response.status} ${response.statusText}`);
        }
        
        const data = await response.json();
        console.log('Dados recebidos da API:', data);
        
        // A API retorna { restaurant_id, categories: [...] }
        const categories = data.categories || [];
        console.log('Categorias encontradas:', categories.length);
        
        // Renderizar cardápio com botões para escolher item
        if (categories && categories.length > 0) {
            let html = '';
            categories.forEach(category => {
                html += `<div class="story-menu-category">
                    <h3 class="story-menu-category-title">${category.name || 'Sem categoria'}</h3>
                    <div class="story-menu-items">`;
                
                if (category.items && category.items.length > 0) {
                    category.items.forEach(item => {
                        const price = parseFloat(item.price || 0).toLocaleString('pt-BR', {
                            style: 'currency',
                            currency: 'BRL'
                        });
                        const isAvailable = item.is_available !== false;
                        const itemTitleEscaped = (item.title || item.name || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        html += `<div class="story-menu-item ${!isAvailable ? 'unavailable' : ''}" data-item-id="${item.id}" data-item-name="${itemTitleEscaped}" data-item-price="${item.price || 0}" style="cursor: ${isAvailable ? 'pointer' : 'not-allowed'};">
                            <div class="story-menu-item-info">
                                <h4 class="story-menu-item-name">${item.title || item.name || 'Item sem nome'}</h4>
                                <p class="story-menu-item-price">${price}</p>
                            </div>
                            ${item.prep_time ? `<span class="story-menu-item-time">${item.prep_time}min</span>` : ''}
                            ${isAvailable ? '<button class="story-menu-item-select-btn" onclick="selectMenuItem(' + item.id + ', \'' + itemTitleEscaped + '\', ' + (item.price || 0) + ')">Escolher</button>' : '<span class="story-menu-item-unavailable-label">Indisponível</span>'}
                        </div>`;
                    });
                } else {
                    html += '<p class="story-menu-empty">Nenhum item nesta categoria</p>';
                }
                
                html += `</div></div>`;
            });
            modalBody.innerHTML = html;
            console.log('✅ Cardápio renderizado com sucesso');
        } else {
            console.warn('⚠️ Nenhuma categoria encontrada no cardápio');
            modalBody.innerHTML = '<div class="story-menu-empty">Cardápio não disponível ou vazio</div>';
        }
    } catch (error) {
        console.error('❌ Erro ao carregar cardápio:', error);
        modalBody.innerHTML = `<div class="story-menu-error">Erro ao carregar cardápio: ${error.message}. Tente novamente.</div>`;
    }
}

/**
 * Seleciona um item do cardápio e redireciona para o produto
 */
function selectMenuItem(itemId, itemName, itemPrice) {
    // Fechar modal
    closeStoryMenuModal();
    
    // Fechar também o story viewer
    closeStoryViewer();
    
    // Redirecionar para página do produto ou abrir modal de detalhes
    // Verificar se existe função para abrir modal de produto
    if (typeof openProductModal === 'function') {
        openProductModal(itemId);
    } else if (typeof showProductDetails === 'function') {
        showProductDetails(itemId);
    } else {
        // Fallback: redirecionar para página do produto
        window.location.href = `/produto/?id=${itemId}`;
    }
}

/**
 * Fecha modal de cardápio
 */
function closeStoryMenuModal() {
    const modal = document.getElementById('storyMenuModal');
    if (modal) {
        modal.classList.remove('active');
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
let bannerAutoPlayInterval = null;

async function renderBanners() {
    const container = document.getElementById('bannerCarousel');
    const dotsContainer = document.getElementById('bannerDots');
    
    if (!container || !dotsContainer) return;
    
    // Mostrar skeleton loading
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Carregando banners...</div>';
    
    const banners = await fetchBanners();
    
    if (banners.length === 0) {
        // Se não houver banners, mostrar placeholder
        container.innerHTML = `
            <div class="banner-slide" data-index="0">
                <img src="${PLACEHOLDER_IMAGE}" alt="Sem banners" class="banner-image">
                <div class="banner-overlay">
                    <div class="banner-title">Bem-vindo ao VemComer</div>
                    <div class="banner-subtitle">Cadastre banners no painel administrativo</div>
                </div>
            </div>
        `;
        dotsContainer.innerHTML = '';
        // Não inicializar carousel se não houver banners reais
        return;
    }
    
    // Renderizar banners
    container.innerHTML = banners.map((banner, index) => `
    <div class="banner-slide" data-index="${index}" ${banner.link ? `onclick="window.location.href='${banner.link}'"` : ''} style="cursor: pointer;">
            <img src="${banner.image || PLACEHOLDERS.default}" alt="${banner.title}" class="banner-image" loading="lazy" onerror="this.onerror=null; this.src='${PLACEHOLDERS.default}';">
            <div class="banner-overlay">
                <div class="banner-title">${banner.title || 'Bem-vindo ao VemComer'}</div>
                ${banner.subtitle ? `<div class="banner-subtitle">${banner.subtitle}</div>` : ''}
            </div>
        </div>
    `).join('');
    
    // Renderizar dots (apenas se houver mais de 1 banner)
    if (banners.length > 1) {
        dotsContainer.innerHTML = banners.map((_, index) => `
            <span class="banner-dot ${index === 0 ? 'active' : ''}" data-index="${index}"></span>
        `).join('');
    } else {
        dotsContainer.innerHTML = '';
    }
    
    // Reinicializar carousel
    initBannerCarousel();
}

function initBannerCarousel() {
    bannerCarousel = document.getElementById('bannerCarousel');
    bannerDots = document.querySelectorAll('.banner-dot');
    
    if (!bannerCarousel || !bannerDots.length) return;
    
    // Limpar intervalo anterior
    if (bannerAutoPlayInterval) {
        clearInterval(bannerAutoPlayInterval);
    }

    function updateBannerCarousel(index) {
        const dots = document.querySelectorAll('.banner-dot');
        if (index >= dots.length) return;
        
        currentBannerIndex = index;
        const offset = -index * 100;
        bannerCarousel.style.transform = `translateX(${offset}%)`;
        
        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === index);
        });
    }

    bannerDots.forEach(dot => {
        dot.addEventListener('click', () => {
            updateBannerCarousel(parseInt(dot.dataset.index));
            // Resetar autoplay
            if (bannerAutoPlayInterval) {
                clearInterval(bannerAutoPlayInterval);
            }
            startBannerAutoPlay();
        });
    });

    function startBannerAutoPlay() {
        if (bannerDots.length <= 1) return;
        
        bannerAutoPlayInterval = setInterval(() => {
            const nextIndex = (currentBannerIndex + 1) % bannerDots.length;
            updateBannerCarousel(nextIndex);
        }, 5000);
    }
    
    startBannerAutoPlay();

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
            if (bannerAutoPlayInterval) {
                clearInterval(bannerAutoPlayInterval);
            }
            startBannerAutoPlay();
        }
        if (touchEndX - touchStartX > 50) {
            const prevIndex = Math.max(currentBannerIndex - 1, 0);
            updateBannerCarousel(prevIndex);
            if (bannerAutoPlayInterval) {
                clearInterval(bannerAutoPlayInterval);
            }
            startBannerAutoPlay();
        }
    }
}

// ============ RENDER OTHER SECTIONS ============
async function fetchFeaturedDishes() {
    try {
        const response = await fetch(`${API_BASE}/menu-items?featured=true&per_page=10`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Erro ao buscar pratos do dia:', error);
        return [];
    }
}

async function renderDishes() {
    const container = document.getElementById('dishesScroll');
    if (!container) return;
    
    // Mostrar skeleton loading
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Carregando pratos do dia...</div>';
    
    const dishes = await fetchFeaturedDishes();
    
    if (dishes.length === 0) {
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Nenhum prato em destaque no momento.</div>';
        return;
    }
    
    // Buscar categorias dos restaurantes para fallback inteligente
    const restaurantIds = [...new Set(dishes.map(d => d.restaurant_id).filter(Boolean))];
    const restaurantCuisinesMap = {};
    
    // Buscar categorias em paralelo
    await Promise.all(restaurantIds.map(async (restId) => {
        try {
            const res = await fetch(`${API_BASE}/restaurants/${restId}`);
            if (res.ok) {
                const restData = await res.json();
                restaurantCuisinesMap[restId] = restData.cuisines || [];
            }
        } catch (e) {
            console.warn('Erro ao buscar categoria do restaurante:', e);
        }
    }));
    
    container.innerHTML = dishes.map(dish => {
        // Obter categoria do restaurante para fallback inteligente
        const cuisines = restaurantCuisinesMap[dish.restaurant_id] || [];
        const categoryName = cuisines.length > 0 ? cuisines[0] : '';
        
        // Concatena nome e categoria para melhor precisão (FORÇA FALLBACK AQUI)
        const contextText = `${dish.name || ''} ${categoryName}`.trim();
        const finalImage = isValidImage(dish.image) 
            ? dish.image 
            : getSmartImage(contextText); // Força o fallback aqui!
        
        // Escapar aspas e caracteres especiais para JSON seguro
        const safeName = (dish.name || 'Prato').replace(/'/g, "\\'").replace(/"/g, '&quot;');
        const safeDesc = (dish.description || '').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, ' ');
        const safePrice = parseFloat((dish.price || '0').replace(/[^\d,]/g, '').replace(',', '.')) || 0;
        const safeImage = finalImage.replace(/'/g, "\\'");
        
        return `
        <div class="dish-card" data-dish-id="${dish.id || ''}" data-dish-name="${safeName}" data-dish-desc="${safeDesc}" data-dish-price="${safePrice}" data-dish-image="${safeImage}" style="cursor: pointer;">
            <div class="dish-image-wrapper">
                <img src="${finalImage}" alt="${dish.name || 'Prato'}" class="dish-image" loading="lazy" onerror="this.onerror=null; this.src='${PLACEHOLDERS.default}';">
                ${dish.badge ? `<div class="dish-badge">${dish.badge}</div>` : ''}
                ${dish.price ? `<div class="dish-price-badge">${dish.price}</div>` : ''}
            </div>
            <div class="dish-content">
                <div class="dish-restaurant">${dish.restaurant || 'Restaurante'}</div>
                <div class="dish-name">${dish.name}</div>
                <div class="dish-description">${dish.description || ''}</div>
            </div>
        </div>
    `;
    }).join('');
}

async function fetchEvents() {
    try {
        // Buscar eventos de hoje ou futuros, featured
        const today = new Date().toISOString().split('T')[0];
        const response = await fetch(`${API_BASE}/events?date=${today}&featured=true&per_page=10`, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Erro ao buscar eventos:', error);
        return [];
    }
}

async function renderEvents() {
    const container = document.getElementById('eventsScroll');
    if (!container) return;
    
    // Mostrar skeleton loading
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Carregando eventos...</div>';
    
    const events = await fetchEvents();
    
    if (events.length === 0) {
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Nenhum evento programado para hoje.</div>';
        return;
    }
    
    container.innerHTML = events.map(event => `
        <div class="event-card" onclick="window.location.href='${TEMPLATE_PATH}detalhes-evento.html'" style="cursor: pointer;">
            <div class="event-image-wrapper">
                <img src="${event.image || PLACEHOLDERS.default}" alt="${event.title}" class="event-image" loading="lazy" onerror="this.onerror=null; this.src='${PLACEHOLDERS.default}';">
                <div class="event-date-badge">
                    <div class="event-day">${event.date.day || ''}</div>
                    <div class="event-month">${event.date.month || ''}</div>
                </div>
                ${event.is_live ? '<div class="event-live-badge">● AO VIVO</div>' : ''}
            </div>
            <div class="event-content">
                <div class="event-restaurant">${event.restaurant || 'Restaurante'}</div>
                <div class="event-title">${event.title}</div>
                <div class="event-info">
                    ${event.time ? `
                        <div class="event-info-item">
                            <svg class="event-info-icon" viewBox="0 0 24 24">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            ${event.time}
                        </div>
                    ` : ''}
                    ${event.price ? `
                        <div class="event-info-item">
                            <svg class="event-info-icon" viewBox="0 0 24 24">
                                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                            </svg>
                            ${event.price}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `).join('');
}

async function renderFeatured() {
    const container = document.getElementById('featuredGrid');
    if (!container) return;
    
    // Mostrar skeleton loading
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Carregando destaques...</div>';
    
    const restaurants = await fetchFeaturedRestaurants();
    
    if (restaurants.length === 0) {
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Nenhum restaurante em destaque no momento.</div>';
        return;
    }
    
    container.innerHTML = restaurants.map(restaurant => {
        const profileUrl = normalizeRestaurantUrl(restaurant.url, restaurant.slug, restaurant.id, restaurant.name)
            || getRestaurantProfileUrl(restaurant.slug, restaurant.id, restaurant.name)
            || '#';
        return `
        <div class="featured-card" data-restaurant-id="${restaurant.id}" data-restaurant-slug="${restaurant.slug || ''}" data-restaurant-url="${profileUrl}" data-restaurant-name="${restaurant.name || ''}" onclick="window.location.href='${profileUrl}'" style="cursor: pointer;">
            <div class="featured-image-wrapper">
                <img
                    src="${restaurant.image}"
                    alt="${restaurant.name}"
                    class="featured-image"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='${PLACEHOLDERS.default}';"
                >
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
                    ${restaurant.tags.length > 0
                        ? restaurant.tags.map(tag => `<span class="featured-tag">${tag}</span>`).join('')
                        : '<span class="featured-tag">Restaurante</span>'
                    }
                </div>
                <div class="featured-info">
                    <span>${restaurant.deliveryTime}</span>
                    <span class="info-dot"></span>
                    <span>${restaurant.deliveryFee}</span>
                </div>
                ${restaurant.hasReservation ? `
                    <button class="featured-reserve-btn" data-restaurant-id="${restaurant.id}">
                        <svg class="reserve-icon" viewBox="0 0 24 24">
                            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/>
                        </svg>
                        Reservar Mesa
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    }).join('');
    
    // Adicionar event listeners após renderização
    attachFeaturedCardListeners();
}


async function renderRestaurants() {
    const container = document.getElementById('restaurantsGrid');
    if (!container) {
        console.error('❌ mobile-shell-v2.js: Container #restaurantsGrid não encontrado!');
        return;
    }
    
    console.log('✅ mobile-shell-v2.js: Iniciando renderRestaurants()...');
    
    // Mostrar skeleton loading
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Carregando restaurantes...</div>';
    
    let restaurants = [];
    try {
        restaurants = await fetchRestaurants({ per_page: 20 });
        console.log('✅ mobile-shell-v2.js: Restaurantes recebidos:', restaurants.length);
    } catch (error) {
        console.error('❌ mobile-shell-v2.js: Erro ao buscar restaurantes:', error);
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #f44336;">Erro ao carregar restaurantes. Verifique o console.</div>';
        return;
    }
    
    if (restaurants.length === 0) {
        console.warn('⚠️ mobile-shell-v2.js: Nenhum restaurante encontrado na API');
        container.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">Nenhum restaurante encontrado.</div>';
        return;
    }
    
    container.innerHTML = restaurants.map(restaurant => {
        const profileUrl = normalizeRestaurantUrl(restaurant.url, restaurant.slug, restaurant.id, restaurant.name)
            || getRestaurantProfileUrl(restaurant.slug, restaurant.id, restaurant.name)
            || '#';
        return `
        <div class="restaurant-card" data-restaurant-id="${restaurant.id}" data-restaurant-slug="${restaurant.slug || ''}" data-restaurant-url="${profileUrl}" data-restaurant-name="${restaurant.name || ''}" onclick="window.location.href='${profileUrl}'" style="cursor: pointer;">
            <div class="card-image-wrapper">
                <img
                    src="${restaurant.image}"
                    alt="${restaurant.name}" 
                    class="card-image" 
                    loading="lazy"
                    onerror="this.onerror=null; this.src='${PLACEHOLDERS.default}';"
                >
                
                <div class="card-badges">
                    <div class="card-badge ${restaurant.isOpen ? 'badge-open' : 'badge-closed'}">
                        ${restaurant.isOpen ? '• Aberto' : 'Fechado'}
                    </div>
                </div>

                <div class="card-logo-wrapper" style="position:absolute; bottom:10px; left:10px; width:40px; height:40px; border-radius:50%; overflow:hidden; border:2px solid white; background:#fff; z-index:2;">
                    ${restaurant.hasLogo 
                        ? `<img src="${restaurant.logo}" style="width:100%; height:100%; object-fit:cover;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                           <div class="logo-fallback-hidden" style="display:none; width:100%; height:100%;">${getLogoFallback(restaurant.name)}</div>`
                        : getLogoFallback(restaurant.name)
                    }
                </div>

                <button class="favorite-btn" data-restaurant-id="${restaurant.id}">
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
                    <span>${restaurant.category || 'Restaurante'}</span>
                    <span class="info-dot"></span>
                    <span>${restaurant.deliveryTime}</span>
                    <span class="info-dot"></span>
                    <span>${restaurant.deliveryFee}</span>
                </div>
            </div>
        </div>
    `;}).join('');
    
    // Adicionar event listeners após renderização
    attachRestaurantCardListeners();
}


// ============ EVENT HANDLERS ============
// Garantir que as funções estejam no escopo global
window.openDish = function(id) {
    // Buscar dados do produto e abrir modal
    fetch(`${API_BASE}/menu-items/${id}`)
        .then(response => response.json())
        .then(produto => {
            abrirModalProduto({
                img: produto.image || produto.featured_image || PLACEHOLDERS.default,
                titulo: produto.title || produto.name || 'Produto',
                descricao: produto.description || produto.excerpt || '',
                preco: parseFloat(produto.price || produto.meta?._vc_menu_item_price || 0)
            });
        })
        .catch(error => {
            console.error('Erro ao buscar produto:', error);
            // Fallback: redirecionar para página estática
            window.location.href = TEMPLATE_PATH + 'modal-detalhes-produto.html';
        });
};

window.openEvent = function(id) {
    window.location.href = TEMPLATE_PATH + 'detalhes-evento.html';
};

window.openRestaurant = function(id, slug) {
    const targetUrl = getRestaurantProfileUrl(slug, id);
    if (targetUrl) {
        window.location.href = targetUrl;
    }
};

window.openReservation = function(id, event) {
    if (event) {
        event.stopPropagation();
    }
    window.location.href = TEMPLATE_PATH + 'minhas-reservas.html';
};

window.toggleFavorite = function(event, id) {
    if (event) {
        event.stopPropagation();
    }
    // TODO: Implementar lógica de favoritos
    console.log('Toggle favorite:', id);
};

/**
 * Attach event listeners para cards de restaurantes (event delegation)
 */
function attachRestaurantCardListeners() {
    // Event delegation para cliques nos cards
    document.addEventListener('click', function(e) {
        const restaurantCard = e.target.closest('.restaurant-card');
        if (restaurantCard) {
            // Ignorar cliques no botão de favorito
            if (e.target.closest('.favorite-btn')) {
                const restaurantId = parseInt(e.target.closest('.favorite-btn').dataset.restaurantId);
                if (restaurantId) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.toggleFavorite(e, restaurantId);
                }
                return;
            }
            
            const restaurantId = restaurantCard.dataset.restaurantId;
            const restaurantSlug = restaurantCard.dataset.restaurantSlug;
            const restaurantName = restaurantCard.dataset.restaurantName || '';
            const targetUrl = normalizeRestaurantUrl(restaurantCard.dataset.restaurantUrl, restaurantSlug, restaurantId, restaurantName);

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        }
    });
}

/**
 * Attach event listeners para cards de restaurantes em destaque
 */
function attachFeaturedCardListeners() {
    // Event delegation para cliques nos cards featured
    document.addEventListener('click', function(e) {
        const featuredCard = e.target.closest('.featured-card');
        if (featuredCard) {
            // Ignorar cliques no botão de reserva
            if (e.target.closest('.featured-reserve-btn')) {
                const restaurantId = parseInt(e.target.closest('.featured-reserve-btn').dataset.restaurantId);
                if (restaurantId) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.openReservation(restaurantId, e);
                }
                return;
            }
            
            const restaurantId = featuredCard.dataset.restaurantId;
            const restaurantSlug = featuredCard.dataset.restaurantSlug;
            const restaurantName = featuredCard.dataset.restaurantName || '';
            const targetUrl = normalizeRestaurantUrl(featuredCard.dataset.restaurantUrl, restaurantSlug, restaurantId, restaurantName);

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        }
    });
}


/**
 * Attach event listeners para resultados de busca
 */
function attachSearchResultListeners() {
    // Event delegation para cliques nos resultados de busca
    document.addEventListener('click', function(e) {
        const searchResult = e.target.closest('.search-result-item');
        if (searchResult) {
            const restaurantId = searchResult.dataset.restaurantId;
            const restaurantSlug = searchResult.dataset.restaurantSlug;
            const restaurantName = searchResult.dataset.restaurantName || '';
            const targetUrl = normalizeRestaurantUrl(searchResult.dataset.restaurantUrl, restaurantSlug, restaurantId, restaurantName);

            if (targetUrl) {
                window.location.href = targetUrl;
            }
        }
    });
}


// ============ LOCATION MANAGEMENT ============
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days) {
    // Máximo de tempo possível (365 dias = 1 ano)
    const maxDays = days || 365;
    const expires = new Date();
    expires.setTime(expires.getTime() + (maxDays * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
}

function updateLocationText() {
    const locationTextEl = document.getElementById('locationText');
    if (!locationTextEl) return;
    
    // Prioridade: bairro > cidade > endereço completo > padrão
    const neighborhood = localStorage.getItem('vc_user_neighborhood') || getCookie('vc_user_neighborhood');
    const city = localStorage.getItem('vc_user_city') || getCookie('vc_user_city');
    const savedLocation = localStorage.getItem('vc_user_location');
    
    let displayText = 'Endereço'; // Texto padrão quando não há localização
    
    if (neighborhood) {
        displayText = neighborhood;
    } else if (city) {
        displayText = city;
    } else if (savedLocation) {
        try {
            const locData = JSON.parse(savedLocation);
            if (locData.neighborhood) {
                displayText = locData.neighborhood;
            } else if (locData.city) {
                displayText = locData.city;
            } else if (locData.address) {
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
        
        // Salvar localização completa (com bairro priorizado)
        const locationData = {
            lat: lat,
            lng: lng,
            neighborhood: address.neighborhood || '',
            city: address.city || '',
            state: address.state || '',
            fullAddress: address.fullAddress || ''
        };
        localStorage.setItem('vc_user_location', JSON.stringify(locationData));
        
        // Salvar bairro (prioridade máxima) - cookie com 365 dias
        if (address.neighborhood) {
            localStorage.setItem('vc_user_neighborhood', address.neighborhood);
            setCookie('vc_user_neighborhood', address.neighborhood, 365);
        }
        
        // Salvar cidade - cookie com 365 dias
        if (address.city) {
            localStorage.setItem('vc_user_city', address.city);
            setCookie('vc_user_city', address.city, 365);
        }
        
        // Marcar localização como aceita/validada
        localStorage.setItem('vc_location_accepted', 'true');
        
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
            errorMsg = 'Permissão de localização negada. Você pode permitir nas configurações do navegador ou pular esta etapa.';
        } else if (error.code === error.POSITION_UNAVAILABLE || error.code === 2) {
            errorMsg = 'Localização indisponível. Verifique se o GPS está ativado ou pule esta etapa.';
        } else if (error.code === error.TIMEOUT || error.code === 3) {
            errorMsg = 'Tempo de espera esgotado. Tente novamente ou pule esta etapa.';
        } else if (error.message) {
            errorMsg = error.message;
        }
        
        showLocationError(errorMsg);
    }
}

async function tryAutoLocation() {
    // Tentar obter localização automaticamente (silenciosamente)
    if (!navigator.geolocation) {
        return false;
    }
    
    try {
        const position = await new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000 // Aceitar cache de até 1 minuto
                }
            );
        });
        
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        // Fazer reverse geocoding para obter bairro
        const address = await reverseGeocode(lat, lng);
        
        // Salvar localização completa (com bairro priorizado)
        const locationData = {
            lat: lat,
            lng: lng,
            neighborhood: address.neighborhood || '',
            city: address.city || '',
            state: address.state || '',
            fullAddress: address.fullAddress || ''
        };
        localStorage.setItem('vc_user_location', JSON.stringify(locationData));
        
        // Salvar bairro (prioridade máxima) - cookie com 365 dias
        if (address.neighborhood) {
            localStorage.setItem('vc_user_neighborhood', address.neighborhood);
            setCookie('vc_user_neighborhood', address.neighborhood, 365);
        }
        
        // Salvar cidade - cookie com 365 dias
        if (address.city) {
            localStorage.setItem('vc_user_city', address.city);
            setCookie('vc_user_city', address.city, 365);
        }
        
        // Marcar localização como aceita/validada
        localStorage.setItem('vc_location_accepted', 'true');
        
        // Atualizar texto e fechar modal
        updateLocationText();
        hideLocationModal();
        
        return true;
    } catch (error) {
        // Silenciosamente falhar - não mostrar erro, apenas deixar o modal aberto
        console.log('Tentativa automática de localização falhou:', error);
        return false;
    }
}

function checkLocationAndShowModal() {
    // Verificar se o usuário já pulou a localização
    const locationSkipped = localStorage.getItem('vc_location_skipped') === 'true';
    if (locationSkipped) {
        // Se pulou, não mostrar modal novamente
        updateLocationText();
        hideLocationModal();
        return;
    }
    
    // Verificar se já tem localização salva e válida
    const neighborhood = localStorage.getItem('vc_user_neighborhood') || getCookie('vc_user_neighborhood');
    const city = localStorage.getItem('vc_user_city') || getCookie('vc_user_city');
    const savedLocation = localStorage.getItem('vc_user_location');
    
    // Verificar se há localização válida salva
    let hasValidLocation = false;
    
    // Verificar se tem bairro ou cidade salva
    if (neighborhood && neighborhood.trim() !== '' && neighborhood !== 'Endereço') {
        hasValidLocation = true;
    } else if (city && city.trim() !== '' && city !== 'Endereço') {
        hasValidLocation = true;
    }
    
    // Verificar se tem localização completa salva com dados válidos
    if (!hasValidLocation && savedLocation) {
        try {
            const locData = JSON.parse(savedLocation);
            // Verificar se tem dados válidos (neighborhood, city ou fullAddress)
            if ((locData.neighborhood && locData.neighborhood.trim() !== '') ||
                (locData.city && locData.city.trim() !== '') ||
                (locData.fullAddress && locData.fullAddress.trim() !== '')) {
                hasValidLocation = true;
            }
        } catch (e) {
            // Se não conseguir parsear, considerar inválido
            console.error('Erro ao parsear localização salva:', e);
        }
    }
    
    // Verificar flag de localização aceita/validada
    const locationAccepted = localStorage.getItem('vc_location_accepted') === 'true';
    if (locationAccepted) {
        hasValidLocation = true;
    }
    
    // Se não tiver nenhuma localização válida, mostrar modal e tentar obter automaticamente
    if (!hasValidLocation) {
        showLocationModal();
        // Tentar obter localização automaticamente quando o modal abrir
        setTimeout(() => {
            tryAutoLocation();
        }, 500); // Pequeno delay para o modal aparecer primeiro
    } else {
        // Atualizar texto do locationText (já tem localização válida)
        updateLocationText();
        // Garantir que o modal está escondido
        hideLocationModal();
    }
}

function skipLocation() {
    // Marcar que o usuário pulou a localização
    localStorage.setItem('vc_location_skipped', 'true');
    // Fechar modal
    hideLocationModal();
    // Atualizar texto do locationText (mostrar "Endereço" ou padrão)
    updateLocationText();
}

// ============ SEARCH FUNCTIONALITY ============
let searchTimeout = null;
let currentSearchAbortController = null;

async function searchRestaurants(query) {
    if (!query || query.trim().length < 2) {
        return [];
    }
    
    const url = `${API_BASE}/restaurants?search=${encodeURIComponent(query)}&per_page=10`;
    
    try {
        // Cancelar requisição anterior se existir
        if (currentSearchAbortController) {
            currentSearchAbortController.abort();
        }
        currentSearchAbortController = new AbortController();
        
        const response = await fetch(url, {
            signal: currentSearchAbortController.signal,
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data.restaurants || [];
    } catch (error) {
        if (error.name === 'AbortError') {
            return [];
        }
        console.error('Erro na busca de restaurantes:', error);
        return [];
    }
}

async function searchCategories(query) {
    if (!query || query.trim().length < 2) {
        return [];
    }
    
    // Buscar categorias via API REST do WordPress
    const url = `/wp-json/wp/v2/vc_cuisine?search=${encodeURIComponent(query)}&per_page=5`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            return [];
        }
        
        const data = await response.json();
        return Array.isArray(data) ? data : [];
    } catch (error) {
        console.error('Erro na busca de categorias:', error);
        return [];
    }
}

async function searchMenuItems(query) {
    if (!query || query.trim().length < 2) {
        return [];
    }
    
    // Buscar itens do cardápio via API REST do WordPress
    // Nota: A busca de itens do cardápio será simplificada
    // pois a API padrão não expõe meta fields facilmente
    const url = `/wp-json/wp/v2/vc_menu_item?search=${encodeURIComponent(query)}&per_page=5&status=publish`;
    
    try {
        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            return [];
        }
        
        const data = await response.json();
        if (!Array.isArray(data)) return [];
        
        // Retornar apenas itens com título (sem meta fields por enquanto)
        return data.map(item => ({
            id: item.id,
            title: item.title?.rendered || item.title || '',
            restaurant_id: '', // Será preenchido se necessário
            price: ''
        }));
    } catch (error) {
        console.error('Erro na busca de itens:', error);
        return [];
    }
}

async function performSearch(query) {
    const resultsContainer = document.getElementById('searchResultsContent');
    const resultsDropdown = document.getElementById('searchResults');
    
    if (!resultsContainer || !resultsDropdown) return;
    
    if (!query || query.trim().length < 2) {
        resultsDropdown.style.display = 'none';
        return;
    }
    
    // Mostrar loading
    resultsContainer.innerHTML = `
        <div class="search-results-loading">
            <div class="spinner"></div>
            <span>Buscando...</span>
        </div>
    `;
    resultsDropdown.style.display = 'block';
    
    try {
        // Buscar em paralelo
        const [restaurants, categories, menuItems] = await Promise.all([
            searchRestaurants(query),
            searchCategories(query),
            searchMenuItems(query)
        ]);
        
        // Renderizar resultados (async)
        await renderSearchResults(restaurants, categories, menuItems, query);
    } catch (error) {
        console.error('Erro na busca:', error);
        resultsContainer.innerHTML = `
            <div class="search-results-empty">
                Erro ao buscar. Tente novamente.
            </div>
        `;
    }
}

async function renderSearchResults(restaurants, categories, menuItems, query) {
    const resultsContainer = document.getElementById('searchResultsContent');
    const resultsDropdown = document.getElementById('searchResults');
    
    if (!resultsContainer || !resultsDropdown) return;
    
    let html = '';
    
    // Restaurantes
    if (restaurants.length > 0) {
        restaurants.forEach(restaurant => {
            const rating = restaurant.rating?.average || 0;
            const cuisine = restaurant.cuisines && restaurant.cuisines.length > 0 
                ? restaurant.cuisines[0] 
                : 'Restaurante';
            
            // Buscar nome da categoria se for slug
            let cuisineName = cuisine;
            if (categories.length > 0) {
                const foundCategory = categories.find(cat => cat.slug === cuisine);
                if (foundCategory) {
                    cuisineName = foundCategory.name;
                }
            }
            
            const restaurantUrl = normalizeRestaurantUrl(restaurant.url || restaurant.link, restaurant.slug, restaurant.id, restaurant.name)
                || getRestaurantProfileUrl(restaurant.slug, restaurant.id, restaurant.name)
                || '#';
            html += `
                <div class="search-result-item" data-restaurant-id="${restaurant.id}" data-restaurant-slug="${restaurant.slug || ''}" data-restaurant-url="${restaurantUrl}" data-restaurant-name="${restaurant.name || ''}">
                    <div class="search-result-icon restaurant">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${highlightMatch(restaurant.title || restaurant.name, query)}</div>
                        <div class="search-result-subtitle">${cuisineName} ${rating > 0 ? '• ⭐ ' + rating.toFixed(1) : ''}</div>
                        <div class="search-result-type">Restaurante</div>
                    </div>
                </div>
            `;
        });
    }
    
    // Categorias
    if (categories.length > 0) {
        categories.forEach(category => {
            html += `
                <div class="search-result-item" onclick="window.location.href='/?cuisine=${category.slug}'">
                    <div class="search-result-icon category">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${highlightMatch(category.name, query)}</div>
                        <div class="search-result-subtitle">${category.count || 0} restaurantes</div>
                        <div class="search-result-type">Categoria</div>
                    </div>
                </div>
            `;
        });
    }
    
    // Itens do cardápio (simplificado - sem meta fields por enquanto)
    // Nota: A busca de itens do cardápio será melhorada quando houver endpoint dedicado
    if (menuItems.length > 0) {
        menuItems.forEach(item => {
            const itemTitle = item.title || '';
            if (!itemTitle) return;
            
            html += `
                <div class="search-result-item" onclick="window.location.href='${TEMPLATE_PATH}modal-detalhes-produto.html'">
                    <div class="search-result-icon dish">
                        <svg viewBox="0 0 24 24">
                            <path d="M8.1 13.34l2.83-2.83L3.91 3.5c-1.56 1.56-1.56 4.09 0 5.66l4.19 4.18zm6.78-1.81c1.53.71 3.68.21 5.27-1.38 1.91-1.91 2.28-4.65.81-6.12-1.46-1.46-4.2-1.1-6.12.81-1.59 1.59-2.09 3.74-1.38 5.27L3.7 19.87l1.41 1.41L12 14.41l6.88 6.88 1.41-1.41L13.41 13l1.47-1.47z"/>
                        </svg>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${highlightMatch(itemTitle, query)}</div>
                        <div class="search-result-subtitle">Item do cardápio</div>
                        <div class="search-result-type">Prato</div>
                    </div>
                </div>
            `;
        });
    }
    
    // Se não houver resultados
    if (!html) {
        html = `
            <div class="search-results-empty">
                Nenhum resultado encontrado para "${query}"
            </div>
        `;
    }
    
    resultsContainer.innerHTML = html;
    resultsDropdown.style.display = 'block';
}

function highlightMatch(text, query) {
    if (!text || !query) return text;
    const regex = new RegExp(`(${query})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchResults = document.getElementById('searchResults');
    
    if (!searchInput || !searchResults) return;
    
    // Debounce na busca
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300); // 300ms de debounce
    });
    
    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
    
    // Fechar ao pressionar Escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            searchResults.style.display = 'none';
            searchInput.blur();
        }
    });
}

// ============ INITIALIZE ============
async function initApp() {
    // Anexar event listeners para cards de restaurantes (uma vez no início)
    attachRestaurantCardListeners();
    attachFeaturedCardListeners();
    attachSearchResultListeners();
    
    // Verificar localização e mostrar modal se necessário
    checkLocationAndShowModal();
    
    // Event listener para o botão do modal
    const locationModalBtn = document.getElementById('locationModalBtn');
    if (locationModalBtn) {
        locationModalBtn.addEventListener('click', requestLocationPermission);
    }
    
    // Event listener para o botão "Pular por enquanto"
    const locationModalSkipBtn = document.getElementById('locationModalSkipBtn');
    if (locationModalSkipBtn) {
        locationModalSkipBtn.addEventListener('click', skipLocation);
    }
    
    // Inicializar busca
    initSearch();
    
    // Renderizar conteúdo (carregar em paralelo)
    await Promise.all([
        renderBanners(),
        renderStories(), // Agora busca da API
        renderDishes(), // Agora busca da API
        renderEvents(), // Agora busca da API
        renderFeatured(),
        renderRestaurants()
    ]);
}

// ============ MODAL DETALHES PRODUTO ============
let qtdProduto = 1, precoBaseProduto = 12.90, precoModsProduto = 0;

function alteraQtd(v) {
    qtdProduto = Math.max(1, qtdProduto + v);
    const qtdEl = document.getElementById('qtdProduto');
    if (qtdEl) qtdEl.textContent = qtdProduto;
    atualizaTotal();
}

function atualizaTotal() {
    let precoAdicionais = 0;
    
    // Soma Radio (Proteína)
    const radioChecked = document.querySelector('input[name="proteina"]:checked');
    if (radioChecked) {
        precoAdicionais += parseFloat(radioChecked.value || 0);
    }
    
    // Soma Checkboxes (Adicionais)
    document.querySelectorAll('input[name="adicional"]:checked').forEach(el => {
        precoAdicionais += parseFloat(el.value || 0);
    });
    
    const total = (precoBaseProduto + precoAdicionais) * qtdProduto;
    const precoTotalEl = document.getElementById('precoTotal');
    if (precoTotalEl) {
        precoTotalEl.textContent = 'R$ ' + formatMoney(total);
    }
}

// Função para abrir o modal com dados do produto (global)
window.abrirModalProduto = function(produto) {
    const imgEl = document.getElementById('imgProduto');
    const tituloEl = document.getElementById('tituloProduto');
    const descEl = document.getElementById('descProduto');
    const precoBaseEl = document.getElementById('precoBase');
    const qtdEl = document.getElementById('qtdProduto');
    const precoTotalEl = document.getElementById('precoTotal');
    const obsEl = document.getElementById('obsProduto');
    const modalEl = document.getElementById('modalProduto');
    
    if (!imgEl || !tituloEl || !descEl || !precoBaseEl || !qtdEl || !precoTotalEl || !obsEl || !modalEl) {
        console.error('Elementos do modal não encontrados');
        return;
    }
    
    // Popula os dados
    imgEl.src = produto.img || PLACEHOLDERS.default;
    tituloEl.textContent = produto.titulo || produto.name || 'Produto';
    descEl.textContent = produto.descricao || produto.description || '';
    
    precoBaseProduto = produto.preco || produto.price || 0;
    precoBaseEl.textContent = formatMoney(precoBaseProduto);
    
    // Reseta estado
    qtdProduto = 1;
    qtdEl.textContent = qtdProduto;
    obsEl.value = '';
    
    // Reseta checkboxes
    document.querySelectorAll('input[name="adicional"]').forEach(el => el.checked = false);
    const proteinas = document.querySelectorAll('input[name="proteina"]');
    if (proteinas.length > 0) {
        proteinas[0].checked = true;
    }
    
    // Mostra modal
    modalEl.classList.add('show');
    
    // Calcula total inicial
    atualizaTotal();
}

// Função auxiliar para formatar dinheiro
function formatMoney(val) {
    return val.toFixed(2).replace('.', ',');
}

window.fecharModalProduto = function() {
    const modalEl = document.getElementById('modalProduto');
    if (modalEl) {
        modalEl.classList.remove('show');
        // Não volta para página anterior se estiver na home
        // setTimeout(() => { history.back(); }, 200);
    }
}

window.adicionarAoCarrinho = function() {
    // Redireciona para o carrinho
    window.location.href = TEMPLATE_PATH + 'carrinho-side-cart.html';
}

window.alteraQtd = function(v) {
    qtdProduto = Math.max(1, qtdProduto + v);
    const qtdEl = document.getElementById('qtdProduto');
    if (qtdEl) qtdEl.textContent = qtdProduto;
    atualizaTotal();
}

// Event delegation para cliques nos cards de pratos
document.addEventListener('click', function(e) {
    const dishCard = e.target.closest('.dish-card');
    if (dishCard && dishCard.dataset.dishName) {
        e.preventDefault();
        e.stopPropagation();
        const dishData = {
            img: dishCard.dataset.dishImage || PLACEHOLDERS.default,
            titulo: dishCard.dataset.dishName || 'Prato',
            descricao: dishCard.dataset.dishDesc || '',
            preco: parseFloat(dishCard.dataset.dishPrice || '0')
        };
        if (window.abrirModalProduto) {
            window.abrirModalProduto(dishData);
        } else {
            console.error('Função abrirModalProduto não encontrada');
        }
    }
});

// Listeners para recalcular preço ao clicar nas opções
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[name="proteina"],input[name="adicional"]').forEach(function(el) {
        el.addEventListener('change', atualizaTotal);
    });
    
    // Inicia com valor atualizado
    if (document.getElementById('precoTotal')) {
        atualizaTotal();
    }
});

document.addEventListener('DOMContentLoaded', initApp);

