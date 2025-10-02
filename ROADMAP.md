# Vemcomer Core — ROADMAP v0.6+

> Alinhado ao [Checklist de Implementação](./checklist-v0.6.md)

## 🚩 Marcos

### v0.6.0 — Base & Modularização
- Criar /inc (bootstrap, filters, geo, restaurants, checkout, kds, rest, shortcodes, settings)
- Mover funções duplicadas para módulos
- Garantir QA de regressão

### v0.6.1 — Filtros, UX e Cache GEO
- Implementar filtros públicos (`vemcomer_*`)
- Substituir `alert()` por mensagens inline acessíveis
- Implementar página de Settings no WP Admin
- Cache de geocodificação com transients

### v0.6.2 — Regras de Negócio & REST
- Single-seller no carrinho
- Fechado = não comprável
- Frete base + km + grátis > X
- REST API para KDS e Order Tracker
- Integração `assets/kds.js` com REST

### v0.6.3 — Webhooks / Automations / Relatórios
- Hooks de status de pedido
- Webhooks configuráveis (`vemcomer_order_webhook_payload`)
- Integração opcional com Automator
- Relatórios de vendas por restaurante
- Export CSV simples

### v0.6.4 — Acessibilidade, SEO & Performance
- A11y: aria-live, role="status", foco
- SEO: Schema LocalBusiness/Restaurant
- Performance: carregar scripts apenas quando necessários
- Minificação e otimização LCP
