# Templates da Home Page

O tema VemComer suporta múltiplos templates para a página inicial, permitindo que você escolha o design que melhor se adequa ao seu marketplace.

## Templates Disponíveis

### 1. `front-page.php` (Padrão)
Template original da home page com design limpo e funcional.

**Características:**
- Hero section com busca
- Botão de geolocalização
- Categorias populares
- Restaurantes em destaque
- Lista completa de restaurantes
- Mapa interativo
- Seção "Para você" (favoritos e pedidos)
- CTA para restaurantes

### 2. `front-page-v2.php` (Design Elaborado)
Template alternativo com design mais elaborado e completo.

**Características:**
- Hero section com background image
- Barra de busca estilizada
- Filtros rápidos expandidos
- Categorias populares com ícones
- Destaques do dia
- Restaurantes em destaque
- Ranking dos melhores
- Seção Premium
- Mapa de restaurantes
- Eventos & Agenda
- Parceiros & Serviços
- Blog & Dicas
- Sidebar com estatísticas, ações do usuário, newsletter, FAQ
- Promoção de app
- Footer completo

## Como Alternar Entre Templates

### Método 1: Constante no wp-config.php (Recomendado)

Adicione no arquivo `wp-config.php` (antes da linha `/* That's all, stop editing! */`):

```php
// Usar template v2 da home
define( 'VC_HOME_TEMPLATE_V2', true );
```

Para voltar ao template padrão, remova a linha ou defina como `false`:

```php
define( 'VC_HOME_TEMPLATE_V2', false );
```

### Método 2: Filtro WordPress

Adicione no `functions.php` do tema filho ou em um plugin:

```php
// Usar template v2
add_filter( 'vemcomer_home_template_version', function() {
    return 'front-page-v2.php';
} );

// Ou voltar ao padrão
add_filter( 'vemcomer_home_template_version', function() {
    return 'front-page.php';
} );
```

### Método 3: Opção do Tema (Futuro)

Em uma versão futura, será possível alternar via Customizer do WordPress.

## Funcionalidades Mantidas

Ambos os templates mantêm todas as funcionalidades do tema:

- ✅ Geolocalização
- ✅ Busca inteligente
- ✅ Filtros rápidos
- ✅ Cards de restaurantes
- ✅ Favoritos
- ✅ Modo escuro
- ✅ Responsividade
- ✅ Integração com shortcodes do plugin

## Personalização

### Personalizar o Template V2

O template `front-page-v2.php` inclui estilos inline. Para personalizar:

1. **Criar um tema filho** (recomendado)
2. **Copiar `front-page-v2.php`** para o tema filho
3. **Modificar os estilos** conforme necessário
4. **Ou criar um CSS separado** e enfileirar via `functions.php`

### Exemplo de Personalização

```php
// No functions.php do tema filho
function meu_tema_enqueue_styles() {
    wp_enqueue_style( 
        'home-v2-custom', 
        get_stylesheet_directory_uri() . '/css/home-v2-custom.css',
        [],
        '1.0.0'
    );
}
add_action( 'wp_enqueue_scripts', 'meu_tema_enqueue_styles' );
```

## Compatibilidade

- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ Navegadores modernos (Chrome, Firefox, Safari, Edge)
- ✅ Responsivo (mobile, tablet, desktop)
- ✅ Compatível com plugin vemcomer-core

## Suporte

Para dúvidas ou problemas:
1. Verifique se o template existe em `theme-vemcomer/`
2. Verifique se a constante está definida corretamente
3. Limpe o cache do WordPress e do navegador
4. Verifique os logs de erro do WordPress

## Notas

- O template v2 usa Font Awesome via CDN
- Ambos os templates são totalmente responsivos
- As funcionalidades JavaScript são compartilhadas
- Os shortcodes do plugin funcionam em ambos os templates

