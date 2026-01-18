# Guia de Integração: Frontend PWA (Lovable) ↔ WordPress REST API

Este guia descreve como integrar o frontend PWA desenvolvido no Lovable com a REST API do WordPress (VemComer Core).

## Repositório

- **GitHub**: https://github.com/Staryannfree/vemcomer-core
- **Documentação da API**: [API_ENDPOINTS.md](../docs/API_ENDPOINTS.md)
- **Base URL da API**: `https://pedevem.com/wp-json/vemcomer/v1`

## Configuração Inicial

### Variáveis de Ambiente

Crie um arquivo `.env` na raiz do projeto Lovable com as seguintes variáveis:

```env
# Desenvolvimento Local (Local by Flywheel)
REACT_APP_API_URL=http://pedevem-local.local

# OU Produção
# REACT_APP_API_URL=https://pedevem.com

# Base path da API REST
REACT_APP_API_BASE=/wp-json/vemcomer/v1

# URL completa da API (gerada automaticamente)
# REACT_APP_API_FULL_URL=${REACT_APP_API_URL}${REACT_APP_API_BASE}
```

**Nota:** Para desenvolvimento local com Local by Flywheel, use:
```env
REACT_APP_API_URL=http://pedevem-local.local
```

**Nota:** Para produção, use:
```env
REACT_APP_API_URL=https://pedevem.com
```

### Configuração de CORS

O WordPress já está configurado para aceitar requisições do frontend. Se você precisar adicionar uma nova origem, use o filtro WordPress:

```php
add_filter( 'vemcomer_rest_allowed_origins', function( $origins ) {
    $origins[] = 'https://seu-frontend.com';
    return $origins;
} );
```

## Cliente API (Fetch Wrapper)

### Implementação Básica

Crie um arquivo `src/api/client.js` (ou `src/api/client.ts` para TypeScript):

```javascript
// Para desenvolvimento local: http://pedevem-local.local
// Para produção: https://pedevem.com
const API_URL = process.env.REACT_APP_API_URL || process.env.VITE_API_URL || 'http://pedevem-local.local';
const API_BASE = process.env.REACT_APP_API_BASE || '/wp-json/vemcomer/v1';
const API_FULL_URL = `${API_URL}${API_BASE}`;

/**
 * Cliente API para comunicação com WordPress REST API
 */
class ApiClient {
  constructor() {
    this.baseURL = API_FULL_URL;
    this.headers = {
      'Content-Type': 'application/json',
    };
  }

  /**
   * Adiciona token de autenticação (se necessário)
   */
  setAuthToken(token) {
    if (token) {
      this.headers['Authorization'] = `Bearer ${token}`;
    } else {
      delete this.headers['Authorization'];
    }
  }

  /**
   * Faz uma requisição HTTP
   */
  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;
    const config = {
      ...options,
      headers: {
        ...this.headers,
        ...options.headers,
      },
    };

    try {
      const response = await fetch(url, config);
      
      // Verifica se a resposta é JSON
      const contentType = response.headers.get('content-type');
      const isJson = contentType && contentType.includes('application/json');
      
      let data;
      if (isJson) {
        data = await response.json();
      } else {
        data = await response.text();
      }

      // Se não for sucesso, lança erro
      if (!response.ok) {
        throw new ApiError(
          data.message || 'Erro na requisição',
          response.status,
          data
        );
      }

      return data;
    } catch (error) {
      if (error instanceof ApiError) {
        throw error;
      }
      
      // Erro de rede ou outro erro
      throw new ApiError(
        'Erro de conexão. Verifique sua internet.',
        0,
        { originalError: error.message }
      );
    }
  }

  /**
   * GET request
   */
  async get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;
    return this.request(url, { method: 'GET' });
  }

  /**
   * POST request
   */
  async post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'POST',
      body: JSON.stringify(data),
    });
  }

  /**
   * PATCH request
   */
  async patch(endpoint, data = {}) {
    return this.request(endpoint, {
      method: 'PATCH',
      body: JSON.stringify(data),
    });
  }

  /**
   * DELETE request
   */
  async delete(endpoint) {
    return this.request(endpoint, { method: 'DELETE' });
  }
}

/**
 * Classe de erro customizada para API
 */
class ApiError extends Error {
  constructor(message, status, data = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.data = data;
  }
}

// Exporta instância singleton
export const apiClient = new ApiClient();
export { ApiError };
```

### Uso do Cliente API

