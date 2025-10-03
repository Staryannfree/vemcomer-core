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
