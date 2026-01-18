# Documentação da API REST - VemComer Core

Esta documentação descreve todos os endpoints públicos da REST API do VemComer Core, disponíveis em `/wp-json/vemcomer/v1/`.

## Repositório

- **GitHub**: https://github.com/Staryannfree/vemcomer-core
- **Guia de Integração**: [LOVABLE_INTEGRATION.md](./LOVABLE_INTEGRATION.md)

## Base URL

```
https://pedevem.com/wp-json/vemcomer/v1
```

## Autenticação

A maioria dos endpoints públicos não requer autenticação. Endpoints que requerem autenticação usam o sistema padrão do WordPress (cookies ou Application Passwords).

Para requisições autenticadas, inclua o header:
```
Authorization: Bearer {token}
```

## CORS

A API suporta CORS (Cross-Origin Resource Sharing) para requisições do frontend PWA. As origens permitidas podem ser configuradas via filtro `vemcomer_rest_allowed_origins`.

Por padrão, as seguintes origens são permitidas:
- `http://localhost:3000`
- `http://localhost:5173`
- `http://localhost:8080`
- `http://127.0.0.1:3000`
- `http://127.0.0.1:5173`
- `http://pedevem-local.local` (ambiente local Local by Flywheel)
- `https://hungry-hub-core.lovable.app` (frontend Lovable em produção)
- `https://47191717-b1f5-4559-bdab-f069bc62cec6.lovableproject.com` (frontend Lovable em desenvolvimento)
- `https://periodic-symbol.localsite.io` (Live Link do Local by Flywheel)

## Endpoints

### Restaurantes

#### Listar Restaurantes

```http
GET /wp-json/vemcomer/v1/restaurants
```

