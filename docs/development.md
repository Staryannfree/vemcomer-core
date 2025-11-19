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
