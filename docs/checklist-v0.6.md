# Vemcomer Core — Checklist de Implementação (v0.6+)

> Diretrizes fixas: ✅ Pagamento 100% offline · ✅ Single-seller · ✅ Geo/horários respeitados

## 🔖 Marcos
- [ ] **v0.6.0 – Base & modularização**
- [ ] **v0.6.1 – Filtros, UX e cache GEO**
- [ ] **v0.6.2 – Regras de negócio & REST**
- [ ] **v0.6.3 – Webhooks/Automations & Relatórios**
- [ ] **v0.6.4 – A11y, SEO & Performance**

---
 
## Qualidade e DX
- [x] ESLint/Stylelint configurados
- [x] CI: job de lint JS/CSS

## Front
- [x] Shortcodes de listagem e card (restaurants)
- [x] Shortcode de filtros integrado
- [x] Shortcode de menu (itens do cardápio)
- [x] CSS dos shortcodes com enqueue condicional
## Segurança & Observabilidade
- [x] Rate limiting nas rotas REST
- [x] Auditoria de ações (CPT vc_audit)

## Catálogo: Restaurantes
- [x] Delete em cascata (restaurante → itens de cardápio)

## Catálogo: Restaurantes (Fase 2)
- [x] REST de escrita (POST/PATCH) + DELETE com caps

## Admin UX
- [x] Filtros na listagem do CPT (cozinha, bairro, delivery)
- [x] Export CSV a partir da listagem
- [x] Colunas ordenáveis e ajustes visuais

## 0) Higiene e fundação
- [ ] Blindar `define()` de constantes (URL/PATH/VERSION) com `if (!defined(...))`
- [ ] `require_once` de módulos fora de `if` de versão
- [ ] Um único plugin ativo (evitar *Cannot redeclare*/duplicidades)
- [ ] Ativar `WP_DEBUG` + `WP_DEBUG_LOG` (sem display) e validar `wp-content/debug.log`
- [ ] Bump de versão ao alterar JS/CSS (`Version:` + `VEMCOMER_CORE_VERSION`)

---

## 1) Modularização (/inc) — v0.6.0
- [ ] **/inc/bootstrap.php** — registrar assets (não enfileirar global)
- [ ] **/inc/filters.php** — filtros públicos:
  - [ ] `vemcomer_kds_poll`
  - [ ] `vemcomer_tiles_url`
  - [ ] `vemcomer_default_radius`
  - [ ] `vemcomer_checkout_labels`
  - [ ] `vemcomer_order_webhook_payload`
- [ ] **/inc/geo.php** — `vc_geo_cache_get/set`, `vc_haversine_km` (com `function_exists`)
- [ ] **/inc/restaurants.php** — helpers: aceitar pedidos, horários, raio
- [ ] **/inc/checkout.php** — campos, validações, fees, single-seller
- [ ] **/inc/kds.php** — `wp_localize_script` (VC_KDS: rest, nonce, rid, poll)
- [ ] **/inc/rest.php** — rotas REST (orders list + mutate status)
- [ ] **/inc/shortcodes.php** — [vc_explore], [vc_restaurant_menu], [vc_kds], etc.
- [ ] **/inc/settings.php** — página de opções no Admin
- [ ] Incluir todos os `require_once` no arquivo principal
- [ ] QA de regressão: explorar, restaurante, checkout GEO, KDS, favoritos, histórico

---

## 2) Regras de negócio (WooCommerce) — v0.6.2
### 2.1 Single-seller
- [ ] Validação `woocommerce_add_to_cart_validation` impede misturar restaurantes
- [ ] Mensagem clara ao usuário (notice de erro)
- [ ] Testes: carrinho com itens A → tentar adicionar B = bloqueado

### 2.2 Fechado = não comprável
- [ ] `woocommerce_is_purchasable` retorna `false` se loja pausada ou fora do horário
- [ ] UI: badge “Fechado” + CTA desabilitado/aviso
- [ ] Testes: simular horário fechado → impedir add-to-cart

### 2.3 Frete (base + km + grátis > X)
- [ ] Calcular distância com `vc_haversine_km` (restaurante ↔ cliente)
- [ ] `woocommerce_cart_calculate_fees` adiciona taxa conforme regras
- [ ] Respeitar frete grátis acima de X
- [ ] Testes com distâncias 0 / média / fora do raio

---

