/**
 * Service Worker - VemComer PWA
 * Cache Strategy: Híbrida (Network First para API, Cache First para Assets)
 * 
 * @version 1.0.0
 */

const CACHE_NAME = 'vemcomer-pwa-v1';
const APP_SHELL_CACHE = 'vemcomer-appshell-v1';

// App Shell - Recursos críticos para funcionamento offline
const APP_SHELL_URLS = [
    '/',
    '/inicio/',
    '/wp-content/themes/theme-vemcomer/style.css',
    '/wp-content/plugins/vemcomer-core/assets/css/product-modal.css',
    '/wp-content/themes/theme-vemcomer/assets/css/main.css',
];

/**
 * Install Event - Pre-cache do App Shell
 */
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');
    
    event.waitUntil(
        caches.open(APP_SHELL_CACHE)
            .then((cache) => {
                console.log('[SW] Caching App Shell');
                // Cache apenas os recursos críticos do App Shell
                return cache.addAll(APP_SHELL_URLS.map(url => new Request(url, { cache: 'reload' })))
                    .catch((error) => {
                        console.warn('[SW] Failed to cache some App Shell resources:', error);
                        // Não falhar a instalação se alguns recursos não puderem ser cacheados
                    });
            })
            .then(() => {
                // Força a ativação imediata do novo Service Worker
                return self.skipWaiting();
            })
    );
});

/**
 * Activate Event - Limpa caches antigos
 */
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');
    
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        // Remove caches antigos que não correspondem aos atuais
                        if (cacheName !== CACHE_NAME && cacheName !== APP_SHELL_CACHE) {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                // Assume controle de todas as páginas imediatamente
                return self.clients.claim();
            })
    );
});

/**
 * Fetch Event - Estratégia de cache híbrida
 */
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // IGNORAR: Não interceptar requisições de admin ou login
    if (url.pathname.startsWith('/wp-admin/') || 
        url.pathname.startsWith('/wp-login.php') ||
        url.pathname.startsWith('/wp-json/wp/') ||
        url.pathname.includes('preview=true')) {
        return; // Deixa passar sem interceptação
    }
    
    // ESTRATÉGIA 1: API REST (Network First)
    if (url.pathname.startsWith('/wp-json/')) {
        event.respondWith(networkFirstStrategy(request));
        return;
    }
    
    // ESTRATÉGIA 2: Imagens e Assets (Cache First com Stale-While-Revalidate)
    if (isAssetRequest(request)) {
        event.respondWith(cacheFirstStaleWhileRevalidate(request));
        return;
    }
    
    // ESTRATÉGIA 3: Navegação HTML (Network First com fallback)
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(networkFirstWithOfflineFallback(request));
        return;
    }
    
    // ESTRATÉGIA 4: Outros recursos (Network First)
    event.respondWith(networkFirstStrategy(request));
});

/**
 * Verifica se é uma requisição de asset (imagem, CSS, JS, fontes)
 */
function isAssetRequest(request) {
    const url = new URL(request.url);
    const pathname = url.pathname.toLowerCase();
    
    return pathname.match(/\.(jpg|jpeg|png|gif|webp|svg|ico|css|js|woff|woff2|ttf|eot)$/i) !== null ||
           pathname.includes('/wp-content/uploads/') ||
           pathname.includes('/wp-content/themes/') ||
           pathname.includes('/wp-content/plugins/');
}

/**
 * Network First Strategy
 * Tenta rede primeiro, se falhar retorna cache ou erro
 */
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        
        // Se a resposta é válida, cacheia e retorna
        if (networkResponse && networkResponse.status === 200) {
            const cache = await caches.open(CACHE_NAME);
            // Clona a resposta antes de cachear (respostas só podem ser lidas uma vez)
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
        
        // Se a rede falhou, tenta cache
        return await caches.match(request);
    } catch (error) {
        console.warn('[SW] Network failed, trying cache:', error);
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Se não tem cache e está offline, retorna erro JSON para APIs
        if (request.url.includes('/wp-json/')) {
            return new Response(
                JSON.stringify({ 
                    error: 'offline', 
                    message: 'Você está offline. Alguns recursos podem não estar disponíveis.' 
                }),
                {
                    status: 503,
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }
        
        throw error;
    }
}

/**
 * Cache First com Stale-While-Revalidate
 * Retorna cache imediatamente, atualiza em background
 */
async function cacheFirstStaleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await caches.match(request);
    
    // Busca atualização em background
    const fetchPromise = fetch(request).then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(() => {
        // Ignora erros de rede em background
    });
    
    // Retorna cache imediatamente se disponível, senão espera a rede
    return cachedResponse || fetchPromise;
}

/**
 * Network First com Fallback Offline
 * Tenta rede, se falhar retorna App Shell cacheado
 */
async function networkFirstWithOfflineFallback(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse && networkResponse.status === 200) {
            // Cacheia a resposta para uso offline futuro
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
            return networkResponse;
        }
    } catch (error) {
        console.warn('[SW] Network failed for navigation, trying cache:', error);
    }
    
    // Tenta cache específico da requisição
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // Fallback: Retorna App Shell (Home) se estiver offline
    const appShellResponse = await caches.match('/');
    if (appShellResponse) {
        return appShellResponse;
    }
    
    // Último recurso: retorna página offline genérica
    return new Response(
        '<!DOCTYPE html><html><head><title>Offline - VemComer</title></head><body><h1>Você está offline</h1><p>Algumas funcionalidades podem não estar disponíveis.</p></body></html>',
        {
            status: 200,
            headers: { 'Content-Type': 'text/html' }
        }
    );
}

/**
 * Message Handler - Para comunicação com a página
 */
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
    
    if (event.data && event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(CACHE_NAME)
                .then((cache) => {
                    return cache.addAll(event.data.urls);
                })
        );
    }
});

