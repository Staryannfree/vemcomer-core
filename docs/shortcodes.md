# Shortcodes do VemComer

## [vc_filters]
Formulário de filtros (GET) para usar com a grade.

## [vc_restaurants]
Grade de restaurantes.

Parâmetros:
- `cuisine` (slug)
- `location` (slug)
- `delivery` (`true`|`false`)
- `search` (texto)
- `per_page` (número, default 12)
- `page` (número; se não informado, usa `paged` do WP)
- `orderby` (`title`|`date`)
- `order` (`ASC`|`DESC`)

Exemplo:
```text
[vc_filters]
[vc_restaurants per_page="9" orderby="title" order="ASC"]
```

## [vc_restaurant]

Card de um único restaurante. Se `id` não for passado, usa o post atual.

```text
[vc_restaurant id="123"]
```

## [vc_menu_items]

Lista itens do cardápio para um restaurante.
Parâmetros:

* `restaurant_id` (opcional se estiver na single do restaurante)
* `per_page` (default 100)

Exemplos:

```text
[vc_menu_items restaurant_id="123" per_page="50"]
```

```text
[vc_menu_items]
```
