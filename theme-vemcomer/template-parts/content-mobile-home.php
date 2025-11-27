<!-- LOCATION PERMISSION MODAL (Obrigatório) -->
<div class="location-modal" id="locationModal">
    <div class="location-modal-content">
        <div class="location-modal-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
            </svg>
        </div>
        <h2 class="location-modal-title">Precisamos da sua localização</h2>
        <p class="location-modal-text">
            Para mostrar os melhores estabelecimentos perto de você, precisamos acessar sua localização.
        </p>
        <button class="location-modal-btn" id="locationModalBtn">
            <span class="btn-text">Permitir Localização</span>
            <span class="btn-loader" style="display: none;">
                <svg class="spinner" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                        <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416;0 31.416" repeatCount="indefinite"/>
                        <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416;-31.416" repeatCount="indefinite"/>
                    </circle>
                </svg>
            </span>
        </button>
        <p class="location-modal-error" id="locationModalError" style="display: none;"></p>
    </div>
</div>

<!-- TOP BAR -->
<header class="top-bar">
    <div class="logo">Pedevem</div>
    <div class="location-selector" id="locationSelector">
        <svg class="location-icon" viewBox="0 0 24 24">
            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
        </svg>
        <span class="location-text" id="locationText">Endereço</span>
        <span>▾</span>
    </div>
    <button class="notification-btn" id="notificationBtn">
        <svg class="notification-icon" viewBox="0 0 24 24">
            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
        </svg>
        <span class="notification-badge">3</span>
    </button>
</header>

<!-- HERO BANNER CAROUSEL -->
<!-- Os banners serão carregados dinamicamente via JavaScript da API -->
<section class="hero-banner-section">
    <div class="banner-carousel" id="bannerCarousel">
        <!-- Banners serão inseridos aqui via JavaScript -->
    </div>
    <div class="banner-dots" id="bannerDots">
        <!-- Dots serão inseridos aqui via JavaScript -->
    </div>
</section>

<!-- ============ STORIES SECTION ============ -->
<section class="stories-section">
    <div class="stories-scroll" id="storiesScroll">
        <!-- Stories will be rendered here -->
    </div>
</section>

<!-- STORY VIEWER MODAL -->
<div class="story-viewer" id="storyViewer">
    <div class="story-progress-bars" id="storyProgressBars">
        <!-- Progress bars will be rendered here -->
    </div>
    
    <div class="story-header">
        <div class="story-header-left">
            <img src="" alt="Avatar" class="story-header-avatar" id="storyHeaderAvatar">
            <div class="story-header-info">
                <div class="story-header-name" id="storyHeaderName"></div>
                <div class="story-header-time" id="storyHeaderTime"></div>
            </div>
        </div>
        <button class="story-close-btn" id="storyCloseBtn">
            <svg class="story-close-icon" viewBox="0 0 24 24">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    
    <div class="story-content" id="storyContent">
        <img src="" alt="Story" class="story-media" id="storyMedia">
        <div class="story-tap-areas">
            <div class="story-tap-left" id="storyTapLeft"></div>
            <div class="story-tap-right" id="storyTapRight"></div>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="quick-actions">
    <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/busca-avancada.html" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M18 18.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5-1.5.67-1.5 1.5.67 1.5 1.5 1.5zm1.5-9H17V12h4.46L19.5 9.5zM6 18.5c.83 0 1.5-.67 1.5-1.5s-.67-1.5-1.5-1.5-1.5.67-1.5 1.5.67 1.5 1.5 1.5zM20 8l3 4v5h-2c0 1.66-1.34 3-3 3s-3-1.34-3-3H9c0 1.66-1.34 3-3 3s-3-1.34-3-3H1V6c0-1.11.89-2 2-2h14v4h3zM3 6v9h.76c.55-.61 1.35-1 2.24-1s1.69.39 2.24 1H15V6H3z"/>
        </svg>
        <span class="quick-action-label">Delivery</span>
    </a>
    
    <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/minhas-reservas.html" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/>
        </svg>
        <span class="quick-action-label">Reservas</span>
    </a>
    
    <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/feed-eventos.html" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/>
        </svg>
        <span class="quick-action-label">Eventos</span>
    </a>
    
    <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/todas-as-categorias.html" class="quick-action-btn">
        <svg class="quick-action-icon" viewBox="0 0 24 24">
            <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
        </svg>
        <span class="quick-action-label">Promoções</span>
    </a>
</div>