```javascript
import { apiClient } from './api/client';

// Listar restaurantes
const restaurants = await apiClient.get('/restaurants', {
  per_page: 20,
  orderby: 'rating',
  order: 'desc'
});

// Obter detalhes de um restaurante
const restaurant = await apiClient.get('/restaurants/123');

// Listar itens do cardápio
const menuItems = await apiClient.get('/restaurants/123/menu-items');

// Obter modificadores de um item
const modifiers = await apiClient.get('/menu-items/456/modifiers');

// Criar avaliação (requer autenticação)
apiClient.setAuthToken('seu-token-aqui');
await apiClient.post('/reviews', {
  restaurant_id: 123,
  rating: 5,
  comment: 'Excelente restaurante!'
});
```

## Estrutura de Dados

### Restaurante

```typescript
interface Restaurant {
  id: number;
  title: string;
  slug: string;
  description: string;
  cuisine: string;
  has_delivery: boolean;
  is_open: boolean;
  rating: {
    average: number;
    count: number;
    formatted?: string;
  };
  image: string | null;
  logo: string | null;
  address: string;
  neighborhood: string;
  city: string;
  state: string;
  zipcode: string;
  phone: string;
  whatsapp: string;
  email?: string;
  website?: string;
  delivery_radius?: number;
  delivery_time?: number;
  min_order?: number;
}
```

### Item do Cardápio

```typescript
interface MenuItem {
  id: number;
  title: string;
  description: string;
  price: number;
  category: string;
  image: string | null;
  featured: boolean;
  available: boolean;
  restaurant_id: number;
  restaurant_slug: string;
}
```

### Modificador

```typescript
interface Modifier {
  id: number;
  title: string;
  type: 'required' | 'optional';
  price: number;
  min: number;
  max: number;
  options: ModifierOption[];
}

interface ModifierOption {
  id: number;
  title: string;
  price: number;
}
```

### Avaliação

```typescript
interface Review {
  id: number;
  restaurant_id: number;
  customer_name: string;
  rating: number; // 1-5
  comment: string;
  date: string;
  order_id?: number;
  verified: boolean;
}
```

### Cotação de Frete

```typescript
interface ShippingQuote {
  restaurant_id: number;
  subtotal: number;
  is_open: boolean;
  distance?: number;
  within_radius?: boolean;
  radius?: number;
  methods: ShippingMethod[];
}

interface ShippingMethod {
  id: string;
  name: string;
  price: number;
  eta: number; // minutos
  available: boolean;
}
```

## Tratamento de Erros

### Hook React para Tratamento de Erros

```javascript
import { useState, useEffect } from 'react';
import { apiClient, ApiError } from './api/client';

function useApi(endpoint, params = {}) {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    async function fetchData() {
      try {
        setLoading(true);
        setError(null);
        const result = await apiClient.get(endpoint, params);
        setData(result);
      } catch (err) {
        if (err instanceof ApiError) {
          setError({
            message: err.message,
            status: err.status,
            data: err.data,
          });
        } else {
          setError({
            message: 'Erro desconhecido',
            status: 0,
          });
        }
      } finally {
        setLoading(false);
      }
    }

    fetchData();
  }, [endpoint, JSON.stringify(params)]);

  return { data, loading, error };
}

// Uso
function RestaurantsList() {
  const { data, loading, error } = useApi('/restaurants', {
    per_page: 20
  });

  if (loading) return <div>Carregando...</div>;
  if (error) return <div>Erro: {error.message}</div>;
  if (!data) return null;

  return (
    <div>
      {data.data.map(restaurant => (
        <div key={restaurant.id}>{restaurant.title}</div>
      ))}
    </div>
  );
}
```

### Tratamento de Erros Específicos

```javascript
try {
  const restaurant = await apiClient.get('/restaurants/123');
} catch (error) {
  if (error instanceof ApiError) {
    switch (error.status) {
      case 404:
        console.error('Restaurante não encontrado');
        break;
      case 400:
        console.error('Parâmetros inválidos:', error.data);
        break;
      case 401:
        console.error('Não autenticado. Faça login novamente.');
        break;
      case 429:
        console.error('Muitas requisições. Aguarde um momento.');
        break;
      default:
        console.error('Erro:', error.message);
    }
  }
}
```

## Exemplos de Uso Completos

### Listar Restaurantes com Filtros

```javascript
import { apiClient } from './api/client';

async function getRestaurants(filters = {}) {
  try {
    const response = await apiClient.get('/restaurants', {
      per_page: 20,
      orderby: 'rating',
      order: 'desc',
      ...filters
    });
    
    return response.data;
  } catch (error) {
    console.error('Erro ao buscar restaurantes:', error);
    return [];
  }
}

// Uso
const restaurants = await getRestaurants({
  cuisine: 'brasileira',
  has_delivery: true,
  is_open: true
});
```

