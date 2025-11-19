# Desenvolvimento — PHPCS/CI/REST/Templates

## PHPCS local
```bash
composer install
vendor/bin/phpcs -q
# (opcional) vendor/bin/phpcbf para auto-fix quando possível
```

## Testes rápidos REST (escrita)

```bash
# criar
curl -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{
    "title":"Rest Demo API",
    "cnpj":"00.000.000/00001-00",
    "whatsapp":"+55 11 90000-0000",
    "site":"https://exemplo.test",
    "open_hours":"Seg-Dom 11:00–23:00",
    "delivery":true,
    "address":"Rua Teste, 100",
    "cuisine":"pizza",
    "location":"centro"
  }' \
  https://SEU.DOMINIO/wp-json/vemcomer/v1/restaurants

# atualizar
curl -X PATCH \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <TOKEN>" \
  -d '{ "title":"Nome Atualizado" }' \
  https://SEU.DOMINIO/wp-json/vemcomer/v1/restaurants/123
```

## QA manual — CNPJ do restaurante

1. Abra **Restaurantes ▸ Adicionar novo** (ou edite um existente) e tente salvar com `00.000.000/0000-00`.
   - O metabox deve impedir o salvamento, mantendo o CNPJ anterior, e exibir um aviso em vermelho com a mensagem de erro.
2. Atualize o campo para um CNPJ válido (ex.: `04.252.011/0001-10`) e salve novamente.
   - O aviso desaparece e o valor normalizado (apenas dígitos) é persistido normalmente na meta `vc_restaurant_cnpj`.

## QA manual — Configurações (Settings ▸ VemComer)

1. Acesse **Settings ▸ VemComer**, preencha todos os campos obrigatórios (Tiles URL, raio padrão, KDS poll e frete base/km/grátis) e salve.
   - Recarregue a página para confirmar que os valores persistem em `vemcomer_settings` (sem reset).
2. Informe valores inválidos (texto em campos numéricos, números negativos, URLs vazias) e salve novamente.
   - Os valores devem ser higienizados, retomando o default quando o dado não é aceitável.
3. Execute `wp shell -r 'var_dump( vc_tiles_url(), vc_default_radius(), vc_kds_poll_interval(), vc_default_freight_base(), vc_default_freight_per_km(), vc_freight_free_above() );'`.
   - Todos os helpers devem refletir os novos valores antes de recorrer aos defaults/filtros.
4. Abra um shortcode com mapas (ex.: página que carrega Leaflet) e um painel KDS.
   - O JS deve usar a URL configurada (`VC_EXPLORE_MAP.tiles` / `VC_RESTAURANT_MAP.tiles`) e o polling do KDS precisa respeitar o intervalo salvo (`VC_KDS.poll`).
