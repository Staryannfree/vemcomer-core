# Tema Pedevem

Tema WordPress completo para o marketplace Pedevem.

## Instalação

1. **Copie o tema para o WordPress:**
   ```bash
   cp -r theme-vemcomer /caminho/para/wordpress/wp-content/themes/vemcomer
   ```

2. **Ative o tema:**
   - Vá em **Aparência ▸ Temas**
   - Encontre **Pedevem**
   - Clique em **Ativar**

3. **Ative o plugin vemcomer-core:**
   - Vá em **Plugins ▸ Plugins Instalados**
   - Ative **Pedevem Core**

## Estrutura

```
theme-vemcomer/
├── style.css              # Arquivo principal do tema (obrigatório)
├── functions.php          # Funções do tema
├── header.php             # Cabeçalho
├── footer.php             # Rodapé
├── index.php              # Template padrão (fallback)
├── front-page.php         # Página inicial
├── page.php               # Páginas genéricas
├── single-vc-restaurant.php  # Página de restaurante
├── archive-vc-restaurant.php # Arquivo de restaurantes
├── assets/
│   ├── css/
│   │   └── main.css       # Estilos principais
│   └── js/
│       └── main.js        # JavaScript principal
└── README.md              # Este arquivo
```

## Funcionalidades

- ✅ Header com menu e área de usuário
- ✅ Footer com widgets e links
- ✅ Página inicial completa (front-page.php)
- ✅ Suporte a todos os shortcodes do vemcomer-core
- ✅ Estilos responsivos
- ✅ Menu mobile
- ✅ Tabs funcionais
- ✅ Scroll suave

## Personalização

### Cores

Edite `assets/css/main.css` e altere as variáveis CSS:

```css
:root {
    --color-primary: #2f9e44;
    --color-primary-dark: #1e7e34;
    /* ... */
}
```

### Menus

1. Vá em **Aparência ▸ Menus**
2. Crie um menu e atribua à localização **Menu Principal**
3. Crie outro menu para **Menu Rodapé**

### Widgets

1. Vá em **Aparência ▸ Widgets**
2. Adicione widgets às áreas:
   - Sidebar Principal
   - Rodapé 1, 2, 3

### Logo

1. Vá em **Aparência ▸ Personalizar**
2. Selecione **Identidade do Site**
3. Faça upload do logo

## Compatibilidade

- WordPress 6.0+
- PHP 8.0+
- Plugin vemcomer-core (recomendado)

## Suporte

Para dúvidas, consulte a documentação do plugin vemcomer-core.