## 3) GEO & Mapas
- [ ] **Explorar**: GPS (`assets/explore.js`) e markers (`assets/explore-map.js`)
- [ ] **Restaurante**: mapa único (`assets/restaurant-map.js`)
- [ ] **Checkout**: GPS (`assets/checkout-geo.js`) + Buscar endereço + reverse (`assets/geo-address.js`)
- [ ] **Cache GEO**: usar `vc_geo_cache_*` para consultas Nominatim (por query e por lat:lng)
- [ ] **Tiles**: URL configurável por filtro/opção (Mapbox/OSM)
- [ ] Enqueue condicional: carregar Leaflet/mapas só onde necessário

---

## 4) KDS & REST — v0.6.2
- [ ] REST: `GET /vemcomer/v1/orders?rid=` (lista por status)
- [ ] REST: `POST /vemcomer/v1/orders/{id}/status` (confirm/prepare/out/delivered/cancel)
- [ ] `assets/kds.js` consome `VC_KDS` (rest, nonce, rid, poll)
- [ ] Poll configurável (`vemcomer_kds_poll`)
- [ ] Beep opcional (toggle)
- [ ] Testes ponta-a-ponta: criar pedido → mudar status no KDS → refletir no pedido

---

## 5) Checkout & UX — v0.6.1
- [ ] Substituir `alert()` por mensagens inline (aria-live) no checkout e explorar
- [ ] Campos extras: forma de pagamento offline, troco, observações
- [ ] Validação: método entrega/retirada; endereço/lat-lng obrigatórios quando entrega
- [ ] Persistir lat/lng na sessão (`woocommerce_after_checkout_validation`)
- [ ] Texto claro de pagamento **na entrega** (Pix/cartão/dinheiro)

---

## 6) Settings (Admin) — v0.6.1
- [ ] Página `Configurações → VemComer`
- [ ] Campos:
  - [ ] Tiles URL
  - [ ] Raio padrão (km)
  - [ ] KDS Poll (ms)
  - [ ] Frete base
  - [ ] Frete por km
  - [ ] Frete grátis acima de
  - [ ] Textos de pagamento offline (checkout)
- [ ] `update_option()` + saneamento; carregamento com defaults via filtros

---

## 7) Favoritos & Histórico
- [ ] Conferir shortcodes `[vc_favorites]`, `[vc_favorite_button]`, `[vc_customer_history]`
- [ ] Garantir segurança (nonce) e redirecionamento pós-ação
- [ ] Otimizar queries (ordenar por `post__in`, limitar itens)

---

## 8) Automations/Webhooks — v0.6.3
- [ ] Hooks internos em mudanças de status de pedido (`woocommerce_order_status_changed`)
- [ ] Filtro `vemcomer_order_webhook_payload` para customização do payload
- [ ] Integração opcional com Automator/SMClick (sem gateway online)
- [ ] Logs de envio e idempotência básica

---

## 9) Relatórios — v0.6.3
- [ ] Sumários (admin/seller): pedidos por período, ticket médio, top itens
- [ ] Export CSV simples
- [ ] Restringir por restaurante (seller só vê o seu)

---

## 10) Acessibilidade & SEO — v0.6.4
- [ ] Mensagens com `role="status"`/`aria-live="polite"`
- [ ] Foco ao abrir feedback/erros
- [ ] Schema: Organization, LocalBusiness/Restaurant, Product
- [ ] Titles/Meta básicos nas páginas-chave

---

## 11) Performance — v0.6.4
- [ ] Carregar scripts apenas quando necessários (shortcodes)
- [ ] Minificar JS/CSS (build simples ou `.min` estático)
- [ ] LCP < 2,5s em páginas principais
- [ ] Revisão de polling KDS × tráfego (avaliar SSE/WebSocket futuramente)

---

## 12) DevX & CI
- [ ] README com “onde mexer” + “como validar”
- [ ] `docs/` com guia de edição e testes de aceitação
- [ ] GitHub Actions (lint/PHPCS básico) — opcional
- [ ] Fluxo WP Pusher (Push-to-Deploy) documentado

---

## 13) Testes de aceitação (sempre)
- [ ] Pedido completo (explorar → menu → add to cart → checkout)
- [ ] Single-seller: impedir mistura
- [ ] Fechado: não comprável
- [ ] GEO: GPS + busca por endereço atualizam lat/lng e taxa
- [ ] KDS: pedidos aparecem e mudam de coluna ao alterar status
- [ ] Logs: `wp-content/debug.log` sem fatals/warnings recorrentes

---

## 🔁 Depois de cada entrega
- [ ] Bump de versão (`Version:` + `VEMCOMER_CORE_VERSION`)
- [ ] Commit em `main`
- [ ] WP Admin → WP Pusher → **Update plugin**
- [ ] Limpar cache; se preciso, desativar/ativar plugin