<!-- SEARCH BAR -->
<div class="search-container">
    <div class="search-box">
        <svg class="search-icon" viewBox="0 0 24 24">
            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        <input type="search" class="search-input" placeholder="Buscar restaurantes, pratos ou eventos" id="searchInput" autocomplete="off">
        <button class="filter-btn" id="filterBtn">
            <svg class="filter-icon" viewBox="0 0 24 24">
                <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/>
            </svg>
        </button>
    </div>
    <!-- SEARCH RESULTS DROPDOWN -->
    <div class="search-results" id="searchResults" style="display: none;">
        <div class="search-results-content" id="searchResultsContent">
            <!-- Resultados serão inseridos aqui via JavaScript -->
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<main class="content">
    <!-- PRATOS DO DIA -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M8.1 13.34l2.83-2.83L3.91 3.5c-1.56 1.56-1.56 4.09 0 5.66l4.19 4.18zm6.78-1.81c1.53.71 3.68.21 5.27-1.38 1.91-1.91 2.28-4.65.81-6.12-1.46-1.46-4.2-1.1-6.12.81-1.59 1.59-2.09 3.74-1.38 5.27L3.7 19.87l1.41 1.41L12 14.41l6.88 6.88 1.41-1.41L13.41 13l1.47-1.47z"/>
                </svg>
                Pratos do Dia
            </h2>
            <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/busca-avancada.html" class="section-link">
                Ver todos
                <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                </svg>
            </a>
        </div>
        <div class="dishes-scroll" id="dishesScroll"></div>
    </section>

    <!-- PROGRAMAÇÕES DE HOJE -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M12 3v9.28c-.47-.17-.97-.28-1.5-.28C8.01 12 6 14.01 6 16.5S8.01 21 10.5 21c2.31 0 4.2-1.75 4.45-4H15V6h4V3h-7z"/>
                </svg>
                Programações de Hoje
            </h2>
            <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/feed-eventos.html" class="section-link">
                Ver agenda
                <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                </svg>
            </a>
        </div>
        <div class="events-scroll" id="eventsScroll"></div>
    </section>

    <!-- RESTAURANTES EM DESTAQUE -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                </svg>
                Restaurantes em Destaque
            </h2>
            <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/busca-avancada.html" class="section-link">
                Ver todos
                <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                </svg>
            </a>
        </div>
        <div class="featured-grid" id="featuredGrid"></div>
    </section>

    <!-- TODOS OS RESTAURANTES -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title">
                <svg class="section-icon" viewBox="0 0 24 24">
                    <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
                </svg>
                Todos os Restaurantes
            </h2>
        </div>
        <div class="restaurants-grid" id="restaurantsGrid"></div>
    </section>
</main>

<!-- CART BUTTON -->
<button class="cart-btn" id="cartBtn">
    <svg class="cart-icon" viewBox="0 0 24 24">
        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
    </svg>
    <span class="cart-badge" id="cartBadge">0</span>
</button>

<!-- BOTTOM NAVIGATION -->
<nav class="bottom-nav">
    <div class="nav-items">
        <a href="/?mode=app" class="nav-item active">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
            </svg>
            <span>Início</span>
        </a>
        
        <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/busca-avancada.html" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
            </svg>
            <span>Buscar</span>
        </a>
        
        <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/todas-as-categorias.html" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V9h14v10zm0-12H5V5h14v2zM7 11h5v5H7z"/>
            </svg>
            <span>Categorias</span>
        </a>
        
        <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/meus-pedidos.html" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
            </svg>
            <span>Pedidos</span>
        </a>
        
        <a href="/wp-content/themes/theme-vemcomer/templates/marketplace/minha-conta-cliente.html" class="nav-item">
            <svg class="nav-icon" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
            <span>Perfil</span>
        </a>
    </div>
</nav>

<!-- MODAL DETALHES PRODUTO -->
<div id="modalProduto">
    <div class="prod-modal-content">
                <button class="prod-close" onclick="window.fecharModalProduto()">×</button>
        <img id="imgProduto" class="prod-img" src="" alt="Foto Produto">
        <div class="prod-details">
            <div id="tituloProduto" class="prod-title"></div>
            <div id="descProduto" class="prod-desc"></div>
            <div class="prod-price">R$ <span id="precoBase"></span></div>
            <!-- Modificadores obrigatórios -->
            <div class="prod-mod-group">
                <b>Escolha uma Proteína <span style="color:#ea5252">*</span></b><br>
                <label><input type="radio" name="proteina" checked> Tofu Crocante (+R$ 3,00)</label>
                <label><input type="radio" name="proteina"> Grão-de-Bico (incluso)</label>
            </div>
            <!-- Modificadores opcionais -->
            <div class="prod-mod-group">
                <b>Adicionais</b><br>
                <label><input type="checkbox" name="adicional"> Tomate seco (+R$ 2,00)</label>
                <label><input type="checkbox" name="adicional"> Queijo vegano (+R$ 3,00)</label>
            </div>
            <!-- Observação -->
            <div>
                <textarea id="obsProduto" placeholder="Observação para o restaurante..."></textarea>
            </div>
            <!-- Contador quantidade e botão -->
            <div class="prod-actions">
                <div class="prod-qtd-btns">
                    <button type="button" onclick="window.alteraQtd(-1)">-</button>
                    <span id="qtdProduto">1</span>
                    <button type="button" class="plus" onclick="window.alteraQtd(1)">+</button>
                </div>
                <button id="btnAddCarrinho" onclick="window.location.href='/wp-content/themes/theme-vemcomer/templates/marketplace/carrinho-side-cart.html'">Adicionar <span id="precoTotal"></span></button>
            </div>
        </div>
    </div>
</div>