**Parâmetros de Query:**
- `cuisine` (string, opcional) - Slug da taxonomia `vc_cuisine` para filtrar por tipo de cozinha
- `delivery` (boolean, opcional) - Filtrar restaurantes com delivery (`true`) ou sem delivery (`false`)
- `has_delivery` (boolean, opcional) - Alias para `delivery`
- `is_open` (boolean, opcional) - Filtrar restaurantes abertos (`true`) ou fechados (`false`)
- `per_page` (integer, opcional) - Número de resultados por página (1-50, padrão: 10)
- `page` (integer, opcional) - Número da página (padrão: 1)
- `search` (string, opcional) - Busca por texto no nome ou descrição do restaurante
- `orderby` (string, opcional) - Ordenação: `title`, `date`, `rating` (padrão: `date`)
- `order` (string, opcional) - Direção: `asc`, `desc` (padrão: `desc`)
- `featured` (boolean, opcional) - Filtrar apenas restaurantes em destaque

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants?per_page=20&orderby=rating&order=desc"
```

**Exemplo de Resposta:**
```json
{
  "data": [
    {
      "id": 123,
      "title": "Restaurante Exemplo",
      "slug": "restaurante-exemplo",
      "description": "Descrição do restaurante",
      "cuisine": "brasileira",
      "has_delivery": true,
      "is_open": true,
      "rating": {
        "average": 4.5,
        "count": 42
      },
      "image": "https://pedevem.com/wp-content/uploads/...",
      "address": "Rua Exemplo, 123",
      "phone": "(62) 99999-9999",
      "whatsapp": "5562999999999"
    }
  ],
  "total": 50,
  "pages": 3,
  "current_page": 1
}
```

**Status HTTP:**
- `200 OK` - Sucesso

---

#### Obter Detalhes de um Restaurante

```http
GET /wp-json/vemcomer/v1/restaurants/{id}
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123"
```

**Exemplo de Resposta:**
```json
{
  "id": 123,
  "title": "Restaurante Exemplo",
  "slug": "restaurante-exemplo",
  "description": "Descrição completa do restaurante",
  "cuisine": "brasileira",
  "has_delivery": true,
  "is_open": true,
  "rating": {
    "average": 4.5,
    "count": 42,
    "formatted": "4.5 (42 avaliações)"
  },
  "image": "https://pedevem.com/wp-content/uploads/...",
  "logo": "https://pedevem.com/wp-content/uploads/...",
  "address": "Rua Exemplo, 123",
  "neighborhood": "Centro",
  "city": "Goiânia",
  "state": "GO",
  "zipcode": "74000-000",
  "phone": "(62) 3333-3333",
  "whatsapp": "5562999999999",
  "email": "contato@restaurante.com",
  "website": "https://restaurante.com",
  "delivery_radius": 5,
  "delivery_time": 45,
  "min_order": 25.00
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

#### Listar Itens do Cardápio de um Restaurante

```http
GET /wp-json/vemcomer/v1/restaurants/{id}/menu-items
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Parâmetros de Query:**
- `category` (string, opcional) - Slug da categoria para filtrar itens
- `featured` (boolean, opcional) - Filtrar apenas itens em destaque
- `per_page` (integer, opcional) - Número de resultados por página
- `page` (integer, opcional) - Número da página

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123/menu-items"
```

**Exemplo de Resposta:**
```json
{
  "data": [
    {
      "id": 456,
      "title": "Prato Exemplo",
      "description": "Descrição do prato",
      "price": 29.90,
      "category": "pratos-principais",
      "image": "https://pedevem.com/wp-content/uploads/...",
      "featured": false,
      "available": true
    }
  ],
  "total": 25,
  "pages": 3
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

#### Listar Categorias do Cardápio de um Restaurante

```http
GET /wp-json/vemcomer/v1/restaurants/{id}/menu-categories
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123/menu-categories"
```

**Exemplo de Resposta:**
```json
{
  "data": [
    {
      "id": 10,
      "name": "Pratos Principais",
      "slug": "pratos-principais",
      "order": 1,
      "image": "https://pedevem.com/wp-content/uploads/...",
      "item_count": 15
    },
    {
      "id": 11,
      "name": "Bebidas",
      "slug": "bebidas",
      "order": 2,
      "image": null,
      "item_count": 8
    }
  ]
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

#### Obter Horários de um Restaurante

```http
GET /wp-json/vemcomer/v1/restaurants/{id}/schedule
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123/schedule"
```

**Exemplo de Resposta:**
```json
{
  "schedule": {
    "monday": {
      "enabled": true,
      "periods": [
        {
          "open": "09:00",
          "close": "14:00"
        },
        {
          "open": "18:00",
          "close": "22:00"
        }
      ]
    },
    "tuesday": {
      "enabled": true,
      "periods": [
        {
          "open": "09:00",
          "close": "22:00"
        }
      ]
    }
  },
  "holidays": [
    "2024-12-25",
    "2025-01-01"
  ],
  "legacy": "Segunda a Sexta: 09:00 - 22:00"
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

#### Verificar se Restaurante está Aberto

```http
GET /wp-json/vemcomer/v1/restaurants/{id}/is-open
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Parâmetros de Query:**
- `timestamp` (integer, opcional) - Timestamp Unix para verificar horário específico (padrão: agora)

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123/is-open"
```

**Exemplo de Resposta (Aberto):**
```json
{
  "is_open": true,
  "current_time": "2024-12-20 15:30:00"
}
```

**Exemplo de Resposta (Fechado):**
```json
{
  "is_open": false,
  "current_time": "2024-12-20 23:30:00",
  "next_open_time": "2024-12-21 09:00:00",
  "next_open_timestamp": 1737360000
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

### Itens do Cardápio

#### Listar Itens do Cardápio

```http
GET /wp-json/vemcomer/v1/menu-items
```

**Parâmetros de Query:**
- `restaurant_id` (integer, opcional) - Filtrar por restaurante
- `category` (string, opcional) - Filtrar por categoria (slug)
- `featured` (boolean, opcional) - Filtrar apenas itens em destaque
- `search` (string, opcional) - Busca por texto
- `per_page` (integer, opcional) - Número de resultados por página
- `page` (integer, opcional) - Número da página

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/menu-items?restaurant_id=123&featured=true"
```

**Status HTTP:**
- `200 OK` - Sucesso

---

#### Obter Modificadores de um Item do Cardápio

```http
GET /wp-json/vemcomer/v1/menu-items/{id}/modifiers
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do item do cardápio

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/menu-items/456/modifiers"
```

**Exemplo de Resposta:**
```json
{
  "data": [
    {
      "id": 789,
      "title": "Tamanho",
      "type": "required",
      "price": 0.00,
      "min": 1,
      "max": 1,
      "options": [
        {
          "id": 1,
          "title": "Pequeno",
          "price": 0.00
        },
        {
          "id": 2,
          "title": "Grande",
          "price": 5.00
        }
      ]
    },
    {
      "id": 790,
      "title": "Adicionais",
      "type": "optional",
      "price": 0.00,
      "min": 0,
      "max": 5,
      "options": [
        {
          "id": 3,
          "title": "Bacon",
          "price": 3.00
        },
        {
          "id": 4,
          "title": "Queijo Extra",
          "price": 2.50
        }
      ]
    }
  ]
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Item não encontrado

---

### Cotação de Frete

#### Obter Cotação de Frete

```http
GET /wp-json/vemcomer/v1/shipping/quote
```

**Parâmetros de Query:**
- `restaurant_id` (integer, obrigatório) - ID do restaurante
- `subtotal` (float, obrigatório) - Valor do subtotal do pedido
- `lat` (float, opcional) - Latitude do endereço de entrega
- `lng` (float, opcional) - Longitude do endereço de entrega
- `address` (string, opcional) - Endereço completo
- `neighborhood` (string, opcional) - Bairro

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/shipping/quote?restaurant_id=123&subtotal=49.90&lat=-16.6864&lng=-49.2643"
```

**Exemplo de Resposta:**
```json
{
  "restaurant_id": 123,
  "subtotal": 49.90,
  "is_open": true,
  "distance": 2.5,
  "within_radius": true,
  "radius": 5,
  "methods": [
    {
      "id": "flat_rate_delivery",
      "name": "Entrega Padrão",
      "price": 9.90,
      "eta": 45,
      "available": true
    },
    {
      "id": "distance_based_delivery",
      "name": "Entrega por Distância",
      "price": 12.50,
      "eta": 50,
      "available": true
    }
  ]
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `400 Bad Request` - Parâmetros inválidos
- `404 Not Found` - Restaurante não encontrado

---

### Avaliações e Ratings

#### Listar Avaliações de um Restaurante

```http
GET /wp-json/vemcomer/v1/restaurants/{id}/reviews
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Parâmetros de Query:**
- `per_page` (integer, opcional) - Número de resultados por página (1-50, padrão: 10)
- `page` (integer, opcional) - Número da página (padrão: 1)

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123/reviews?per_page=10&page=1"
```

**Exemplo de Resposta:**
```json
{
  "data": [
    {
      "id": 100,
      "restaurant_id": 123,
      "customer_name": "João Silva",
      "rating": 5,
      "comment": "Excelente restaurante! Comida deliciosa e atendimento impecável.",
      "date": "2024-12-15 20:30:00",
      "order_id": 500,
      "verified": true
    }
  ],
  "total": 42,
  "pages": 5,
  "current_page": 1
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

#### Obter Rating Agregado de um Restaurante

```http
GET /wp-json/vemcomer/v1/restaurants/{id}/rating
```

**Parâmetros de URL:**
- `id` (integer, obrigatório) - ID do restaurante

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/restaurants/123/rating"
```

**Exemplo de Resposta:**
```json
{
  "restaurant_id": 123,
  "average": 4.5,
  "count": 42,
  "formatted": "4.5 (42 avaliações)",
  "distribution": {
    "5": 20,
    "4": 15,
    "3": 5,
    "2": 1,
    "1": 1
  }
}
```

**Status HTTP:**
- `200 OK` - Sucesso
- `404 Not Found` - Restaurante não encontrado

---

#### Criar Avaliação

```http
POST /wp-json/vemcomer/v1/reviews
```

**Autenticação:** Obrigatória

**Body (JSON):**
```json
{
  "restaurant_id": 123,
  "rating": 5,
  "comment": "Excelente restaurante!",
  "order_id": 500
}
```

**Parâmetros:**
- `restaurant_id` (integer, obrigatório) - ID do restaurante
- `rating` (integer, obrigatório) - Nota de 1 a 5
- `comment` (string, opcional) - Comentário da avaliação
- `order_id` (integer, opcional) - ID do pedido relacionado

**Status HTTP:**
- `201 Created` - Avaliação criada com sucesso
- `400 Bad Request` - Parâmetros inválidos
- `401 Unauthorized` - Não autenticado
- `404 Not Found` - Restaurante não encontrado

---

### Banners

#### Listar Banners

```http
GET /wp-json/vemcomer/v1/banners
```

**Parâmetros de Query:**
- `restaurant_id` (integer, opcional) - Filtrar banners de um restaurante específico

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/banners"
```

**Exemplo de Resposta:**
```json
{
  "data": [
    {
      "id": 200,
      "title": "Promoção Especial",
      "image": "https://pedevem.com/wp-content/uploads/...",
      "link": "https://pedevem.com/promocao",
      "restaurant_id": null,
      "order": 1
    }
  ]
}
```

**Status HTTP:**
- `200 OK` - Sucesso

---

### Categorias de Cardápio

#### Listar Categorias de Cardápio

```http
GET /wp-json/vemcomer/v1/menu-categories
```

**Parâmetros de Query:**
- `restaurant_id` (integer, opcional) - Filtrar categorias de um restaurante específico

**Exemplo de Requisição:**
```bash
curl "https://pedevem.com/wp-json/vemcomer/v1/menu-categories"
```

**Status HTTP:**
- `200 OK` - Sucesso

---

## Códigos de Status HTTP

- `200 OK` - Requisição bem-sucedida
- `201 Created` - Recurso criado com sucesso
- `400 Bad Request` - Parâmetros inválidos
- `401 Unauthorized` - Não autenticado
- `403 Forbidden` - Sem permissão
- `404 Not Found` - Recurso não encontrado
- `500 Internal Server Error` - Erro no servidor

## Tratamento de Erros

Todas as respostas de erro seguem o formato:

```json
{
  "code": "vc_error_code",
  "message": "Mensagem de erro legível",
  "data": {
    "status": 400
  }
}
```

## Paginação

Endpoints que retornam listas suportam paginação através dos parâmetros `per_page` e `page`. A resposta inclui metadados de paginação:

```json
{
  "data": [...],
  "total": 100,
  "pages": 10,
  "current_page": 1,
  "per_page": 10
}
```

## Rate Limiting

A API pode implementar rate limiting para prevenir abuso. Em caso de limite excedido, a resposta será:

```json
{
  "code": "vc_rate_limit_exceeded",
  "message": "Muitas requisições. Tente novamente mais tarde.",
  "data": {
    "status": 429,
    "retry_after": 60
  }
}
```

## Notas

- Todos os valores monetários são retornados como números decimais (float)
- Datas são retornadas no formato ISO 8601: `YYYY-MM-DD HH:MM:SS`
- URLs de imagens são absolutas e apontam para o servidor WordPress
- O campo `slug` está disponível em todos os recursos que possuem permalink