### Obter Cardápio Completo de um Restaurante

```javascript
async function getRestaurantMenu(restaurantId) {
  try {
    const [restaurant, menuItems, categories] = await Promise.all([
      apiClient.get(`/restaurants/${restaurantId}`),
      apiClient.get(`/restaurants/${restaurantId}/menu-items`),
      apiClient.get(`/restaurants/${restaurantId}/menu-categories`)
    ]);

    return {
      restaurant,
      items: menuItems.data,
      categories: categories.data
    };
  } catch (error) {
    console.error('Erro ao buscar cardápio:', error);
    throw error;
  }
}
```

### Cotação de Frete

```javascript
async function getShippingQuote(restaurantId, subtotal, address) {
  try {
    const quote = await apiClient.get('/shipping/quote', {
      restaurant_id: restaurantId,
      subtotal: subtotal,
      lat: address.lat,
      lng: address.lng,
      address: address.full,
      neighborhood: address.neighborhood
    });

    return quote;
  } catch (error) {
    console.error('Erro ao calcular frete:', error);
    throw error;
  }
}
```

### Criar Avaliação

```javascript
async function createReview(restaurantId, rating, comment, orderId = null) {
  try {
    // Certifique-se de que o token está configurado
    const token = localStorage.getItem('auth_token');
    apiClient.setAuthToken(token);

    const review = await apiClient.post('/reviews', {
      restaurant_id: restaurantId,
      rating: rating,
      comment: comment,
      order_id: orderId
    });

    return review;
  } catch (error) {
    if (error.status === 401) {
      // Redirecionar para login
      window.location.href = '/login';
    }
    throw error;
  }
}
```

## Autenticação

### WordPress Application Passwords

Para requisições autenticadas, você pode usar WordPress Application Passwords:

1. No WordPress Admin, vá em **Usuários → Seu Perfil**
2. Role até **Application Passwords**
3. Crie uma nova senha de aplicativo
4. Use o token gerado no header `Authorization: Basic {base64(username:password)}`

### Exemplo de Autenticação

```javascript
import { apiClient } from './api/client';

// Configurar autenticação
function setAuth(username, appPassword) {
  const token = btoa(`${username}:${appPassword}`);
  apiClient.setAuthToken(token);
}

// Usar
setAuth('seu-usuario', 'xxxx xxxx xxxx xxxx xxxx xxxx');
```

## Boas Práticas

1. **Cache de Requisições**: Considere implementar cache para requisições que não mudam frequentemente (ex: lista de restaurantes)

2. **Paginação**: Sempre use paginação para listas grandes

3. **Loading States**: Sempre mostre estados de carregamento durante requisições

4. **Error Boundaries**: Use React Error Boundaries para capturar erros de API

5. **Retry Logic**: Implemente retry para requisições que falharam por problemas de rede

6. **TypeScript**: Use TypeScript para type safety e melhor autocomplete

## Troubleshooting

### CORS Errors

Se você receber erros de CORS:
1. Verifique se a origem está na lista de permitidas no WordPress
2. Verifique se o header `Access-Control-Allow-Origin` está sendo enviado
3. Use o filtro `vemcomer_rest_allowed_origins` para adicionar sua origem

### 404 Not Found

- Verifique se a URL da API está correta
- Verifique se o endpoint existe na documentação
- Verifique se o ID do recurso é válido

### 401 Unauthorized

- Verifique se o token de autenticação está correto
- Verifique se o token não expirou
- Verifique se o usuário tem permissão para acessar o recurso

### Rate Limiting

Se você receber erro 429:
- Aguarde o tempo especificado em `retry_after`
- Implemente backoff exponencial
- Considere reduzir a frequência de requisições

## Recursos Adicionais

- [Documentação Completa da API](./API_ENDPOINTS.md)
- [Repositório no GitHub](https://github.com/Staryannfree/vemcomer-core)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [React Query](https://tanstack.com/query/latest) - Biblioteca recomendada para gerenciamento de estado de API

## Links Úteis

- **Repositório**: https://github.com/Staryannfree/vemcomer-core
- **Documentação da API**: https://github.com/Staryannfree/vemcomer-core/blob/main/docs/API_ENDPOINTS.md
- **Este Guia**: https://github.com/Staryannfree/vemcomer-core/blob/main/docs/LOVABLE_INTEGRATION.md
