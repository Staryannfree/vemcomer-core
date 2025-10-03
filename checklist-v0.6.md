# Checklist v0.6 – VemComer Core

## Núcleo do Plugin
- [x] Estrutura base do plugin (arquivo principal, `inc/`, `assets/`)
- [x] Definir constante de versão e carregamento de módulos

## Catálogo: Restaurantes
- [x] Registrar CPT `vc_restaurant`
- [x] Registrar taxonomia `vc_cuisine`
- [x] Registrar taxonomia `vc_location`
- [x] Metaboxes de dados (CNPJ, WhatsApp, Site, Horários, Delivery, Endereço)
- [x] Colunas de admin personalizadas
- [x] Seeder WP-CLI com dados de exemplo (`bin/wp-cli-seed-restaurants.php`)
- [x] Mapeamento de capabilities para roles padrão (admin, editor, shop_manager)
- [x] REST API `/vemcomer/v1/restaurants` com filtros (`search`, `cuisine`, `location`, `delivery`, `page`, `per_page`)

## Catálogo: Restaurantes (Fase 2)
- [x] REST de escrita (POST/PATCH) com validação/sanitização e caps

## Qualidade e DX
- [x] PHPCS + WPCS via Composer
- [x] CI (composer install, php -v, vendor/bin/phpcs)
- [ ] Logs e modo debug (wp_debug + hooks de log do plugin)

## Próximos Passos
- [x] Templates de front (arquivo de tema: `archive-vc_restaurant.php`, `single-vc_restaurant.php`)
- [ ] Validação avançada de CNPJ (serviço externo/algoritmo)
- [ ] Painel admin: lista rápida de restaurantes com filtros
- [ ] Integração com métodos de pedido/entrega (futuro)

### Como usar o seeder
```bash
wp vemcomer seed-restaurants --count=8 --force
```

Roda após ativar o plugin e garante termos básicos de `vc_cuisine` e `vc_location`.
